<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Quotation;
use Illuminate\Support\Str;

class MigrateQuotationsData extends Command
{
    protected $signature = 'quotations:migrate-data 
                            {--dry-run : Execute sem fazer alterações} 
                            {--force : Força execução sem confirmação}
                            {--rollback : Desfaz a migração}';

    protected $description = 'Migra os dados de quotations do banco de origem para o sistema de versões no banco de destino';

    protected $sourceConnection = 'source_db'; // Conexão com o banco de origem
    protected $destinationConnection = 'mysql'; // Conexão com o banco de destino

    public function handle()
    {
        if ($this->option('rollback')) {
            return $this->rollbackMigration();
        }

        $isDryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('=== MIGRAÇÃO DE DADOS PARA SISTEMA DE VERSÕES ===');
        $this->info($isDryRun ? 'MODO DRY-RUN (nenhuma alteração será feita)' : 'MODO EXECUÇÃO');

        if (!$this->runPreChecks()) {
            return 1;
        }

        if (!$force && !$isDryRun) {
            if (!$this->confirm('Deseja continuar com a migração de dados? Esta operação modificará o banco de dados de destino.')) {
                $this->info('Migração de dados cancelada.');
                return 0;
            }
        }

        try {
            if (!$isDryRun) {
                DB::connection($this->destinationConnection)->transaction(function () {
                    $this->performMigrationSteps();
                });
                $this->info('✅ Migração de dados concluída com sucesso!');
            } else {
                $this->performMigrationSteps();
                $this->info('✅ Dry-run concluído. Nenhuma alteração foi feita.');
            }

            $this->showFinalStats();
        } catch (\Exception $e) {
            $this->error('❌ Erro durante a migração de dados: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }

        return 0;
    }

    protected function runPreChecks(): bool
    {
        $this->info('🔍 Executando verificações preliminares no banco de origem...');

        // Verificar tabelas no banco de origem
        if (!Schema::connection($this->sourceConnection)->hasTable('quotations')) {
            $this->error('❌ Tabela quotations não encontrada no banco de origem');
            return false;
        }

        if (!Schema::connection($this->sourceConnection)->hasTable('quotation_line_items')) {
            $this->error('❌ Tabela quotation_line_items não encontrada no banco de origem');
            return false;
        }

        if (!Schema::connection($this->sourceConnection)->hasTable('insurance_branches')) {
            $this->error('❌ Tabela insurance_branches não encontrada no banco de origem');
            return false;
        }

        if (!Schema::connection($this->sourceConnection)->hasTable('quotation_premiums')) {
            $this->error('❌ Tabela quotation_premiums não encontrada no banco de origem');
            return false;
        }

        if (!Schema::connection($this->sourceConnection)->hasTable('quotation_special_conditions')) {
            $this->warn('⚠️ Tabela quotation_special_conditions não encontrada no banco de origem. Migração de special_conditions será ignorada.');
        }

        if (!Schema::connection($this->sourceConnection)->hasTable('special_conditions')) {
            $this->warn('⚠️ Tabela special_conditions não encontrada no banco de origem. A chave estrangeira será ignorada.');
        }

        if (!Schema::connection($this->sourceConnection)->hasTable('commercial_agents')) {
            $this->warn('⚠️ Tabela commercial_agents não encontrada no banco de origem. A chave estrangeira será ignorada.');
        }

        if (!Schema::connection($this->sourceConnection)->hasTable('sales_channels')) {
            $this->warn('⚠️ Tabela sales_channels não encontrada no banco de origem. A chave estrangeira será ignorada.');
        }

        if (!Schema::connection($this->sourceConnection)->hasTable('cae_rates_branches')) {
            $this->warn('⚠️ Tabela cae_rates_branches não encontrada no banco de origem. A chave estrangeira será ignorada.');
        }

        if (!Schema::connection($this->sourceConnection)->hasTable('policies')) {
            $this->warn('⚠️ Tabela policies não encontrada no banco de origem. A chave estrangeira será ignorada.');
        }

        // Contar registros no banco de origem
        $quotationsCount = DB::connection($this->sourceConnection)->table('quotations')->count();
        $uniqueQuotationNumbers = DB::connection($this->sourceConnection)
            ->table('quotations')
            ->select('quotation_number')
            ->groupBy('quotation_number')
            ->get()
            ->count();
        $lineItemsCount = DB::connection($this->sourceConnection)->table('quotation_line_items')->count();
        $premiumsCount = DB::connection($this->sourceConnection)->table('quotation_premiums')->count();
        $specialConditionsCount = Schema::connection($this->sourceConnection)->hasTable('quotation_special_conditions')
            ? DB::connection($this->sourceConnection)->table('quotation_special_conditions')->count()
            : 0;
        $nullQuotations = DB::connection($this->sourceConnection)->table('quotations')->whereNull('quotation_number')->count();
        $deletedQuotations = Schema::connection($this->sourceConnection)->hasColumn('quotations', 'deleted_at')
            ? DB::connection($this->sourceConnection)->table('quotations')->whereNotNull('deleted_at')->count()
            : 0;

        $this->info("📊 Dados encontrados no banco de origem:");
        $this->info("   - Quotations totais: {$quotationsCount}");
        $this->info("   - Quotations únicas (por quotation_number): {$uniqueQuotationNumbers}");
        $this->info("   - Line Items: {$lineItemsCount}");
        $this->info("   - Premiums: {$premiumsCount}");
        $this->info("   - Special Conditions: {$specialConditionsCount}");
        $this->info("   - Quotations com quotation_number nulo: {$nullQuotations}");
        $this->info("   - Quotations deletadas (soft delete): {$deletedQuotations}");

        if ($quotationsCount == 0) {
            $this->warn('⚠️ Nenhuma quotation encontrada no banco de origem. Migração desnecessária.');
            return false;
        }

        return true;
    }

    protected function performMigrationSteps(): void
    {
        $isDryRun = $this->option('dry-run');

        // Passo 1: Copiar tabelas relacionadas
        // $this->copyRelatedTables($isDryRun);

        // Passo 2: Copiar dados das colunas obsoletas para quotation_versions
        $this->stepPreMigrateData($isDryRun);

        // Passo 3: Criar versões para quotations agrupadas
        $this->step3CreateVersionsForExistingQuotationsGrouped($isDryRun);

        // Passo 4: Atualizar quotation_number com base em insurance_branches.code
        $this->step2UpdateQuotationNumber($isDryRun);

        // Passo 5: Migrar line_items para versões
        $this->step5MigrateLineItemsToVersions($isDryRun);

        // Passo 6: Migrar premiums para versões
        $this->step6MigratePremiumsToVersions($isDryRun);

        // Passo 7: Migrar special_conditions para versões
        $this->step7MigrateSpecialConditionsToVersions($isDryRun);

        // Passo 8: Remover duplicatas em quotations
        $this->step8RemoveDuplicateQuotations($isDryRun);
    }

    // protected function copyRelatedTables($isDryRun): void
    // {
    //     $this->info('📝 Passo 1: Copiando tabelas relacionadas do banco de origem para o destino...');

    //     if ($isDryRun) {
    //         $this->info('   📊 No modo dry-run: copiaria tabelas relacionadas');
    //         return;
    //     }

    //     $tables = ['entities', 'insurance_branches', 'coverages', 'quotation_insured_objects', 'deductibles', 'special_conditions', 'commercial_agents', 'sales_channels', 'cae_rates_branches', 'policies'];

    //     foreach ($tables as $table) {
    //         if (Schema::connection($this->sourceConnection)->hasTable($table)) {
    //             if (!Schema::connection($this->destinationConnection)->hasTable($table)) {
    //                 $this->warn("   ⚠️ Tabela {$table} não existe no banco de destino. Criando estrutura básica...");
    //                 $this->createBasicTable($table);
    //             }

    //             $records = DB::connection($this->sourceConnection)->table($table)->get();
    //             foreach ($records as $record) {
    //                 DB::connection($this->destinationConnection)
    //                     ->table($table)
    //                     ->updateOrInsert(
    //                         ['id' => $record->id],
    //                         (array) $record
    //                     );
    //             }
    //             $this->info("   ✅ Tabela {$table} copiada para o banco de destino");
    //         } else {
    //             $this->warn("   ⚠️ Tabela {$table} não encontrada no banco de origem. Pulando...");
    //         }
    //     }
    // }

    protected function createBasicTable($tableName): void
    {
        Schema::connection($this->destinationConnection)->create($tableName, function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function stepPreMigrateData($isDryRun): void
    {
        $this->info('📝 Passo 2: Salvando dados das colunas obsoletas para quotation_versions...');

        if ($isDryRun) {
            $this->info('   📊 No modo dry-run: salvaria dados das colunas obsoletas do banco de origem');
            return;
        }

        // Limpar quotation_versions se existir
        if (Schema::connection($this->destinationConnection)->hasTable('quotation_versions')) {
            DB::connection($this->destinationConnection)->table('quotation_versions')->truncate();
            $this->info('   ✅ Tabela quotation_versions limpa no banco de destino');
        }

        // Consultar dados do banco de origem
        $columns = ['id', 'quotation_number', 'customer_id', 'insurance_branche_id', 'slug', 'created_at', 'updated_at', 'deleted_at'];
        $obsoleteColumns = [
            'commercial_agent_id', 'sales_channel_id', 'cae_rates_branche_id', 'total_premium',
            'status', 'created_date', 'valid_until', 'policy_id', 'discountPerc', 'version_number'
        ];
        foreach ($obsoleteColumns as $column) {
            if (Schema::connection($this->sourceConnection)->hasColumn('quotations', $column)) {
                $columns[] = $column;
            }
        }

        // Usar chunk para processar grandes quantidades de dados
        DB::connection($this->sourceConnection)
            ->table('quotations')
            ->select($columns)
            ->orderBy('id')
            ->chunk(1000, function ($quotations) {
                foreach ($quotations as $quotation) {
                    DB::connection($this->destinationConnection)->table('quotation_versions')->insert([
                        'quotation_id' => $quotation->id,
                        'version' => $quotation->version_number ?? 'v1',
                        'commercial_agent_id' => $quotation->commercial_agent_id ?? null,
                        'sales_channel_id' => $quotation->sales_channel_id ?? null,
                        'cae_rates_branche_id' => $quotation->cae_rates_branche_id ?? null,
                        'total_premium' => $quotation->total_premium ?? 0.0,
                        'status' => $quotation->status ?? 'Draft',
                        'created_date' => $quotation->created_date ?? now()->toDateString(),
                        'valid_until' => $quotation->valid_until ?? now()->addDays(30)->toDateString(),
                        'policy_id' => $quotation->policy_id ?? null,
                        'discountPerc' => $quotation->discountPerc ?? null,
                        'created_at' => $quotation->created_at,
                        'updated_at' => $quotation->updated_at,
                        'deleted_at' => $quotation->deleted_at,
                    ]);
                }
            });

        $this->info('   ✅ Dados das colunas obsoletas migrados do banco de origem para quotation_versions no banco de destino');
    }

    protected function step3CreateVersionsForExistingQuotationsGrouped($isDryRun): void
    {
        $this->info('📝 Passo 3: Criando versões para quotations existentes agrupando por quotation_number...');

        $groupedQuotations = DB::connection($this->sourceConnection)
            ->table('quotations')
            ->select([
                'id', 'quotation_number', 'customer_id', 'insurance_branche_id', 'slug', 'created_at', 'updated_at', 'deleted_at'
            ])
            ->get()
            ->groupBy(function ($item) {
                return $item->quotation_number ?? 'MIGR_' . $item->id;
            });

        $totalGroups = $groupedQuotations->count();
        $totalEntries = $groupedQuotations->flatten()->count();
        $this->info("   🔄 {$totalGroups} grupos encontrados, com {$totalEntries} entradas totais no banco de origem.");

        if ($isDryRun) {
            $this->info('   📊 No modo dry-run: criaria versões para quotations agrupadas');
            return;
        }

        $newQuotationIdMap = [];

        foreach ($groupedQuotations as $quotationNumber => $group) {
            $masterQuotation = $group->first();
            $newQuotation = Quotation::create([
                'quotation_number' => str_starts_with($quotationNumber, 'MIGR_') ? ('MIGR-' . Str::random(8)) : $quotationNumber,
                'customer_id' => $masterQuotation->customer_id,
                'insurance_branche_id' => $masterQuotation->insurance_branche_id,
                'slug' => $masterQuotation->slug ?? Str::slug((str_starts_with($quotationNumber, 'MIGR_') ? 'migr-' . Str::random(8) : $quotationNumber) . '-' . Str::random(8)),
                'created_at' => $masterQuotation->created_at,
                'updated_at' => $masterQuotation->updated_at,
                'deleted_at' => $masterQuotation->deleted_at,
            ]);

            $newQuotationId = $newQuotation->id;
            $newQuotationIdMap[$masterQuotation->id] = $newQuotationId;

            foreach ($group as $index => $oldQuotation) {
                DB::connection($this->destinationConnection)
                    ->table('quotation_versions')
                    ->where('quotation_id', $oldQuotation->id)
                    ->update(['quotation_id' => $newQuotationId]);
            }
        }

        foreach ($groupedQuotations as $group) {
            $masterId = $group->first()->id;
            $newId = $newQuotationIdMap[$masterId];
            foreach ($group as $oldQuotation) {
                $newQuotationIdMap[$oldQuotation->id] = $newId;
            }
        }

        $this->updateRelatedTablesWithMapping($newQuotationIdMap, $isDryRun);

        $this->info('   ✅ Versões criadas e grupos consolidados no banco de destino');
    }

    protected function updateRelatedTablesWithMapping($mapping, $isDryRun): void
    {
        if ($isDryRun) {
            $this->info('   📊 No modo dry-run: atualizaria relações em quotation_line_items, quotation_premiums e quotation_special_conditions');
            return;
        }

        $lineItems = DB::connection($this->sourceConnection)->table('quotation_line_items')->get();
        foreach ($lineItems as $item) {
            DB::connection($this->destinationConnection)
                ->table('quotation_line_items')
                ->updateOrInsert(
                    ['id' => $item->id],
                    [
                        'quotation_id' => isset($mapping[$item->quotation_id]) ? $mapping[$item->quotation_id] : $item->quotation_id,
                        'quotation_version_id' => $item->quotation_version_id ?? null,
                        'coverage_id' => $item->coverage_id,
                        'insured_object_id' => $item->insured_object_id,
                        'coverage_premium' => $item->coverage_premium,
                        'capital_value' => $item->capital_value,
                        'coverage_perc' => $item->coverage_perc,
                        'applied_rate' => $item->applied_rate,
                        'rate_origin' => $item->rate_origin ?? null,
                        'rate_id' => $item->rate_id ?? null,
                        'deductible_id' => $item->deductible_id ?? null,
                        'slug' => $item->slug,
                        'created_at' => $item->created_at ?? now(),
                        'updated_at' => $item->updated_at ?? now(),
                        'deleted_at' => $item->deleted_at ?? null,
                    ]
                );
        }

        $premiums = DB::connection($this->sourceConnection)->table('quotation_premiums')->get();
        foreach ($premiums as $premium) {
            DB::connection($this->destinationConnection)
                ->table('quotation_premiums')
                ->updateOrInsert(
                    ['id' => $premium->id],
                    [
                        'quotation_id' => isset($mapping[$premium->quotation_id]) ? $mapping[$premium->quotation_id] : $premium->quotation_id,
                        'quotation_version_id' => $premium->quotation_version_id ?? null,
                        'type' => $premium->type,
                        'value' => $premium->value,
                        'status' => $premium->status ?? false,
                        'created_at' => $premium->created_at ?? now(),
                        'updated_at' => $premium->updated_at ?? now(),
                    ]
                );
        }

        if (Schema::connection($this->sourceConnection)->hasTable('quotation_special_conditions')) {
            $specialConditions = DB::connection($this->sourceConnection)->table('quotation_special_conditions')->get();
            foreach ($specialConditions as $condition) {
                DB::connection($this->destinationConnection)
                    ->table('quotation_special_conditions')
                    ->updateOrInsert(
                        ['id' => $condition->id],
                        [
                            'quotation_id' => isset($mapping[$condition->quotation_id]) ? $mapping[$condition->quotation_id] : $condition->quotation_id,
                            'quotation_version_id' => $condition->quotation_version_id ?? null,
                            'special_condition_id' => $condition->special_condition_id,
                            'slug' => $condition->slug,
                            'created_at' => $condition->created_at ?? now(),
                            'updated_at' => $condition->updated_at ?? now(),
                            'deleted_at' => $condition->deleted_at ?? null,
                        ]
                    );
            }
        }

        foreach ($mapping as $oldId => $newId) {
            DB::connection($this->destinationConnection)
                ->table('quotation_line_items')
                ->where('quotation_id', $oldId)
                ->update(['quotation_id' => $newId]);
            $this->info("   - Atualizado quotation_id {$oldId} para {$newId} em line_items");

            DB::connection($this->destinationConnection)
                ->table('quotation_premiums')
                ->where('quotation_id', $oldId)
                ->update(['quotation_id' => $newId]);
            $this->info("   - Atualizado quotation_id {$oldId} para {$newId} em premiums");

            if (Schema::connection($this->destinationConnection)->hasTable('quotation_special_conditions')) {
                DB::connection($this->destinationConnection)
                    ->table('quotation_special_conditions')
                    ->where('quotation_id', $oldId)
                    ->update(['quotation_id' => $newId]);
                $this->info("   - Atualizado quotation_id {$oldId} para {$newId} em special_conditions");
            }
        }

        $this->info('   ✅ Relações atualizadas com novos IDs de quotations no banco de destino');
    }

    protected function step2UpdateQuotationNumber($isDryRun): void
    {
        $this->info('📝 Passo 4: Atualizando quotation_number com base em insurance_branches.code...');

        if ($isDryRun) {
            $this->info('   📊 No modo dry-run: atualizaria quotation_number com base em insurance_branches.code');
            return;
        }

        $quotations = DB::connection($this->sourceConnection)
            ->table('quotations')
            ->leftJoin('insurance_branches', 'quotations.insurance_branche_id', '=', 'insurance_branches.id')
            ->select('quotations.id', 'quotations.quotation_number', 'insurance_branches.code', 'quotations.insurance_branche_id')
            ->get();

        $this->info("   🔄 Processando {$quotations->count()} quotation_numbers...");

        $converted = 0;
        $counter = [];

        foreach ($quotations as $quotation) {
            $code = $quotation->code ?? 'SEGR';
            if (!isset($counter[$code])) {
                $counter[$code] = 0;
            }

            $sequentialNumber = null;
            if (!empty($quotation->quotation_number)) {
                if (preg_match('/^([A-Z0-9]+)-(\d+)$/', $quotation->quotation_number, $matches)) {
                    $sequentialNumber = $matches[2];
                } else {
                    if (is_numeric($quotation->quotation_number)) {
                        $sequentialNumber = $quotation->quotation_number;
                    }
                }
            }

            if (!$sequentialNumber) {
                $counter[$code]++;
                $sequentialNumber = $counter[$code];
            }

            $formattedNumber = str_pad($sequentialNumber, 5, '0', STR_PAD_LEFT);
            $newQuotationNumber = "{$code}-{$formattedNumber}";

            if ($quotation->quotation_number !== $newQuotationNumber) {
                DB::connection($this->destinationConnection)
                    ->table('quotations')
                    ->where('id', $quotation->id)
                    ->update(['quotation_number' => $newQuotationNumber]);
                $converted++;
            }
        }

        $this->info("   ✅ {$converted} quotation_numbers convertidos");
    }

    protected function step5MigrateLineItemsToVersions($isDryRun): void
    {
        $this->info('📝 Passo 5: Migrando line_items para versões no banco de destino...');

        if ($isDryRun) {
            $count = DB::connection($this->destinationConnection)->table('quotation_line_items')->count();
            $this->info("   📊 {$count} line_items seriam migrados");
            return;
        }

        $unmigrated = DB::connection($this->destinationConnection)->table('quotation_line_items')->whereNull('quotation_version_id')->count();
        $matching = DB::connection($this->destinationConnection)
            ->table('quotation_line_items')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('quotation_versions')
                    ->whereColumn('quotation_versions.quotation_id', 'quotation_line_items.quotation_id');
            })->count();
        $this->info("   - Line items sem quotation_version_id: {$unmigrated}");
        $this->info("   - Line items com quotation_id correspondente em quotation_versions: {$matching}");

        $updated = DB::connection($this->destinationConnection)->update("
            UPDATE quotation_line_items qli 
            SET quotation_version_id = (
                SELECT qv.id 
                FROM quotation_versions qv 
                WHERE qv.quotation_id = qli.quotation_id 
                ORDER BY qv.version ASC
                LIMIT 1
            )
            WHERE quotation_version_id IS NULL
        ");

        $this->info("   ✅ {$updated} line_items migrados para versões no banco de destino");
    }

    protected function step6MigratePremiumsToVersions($isDryRun): void
    {
        $this->info('📝 Passo 6: Migrando premiums para versões no banco de destino...');

        if ($isDryRun) {
            $count = DB::connection($this->destinationConnection)->table('quotation_premiums')->count();
            $this->info("   📊 {$count} premiums seriam migrados");
            return;
        }

        $unmigrated = DB::connection($this->destinationConnection)->table('quotation_premiums')->whereNull('quotation_version_id')->count();
        $matching = DB::connection($this->destinationConnection)
            ->table('quotation_premiums')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('quotation_versions')
                    ->whereColumn('quotation_versions.quotation_id', 'quotation_premiums.quotation_id');
            })->count();
        $this->info("   - Premiums sem quotation_version_id: {$unmigrated}");
        $this->info("   - Premiums com quotation_id correspondente em quotation_versions: {$matching}");

        $updated = DB::connection($this->destinationConnection)->update("
            UPDATE quotation_premiums qp
            SET quotation_version_id = (
                SELECT qv.id 
                FROM quotation_versions qv 
                WHERE qv.quotation_id = qp.quotation_id 
                ORDER BY qv.version ASC
                LIMIT 1
            )
            WHERE quotation_version_id IS NULL
        ");

        $this->info("   ✅ {$updated} premiums migrados para versões no banco de destino");
    }

    protected function step7MigrateSpecialConditionsToVersions($isDryRun): void
    {
        $this->info('📝 Passo 7: Migrando special_conditions para versões no banco de destino...');

        if (!Schema::connection($this->destinationConnection)->hasTable('quotation_special_conditions')) {
            $this->warn('   Tabela quotation_special_conditions não existe no banco de destino. Pulando...');
            return;
        }

        if ($isDryRun) {
            $count = DB::connection($this->destinationConnection)->table('quotation_special_conditions')->count();
            $this->info("   📊 {$count} special_conditions seriam migrados");
            return;
        }

        $unmigrated = DB::connection($this->destinationConnection)->table('quotation_special_conditions')->whereNull('quotation_version_id')->count();
        $matching = DB::connection($this->destinationConnection)
            ->table('quotation_special_conditions')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('quotation_versions')
                    ->whereColumn('quotation_versions.quotation_id', 'quotation_special_conditions.quotation_id');
            })->count();
        $this->info("   - Special conditions sem quotation_version_id: {$unmigrated}");
        $this->info("   - Special conditions com quotation_id correspondente em quotation_versions: {$matching}");

        $updated = DB::connection($this->destinationConnection)->update("
            UPDATE quotation_special_conditions qsc
            SET quotation_version_id = (
                SELECT qv.id 
                FROM quotation_versions qv 
                WHERE qv.quotation_id = qsc.quotation_id 
                ORDER BY qv.version ASC
                LIMIT 1
            )
            WHERE quotation_version_id IS NULL
        ");

        $this->info("   ✅ {$updated} special_conditions migrados para versões no banco de destino");
    }

    protected function step8RemoveDuplicateQuotations($isDryRun): void
    {
        $this->info('📝 Passo 8: Removendo entradas duplicadas em quotations no banco de destino...');

        if ($isDryRun) {
            $this->info('   📊 No modo dry-run: removeria entradas duplicadas em quotations');
            return;
        }

        $grouped = DB::connection($this->destinationConnection)
            ->table('quotations')
            ->select(DB::raw('MIN(id) as id'), 'quotation_number')
            ->whereNull('deleted_at')
            ->groupBy('quotation_number')
            ->get();

        $deleted = 0;
        foreach ($grouped as $master) {
            $deleted += DB::connection($this->destinationConnection)
                ->table('quotations')
                ->where('quotation_number', $master->quotation_number)
                ->where('id', '!=', $master->id)
                ->whereNull('deleted_at')
                ->delete();
        }

        $this->info("   ✅ {$deleted} entradas duplicadas removidas no banco de destino");
    }

    protected function showFinalStats(): void
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $quotationsCount = DB::connection($this->sourceConnection)->table('quotations')->count();
            $uniqueQuotationNumbers = DB::connection($this->sourceConnection)
                ->table('quotations')
                ->select('quotation_number')
                ->groupBy('quotation_number')
                ->get()
                ->count();
            $lineItemsCount = DB::connection($this->sourceConnection)->table('quotation_line_items')->count();
            $premiumsCount = DB::connection($this->sourceConnection)->table('quotation_premiums')->count();
            $specialConditionsCount = Schema::connection($this->sourceConnection)->hasTable('quotation_special_conditions')
                ? DB::connection($this->sourceConnection)->table('quotation_special_conditions')->count()
                : 0;

            $this->info('');
            $this->info('📊 ESTATÍSTICAS ESTIMADAS (DRY-RUN) NO BANCO DE ORIGEM:');
            $this->info("   - Quotations totais: {$quotationsCount}");
            $this->info("   - Quotations únicas: {$uniqueQuotationNumbers}");
            $this->info("   - Versions que seriam criadas: {$quotationsCount}");
            $this->info("   - Line Items: {$lineItemsCount}");
            $this->info("   - Line Items que seriam migrados: {$lineItemsCount}");
            $this->info("   - Premiums: {$premiumsCount}");
            $this->info("   - Premiums que seriam migrados: {$premiumsCount}");
            $this->info("   - Special Conditions: {$specialConditionsCount}");
            $this->info("   - Special Conditions que seriam migrados: {$specialConditionsCount}");
            $this->info('');
            return;
        }

