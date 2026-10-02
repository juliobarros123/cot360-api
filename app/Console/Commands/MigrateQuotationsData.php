<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateQuotationsData extends Command
{
    protected $signature = 'quotations:migrate-data 
                            {--dry-run : Execute sem fazer alterações} 
                            {--force : Força execução sem confirmação}';

    protected $description = 'Migra os dados de quotations do banco de origem para a tabela quotation_versions no banco de destino';

    protected $sourceConnection = 'source_db'; // Conexão com o banco de origem
    protected $destinationConnection = 'mysql'; // Conexão com o banco de destino

    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('=== MIGRAÇÃO DE DADOS PARA TABELA QUOTATION_VERSIONS ===');
        $this->info($isDryRun ? 'MODO DRY-RUN (nenhuma alteração será feita)' : 'MODO EXECUÇÃO');

        if (!$this->runPreChecks()) {
            return 1;
        }

        if (!$force && !$isDryRun) {
            if (!$this->confirm('Deseja continuar com a migração de dados para quotation_versions? Esta operação modificará o banco de dados de destino.')) {
                $this->info('Migração de dados cancelada.');
                return 0;
            }
        }

        try {
            if (!$isDryRun) {
                DB::connection($this->destinationConnection)->transaction(function () {
                    $this->stepPreMigrateData(false);
                    $this->updateRelatedTablesWithMapping(false);
                });
                $this->info('✅ Migração de dados para quotation_versions concluída com sucesso!');
            } else {
                $this->stepPreMigrateData(true);
                $this->updateRelatedTablesWithMapping(true);
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

        // Verificar tabela quotations no banco de origem
        if (!Schema::connection($this->sourceConnection)->hasTable('quotations')) {
            $this->error('❌ Tabela quotations não encontrada no banco de origem');
            return false;
        }

        // Verificar tabela quotation_versions no banco de destino
        if (!Schema::connection($this->destinationConnection)->hasTable('quotation_versions')) {
            $this->error('❌ Tabela quotation_versions não encontrada no banco de destino');
            return false;
        }

        // Contar registros no banco de origem
        $quotationsCount = DB::connection($this->sourceConnection)->table('quotations')->count();
        $nullQuotations = DB::connection($this->sourceConnection)->table('quotations')->whereNull('quotation_number')->count();
        $deletedQuotations = Schema::connection($this->sourceConnection)->hasColumn('quotations', 'deleted_at')
            ? DB::connection($this->sourceConnection)->table('quotations')->whereNotNull('deleted_at')->count()
            : 0;

        $this->info("📊 Dados encontrados no banco de origem:");
        $this->info("   - Quotations totais: {$quotationsCount}");
        $this->info("   - Quotations com quotation_number nulo: {$nullQuotations}");
        $this->info("   - Quotations deletadas (soft delete): {$deletedQuotations}");

        if ($quotationsCount == 0) {
            $this->warn('⚠️ Nenhuma quotation encontrada no banco de origem. Migração desnecessária.');
            return false;
        }

        return true;
    }

    protected function stepPreMigrateData($isDryRun): void
    {
        $this->info('📝 Migrando dados das colunas obsoletas para quotation_versions...');

        if ($isDryRun) {
            $this->info('   📊 No modo dry-run: salvaria dados das colunas obsoletas do banco de origem');
            return;
        }

        // Carregar pares existentes de quotation_id e version para evitar duplicatas
        $existingVersions = DB::connection($this->destinationConnection)
            ->table('quotation_versions')
            ->select('quotation_id', 'version')
            ->get()
            ->keyBy(function ($item) {
                return "{$item->quotation_id}_{$item->version}";
            })->keys()->toArray();

        // Consultar dados do banco de origem
        $columns = ['id', 'quotation_number', 'created_at', 'updated_at', 'deleted_at'];
        $obsoleteColumns = [
            'commercial_agent_id',
            'sales_channel_id',
            'cae_rates_branche_id',
            'total_premium',
            'status',
            'created_date',
            'valid_until',
            'policy_id',
            'discountPerc',
            'version_number'
        ];
        foreach ($obsoleteColumns as $column) {
            if (Schema::connection($this->sourceConnection)->hasColumn('quotations', $column)) {
                $columns[] = $column;
            }
        }

        // Agrupar quotations por quotation_number
        $groupedQuotations = DB::connection($this->sourceConnection)
            ->table('quotations')
            ->select($columns)
            ->orderBy('id')
            ->get()
            ->groupBy(function ($item) {
                return $item->quotation_number ?? 'MIGR_' . $item->id;
            });

        $this->info("   🔄 {$groupedQuotations->count()} grupos de quotations encontrados no banco de origem");

        $insertedCount = 0;

        // Processar cada grupo
        foreach ($groupedQuotations as $quotationNumber => $quotations) {
            // Ordenar por id para garantir consistência
            $quotations = $quotations->sortBy('id');
            $masterQuotationId = $quotations->first()->id;

            foreach ($quotations as $index => $quotation) {
                // Atribuir versão: '1' para o primeiro, '2' para o segundo, etc.
                $version = $quotation->version_number ?? (string) ($index + 1);

                // Verificar se o par quotation_id e version já existe
                $versionKey = "{$masterQuotationId}_{$version}";
                if (in_array($versionKey, $existingVersions)) {
                    $this->info("   ℹ️ Registro com quotation_id={$masterQuotationId} e version={$version} já existe. Pulando...");
                    continue;
                }

                DB::connection($this->destinationConnection)->table('quotation_versions')->insert([
                    'quotation_id' => $masterQuotationId, // Usar o ID do primeiro registro do grupo
                    'version' => $version,
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

                $insertedCount++;
            }
        }

        $this->info("   ✅ {$insertedCount} novos registros inseridos em quotation_versions no banco de destino");
    }


    protected function updateRelatedTablesWithMapping($isDryRun): void
    {
        if ($isDryRun) {
            $this->info('   📊 No modo dry-run: atualizaria relações em quotation_line_items, quotation_premiums e quotation_special_conditions');

            // Simular a contagem de registros que seriam afetados
            $lineItemsCount = DB::connection($this->sourceConnection)->table('quotation_line_items')->count();
            $premiumsCount = DB::connection($this->sourceConnection)->table('quotation_premiums')->count();

            $specialConditionsCount = 0;
            if (Schema::connection($this->sourceConnection)->hasTable('quotation_special_conditions')) {
                $specialConditionsCount = DB::connection($this->sourceConnection)->table('quotation_special_conditions')->count();
            }

            $this->info("   📈 Seriam processados:");
            $this->info("      - {$lineItemsCount} quotation_line_items");
            $this->info("      - {$premiumsCount} quotation_premiums");
            $this->info("      - {$specialConditionsCount} quotation_special_conditions");

            // Simular algumas atualizações que seriam feitas
            $sampleLineItems = DB::connection($this->sourceConnection)
                ->table('quotation_line_items')
                ->limit(3)
                ->get();

            foreach ($sampleLineItems as $item) {
                $quotation = DB::connection($this->sourceConnection)
                    ->table('quotations')
                    ->where('id', $item->quotation_id)
                    ->first();

                if ($quotation) {
                    $baseQuotation = DB::connection($this->sourceConnection)
                        ->table('quotations')
                        ->where('quotation_number', $quotation->quotation_number)
                        ->orderBy('id', 'asc')
                        ->first();

                    // VERIFICAR se a quotation base existe no destino
                    $baseQuotationExists = DB::connection($this->destinationConnection)
                        ->table('quotations')
                        ->where('id', $baseQuotation->id)
                        ->exists();

                    if ($baseQuotationExists) {
                        $quotationVersion = DB::connection($this->destinationConnection)
                            ->table('quotation_versions')
                            ->where('quotation_id', $baseQuotation->id)
                            ->where('version', $quotation->version_number)
                            ->first();

                        if ($quotationVersion) {
                            $this->info("   🔄 Atualizaria quotation_line_items: quotation_id {$item->quotation_id} → {$quotationVersion->id}");
                        } else {
                            $this->info("   ⚠️  Version não encontrada para quotation_id {$baseQuotation->id}, version {$quotation->version_number}");
                        }
                    } else {
                        $this->info("   ⚠️  Base quotation não existe no destino: ID {$baseQuotation->id}");
                    }
                }
            }

            // Simular eliminação de cotações não-base
            $nonBaseQuotations = DB::connection($this->destinationConnection)
                ->table('quotations')
                ->whereNotIn('id', function ($query) {
                    $query->select('quotation_id')
                        ->from('quotation_versions')
                        ->whereColumn('quotation_versions.quotation_id', 'quotations.id');
                })
                ->count();

            $this->info("   🗑️  Seriam eliminadas {$nonBaseQuotations} cotações não-base da tabela quotations");

            $this->info('   ✅ Simulação dry-run concluída - nenhuma alteração real foi feita');
            return;
        }

        // Modo de execução real - CORRIGIDO
        $this->info('   🔄 Atualizando quotation_line_items...');
        $lineItems = DB::connection($this->sourceConnection)->table('quotation_line_items')->get();
        $lineItemsUpdated = 0;
        $lineItemsSkipped = 0;

        foreach ($lineItems as $item) {
            // Buscar a quotation original
            $quotation = DB::connection($this->sourceConnection)
                ->table('quotations')
                ->where('id', $item->quotation_id)
                ->first();

            if (!$quotation) {
                $this->warn("   ⚠️  Quotation não encontrada para line_item {$item->id}");
                $lineItemsSkipped++;
                continue;
            }

            // Buscar a quotation base (primeira versão)
            $baseQuotation = DB::connection($this->sourceConnection)
                ->table('quotations')
                ->where('quotation_number', $quotation->quotation_number)
                ->orderBy('id', 'asc')
                ->first();

            if (!$baseQuotation) {
                $this->warn("   ⚠️  Base quotation não encontrada para quotation_number {$quotation->quotation_number}");
                $lineItemsSkipped++;
                continue;
            }

            // VERIFICAR se a quotation base existe no banco de destino
            $baseQuotationExists = DB::connection($this->destinationConnection)
                ->table('quotations')
                ->where('id', $baseQuotation->id)
                ->exists();

            if (!$baseQuotationExists) {
                $this->warn("   ⚠️  Base quotation não existe no destino: ID {$baseQuotation->id}");
                $lineItemsSkipped++;
                continue;
            }

            // Buscar a quotation_version correspondente
            $quotationVersion = DB::connection($this->destinationConnection)
                ->table('quotation_versions')
                ->where('quotation_id', $baseQuotation->id)
                ->where('version', $quotation->version_number)
                ->first();

            if (!$quotationVersion) {
                $this->warn("   ⚠️  Quotation version não encontrada para quotation_id {$baseQuotation->id}, version {$quotation->version_number}");
                $lineItemsSkipped++;
                continue;
            }

            // VERIFICAR se o line_item existe no destino antes de atualizar
            $lineItemExists = DB::connection($this->destinationConnection)
                ->table('quotation_line_items')
                ->where('id', $item->id)
                ->exists();

            if (!$lineItemExists) {
                $this->warn("   ⚠️  Line item não existe no destino: ID {$item->id}");
                $lineItemsSkipped++;
                continue;
            }

            // FINALMENTE atualizar
            try {
                $affected = DB::connection($this->destinationConnection)
                    ->table('quotation_line_items')
                    ->where('id', $item->id)
                    ->update(['quotation_version_id' => $quotationVersion->id]);

                if ($affected > 0) {
                    $lineItemsUpdated++;
                    $this->info("   ✅ Atualizado line_item {$item->id}: quotation_version_id {$quotationVersion->id} quotation_id  {$item->quotation_id} → ");
                }
            } catch (\Exception $e) {
                $this->error("   ❌ Erro ao atualizar line_item {$item->id}: " . $e->getMessage());
                $lineItemsSkipped++;
            }
        }

        $this->info("   📊 Resultado quotation_line_items: {$lineItemsUpdated} atualizados, {$lineItemsSkipped} ignorados");

        // Atualizar quotation_premiums - COM VERIFICAÇÕES SIMILARES
        $this->info('   🔄 Atualizando quotation_premiums...');
        $premiums = DB::connection($this->sourceConnection)->table('quotation_premiums')->get();
        $premiumsUpdated = 0;
        $premiumsSkipped = 0;

        foreach ($premiums as $premium) {
            $quotation = DB::connection($this->sourceConnection)
                ->table('quotations')
                ->where('id', $premium->quotation_id)
                ->first();

            if (!$quotation) {
                $premiumsSkipped++;
                continue;
            }

            $baseQuotation = DB::connection($this->sourceConnection)
                ->table('quotations')
                ->where('quotation_number', $quotation->quotation_number)
                ->orderBy('id', 'asc')
                ->first();

            if (!$baseQuotation) {
                $premiumsSkipped++;
                continue;
            }

            // Verificar existência no destino
            $baseQuotationExists = DB::connection($this->destinationConnection)
                ->table('quotations')
                ->where('id', $baseQuotation->id)
                ->exists();

            if (!$baseQuotationExists) {
                $premiumsSkipped++;
                continue;
            }

            $quotationVersion = DB::connection($this->destinationConnection)
                ->table('quotation_versions')
                ->where('quotation_id', $baseQuotation->id)
                ->where('version', $quotation->version_number)
                ->first();

            if (!$quotationVersion) {
                $premiumsSkipped++;
                continue;
            }

            // Verificar se o premium existe no destino
            $premiumExists = DB::connection($this->destinationConnection)
                ->table('quotation_premiums')
                ->where('id', $premium->id)
                ->exists();

            if (!$premiumExists) {
                $premiumsSkipped++;
                continue;
            }

            try {
                $affected = DB::connection($this->destinationConnection)
                    ->table('quotation_premiums')
                    ->where('id', $premium->id)
                    ->update(['quotation_version_id' => $quotationVersion->id]);

                if ($affected > 0) {
                    $premiumsUpdated++;
                }
            } catch (\Exception $e) {
                $premiumsSkipped++;
            }
        }
        $this->info("   ✅ {$premiumsUpdated} quotation_premiums atualizados, {$premiumsSkipped} ignorados");

        // Atualizar quotation_special_conditions (se a tabela existir)
        if (Schema::connection($this->sourceConnection)->hasTable('quotation_special_conditions')) {
            $this->info('   🔄 Atualizando quotation_special_conditions...');
            $specialConditions = DB::connection($this->sourceConnection)->table('quotation_special_conditions')->get();
            $conditionsUpdated = 0;
            $conditionsSkipped = 0;

            foreach ($specialConditions as $condition) {
                $quotation = DB::connection($this->sourceConnection)
                    ->table('quotations')
                    ->where('id', $condition->quotation_id)
                    ->first();

                if (!$quotation) {
                    $conditionsSkipped++;
                    continue;
                }

                $baseQuotation = DB::connection($this->sourceConnection)
                    ->table('quotations')
                    ->where('quotation_number', $quotation->quotation_number)
                    ->orderBy('id', 'asc')
                    ->first();

                if (!$baseQuotation) {
                    $conditionsSkipped++;
                    continue;
                }

                // Verificar existência no destino
                $baseQuotationExists = DB::connection($this->destinationConnection)
                    ->table('quotations')
                    ->where('id', $baseQuotation->id)
                    ->exists();

                if (!$baseQuotationExists) {
                    $conditionsSkipped++;
                    continue;
                }

                $quotationVersion = DB::connection($this->destinationConnection)
                    ->table('quotation_versions')
                    ->where('quotation_id', $baseQuotation->id)
                    ->where('version', $quotation->version_number)
                    ->first();

                if (!$quotationVersion) {
                    $conditionsSkipped++;
                    continue;
                }

                // Verificar se a condition existe no destino
                $conditionExists = DB::connection($this->destinationConnection)
                    ->table('quotation_special_conditions')
                    ->where('id', $condition->id)
                    ->exists();

                if (!$conditionExists) {
                    $conditionsSkipped++;
                    continue;
                }

                try {
                    $affected = DB::connection($this->destinationConnection)
                        ->table('quotation_special_conditions')
                        ->where('id', $condition->id)
                        ->update(['quotation_version_id' => $quotationVersion->id]);

                    if ($affected > 0) {
                        $conditionsUpdated++;
                    }
                } catch (\Exception $e) {
                    $conditionsSkipped++;
                }
            }
            $this->info("   ✅ {$conditionsUpdated} quotation_special_conditions atualizados, {$conditionsSkipped} ignorados");
        }

        // ETAPA FINAL: Eliminar cotações que não são bases
        $this->info('   🗑️  Eliminando cotações não-base da tabela quotations...');

        try {
            // Identificar cotações que não são referenciadas em quotation_versions como quotation_id base
            $nonBaseQuotations = DB::connection($this->destinationConnection)
                ->table('quotations')
                ->whereNotIn('id', function ($query) {
                    $query->select('quotation_id')
                        ->from('quotation_versions')
                        ->whereColumn('quotation_versions.quotation_id', 'quotations.id');
                })
                ->get();

            $deletedCount = 0;

            foreach ($nonBaseQuotations as $quotation) {
                $this->info("   🔍 Verificando quotation ID {$quotation->id} (Nº: {$quotation->quotation_number}, Versão: {$quotation->version_number})");

                // Verificar se existem quotation_versions que referenciam esta quotation como base
                $hasVersions = DB::connection($this->destinationConnection)
                    ->table('quotation_versions')
                    ->where('quotation_id', $quotation->id)
                    ->exists();

                if (!$hasVersions) {
                    // Verificar se existem relações dependentes antes de eliminar
                    $hasLineItems = DB::connection($this->destinationConnection)
                        ->table('quotation_line_items')
                        ->where('quotation_id', $quotation->id)
                        ->exists();

                    $hasPremiums = DB::connection($this->destinationConnection)
                        ->table('quotation_premiums')
                        ->where('quotation_id', $quotation->id)
                        ->exists();

                    $hasSpecialConditions = false;
                    if (Schema::connection($this->destinationConnection)->hasTable('quotation_special_conditions')) {
                        $hasSpecialConditions = DB::connection($this->destinationConnection)
                            ->table('quotation_special_conditions')
                            ->where('quotation_id', $quotation->id)
                            ->exists();
                    }

                    if (!$hasLineItems && !$hasPremiums && !$hasSpecialConditions) {
                        // Eliminar a quotation não-base
                        DB::connection($this->destinationConnection)
                            ->table('quotations')
                            ->where('id', $quotation->id)
                            ->delete();

                        $deletedCount++;
                        $this->info("   ✅ Eliminada quotation não-base ID {$quotation->id}");
                    } else {
                        $this->warn("   ⚠️  Quotation ID {$quotation->id} tem dependências (line_items: {$hasLineItems}, premiums: {$hasPremiums}, special_conditions: {$hasSpecialConditions}) - mantida por segurança");
                    }
                } else {
                    $this->info("   ℹ️  Quotation ID {$quotation->id} é uma quotation base (tem versões) - mantida");
                }
            }

            $this->info("   ✅ Eliminação concluída: {$deletedCount} cotações não-base removidas");

        } catch (\Exception $e) {
            $this->error("   ❌ Erro ao eliminar cotações não-base: " . $e->getMessage());
        }

        $this->info('   ✅ Processo de atualização de relações concluído');
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
            $this->info('');
            $this->info('📊 ESTATÍSTICAS ESTIMADAS (DRY-RUN) NO BANCO DE ORIGEM:');
            $this->info("   - Quotations totais: {$quotationsCount}");
            $this->info("   - Quotations únicas (por quotation_number): {$uniqueQuotationNumbers}");
            $this->info("   - Versions que seriam criadas: {$quotationsCount}");
            $this->info('');
            return;
        }

        $versionsCount = DB::connection($this->destinationConnection)->table('quotation_versions')->count();
        $uniqueQuotationIds = DB::connection($this->destinationConnection)
            ->table('quotation_versions')
            ->select('quotation_id')
            ->groupBy('quotation_id')
            ->get()
            ->count();
        $this->info('');
        $this->info('📊 ESTATÍSTICAS FINAIS NO BANCO DE DESTINO:');
        $this->info("   - Versions: {$versionsCount}");
        $this->info("   - Quotations únicas (por quotation_id): {$uniqueQuotationIds}");
        $this->info('');
    }
}