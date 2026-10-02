<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

class SetupQuotationsSchema extends Command
{
    protected $signature = 'quotations:setup-schema 
                            {--dry-run : Execute sem fazer alterações} 
                            {--force : Força execução sem confirmação}';

    protected $description = 'Configura a estrutura das tabelas para o sistema de versões no banco de destino';

    protected $destinationConnection = 'mysql'; // Conexão com o banco de destino

    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('=== CONFIGURAÇÃO DA ESTRUTURA DAS TABELAS ===');
        $this->info($isDryRun ? 'MODO DRY-RUN (nenhuma alteração será feita)' : 'MODO EXECUÇÃO');

        if (!$force && !$isDryRun) {
            if (!$this->confirm('Deseja continuar com a configuração do esquema? Esta operação modificará o banco de dados de destino.')) {
                $this->info('Configuração cancelada.');
                return 0;
            }
        }

        try {
            $this->setupSchema($isDryRun);
            $this->info('✅ Estrutura das tabelas configurada com sucesso!');
        } catch (\Exception $e) {
            $this->error('❌ Erro durante a configuração do esquema: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }

        return 0;
    }

    protected function setupSchema($isDryRun): void
    {
        $this->info('📝 Configurando estrutura das tabelas no banco de destino...');

        // Passo 0: Removendo colunas obsoletas de quotations
        $this->step0CleanupObsoleteColumns($isDryRun);

        // Passo 1: Criando ou atualizando tabela quotation_versions
        $this->createOrUpdateQuotationVersionsTable($isDryRun);

        // Passo 2: Criando ou atualizando tabela quotations
        $this->createOrUpdateQuotationsTable($isDryRun);

        // Passo 3: Criando ou atualizando tabela quotation_line_items
        $this->createOrUpdateQuotationLineItemsTable($isDryRun);

        // Passo 4: Criando ou atualizando tabela quotation_premiums
        $this->createOrUpdateQuotationPremiumsTable($isDryRun);

        // Passo 5: Criando ou atualizando tabela quotation_special_conditions
        $this->createOrUpdateQuotationSpecialConditionsTable($isDryRun);

        // Passo 6: Adicionando coluna code a insurance_branches
        // $this->addCodeToInsuranceBranches($isDryRun);
    }

    protected function step0CleanupObsoleteColumns($isDryRun): void
    {
        $this->info('📝 Passo 0: Removendo colunas obsoletas de quotations no banco de destino...');

        $columnsToRemove = [
            'parent_quotation_id',
            'version_number',
            'commercial_agent_id',
            'sales_channel_id',
            'cae_rates_branche_id',
            'total_premium',
            'status',
            'created_date',
            'valid_until',
            'policy_id',
            'discountPerc'
        ];

        if ($isDryRun) {
            $this->info('   📊 No modo dry-run: verificaria e removeria colunas obsoletas de quotations');
            foreach ($columnsToRemove as $column) {
                if (Schema::connection($this->destinationConnection)->hasColumn('quotations', $column)) {
                    $this->info("   - Coluna {$column} seria removida");
                }
            }
            return;
        }

        foreach ($columnsToRemove as $column) {
            if (Schema::connection($this->destinationConnection)->hasColumn('quotations', $column)) {
                Schema::connection($this->destinationConnection)->table('quotations', function (Blueprint $table) use ($column) {
                    if (in_array($column, ['parent_quotation_id', 'commercial_agent_id', 'sales_channel_id', 'cae_rates_branche_id'])) {
                        $foreignKey = "quotations_{$column}_foreign";
                        try {
                            $table->dropForeign($foreignKey);
                            $this->info("   ✅ Chave estrangeira {$foreignKey} removida");
                        } catch (\Exception $e) {
                            $this->warn("   ⚠️ Chave estrangeira {$foreignKey} não encontrada. Pulando...");
                        }
                    }
                    if ($column === 'policy_id') {
                        $this->warn("   ⚠️ Coluna {$column} removida sem chave estrangeira");
                    }
                    $table->dropColumn($column);
                });
                $this->info("   ✅ Coluna {$column} removida de quotations no banco de destino");
            }
        }

        $this->info('   ✅ Limpeza de colunas obsoletas concluída no banco de destino');
    }

    protected function createOrUpdateQuotationsTable($isDryRun): void
    {
        $this->info('📝 Passo 2: Criando ou atualizando tabela quotations no banco de destino...');

        if ($isDryRun) {
            $this->info('   📊 No modo dry-run: criaria ou atualizaria tabela quotations');
            return;
        }

        if (!Schema::connection($this->destinationConnection)->hasTable('quotations')) {
            Schema::connection($this->destinationConnection)->create('quotations', function (Blueprint $table) {
                $table->id();
                $table->string('quotation_number')->nullable();
                $table->unsignedBigInteger('customer_id');
                $table->foreign('customer_id')->references('id')->on('entities')->onDelete('CASCADE')->onUpdate('CASCADE');
                $table->unsignedBigInteger('insurance_branche_id');
                $table->foreign('insurance_branche_id')->references('id')->on('insurance_branches')->onDelete('CASCADE')->onUpdate('CASCADE');
                $table->string('slug')->unique();
                $table->softDeletes();
                $table->timestamps();
            });
            $this->info('   ✅ Tabela quotations criada no banco de destino');
        } else {
            Schema::connection($this->destinationConnection)->table('quotations', function (Blueprint $table) {
                if (!Schema::connection($this->destinationConnection)->hasColumn('quotations', 'quotation_number')) {
                    $table->string('quotation_number')->nullable();
                } else {
                    $table->string('quotation_number')->nullable()->change();
                }

                if (!Schema::connection($this->destinationConnection)->hasColumn('quotations', 'customer_id')) {
                    $table->unsignedBigInteger('customer_id');
                    $table->foreign('customer_id')->references('id')->on('entities')->onDelete('CASCADE')->onUpdate('CASCADE');
                }

                if (!Schema::connection($this->destinationConnection)->hasColumn('quotations', 'insurance_branche_id')) {
                    $table->unsignedBigInteger('insurance_branche_id');
                    $table->foreign('insurance_branche_id')->references('id')->on('insurance_branches')->onDelete('CASCADE')->onUpdate('CASCADE');
                }

                if (!Schema::connection($this->destinationConnection)->hasColumn('quotations', 'slug')) {
                    $table->string('slug')->unique();
                }

                if (!Schema::connection($this->destinationConnection)->hasColumn('quotations', 'deleted_at')) {
                    $table->softDeletes();
                }
            });
            $this->info('   ✅ Tabela quotations atualizada no banco de destino');
        }
    }

    protected function createOrUpdateQuotationVersionsTable($isDryRun): void
    {
        $this->info('📝 Passo 1: Criando ou atualizando tabela quotation_versions no banco de destino...');

        if ($isDryRun) {
            $this->info('   📊 No modo dry-run: criaria ou atualizaria tabela quotation_versions');
            return;
        }

        if (!Schema::connection($this->destinationConnection)->hasTable('quotation_versions')) {
            Schema::connection($this->destinationConnection)->create('quotation_versions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('quotation_id');
                $table->foreign('quotation_id')->references('id')->on('quotations')->onDelete('CASCADE')->onUpdate('CASCADE');
                $table->string('version')->default(1);
                $table->string('table')->default('A');
                $table->unsignedBigInteger('commercial_agent_id')->nullable();
                $table->foreign('commercial_agent_id')->references('id')->on('commercial_agents')->onDelete('CASCADE')->onUpdate('CASCADE');
                $table->unsignedBigInteger('sales_channel_id');
                $table->foreign('sales_channel_id')->references('id')->on('sales_channels')->onDelete('CASCADE')->onUpdate('CASCADE');
                $table->unsignedBigInteger('cae_rates_branche_id')->nullable();
                $table->foreign('cae_rates_branche_id')->references('id')->on('cae_rates_branches')->onDelete('CASCADE')->onUpdate('CASCADE');
                $table->double('total_premium');
                $table->double('discountPerc')->nullable();
                $table->enum('status', ['Pending', 'Accepted', 'Rejected', 'Draft', 'Alt_Version'])->default('Pending');
                $table->date('created_date');
                $table->date('valid_until');
                $table->unsignedBigInteger('policy_id')->nullable();
                $table->softDeletes();
                $table->timestamps();

                // $table->index(['quotation_id', 'version']);
                // $table->unique(['quotation_id', 'version']);
            });
            $this->info('   ✅ Tabela quotation_versions criada no banco de destino');
        } else {
            Schema::connection($this->destinationConnection)->table('quotation_versions', function (Blueprint $table) {
                if (!Schema::connection($this->destinationConnection)->hasColumn('quotation_versions', 'quotation_id')) {
                    $table->unsignedBigInteger('quotation_id');
                    $table->foreign('quotation_id')->references('id')->on('quotations')->onDelete('CASCADE')->onUpdate('CASCADE');
                }

                if (!Schema::connection($this->destinationConnection)->hasColumn('quotation_versions', 'version')) {
                    $table->string('version');
                }

                if (!Schema::connection($this->destinationConnection)->hasColumn('quotation_versions', 'commercial_agent_id')) {
                    $table->unsignedBigInteger('commercial_agent_id')->nullable();
                    $table->foreign('commercial_agent_id')->references('id')->on('commercial_agents')->onDelete('CASCADE')->onUpdate('CASCADE');
                }

                if (!Schema::connection($this->destinationConnection)->hasColumn('quotation_versions', 'sales_channel_id')) {
                    $table->unsignedBigInteger('sales_channel_id');
                    $table->foreign('sales_channel_id')->references('id')->on('sales_channels')->onDelete('CASCADE')->onUpdate('CASCADE');
                }

                if (!Schema::connection($this->destinationConnection)->hasColumn('quotation_versions', 'cae_rates_branche_id')) {
                    $table->unsignedBigInteger('cae_rates_branche_id')->nullable();
                    $table->foreign('cae_rates_branche_id')->references('id')->on('cae_rates_branches')->onDelete('CASCADE')->onUpdate('CASCADE');
                }

                if (!Schema::connection($this->destinationConnection)->hasColumn('quotation_versions', 'total_premium')) {
                    $table->double('total_premium');
                }

                if (!Schema::connection($this->destinationConnection)->hasColumn('quotation_versions', 'discountPerc')) {
                    $table->double('discountPerc')->nullable();
                }

                if (!Schema::connection($this->destinationConnection)->hasColumn('quotation_versions', 'status')) {
                    $table->enum('status', ['Pending', 'Accepted', 'Rejected', 'Draft', 'Alt_Version'])->default('Pending');
                }

                if (!Schema::connection($this->destinationConnection)->hasColumn('quotation_versions', 'created_date')) {
                    $table->date('created_date');
                }

                if (!Schema::connection($this->destinationConnection)->hasColumn('quotation_versions', 'valid_until')) {
                    $table->date('valid_until');
                }

                if (!Schema::connection($this->destinationConnection)->hasColumn('quotation_versions', 'policy_id')) {
                    $table->unsignedBigInteger('policy_id')->nullable();
                }

                if (!Schema::connection($this->destinationConnection)->hasColumn('quotation_versions', 'deleted_at')) {
                    $table->softDeletes();
                }

                // $table->index(['quotation_id', 'version']);
                // $table->unique(['quotation_id', 'version']);
            });
            $this->info('   ✅ Tabela quotation_versions atualizada no banco de destino');
        }
    }