        $quotationsCount = DB::connection($this->destinationConnection)->table('quotations')->count();
        $versionsCount = DB::connection($this->destinationConnection)->table('quotation_versions')->count();
        $lineItemsCount = DB::connection($this->destinationConnection)->table('quotation_line_items')->count();
        $migratedLineItems = Schema::connection($this->destinationConnection)->hasColumn('quotation_line_items', 'quotation_version_id')
            ? DB::connection($this->destinationConnection)->table('quotation_line_items')->whereNotNull('quotation_version_id')->count()
            : 0;
        $premiumsCount = DB::connection($this->destinationConnection)->table('quotation_premiums')->count();
        $migratedPremiums = Schema::connection($this->destinationConnection)->hasColumn('quotation_premiums', 'quotation_version_id')
            ? DB::connection($this->destinationConnection)->table('quotation_premiums')->whereNotNull('quotation_version_id')->count()
            : 0;
        $specialConditionsCount = Schema::connection($this->destinationConnection)->hasTable('quotation_special_conditions')
            ? DB::connection($this->destinationConnection)->table('quotation_special_conditions')->count()
            : 0;
        $migratedSpecialConditions = Schema::connection($this->destinationConnection)->hasColumn('quotation_special_conditions', 'quotation_version_id')
            ? DB::connection($this->destinationConnection)->table('quotation_special_conditions')->whereNotNull('quotation_version_id')->count()
            : 0;

