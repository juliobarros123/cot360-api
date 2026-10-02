<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class MigrateCfgData extends Command
{
    protected $signature = 'migrate:cfg-data
                            {--fresh    : Limpa as tabelas de destino antes de migrar}
                            {--only=*  : Migra apenas tabelas específicas}
                            {--dry-run : Simula a migração sem gravar nada}
                            {--chunk=200 : Número de registos por lote}';

    protected $description = 'Migra dados CFG do banco antigo (omni_insurance_v2) para o novo modelo';

    private string $OLD = 'mysql_old';
    private string $NEW = 'mysql';
    private array $stats = [];

    // Mapas old_id → new_id
    private array $branchMap = [];
    private array $templateMap = [];
    private array $coverageMap = [];

    public function handle(): int
    {
        $this->info('');
        $this->info('╔══════════════════════════════════════════════════╗');
        $this->info('║   OMNI INSURANCE v2 — Migração CFG               ║');
        $this->info('╚══════════════════════════════════════════════════╝');
        $this->info('');

        if ($this->option('dry-run')) {
            $this->warn('⚠  MODO DRY-RUN — nenhum dado será gravado.');
            $this->info('');
        }

        if (!$this->testConnections()) {
            return self::FAILURE;
        }

        if ($this->option('fresh') && !$this->option('dry-run')) {
            if (!$this->confirm('⚠  --fresh irá APAGAR todos os dados CFG no banco novo. Confirmas?')) {
                return self::SUCCESS;
            }
            $this->truncateCfgTables();
        }

        // Carregar mapeamentos existentes primeiro
        $this->loadExistingMappings();

        $only = $this->option('only');
        $pipelines = $this->buildPipelines();

        // Ordem correta: branches → templates → coverages → resto
        // Ordem correta: branches → templates → coverages → resto
        $order = [
            'branches',
            'templates',
            'coverages',
            'deductibles',
            'special_conditions',
            'rates_automobile',  // ← Adicionado
            'rates_cae',
            'rates_health',
            'rates_occupants'
        ];

        foreach ($order as $key) {
            if (!empty($only) && !in_array($key, $only)) {
                continue;
            }
            if (isset($pipelines[$key])) {
                $this->runPipeline($key, $pipelines[$key]);
            }
        }

        // Template coverage depois que tudo está mapeado
        if (empty($only) || in_array('template_coverage', $only)) {
            $this->runTemplateCoverage();
        }

        $this->printSummary();
        return self::SUCCESS;
    }

    private function loadExistingMappings(): void
    {
        $this->info('Carregando mapeamentos existentes...');

        // Carregar branches existentes - MAPEAR POR CÓDIGO
        $branches = DB::connection($this->NEW)
            ->table('cfg_insurance_branches')
            ->get(['id', 'code']);

        foreach ($branches as $branch) {
            $this->branchMap[$branch->code] = $branch->id;
        }

        $this->info(sprintf('✓ %d branches carregados', count($this->branchMap)));

        // Carregar templates existentes
        $templates = DB::connection($this->NEW)
            ->table('cfg_templates')
            ->get(['id', 'insurance_branch_id', 'code']);

        foreach ($templates as $template) {
            $key = $template->insurance_branch_id . '|' . $template->code;
            $this->templateMap[$key] = $template->id;
        }

        $this->info('✓ Mapeamentos carregados');
        $this->info('');
    }
    private function buildPipelines(): array
    {
        return [
            'branches' => [
                'label' => 'insurance_branches → cfg_insurance_branches',
                'source' => 'insurance_branches',
                'target' => 'cfg_insurance_branches',
                'map' => function (object $old): ?array {
                    return [
                        'code' => $old->code,
                        'name' => $old->name,
                        'description' => $old->description ?? null,
                        'requires_coordinates' => 0,
                        'default_validity_days' => 30,
                        'policy_number_prefix' => null,
                        'active' => $old->active ?? 1,
                        'slug' => $this->generateUniqueSlug($old->slug, 'cfg_insurance_branches'),
                        'created_by' => null,
                        'updated_by' => null,
                        'created_at' => $old->created_at,
                        'updated_at' => $old->updated_at,
                        'deleted_at' => $old->deleted_at ?? null,
                    ];
                },
                'find_existing' => function (object $old): ?int {
                    return DB::connection($this->NEW)
                        ->table('cfg_insurance_branches')
                        ->where('code', $old->code)
                        ->value('id');
                },
                'after_insert' => function (object $old, int $newId): void {
                    $this->branchMap[$old->code] = $newId;
                },
            ],

            'templates' => [
                'label' => 'templates → cfg_templates',
                'source' => 'templates',
                'target' => 'cfg_templates',
                'map' => function (object $old): ?array {
                    $newBranchId = $this->branchMap[$old->insurance_branche_id] ?? null;

                    // Tentar encontrar branch pelo código se não estiver no map
                    if (!$newBranchId) {
                        $branchCode = DB::connection($this->OLD)
                            ->table('insurance_branches')
                            ->where('id', $old->insurance_branche_id)
                            ->value('code');

                        if ($branchCode) {
                            $newBranchId = DB::connection($this->NEW)
                                ->table('cfg_insurance_branches')
                                ->where('code', $branchCode)
                                ->value('id');
                        }
                    }

                    if (!$newBranchId) {
                        $this->warn("  skip template id={$old->id}: branch_id={$old->insurance_branche_id} não encontrado");
                        return null;
                    }

                    return [
                        'insurance_branch_id' => $newBranchId,
                        'code' => $old->code,
                        'name' => $old->name,
                        'description' => $old->description ?? null,
                        'package' => $old->package ?? null,
                        'object_type' => $old->type_obj ?? null,
                        'qty_objects' => $old->qty_obj ?? null,
                        'active' => $old->active ?? 1,
                        'slug' => $this->generateUniqueSlug($old->slug, 'cfg_templates'),
                        'created_by' => null,
                        'updated_by' => null,
                        'created_at' => $old->created_at,
                        'updated_at' => $old->updated_at,
                        'deleted_at' => $old->deleted_at ?? null,
                    ];
                },
                'find_existing' => function (object $old): ?int {
                    $newBranchId = $this->branchMap[$old->insurance_branche_id] ?? null;
                    if (!$newBranchId) {
                        return null;
                    }

                    return DB::connection($this->NEW)
                        ->table('cfg_templates')
                        ->where('insurance_branch_id', $newBranchId)
                        ->where('code', $old->code)
                        ->value('id');
                },
                'after_insert' => function (object $old, int $newId): void {
                    $newBranchId = $this->branchMap[$old->insurance_branche_id] ?? null;
                    if ($newBranchId) {
                        $this->templateMap[$newBranchId . '|' . $old->code] = $newId;
                    }
                },
            ],

            'coverages' => [
                'label' => 'coverages → cfg_coverages',
                'source' => 'coverages',
                'target' => 'cfg_coverages',
                'order_by' => 'COALESCE(group_id, 0), id',
                'map' => function (object $old): ?array {
                    $newBranchId = $this->branchMap[$old->insurance_branch_id] ?? null;

                    if (!$newBranchId) {
                        $branchCode = DB::connection($this->OLD)
                            ->table('insurance_branches')
                            ->where('id', $old->insurance_branch_id)
                            ->value('code');

                        if ($branchCode) {
                            $newBranchId = DB::connection($this->NEW)
                                ->table('cfg_insurance_branches')
                                ->where('code', $branchCode)
                                ->value('id');
                        }
                    }

                    if (!$newBranchId) {
                        $this->warn("  skip coverage id={$old->id}: branch_id={$old->insurance_branch_id} não encontrado");
                        return null;
                    }

                    $newGroupId = null;
                    if (!empty($old->group_id)) {
                        // Encontrar grupo pelo id antigo
                        $groupCoverage = DB::connection($this->OLD)
                            ->table('coverages')
                            ->where('id', $old->group_id)
                            ->first();

                        if ($groupCoverage) {
                            $newGroupId = $this->resolveNewCoverageId($groupCoverage);
                        }
                    }

                    return [
                        'insurance_branch_id' => $newBranchId,
                        'group_id' => $newGroupId,
                        'seq' => $old->seq,
                        'gis_cod' => $old->gis_cod ?? null,
                        'type_cov' => $old->type_cov,
                        'short_name' => $old->short_name ?? null,
                        'name' => $old->name ?? null,
                        'description' => $old->description ?? null,
                        'obj_type' => $old->obj_type ?? null,
                        'capital_value' => $old->capital_value ?? null,
                        'capital_origin' => $old->capital_origin ?? null,
                        'rate_type' => $old->rate_type ?? null,
                        'risk_rate' => $old->risk_rate ?? null,
                        'risk_rate_origin' => $old->risk_rate_origin ?? null,
                        'cov_opt' => $old->cov_opt ?? null,
                        'type_covt' => $old->type_covt ?? null,
                        'cov_perc' => $old->cov_perc ?? null,
                        'active' => $old->active ?? 1,
                        'slug' => $this->generateUniqueSlug($old->slug, 'cfg_coverages'),
                        'created_at' => $old->created_at,
                        'updated_at' => $old->updated_at,
                        'deleted_at' => $old->deleted_at ?? null,
                    ];
                },
                'find_existing' => function (object $old): ?int {
                    $newBranchId = $this->branchMap[$old->insurance_branch_id] ?? null;
                    if (!$newBranchId) {
                        return null;
                    }

                    return $this->findExistingCoverageId($newBranchId, $old);
                },
                'after_insert' => function (object $old, int $newId): void {
                    // Mapear por old_id para uso em outras tabelas
                    $this->coverageMap[$old->id] = $newId;
                },
            ],

            'deductibles' => [
                'label' => 'deductibles → cfg_deductibles',
                'source' => 'deductibles',
                'target' => 'cfg_deductibles',
                'map' => function (object $old): ?array {
                    $newCoverageId = null;

                    if (!empty($old->coverage_id)) {
                        // Encontrar coverage pelo id antigo
                        $coverage = DB::connection($this->OLD)
                            ->table('coverages')
                            ->where('id', $old->coverage_id)
                            ->first();

                        if ($coverage) {
                            $newCoverageId = $this->resolveNewCoverageId($coverage);
                        }
                    }

                    return [
                        'coverage_id' => $newCoverageId,
                        'deduct_type' => $old->deduct_type,
                        'deduct_object' => $old->deduct_object,
                        'deduct_percent' => $old->deduct_percent ?? null,
                        'deduct_value' => $old->deduct_value ?? null,
                        'deduct_min' => $old->deduct_min ?? null,
                        'deduct_max' => $old->deduct_max ?? null,
                        'discount' => null,
                        'details' => $old->details ?? null,
                        'slug' => $this->generateUniqueSlug($old->slug, 'cfg_deductibles'),
                        'created_at' => $old->created_at,
                        'updated_at' => $old->updated_at,
                        'deleted_at' => $old->deleted_at ?? null,
                    ];
                },
            ],

            'special_conditions' => [
                'label' => 'special_conditions → cfg_special_conditions',
                'source' => 'special_conditions',
                'target' => 'cfg_special_conditions',
                'map' => function (object $old): ?array {
                    $newBranchId = $this->branchMap[$old->insurance_branche_id] ?? null;

                    if (!$newBranchId) {
                        $branchCode = DB::connection($this->OLD)
                            ->table('insurance_branches')
                            ->where('id', $old->insurance_branche_id)
                            ->value('code');

                        if ($branchCode) {
                            $newBranchId = DB::connection($this->NEW)
                                ->table('cfg_insurance_branches')
                                ->where('code', $branchCode)
                                ->value('id');
                        }
                    }

                    if (!$newBranchId) {
                        $this->warn("  skip special_condition id={$old->id}: branch não encontrado");
                        return null;
                    }

                    return [
                        'insurance_branch_id' => $newBranchId,
                        'code' => 'SC' . $old->id,
                        'condition_type' => 'Clause',
                        'short_text' => $old->short_text,
                        'long_text' => $old->long_text ?? null,
                        'active' => 1,
                        'slug' => $this->generateUniqueSlug($old->slug, 'cfg_special_conditions'),
                        'created_at' => $old->created_at,
                        'updated_at' => $old->updated_at,
                        'deleted_at' => $old->deleted_at ?? null,
                    ];
                },
            ],
            'rates_automobile' => [
                'label' => 'rates_automobiles → cfg_rates_automobile',
                'source' => 'rates_automobiles',
                'target' => 'cfg_rates_automobile',
                'order_by' => 'code, created_at', // Ordenar por código e data de criação
                'map' => function (object $old): ?array {
                    // Buscar o branch no banco antigo
                    $branch = DB::connection($this->OLD)
                        ->table('insurance_branches')
                        ->where('id', $old->insurance_branch_id)
                        ->first();

                    $newBranchId = null;

                    // Tentar encontrar o branch pelo código
                    if ($branch && !empty($branch->code)) {
                        $newBranchId = $this->branchMap[$branch->code] ?? null;
                    }

                    // Se não encontrou, tentar pelo ID
                    if (!$newBranchId && $branch) {
                        $newBranchId = DB::connection($this->NEW)
                            ->table('cfg_insurance_branches')
                            ->where('code', $branch->code)
                            ->value('id');
                    }

                    // Log para debug (em dry-run ou ambiente local)
                    if (!$newBranchId && app()->environment('local')) {
                        $this->warn("  rates_automobile id={$old->id}: branch_id={$old->insurance_branch_id} não mapeado");
                    }

                    return [
                        'insurance_branch_id' => $newBranchId, // Pode ser null, mas já estamos tratando
                        'code' => substr($old->code ?? '', 0, 20),
                        'description' => $old->description ?? null,
                        'vehicle_type' => $old->vehicle_type ?? null,
                        'category' => $old->category ?? null,
                        'engine_min' => $old->engine_min ?? null,
                        'engine_max' => $old->engine_max ?? null,
                        'seat_min' => $old->seat_min ?? null,
                        'seat_max' => $old->seat_max ?? null,
                        'weight_min' => $old->weight_min ?? null,
                        'weight_max' => $old->weight_max ?? null,
                        'min_rc_legal_ucf' => $old->min_rc_legal_ucf ?? null,
                        'min_rc_limit_kz' => $old->min_rc_limit_kz ?? null,
                        'rc_premium_kz' => $old->rc_premium_kz ?? null,
                        'ccc_percent' => $old->ccc_percent ?? null,
                        'ire_percent' => $old->ire_percent ?? null,
                        'fr_percent' => $old->fr_percent ?? null,
                        'av_percent' => $old->av_percent ?? null,
                        'fn_percent' => $old->fn_percent ?? null,
                        'gtaop_percent' => $old->gtaop_percent ?? null,
                        'active' => $old->active ?? 1,
                        'slug' => $this->generateUniqueSlug($old->slug ?? 'rate-' . uniqid(), 'cfg_rates_automobile'),
                        'created_at' => $old->created_at,
                        'updated_at' => $old->updated_at,
                        'deleted_at' => $old->deleted_at ?? null,
                    ];
                },
                'find_existing' => function (object $old): ?int {
                    // Buscar registros com mesmo código e mesma ordem de criação
                    return DB::connection($this->NEW)
                        ->table('cfg_rates_automobile')
                        ->where('code', substr($old->code ?? '', 0, 20))
                        ->where('created_at', $old->created_at)
                        ->value('id');
                },
                'after_insert' => function (object $old, int $newId): void {
                    // Opcional: guardar mapeamento se precisar referenciar depois
                    // $this->ratesAutoMap[$old->id] = $newId;
                },
            ],
            'rates_cae' => [
                'label' => 'cae_rates_branches → cfg_rates_cae',
                'source' => 'cae_rates_branches',
                'target' => 'cfg_rates_cae',
                'map' => function (object $old): ?array {
                    return [
                        'cae' => $old->cae,
                        'description' => $old->description ?? null,
                        'rate_mre_building' => $old->rate_mre_building ?? null,
                        'rate_mre_content' => $old->rate_mre_content ?? null,
                        'work_accident' => $old->work_accident ?? null,
                        'active' => $old->active ?? 1,
                        'slug' => $this->generateUniqueSlug($old->slug, 'cfg_rates_cae'),
                        'created_at' => $old->created_at,
                        'updated_at' => $old->updated_at,
                        'deleted_at' => $old->deleted_at ?? null,
                    ];
                },
            ],

            'rates_health' => [
                'label' => 'rates_healths → cfg_rates_health',
                'source' => 'rates_healths',
                'target' => 'cfg_rates_health',
                'map' => function (object $old): ?array {
                    $newCoverageId = null;

                    if (!empty($old->coverage_id)) {
                        $coverage = DB::connection($this->OLD)
                            ->table('coverages')
                            ->where('id', $old->coverage_id)
                            ->first();

                        if ($coverage) {
                            $newCoverageId = $this->resolveNewCoverageId($coverage);
                        }
                    }

                    return [
                        'insurance_branch_id' => null,
                        'coverage_id' => $newCoverageId,
                        'age_min' => $old->age_min ?? null,
                        'age_max' => $old->age_max ?? null,
                        'gender' => $old->gender ?? null,
                        'type' => $old->type ?? null,
                        'package' => $old->package ?? null,
                        'premium_value' => $old->premium_value ?? null,
                        'active' => $old->active ?? 1,
                        'slug' => $this->generateUniqueSlug($old->slug, 'cfg_rates_health'),
                        'created_at' => $old->created_at,
                        'updated_at' => $old->updated_at,
                        'deleted_at' => $old->deleted_at ?? null,
                    ];
                },
            ],

            'rates_occupants' => [
                'label' => 'rates_occupants → cfg_rates_occupants',
                'source' => 'rates_occupants',
                'target' => 'cfg_rates_occupants',
                'map' => function (object $old): ?array {
                    return [
                        'seat_min' => $old->seat_min,
                        'seat_max' => $old->seat_max,
                        'package' => $old->package,
                        'mip' => $old->mip,
                        'dt' => $old->dt,
                        'df' => $old->df,
                        'premium' => $old->premium,
                        'slug' => $this->generateUniqueSlug($old->slug, 'cfg_rates_occupants'),
                        'created_at' => $old->created_at,
                        'updated_at' => $old->updated_at,
                        'deleted_at' => $old->deleted_at ?? null,
                    ];
                },
            ],
        ];
    }

    private function resolveNewCoverageId(object $oldCoverage): ?int
    {
        if (isset($this->coverageMap[$oldCoverage->id])) {
            return $this->coverageMap[$oldCoverage->id];
        }

        $newBranchId = $this->branchMap[$oldCoverage->insurance_branch_id] ?? null;
        if (!$newBranchId) {
            $branchCode = DB::connection($this->OLD)
                ->table('insurance_branches')
                ->where('id', $oldCoverage->insurance_branch_id)
                ->value('code');

            if ($branchCode) {
                $newBranchId = DB::connection($this->NEW)
                    ->table('cfg_insurance_branches')
                    ->where('code', $branchCode)
                    ->value('id');
            }
        }

        if (!$newBranchId) {
            return null;
        }

        $resolvedId = $this->findExistingCoverageId($newBranchId, $oldCoverage);
        if ($resolvedId) {
            $this->coverageMap[$oldCoverage->id] = $resolvedId;
        }

        return $resolvedId;
    }

    private function findExistingCoverageId(int $newBranchId, object $oldCoverage): ?int
    {
        $q = DB::connection($this->NEW)
            ->table('cfg_coverages')
            ->where('insurance_branch_id', $newBranchId)
            ->where('seq', $oldCoverage->seq);

        if (!empty($oldCoverage->short_name)) {
            $q->where('short_name', $oldCoverage->short_name);
        }
        if (!empty($oldCoverage->type_cov)) {
            $q->where('type_cov', $oldCoverage->type_cov);
        }
        if (!empty($oldCoverage->gis_cod)) {
            $q->where('gis_cod', $oldCoverage->gis_cod);
        }
        if (empty($oldCoverage->short_name) && !empty($oldCoverage->name)) {
            $q->where('name', $oldCoverage->name);
        }

        return $q->value('id');
    }

    private function runTemplateCoverage(): void
    {
        $label = 'coverages → cfg_template_coverage';
        $dryRun = (bool) $this->option('dry-run');
        $this->info("▶  {$label}");

        $total = DB::connection($this->OLD)
            ->table('coverages')
            ->count();

        $inserted = 0;
        $skipped = 0;
        $errors = 0;

        $this->line("   Processando relações template-coverage...");
        $bar = $this->output->createProgressBar($total);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%%');
        $bar->start();

        DB::connection($this->OLD)
            ->table('coverages')
            ->orderBy('id')
            ->chunk((int) $this->option('chunk'), function ($coverages) use ($dryRun, &$inserted, &$skipped, &$errors, $bar) {
                foreach ($coverages as $coverage) {
                    try {
                        // Encontrar template correspondente
                        $template = DB::connection($this->OLD)
                            ->table('templates')
                            ->where('insurance_branche_id', $coverage->insurance_branch_id)
                            ->where('code', $coverage->template_code)
                            ->first();

                        if (!$template) {
                            $skipped++;
                            $bar->advance();
                            continue;
                        }

                        // Encontrar novos IDs
                        $newBranchId = $this->branchMap[$coverage->insurance_branch_id] ?? null;
                        if (!$newBranchId) {
                            $branchCode = DB::connection($this->OLD)
                                ->table('insurance_branches')
                                ->where('id', $coverage->insurance_branch_id)
                                ->value('code');

                            if ($branchCode) {
                                $newBranchId = DB::connection($this->NEW)
                                    ->table('cfg_insurance_branches')
                                    ->where('code', $branchCode)
                                    ->value('id');
                            }
                        }

                        if (!$newBranchId) {
                            $skipped++;
                            $bar->advance();
                            continue;
                        }

                        $newTemplateId = $this->templateMap[$newBranchId . '|' . $template->code] ?? null;
                        if (!$newTemplateId) {
                            $newTemplateId = DB::connection($this->NEW)
                                ->table('cfg_templates')
                                ->where('insurance_branch_id', $newBranchId)
                                ->where('code', $template->code)
                                ->value('id');
                        }
                        $newCoverageId = $this->resolveNewCoverageId($coverage);

                        if (!$newTemplateId || !$newCoverageId) {
                            $skipped++;
                            $bar->advance();
                            continue;
                        }

                        $isMandatory = ($coverage->type_covt === 'O' || $coverage->cov_opt === 'O') ? 1 : 0;
                        $displayOrder = $coverage->seq > 0 ? $coverage->seq : ($coverage->gis_cod ?? 0);

                        if (!$dryRun) {
                            $exists = DB::connection($this->NEW)
                                ->table('cfg_template_coverage')
                                ->where('template_id', $newTemplateId)
                                ->where('coverage_id', $newCoverageId)
                                ->exists();

                            if (!$exists) {
                                DB::connection($this->NEW)
                                    ->table('cfg_template_coverage')
                                    ->insert([
                                        'template_id' => $newTemplateId,
                                        'coverage_id' => $newCoverageId,
                                        'is_mandatory' => $isMandatory,
                                        'display_order' => $displayOrder,
                                        'created_at' => now(),
                                        'updated_at' => now(),
                                    ]);
                                $inserted++;
                            } else {
                                $skipped++;
                            }
                        } else {
                            $inserted++;
                        }
                    } catch (Throwable $e) {
                        $errors++;
                        $this->newLine();
                        $this->error("   ✗ coverage id={$coverage->id}: " . $e->getMessage());
                    }

                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine(2);

        $this->stats['template_coverage'] = [
            'label' => $label,
            'inserted' => $inserted,
            'skipped' => $skipped,
            'errors' => $errors,
        ];

        $color = $errors > 0 ? 'red' : ($skipped > 0 ? 'yellow' : 'green');
        $this->line("   <fg={$color}>✓ Inseridos: {$inserted} | Ignorados: {$skipped} | Erros: {$errors}</>");
        $this->info('');
    }

    private function runPipeline(string $key, array $pipeline): void
    {
        $label = $pipeline['label'];
        $source = $pipeline['source'];
        $target = $pipeline['target'];
        $mapFn = $pipeline['map'];
        $findExisting = $pipeline['find_existing'] ?? null;
        $afterFn = $pipeline['after_insert'] ?? null;
        $orderBy = $pipeline['order_by'] ?? 'id';
        $chunk = (int) $this->option('chunk');
        $dryRun = (bool) $this->option('dry-run');

        $this->info("▶  {$label}");

        if (!$this->tableExistsOld($source)) {
            $this->warn("   Tabela '{$source}' não encontrada no banco antigo — ignorado.");
            $this->stats[$key] = ['label' => $label, 'inserted' => 0, 'skipped' => 0, 'errors' => 0];
            return;
        }

        $total = DB::connection($this->OLD)->table($source)->count();
        $inserted = 0;
        $skipped = 0;
        $errors = 0;

        $this->line("   Registos no banco antigo: <fg=yellow>{$total}</>");

        $bar = $this->output->createProgressBar($total);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%%');
        $bar->start();

        $query = DB::connection($this->OLD)->table($source);

        // CORREÇÃO: Aplicar order by corretamente
        if ($orderBy !== 'id') {
            // Se for uma string complexa com SQL, usar orderByRaw
            $query->orderByRaw($orderBy);
        } else {
            // Simples, usar orderBy normal
            $query->orderBy($orderBy);
        }

        $query->chunk($chunk, function ($rows) use ($target, $mapFn, $findExisting, $afterFn, $dryRun, &$inserted, &$skipped, &$errors, $bar) {
            foreach ($rows as $old) {
                try {
                    // Verificar se já existe
                    if ($findExisting) {
                        $existingId = $findExisting($old);
                        if ($existingId) {
                            if ($afterFn) {
                                $afterFn($old, $existingId);
                            }
                            $skipped++;
                            $bar->advance();
                            continue;
                        }
                    }

                    $newData = $mapFn($old);

                    if ($newData === null) {
                        $skipped++;
                        $bar->advance();
                        continue;
                    }

                    if (!$dryRun) {
                        $newId = DB::connection($this->NEW)
                            ->table($target)
                            ->insertGetId($newData);

                        if ($afterFn) {
                            $afterFn($old, $newId);
                        }
                    } else {
                        if ($afterFn) {
                            $afterFn($old, $old->id);
                        }
                    }

                    $inserted++;
                } catch (Throwable $e) {
                    $errors++;
                    $this->newLine();
                    $this->error("   ✗ id={$old->id}: " . $e->getMessage());
                    if (app()->environment('local')) {
                        $this->line("      Data: " . json_encode($newData ?? []));
                    }
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        $this->stats[$key] = [
            'label' => $label,
            'inserted' => $inserted,
            'skipped' => $skipped,
            'errors' => $errors
        ];

        $color = $errors > 0 ? 'red' : ($skipped > 0 ? 'yellow' : 'green');
        $this->line("   <fg={$color}>✓ Inseridos: {$inserted} | Ignorados: {$skipped} | Erros: {$errors}</>");
        $this->info('');
    }
    private function generateUniqueSlug(string $base, string $table): string
    {
        $slug = Str::slug($base);
        if (empty($slug)) {
            $slug = 'item-' . uniqid();
        }

        $original = $slug;
        $counter = 1;

        while (DB::connection($this->NEW)->table($table)->where('slug', $slug)->exists()) {
            $slug = $original . '-' . $counter++;
        }

        return $slug;
    }

    private function testConnections(): bool
    {
        $this->line('Testando conexões...');

        try {
            DB::connection($this->OLD)->getPdo();
            $this->line('  <fg=green>✓ Banco ANTIGO: ' . config("database.connections.{$this->OLD}.database") . '</>');
        } catch (Throwable $e) {
            $this->error('  ✗ Banco ANTIGO falhou: ' . $e->getMessage());
            return false;
        }

        try {
            DB::connection($this->NEW)->getPdo();
            $this->line('  <fg=green>✓ Banco NOVO: ' . config("database.connections.{$this->NEW}.database") . '</>');
        } catch (Throwable $e) {
            $this->error('  ✗ Banco NOVO falhou: ' . $e->getMessage());
            return false;
        }

        $this->info('');
        return true;
    }

    private function tableExistsOld(string $table): bool
    {
        try {
            DB::connection($this->OLD)->table($table)->limit(1)->get();
            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function truncateCfgTables(): void
    {
        $this->warn('Limpando tabelas CFG no banco novo...');

        DB::connection($this->NEW)->statement('SET FOREIGN_KEY_CHECKS=0');

        $tables = [
            'cfg_template_coverage',
            'cfg_deductibles',
            'cfg_special_conditions',
            'cfg_rates_automobile',
            'cfg_rates_health',
            'cfg_rates_cae',
            'cfg_rates_occupants',
            'cfg_rates_rc_options',
            'cfg_coverages',
            'cfg_templates',
            'cfg_insurance_branches',
        ];

        foreach ($tables as $table) {
            try {
                DB::connection($this->NEW)->table($table)->truncate();
                $this->line("  truncated: {$table}");
            } catch (Throwable $e) {
                $this->warn("  não foi possível truncar {$table}: " . $e->getMessage());
            }
        }

        DB::connection($this->NEW)->statement('SET FOREIGN_KEY_CHECKS=1');
        $this->info('');
    }

    private function printSummary(): void
    {
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║   RESUMO                                                     ║');
        $this->info('╠══════════════════════════════════════════════════════════════╣');

        $totalInserted = 0;
        $totalSkipped = 0;
        $totalErrors = 0;

        foreach ($this->stats as $key => $stat) {
            $label = str_pad($stat['label'] ?? $key, 45);
            $color = $stat['errors'] > 0 ? 'red' : ($stat['skipped'] > 0 ? 'yellow' : 'green');
            $this->line("║  <fg={$color}>{$label}  +{$stat['inserted']}  ⊘{$stat['skipped']}  ✗{$stat['errors']}</>");

            $totalInserted += $stat['inserted'];
            $totalSkipped += $stat['skipped'];
            $totalErrors += $stat['errors'];
        }

        $this->info('╠══════════════════════════════════════════════════════════════╣');
        $this->info("║  TOTAL  +{$totalInserted}  ⊘{$totalSkipped}  ✗{$totalErrors}");
        $this->info('╚══════════════════════════════════════════════════════════════╝');

        if ($this->option('dry-run')) {
            $this->warn('⚠  DRY-RUN: nenhum dado foi gravado.');
        }
    }
}
