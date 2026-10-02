<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class RepairSaudeFamiliaBranchMigration extends Command
{
    protected $signature = 'repair:saude-familia-branch-migration
                            {--dry-run : Simula sem gravar}
                            {--chunk=500 : Registos por lote}
                            {--old-branch-id=14 : ID do ramo no legado}
                            {--branch-code=18 : Código do ramo (legado/novo)}
                            {--quotation-number= : Limitar a uma cotação específica}
                            {--only=* : Passos específicos (templates,template_coverage,risks,template_code)}';

    protected $description = 'Repara migração do ramo SAÚDE FAMÍLIA (templates, coberturas e template_code)';

    private string $OLD = 'mysql_old';
    private string $NEW = 'mysql';

    private ?int $oldBranchId = null;
    private ?int $newBranchId = null;

    /** @var array<int, int> */
    private array $versionMap = []; // old quotation_versions.id => new quo_quotation_versions.id
    /** @var array<int, int> */
    private array $objectMap = []; // old quotation_insured_objects.id => new obj_objects.id
    /** @var array<int, int> */
    private array $coverageMap = []; // old coverages.id => new cfg_coverages.id
    /** @var array<int, int> */
    private array $templateMap = []; // old templates.id => new cfg_templates.id
    /** @var array<int, int> */
    private array $deductibleMap = []; // old deductibles.id => new cfg_deductibles.id

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $only = array_values(array_filter((array) $this->option('only')));
        $steps = !empty($only) ? $only : ['templates', 'template_coverage', 'risks', 'template_code'];

        $this->info('Reparação dedicada do ramo SAÚDE FAMÍLIA');
        if ($dryRun) {
            $this->warn('Modo DRY-RUN ativo (sem gravação).');
        }

        if (!$this->testConnections()) {
            return self::FAILURE;
        }

        if (!$this->resolveBranches()) {
            return self::FAILURE;
        }

        $this->loadMappings();

        if (in_array('templates', $steps, true)) {
            $this->repairTemplates($dryRun);
            $this->reloadTemplateMap();
        }
        if (in_array('template_coverage', $steps, true)) {
            $this->repairTemplateCoverage($dryRun);
        }
        if (in_array('risks', $steps, true)) {
            $this->repairRisks($dryRun);
        }
        if (in_array('template_code', $steps, true)) {
            $this->repairTemplateCodeOnVersions($dryRun);
        }

        $this->info('Reparação do ramo SAÚDE FAMÍLIA concluída.');
        return self::SUCCESS;
    }

    private function testConnections(): bool
    {
        foreach ([$this->OLD, $this->NEW] as $conn) {
            try {
                DB::connection($conn)->getPdo();
            } catch (Throwable $e) {
                $this->error("Falha na conexão {$conn}: " . $e->getMessage());
                return false;
            }
        }
        return true;
    }

    private function resolveBranches(): bool
    {
        $oldBranchIdOpt = (int) $this->option('old-branch-id');
        $branchCode = (int) $this->option('branch-code');

        $oldBranch = DB::connection($this->OLD)
            ->table('insurance_branches')
            ->where('id', $oldBranchIdOpt)
            ->where('code', $branchCode)
            ->first(['id', 'code', 'name', 'slug']);

        if (!$oldBranch) {
            $oldBranch = DB::connection($this->OLD)
                ->table('insurance_branches')
                ->where('code', $branchCode)
                ->orderBy('id')
                ->first(['id', 'code', 'name', 'slug']);
        }

        if (!$oldBranch) {
            $this->error("Ramo legado não encontrado para code={$branchCode}.");
            return false;
        }

        $newBranchId = DB::connection($this->NEW)
            ->table('cfg_insurance_branches')
            ->where('code', $branchCode)
            ->value('id');

        if (!$newBranchId) {
            $this->error("Ramo novo (cfg_insurance_branches) não encontrado para code={$branchCode}.");
            return false;
        }

        $this->oldBranchId = (int) $oldBranch->id;
        $this->newBranchId = (int) $newBranchId;

        $this->line(sprintf(
            'Ramo alvo: old_id=%d code=%s name=%s | new_id=%d',
            $this->oldBranchId,
            (string) $oldBranch->code,
            (string) $oldBranch->name,
            $this->newBranchId
        ));

        return true;
    }

    private function loadMappings(): void
    {
        $this->line('Carregando mapeamentos para SAÚDE FAMÍLIA...');
        $this->reloadTemplateMap();
        $this->loadCoverageMap();
        $this->loadVersionMap();
        $this->loadObjectMap();
        $this->loadDeductibleMap();

        $this->line(sprintf(
            'Mapas: versions=%d objects=%d coverages=%d templates=%d deductibles=%d',
            count($this->versionMap),
            count($this->objectMap),
            count($this->coverageMap),
            count($this->templateMap),
            count($this->deductibleMap)
        ));
    }

    private function reloadTemplateMap(): void
    {
        $this->templateMap = [];

        DB::connection($this->OLD)
            ->table('templates')
            ->where('insurance_branche_id', $this->oldBranchId)
            ->select('id', 'code')
            ->orderBy('id')
            ->chunk((int) $this->option('chunk'), function ($rows) {
                foreach ($rows as $oldTemplate) {
                    $newTemplateId = DB::connection($this->NEW)
                        ->table('cfg_templates')
                        ->where('insurance_branch_id', $this->newBranchId)
                        ->where('code', (int) $oldTemplate->code)
                        ->value('id');

                    if ($newTemplateId) {
                        $this->templateMap[(int) $oldTemplate->id] = (int) $newTemplateId;
                    }
                }
            });
    }

    private function loadCoverageMap(): void
    {
        $this->coverageMap = [];

        DB::connection($this->OLD)
            ->table('coverages')
            ->where('insurance_branch_id', $this->oldBranchId)
            ->select('id', 'insurance_branch_id', 'seq', 'short_name', 'name', 'gis_cod', 'type_cov')
            ->orderBy('id')
            ->chunk((int) $this->option('chunk'), function ($rows) {
                foreach ($rows as $oldCoverage) {
                    $newCoverageId = $this->resolveNewCoverageId((array) $oldCoverage);
                    if ($newCoverageId) {
                        $this->coverageMap[(int) $oldCoverage->id] = $newCoverageId;
                    }
                }
            });
    }

    private function loadVersionMap(): void
    {
        $this->versionMap = [];
        $quotationNumber = $this->option('quotation-number');

        DB::connection($this->OLD)
            ->table('quotation_versions as ov')
            ->join('quotations as oq', 'oq.id', '=', 'ov.quotation_id')
            ->where('oq.insurance_branche_id', $this->oldBranchId)
            ->when(!empty($quotationNumber), fn($q) => $q->where('oq.quotation_number', $quotationNumber))
            ->select('ov.id', 'ov.version', 'ov.table', 'oq.quotation_number')
            ->orderBy('ov.id')
            ->chunk((int) $this->option('chunk'), function ($rows) {
                foreach ($rows as $oldVersion) {
                    $newQuotationId = DB::connection($this->NEW)
                        ->table('quo_quotations')
                        ->where('quotation_number', $oldVersion->quotation_number)
                        ->value('id');
                    if (!$newQuotationId) {
                        continue;
                    }

                    $newVersionId = DB::connection($this->NEW)
                        ->table('quo_quotation_versions')
                        ->where('quotation_id', $newQuotationId)
                        ->where('version', (string) $oldVersion->version)
                        ->where('table_code', $oldVersion->table)
                        ->value('id');

                    if ($newVersionId) {
                        $this->versionMap[(int) $oldVersion->id] = (int) $newVersionId;
                    }
                }
            });
    }

    private function loadObjectMap(): void
    {
        $this->objectMap = [];
        if (empty($this->versionMap)) {
            return;
        }

        DB::connection($this->OLD)
            ->table('quotation_line_items as qli')
            ->join('quotation_insured_objects as oio', 'oio.id', '=', 'qli.insured_object_id')
            ->whereIn('qli.quotation_version_id', array_keys($this->versionMap))
            ->select('oio.id', 'oio.slug')
            ->distinct()
            ->orderBy('oio.id')
            ->chunk((int) $this->option('chunk'), function ($rows) {
                foreach ($rows as $oldObject) {
                    $newObjectId = DB::connection($this->NEW)
                        ->table('obj_objects')
                        ->where('slug', $oldObject->slug)
                        ->value('id');
                    if ($newObjectId) {
                        $this->objectMap[(int) $oldObject->id] = (int) $newObjectId;
                    }
                }
            });
    }

    private function loadDeductibleMap(): void
    {
        $this->deductibleMap = [];
        if (empty($this->coverageMap)) {
            return;
        }

        DB::connection($this->OLD)
            ->table('deductibles')
            ->whereIn('coverage_id', array_keys($this->coverageMap))
            ->select('id', 'coverage_id', 'deduct_type', 'deduct_object')
            ->orderBy('id')
            ->chunk((int) $this->option('chunk'), function ($rows) {
                foreach ($rows as $oldDeductible) {
                    $newCoverageId = $this->coverageMap[(int) $oldDeductible->coverage_id] ?? null;
                    if (!$newCoverageId) {
                        continue;
                    }

                    $newDeductibleId = DB::connection($this->NEW)
                        ->table('cfg_deductibles')
                        ->where('coverage_id', $newCoverageId)
                        ->where('deduct_type', $oldDeductible->deduct_type)
                        ->where('deduct_object', $oldDeductible->deduct_object)
                        ->value('id');

                    if ($newDeductibleId) {
                        $this->deductibleMap[(int) $oldDeductible->id] = (int) $newDeductibleId;
                    }
                }
            });
    }

    private function repairTemplates(bool $dryRun): void
    {
        $this->info('Passo 1: Reparar cfg_templates do ramo SAÚDE FAMÍLIA');

        $inserted = 0;
        $skipped = 0;

        DB::connection($this->OLD)
            ->table('templates')
            ->where('insurance_branche_id', $this->oldBranchId)
            ->select('id', 'code', 'name', 'description', 'package', 'object_type', 'qty_objects', 'active', 'slug', 'created_at', 'updated_at', 'deleted_at')
            ->orderBy('id')
            ->chunk((int) $this->option('chunk'), function ($rows) use (&$inserted, &$skipped, $dryRun) {
                foreach ($rows as $oldTemplate) {
                    $existsId = DB::connection($this->NEW)
                        ->table('cfg_templates')
                        ->where('insurance_branch_id', $this->newBranchId)
                        ->where('code', (int) $oldTemplate->code)
                        ->value('id');

                    if ($existsId) {
                        $skipped++;
                        continue;
                    }

                    if (!$dryRun) {
                        DB::connection($this->NEW)
                            ->table('cfg_templates')
                            ->insert([
                                'insurance_branch_id' => $this->newBranchId,
                                'code' => (int) $oldTemplate->code,
                                'name' => (string) ($oldTemplate->name ?? ('TPL-' . $oldTemplate->code)),
                                'description' => $oldTemplate->description,
                                'package' => $oldTemplate->package,
                                'object_type' => $oldTemplate->object_type,
                                'qty_objects' => $oldTemplate->qty_objects,
                                'active' => $oldTemplate->active ?? 1,
                                'slug' => $this->uniqueSlug((string) ($oldTemplate->slug ?? ('tpl-sf-' . $oldTemplate->id)), 'cfg_templates'),
                                'created_by' => null,
                                'updated_by' => null,
                                'created_at' => $oldTemplate->created_at ?? now(),
                                'updated_at' => $oldTemplate->updated_at ?? now(),
                                'deleted_at' => $oldTemplate->deleted_at,
                            ]);
                    }

                    $inserted++;
                }
            });

        $this->line("templates => inseridos={$inserted}, ignorados={$skipped}");
    }

    private function repairTemplateCoverage(bool $dryRun): void
    {
        $this->info('Passo 2: Reparar cfg_template_coverage do ramo SAÚDE FAMÍLIA');

        $inserted = 0;
        $skipped = 0;
        $missing = 0;

        DB::connection($this->OLD)
            ->table('coverages')
            ->where('insurance_branch_id', $this->oldBranchId)
            ->whereNotNull('template_code')
            ->select('id', 'insurance_branch_id', 'template_code', 'type_covt', 'cov_opt', 'seq', 'gis_cod')
            ->orderBy('id')
            ->chunk((int) $this->option('chunk'), function ($rows) use (&$inserted, &$skipped, &$missing, $dryRun) {
                foreach ($rows as $oldCoverage) {
                    $newCoverageId = $this->coverageMap[(int) $oldCoverage->id] ?? null;
                    if (!$newCoverageId) {
                        $missing++;
                        continue;
                    }

                    $oldTemplateId = DB::connection($this->OLD)
                        ->table('templates')
                        ->where('insurance_branche_id', $this->oldBranchId)
                        ->where('code', (int) $oldCoverage->template_code)
                        ->value('id');
                    $newTemplateId = $oldTemplateId ? ($this->templateMap[(int) $oldTemplateId] ?? null) : null;

                    if (!$newTemplateId) {
                        $missing++;
                        continue;
                    }

                    $exists = DB::connection($this->NEW)
                        ->table('cfg_template_coverage')
                        ->where('template_id', $newTemplateId)
                        ->where('coverage_id', $newCoverageId)
                        ->exists();

                    if ($exists) {
                        $skipped++;
                        continue;
                    }

                    if (!$dryRun) {
                        DB::connection($this->NEW)
                            ->table('cfg_template_coverage')
                            ->insert([
                                'template_id' => $newTemplateId,
                                'coverage_id' => $newCoverageId,
                                'is_mandatory' => ($oldCoverage->type_covt === 'O' || $oldCoverage->cov_opt === 'O') ? 1 : 0,
                                'display_order' => $oldCoverage->seq > 0 ? $oldCoverage->seq : ((int) ($oldCoverage->gis_cod ?? 1)),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                    }

                    $inserted++;
                }
            });

        $this->line("template_coverage => inseridos={$inserted}, ignorados={$skipped}, faltantes={$missing}");
    }

    private function repairRisks(bool $dryRun): void
    {
        $this->info('Passo 3: Reparar quo_object_risk (coberturas de cotações) do ramo SAÚDE FAMÍLIA');

        $inserted = 0;
        $skipped = 0;
        $missingMap = 0;
        $quotationNumber = $this->option('quotation-number');

        DB::connection($this->OLD)
            ->table('quotation_line_items as qli')
            ->join('quotation_versions as ov', 'ov.id', '=', 'qli.quotation_version_id')
            ->join('quotations as oq', 'oq.id', '=', 'ov.quotation_id')
            ->where('oq.insurance_branche_id', $this->oldBranchId)
            ->when(!empty($quotationNumber), fn($q) => $q->where('oq.quotation_number', $quotationNumber))
            ->select([
                'qli.id',
                'qli.slug',
                'qli.quotation_version_id',
                'qli.insured_object_id',
                'qli.coverage_id',
                'qli.capital_value',
                'qli.coverage_perc',
                'qli.applied_rate',
                'qli.coverage_premium',
                'qli.rate_origin',
                'qli.rate_id',
                'qli.deductible_id',
                'qli.created_at',
                'qli.updated_at',
                'qli.deleted_at',
            ])
            ->orderBy('qli.id')
            ->chunk((int) $this->option('chunk'), function ($rows) use (&$inserted, &$skipped, &$missingMap, $dryRun) {
                foreach ($rows as $old) {
                    $newVersionId = $this->versionMap[(int) $old->quotation_version_id] ?? null;
                    $newObjectId = $this->objectMap[(int) $old->insured_object_id] ?? null;
                    $newCoverageId = $this->coverageMap[(int) $old->coverage_id] ?? null;

                    if (!$newVersionId || !$newObjectId || !$newCoverageId) {
                        $missingMap++;
                        continue;
                    }

                    $exists = DB::connection($this->NEW)
                        ->table('quo_object_risk')
                        ->where('quotation_version_id', $newVersionId)
                        ->where('object_id', $newObjectId)
                        ->where('coverage_id', $newCoverageId)
                        ->exists();

                    if ($exists) {
                        $skipped++;
                        continue;
                    }

                    if (!$dryRun) {
                        $newRiskId = DB::connection($this->NEW)
                            ->table('quo_object_risk')
                            ->insertGetId([
                                'quotation_version_id' => $newVersionId,
                                'object_id' => $newObjectId,
                                'coverage_id' => $newCoverageId,
                                'object_risk_capital_id' => null,
                                'dp_capital' => null,
                                'rc_capital' => null,
                                'building_capital' => null,
                                'content_capital' => null,
                                'exploration_capital' => null,
                                'capital' => $this->sanitizeDecimal($old->capital_value),
                                'coverage_perc' => $this->sanitizeDecimal($old->coverage_perc) ?? 0,
                                'applied_rate' => $this->sanitizeDecimal($old->applied_rate) ?? 0,
                                'coverage_premium' => $this->sanitizeDecimal($old->coverage_premium) ?? 0,
                                'rate_origin' => $old->rate_origin ?? null,
                                'rate_id' => $this->resolveHealthRateId($old->rate_id, (int) $old->coverage_id),
                                'discount_percent' => 0.00,
                                'surcharge_percent' => 0.00,
                                'manual_rate' => null,
                                'slug' => $this->uniqueSlug((string) ($old->slug ?: ('qli-sf-' . $old->id)), 'quo_object_risk'),
                                'created_at' => $old->created_at,
                                'updated_at' => $old->updated_at,
                                'deleted_at' => $old->deleted_at,
                            ]);

                        if (!empty($old->deductible_id)) {
                            $newDeductibleId = $this->deductibleMap[(int) $old->deductible_id] ?? null;
                            if ($newDeductibleId) {
                                DB::connection($this->NEW)
                                    ->table('quo_object_risk_deductibles')
                                    ->insert([
                                        'object_risk_id' => $newRiskId,
                                        'deductible_id' => $newDeductibleId,
                                        'applied_value' => null,
                                        'applied_percent' => null,
                                        'slug' => $this->uniqueSlug('ded-sf-' . $newRiskId . '-' . $newDeductibleId, 'quo_object_risk_deductibles'),
                                        'created_at' => $old->created_at,
                                        'updated_at' => $old->updated_at,
                                        'deleted_at' => $old->deleted_at,
                                    ]);
                            }
                        }
                    }

                    $inserted++;
                }
            });

        $this->line("risks => inseridos={$inserted}, ignorados={$skipped}, faltantes_map={$missingMap}");
    }

    private function repairTemplateCodeOnVersions(bool $dryRun): void
    {
        $this->info('Passo 4: Reparar template_code em versões do ramo SAÚDE FAMÍLIA');

        $updated = 0;
        $skipped = 0;
        $missing = 0;

        foreach ($this->versionMap as $oldVersionId => $newVersionId) {
            $currentTemplateCode = DB::connection($this->NEW)
                ->table('quo_quotation_versions')
                ->where('id', $newVersionId)
                ->value('template_code');

            if (!empty($currentTemplateCode)) {
                $skipped++;
                continue;
            }

            $oldTemplateCode = DB::connection($this->OLD)
                ->table('quotation_line_items as qli')
                ->join('coverages as c', 'c.id', '=', 'qli.coverage_id')
                ->where('qli.quotation_version_id', $oldVersionId)
                ->whereNotNull('c.template_code')
                ->orderBy('qli.id')
                ->value('c.template_code');

            if (!$oldTemplateCode) {
                $missing++;
                continue;
            }

            $templateExists = DB::connection($this->NEW)
                ->table('cfg_templates')
                ->where('insurance_branch_id', $this->newBranchId)
                ->where('code', (int) $oldTemplateCode)
                ->exists();

            if (!$templateExists) {
                $missing++;
                continue;
            }

            if (!$dryRun) {
                DB::connection($this->NEW)
                    ->table('quo_quotation_versions')
                    ->where('id', $newVersionId)
                    ->update([
                        'template_code' => (string) $oldTemplateCode,
                        'updated_at' => now(),
                    ]);
            }

            $updated++;
        }

        $this->line("template_code => atualizados={$updated}, ignorados={$skipped}, faltantes={$missing}");
    }

    private function resolveNewCoverageId(array $oldCoverage): ?int
    {
        $seq = $oldCoverage['seq'] ?? null;
        $shortName = $oldCoverage['short_name'] ?? null;
        $name = $oldCoverage['name'] ?? null;
        $gisCod = $oldCoverage['gis_cod'] ?? null;
        $typeCov = $oldCoverage['type_cov'] ?? null;

        $q1 = DB::connection($this->NEW)
            ->table('cfg_coverages')
            ->where('insurance_branch_id', $this->newBranchId)
            ->where('seq', $seq);
        if (!empty($shortName)) {
            $q1->where('short_name', $shortName);
        }
        if (!empty($typeCov)) {
            $q1->where('type_cov', $typeCov);
        }
        if (!empty($gisCod)) {
            $q1->where('gis_cod', $gisCod);
        }
        $id = $q1->value('id');
        if ($id) {
            return (int) $id;
        }

        if (!empty($shortName)) {
            $id = DB::connection($this->NEW)
                ->table('cfg_coverages')
                ->where('insurance_branch_id', $this->newBranchId)
                ->where('short_name', $shortName)
                ->orderBy('id')
                ->value('id');
            if ($id) {
                return (int) $id;
            }
        }

        if (!empty($name)) {
            $id = DB::connection($this->NEW)
                ->table('cfg_coverages')
                ->where('insurance_branch_id', $this->newBranchId)
                ->where('name', $name)
                ->orderBy('id')
                ->value('id');
            if ($id) {
                return (int) $id;
            }
        }

        if (!empty($seq)) {
            $id = DB::connection($this->NEW)
                ->table('cfg_coverages')
                ->where('insurance_branch_id', $this->newBranchId)
                ->where('seq', $seq)
                ->orderBy('id')
                ->value('id');
            if ($id) {
                return (int) $id;
            }
        }

        return null;
    }

    private function resolveHealthRateId(mixed $oldRateId, int $oldCoverageId): ?int
    {
        if (empty($oldRateId)) {
            return null;
        }

        $oldRate = DB::connection($this->OLD)
            ->table('rates_healths')
            ->where('id', (int) $oldRateId)
            ->first([
                'insurance_branch_id',
                'coverage_id',
                'age_min',
                'age_max',
                'gender',
                'type',
                'package',
                'premium_value',
            ]);

        if (!$oldRate || (int) $oldRate->insurance_branch_id !== $this->oldBranchId) {
            return null;
        }

        $resolvedOldCoverageId = !empty($oldRate->coverage_id) ? (int) $oldRate->coverage_id : $oldCoverageId;
        $newCoverageId = $this->coverageMap[$resolvedOldCoverageId] ?? null;

        $q = DB::connection($this->NEW)
            ->table('cfg_rates_health')
            ->where('insurance_branch_id', $this->newBranchId)
            ->where('age_min', $oldRate->age_min)
            ->where('age_max', $oldRate->age_max)
            ->where('gender', $oldRate->gender)
            ->where('type', $oldRate->type)
            ->where('package', $oldRate->package)
            ->where('premium_value', $oldRate->premium_value);

        if ($newCoverageId) {
            $q->where(function ($x) use ($newCoverageId) {
                $x->where('coverage_id', $newCoverageId)
                    ->orWhereNull('coverage_id');
            });
        }

        $id = $q->value('id');
        return $id ? (int) $id : null;
    }

    private function sanitizeDecimal(mixed $value, float $maxAllowed = 999_999_999_999.99): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        $float = (float) $value;
        if (!is_finite($float) || abs($float) > $maxAllowed) {
            return null;
        }
        return $float;
    }

    private function uniqueSlug(string $base, string $table): string
    {
        $slug = Str::slug($base);
        if ($slug === '') {
            $slug = 'item-' . uniqid();
        }

        $original = $slug;
        $counter = 1;
        while (DB::connection($this->NEW)->table($table)->where('slug', $slug)->exists()) {
            $slug = $original . '-' . $counter++;
        }
        return $slug;
    }
}
