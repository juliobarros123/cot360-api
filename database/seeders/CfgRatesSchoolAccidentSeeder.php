<?php

namespace Database\Seeders;

use App\Models\CFG\CFG_insurance_branches;
use Illuminate\Database\Seeder;
use App\Models\CFG\CfgRatesSchoolAccident;
use App\Models\CFG\CFG_coverages;

class CfgRatesSchoolAccidentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buscar o ramo de seguro (assumindo que já existe)
        $insuranceBranch = CFG_insurance_branches::where('slug', 'school-accident')->first();
        
        if (!$insuranceBranch) {
            $this->command->error('Insurance branch not found. Please create "school-accident" branch first.');
            return;
        }

        // Buscar ou criar as coberturas
        $coverageObrigatoria = CFG_coverages::firstOrCreate(
            ['name' => 'Coberturas Obrigatório'],
            [
                'slug' => 'coberturas-obrigatorio',
                'active' => true,
            ]
        );

        $coverageFacultativa = CFG_coverages::firstOrCreate(
            ['name' => 'Coberturas Facultativa'],
            [
                'slug' => 'coberturas-facultativa',
                'active' => true,
            ]
        );

        // Dados das tarifas
        $rates = [
            // Opção A
            [
                'insurance_branch_id' => $insuranceBranch->id,
                'coverage_id' => $coverageObrigatoria->id,
                'type' => 'Obrigatorio',
                'package' => 'Option A',
                'premium_value' => 1963.00,
                'slug' => 'school-accident-obrigatorio-option-a',
            ],
            [
                'insurance_branch_id' => $insuranceBranch->id,
                'coverage_id' => $coverageFacultativa->id,
                'type' => 'Facultativa',
                'package' => 'Option A',
                'premium_value' => 2257.00,
                'slug' => 'school-accident-facultativa-option-a',
            ],
            // Opção B
            [
                'insurance_branch_id' => $insuranceBranch->id,
                'coverage_id' => $coverageObrigatoria->id,
                'type' => 'Obrigatorio',
                'package' => 'Option B',
                'premium_value' => 3741.00,
                'slug' => 'school-accident-obrigatorio-option-b',
            ],
            [
                'insurance_branch_id' => $insuranceBranch->id,
                'coverage_id' => $coverageFacultativa->id,
                'type' => 'Facultativa',
                'package' => 'Option B',
                'premium_value' => 2257.00,
                'slug' => 'school-accident-facultativa-option-b',
            ],
            // Opção C
            [
                'insurance_branch_id' => $insuranceBranch->id,
                'coverage_id' => $coverageObrigatoria->id,
                'type' => 'Obrigatorio',
                'package' => 'Option C',
                'premium_value' => 5551.00,
                'slug' => 'school-accident-obrigatorio-option-c',
            ],
            [
                'insurance_branch_id' => $insuranceBranch->id,
                'coverage_id' => $coverageFacultativa->id,
                'type' => 'Facultativa',
                'package' => 'Option C',
                'premium_value' => 3160.00,
                'slug' => 'school-accident-facultativa-option-c',
            ],
        ];

        // Inserir as tarifas
        foreach ($rates as $rate) {
            CfgRatesSchoolAccident::updateOrCreate(
                ['slug' => $rate['slug']],
                $rate
            );
        }

        $this->command->info('School Accident rates seeded successfully!');
    }
}