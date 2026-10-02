<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Migra dados de COTAÇÕES do banco antigo (omni_insurance_2) para o novo modelo (omni_insurance_new_model).
 *
 * Mapeamento de tabelas:
 *   entities                   → ent_entities  (+ent_personal_entities / ent_corporate_entities)
 *   sales_channels             → ent_sales_channels
 *   quotations                 → quo_quotations
 *   quotation_versions         → quo_quotation_versions
 *   quotation_insured_objects  → obj_objects
 *   quotation_insured_vehicles → obj_vehicles
 *   quotation_insured_properties→ obj_properties
 *   quotation_insured_people   → obj_persons
 *   quotation_insured_salaries → obj_salary_aggregates
 *   quotation_line_items       → quo_object_risk  (+quo_object_risk_deductibles)
 *   quotation_premiums         → quo_premiums
 *   quotation_special_conditions→ quo_special_conditions
 */
class MigrateQuotationData extends Command
{
    protected $signature = 'migrate:quotation-data
                            {--fresh    : Limpa as tabelas de destino antes de migrar}
                            {--dry-run  : Simula a migração sem gravar nada}
                            {--chunk=200: Registos por lote}
                            {--only=*  : Migra apenas secções específicas (entities,sales_channels,quotations,versions,objects,line_items,premiums,special_conditions)}';

    protected $description = 'Migra dados de cotações do banco antigo para o novo modelo';

    private string $OLD = 'mysql_old';
    private string $NEW = 'mysql';

    // ── Mapas old_id → new_id ──────────────────────────────────────────────
    private array $entityMap     = []; // old entities.id          → ent_entities.id
    private array $channelMap    = []; // old sales_channels.id    → ent_sales_channels.id
    private array $quotationMap  = []; // old quotations.id        → quo_quotations.id
    private array $versionMap    = []; // old quotation_versions.id→ quo_quotation_versions.id
    private array $objectMap     = []; // old insured_objects.id   → obj_objects.id
    private array $coverageMap   = []; // old coverages.id         → cfg_coverages.id
    private array $deductibleMap = []; // old deductibles.id       → cfg_deductibles.id
    private array $caeRateMap    = []; // old cae_rates_branches.id→ cfg_rates_cae.id
    private array $rateAutoMap   = []; // old rates_automobiles.id → cfg_rates_automobile.id
    private array $rateHealthMap = []; // old rates_healths.id     → cfg_rates_health.id
    private array $rateOccMap    = []; // old rates_occupants.id   → cfg_rates_occupants.id
    private array $rateRcMap     = []; // old rate_rc_opts.id      → cfg_rates_rc_options.id
    private array $templateCoverageByBranch = []; // branch_id => [code => [coverage_ids]]
    private array $templateResolveCache = [];     // "branch|cov1,cov2,..." => template_code|null

    private array $stats = [];

    // ──────────────────────────────────────────────────────────────────────
    public function handle(): int
    {
        $this->banner();

        if ($this->option('dry-run')) {
            $this->warn('⚠  MODO DRY-RUN — nenhum dado será gravado.');
            $this->newLine();
        }

        if (!$this->testConnections()) {
            return self::FAILURE;
        }
        // Ajustes de tipo/limpeza agora são feitos por migration própria.

        if ($this->option('fresh') && !$this->option('dry-run')) {
            if (!$this->confirm('⚠  --fresh irá APAGAR todos os dados de cotações no banco novo. Confirmas?')) {
                return self::SUCCESS;
            }
            $this->truncateQuoTables();
        }

        $this->loadExistingMappings();

        $only  = $this->option('only');
        $steps = [
            'entities'           => fn() => $this->migrateEntities(),
            'sales_channels'     => fn() => $this->migrateSalesChannels(),
            'quotations'         => fn() => $this->migrateQuotations(),
            'versions'           => fn() => $this->migrateVersions(),
            'objects'            => fn() => $this->migrateObjects(),
            'line_items'         => fn() => $this->migrateLineItems(),
            'premiums'           => fn() => $this->migratePremiums(),
            'special_conditions' => fn() => $this->migrateSpecialConditions(),
        ];

        foreach ($steps as $key => $fn) {
            if (!empty($only) && !in_array($key, $only)) {
                continue;
            }
            $fn();
        }

        $this->printSummary();
        return self::SUCCESS;
    }

    // ══════════════════════════════════════════════════════════════════════
    // LOAD EXISTING MAPPINGS
    // ══════════════════════════════════════════════════════════════════════

    private function loadExistingMappings(): void
    {
        $this->info('Carregando mapeamentos existentes…');

        // Entities: match por NIF
        DB::connection($this->NEW)->table('ent_entities')->select('id', 'nif', 'name')->orderBy('id')
            ->each(function ($e) {
                if (!empty($e->nif)) {
                    $old = DB::connection($this->OLD)->table('entities')->where('nif', $e->nif)->value('id');
                    if ($old) {
                        $this->entityMap[$old] = $e->id;
                    }
                }
            });

        // Sales channels: match por channel_type
        DB::connection($this->NEW)->table('ent_sales_channels')->select('id', 'channel_type', 'name')->orderBy('id')
            ->each(function ($sc) {
                $oldId = DB::connection($this->OLD)->table('sales_channels')
                    ->where('channel_type', $sc->channel_type)
                    ->value('id');
                if ($oldId) {
                    $this->channelMap[$oldId] = $sc->id;
                }
            });

        // Quotations: match por quotation_number
        DB::connection($this->NEW)->table('quo_quotations')->select('id', 'quotation_number')->orderBy('id')
            ->each(function ($q) {
                if (!empty($q->quotation_number)) {
                    $oldId = DB::connection($this->OLD)->table('quotations')
                        ->where('quotation_number', $q->quotation_number)->value('id');
                    if ($oldId) {
                        $this->quotationMap[$oldId] = $q->id;
                    }
                }
            });

        // Versions: match por quotation_id+version+table_code
        DB::connection($this->NEW)->table('quo_quotation_versions')
            ->select('id', 'quotation_id', 'version', 'table_code')->orderBy('id')
            ->each(function ($v) {
                $newQuo   = $v->quotation_id;
                $oldQuoId = array_search($newQuo, $this->quotationMap);
                if ($oldQuoId !== false) {
                    $oldVer = DB::connection($this->OLD)->table('quotation_versions')
                        ->where('quotation_id', $oldQuoId)
                        ->where('version', $v->version)
                        ->where('table', $v->table_code)
                        ->value('id');
                    if ($oldVer) {
                        $this->versionMap[$oldVer] = $v->id;
                    }
                }
            });

        // Objects: match por slug (imutável)
        DB::connection($this->NEW)->table('obj_objects')->select('id', 'slug')->orderBy('id')
            ->each(function ($o) {
                $oldId = DB::connection($this->OLD)->table('quotation_insured_objects')
                    ->where('slug', $o->slug)->value('id');
                if ($oldId) {
                    $this->objectMap[$oldId] = $o->id;
                }
            });

        // Coverages: match por branch (code) + seq + short_name/gis_cod/type_cov
        $cfgCoverages = DB::connection($this->NEW)->table('cfg_coverages as c')
            ->leftJoin('cfg_insurance_branches as b', 'b.id', '=', 'c.insurance_branch_id')
            ->select('c.id', 'c.insurance_branch_id', 'c.seq', 'c.short_name', 'c.gis_cod', 'c.type_cov', 'b.code as branch_code')
            ->get();
        foreach ($cfgCoverages as $nc) {
            $oldRows = DB::connection($this->OLD)->table('coverages as c')
                ->leftJoin('insurance_branches as b', 'b.id', '=', 'c.insurance_branch_id')
                ->when(!empty($nc->branch_code), fn($q) => $q->where('b.code', $nc->branch_code))
                ->where('seq', $nc->seq)
                ->when(!empty($nc->short_name), fn($q) => $q->where('c.short_name', $nc->short_name))
                ->when(!empty($nc->gis_cod),    fn($q) => $q->where('c.gis_cod',    $nc->gis_cod))
                ->when(!empty($nc->type_cov),   fn($q) => $q->where('c.type_cov',   $nc->type_cov))
                ->get(['c.id']);
            foreach ($oldRows as $oc) {
                if (!isset($this->coverageMap[$oc->id])) {
                    $this->coverageMap[$oc->id] = $nc->id;
                }
            }
        }

        // Deductibles: match por coverage_id+deduct_type+deduct_object
        $cfgDeductibles = DB::connection($this->NEW)->table('cfg_deductibles')->get();
        foreach ($cfgDeductibles as $nd) {
            if (empty($nd->coverage_id)) {
                continue;
            }
            $oldCovId = array_search($nd->coverage_id, $this->coverageMap);
            if ($oldCovId === false) {
                continue;
            }
            $oldDed = DB::connection($this->OLD)->table('deductibles')
                ->where('coverage_id', $oldCovId)
                ->where('deduct_type', $nd->deduct_type)
                ->where('deduct_object', $nd->deduct_object)
                ->value('id');
            if ($oldDed) {
                $this->deductibleMap[$oldDed] = $nd->id;
            }
        }

        // CAE rates: match por cae
        DB::connection($this->NEW)->table('cfg_rates_cae')->select('id', 'cae')->get()
            ->each(function ($r) {
                $oldId = DB::connection($this->OLD)->table('cae_rates_branches')
                    ->where('cae', $r->cae)->value('id');
                if ($oldId) {
                    $this->caeRateMap[$oldId] = $r->id;
                }
            });

        // Rates: mapear IDs antigos para IDs novos para preservar rate_id em quo_object_risk
        $this->loadRateMappings();

        $this->line(sprintf(
            '  ✓ entities=%d  channels=%d  quotations=%d  versions=%d  objects=%d  coverages=%d  deductibles=%d  caeRates=%d  autoRates=%d  healthRates=%d  occRates=%d  rcRates=%d',
            count($this->entityMap),
            count($this->channelMap),
            count($this->quotationMap),
            count($this->versionMap),
            count($this->objectMap),
            count($this->coverageMap),
            count($this->deductibleMap),
            count($this->caeRateMap),
            count($this->rateAutoMap),
            count($this->rateHealthMap),
            count($this->rateOccMap),
            count($this->rateRcMap)
        ));
        $this->newLine();
    }

