<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class RepairHealthMigrationData extends Command
{
    protected $signature = 'repair:health-migration-data
                            {--dry-run : Simula sem gravar}
                            {--chunk=500 : Registos por lote}
                            {--only=* : Passos específicos (templates,coverages,template_coverage)}
                            {--allow-create-missing-template-codes : Permite criar templates ausentes a partir de coverages.template_code}
                            {--cleanup-generated-template-codes : Remove templates gerados automaticamente (slug tpl-health-code-*)}';

    protected $description = 'Repara lacunas da migração de Saúde em dados de configuração (templates/coberturas)';

    private string $OLD = 'mysql_old';
    private string $NEW = 'mysql';

    /** @var array<int, int> */
    private array $branchMap = []; // old insurance_branches.id => new cfg_insurance_branches.id (saúde)
    /** @var array<int, int> */
    private array $coverageMap = []; // old coverages.id => new cfg_coverages.id
    /** @var array<int, int> */
    private array $templateMap = []; // old templates.id => new cfg_templates.id
    /** @var array<string, int> */
    private array $templateCodeMap = []; // "oldBranchId|template_code" => new cfg_templates.id
    private int $dryRunTemplateId = -1;
    private int $dryRunCoverageId = -1;
    /** @var array<string, bool> */
    private array $oldColumnCache = [];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $only = array_values(array_filter((array) $this->option('only')));
        $steps = !empty($only) ? $only : ['templates', 'coverages', 'template_coverage'];
        $allowedSteps = ['templates', 'coverages', 'template_coverage'];
        $steps = array_values(array_intersect($steps, $allowedSteps));

        $this->info('Reparação da migração de Saúde (somente configuração)');
        if ($dryRun) {
            $this->warn('Modo DRY-RUN ativo (sem gravação).');
        }

        if (!$this->testConnections()) {
            return self::FAILURE;
        }

        $this->loadHealthMappings();

        if ((bool) $this->option('cleanup-generated-template-codes')) {
            $this->cleanupGeneratedTemplateCodes($dryRun);
            $this->loadHealthMappings();
        }

        if (empty($this->branchMap)) {
            $this->warn('Nenhum ramo de Saúde (código 18/20) foi encontrado para reparação.');
            return self::SUCCESS;
        }

        if (empty($steps)) {
            $this->warn('Nenhum passo de configuração válido informado. Use: templates, coverages, template_coverage');
            return self::SUCCESS;
        }

        if (in_array('templates', $steps, true)) {
            $this->repairHealthTemplates($dryRun);
        }
        if (in_array('coverages', $steps, true)) {
            $this->repairHealthCoverages($dryRun);
        }
        if (in_array('template_coverage', $steps, true)) {
            $this->repairTemplateCoverage($dryRun);
        }

        $this->info('Reparação de Saúde concluída.');
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

    private function loadHealthMappings(): void
    {
        $this->line('Carregando mapeamentos base de Saúde...');

        $oldHealthBranches = DB::connection($this->OLD)
            ->table('insurance_branches')
            ->whereIn('code', [18, 20])
            ->get(['id', 'code']);

        foreach ($oldHealthBranches as $oldBranch) {
            $newBranchId = DB::connection($this->NEW)
                ->table('cfg_insurance_branches')
                ->where('code', $oldBranch->code)
                ->value('id');

            if ($newBranchId) {
                $this->branchMap[(int) $oldBranch->id] = (int) $newBranchId;
            }
        }

        // old template id -> new template id (health only)
        DB::connection($this->OLD)
            ->table('templates')
            ->whereIn('insurance_branche_id', array_keys($this->branchMap))
            ->select('id', 'insurance_branche_id', 'code')
            ->orderBy('id')
            ->chunk((int) $this->option('chunk'), function ($rows) {
                foreach ($rows as $oldTemplate) {
                    $newBranchId = $this->branchMap[(int) $oldTemplate->insurance_branche_id] ?? null;
                    if (!$newBranchId) {
                        continue;
                    }

                    $newTemplateId = DB::connection($this->NEW)
                        ->table('cfg_templates')
                        ->where('insurance_branch_id', $newBranchId)
                        ->where('code', (string) $oldTemplate->code)
                        ->value('id');

                    if ($newTemplateId) {
                        $this->templateMap[(int) $oldTemplate->id] = (int) $newTemplateId;
                        $this->setTemplateCodeMap((int) $oldTemplate->insurance_branche_id, (int) $oldTemplate->code, (int) $newTemplateId);
                    }
                }
            });

        // coverage map com fallback mais permissivo para saúde
        DB::connection($this->OLD)
            ->table('coverages')
            ->whereIn('insurance_branch_id', array_keys($this->branchMap))
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

        $this->line(sprintf(
            'Mapas: branches=%d coverages=%d templates=%d',
            count($this->branchMap),
            count($this->coverageMap),
            count($this->templateMap)
        ));
    }

    private function repairTemplateCoverage(bool $dryRun): void
    {
        $this->info('Passo 3: Reparar cfg_template_coverage (Saúde)');

        $inserted = 0;
        $skipped = 0;
        $missing = 0;

        $process = function () use (&$inserted, &$skipped, &$missing, $dryRun): void {
            DB::connection($this->OLD)
                ->table('coverages')
                ->whereIn('insurance_branch_id', array_keys($this->branchMap))
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

                        $newTemplateId = $this->resolveNewTemplateIdByCode(
                            (int) $oldCoverage->insurance_branch_id,
                            (int) $oldCoverage->template_code
                        );
                        if (!$newTemplateId) {
                            $missing++;
                            continue;
                        }

                        if ($dryRun) {
                            if ($newTemplateId > 0 && $newCoverageId > 0) {
                                $exists = DB::connection($this->NEW)
                                    ->table('cfg_template_coverage')
                                    ->where('template_id', $newTemplateId)
                                    ->where('coverage_id', $newCoverageId)
                                    ->exists();
                                if ($exists) {
                                    $skipped++;
                                    continue;
                                }
                            }
                            $inserted++;
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

                        DB::connection($this->NEW)
                            ->table('cfg_template_coverage')
                            ->insert([
                                'template_id' => $newTemplateId,
                                'coverage_id' => $newCoverageId,
                                'is_mandatory' => ($oldCoverage->type_covt === 'O' || $oldCoverage->cov_opt === 'O') ? 1 : 0,
                                'display_order' => $oldCoverage->seq > 0 ? $oldCoverage->seq : ((int) ($oldCoverage->gis_cod ?? 0)),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);

                        $inserted++;
                    }
                });
        };

        if ($dryRun) {
            $process();
        } else {
            DB::connection($this->NEW)->transaction(function () use ($process) {
                $process();
            });
        }

        $this->line("template_coverage => inseridos={$inserted}, ignorados={$skipped}, faltantes={$missing}");
    }

    private function repairHealthTemplates(bool $dryRun): void
    {
        $this->info('Passo 1: Reparar cfg_templates (Saúde)');

        $inserted = 0;
        $skipped = 0;
        $missing = 0;

        $process = function () use (&$inserted, &$skipped, &$missing, $dryRun): void {
            $templateColumns = ['id', 'insurance_branche_id', 'code'];
            $optionalColumns = [
                'name',
                'description',
                'package',
                'object_type',
                'type_obj',
                'qty_objects',
                'qty_obj',
                'active',
                'slug',
                'created_at',
                'updated_at',
                'deleted_at',
            ];
            foreach ($optionalColumns as $column) {
                if ($this->oldHasColumn('templates', $column)) {
                    $templateColumns[] = $column;
                }
            }

            DB::connection($this->OLD)
                ->table('templates')
                ->whereIn('insurance_branche_id', array_keys($this->branchMap))
                ->select($templateColumns)
                ->orderBy('id')
                ->chunk((int) $this->option('chunk'), function ($rows) use (&$inserted, &$skipped, &$missing, $dryRun) {
                    foreach ($rows as $oldTemplate) {
                        $oldTemplateId = (int) $oldTemplate->id;
                        $newBranchId = $this->branchMap[(int) $oldTemplate->insurance_branche_id] ?? null;
                        if (!$newBranchId) {
                            $missing++;
                            continue;
                        }

                        $existingTemplateId = DB::connection($this->NEW)
                            ->table('cfg_templates')
                            ->where('insurance_branch_id', $newBranchId)
                            ->where('code', (int) $oldTemplate->code)
                            ->value('id');

                        if ($existingTemplateId) {
                            $this->templateMap[$oldTemplateId] = (int) $existingTemplateId;
                            $this->setTemplateCodeMap((int) $oldTemplate->insurance_branche_id, (int) $oldTemplate->code, (int) $existingTemplateId);
                            $skipped++;
                            continue;
                        }

                        if ($dryRun) {
                            $fakeTemplateId = $this->dryRunTemplateId--;
                            $this->templateMap[$oldTemplateId] = $fakeTemplateId;
                            $this->setTemplateCodeMap((int) $oldTemplate->insurance_branche_id, (int) $oldTemplate->code, $fakeTemplateId);
                            $inserted++;
                            continue;
                        }

                        $legacyObjectType = $oldTemplate->object_type ?? $oldTemplate->type_obj ?? null;
                        $legacyQtyObjects = $oldTemplate->qty_objects ?? $oldTemplate->qty_obj ?? null;

                        $newTemplateId = DB::connection($this->NEW)
                            ->table('cfg_templates')
                            ->insertGetId([
                                'insurance_branch_id' => $newBranchId,
                                'code' => (int) $oldTemplate->code,
                                'name' => substr((string) (($oldTemplate->name ?? null) ?: ('Template ' . $oldTemplate->code)), 0, 100),
                                'description' => $oldTemplate->description ?? null,
                                'package' => !empty($oldTemplate->package) ? substr((string) $oldTemplate->package, 0, 50) : null,
                                'object_type' => !empty($legacyObjectType) ? substr((string) $legacyObjectType, 0, 50) : null,
                                'qty_objects' => $legacyQtyObjects,
                                'active' => (int) (($oldTemplate->active ?? 1)) === 1 ? 1 : 0,
                                'slug' => $this->uniqueSlug((string) (($oldTemplate->slug ?? null) ?: ('tpl-health-' . $oldTemplateId)), 'cfg_templates'),
                                'created_by' => null,
                                'updated_by' => null,
                                'created_at' => $oldTemplate->created_at ?? now(),
                                'updated_at' => $oldTemplate->updated_at ?? now(),
                                'deleted_at' => $oldTemplate->deleted_at ?? null,
                            ]);

                        $this->templateMap[$oldTemplateId] = (int) $newTemplateId;
                        $this->setTemplateCodeMap((int) $oldTemplate->insurance_branche_id, (int) $oldTemplate->code, (int) $newTemplateId);
                        $inserted++;
                    }
                });

            if ((bool) $this->option('allow-create-missing-template-codes')) {
                // Opcional: garantir templates para template_code usados nas coberturas do modelo antigo.
                DB::connection($this->OLD)
                    ->table('coverages')
                    ->whereIn('insurance_branch_id', array_keys($this->branchMap))
                    ->whereNotNull('template_code')
                    ->select('insurance_branch_id', 'template_code')
                    ->distinct()
                    ->orderBy('insurance_branch_id')
                    ->orderBy('template_code')
                    ->chunk((int) $this->option('chunk'), function ($rows) use (&$inserted, &$skipped, &$missing, $dryRun) {
                        foreach ($rows as $row) {
                            $oldBranchId = (int) $row->insurance_branch_id;
                            $templateCode = (int) $row->template_code;
                            $newBranchId = $this->branchMap[$oldBranchId] ?? null;
                            if (!$newBranchId) {
                                $missing++;
                                continue;
                            }

                            $key = $this->templateCodeKey($oldBranchId, $templateCode);
                            if (isset($this->templateCodeMap[$key])) {
                                $skipped++;
                                continue;
                            }

                            $existingTemplateId = DB::connection($this->NEW)
                                ->table('cfg_templates')
                                ->where('insurance_branch_id', $newBranchId)
                                ->where('code', $templateCode)
                                ->value('id');
                            if ($existingTemplateId) {
                                $this->setTemplateCodeMap($oldBranchId, $templateCode, (int) $existingTemplateId);
                                $skipped++;
                                continue;
                            }

                            if ($dryRun) {
                                $this->setTemplateCodeMap($oldBranchId, $templateCode, $this->dryRunTemplateId--);
                                $inserted++;
                                continue;
                            }

                            $newTemplateId = DB::connection($this->NEW)
                                ->table('cfg_templates')
                                ->insertGetId([
                                    'insurance_branch_id' => $newBranchId,
                                    'code' => $templateCode,
                                    'name' => substr('Template ' . $templateCode, 0, 100),
                                    'description' => null,
                                    'package' => null,
                                    'object_type' => null,
                                    'qty_objects' => null,
                                    'active' => 1,
                                    'slug' => $this->uniqueSlug('tpl-health-code-' . $oldBranchId . '-' . $templateCode, 'cfg_templates'),
                                    'created_by' => null,
                                    'updated_by' => null,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                    'deleted_at' => null,
                                ]);

                            $this->setTemplateCodeMap($oldBranchId, $templateCode, (int) $newTemplateId);
                            $inserted++;
                        }
                    });
            }
        };

        if ($dryRun) {
            $process();
        } else {
            DB::connection($this->NEW)->transaction(function () use ($process) {
                $process();
            });
        }

        $this->line("templates => inseridos={$inserted}, ignorados={$skipped}, faltantes={$missing}");
    }

    private function repairHealthCoverages(bool $dryRun): void
    {
        $this->info('Passo 2: Reparar cfg_coverages (Saúde)');

        $inserted = 0;
        $skipped = 0;
        $missing = 0;

        $process = function () use (&$inserted, &$skipped, &$missing, $dryRun): void {
            DB::connection($this->OLD)
                ->table('coverages')
                ->whereIn('insurance_branch_id', array_keys($this->branchMap))
                ->select([
                    'id',
                    'insurance_branch_id',
                    'group_id',
                    'seq',
                    'gis_cod',
                    'type_cov',
                    'short_name',
                    'name',
                    'description',
                    'obj_type',
                    'capital_value',
                    'capital_origin',
                    'rate_type',
                    'risk_rate',
                    'risk_rate_origin',
                    'cov_opt',
                    'type_covt',
                    'cov_perc',
                    'active',
                    'slug',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                ])
                ->orderByRaw('COALESCE(group_id, 0), id')
                ->chunk((int) $this->option('chunk'), function ($rows) use (&$inserted, &$skipped, &$missing, $dryRun) {
                    foreach ($rows as $oldCoverage) {
                        $oldCoverageArray = (array) $oldCoverage;
                        $oldCoverageId = (int) $oldCoverage->id;
                        $newBranchId = $this->branchMap[(int) $oldCoverage->insurance_branch_id] ?? null;
                        if (!$newBranchId) {
                            $missing++;
                            continue;
                        }

                        $existingCoverageId = $this->resolveNewCoverageId($oldCoverageArray);
                        if ($existingCoverageId) {
                            $this->coverageMap[$oldCoverageId] = $existingCoverageId;
                            $skipped++;
                            continue;
                        }

                        $newGroupId = null;
                        if (!empty($oldCoverage->group_id)) {
                            $oldGroupId = (int) $oldCoverage->group_id;
                            $newGroupId = $this->coverageMap[$oldGroupId] ?? null;

                            if (!$newGroupId) {
                                $oldGroupCoverage = DB::connection($this->OLD)
                                    ->table('coverages')
                                    ->where('id', $oldGroupId)
                                    ->first(['id', 'insurance_branch_id', 'seq', 'short_name', 'name', 'gis_cod', 'type_cov']);
                                if ($oldGroupCoverage) {
                                    $newGroupId = $this->resolveNewCoverageId((array) $oldGroupCoverage);
                                }
                            }
                        }

                        if ($dryRun) {
                            $this->coverageMap[$oldCoverageId] = $this->dryRunCoverageId--;
                            $inserted++;
                            continue;
                        }

                        $newCoverageId = DB::connection($this->NEW)
                            ->table('cfg_coverages')
                            ->insertGetId([
                                'insurance_branch_id' => $newBranchId,
                                'group_id' => $newGroupId,
                                'seq' => (int) $oldCoverage->seq,
                                'gis_cod' => !empty($oldCoverage->gis_cod) ? (int) $oldCoverage->gis_cod : null,
                                'type_cov' => substr((string) $oldCoverage->type_cov, 0, 50),
                                'short_name' => $oldCoverage->short_name ? substr((string) $oldCoverage->short_name, 0, 50) : null,
                                'name' => $oldCoverage->name ? substr((string) $oldCoverage->name, 0, 255) : null,
                                'description' => $oldCoverage->description ? substr((string) $oldCoverage->description, 0, 255) : null,
                                'obj_type' => $oldCoverage->obj_type ? substr((string) $oldCoverage->obj_type, 0, 50) : null,
                                'capital_value' => is_numeric($oldCoverage->capital_value) ? (float) $oldCoverage->capital_value : null,
                                'capital_origin' => $oldCoverage->capital_origin ? substr((string) $oldCoverage->capital_origin, 0, 100) : null,
                                'rate_type' => $oldCoverage->rate_type ? substr((string) $oldCoverage->rate_type, 0, 50) : null,
                                'risk_rate' => is_numeric($oldCoverage->risk_rate) ? (float) $oldCoverage->risk_rate : null,
                                'risk_rate_origin' => $oldCoverage->risk_rate_origin ? substr((string) $oldCoverage->risk_rate_origin, 0, 50) : null,
                                'cov_opt' => $oldCoverage->cov_opt ? substr((string) $oldCoverage->cov_opt, 0, 50) : null,
                                'type_covt' => $oldCoverage->type_covt ? substr((string) $oldCoverage->type_covt, 0, 50) : null,
                                'cov_perc' => is_numeric($oldCoverage->cov_perc) ? (float) $oldCoverage->cov_perc : null,
                                'active' => (int) ($oldCoverage->active ?? 1) === 1 ? 1 : 0,
                                'slug' => $this->uniqueSlug((string) ($oldCoverage->slug ?: ('cov-health-' . $oldCoverageId)), 'cfg_coverages'),
                                'created_at' => $oldCoverage->created_at ?? now(),
                                'updated_at' => $oldCoverage->updated_at ?? now(),
                                'deleted_at' => $oldCoverage->deleted_at,
                            ]);

                        $this->coverageMap[$oldCoverageId] = (int) $newCoverageId;
                        $inserted++;
                    }
                });
        };

        if ($dryRun) {
            $process();
        } else {
            DB::connection($this->NEW)->transaction(function () use ($process) {
                $process();
            });
        }

        $this->line("coverages => inseridos={$inserted}, ignorados={$skipped}, faltantes={$missing}");
    }

    private function resolveNewCoverageId(array $oldCoverage): ?int
    {
        $oldBranchId = (int) ($oldCoverage['insurance_branch_id'] ?? 0);
        $newBranchId = $this->branchMap[$oldBranchId] ?? null;
        if (!$newBranchId) {
            return null;
        }

        $seq = $oldCoverage['seq'] ?? null;
        $shortName = $this->normalizeString($oldCoverage['short_name'] ?? null);
        $name = $this->normalizeString($oldCoverage['name'] ?? null);
        $gisCod = $oldCoverage['gis_cod'] ?? null;
        $typeCov = $this->normalizeString($oldCoverage['type_cov'] ?? null);

        // 1) Match forte
        $q1 = DB::connection($this->NEW)
            ->table('cfg_coverages')
            ->where('insurance_branch_id', $newBranchId)
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

        // 2) short_name + branch
        if (!empty($shortName)) {
            $id = DB::connection($this->NEW)
                ->table('cfg_coverages')
                ->where('insurance_branch_id', $newBranchId)
                ->whereRaw('TRIM(short_name) = ?', [$shortName])
                ->orderBy('id')
                ->value('id');
            if ($id) {
                return (int) $id;
            }
        }

        // 3) name + branch
        if (!empty($name)) {
            $id = DB::connection($this->NEW)
                ->table('cfg_coverages')
                ->where('insurance_branch_id', $newBranchId)
                ->whereRaw('TRIM(name) = ?', [$name])
                ->orderBy('id')
                ->value('id');
            if ($id) {
                return (int) $id;
            }
        }

        // 4) gis_cod + branch
        if (!empty($gisCod)) {
            $id = DB::connection($this->NEW)
                ->table('cfg_coverages')
                ->where('insurance_branch_id', $newBranchId)
                ->where('gis_cod', $gisCod)
                ->orderBy('id')
                ->value('id');
            if ($id) {
                return (int) $id;
            }
        }

        // 5) seq + branch
        if (!empty($seq)) {
            $id = DB::connection($this->NEW)
                ->table('cfg_coverages')
                ->where('insurance_branch_id', $newBranchId)
                ->where('seq', $seq)
                ->orderBy('id')
                ->value('id');
            if ($id) {
                return (int) $id;
            }
        }

        return null;
    }

    private function normalizeString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);
        return $normalized === '' ? null : $normalized;
    }

    private function resolveNewTemplateIdByCode(int $oldBranchId, int $templateCode): ?int
    {
        $key = $this->templateCodeKey($oldBranchId, $templateCode);
        if (isset($this->templateCodeMap[$key])) {
            return $this->templateCodeMap[$key];
        }

        $newBranchId = $this->branchMap[$oldBranchId] ?? null;
        if (!$newBranchId) {
            return null;
        }

        $id = DB::connection($this->NEW)
            ->table('cfg_templates')
            ->where('insurance_branch_id', $newBranchId)
            ->where('code', $templateCode)
            ->value('id');
        if (!$id) {
            return null;
        }

        $this->setTemplateCodeMap($oldBranchId, $templateCode, (int) $id);
        return (int) $id;
    }

    private function setTemplateCodeMap(int $oldBranchId, int $templateCode, int $templateId): void
    {
        $this->templateCodeMap[$this->templateCodeKey($oldBranchId, $templateCode)] = $templateId;
    }

    private function templateCodeKey(int $oldBranchId, int $templateCode): string
    {
        return $oldBranchId . '|' . $templateCode;
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

    private function oldHasColumn(string $table, string $column): bool
    {
        $key = $table . '.' . $column;
        if (array_key_exists($key, $this->oldColumnCache)) {
            return $this->oldColumnCache[$key];
        }

        $exists = Schema::connection($this->OLD)->hasColumn($table, $column);
        $this->oldColumnCache[$key] = $exists;
        return $exists;
    }

    private function cleanupGeneratedTemplateCodes(bool $dryRun): void
    {
        $this->info('Limpeza: remover templates gerados automaticamente (tpl-health-code-*)');

        if (empty($this->branchMap)) {
            $this->line('cleanup_generated_templates => removidos=0');
            return;
        }

        $query = DB::connection($this->NEW)
            ->table('cfg_templates')
            ->whereIn('insurance_branch_id', array_values($this->branchMap))
            ->where('slug', 'like', 'tpl-health-code-%');

        $count = (clone $query)->count();
        if ($dryRun) {
            $this->line("cleanup_generated_templates => removeria={$count}");
            return;
        }

        DB::connection($this->NEW)->transaction(function () use ($query) {
            $query->delete();
        });

        $this->line("cleanup_generated_templates => removidos={$count}");
    }

}