        $this->info('');
        $this->info('📊 ESTATÍSTICAS FINAIS NO BANCO DE DESTINO:');
        $this->info("   - Quotations únicas restantes: {$quotationsCount}");
        $this->info("   - Versions: {$versionsCount}");
        $this->info("   - Line Items: {$lineItemsCount}");
        $this->info("   - Line Items migrados: {$migratedLineItems}");
        $this->info("   - Premiums: {$premiumsCount}");
        $this->info("   - Premiums migrados: {$migratedPremiums}");
        $this->info("   - Special Conditions: {$specialConditionsCount}");
        $this->info("   - Special Conditions migrados: {$migratedSpecialConditions}");
        $this->info('');
    }

    protected function rollbackMigration(): int
    {
        $this->warn('🔄 Executando rollback da migração de dados no banco de destino...');
        $this->info('MODO ' . ($this->option('dry-run') ? 'DRY-RUN (nenhuma alteração será feita)' : 'EXECUÇÃO'));

        if ($this->option('dry-run')) {
            $this->info('📊 No modo dry-run: verificaria e reverteria alterações de dados');
            return 0;
        }

        if (!$this->confirm('Tem certeza que deseja desfazer a migração de dados? Esta ação é irreversível no banco de destino.')) {
            $this->info('Rollback cancelado.');
            return 0;
        }

        try {
            DB::connection($this->destinationConnection)->transaction(function () {
                DB::connection($this->destinationConnection)->table('quotation_versions')->truncate();
                $this->info('✅ Tabela quotation_versions limpa');

                DB::connection($this->destinationConnection)->table('quotations')->truncate();
                $this->info('✅ Tabela quotations limpa');

                DB::connection($this->destinationConnection)->table('quotation_line_items')->truncate();
                $this->info('✅ Tabela quotation_line_items limpa');

                DB::connection($this->destinationConnection)->table('quotation_premiums')->truncate();
                $this->info('✅ Tabela quotation_premiums limpa');

                if (Schema::connection($this->destinationConnection)->hasTable('quotation_special_conditions')) {
                    DB::connection($this->destinationConnection)->table('quotation_special_conditions')->truncate();
                    $this->info('✅ Tabela quotation_special_conditions limpa');
                }
            });

            $this->info('✅ Rollback de dados concluído com sucesso!');
            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Erro durante o rollback: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }
    }
}