    // ══════════════════════════════════════════════════════════════════════
    // 1. ENTITIES  →  ent_entities (+ent_personal_entities / ent_corporate_entities)
    // ══════════════════════════════════════════════════════════════════════

    private function migrateEntities(): void
    {
        $label    = 'entities → ent_entities';
        $dryRun   = (bool) $this->option('dry-run');
        $chunk    = (int)  $this->option('chunk');
        $inserted = $skipped = $errors = 0;

        $this->info("▶  {$label}");
        $total = DB::connection($this->OLD)->table('entities')->count();
        $this->line("   Registos no banco antigo: <fg=yellow>{$total}</>");

        $bar = $this->output->createProgressBar($total);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%%');
        $bar->start();

        DB::connection($this->OLD)->table('entities')->orderBy('id')
            ->chunk($chunk, function ($rows) use ($dryRun, &$inserted, &$skipped, &$errors, $bar) {
                foreach ($rows as $old) {
                    try {
                        if (isset($this->entityMap[$old->id])) {
                            $skipped++;
                            $bar->advance();
                            continue;
                        }

                        $existingId = null;

                        if (!empty($old->nif)) {
                            $existingId = DB::connection($this->NEW)->table('ent_entities')
                                ->where('nif', $old->nif)->value('id');
                        }

                        if (!$existingId) {
                            $existingId = DB::connection($this->NEW)->table('ent_entities')
                                ->where('name', $old->name)
                                ->whereNull('nif')
                                ->value('id');
                        }

                        if ($existingId) {
                            $this->entityMap[$old->id] = $existingId;
                            $skipped++;
                            $bar->advance();
                            continue;
                        }

                        $entityType = ($old->type === 'Corporate') ? 'Corporate' : 'Personal';
                        $slug       = $this->uniqueSlug($old->slug ?? Str::slug($old->name), 'ent_entities');

                        if (!$dryRun) {
                            $newId = DB::connection($this->NEW)->table('ent_entities')->insertGetId([
                                'entity_type'       => $entityType,
                                'name'              => $old->name,
                                'nif'               => $old->nif ?? null,
                                'id_card'           => null,
                                'passport'          => null,
                                'temp_identifier'   => null,
                                'identifier_status' => empty($old->nif) ? 'Temporary' : 'Partial',
                                'kyc_status'        => 'Not_Started',
                                'pep_status'        => 'Not_Checked',
                                'us_person'         => 0,
                                'status'            => $old->active ? 'Active' : 'Inactive',
                                'active'            => $old->active ?? 1,
                                'slug'              => $slug,
                                'created_by'        => null,
                                'updated_by'        => null,
                                'created_at'        => $old->created_at,
                                'updated_at'        => $old->updated_at,
                                'deleted_at'        => $old->deleted_at ?? null,
                            ]);

                            $this->entityMap[$old->id] = $newId;

                            if ($entityType === 'Personal') {
                                DB::connection($this->NEW)->table('ent_personal_entities')->insert([
                                    'entity_id'   => $newId,
                                    'nationality' => $old->nationality ?? null,
                                    'slug'        => $this->uniqueSlug('pe-' . $slug, 'ent_personal_entities'),
                                    'created_at'  => $old->created_at,
                                    'updated_at'  => $old->updated_at,
                                ]);
                            } else {
                                $corp = DB::connection($this->OLD)->table('corporate_entities')
                                    ->where('entity_id', $old->id)->first();

                                DB::connection($this->NEW)->table('ent_corporate_entities')->insert([
                                    'entity_id'           => $newId,
                                    'registration_number' => $corp->registration_number ?? null,
                                    'industry_type'       => $corp->industry_type ?? null,
                                    'tax_classification'  => $corp->tax_classification ?? null,
                                    'slug'                => $this->uniqueSlug('ce-' . $slug, 'ent_corporate_entities'),
                                    'created_at'          => $old->created_at,
                                    'updated_at'          => $old->updated_at,
                                ]);
                            }
                        } else {
                            $this->entityMap[$old->id] = $old->id;
                        }

                        $inserted++;
                    } catch (Throwable $e) {
                        $errors++;
                        $this->newLine();
                        $this->error("   ✗ entity id={$old->id} ({$old->name}): " . $e->getMessage());
                    }
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine(2);
        $this->recordStats('entities', $label, $inserted, $skipped, $errors);
    }

    // ══════════════════════════════════════════════════════════════════════
    // 2. SALES_CHANNELS  →  ent_sales_channels
    // ══════════════════════════════════════════════════════════════════════

    private function migrateSalesChannels(): void
    {
        $label    = 'sales_channels → ent_sales_channels';
        $dryRun   = (bool) $this->option('dry-run');
        $chunk    = (int)  $this->option('chunk');
        $inserted = $skipped = $errors = 0;

        $this->info("▶  {$label}");
        $total = DB::connection($this->OLD)->table('sales_channels')->count();
        $this->line("   Registos no banco antigo: <fg=yellow>{$total}</>");

        $bar = $this->output->createProgressBar($total);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%%');
        $bar->start();

        DB::connection($this->OLD)->table('sales_channels')->orderBy('id')
            ->chunk($chunk, function ($rows) use ($dryRun, &$inserted, &$skipped, &$errors, $bar) {
                foreach ($rows as $old) {
                    try {
                        if (isset($this->channelMap[$old->id])) {
                            $skipped++;
                            $bar->advance();
                            continue;
                        }

                        $existingId = DB::connection($this->NEW)->table('ent_sales_channels')
                            ->where('channel_type', $old->channel_type)
                            ->where(function ($q) use ($old) {
                                $q->where('responsible', $old->responsible)
                                  ->orWhereNull('responsible');
                            })
                            ->value('id');

                        if ($existingId) {
                            $this->channelMap[$old->id] = $existingId;
                            $skipped++;
                            $bar->advance();
                            continue;
                        }

                        $slug = $this->uniqueSlug($old->slug, 'ent_sales_channels');

                        if (!$dryRun) {
                            $newId = DB::connection($this->NEW)->table('ent_sales_channels')->insertGetId([
                                'channel_type' => $old->channel_type,
                                'name'         => $old->description ?? $old->channel_type,
                                'description'  => $old->area ?? null,
                                'responsible'  => $old->responsible ?? null,
                                'active'       => 1,
                                'slug'         => $slug,
                                'created_at'   => $old->created_at,
                                'updated_at'   => $old->updated_at,
                                'deleted_at'   => $old->deleted_at ?? null,
                            ]);
                            $this->channelMap[$old->id] = $newId;
                        } else {
                            $this->channelMap[$old->id] = $old->id;
                        }
                        $inserted++;
                    } catch (Throwable $e) {
                        $errors++;
                        $this->newLine();
                        $this->error("   ✗ sales_channel id={$old->id}: " . $e->getMessage());
                    }
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine(2);
        $this->recordStats('sales_channels', $label, $inserted, $skipped, $errors);
    }

    // ══════════════════════════════════════════════════════════════════════
    // 3. QUOTATIONS  →  quo_quotations
    // ══════════════════════════════════════════════════════════════════════

    private function migrateQuotations(): void
    {
        $label    = 'quotations → quo_quotations';
        $dryRun   = (bool) $this->option('dry-run');
        $chunk    = (int)  $this->option('chunk');
        $inserted = $skipped = $errors = 0;

        $this->info("▶  {$label}");
        $total = DB::connection($this->OLD)->table('quotations')->count();
        $this->line("   Registos no banco antigo: <fg=yellow>{$total}</>");

        $bar = $this->output->createProgressBar($total);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%%');
        $bar->start();

        DB::connection($this->OLD)->table('quotations')->orderBy('id')
            ->chunk($chunk, function ($rows) use ($dryRun, &$inserted, &$skipped, &$errors, $bar) {
                foreach ($rows as $old) {
                    try {
                        if (isset($this->quotationMap[$old->id])) {
                            $skipped++;
                            $bar->advance();
                            continue;
                        }

                        if (!empty($old->quotation_number)) {
                            $existingId = DB::connection($this->NEW)->table('quo_quotations')
                                ->where('quotation_number', $old->quotation_number)->value('id');
                            if ($existingId) {
                                $this->quotationMap[$old->id] = $existingId;
                                $skipped++;
                                $bar->advance();
                                continue;
                            }
                        }

                        $newCustomerId = $this->resolveOrCreateCustomerEntityId($old->customer_id, $dryRun);
                        if (!$newCustomerId) {
                            $this->warn("  skip quotation id={$old->id}: customer_id={$old->customer_id} não mapeado");
                            $skipped++;
                            $bar->advance();
                            continue;
                        }

                        $oldBranch   = DB::connection($this->OLD)->table('insurance_branches')
                            ->where('id', $old->insurance_branche_id)->first();
                        $newBranchId = null;
                        if ($oldBranch) {
                            $newBranchId = DB::connection($this->NEW)->table('cfg_insurance_branches')
                                ->where('code', $oldBranch->code)->value('id');
                        }
                        if (!$newBranchId) {
                            $this->warn("  skip quotation id={$old->id}: branch_id={$old->insurance_branche_id} não mapeado");
                            $skipped++;
                            $bar->advance();
                            continue;
                        }

                        $status         = $this->deriveQuotationStatus($old->id);
                        $opportunityRef = null;
                        if (!empty($old->id_opportunity)) {
                            $opportunityRef = DB::connection($this->OLD)->table('opportunities')
                                ->where('id', $old->id_opportunity)->value('sequence');
                        }

                        $slug = $this->uniqueSlug($old->slug, 'quo_quotations');

                        if (!$dryRun) {
                            $newId = DB::connection($this->NEW)->table('quo_quotations')->insertGetId([
                                'quotation_number'    => $old->quotation_number ?? null,
                                'customer_id'         => $newCustomerId,
                                'insurance_branch_id' => $newBranchId,
                                'agent_id'            => null,
                                'opportunity_id'      => $opportunityRef,
                                'lead_source'         => null,
                                'status'              => $status,
                                'slug'                => $slug,
                                'created_by'          => null,
                                'updated_by'          => null,
                                'created_at'          => $old->created_at,
                                'updated_at'          => $old->updated_at,
                                'deleted_at'          => $old->deleted_at ?? null,
                            ]);
                            $this->quotationMap[$old->id] = $newId;
                        } else {
                            $this->quotationMap[$old->id] = $old->id;
                        }
                        $inserted++;
                    } catch (Throwable $e) {
                        $errors++;
                        $this->newLine();
                        $this->error("   ✗ quotation id={$old->id}: " . $e->getMessage());
                    }
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine(2);
        $this->recordStats('quotations', $label, $inserted, $skipped, $errors);
    }

    // ══════════════════════════════════════════════════════════════════════
    // 4. QUOTATION_VERSIONS  →  quo_quotation_versions
    // ══════════════════════════════════════════════════════════════════════

    private function migrateVersions(): void
    {
        $label    = 'quotation_versions → quo_quotation_versions';
        $dryRun   = (bool) $this->option('dry-run');
        $chunk    = (int)  $this->option('chunk');
        $inserted = $skipped = $errors = 0;

        $this->info("▶  {$label}");
        $total = DB::connection($this->OLD)->table('quotation_versions')->count();
        $this->line("   Registos no banco antigo: <fg=yellow>{$total}</>");

        $bar = $this->output->createProgressBar($total);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%%');
        $bar->start();

        DB::connection($this->OLD)->table('quotation_versions')->orderBy('id')
            ->chunk($chunk, function ($rows) use ($dryRun, &$inserted, &$skipped, &$errors, $bar) {
                foreach ($rows as $old) {
                    try {
                        if (isset($this->versionMap[$old->id])) {
                            $skipped++;
                            $bar->advance();
                            continue;
                        }

                        $newQuotationId = $this->quotationMap[$old->quotation_id] ?? null;
                        if (!$newQuotationId) {
                            $skipped++;
                            $bar->advance();
                            continue;
                        }

                        $existingId = DB::connection($this->NEW)->table('quo_quotation_versions')
                            ->where('quotation_id', $newQuotationId)
                            ->where('version', $old->version)
                            ->where('table_code', $old->table)
                            ->value('id');
                        if ($existingId) {
                            $this->versionMap[$old->id] = $existingId;
                            $skipped++;
                            $bar->advance();
                            continue;
                        }

                        $newChannelId   = $this->channelMap[$old->sales_channel_id] ?? null;
                        $newCaeRateId   = !empty($old->cae_rates_branche_id)
                            ? ($this->caeRateMap[$old->cae_rates_branche_id] ?? null)
                            : null;
                        $newAgentRoleId = $this->resolveAgentRoleId($old->commercial_agent_id);
                        $templateCode   = $this->resolveTemplateCodeFromVersion($old, $newQuotationId);

                        if (!$dryRun) {
                            $newId = DB::connection($this->NEW)->table('quo_quotation_versions')->insertGetId([
                                'quotation_id'             => $newQuotationId,
                                'version'                  => (string) $old->version,
                                'table_code'               => $old->table ?? null,
                                'commercial_agent_role_id' => $newAgentRoleId,
                                'sales_channel_id'         => $newChannelId,
                                'cae_rate_id'              => $newCaeRateId,
                                'total_premium'            => $this->sanitizeDecimal($old->total_premium),
                                'discount_percent'         => $old->discountPerc ?? null,
                                'currency'                 => 'AOA',
                                'exchange_rate'            => 1.000000,
                                'total_premium_usd'        => null,
                                'status'                   => $old->status,
                                'created_date'             => $old->created_date,
                                'valid_until'              => $old->valid_until,
                                'policy_id'                => null,
                                'snapshot_json'            => null,
                                'template_code'            => $templateCode,
                                'created_by'               => null,
                                'updated_by'               => null,
                                'created_at'               => $old->created_at,
                                'updated_at'               => $old->updated_at,
                                'deleted_at'               => $old->deleted_at ?? null,
                            ]);
                            $this->versionMap[$old->id] = $newId;
                        } else {
                            $this->versionMap[$old->id] = $old->id;
                        }
                        $inserted++;
                    } catch (Throwable $e) {
                        $errors++;
                        $this->newLine();
                        $this->error("   ✗ version id={$old->id}: " . $e->getMessage());
                    }
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine(2);
        $this->recordStats('versions', $label, $inserted, $skipped, $errors);
    }

    // ══════════════════════════════════════════════════════════════════════
    // 5. INSURED OBJECTS  →  obj_objects  (+ sub-tabelas por tipo)
    // ══════════════════════════════════════════════════════════════════════

    private function migrateObjects(): void
    {
        $this->migrateInsuredObjects();
        $this->migrateInsuredVehicles();
        $this->migrateInsuredProperties();
        $this->migrateInsuredPeople();
        $this->migrateInsuredSalaries();
    }

    private function migrateInsuredObjects(): void
    {
        $label    = 'quotation_insured_objects → obj_objects';
        $dryRun   = (bool) $this->option('dry-run');
        $chunk    = (int)  $this->option('chunk');
        $inserted = $skipped = $errors = 0;

        $this->info("▶  {$label}");
        $total = DB::connection($this->OLD)->table('quotation_insured_objects')->count();
        $bar   = $this->output->createProgressBar($total);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%%');
        $bar->start();

        $typeMap = [
            'Vehicle'               => 'Vehicle',
            'Property'              => 'Property',
            'Person'                => 'Person',
            'WorkAccidentAggregate' => 'SalaryAggregate',
            'Other'                 => 'Other',
        ];

        DB::connection($this->OLD)->table('quotation_insured_objects')->orderBy('id')
            ->chunk($chunk, function ($rows) use ($dryRun, $typeMap, &$inserted, &$skipped, &$errors, $bar) {
                foreach ($rows as $old) {
                    try {
                        if (isset($this->objectMap[$old->id])) {
                            $skipped++;
                            $bar->advance();
                            continue;
                        }

                        $existingId = DB::connection($this->NEW)->table('obj_objects')
                            ->where('slug', $old->slug)->value('id');
                        if ($existingId) {
                            $this->objectMap[$old->id] = $existingId;
                            $skipped++;
                            $bar->advance();
                            continue;
                        }

                        $newEntityId = !empty($old->entity_id)
                            ? ($this->entityMap[$old->entity_id] ?? null)
                            : null;

                        $newType = $typeMap[$old->type] ?? 'Other';
                        $slug    = $this->uniqueSlug($old->slug, 'obj_objects');

                        if (!$dryRun) {
                            $newId = DB::connection($this->NEW)->table('obj_objects')->insertGetId([
                                'entity_id'   => $newEntityId,
                                'type'        => $newType,
                                'description' => $old->description ?? null,
                                'slug'        => $slug,
                                'created_by'  => null,
                                'updated_by'  => null,
                                'created_at'  => $old->created_at,
                                'updated_at'  => $old->updated_at,
                                'deleted_at'  => $old->deleted_at ?? null,
                            ]);
                            $this->objectMap[$old->id] = $newId;
                        } else {
                            $this->objectMap[$old->id] = $old->id;
                        }
                        $inserted++;
                    } catch (Throwable $e) {
                        $errors++;
                        $this->newLine();
                        $this->error("   ✗ insured_object id={$old->id}: " . $e->getMessage());
                    }
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine(2);
        $this->recordStats('insured_objects', $label, $inserted, $skipped, $errors);
    }

    private function migrateInsuredVehicles(): void
    {
        $label    = 'quotation_insured_vehicles → obj_vehicles';
        $dryRun   = (bool) $this->option('dry-run');
        $chunk    = (int)  $this->option('chunk');
        $inserted = $skipped = $errors = 0;

        $this->info("▶  {$label}");
        $total = DB::connection($this->OLD)->table('quotation_insured_vehicles')->count();
        $bar   = $this->output->createProgressBar($total);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%%');
        $bar->start();

        DB::connection($this->OLD)->table('quotation_insured_vehicles')->orderBy('id')
            ->chunk($chunk, function ($rows) use ($dryRun, &$inserted, &$skipped, &$errors, $bar) {
                foreach ($rows as $old) {
                    try {
                        $newObjectId = $this->objectMap[$old->insured_object_id] ?? null;
                        if (!$newObjectId) {
                            $skipped++;
                            $bar->advance();
                            continue;
                        }

                        $exists = DB::connection($this->NEW)->table('obj_vehicles')
                            ->where('object_id', $newObjectId)->exists();
                        if ($exists) {
                            $skipped++;
                            $bar->advance();
                            continue;
                        }

                        $slug = $this->uniqueSlug($old->slug, 'obj_vehicles');

                        if (!$dryRun) {
                            $dpCapital = $this->sanitizeDecimal($old->dp_capital_value, 9_999_999_999_999_999.99);
                            $rcCapital = $this->sanitizeDecimal($old->rc_capital_value, 9_999_999_999_999_999.99);

                            $newVehicleId = DB::connection($this->NEW)->table('obj_vehicles')->insertGetId([
                                'object_id'        => $newObjectId,
                                'plate'            => $old->plate ?? null,
                                'chassis_number'   => $old->chassis_number ?? null,
                                'brand'            => $old->brand ?? null,
                                'model'            => $old->model ?? null,
                                'version'          => $old->version ?? null,
                                'color'            => $old->color ?? null,
                                'type'             => $old->type ?? null,
                                'subcategory'      => $old->subcategory ?? null,
                                'cylinder_cc'      => $old->cilinder_cc ?? null,
                                'number_seats'     => $old->number_seats ?? null,
                                'weight'           => isset($old->weight) ? (int) $old->weight : null,
                                'first_plate_date' => $old->first_plate_date ?? null,
                                'dp_capital'       => $dpCapital,
                                'dp_initial_capital' => $dpCapital,
                                'rc_capital'       => $rcCapital,
                                'slug'             => $slug,
                                'created_at'       => $old->created_at,
                                'updated_at'       => $old->updated_at,
                                'deleted_at'       => $old->deleted_at ?? null,
                            ]);

                            $this->insertObjectRiskCapitals($newObjectId, [
                                'DP' => $dpCapital,
                                'RC' => $rcCapital,
                            ]);
                        }
                        $inserted++;
                    } catch (Throwable $e) {
                        $errors++;
                        $this->newLine();
                        $this->error("   ✗ vehicle id={$old->id}: " . $e->getMessage());
                    }
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine(2);
        $this->recordStats('insured_vehicles', $label, $inserted, $skipped, $errors);
    }

    private function migrateInsuredProperties(): void
    {
        $label    = 'quotation_insured_properties → obj_properties';
        $dryRun   = (bool) $this->option('dry-run');
        $chunk    = (int)  $this->option('chunk');
        $inserted = $skipped = $errors = 0;

        $this->info("▶  {$label}");
        $total = DB::connection($this->OLD)->table('quotation_insured_properties')->count();
        $bar   = $this->output->createProgressBar($total);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%%');
        $bar->start();

        DB::connection($this->OLD)->table('quotation_insured_properties')->orderBy('id')
            ->chunk($chunk, function ($rows) use ($dryRun, &$inserted, &$skipped, &$errors, $bar) {
                foreach ($rows as $old) {
                    try {
                        $newObjectId = $this->objectMap[$old->insured_object_id] ?? null;
                        if (!$newObjectId) {
                            $skipped++;
                            $bar->advance();
                            continue;
                        }

                        $exists = DB::connection($this->NEW)->table('obj_properties')
                            ->where('object_id', $newObjectId)->exists();
                        if ($exists) {
                            $skipped++;
                            $bar->advance();
                            continue;
                        }

                        $slug = $this->uniqueSlug($old->slug, 'obj_properties');

                        if (!$dryRun) {
                            $buildingCapital = $this->sanitizeDecimal($old->building_capital_value, 9_999_999_999_999_999.99);
                            $contentCapital = $this->sanitizeDecimal($old->content_capital_value, 9_999_999_999_999_999.99);
                            $explorationCapital = $this->sanitizeDecimal($old->exploration_capital_value, 9_999_999_999_999_999.99);

                            DB::connection($this->NEW)->table('obj_properties')->insert([
                                'object_id'           => $newObjectId,
                                'address'             => $old->address ?? null,
                                'city'                => null,
                                'province'            => null,
                                'postal_code'         => null,
                                'latitude'            => null,
                                'longitude'           => null,
                                'property_type'       => $old->property_type ?? null,
                                'total_area'          => $old->total_area ?? null,
                                'number_of_floors'    => $old->number_of_floors ?? null,
                                'construction_year'   => null,
                                'fire_protection'     => $old->fire_protection ?? 0,
                                'security_system'     => 0,
                                'building_capital'    => $buildingCapital,
                                'content_capital'     => $contentCapital,
                                'exploration_capital' => $explorationCapital,
                                'slug'                => $slug,
                                'created_at'          => $old->created_at,
                                'updated_at'          => $old->updated_at,
                                'deleted_at'          => $old->deleted_at ?? null,
                            ]);

                            $this->insertObjectRiskCapitals($newObjectId, [
                                'BUILDING'    => $buildingCapital,
                                'CONTENT'     => $contentCapital,
                                'EXPLORATION' => $explorationCapital,
                            ]);
                        }
                        $inserted++;
                    } catch (Throwable $e) {
                        $errors++;
                        $this->newLine();
                        $this->error("   ✗ property id={$old->id}: " . $e->getMessage());
                    }
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine(2);
        $this->recordStats('insured_properties', $label, $inserted, $skipped, $errors);
    }

    private function migrateInsuredPeople(): void
    {
        $label    = 'quotation_insured_people → obj_persons';
        $dryRun   = (bool) $this->option('dry-run');
        $chunk    = (int)  $this->option('chunk');
        $inserted = $skipped = $errors = 0;

        $this->info("▶  {$label}");
        $total = DB::connection($this->OLD)->table('quotation_insured_people')->count();
        $bar   = $this->output->createProgressBar($total);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%%');
        $bar->start();

        DB::connection($this->OLD)->table('quotation_insured_people')->orderBy('id')
            ->chunk($chunk, function ($rows) use ($dryRun, &$inserted, &$skipped, &$errors, $bar) {
                foreach ($rows as $old) {
                    try {
                        $newObjectId = $this->objectMap[$old->insured_object_id] ?? null;
                        if (!$newObjectId) {
                            $skipped++;
                            $bar->advance();
                            continue;
                        }

                        $exists = DB::connection($this->NEW)->table('obj_persons')
                            ->where('object_id', $newObjectId)->exists();
                        if ($exists) {
                            $skipped++;
                            $bar->advance();
                            continue;
                        }

                        $newEntityId = $this->entityMap[$old->entity_id] ?? null;
                        $slug        = $this->uniqueSlug($old->slug, 'obj_persons');

                        $name = null;
                        if (!empty($old->entity_id)) {
                            $name = DB::connection($this->OLD)->table('entities')
                                ->where('id', $old->entity_id)->value('name');
                        }

                        if (!$dryRun) {
                            DB::connection($this->NEW)->table('obj_persons')->insert([
                                'object_id'    => $newObjectId,
                                'entity_id'    => $newEntityId,
                                'name'         => $name,
                                'gender'       => $old->gender,
                                'birthdate'    => $old->birthdate ?? null,
                                'age'          => $old->age ?? null,
                                'dependent'    => $old->dependent ?? 0,
                                'relationship' => $old->relationship ?? null,
                                'job_category' => $old->job_category ?? 'Not specified',
                                'salary'       => $old->salary ?? null,
                                'slug'         => $slug,
                                'created_at'   => $old->created_at,
                                'updated_at'   => $old->updated_at,
                                'deleted_at'   => $old->deleted_at ?? null,
                            ]);
                        }
                        $inserted++;
                    } catch (Throwable $e) {
                        $errors++;
                        $this->newLine();
                        $this->error("   ✗ person id={$old->id}: " . $e->getMessage());
                    }
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine(2);
        $this->recordStats('insured_people', $label, $inserted, $skipped, $errors);
    }

    private function migrateInsuredSalaries(): void
    {
        $label    = 'quotation_insured_salaries → obj_salary_aggregates';
        $dryRun   = (bool) $this->option('dry-run');
        $chunk    = (int)  $this->option('chunk');
        $inserted = $skipped = $errors = 0;

        $this->info("▶  {$label}");
        $total = DB::connection($this->OLD)->table('quotation_insured_salaries')->count();
        $bar   = $this->output->createProgressBar($total);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%%');
        $bar->start();

        DB::connection($this->OLD)->table('quotation_insured_salaries')->orderBy('id')
            ->chunk($chunk, function ($rows) use ($dryRun, &$inserted, &$skipped, &$errors, $bar) {
                foreach ($rows as $old) {
                    try {
                        $newObjectId = $this->objectMap[$old->insured_object_id] ?? null;
                        if (!$newObjectId) {
                            $skipped++;
                            $bar->advance();
                            continue;
                        }

                        $exists = DB::connection($this->NEW)->table('obj_salary_aggregates')
                            ->where('object_id', $newObjectId)->exists();
                        if ($exists) {
                            $skipped++;
                            $bar->advance();
                            continue;
                        }

                        $slug = $this->uniqueSlug($old->slug, 'obj_salary_aggregates');

                        if (!$dryRun) {
                            DB::connection($this->NEW)->table('obj_salary_aggregates')->insert([
                                'object_id'                 => $newObjectId,
                                'total_base_salary'         => $old->total_base_salary,
                                'total_addons_salary'       => $old->total_addons_salary,
                                'total_avg_variable_salary' => $old->total_avg_variable_salary,
                                'number_of_months'          => $old->number_of_months,
                                'employee_count'            => $old->employee_count,
                                'slug'                      => $slug,
                                'created_at'                => $old->created_at,
                                'updated_at'                => $old->updated_at,
                                'deleted_at'                => $old->deleted_at ?? null,
                            ]);
                        }
                        $inserted++;
                    } catch (Throwable $e) {
                        $errors++;
                        $this->newLine();
                        $this->error("   ✗ salary id={$old->id}: " . $e->getMessage());
                    }
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine(2);
        $this->recordStats('insured_salaries', $label, $inserted, $skipped, $errors);
    }

    // ══════════════════════════════════════════════════════════════════════
    // 6. LINE ITEMS  →  quo_object_risk  (+quo_object_risk_deductibles)
    // ══════════════════════════════════════════════════════════════════════

private function migrateLineItems(): void
{
    $label    = 'quotation_line_items → quo_object_risk';
    $dryRun   = (bool) $this->option('dry-run');
    $chunk    = (int)  $this->option('chunk');
    $inserted = $skipped = $errors = 0;

    $this->info("▶  {$label}");
    $total = DB::connection($this->OLD)->table('quotation_line_items')->count();
    $this->line("   Registos no banco antigo: <fg=yellow>{$total}</>");

    $bar = $this->output->createProgressBar($total);
    $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%%');
    $bar->start();

    DB::connection($this->OLD)->table('quotation_line_items')->orderBy('id')
        ->chunk($chunk, function ($rows) use ($dryRun, &$inserted, &$skipped, &$errors, $bar) {
            foreach ($rows as $old) {
                try {
                    $newVersionId  = !empty($old->quotation_version_id)
                        ? ($this->versionMap[$old->quotation_version_id] ?? null)
                        : null;
                        $newCoverageId = $this->coverageMap[$old->coverage_id] ?? null;
                        $newObjectId   = $this->objectMap[$old->insured_object_id] ?? null;

                        if (!$newVersionId || !$newCoverageId || !$newObjectId) {
                            $skipped++;
                        $bar->advance();
                        continue;
                    }

                    $existingId = DB::connection($this->NEW)->table('quo_object_risk')
                        ->where('quotation_version_id', $newVersionId)
                        ->where('object_id', $newObjectId)
                        ->where('coverage_id', $newCoverageId)
                        ->value('id');
                    if ($existingId) {
                        // Backfill para migrações anteriores: riscos de SalaryAggregate sem capital_details
                        if (!$dryRun) {
                            $currentCapId = DB::connection($this->NEW)->table('quo_object_risk')
                                ->where('id', $existingId)
                                ->value('object_risk_capital_id');
                            if (empty($currentCapId)) {
                                $objectType = DB::connection($this->NEW)->table('obj_objects')
                                    ->where('id', $newObjectId)
                                    ->value('type');
                                if ($objectType === 'SalaryAggregate') {
                                    $salaryCapId = $this->upsertObjectRiskCapitalAndGetId(
                                        $newObjectId,
                                        'SALARY',
                                        $old->capital_value
                                    );
                                    if ($salaryCapId) {
                                        DB::connection($this->NEW)->table('quo_object_risk')
                                            ->where('id', $existingId)
                                            ->update(['object_risk_capital_id' => $salaryCapId]);
                                    }
                                }
                            }
                        }
                        $skipped++;
                        $bar->advance();
                        continue;
                    }

                    $slug = $this->uniqueSlug($old->slug, 'quo_object_risk');
                    $newRateId = $this->resolveRateId($old->rate_id, $old->rate_origin);
                    $objectRiskCapitalId = null;

                    if (!$dryRun) {
                        $objectType = DB::connection($this->NEW)->table('obj_objects')
                            ->where('id', $newObjectId)
                            ->value('type');
                        if ($objectType === 'SalaryAggregate') {
                            $objectRiskCapitalId = $this->upsertObjectRiskCapitalAndGetId(
                                $newObjectId,
                                'SALARY',
                                $old->capital_value
                            );
                        }
                    }

                    if (!$dryRun) {
                        $newRiskId = DB::connection($this->NEW)->table('quo_object_risk')->insertGetId([
                            'quotation_version_id'   => $newVersionId,
                            'object_id'              => $newObjectId,
                            'coverage_id'            => $newCoverageId,
                            'object_risk_capital_id' => $objectRiskCapitalId,
                            'dp_capital'             => null,
                            'rc_capital'             => null,
                            'building_capital'       => null,
                            'content_capital'        => null,
                            'exploration_capital'    => null,
                            'capital'                => $this->sanitizeDecimal($old->capital_value),
                            'coverage_perc'          => $this->sanitizeDecimal($old->coverage_perc)   ?? 0,
                            'applied_rate'           => $this->sanitizeDecimal($old->applied_rate)    ?? 0,
                            'coverage_premium'       => $this->sanitizeDecimal($old->coverage_premium) ?? 0,
                            'rate_origin'            => $old->rate_origin ?? null,
                            'rate_id'                => $newRateId,
                            'discount_percent'       => 0.00,
                            'surcharge_percent'      => 0.00,
                            'manual_rate'            => null,
                            'slug'                   => $slug,
                            'created_at'             => $old->created_at,
                            'updated_at'             => $old->updated_at,
                            'deleted_at'             => $old->deleted_at ?? null,
                        ]);

                        if (!empty($old->deductible_id)) {
                            $newDeductibleId = $this->deductibleMap[$old->deductible_id] ?? null;
                            if ($newDeductibleId) {
                                $dSlug = $this->uniqueSlug('ded-' . $newRiskId . '-' . $newDeductibleId, 'quo_object_risk_deductibles');
                                DB::connection($this->NEW)->table('quo_object_risk_deductibles')->insert([
                                    'object_risk_id'  => $newRiskId,
                                    'deductible_id'   => $newDeductibleId,
                                    'applied_value'   => null,
                                    'applied_percent' => null,
                                    'slug'            => $dSlug,
                                    'created_at'      => $old->created_at,
                                    'updated_at'      => $old->updated_at,
                                    'deleted_at'      => $old->deleted_at ?? null,
                                ]);
                            }
                        }
                    }
                    $inserted++;
                } catch (Throwable $e) {
                    $errors++;
                    $this->newLine();
                    $this->error("   ✗ line_item id={$old->id}: " . $e->getMessage());
                }
                $bar->advance();
            }
        });

    $bar->finish();
    $this->newLine(2);
    $this->recordStats('line_items', $label, $inserted, $skipped, $errors);
}
    // ══════════════════════════════════════════════════════════════════════
    // 7. PREMIUMS  →  quo_premiums
    // ══════════════════════════════════════════════════════════════════════

    private function migratePremiums(): void
    {
        $label    = 'quotation_premiums → quo_premiums';
        $dryRun   = (bool) $this->option('dry-run');
        $chunk    = (int)  $this->option('chunk');
        $inserted = $skipped = $errors = 0;

        $this->info("▶  {$label}");
        $total = DB::connection($this->OLD)->table('quotation_premiums')->count();
        $bar   = $this->output->createProgressBar($total);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%%');
        $bar->start();

        DB::connection($this->OLD)->table('quotation_premiums')->orderBy('id')
            ->chunk($chunk, function ($rows) use ($dryRun, &$inserted, &$skipped, &$errors, $bar) {
                foreach ($rows as $old) {
                    try {
                        $newVersionId = !empty($old->quotation_version_id)
                            ? ($this->versionMap[$old->quotation_version_id] ?? null)
                            : null;
                        if (!$newVersionId) {
                            $skipped++;
                            $bar->advance();
                            continue;
                        }

                        $exists = DB::connection($this->NEW)->table('quo_premiums')
                            ->where('quotation_version_id', $newVersionId)
                            ->where('type', $this->normalizePremiumType((string) $old->type))
                            ->exists();
                        if ($exists) {
                            $skipped++;
                            $bar->advance();
                            continue;
                        }

                        if (!$dryRun) {
                            DB::connection($this->NEW)->table('quo_premiums')->insert([
                                'quotation_version_id' => $newVersionId,
                                'type'                 => $this->normalizePremiumType((string) $old->type),
                                'value'                => $this->sanitizeDecimal($old->value) ?? 0,
                                'status'               => $old->status ?? 1,
                                'created_at'           => $old->created_at,
                                'updated_at'           => $old->updated_at,
                            ]);
                        }
                        $inserted++;
                    } catch (Throwable $e) {
                        $errors++;
                        $this->newLine();
                        $this->error("   ✗ premium id={$old->id}: " . $e->getMessage());
                    }
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine(2);
        $this->recordStats('premiums', $label, $inserted, $skipped, $errors);
    }

    // ══════════════════════════════════════════════════════════════════════
    // 8. SPECIAL CONDITIONS  →  quo_special_conditions
    // ══════════════════════════════════════════════════════════════════════

    private function migrateSpecialConditions(): void
    {
        $label    = 'quotation_special_conditions → quo_special_conditions';
        $dryRun   = (bool) $this->option('dry-run');
        $chunk    = (int)  $this->option('chunk');
        $inserted = $skipped = $errors = 0;

        $this->info("▶  {$label}");
        $total = DB::connection($this->OLD)->table('quotation_special_conditions')->count();
        $bar   = $this->output->createProgressBar($total);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%%');
        $bar->start();

        DB::connection($this->OLD)->table('quotation_special_conditions')->orderBy('id')
            ->chunk($chunk, function ($rows) use ($dryRun, &$inserted, &$skipped, &$errors, $bar) {
                foreach ($rows as $old) {
                    try {
                        $newVersionId = null;
                        if (!empty($old->quotation_version_id)) {
                            $newVersionId = $this->versionMap[$old->quotation_version_id] ?? null;
                        }
                        if (!$newVersionId && !empty($old->quotation_id)) {
                            $newQuotationId = $this->quotationMap[$old->quotation_id] ?? null;
                            if ($newQuotationId) {
                                $newVersionId = DB::connection($this->NEW)->table('quo_quotation_versions')
                                    ->where('quotation_id', $newQuotationId)
                                    ->orderByDesc('created_at')
                                    ->value('id');
                            }
                        }

                        if (!$newVersionId) {
                            $skipped++;
                            $bar->advance();
                            continue;
                        }

                        $scCode  = 'SC' . $old->special_condition_id;
                        $newScId = DB::connection($this->NEW)->table('cfg_special_conditions')
                            ->where('code', $scCode)->value('id');
                        if (!$newScId) {
                            $skipped++;
                            $bar->advance();
                            continue;
                        }

                        $exists = DB::connection($this->NEW)->table('quo_special_conditions')
                            ->where('quotation_version_id', $newVersionId)
                            ->where('special_condition_id', $newScId)
                            ->exists();
                        if ($exists) {
                            $skipped++;
                            $bar->advance();
                            continue;
                        }

                        if (!$dryRun) {
                            DB::connection($this->NEW)->table('quo_special_conditions')->insert([
                                'quotation_version_id' => $newVersionId,
                                'special_condition_id' => $newScId,
                                'custom_text'          => null,
                                'display_order'        => 1,
                                'created_at'           => $old->created_at,
                                'updated_at'           => $old->updated_at,
                                'deleted_at'           => $old->deleted_at ?? null,
                            ]);
                        }
                        $inserted++;
                    } catch (Throwable $e) {
                        $errors++;
                        $this->newLine();
                        $this->error("   ✗ special_condition id={$old->id}: " . $e->getMessage());
                    }
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine(2);
        $this->recordStats('special_conditions', $label, $inserted, $skipped, $errors);
    }

    // ══════════════════════════════════════════════════════════════════════
    // HELPERS
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Sanitiza um valor numérico para caber num DECIMAL(20,2) do MySQL.
     * Valores corrompidos (overflow, infinito, NaN) são devolvidos como null.
     */
   private function sanitizeDecimal(mixed $value, float $maxAllowed = 999_999_999_999.99): ?float
{
    if ($value === null || $value === '') {
        return null;
    }
    $float = (float) $value;
    if (!is_finite($float) || abs($float) > $maxAllowed) {
        return null;  // valor corrompido → null
    }
    return $float;
}

    /**
     * Inserir capitals do objeto em quo_object_risk_capitals (ignora valores nulos/zero).
     */
    private function insertObjectRiskCapitals(int $newObjectId, array $capitals): void
    {
        foreach ($capitals as $type => $value) {
            $safeValue = $this->sanitizeDecimal($value, 9_999_999_999_999_999.99);
            if ($safeValue === null || $safeValue == 0) {
                continue;
            }
            $exists = DB::connection($this->NEW)->table('quo_object_risk_capitals')
                ->where('obj_object_id', $newObjectId)
                ->where('capital_type', $type)
                ->exists();
            if (!$exists) {
                DB::connection($this->NEW)->table('quo_object_risk_capitals')->insert([
                    'obj_object_id' => $newObjectId,
                    'capital_type'  => $type,
                    'capital'       => $safeValue,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }
        }
    }

    private function upsertObjectRiskCapitalAndGetId(int $newObjectId, string $type, mixed $value): ?int
    {
        $safeValue = $this->sanitizeDecimal($value, 9_999_999_999_999_999.99);
        if ($safeValue === null || $safeValue == 0) {
            return null;
        }

        $existing = DB::connection($this->NEW)->table('quo_object_risk_capitals')
            ->where('obj_object_id', $newObjectId)
            ->where('capital_type', $type)
            ->first(['id', 'capital']);

        if ($existing) {
            // Manter sincronizado com o capital do risco migrado
            if ((float) $existing->capital !== (float) $safeValue) {
                DB::connection($this->NEW)->table('quo_object_risk_capitals')
                    ->where('id', $existing->id)
                    ->update([
                        'capital' => $safeValue,
                        'updated_at' => now(),
                    ]);
            }
            return (int) $existing->id;
        }

        return (int) DB::connection($this->NEW)->table('quo_object_risk_capitals')->insertGetId([
            'obj_object_id' => $newObjectId,
            'capital_type'  => $type,
            'capital'       => $safeValue,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    /**
     * Derivar status da cotação a partir das suas versões no banco antigo.
     */
    private function deriveQuotationStatus(int $oldQuotationId): string
    {
        $statuses = DB::connection($this->OLD)->table('quotation_versions')
            ->where('quotation_id', $oldQuotationId)
            ->pluck('status')
            ->toArray();

        if (in_array('Accepted', $statuses)) return 'Accepted';
        if (in_array('Pending',  $statuses)) return 'Pending';
        if (in_array('Rejected', $statuses)) return 'Rejected';
        return 'Draft';
    }

    /**
     * Resolver o agent role id a partir do old commercial_agent_id.
     */
    private function resolveAgentRoleId(?int $oldAgentId): ?int
    {
        if (empty($oldAgentId)) {
            return null;
        }

        $agent = DB::connection($this->OLD)->table('commercial_agents')
            ->where('id', $oldAgentId)->first();
        if (!$agent) {
            return null;
        }

        $newEntityId = $this->entityMap[$agent->entity_id] ?? null;
        if (!$newEntityId) {
            return null;
        }

        return DB::connection($this->NEW)->table('ent_entity_roles')
            ->where('entity_id', $newEntityId)
            ->where('active', 1)
            ->value('id');
    }

    /**
     * Resolver template_code a partir do branch da cotação antiga.
     */
    private function resolveTemplateCode(int $oldQuotationId): ?string
    {
        $brancheId = DB::connection($this->OLD)->table('quotations')
            ->where('id', $oldQuotationId)->value('insurance_branche_id');
        if (!$brancheId) {
            return null;
        }
        return DB::connection($this->OLD)->table('templates')
            ->where('insurance_branche_id', $brancheId)
            ->orderBy('id')
            ->value('code');
    }

    private function resolveTemplateCodeFromVersion(object $oldVersion, int $newQuotationId): ?string
    {
        $newBranchId = DB::connection($this->NEW)->table('quo_quotations')
            ->where('id', $newQuotationId)
            ->value('insurance_branch_id');

        if (!$newBranchId) {
            return $this->resolveTemplateCode($oldVersion->quotation_id);
        }

        // 0) Prioridade: no legado cada coverage já tem template_code.
        // Usar essa fonte primeiro evita erros quando o mapeamento de coverage não é 100% 1:1.
        $oldDerivedTemplateCode = $this->resolveOldTemplateCodeFromVersionCoverages(
            (int) $oldVersion->id,
            (int) $oldVersion->quotation_id,
            (int) $newBranchId
        );
        if ($oldDerivedTemplateCode !== null) {
            return $oldDerivedTemplateCode;
        }

        $oldCoverageIds = DB::connection($this->OLD)->table('quotation_line_items')
            ->where('quotation_version_id', $oldVersion->id)
            ->pluck('coverage_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $newCoverageIds = [];
        foreach ($oldCoverageIds as $oldCoverageId) {
            $mapped = $this->coverageMap[(int) $oldCoverageId] ?? null;
            if ($mapped) {
                $newCoverageIds[] = (int) $mapped;
            }
        }

        $newCoverageIds = array_values(array_unique($newCoverageIds));
        sort($newCoverageIds);

        if (empty($newCoverageIds)) {
            return $this->resolveTemplateCode($oldVersion->quotation_id);
        }

        $cacheKey = $newBranchId . '|' . implode(',', $newCoverageIds);
        if (array_key_exists($cacheKey, $this->templateResolveCache)) {
            return $this->templateResolveCache[$cacheKey];
        }

        $templates = $this->loadTemplateCoverageByBranch((int) $newBranchId);
        if (empty($templates)) {
            return $this->templateResolveCache[$cacheKey] = $this->resolveTemplateCode($oldVersion->quotation_id);
        }

        // 1) Match exacto de coberturas
        foreach ($templates as $code => $templateCoverageIds) {
            if ($templateCoverageIds === $newCoverageIds) {
                return $this->templateResolveCache[$cacheKey] = $code;
            }
        }

        // 2) Melhor aproximação: template que contenha todas as coberturas da versão
        // e com menor número de coberturas extra.
        $bestCode = null;
        $bestExtraCount = PHP_INT_MAX;
        $usedSet = array_flip($newCoverageIds);

        foreach ($templates as $code => $templateCoverageIds) {
            $allCovered = true;
            foreach ($newCoverageIds as $covId) {
                if (!in_array($covId, $templateCoverageIds, true)) {
                    $allCovered = false;
                    break;
                }
            }

            if (!$allCovered) {
                continue;
            }

            $extraCount = 0;
            foreach ($templateCoverageIds as $covId) {
                if (!isset($usedSet[$covId])) {
                    $extraCount++;
                }
            }

            if ($extraCount < $bestExtraCount) {
                $bestExtraCount = $extraCount;
                $bestCode = $code;
            }
        }

        if ($bestCode) {
            return $this->templateResolveCache[$cacheKey] = $bestCode;
        }

        // 3) Fallback para lógica antiga (primeiro template por branch no banco antigo)
        return $this->templateResolveCache[$cacheKey] = $this->resolveTemplateCode($oldVersion->quotation_id);
    }

    /**
     * Resolve template_code de uma versão antiga usando coverages.template_code do legado.
     * Regra simples: quotation_line_items.coverage_id -> coverages.template_code.
     */
    private function resolveOldTemplateCodeFromVersionCoverages(
        int $oldVersionId,
        int $oldQuotationId,
        int $newBranchId
    ): ?string {
        $oldBranchId = DB::connection($this->OLD)->table('quotations')
            ->where('id', $oldQuotationId)
            ->value('insurance_branche_id');

        $codes = DB::connection($this->OLD)->table('quotation_line_items as qli')
            ->join('coverages as c', 'c.id', '=', 'qli.coverage_id')
            ->where('qli.quotation_version_id', $oldVersionId)
            ->whereNotNull('c.template_code')
            ->orderBy('qli.id')
            ->pluck('c.template_code')
            ->map(fn($v) => (string) $v)
            ->filter(fn($v) => $v !== '')
            ->unique()
            ->values();

        if ($codes->isEmpty()) {
            return null;
        }

        foreach ($codes as $code) {
            if ($code === '') {
                continue;
            }

            $existsOnOldBranch = DB::connection($this->OLD)->table('templates')
                ->where('code', (int) $code)
                ->when($oldBranchId, fn($q) => $q->where('insurance_branche_id', $oldBranchId))
                ->exists();
            if (!$existsOnOldBranch) {
                continue;
            }

            if ($this->existsTemplateCodeOnNewBranch($newBranchId, $code)) {
                return $code;
            }
        }

        return null;
    }

    private function existsTemplateCodeOnNewBranch(int $newBranchId, string $code): bool
    {
        return DB::connection($this->NEW)->table('cfg_templates')
            ->where('insurance_branch_id', $newBranchId)
            ->where('code', $code)
            ->whereNull('deleted_at')
            ->exists();
    }

    private function normalizePremiumType(string $legacyType): string
    {
        $map = [
            'premioSimples'       => 'Net',
            'premioEncargos'      => 'Charges',
            'premioComercial'     => 'Commercial',
            'iva'                 => 'Tax_IVA',
            'fga'                 => 'Tax_FGA',
            'premioTotalAnual'    => 'Gross',
            'premioTotalMensal'   => 'Installment_Month',
            'premioTotalSemestre' => 'Installment_Semester',
            'premioTotalTrimestre'=> 'Installment_Quarter',
        ];

        return $map[$legacyType] ?? $legacyType;
    }

    private function loadTemplateCoverageByBranch(int $newBranchId): array
    {
        if (isset($this->templateCoverageByBranch[$newBranchId])) {
            return $this->templateCoverageByBranch[$newBranchId];
        }

        $rows = DB::connection($this->NEW)->table('cfg_templates as t')
            ->leftJoin('cfg_template_coverage as tc', 'tc.template_id', '=', 't.id')
            ->where('t.insurance_branch_id', $newBranchId)
            ->whereNull('t.deleted_at')
            ->select('t.code', 'tc.coverage_id')
            ->orderBy('t.id')
            ->get();

        $byCode = [];
        foreach ($rows as $row) {
            if (empty($row->code)) {
                continue;
            }
            $byCode[$row->code] ??= [];
            if (!empty($row->coverage_id)) {
                $byCode[$row->code][] = (int) $row->coverage_id;
            }
        }

        foreach ($byCode as $code => $coverageIds) {
            $coverageIds = array_values(array_unique($coverageIds));
            sort($coverageIds);
            $byCode[$code] = $coverageIds;
        }

        return $this->templateCoverageByBranch[$newBranchId] = $byCode;
    }

    private function resolveOrCreateCustomerEntityId(?int $oldCustomerId, bool $dryRun): ?int
    {
        if (empty($oldCustomerId)) {
            return null;
        }

        if (isset($this->entityMap[$oldCustomerId])) {
            $newId = $this->entityMap[$oldCustomerId];
            if (!$dryRun) {
                DB::connection($this->NEW)->table('ent_entities')
                    ->where('id', $newId)
                    ->whereNotNull('deleted_at')
                    ->update(['deleted_at' => null, 'updated_at' => now()]);
            }
            return $newId;
        }

        $oldEntity = DB::connection($this->OLD)->table('entities')->where('id', $oldCustomerId)->first();
        if (!$oldEntity) {
            return null;
        }

        $existingId = null;
        if (!empty($oldEntity->nif)) {
            $existingId = DB::connection($this->NEW)->table('ent_entities')
                ->where('nif', $oldEntity->nif)
                ->value('id');
        }

        if (!$existingId) {
            $existingId = DB::connection($this->NEW)->table('ent_entities')
                ->where('name', $oldEntity->name)
                ->value('id');
        }

        if ($existingId) {
            $this->entityMap[$oldCustomerId] = $existingId;
            if (!$dryRun) {
                DB::connection($this->NEW)->table('ent_entities')
                    ->where('id', $existingId)
                    ->whereNotNull('deleted_at')
                    ->update(['deleted_at' => null, 'updated_at' => now()]);
            }
            return $existingId;
        }

        if ($dryRun) {
            $this->entityMap[$oldCustomerId] = $oldCustomerId;
            return $oldCustomerId;
        }

        $entityType = ($oldEntity->type === 'Corporate') ? 'Corporate' : 'Personal';
        $slug = $this->uniqueSlug($oldEntity->slug ?? Str::slug($oldEntity->name), 'ent_entities');

        $newId = DB::connection($this->NEW)->table('ent_entities')->insertGetId([
            'entity_type'       => $entityType,
            'name'              => $oldEntity->name,
            'nif'               => $oldEntity->nif ?? null,
            'id_card'           => null,
            'passport'          => null,
            'temp_identifier'   => null,
            'identifier_status' => empty($oldEntity->nif) ? 'Temporary' : 'Partial',
            'kyc_status'        => 'Not_Started',
            'pep_status'        => 'Not_Checked',
            'us_person'         => 0,
            'status'            => $oldEntity->active ? 'Active' : 'Inactive',
            'active'            => $oldEntity->active ?? 1,
            'slug'              => $slug,
            'created_by'        => null,
            'updated_by'        => null,
            'created_at'        => $oldEntity->created_at,
            'updated_at'        => $oldEntity->updated_at,
            'deleted_at'        => null,
        ]);

        $this->entityMap[$oldCustomerId] = $newId;
        return $newId;
    }

    private function loadRateMappings(): void
    {
        // RC options: assinatura estável rc_base + rc_fac + perc_rate
        DB::connection($this->OLD)->table('rate_rc_opts')
            ->select('id', 'rc_base', 'rc_fac', 'perc_rate')
            ->orderBy('id')
            ->each(function ($old) {
                $newId = DB::connection($this->NEW)->table('cfg_rates_rc_options')
                    ->where('rc_base', $old->rc_base)
                    ->where('rc_fac', $old->rc_fac)
                    ->where('perc_rate', $old->perc_rate)
                    ->value('id');
                if ($newId) {
                    $this->rateRcMap[$old->id] = $newId;
                }
            });

        // Occupants: assinatura seat_min + seat_max + package + mip + dt + df + premium
        DB::connection($this->OLD)->table('rates_occupants')
            ->select('id', 'seat_min', 'seat_max', 'package', 'mip', 'dt', 'df', 'premium')
            ->orderBy('id')
            ->each(function ($old) {
                $newId = DB::connection($this->NEW)->table('cfg_rates_occupants')
                    ->where('seat_min', $old->seat_min)
                    ->where('seat_max', $old->seat_max)
                    ->where('package', $old->package)
                    ->where('mip', $old->mip)
                    ->where('dt', $old->dt)
                    ->where('df', $old->df)
                    ->where('premium', $old->premium)
                    ->value('id');
                if ($newId) {
                    $this->rateOccMap[$old->id] = $newId;
                }
            });

        // Health: usar coverage e branch já mapeados + assinatura funcional
        DB::connection($this->OLD)->table('rates_healths')
            ->select('id', 'insurance_branch_id', 'coverage_id', 'age_min', 'age_max', 'gender', 'type', 'package', 'premium_value')
            ->orderBy('id')
            ->each(function ($old) {
                $newBranchId = $this->resolveNewBranchIdFromOld($old->insurance_branch_id);
                $newCoverageId = !empty($old->coverage_id) ? ($this->coverageMap[$old->coverage_id] ?? null) : null;

                $q = DB::connection($this->NEW)->table('cfg_rates_health')
                    ->where('age_min', $old->age_min)
                    ->where('age_max', $old->age_max)
                    ->where('gender', $old->gender)
                    ->where('type', $old->type)
                    ->where('package', $old->package)
                    ->where('premium_value', $old->premium_value);

                if ($newBranchId) {
                    $q->where('insurance_branch_id', $newBranchId);
                }
                if ($newCoverageId) {
                    $q->where('coverage_id', $newCoverageId);
                }

                $newId = $q->value('id');
                if ($newId) {
                    $this->rateHealthMap[$old->id] = $newId;
                }
            });

        // Automobile: mapear por code + branch (mais estável)
        DB::connection($this->OLD)->table('rates_automobiles')
            ->select('id', 'code', 'insurance_branch_id')
            ->orderBy('id')
            ->each(function ($old) {
                $newBranchId = $this->resolveNewBranchIdFromOld($old->insurance_branch_id);

                $q = DB::connection($this->NEW)->table('cfg_rates_automobile')
                    ->where('code', $old->code);
                if ($newBranchId) {
                    $q->where('insurance_branch_id', $newBranchId);
                }
                $newId = $q->value('id');

                if ($newId) {
                    $this->rateAutoMap[$old->id] = $newId;
                }
            });
    }

    private function resolveNewBranchIdFromOld(?int $oldBranchId): ?int
    {
        if (empty($oldBranchId)) {
            return null;
        }

        $oldCode = DB::connection($this->OLD)->table('insurance_branches')
            ->where('id', $oldBranchId)
            ->value('code');
        if (!$oldCode) {
            return null;
        }

        return DB::connection($this->NEW)->table('cfg_insurance_branches')
            ->where('code', $oldCode)
            ->value('id');
    }

    private function resolveRateId(mixed $oldRateId, ?string $rateOrigin): ?int
    {
        if (empty($oldRateId)) {
            return null;
        }

        $oldRateId = (int) $oldRateId;
        $tableHint = $this->extractRateTableHint($rateOrigin);

        if ($tableHint === 'manual') {
            return null;
        }

        if ($tableHint === 'rc') {
            return $this->rateRcMap[$oldRateId] ?? null;
        }
        if ($tableHint === 'occupants') {
            return $this->rateOccMap[$oldRateId] ?? null;
        }
        if ($tableHint === 'health') {
            return $this->rateHealthMap[$oldRateId] ?? null;
        }
        if ($tableHint === 'automobile') {
            return $this->rateAutoMap[$oldRateId] ?? null;
        }

        // Sem rate_origin confiável: tentar descobrir em qual mapa existe
        foreach ([$this->rateRcMap, $this->rateOccMap, $this->rateHealthMap, $this->rateAutoMap] as $map) {
            if (isset($map[$oldRateId])) {
                return $map[$oldRateId];
            }
        }

        return null;
    }

    private function extractRateTableHint(?string $rateOrigin): ?string
    {
        if (empty($rateOrigin)) {
            return null;
        }

        $origin = strtolower(trim($rateOrigin));

        if (str_contains($origin, 'manual')) {
            return 'manual';
        }
        if (str_contains($origin, 'rate_rc_opt') || str_contains($origin, 'rc_opt')) {
            return 'rc';
        }
        if (str_contains($origin, 'rates_occupant') || str_contains($origin, 'occupant')) {
            return 'occupants';
        }
        if (str_contains($origin, 'rates_health') || str_contains($origin, 'health')) {
            return 'health';
        }
        if (str_contains($origin, 'rates_automobile') || str_contains($origin, 'automobile')) {
            return 'automobile';
        }

        return null;
    }

    private function uniqueSlug(string $base, string $table): string
    {
        $slug = Str::slug($base);
        if (empty($slug)) {
            $slug = 'item-' . uniqid();
        }
        $original = $slug;
        $counter  = 1;
        while (DB::connection($this->NEW)->table($table)->where('slug', $slug)->exists()) {
            $slug = $original . '-' . $counter++;
        }
        return $slug;
    }

    private function recordStats(string $key, string $label, int $inserted, int $skipped, int $errors): void
    {
        $this->stats[$key] = compact('label', 'inserted', 'skipped', 'errors');
        $color = $errors > 0 ? 'red' : ($skipped > 0 ? 'yellow' : 'green');
        $this->line("   <fg={$color}>✓ Inseridos: {$inserted} | Ignorados: {$skipped} | Erros: {$errors}</>");
        $this->newLine();
    }

    private function testConnections(): bool
    {
        $this->line('Testando conexões…');
        foreach ([$this->OLD, $this->NEW] as $conn) {
            try {
                DB::connection($conn)->getPdo();
                $db = config("database.connections.{$conn}.database");
                $this->line("  <fg=green>✓ {$conn}: {$db}</>");
            } catch (Throwable $e) {
                $this->error("  ✗ {$conn} falhou: " . $e->getMessage());
                return false;
            }
        }
        $this->newLine();
        return true;
    }

    private function truncateQuoTables(): void
    {
        $this->warn('Limpando tabelas quo_* / obj_* / ent_* no banco novo…');
        DB::connection($this->NEW)->statement('SET FOREIGN_KEY_CHECKS=0');

        $tables = [
            'quo_special_conditions',
            'quo_object_risk_deductibles',
            'quo_object_risk',
            'quo_premiums',
            'quo_quotation_versions',
            'quo_quotations',
            'quo_object_risk_capitals',
            'obj_salary_aggregates',
            'obj_persons',
            'obj_properties',
            'obj_vehicles',
            'obj_objects',
            'ent_sales_channels',
            'ent_corporate_entities',
            'ent_personal_entities',
            'ent_entities',
        ];

        foreach ($tables as $t) {
            try {
                DB::connection($this->NEW)->table($t)->truncate();
                $this->line("  truncated: {$t}");
            } catch (Throwable $e) {
                $this->warn("  não foi possível truncar {$t}: " . $e->getMessage());
            }
        }

        DB::connection($this->NEW)->statement('SET FOREIGN_KEY_CHECKS=1');
        $this->newLine();
    }

    private function banner(): void
    {
        $this->info('');
        $this->info('╔══════════════════════════════════════════════════════════╗');
        $this->info('║   OMNI INSURANCE — Migração de Cotações                  ║');
        $this->info('╚══════════════════════════════════════════════════════════╝');
        $this->info('');
    }

    private function printSummary(): void
    {
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║   RESUMO                                                     ║');
        $this->info('╠══════════════════════════════════════════════════════════════╣');

        $totI = $totS = $totE = 0;
        foreach ($this->stats as $stat) {
            $l     = str_pad($stat['label'], 48);
            $color = $stat['errors'] > 0 ? 'red' : ($stat['skipped'] > 0 ? 'yellow' : 'green');
            $this->line("║  <fg={$color}>{$l}  +{$stat['inserted']}  ⊘{$stat['skipped']}  ✗{$stat['errors']}</>");
            $totI += $stat['inserted'];
            $totS += $stat['skipped'];
            $totE += $stat['errors'];
        }

        $this->info('╠══════════════════════════════════════════════════════════════╣');
        $this->info("║  TOTAL  +{$totI}  ⊘{$totS}  ✗{$totE}");
        $this->info('╚══════════════════════════════════════════════════════════════╝');

        if ($this->option('dry-run')) {
            $this->warn('⚠  DRY-RUN: nenhum dado foi gravado.');
        }
    }
}
