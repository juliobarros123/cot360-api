<?php

namespace Database\Seeders;

use App\Models\CFG\CFG_coverages;
use App\Models\CFG\CFG_insurance_branches;
use App\Models\CFG\CFG_templates;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MultirriscosEmpresaGroupCoverageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Seeder para as coberturas do Seguro Multirriscos Empresa
     * INSURANCE_BRANCH_ID fixo: 34
     * Estrutura com dois grupos base (pai):
     * 1. Multirriscos Empresa Conteúdo (GIS_COD: 6000)
     * 2. Multirriscos Empresa Edifício (GIS_COD: 6050)
     */
    public function run(): void
    {
        // Buscar o ramo de seguro com código 34
        $insuranceBranch = CFG_insurance_branches::where('code', 34)->first();

        if (!$insuranceBranch) {
            $this->command->error('Ramo de seguro com código 34 não encontrado!');
            return;
        }

        $template = CFG_templates::where('insurance_branch_id', $insuranceBranch->id)
            ->where('code', 1)
            ->first();

        $parentCoverages = [];
        $updatedParents = [];
        
        // Array com as duas coberturas PAI (GRUPO BASE)
        $parentCoverageDefinitions = [
            [
                'gis_cod' => 6000,
                'name' => 'MULTIRRISCOS EMPRESA CONTEÚDO',
                'short_name' => 'MULTIRISCOS CONTEÚDO',
                'obj_type' => 'BUILDING_CONTENT',
                'type_covt' => 'O',
                'description' => 'GRUPOBASE CONTEÚDO',
            ],
            [
                'gis_cod' => 6050,
                'name' => 'MULTIRRISCOS EMPRESA EDIFÍCIO',
                'short_name' => 'MULTIRISCOS EDIFÍCIO',
                'obj_type' => 'MRE_RC',
                'type_covt' => 'O',
                'description' => 'GRUPOBASE EDIFÍCIO',
            ],
        ];

        // Criar ou ATUALIZAR as coberturas PAI
        foreach ($parentCoverageDefinitions as $parentDef) {
            $parentCoverage = CFG_coverages::where('gis_cod', $parentDef['gis_cod'])
                ->where('insurance_branch_id', $insuranceBranch->id)
                ->first();

            $coverageData = [
                'group_id' => null,
                'insurance_branch_id' => $insuranceBranch->id,
                'seq' => $parentDef['gis_cod'] == 6000 ? 1 : 2,
                'gis_cod' => $parentDef['gis_cod'],
                'type_cov' => 1,
                'short_name' => $parentDef['short_name'],
                'name' => $parentDef['name'],
                'description' => $parentDef['description'],
                'cov_perc' => 0.0,
                'rate_type' => 'PERCENTAGE',
                'obj_type' => $parentDef['obj_type'],
                'capital_value' => null,
                'cov_opt' => null,
                'type_covt' => $parentDef['type_covt'],
                'risk_rate' => null,
                'risk_rate_origin' => null,
                'active' => true,
            ];

            if ($parentCoverage) {
                // Atualizar cobertura existente
                $parentCoverage->update($coverageData);
                if ($template) {
                    $template->coverages()->syncWithoutDetaching([
                        $parentCoverage->id => [
                            'is_mandatory' => 1,
                            'display_order' => $coverageData['seq'] ?? 1,
                        ],
                    ]);
                }
                $this->command->info("✓ Grupo base '{$parentDef['name']}' atualizado com ID: {$parentCoverage->id}");
                $parentCoverages[$parentDef['gis_cod']] = $parentCoverage->id;
                $updatedParents[$parentDef['gis_cod']] = 'ATUALIZADO';
            } else {
                // Criar nova cobertura PAI
                $parentCoverage = CFG_coverages::create($coverageData);
                $parentCoverages[$parentDef['gis_cod']] = $parentCoverage->id;
                $updatedParents[$parentDef['gis_cod']] = 'CRIADO';
                if ($template) {
                    $template->coverages()->syncWithoutDetaching([
                        $parentCoverage->id => [
                            'is_mandatory' => 1,
                            'display_order' => $coverageData['seq'] ?? 1,
                        ],
                    ]);
                }
                $this->command->info("✓ Grupo base '{$parentDef['name']}' criado com ID: {$parentCoverage->id}");
            }
        }

        // Array de coberturas FILHAS (todas terão GROUP_ID = $parentId da cobertura de conteúdo - GIS_COD: 6000)
        // A cobertura de edifício (GIS_COD: 6050) não terá subcoberturas
        $coverages = [
            // Cobertura 1: Incêndio, Queda de Raio e Explosão
            [
                'GROUP_ID' => $parentCoverages[6000],
                'INSURANCE_BRANCH_ID' => $insuranceBranch->id,
                'TEMPLATE_CODE' => 1,
                'SEQ' => 1,
                'GIS_CODE' => 6001,
                'TYPE_COV' => 1,
                'type_covt' => 'O',
                'SHORT_NAME' => 'INCÊNDIO',
                'NAME' => 'Incêndio, Queda de Raio e Explosão',
                'DESCRIPTION' => 'Cobertura contra incêndio, queda de raio e explosão',
                'COV_PERC' => 0.0,
                'RATE_TYPE' => 'PERCENTAGE',
                'OBJ_TYPE' => 'BUILDING_CONTENT',
                'risk_rate_origin' => null,
                'ACTIVE' => true,
            ],

            // Cobertura 2: Tempestades
            [
                'GROUP_ID' => $parentCoverages[6000],
                'INSURANCE_BRANCH_ID' => $insuranceBranch->id,
                'TEMPLATE_CODE' => 1,
                'SEQ' => 2,
                'GIS_CODE' => 6002,
                'TYPE_COV' => 1,
                'type_covt' => 'O',
                'SHORT_NAME' => 'TEMPESTADES',
                'NAME' => 'Tempestades',
                'DESCRIPTION' => 'Cobertura contra danos causados por tempestades',
                'COV_PERC' => 0.0,
                'RATE_TYPE' => 'PERCENTAGE',
                'OBJ_TYPE' => 'BUILDING_CONTENT',
                'risk_rate_origin' => null,
                'ACTIVE' => true,
            ],

            // Cobertura 3: Inundações
            [
                'GROUP_ID' => $parentCoverages[6000],
                'INSURANCE_BRANCH_ID' => $insuranceBranch->id,
                'TEMPLATE_CODE' => 1,
                'SEQ' => 3,
                'GIS_CODE' => 6003,
                'TYPE_COV' => 1,
                'type_covt' => 'O',
                'SHORT_NAME' => 'INUNDAÇÕES',
                'NAME' => 'Inundações',
                'DESCRIPTION' => 'Cobertura contra danos causados por inundações',
                'COV_PERC' => 0.0,
                'RATE_TYPE' => 'PERCENTAGE',
                'OBJ_TYPE' => 'BUILDING_CONTENT',
                'risk_rate_origin' => null,
                'ACTIVE' => true,
            ],

            // Cobertura 4: Demolição e Remoção de Escombros
            [
                'GROUP_ID' => $parentCoverages[6000],
                'INSURANCE_BRANCH_ID' => $insuranceBranch->id,
                'TEMPLATE_CODE' => 1,
                'SEQ' => 4,
                'GIS_CODE' => 6004,
                'TYPE_COV' => 1,
                'type_covt' => 'O',
                'SHORT_NAME' => 'DEMOLIÇÃO',
                'NAME' => 'Demolição e Remoção de Escombros',
                'DESCRIPTION' => 'Cobertura de despesas com demolição e remoção de escombros - 5% do capital seguro',
                'COV_PERC' => 0.05,
                'RATE_TYPE' => 'PERCENTAGE',
                'OBJ_TYPE' => 'BUILDING_CONTENT',
                'risk_rate_origin' => null,
                'ACTIVE' => true,
            ],

            // Cobertura 5: Queda de Aeronaves
            [
                'GROUP_ID' => $parentCoverages[6000],
                'INSURANCE_BRANCH_ID' => $insuranceBranch->id,
                'TEMPLATE_CODE' => 1,
                'SEQ' => 5,
                'GIS_CODE' => 6005,
                'TYPE_COV' => 1,
                'type_covt' => 'O',
                'SHORT_NAME' => 'AERONAVES',
                'NAME' => 'Queda de Aeronaves',
                'DESCRIPTION' => 'Cobertura contra danos causados por queda de aeronaves',
                'COV_PERC' => 0.0,
                'RATE_TYPE' => 'PERCENTAGE',
                'OBJ_TYPE' => 'BUILDING_CONTENT',
                'risk_rate_origin' => null,
                'ACTIVE' => true,
            ],

            // Cobertura 6: Danos por Água (CE 07)
            [
                'GROUP_ID' => $parentCoverages[6000],
                'INSURANCE_BRANCH_ID' => $insuranceBranch->id,
                'TEMPLATE_CODE' => 1,
                'SEQ' => 6,
                'GIS_CODE' => 6006,
                'TYPE_COV' => 1,
                'type_covt' => 'O',
                'SHORT_NAME' => 'DANOS ÁGUA',
                'NAME' => 'Danos por Água',
                'DESCRIPTION' => 'CE 07 - Danos por água',
                'COV_PERC' => 0.0,
                'RATE_TYPE' => 'PERCENTAGE',
                'OBJ_TYPE' => 'BUILDING_CONTENT',
                'risk_rate_origin' => null,
                'ACTIVE' => true,
            ],

            // Cobertura 7: Aluimentos de terras
            [
                'GROUP_ID' => $parentCoverages[6000],
                'INSURANCE_BRANCH_ID' => $insuranceBranch->id,
                'TEMPLATE_CODE' => 1,
                'SEQ' => 7,
                'GIS_CODE' => 6007,
                'TYPE_COV' => 1,
                'type_covt' => 'O',
                'SHORT_NAME' => 'ALUIMENTOS',
                'NAME' => 'Aluimentos de terras',
                'DESCRIPTION' => 'Cobertura contra danos causados por aluimentos de terras',
                'COV_PERC' => 0.0,
                'RATE_TYPE' => 'PERCENTAGE',
                'OBJ_TYPE' => 'BUILDING_CONTENT',
                'risk_rate_origin' => null,
                'ACTIVE' => true,
            ],

            // Cobertura 8: Derrame de sistemas de combate a incêndio
            [
                'GROUP_ID' => $parentCoverages[6000],
                'INSURANCE_BRANCH_ID' => $insuranceBranch->id,
                'TEMPLATE_CODE' => 1,
                'SEQ' => 8,
                'GIS_CODE' => 6008,
                'TYPE_COV' => 1,
                'type_covt' => 'O',
                'SHORT_NAME' => 'DERRAME COMB.',
                'NAME' => 'Derrame de sistemas de combate a incêndio',
                'DESCRIPTION' => 'Cobertura contra derrame de sistemas de combate a incêndio',
                'COV_PERC' => 0.0,
                'RATE_TYPE' => 'PERCENTAGE',
                'OBJ_TYPE' => 'BUILDING_CONTENT',
                'risk_rate_origin' => null,
                'ACTIVE' => true,
            ],

            // Cobertura 9: Quebra de vidros
            [
                'GROUP_ID' => $parentCoverages[6000],
                'INSURANCE_BRANCH_ID' => $insuranceBranch->id,
                'TEMPLATE_CODE' => 1,
                'SEQ' => 9,
                'GIS_CODE' => 6009,
                'TYPE_COV' => 2,
                'type_covt' => 'F',
                'SHORT_NAME' => 'QUEBRA VIDROS',
                'NAME' => 'Quebra de vidros',
                'DESCRIPTION' => 'Cobertura de quebra de vidros - Limite: 30.000 USD por sinistro e anuidade',
                'COV_PERC' => 0.0,
                'RATE_TYPE' => 'FIXED',
                'OBJ_TYPE' => 'BUILDING_CONTENT',
                'capital_value' => 30000,
                'risk_rate_origin' => null,
                'ACTIVE' => true,
            ],

            // Cobertura 10: Choque ou impacto de veículos terrestres
            [
                'GROUP_ID' => $parentCoverages[6000],
                'INSURANCE_BRANCH_ID' => $insuranceBranch->id,
                'TEMPLATE_CODE' => 1,
                'SEQ' => 10,
                'GIS_CODE' => 6010,
                'TYPE_COV' => 1,
                'type_covt' => 'O',
                'SHORT_NAME' => 'CHOQUE VEÍC.',
                'NAME' => 'Choque ou impacto de veículos terrestres ou objectos sólidos',
                'DESCRIPTION' => 'Cobertura contra choque ou impacto de veículos terrestres ou objectos sólidos',
                'COV_PERC' => 0.0,
                'RATE_TYPE' => 'PERCENTAGE',
                'OBJ_TYPE' => 'BUILDING_CONTENT',
                'risk_rate_origin' => null,
                'ACTIVE' => true,
            ],

            // Cobertura 11: Furto ou Roubo
            [
                'GROUP_ID' => $parentCoverages[6000],
                'INSURANCE_BRANCH_ID' => $insuranceBranch->id,
                'TEMPLATE_CODE' => 1,
                'SEQ' => 11,
                'GIS_CODE' => 6011,
                'TYPE_COV' => 1,
                'type_covt' => 'O',
                'SHORT_NAME' => 'FURTO/ROUBO',
                'NAME' => 'Furto ou Roubo',
                'DESCRIPTION' => 'Cobertura de furto ou roubo - Capital Seguro para conteúdo',
                'COV_PERC' => 0.0,
                'RATE_TYPE' => 'PERCENTAGE',
                'OBJ_TYPE' => 'CONTENT',
                'risk_rate_origin' => null,
                'ACTIVE' => true,
            ],

            // Cobertura 12: Quebra ou queda de antenas exteriores
            [
                'GROUP_ID' => $parentCoverages[6000],
                'INSURANCE_BRANCH_ID' => $insuranceBranch->id,
                'TEMPLATE_CODE' => 1,
                'SEQ' => 12,
                'GIS_CODE' => 6012,
                'TYPE_COV' => 2,
                'type_covt' => 'F',
                'SHORT_NAME' => 'ANTENAS',
                'NAME' => 'Quebra ou queda de antenas exteriores',
                'DESCRIPTION' => 'Cobertura de quebra ou queda de antenas exteriores - Limite: 30.000 USD por sinistro e anuidade',
                'COV_PERC' => 0.0,
                'RATE_TYPE' => 'FIXED',
                'OBJ_TYPE' => 'BUILDING_CONTENT',
                'capital_value' => 30000,
                'risk_rate_origin' => null,
                'ACTIVE' => true,
            ],

            // Cobertura 13: Quebra ou queda de painéis solares
            [
                'GROUP_ID' => $parentCoverages[6000],
                'INSURANCE_BRANCH_ID' => $insuranceBranch->id,
                'TEMPLATE_CODE' => 1,
                'SEQ' => 13,
                'GIS_CODE' => 6013,
                'TYPE_COV' => 2,
                'type_covt' => 'F',
                'SHORT_NAME' => 'PAINÉIS SOL.',
                'NAME' => 'Quebra ou queda de painéis solares',
                'DESCRIPTION' => 'Cobertura de quebra ou queda de painéis solares - Limite: 30.000 USD por sinistro e anuidade',
                'COV_PERC' => 0.0,
                'RATE_TYPE' => 'FIXED',
                'OBJ_TYPE' => 'BUILDING_CONTENT',
                'capital_value' => 30000,
                'risk_rate_origin' => null,
                'ACTIVE' => true,
            ],

            // Cobertura 14: RC Proprietário/inquilino/ocupante
            [
                'GROUP_ID' => $parentCoverages[6000],
                'INSURANCE_BRANCH_ID' => $insuranceBranch->id,
                'TEMPLATE_CODE' => 1,
                'SEQ' => 14,
                'GIS_CODE' => 6014,
                'TYPE_COV' => 2,
                'type_covt' => 'F',
                'SHORT_NAME' => 'RC PROP/INQ',
                'NAME' => 'RC Proprietário/inquilino/ocupante',
                'DESCRIPTION' => 'Responsabilidade Civil Proprietário/inquilino/ocupante - Limite: 100.000 USD por sinistro e anuidade',
                'COV_PERC' => 0.0,
                'RATE_TYPE' => 'FIXED',
                'OBJ_TYPE' => 'BUILDING_CONTENT',
                'capital_value' => 100000,
                'risk_rate_origin' => null,
                'ACTIVE' => true,
            ],

            // Cobertura 15: Danos ao edifício por furto ou roubo
            [
                'GROUP_ID' => $parentCoverages[6000],
                'INSURANCE_BRANCH_ID' => $insuranceBranch->id,
                'TEMPLATE_CODE' => 1,
                'SEQ' => 15,
                'GIS_CODE' => 6015,
                'TYPE_COV' => 2,
                'type_covt' => 'F',
                'SHORT_NAME' => 'DANOS EDIFÍCIO',
                'NAME' => 'Danos ao edifício por furto ou roubo',
                'DESCRIPTION' => 'Cobertura de danos ao edifício por furto ou roubo - 10% do capital seguro para edifício',
                'COV_PERC' => 0.10,
                'RATE_TYPE' => 'PERCENTAGE',
                'OBJ_TYPE' => 'BUILDING',
                'risk_rate_origin' => null,
                'ACTIVE' => true,
            ],

            // Cobertura 16: Derrame acidental de óleo (CE 09)
            [
                'GROUP_ID' => $parentCoverages[6000],
                'INSURANCE_BRANCH_ID' => $insuranceBranch->id,
                'TEMPLATE_CODE' => 1,
                'SEQ' => 16,
                'GIS_CODE' => 6016,
                'TYPE_COV' => 1,
                'type_covt' => 'O',
                'SHORT_NAME' => 'DERRAME ÓLEO',
                'NAME' => 'Derrame acidental de óleo',
                'DESCRIPTION' => 'CE 09 - Derrame acidental de óleo',
                'COV_PERC' => 0.0,
                'RATE_TYPE' => 'PERCENTAGE',
                'OBJ_TYPE' => 'BUILDING_CONTENT',
                'risk_rate_origin' => null,
                'ACTIVE' => true,
            ],

            // Cobertura 17: Riscos eléctricos (CE 26)
            [
                'GROUP_ID' => $parentCoverages[6000],
                'INSURANCE_BRANCH_ID' => $insuranceBranch->id,
                'TEMPLATE_CODE' => 1,
                'SEQ' => 17,
                'GIS_CODE' => 6017,
                'TYPE_COV' => 2,
                'type_covt' => 'F',
                'SHORT_NAME' => 'RISCOS ELÉTR.',
                'NAME' => 'Riscos eléctricos',
                'DESCRIPTION' => 'CE 26 - Riscos eléctricos - Limite: 100.000 USD por sinistro e anuidade',
                'COV_PERC' => 0.0,
                'RATE_TYPE' => 'FIXED',
                'OBJ_TYPE' => 'BUILDING_CONTENT',
                'capital_value' => 100000,
                'risk_rate_origin' => null,
                'ACTIVE' => true,
            ],

            // Cobertura 18: Deterioração de bens refrigerados (CE 11)
            [
                'GROUP_ID' => $parentCoverages[6000],
                'INSURANCE_BRANCH_ID' => $insuranceBranch->id,
                'TEMPLATE_CODE' => 1,
                'SEQ' => 18,
                'GIS_CODE' => 6018,
                'TYPE_COV' => 2,
                'type_covt' => 'F',
                'SHORT_NAME' => 'BENS REFRIG.',
                'NAME' => 'Deterioração de bens refrigerados',
                'DESCRIPTION' => 'CE 11 - Deterioração de bens refrigerados - Limite: 500.000 USD por sinistro e anuidade',
                'COV_PERC' => 0.0,
                'RATE_TYPE' => 'FIXED',
                'OBJ_TYPE' => 'CONTENT',
                'capital_value' => 500000,
                'risk_rate_origin' => null,
                'ACTIVE' => true,
            ],

            // Cobertura 19: Infidelidade de funcionários (CE 19)
            [
                'GROUP_ID' => $parentCoverages[6000],
                'INSURANCE_BRANCH_ID' => $insuranceBranch->id,
                'TEMPLATE_CODE' => 1,
                'SEQ' => 19,
                'GIS_CODE' => 6019,
                'TYPE_COV' => 2,
                'type_covt' => 'F',
                'SHORT_NAME' => 'INFIDELIDADE',
                'NAME' => 'Infidelidade de funcionários',
                'DESCRIPTION' => 'CE 19 - Infidelidade de funcionários - Limite: 25.000 USD por sinistro e anuidade',
                'COV_PERC' => 0.0,
                'RATE_TYPE' => 'FIXED',
                'OBJ_TYPE' => 'CONTENT',
                'capital_value' => 25000,
                'risk_rate_origin' => null,
                'ACTIVE' => true,
            ],

            // Cobertura 20: Dinheiro em cofre (CE 13)
            [
                'GROUP_ID' => $parentCoverages[6000],
                'INSURANCE_BRANCH_ID' => $insuranceBranch->id,
                'TEMPLATE_CODE' => 1,
                'SEQ' => 20,
                'GIS_CODE' => 6020,
                'TYPE_COV' => 2,
                'type_covt' => 'F',
                'SHORT_NAME' => 'DINHEIRO COFRE',
                'NAME' => 'Dinheiro em cofre',
                'DESCRIPTION' => 'CE 13 - Dinheiro em cofre - Limite: 12.000 USD por sinistro e anuidade',
                'COV_PERC' => 0.0,
                'RATE_TYPE' => 'FIXED',
                'OBJ_TYPE' => 'CONTENT',
                'capital_value' => 12000,
                'risk_rate_origin' => null,
                'ACTIVE' => true,
            ],
        ];

        $createdCount = 0;
        $updatedCount = 0;

        foreach ($coverages as $coverage) {
            // Verificar se a cobertura já existe pelo GIS_CODE e INSURANCE_BRANCH_ID
            $existingCoverage = CFG_coverages::where('gis_cod', $coverage['GIS_CODE'])
                ->where('insurance_branch_id', $insuranceBranch->id)
                ->first();

            $coverageData = [
                'group_id' => $coverage['GROUP_ID'],
                'insurance_branch_id' => $coverage['INSURANCE_BRANCH_ID'],
                'seq' => $coverage['SEQ'],
                'gis_cod' => $coverage['GIS_CODE'],
                'type_cov' => $coverage['TYPE_COV'],
                'short_name' => $coverage['SHORT_NAME'],
                'name' => $coverage['NAME'],
                'description' => $coverage['DESCRIPTION'],
                'cov_perc' => $coverage['COV_PERC'],
                'rate_type' => $coverage['RATE_TYPE'],
                'obj_type' => $coverage['OBJ_TYPE'],
                'capital_value' => $coverage['capital_value'] ?? null,
                'cov_opt' => null,
                'type_covt' => $coverage['type_covt'],
                'risk_rate' => null,
                'risk_rate_origin' => $coverage['risk_rate_origin'],
                'active' => $coverage['ACTIVE'],
            ];

            if ($existingCoverage) {
                // Atualizar cobertura existente
                $existingCoverage->update($coverageData);
                $this->command->info("  ✓ Cobertura '{$coverage['NAME']}' (GIS: {$coverage['GIS_CODE']}) atualizada!");
                $updatedCount++;
            } else {
                // Criar nova cobertura
                $existingCoverage = CFG_coverages::create($coverageData);
                $this->command->info("  ✓ Cobertura '{$coverage['NAME']}' (GIS: {$coverage['GIS_CODE']}) criada!");
                $createdCount++;
            }

            if ($template) {
                $template->coverages()->syncWithoutDetaching([
                    $existingCoverage->id => [
                        'is_mandatory' => ($coverage['type_covt'] ?? null) === 'O' ? 1 : 0,
                        'display_order' => $coverage['SEQ'] ?? 1,
                    ],
                ]);
            }
        }

        $this->command->info("\n========================================");
        $this->command->info("Resumo da importação:");
        $this->command->info("Grupos base:");
        foreach ($parentCoverageDefinitions as $parentDef) {
            $this->command->info("  - {$parentDef['name']} (GIS: {$parentDef['gis_cod']}): ID {$parentCoverages[$parentDef['gis_cod']]} [{$updatedParents[$parentDef['gis_cod']]}]");
        }
        $this->command->info("\nCoberturas filhas (apenas para Conteúdo):");
        $this->command->info("  Coberturas criadas: {$createdCount}");
        $this->command->info("  Coberturas atualizadas: {$updatedCount}");
        $this->command->info("  Total processado: " . ($createdCount + $updatedCount));
        $this->command->info("========================================\n");
    }
}
