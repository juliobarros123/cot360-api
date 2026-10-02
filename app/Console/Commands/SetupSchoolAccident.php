<?php

namespace App\Console\Commands;

use App\Models\CFG\CFG_coverages;
use App\Models\CFG\CFG_insurance_branches;
use App\Models\CFG\CFG_template_coverage;
use App\Models\CFG\CFG_templates;
use App\Models\CFG\CFG_deductibles;
use App\Models\CFG\Cfg_RatesSchoolAccident;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class SetupSchoolAccident extends Command
{
    protected $signature = 'app:setup-school-accident
                            {--branch-code=14-02 : Código do ramo alvo}
                            {--branch-name=ACIDENTES PESSOAIS ESCOLAR : Nome do ramo alvo}
                            {--force : Forçar a execução mesmo com dependências existentes}
                            {--dry-run : Simular a execução sem alterar o banco de dados}';

    protected $description = 'Prepara de ponta a ponta o ramo Acidentes Pessoais Escolar (ramo, templates, coberturas, vínculos, tarifas e franquias)';

    private array $stats = [
        'templates' => 0,
        'coverages' => 0,
        'template_coverages' => 0,
        'deductibles' => 0,
        'rates' => 0,
    ];

    public function handle(): int
    {
        $branchCode = trim((string) $this->option('branch-code'));
        $branchName = trim((string) $this->option('branch-name'));
        $force = $this->option('force');
        $dryRun = $this->option('dry-run');

        if ($branchCode === '') {
            $this->error('O código do ramo é obrigatório.');
            return self::FAILURE;
        }

        if ($dryRun) {
            $this->warn('🔍 Modo DRY-RUN ativado - Nenhuma alteração será feita no banco de dados');
            $this->newLine();
        }

        try {
            // Verifica se o branch existe
            $branch = CFG_insurance_branches::withTrashed()
                ->where('code', $branchCode)
                ->first();

            if ($branch) {
                $this->line("📋 Ramo encontrado: {$branch->name} (ID: {$branch->id})");
                
                // Verifica dependências
                $dependencies = $this->checkDependencies($branch->id);
                
                if (!empty($dependencies) && !$force && !$dryRun) {
                    $this->warn('⚠️  ATENÇÃO: Existem dependências que serão afetadas:');
                    foreach ($dependencies as $dependency) {
                        $this->line("   - {$dependency['table']}: {$dependency['count']} registros");
                    }
                    
                    if (!$this->confirm('Deseja continuar mesmo assim? Isso pode quebrar dados existentes.')) {
                        $this->info('Operação cancelada.');
                        return self::SUCCESS;
                    }
                }

                if ($force) {
                    $this->warn('⚠️  Modo FORCE ativado - Dependências serão ignoradas');
                }
            } else {
                $this->line('📋 Ramo não encontrado - será criado');
            }

            if ($dryRun) {
                $this->simulateExecution($branchCode, $branchName);
                return self::SUCCESS;
            }

            $result = DB::transaction(function () use ($branchCode, $branchName) {
                $branch = $this->upsertBranch($branchCode, $branchName);
                
                if ($branch->wasRecentlyCreated) {
                    $this->info("✅ Ramo criado: {$branch->name}");
                } else {
                    $this->info("✅ Ramo atualizado: {$branch->name}");
                    $this->resetBranchConfiguration($branch->id);
                    $this->info("✅ Configuração anterior removida");
                }

                $templates = $this->seedTemplates($branch->id);
                $coverages = $this->seedCoverages($branch->id);
                $this->seedTemplateCoverage($templates, $coverages);
                $this->seedDeductibles($coverages);
                $this->seedRates($branch->id, $coverages);

                return $branch;
            });

            if ($result) {
                $this->showSummary($branchCode);
                return self::SUCCESS;
            }

            return self::FAILURE;

        } catch (Throwable $e) {
            $this->error('❌ Falha ao preparar configuração: ' . $e->getMessage());
            $this->error('Linha: ' . $e->getLine() . ' - Arquivo: ' . $e->getFile());
            
            if ($this->getOutput()->isVerbose()) {
                $this->error($e->getTraceAsString());
            }
            
            return self::FAILURE;
        }
    }

    /**
     * Verifica dependências existentes que podem ser afetadas
     */
    private function checkDependencies(int $branchId): array
    {
        $dependencies = [];
        $schema = DB::getSchemaBuilder();

        // Lista de tabelas e suas colunas de chave estrangeira
        $tablesToCheck = [
            'quotes' => ['insurance_branch_id', 'branch_id'],
            'policies' => ['insurance_branch_id', 'branch_id'],
            'claims' => ['insurance_branch_id', 'branch_id'],
            'proposals' => ['insurance_branch_id', 'branch_id'],
            'risk_analysis' => ['insurance_branch_id', 'branch_id'],
            'policy_coverages' => ['coverage_id'],
            'quote_coverages' => ['coverage_id'],
            'contracts' => ['insurance_branch_id', 'branch_id'],
            'endorsements' => ['insurance_branch_id', 'branch_id'],
            'renewals' => ['insurance_branch_id', 'branch_id'],
        ];

        foreach ($tablesToCheck as $table => $columns) {
            // Verifica se a tabela existe
            if (!$schema->hasTable($table)) {
                continue;
            }

            // Verifica cada coluna possível
            foreach ($columns as $column) {
                if (!$schema->hasColumn($table, $column)) {
                    continue;
                }

                try {
                    $count = DB::table($table)
                        ->where($column, $branchId)
                        ->count();

                    if ($count > 0) {
                        $dependencies[] = [
                            'table' => $table . ' (' . $column . ')',
                            'count' => $count,
                        ];
                        break; // Encontrou uma coluna válida, sai do loop
                    }
                } catch (\Exception $e) {
                    // Ignora erros de coluna e continua
                    continue;
                }
            }
        }

        // Verifica coberturas com vínculos
        $coverageIds = CFG_coverages::where('insurance_branch_id', $branchId)
            ->pluck('id')
            ->toArray();

        if (!empty($coverageIds)) {
            // Verifica se há cotações usando estas coberturas
            if ($schema->hasTable('quote_coverages')) {
                try {
                    $count = DB::table('quote_coverages')
                        ->whereIn('coverage_id', $coverageIds)
                        ->count();

                    if ($count > 0) {
                        $dependencies[] = [
                            'table' => 'quote_coverages (coverage_id)',
                            'count' => $count,
                        ];
                    }
                } catch (\Exception $e) {
                    // Ignora erro
                }
            }

            // Verifica se há apólices usando estas coberturas
            if ($schema->hasTable('policy_coverages')) {
                try {
                    $count = DB::table('policy_coverages')
                        ->whereIn('coverage_id', $coverageIds)
                        ->count();

                    if ($count > 0) {
                        $dependencies[] = [
                            'table' => 'policy_coverages (coverage_id)',
                            'count' => $count,
                        ];
                    }
                } catch (\Exception $e) {
                    // Ignora erro
                }
            }
        }

        return $dependencies;
    }

    /**
     * Simula a execução sem alterar o banco
     */
    private function simulateExecution(string $branchCode, string $branchName): void
    {
        $this->newLine();
        $this->info('🔍 SIMULAÇÃO DE EXECUÇÃO');
        $this->line('----------------------------------------');
        
        $branch = CFG_insurance_branches::withTrashed()
            ->where('code', $branchCode)
            ->first();

        if ($branch) {
            $this->line("📋 Ramo existente: {$branch->name} (ID: {$branch->id})");
            
            $coverageCount = CFG_coverages::where('insurance_branch_id', $branch->id)->count();
            $templateCount = CFG_templates::where('insurance_branch_id', $branch->id)->count();
            $rateCount = Cfg_RatesSchoolAccident::where('insurance_branch_id', $branch->id)->count();
            $deductibleCount = CFG_deductibles::whereIn('coverage_id', 
                function($query) use ($branch) {
                    $query->select('id')->from('cfg_coverages')->where('insurance_branch_id', $branch->id);
                }
            )->count();

            $this->line("📊 Configuração atual:");
            $this->line("   - Coberturas: {$coverageCount}");
            $this->line("   - Templates: {$templateCount}");
            $this->line("   - Tarifas: {$rateCount}");
            $this->line("   - Franquias: {$deductibleCount}");
            
            if ($coverageCount > 0 || $templateCount > 0 || $rateCount > 0) {
                $this->warn('⚠️  Todos estes registros serão deletados e recriados');
            }
        } else {
            $this->line("📋 Ramo será criado: {$branchName} (Código: {$branchCode})");
        }

        $this->newLine();
        $this->line("📝 Serão criados:");
        $this->line("   - 3 templates (Opção A, B, C)");
        $this->line("   - 2 coberturas principais + 5 subcoberturas");
        $this->line("   - 21 vínculos template-cobertura");
        $this->line("   - 5 franquias");
        $this->line("   - 6 tarifas");
        
        $this->newLine();
        $this->info('✅ Simulação concluída - Nenhuma alteração foi feita');
    }

    private function upsertBranch(string $branchCode, string $branchName): CFG_insurance_branches
    {
        $branch = CFG_insurance_branches::withTrashed()
            ->where('code', $branchCode)
            ->first();

        if (!$branch) {
            $branch = new CFG_insurance_branches();
            $branch->code = $branchCode;
            $branch->slug = 'branch-school-accident-' . Str::slug($branchCode);
        }

        $branch->name = $branchName;
        $branch->description = 'Ramo preparado automaticamente para cotação de Acidentes Pessoais Escolar';
        $branch->requires_coordinates = false;
        $branch->default_validity_days = 30;
        $branch->policy_number_prefix = 'APE';
        $branch->active = true;
        $branch->save();

        if ($branch->trashed()) {
            $branch->restore();
        }

        return $branch->fresh();
    }

    private function resetBranchConfiguration(int $branchId): void
    {
        // Backup dos IDs para possíveis logs
        $templateIds = CFG_templates::withTrashed()
            ->where('insurance_branch_id', $branchId)
            ->pluck('id');

        $coverageIds = CFG_coverages::withTrashed()
            ->where('insurance_branch_id', $branchId)
            ->pluck('id');

        // Remove vínculos
        if ($templateIds->isNotEmpty()) {
            $deleted = CFG_template_coverage::whereIn('template_id', $templateIds)->delete();
            $this->line("   - Removidos {$deleted} vínculos template-cobertura");
        }

        // Remove tarifas
        $deleted = DB::table('cfg_rates_school_accident')
            ->where('insurance_branch_id', $branchId)
            ->delete();
        if ($deleted > 0) {
            $this->line("   - Removidas {$deleted} tarifas");
        }

        // Remove franquias
        if ($coverageIds->isNotEmpty()) {
            $deleted = DB::table('cfg_deductibles')
                ->whereIn('coverage_id', $coverageIds)
                ->delete();
            if ($deleted > 0) {
                $this->line("   - Removidas {$deleted} franquias");
            }
        }

        // Remove templates
        if ($templateIds->isNotEmpty()) {
            $deleted = CFG_templates::withTrashed()
                ->where('insurance_branch_id', $branchId)
                ->forceDelete();
            $this->line("   - Removidos {$deleted} templates");
        }

        // Remove coberturas
        if ($coverageIds->isNotEmpty()) {
            $deleted = CFG_coverages::withTrashed()
                ->where('insurance_branch_id', $branchId)
                ->forceDelete();
            $this->line("   - Removidas {$deleted} coberturas");
        }
    }

    private function seedTemplates(int $branchId): array
    {
        $rows = [
            [
                'code' => 1,
                'name' => 'Opção A',
                'description' => 'ACIDENTES PESSOAIS ESCOLAR - Opção A',
                'package' => 'Option A',
            ],
            [
                'code' => 2,
                'name' => 'Opção B',
                'description' => 'ACIDENTES PESSOAIS ESCOLAR - Opção B',
                'package' => 'Option B',
            ],
            [
                'code' => 3,
                'name' => 'Opção C',
                'description' => 'ACIDENTES PESSOAIS ESCOLAR - Opção C',
                'package' => 'Option C',
            ],
        ];

        $templates = [];

        foreach ($rows as $row) {
            $template = CFG_templates::create([
                'insurance_branch_id' => $branchId,
                'code' => $row['code'],
                'name' => $row['name'],
                'description' => $row['description'],
                'package' => $row['package'],
                'object_type' => 'Person',
                'qty_objects' => null,
                'active' => true,
                'slug' => 'tpl-school-accident-' . $branchId . '-' . $row['code'] . '-' . Str::slug($row['name']),
            ]);

            $templates[$row['code']] = $template;
            $this->stats['templates']++;
        }

        return $templates;
    }

    private function seedCoverages(int $branchId): array
    {
        $coverages = [];

        // 1. Cobertura Obrigatória (principal)
        $coverageObrigatoria = CFG_coverages::create([
            'insurance_branch_id' => $branchId,
            'group_id' => null,
            'seq' => 1,
            'gis_cod' => 1,
            'type_cov' => '1',
            'short_name' => 'OBRIGATORIA',
            'name' => 'COBERTURAS OBRIGATÓRIAS',
            'description' => 'Coberturas obrigatórias - Invalidez Permanente, Despesas de Tratamento e Repatriamento, Despesas de Funeral',
            'obj_type' => 'PERSON',
            'capital_value' => null,
            'capital_origin' => null,
            'rate_type' => 'FIXED',
            'risk_rate' => null,
            'risk_rate_origin' => 'cfg_rates_school_accident.premium_value',
            'cov_opt' => 'O',
            'type_covt' => 'O',
            'cov_perc' => 0,
            'active' => true,
            'slug' => 'cov-school-accident-' . $branchId . '-1-obrigatoria',
        ]);
        $coverages['obrigatoria'] = $coverageObrigatoria;
        $this->stats['coverages']++;

        // 2. Cobertura Facultativa (principal)
        $coverageFacultativa = CFG_coverages::create([
            'insurance_branch_id' => $branchId,
            'group_id' => null,
            'seq' => 2,
            'gis_cod' => 2,
            'type_cov' => '1',
            'short_name' => 'FACULTATIVA',
            'name' => 'COBERTURAS FACULTATIVAS',
            'description' => 'Coberturas facultativas - Responsabilidade Civil dos Alunos, RC do Estabelecimento, RC Cruzada dos Alunos entre si, RC Cruzada dos Alunos e do Estabelecimento entre si',
            'obj_type' => 'PERSON',
            'capital_value' => null,
            'capital_origin' => null,
            'rate_type' => 'FIXED',
            'risk_rate' => null,
            'risk_rate_origin' => 'cfg_rates_school_accident.premium_value',
            'cov_opt' => null,
            'type_covt' => null,
            'cov_perc' => 0,
            'active' => true,
            'slug' => 'cov-school-accident-' . $branchId . '-2-facultativa',
        ]);
        $coverages['facultativa'] = $coverageFacultativa;
        $this->stats['coverages']++;

        // Subcoberturas (vinculadas à cobertura obrigatória)
        $subcoveragesObrigatoria = [
            [
                'seq' => 3,
                'gis_cod' => 3,
                'short_name' => 'INVALIDEZ PERM',
                'name' => 'INVALIDEZ PERMANENTE',
                'description' => 'Garantia de Invalidez Permanente',
                'capital_value' => null,
            ],
            [
                'seq' => 4,
                'gis_cod' => 4,
                'short_name' => 'DESP TRAT REPAT',
                'name' => 'DESPESAS DE TRATAMENTO E REPATRIAMENTO',
                'description' => 'Despesas de Tratamento e Repatriamento',
                'capital_value' => null,
            ],
            [
                'seq' => 5,
                'gis_cod' => 5,
                'short_name' => 'DESP FUNERAL',
                'name' => 'DESPESAS DE FUNERAL',
                'description' => 'Despesas de Funeral',
                'capital_value' => null,
            ],
        ];

        foreach ($subcoveragesObrigatoria as $row) {
            $sub = CFG_coverages::create([
                'insurance_branch_id' => $branchId,
                'group_id' => $coverageObrigatoria->id,
                'seq' => $row['seq'],
                'gis_cod' => $row['gis_cod'],
                'type_cov' => '2',
                'short_name' => $row['short_name'],
                'name' => $row['name'],
                'description' => $row['description'],
                'obj_type' => 'PERSON',
                'capital_value' => $row['capital_value'],
                'capital_origin' => null,
                'rate_type' => 'FIXED',
                'risk_rate' => null,
                'risk_rate_origin' => null,
                'cov_opt' => 'O',
                'type_covt' => 'O',
                'cov_perc' => 0,
                'active' => true,
                'slug' => 'cov-school-accident-' . $branchId . '-' . $row['gis_cod'] . '-' . Str::slug($row['short_name']),
            ]);
            $coverages['sub_obrigatoria_' . $row['gis_cod']] = $sub;
            $this->stats['coverages']++;
        }

        // Subcoberturas (vinculadas à cobertura facultativa)
        $subcoveragesFacultativa = [
            [
                'seq' => 6,
                'gis_cod' => 6,
                'short_name' => 'RC ALUNOS',
                'name' => 'RESPONSABILIDADE CIVIL DOS ALUNOS',
                'description' => 'Responsabilidade Civil dos Alunos',
                'capital_value' => null,
            ],
            [
                'seq' => 7,
                'gis_cod' => 7,
                'short_name' => 'RC ESTABELEC',
                'name' => 'RESPONSABILIDADE CIVIL DO ESTABELECIMENTO',
                'description' => 'Responsabilidade Civil do Estabelecimento',
                'capital_value' => null,
            ],
            [
                'seq' => 8,
                'gis_cod' => 8,
                'short_name' => 'RC CRUZ ALUNOS',
                'name' => 'RC CRUZADA DOS ALUNOS ENTRE SI',
                'description' => 'Responsabilidade Civil Cruzada dos Alunos entre si',
                'capital_value' => null,
            ],
            [
                'seq' => 9,
                'gis_cod' => 9,
                'short_name' => 'RC CRUZ ALU EST',
                'name' => 'RC CRUZADA DOS ALUNOS E DO ESTABELECIMENTO ENTRE SI',
                'description' => 'Responsabilidade Civil Cruzada dos Alunos e do Estabelecimento entre si',
                'capital_value' => null,
            ],
        ];

        foreach ($subcoveragesFacultativa as $row) {
            $sub = CFG_coverages::create([
                'insurance_branch_id' => $branchId,
                'group_id' => $coverageFacultativa->id,
                'seq' => $row['seq'],
                'gis_cod' => $row['gis_cod'],
                'type_cov' => '2',
                'short_name' => $row['short_name'],
                'name' => $row['name'],
                'description' => $row['description'],
                'obj_type' => 'PERSON',
                'capital_value' => $row['capital_value'],
                'capital_origin' => null,
                'rate_type' => 'FIXED',
                'risk_rate' => null,
                'risk_rate_origin' => null,
                'cov_opt' => null,
                'type_covt' => null,
                'cov_perc' => 0,
                'active' => true,
                'slug' => 'cov-school-accident-' . $branchId . '-' . $row['gis_cod'] . '-' . Str::slug($row['short_name']),
            ]);
            $coverages['sub_facultativa_' . $row['gis_cod']] = $sub;
            $this->stats['coverages']++;
        }

        return $coverages;
    }

    private function seedTemplateCoverage(array $templates, array $coverages): void
    {
        foreach ($templates as $template) {
            $displayOrder = 1;

            // Adicionar cobertura obrigatória e suas subcoberturas
            $coverageObrigatoria = $coverages['obrigatoria'];
            CFG_template_coverage::create([
                'template_id' => $template->id,
                'coverage_id' => $coverageObrigatoria->id,
                'is_mandatory' => true,
                'display_order' => $displayOrder++,
            ]);
            $this->stats['template_coverages']++;

            // Adicionar subcoberturas da obrigatória
            foreach ($coverages as $key => $coverage) {
                if (str_starts_with($key, 'sub_obrigatoria_')) {
                    CFG_template_coverage::create([
                        'template_id' => $template->id,
                        'coverage_id' => $coverage->id,
                        'is_mandatory' => true,
                        'display_order' => $displayOrder++,
                    ]);
                    $this->stats['template_coverages']++;
                }
            }

            // Adicionar cobertura facultativa e suas subcoberturas
            $coverageFacultativa = $coverages['facultativa'];
            CFG_template_coverage::create([
                'template_id' => $template->id,
                'coverage_id' => $coverageFacultativa->id,
                'is_mandatory' => false,
                'display_order' => $displayOrder++,
            ]);
            $this->stats['template_coverages']++;

            // Adicionar subcoberturas da facultativa
            foreach ($coverages as $key => $coverage) {
                if (str_starts_with($key, 'sub_facultativa_')) {
                    CFG_template_coverage::create([
                        'template_id' => $template->id,
                        'coverage_id' => $coverage->id,
                        'is_mandatory' => false,
                        'display_order' => $displayOrder++,
                    ]);
                    $this->stats['template_coverages']++;
                }
            }
        }
    }

    private function seedDeductibles(array $coverages): void
    {
        $deductiblesData = [
            'sub_obrigatoria_4' => [
                'deduct_type' => 'Fixed',
                'deduct_object' => 'CLAIM',
                'deduct_percent' => null,
                'deduct_value' => 7500,
                'deduct_min' => null,
                'deduct_max' => null,
                'discount' => null,
                'details' => 'Franquia para Despesas de Tratamento e Repatriamento',
            ],
            'sub_facultativa_6' => [
                'deduct_type' => 'Fixed',
                'deduct_object' => 'CLAIM',
                'deduct_percent' => null,
                'deduct_value' => 7500,
                'deduct_min' => null,
                'deduct_max' => null,
                'discount' => null,
                'details' => 'Franquia para Responsabilidade Civil dos Alunos',
            ],
            'sub_facultativa_7' => [
                'deduct_type' => 'Fixed',
                'deduct_object' => 'CLAIM',
                'deduct_percent' => null,
                'deduct_value' => 10000,
                'deduct_min' => null,
                'deduct_max' => null,
                'discount' => null,
                'details' => 'Franquia para Responsabilidade Civil do Estabelecimento',
            ],
            'sub_facultativa_8' => [
                'deduct_type' => 'Fixed',
                'deduct_object' => 'CLAIM',
                'deduct_percent' => null,
                'deduct_value' => 7500,
                'deduct_min' => null,
                'deduct_max' => null,
                'discount' => null,
                'details' => 'Franquia para RC Cruzada dos Alunos entre si',
            ],
            'sub_facultativa_9' => [
                'deduct_type' => 'Fixed',
                'deduct_object' => 'CLAIM',
                'deduct_percent' => null,
                'deduct_value' => 10000,
                'deduct_min' => null,
                'deduct_max' => null,
                'discount' => null,
                'details' => 'Franquia para RC Cruzada dos Alunos e do Estabelecimento entre si',
            ],
        ];

        foreach ($deductiblesData as $coverageKey => $data) {
            if (isset($coverages[$coverageKey])) {
                CFG_deductibles::create([
                    'coverage_id' => $coverages[$coverageKey]->id,
                    'deduct_type' => $data['deduct_type'],
                    'deduct_object' => $data['deduct_object'],
                    'deduct_percent' => $data['deduct_percent'],
                    'deduct_value' => $data['deduct_value'],
                    'deduct_min' => $data['deduct_min'],
                    'deduct_max' => $data['deduct_max'],
                    'discount' => $data['discount'],
                    'details' => $data['details'],
                    'slug' => 'deduct-school-accident-' . $coverages[$coverageKey]->id . '-' . Str::slug($data['details']),
                ]);
                $this->stats['deductibles']++;
            }
        }
    }

    private function seedRates(int $branchId, array $coverages): void
    {
        $ratesData = [
        // Opção A
        [
            'coverage_key' => 'obrigatoria',
            'type' => 'Obrigatorio',
            'package' => 'Option A',
            'premium_value' => 1963.00,
        ],
        [
            'coverage_key' => 'facultativa',
            'type' => 'Facultativa',
            'package' => 'Option A',
            'premium_value' => 2257.00,
        ],
        // Opção B
        [
            'coverage_key' => 'obrigatoria',
            'type' => 'Obrigatorio',
            'package' => 'Option B',
            'premium_value' => 3741.00,
        ],
        [
            'coverage_key' => 'facultativa',
            'type' => 'Facultativa',
            'package' => 'Option B',
            'premium_value' => 2257.00,
        ],
        // Opção C
        [
            'coverage_key' => 'obrigatoria',
            'type' => 'Obrigatorio',
            'package' => 'Option C',
            'premium_value' => 5551.00,
        ],
        [
            'coverage_key' => 'facultativa',
            'type' => 'Facultativa',
            'package' => 'Option C',
            'premium_value' => 3160.00,
        ],
    ];
        foreach ($ratesData as $rate) {
            $coverage = $coverages[$rate['coverage_key']] ?? null;

            if (!$coverage) {
                $this->warn("⚠️  Cobertura não encontrada para: {$rate['coverage_key']}");
                continue;
            }

            $slug = 'rate-school-accident-' . $branchId . '-' . $coverage->id . '-' . Str::slug($rate['package']) . '-' . Str::slug($rate['type']);

            Cfg_RatesSchoolAccident::updateOrCreate(
                ['slug' => $slug],
                [
                    'insurance_branch_id' => $branchId,
                    'coverage_id' => $coverage->id,
                    'type' => $rate['type'],
                    'package' => $rate['package'],
                    'premium_value' => $rate['premium_value'],
                    'active' => true,
                ]
            );

            $this->stats['rates']++;
        }
    }

    private function showSummary(string $branchCode): void
    {
        $this->newLine();
        $this->info('✅ ACIDENTES PESSOAIS ESCOLAR pronto para uso.');
        $this->line('----------------------------------------');
        $this->line('📊 Resumo da configuração:');
        $this->line("   - Ramo: {$branchCode} (ativo)");
        $this->line("   - Templates: {$this->stats['templates']}");
        $this->line("   - Coberturas: {$this->stats['coverages']}");
        $this->line("   - Vínculos template-cobertura: {$this->stats['template_coverages']}");
        $this->line("   - Franquias: {$this->stats['deductibles']}");
        $this->line("   - Tarifas: {$this->stats['rates']}");
        $this->line('----------------------------------------');
        $this->line('📋 Detalhes:');
        $this->line('   - 3 templates (Opção A, B, C)');
        $this->line('   - 2 coberturas principais (Obrigatória e Facultativa)');
        $this->line('   - 5 subcoberturas vinculadas');
        $this->line('   - 5 franquias configuradas');
        $this->line('   - 6 tarifas de APE inseridas');
    }
}