    protected function createOrUpdateQuotationLineItemsTable($isDryRun): void
    {
        $this->info('📝 Passo 3: Criando ou atualizando tabela quotation_line_items no banco de destino...');

        if ($isDryRun) {
            $this->info('   📊 No modo dry-run: criaria ou atualizaria tabela quotation_line_items');
            $this->info('   📊 No modo dry-run: removeria coluna quotation_id de quotation_line_items');
            return;
        }

        // Primeiro, remover a coluna quotation_id se existir
        if (Schema::connection($this->destinationConnection)->hasColumn('quotation_line_items', 'quotation_id')) {
            $this->info('   🔍 Removendo coluna quotation_id de quotation_line_items...');

            Schema::connection($this->destinationConnection)->table('quotation_line_items', function (Blueprint $table) {
                // Tentar remover chave estrangeira primeiro, se existir
                try {
                    $table->dropForeign(['quotation_id']);
                    $this->info('   ✅ Chave estrangeira quotation_id removida');
                } catch (\Exception $e) {
                    $this->warn('   ⚠️ Chave estrangeira quotation_id não encontrada. Pulando...');
                }

                $table->dropColumn('quotation_id');
            });
            $this->info('   ✅ Coluna quotation_id removida de quotation_line_items');
        }

        if (!Schema::connection($this->destinationConnection)->hasTable('quotation_line_items')) {
            Schema::connection($this->destinationConnection)->create('quotation_line_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('quotation_version_id')->nullable();
                $table->foreign('quotation_version_id')->references('id')->on('quotation_versions')->onDelete('SET NULL')->onUpdate('CASCADE');
                $table->unsignedBigInteger('coverage_id');
                $table->foreign('coverage_id')->references('id')->on('coverages')->onDelete('CASCADE')->onUpdate('CASCADE');
                $table->unsignedBigInteger('insured_object_id');
                $table->foreign('insured_object_id')->references('id')->on('quotation_insured_objects')->onDelete('CASCADE')->onUpdate('CASCADE');
                $table->double('coverage_premium');
                $table->double('capital_value');
                $table->double('coverage_perc');
                $table->double('applied_rate');
                $table->string('rate_origin')->nullable()->comment('Format: [rate_table_name] (e.g., rates_occupant.12)');
                $table->unsignedBigInteger('rate_id')->nullable();
                $table->unsignedBigInteger('deductible_id')->nullable();
                $table->foreign('deductible_id')->references('id')->on('deductibles')->onDelete('CASCADE')->onUpdate('CASCADE');
                $table->string('slug')->unique();
                $table->softDeletes();
                $table->timestamps();
            });
            $this->info('   ✅ Tabela quotation_line_items criada no banco de destino');
        } else {
            // Passo 1: Adicionar ou atualizar a coluna quotation_version_id
            Schema::connection($this->destinationConnection)->table('quotation_line_items', function (Blueprint $table) {
                if (!Schema::connection($this->destinationConnection)->hasColumn('quotation_line_items', 'quotation_version_id')) {
                    $table->unsignedBigInteger('quotation_version_id')->nullable()->after('id');
                } else {
                    $table->unsignedBigInteger('quotation_version_id')->nullable()->change();
                }
            });

            // Passo 2: Limpar valores inválidos em quotation_version_id, se a coluna existir
            if (Schema::connection($this->destinationConnection)->hasColumn('quotation_line_items', 'quotation_version_id')) {
                $this->info('   🔍 Verificando e limpando valores inválidos em quotation_version_id...');
                try {
                    $invalidCount = DB::connection($this->destinationConnection)
                        ->table('quotation_line_items')
                        ->whereNotNull('quotation_version_id')
                        ->whereNotExists(function ($query) {
                            $query->select(DB::raw(1))
                                ->from('quotation_versions')
                                ->whereColumn('quotation_versions.id', 'quotation_line_items.quotation_version_id');
                        })
                        ->update(['quotation_version_id' => null]);
                    $this->info("   ✅ {$invalidCount} registros com quotation_version_id inválido foram ajustados para NULL");
                } catch (\Exception $e) {
                    $this->warn('   ⚠️ Erro ao limpar valores inválidos em quotation_version_id: ' . $e->getMessage());
                }
            }

            // Passo 3: Atualizar a estrutura da tabela e adicionar a chave estrangeira
            Schema::connection($this->destinationConnection)->table('quotation_line_items', function (Blueprint $table) {
                // Adicionar nova chave estrangeira com SET NULL
                $table->foreign('quotation_version_id')
                    ->references('id')->on('quotation_versions')
                    ->onDelete('SET NULL')
                    ->onUpdate('CASCADE');

                // Outras colunas
                if (!Schema::connection($this->destinationConnection)->hasColumn('quotation_line_items', 'coverage_id')) {
                    $table->unsignedBigInteger('coverage_id');
                    $table->foreign('coverage_id')->references('id')->on('coverages')->onDelete('CASCADE')->onUpdate('CASCADE');
                }

                if (!Schema::connection($this->destinationConnection)->hasColumn('quotation_line_items', 'insured_object_id')) {
                    $table->unsignedBigInteger('insured_object_id');
                    $table->foreign('insured_object_id')->references('id')->on('quotation_insured_objects')->onDelete('CASCADE')->onUpdate('CASCADE');
                }

                if (!Schema::connection($this->destinationConnection)->hasColumn('quotation_line_items', 'coverage_premium')) {
                    $table->double('coverage_premium');
                }

                if (!Schema::connection($this->destinationConnection)->hasColumn('quotation_line_items', 'capital_value')) {
                    $table->double('capital_value');
                }

                if (!Schema::connection($this->destinationConnection)->hasColumn('quotation_line_items', 'coverage_perc')) {
                    $table->double('coverage_perc');
                }

                if (!Schema::connection($this->destinationConnection)->hasColumn('quotation_line_items', 'applied_rate')) {
                    $table->double('applied_rate');
                }

                if (!Schema::connection($this->destinationConnection)->hasColumn('quotation_line_items', 'rate_origin')) {
                    $table->string('rate_origin')->nullable()->comment('Format: [rate_table_name] (e.g., rates_occupant.12)');
                }

                if (!Schema::connection($this->destinationConnection)->hasColumn('quotation_line_items', 'rate_id')) {
                    $table->unsignedBigInteger('rate_id')->nullable();
                }

                if (!Schema::connection($this->destinationConnection)->hasColumn('quotation_line_items', 'deductible_id')) {
                    $table->unsignedBigInteger('deductible_id')->nullable();
                    $table->foreign('deductible_id')->references('id')->on('deductibles')->onDelete('CASCADE')->onUpdate('CASCADE');
                }

                if (!Schema::connection($this->destinationConnection)->hasColumn('quotation_line_items', 'slug')) {
                    $table->string('slug')->unique();
                }

                if (!Schema::connection($this->destinationConnection)->hasColumn('quotation_line_items', 'deleted_at')) {
                    $table->softDeletes();
                }
            });
            $this->info('   ✅ Tabela quotation_line_items atualizada no banco de destino');
        }
    }

    protected function createOrUpdateQuotationPremiumsTable($isDryRun): void
    {
        $this->info('📝 Passo 4: Criando ou atualizando tabela quotation_premiums no banco de destino...');

        if ($isDryRun) {
            $this->info('   📊 No modo dry-run: criaria ou atualizaria tabela quotation_premiums');
            $this->info('   📊 No modo dry-run: removeria coluna quotation_id de quotation_premiums');
            return;
        }

        // Primeiro, remover a coluna quotation_id se existir
        if (Schema::connection($this->destinationConnection)->hasColumn('quotation_premiums', 'quotation_id')) {
            $this->info('   🔍 Removendo coluna quotation_id de quotation_premiums...');

            Schema::connection($this->destinationConnection)->table('quotation_premiums', function (Blueprint $table) {
                // Tentar remover chave estrangeira primeiro, se existir
                try {
                    $table->dropForeign(['quotation_id']);
                    $this->info('   ✅ Chave estrangeira quotation_id removida');
                } catch (\Exception $e) {
                    $this->warn('   ⚠️ Chave estrangeira quotation_id não encontrada. Pulando...');
                }

                // Tentar remover o índice único se existir
                try {
                    $table->dropUnique(['quotation_id', 'type']);
                    $this->info('   ✅ Índice único (quotation_id, type) removido');
                } catch (\Exception $e) {
                    $this->warn('   ⚠️ Índice único (quotation_id, type) não encontrado. Pulando...');
                }

                $table->dropColumn('quotation_id');
            });
            $this->info('   ✅ Coluna quotation_id removida de quotation_premiums');
        }

        if (!Schema::connection($this->destinationConnection)->hasTable('quotation_premiums')) {
            Schema::connection($this->destinationConnection)->create('quotation_premiums', function (Blueprint $table) {
                $table->id();
                $table->foreignId('quotation_version_id')->nullable()->constrained('quotation_versions')->onDelete('set null');
                $table->string('type');
                $table->decimal('value', 15, 2)->default(0);
                $table->boolean('status')->default(false);
                $table->timestamps();

                // Novo índice único baseado apenas no quotation_version_id e type
                $table->unique(['quotation_version_id', 'type']);
            });
            $this->info('   ✅ Tabela quotation_premiums criada no banco de destino');
        } else {
            Schema::connection($this->destinationConnection)->table('quotation_premiums', function (Blueprint $table) {
                if (!Schema::connection($this->destinationConnection)->hasColumn('quotation_premiums', 'quotation_version_id')) {
                    $table->foreignId('quotation_version_id')->nullable()->constrained('quotation_versions')->onDelete('set null');
                }

                // Atualizar o índice único para usar quotation_version_id em vez de quotation_id
                if (!Schema::connection($this->destinationConnection)->hasColumn('quotation_premiums', 'quotation_id')) {
                    // Se quotation_id não existe mais, criar índice único com quotation_version_id
                    $indexExists = DB::connection($this->destinationConnection)
                        ->select("SHOW INDEX FROM quotation_premiums WHERE Key_name = 'quotation_premiums_quotation_version_id_type_unique'");

                    if (empty($indexExists)) {
                        $table->unique(['quotation_version_id', 'type']);
                        $this->info('   ✅ Novo índice único (quotation_version_id, type) criado');
                    }
                }

                // Manter as outras colunas básicas se não existirem
                if (!Schema::connection($this->destinationConnection)->hasColumn('quotation_premiums', 'type')) {
                    $table->string('type');
                }

                if (!Schema::connection($this->destinationConnection)->hasColumn('quotation_premiums', 'value')) {
                    $table->decimal('value', 15, 2)->default(0);
                }

                if (!Schema::connection($this->destinationConnection)->hasColumn('quotation_premiums', 'status')) {
                    $table->boolean('status')->default(false);
                }
            });
            $this->info('   ✅ Tabela quotation_premiums atualizada no banco de destino');
        }
    }
    protected function createOrUpdateQuotationSpecialConditionsTable($isDryRun): void
    {
        $this->info('📝 Passo 5: Criando ou atualizando tabela quotation_special_conditions no banco de destino...');

        if ($isDryRun) {
            $this->info('   📊 No modo dry-run: criaria ou atualizaria tabela quotation_special_conditions');
            return;
        }

        if (!Schema::connection($this->destinationConnection)->hasTable('quotation_special_conditions')) {
            Schema::connection($this->destinationConnection)->create('quotation_special_conditions', function (Blueprint $table) {
                $table->id();
                // $table->foreignId('quotation_id')->constrained()->onDelete('cascade');
                $table->foreignId('quotation_version_id')->nullable()->constrained('quotation_versions')->onDelete('set null');
                $table->unsignedBigInteger('special_condition_id');
                $table->foreign('special_condition_id')->references('id')->on('special_conditions')->onDelete('CASCADE')->onUpdate('CASCADE');
                $table->string('slug')->unique();
                $table->softDeletes();
                $table->timestamps();
            });
            $this->info('   ✅ Tabela quotation_special_conditions criada no banco de destino');
        } else {
            Schema::connection($this->destinationConnection)->table('quotation_special_conditions', function (Blueprint $table) {
                if (!Schema::connection($this->destinationConnection)->hasColumn('quotation_special_conditions', 'quotation_id')) {
                    // $table->foreignId('quotation_id')->constrained()->onDelete('cascade');
                }

                if (!Schema::connection($this->destinationConnection)->hasColumn('quotation_special_conditions', 'quotation_version_id')) {
                    $table->foreignId('quotation_version_id')->nullable()->constrained('quotation_versions')->onDelete('set null');
                }

                if (!Schema::connection($this->destinationConnection)->hasColumn('quotation_special_conditions', 'special_condition_id')) {
                    $table->unsignedBigInteger('special_condition_id');
                    $table->foreign('special_condition_id')->references('id')->on('special_conditions')->onDelete('CASCADE')->onUpdate('CASCADE');
                }

                if (!Schema::connection($this->destinationConnection)->hasColumn('quotation_special_conditions', 'slug')) {
                    $table->string('slug')->unique();
                }

                if (!Schema::connection($this->destinationConnection)->hasColumn('quotation_special_conditions', 'deleted_at')) {
                    $table->softDeletes();
                }
            });
            $this->info('   ✅ Tabela quotation_special_conditions atualizada no banco de destino');
        }
    }

    // protected function addCodeToInsuranceBranches($isDryRun): void

    // {
    //     $this->info('📝 Passo 6: Adicionando coluna code a insurance_branches no banco de destino...');

    //     if ($isDryRun) {
    //         $this->info('   📊 No modo dry-run: adicionaria coluna code a insurance_branches');
    //         return;
    //     }

    //     if (!Schema::connection($this->destinationConnection)->hasTable('insurance_branches')) {
    //         Schema::connection($this->destinationConnection)->create('insurance_branches', function (Blueprint $table) {
    //             $table->id();
    //             $table->string('code')->unique();
    //             $table->string('name');
    //             $table->timestamps();
    //             $table->softDeletes();
    //         });
    //         $this->info('   ✅ Tabela insurance_branches criada com coluna code no banco de destino');
    //     } else if (!Schema::connection($this->destinationConnection)->hasColumn('insurance_branches', 'code')) {
    //         Schema::connection($this->destinationConnection)->table('insurance_branches', function (Blueprint $table) {
    //             $table->string('code')->unique()->after('id');
    //         });
    //         $this->info('   ✅ Coluna code adicionada a insurance_branches no banco de destino');
    //     }
    // }
}