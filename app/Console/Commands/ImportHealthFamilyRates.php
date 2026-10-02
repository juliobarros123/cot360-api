<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Importa as tarifas de Saúde Família Segura (ramo 14).
 *
 * Fonte: Tarira Saúde Familia Segura.xlsx
 * Tabela origem: aba "Tarifas Saúde Familiar 2023", secção "Prémio Total".
 *
 * Uso: php artisan rates:import-health-family
 */
class ImportHealthFamilyRates extends Command
{
    protected $signature = 'rates:import-health-family';

    protected $description = 'Importa as tarifas de Saúde Família Segura (ramo 14), fazendo fresh dos dados existentes do ramo.';

    private const INSURANCE_BRANCH_ID = 14;

    public function handle(): int
    {
        $rates = $this->getRates();
        $now   = Carbon::now();

        $payload = array_map(fn (array $r) => [
            'insurance_branch_id' => self::INSURANCE_BRANCH_ID,
            'coverage_id'         => null,
            'age_min'             => $r['age_min'],
            'age_max'             => $r['age_max'],
            'gender'              => $r['gender'],
            'type'                => $r['type'],
            'package'             => $r['package'],
            'premium_value'       => $r['premium_value'],
            'active'              => true,
            'slug'                => Str::slug(sprintf(
                'health-%d-%s-%s-%s-%d-%d',
                self::INSURANCE_BRANCH_ID,
                $r['package'],
                $r['type'],
                $r['gender'],
                $r['age_min'],
                $r['age_max']
            )),
            'created_at'          => $now,
            'updated_at'          => $now,
        ], $rates);

        DB::transaction(function () use ($payload) {
            $deleted = DB::table('cfg_rates_health')
                ->where('insurance_branch_id', self::INSURANCE_BRANCH_ID)
                ->delete();

            $this->warn("Removidas {$deleted} tarifas existentes do ramo " . self::INSURANCE_BRANCH_ID . '.');

            DB::table('cfg_rates_health')->insert($payload);
        });

        $this->info(sprintf('Inseridas %d tarifas para o ramo %d.', count($payload), self::INSURANCE_BRANCH_ID));

        return self::SUCCESS;
    }

    private function getRates(): array
    {
        return [
            ['age_min' => 0, 'age_max' => 5, 'gender' => 'Male', 'type' => 'Dependent', 'package' => 'BIC Plus', 'premium_value' => 409363],
            ['age_min' => 0, 'age_max' => 5, 'gender' => 'Female', 'type' => 'Dependent', 'package' => 'BIC Plus', 'premium_value' => 379040],
            ['age_min' => 0, 'age_max' => 5, 'gender' => 'Male', 'type' => 'Dependent', 'package' => 'BIC Top', 'premium_value' => 769500],
            ['age_min' => 0, 'age_max' => 5, 'gender' => 'Female', 'type' => 'Dependent', 'package' => 'BIC Top', 'premium_value' => 712500],
            ['age_min' => 0, 'age_max' => 5, 'gender' => 'Male', 'type' => 'Dependent', 'package' => 'Bic Mega', 'premium_value' => 1054457],
            ['age_min' => 0, 'age_max' => 5, 'gender' => 'Female', 'type' => 'Dependent', 'package' => 'Bic Mega', 'premium_value' => 976349],
            ['age_min' => 6, 'age_max' => 10, 'gender' => 'Male', 'type' => 'Dependent', 'package' => 'BIC Plus', 'premium_value' => 181939],
            ['age_min' => 6, 'age_max' => 10, 'gender' => 'Female', 'type' => 'Dependent', 'package' => 'BIC Plus', 'premium_value' => 151616],
            ['age_min' => 6, 'age_max' => 10, 'gender' => 'Male', 'type' => 'Dependent', 'package' => 'BIC Top', 'premium_value' => 342000],
            ['age_min' => 6, 'age_max' => 10, 'gender' => 'Female', 'type' => 'Dependent', 'package' => 'BIC Top', 'premium_value' => 285000],
            ['age_min' => 6, 'age_max' => 10, 'gender' => 'Male', 'type' => 'Dependent', 'package' => 'Bic Mega', 'premium_value' => 468648],
            ['age_min' => 6, 'age_max' => 10, 'gender' => 'Female', 'type' => 'Dependent', 'package' => 'Bic Mega', 'premium_value' => 390540],
            ['age_min' => 11, 'age_max' => 15, 'gender' => 'Male', 'type' => 'Dependent', 'package' => 'BIC Plus', 'premium_value' => 139487],
            ['age_min' => 11, 'age_max' => 15, 'gender' => 'Female', 'type' => 'Dependent', 'package' => 'BIC Plus', 'premium_value' => 145551],
            ['age_min' => 11, 'age_max' => 15, 'gender' => 'Male', 'type' => 'Dependent', 'package' => 'BIC Top', 'premium_value' => 262200],
            ['age_min' => 11, 'age_max' => 15, 'gender' => 'Female', 'type' => 'Dependent', 'package' => 'BIC Top', 'premium_value' => 273600],
            ['age_min' => 11, 'age_max' => 15, 'gender' => 'Male', 'type' => 'Dependent', 'package' => 'Bic Mega', 'premium_value' => 359297],
            ['age_min' => 11, 'age_max' => 15, 'gender' => 'Female', 'type' => 'Dependent', 'package' => 'Bic Mega', 'premium_value' => 374918],
            ['age_min' => 16, 'age_max' => 20, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Plus', 'premium_value' => 227094],
            ['age_min' => 16, 'age_max' => 20, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Plus', 'premium_value' => 334633],
            ['age_min' => 16, 'age_max' => 20, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Top', 'premium_value' => 356250],
            ['age_min' => 16, 'age_max' => 20, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Top', 'premium_value' => 570000],
            ['age_min' => 16, 'age_max' => 20, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'Bic Mega', 'premium_value' => 476896],
            ['age_min' => 16, 'age_max' => 20, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'Bic Mega', 'premium_value' => 806996],
            ['age_min' => 16, 'age_max' => 20, 'gender' => 'Male', 'type' => 'Dependent', 'package' => 'BIC Plus', 'premium_value' => 189520],
            ['age_min' => 16, 'age_max' => 20, 'gender' => 'Female', 'type' => 'Dependent', 'package' => 'BIC Plus', 'premium_value' => 197101],
            ['age_min' => 16, 'age_max' => 20, 'gender' => 'Male', 'type' => 'Dependent', 'package' => 'BIC Top', 'premium_value' => 356250],
            ['age_min' => 16, 'age_max' => 20, 'gender' => 'Female', 'type' => 'Dependent', 'package' => 'BIC Top', 'premium_value' => 370500],
            ['age_min' => 16, 'age_max' => 20, 'gender' => 'Male', 'type' => 'Dependent', 'package' => 'Bic Mega', 'premium_value' => 488175],
            ['age_min' => 16, 'age_max' => 20, 'gender' => 'Female', 'type' => 'Dependent', 'package' => 'Bic Mega', 'premium_value' => 507702],
            ['age_min' => 21, 'age_max' => 25, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Plus', 'premium_value' => 401503],
            ['age_min' => 21, 'age_max' => 25, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Plus', 'premium_value' => 836582],
            ['age_min' => 21, 'age_max' => 25, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Top', 'premium_value' => 629850],
            ['age_min' => 21, 'age_max' => 25, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Top', 'premium_value' => 1425000],
            ['age_min' => 21, 'age_max' => 25, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'Bic Mega', 'premium_value' => 843152],
            ['age_min' => 21, 'age_max' => 25, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'Bic Mega', 'premium_value' => 2017490],
            ['age_min' => 21, 'age_max' => 25, 'gender' => 'Male', 'type' => 'Dependent', 'package' => 'BIC Plus', 'premium_value' => 335071],
            ['age_min' => 21, 'age_max' => 25, 'gender' => 'Female', 'type' => 'Dependent', 'package' => 'BIC Plus', 'premium_value' => 530656],
            ['age_min' => 21, 'age_max' => 25, 'gender' => 'Male', 'type' => 'Dependent', 'package' => 'BIC Top', 'premium_value' => 629850],
            ['age_min' => 21, 'age_max' => 25, 'gender' => 'Female', 'type' => 'Dependent', 'package' => 'BIC Top', 'premium_value' => 997500],
            ['age_min' => 21, 'age_max' => 25, 'gender' => 'Male', 'type' => 'Dependent', 'package' => 'Bic Mega', 'premium_value' => 863093],
            ['age_min' => 21, 'age_max' => 25, 'gender' => 'Female', 'type' => 'Dependent', 'package' => 'Bic Mega', 'premium_value' => 1366889],
            ['age_min' => 26, 'age_max' => 30, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Plus', 'premium_value' => 317932],
            ['age_min' => 26, 'age_max' => 30, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Plus', 'premium_value' => 585607],
            ['age_min' => 26, 'age_max' => 30, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Top', 'premium_value' => 498750],
            ['age_min' => 26, 'age_max' => 30, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Top', 'premium_value' => 997500],
            ['age_min' => 26, 'age_max' => 30, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'Bic Mega', 'premium_value' => 667654],
            ['age_min' => 26, 'age_max' => 30, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'Bic Mega', 'premium_value' => 1412243],
            ['age_min' => 31, 'age_max' => 35, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Plus', 'premium_value' => 263430],
            ['age_min' => 31, 'age_max' => 35, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Plus', 'premium_value' => 552144],
            ['age_min' => 31, 'age_max' => 35, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Top', 'premium_value' => 413250],
            ['age_min' => 31, 'age_max' => 35, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Top', 'premium_value' => 940500],
            ['age_min' => 31, 'age_max' => 35, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'Bic Mega', 'premium_value' => 553199],
            ['age_min' => 31, 'age_max' => 35, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'Bic Mega', 'premium_value' => 1331544],
            ['age_min' => 36, 'age_max' => 40, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Plus', 'premium_value' => 299765],
            ['age_min' => 36, 'age_max' => 40, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Plus', 'premium_value' => 451754],
            ['age_min' => 36, 'age_max' => 40, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Top', 'premium_value' => 470250],
            ['age_min' => 36, 'age_max' => 40, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Top', 'premium_value' => 769500],
            ['age_min' => 36, 'age_max' => 40, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'Bic Mega', 'premium_value' => 629503],
            ['age_min' => 36, 'age_max' => 40, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'Bic Mega', 'premium_value' => 1089445],
            ['age_min' => 41, 'age_max' => 45, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Plus', 'premium_value' => 269488],
            ['age_min' => 41, 'age_max' => 45, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Plus', 'premium_value' => 385831],
            ['age_min' => 41, 'age_max' => 45, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Top', 'premium_value' => 441750],
            ['age_min' => 41, 'age_max' => 45, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Top', 'premium_value' => 641250],
            ['age_min' => 41, 'age_max' => 45, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'Bic Mega', 'premium_value' => 591351],
            ['age_min' => 41, 'age_max' => 45, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'Bic Mega', 'premium_value' => 907871],
            ['age_min' => 46, 'age_max' => 50, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Plus', 'premium_value' => 328538],
            ['age_min' => 46, 'age_max' => 50, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Plus', 'premium_value' => 499377],
            ['age_min' => 46, 'age_max' => 50, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Top', 'premium_value' => 534375],
            ['age_min' => 46, 'age_max' => 50, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Top', 'premium_value' => 812250],
            ['age_min' => 46, 'age_max' => 50, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'Bic Mega', 'premium_value' => 726237],
            ['age_min' => 46, 'age_max' => 50, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'Bic Mega', 'premium_value' => 1119458],
            ['age_min' => 51, 'age_max' => 55, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Plus', 'premium_value' => 363351],
            ['age_min' => 51, 'age_max' => 55, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Plus', 'premium_value' => 363351],
            ['age_min' => 51, 'age_max' => 55, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Top', 'premium_value' => 570000],
            ['age_min' => 51, 'age_max' => 55, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Top', 'premium_value' => 570000],
            ['age_min' => 51, 'age_max' => 55, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'Bic Mega', 'premium_value' => 763034],
            ['age_min' => 51, 'age_max' => 55, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'Bic Mega', 'premium_value' => 763034],
            ['age_min' => 56, 'age_max' => 60, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Plus', 'premium_value' => 334130],
            ['age_min' => 56, 'age_max' => 60, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Plus', 'premium_value' => 271967],
            ['age_min' => 56, 'age_max' => 60, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Top', 'premium_value' => 612750],
            ['age_min' => 56, 'age_max' => 60, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Top', 'premium_value' => 498750],
            ['age_min' => 56, 'age_max' => 60, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'Bic Mega', 'premium_value' => 839486],
            ['age_min' => 56, 'age_max' => 60, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'Bic Mega', 'premium_value' => 683302],
            ['age_min' => 61, 'age_max' => 65, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Plus', 'premium_value' => 243604],
            ['age_min' => 61, 'age_max' => 65, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Plus', 'premium_value' => 214077],
            ['age_min' => 61, 'age_max' => 65, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Top', 'premium_value' => 470250],
            ['age_min' => 61, 'age_max' => 65, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Top', 'premium_value' => 413250],
            ['age_min' => 61, 'age_max' => 65, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'Bic Mega', 'premium_value' => 644257],
            ['age_min' => 61, 'age_max' => 65, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'Bic Mega', 'premium_value' => 566165],
            ['age_min' => 66, 'age_max' => 70, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Plus', 'premium_value' => 228840],
            ['age_min' => 66, 'age_max' => 70, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Plus', 'premium_value' => 228840],
            ['age_min' => 66, 'age_max' => 70, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Top', 'premium_value' => 441750],
            ['age_min' => 66, 'age_max' => 70, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Top', 'premium_value' => 441750],
            ['age_min' => 66, 'age_max' => 70, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'Bic Mega', 'premium_value' => 605211],
            ['age_min' => 66, 'age_max' => 70, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'Bic Mega', 'premium_value' => 605211],
            ['age_min' => 71, 'age_max' => 75, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Plus', 'premium_value' => 369097],
            ['age_min' => 71, 'age_max' => 75, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Plus', 'premium_value' => 369097],
            ['age_min' => 71, 'age_max' => 75, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Top', 'premium_value' => 712500],
            ['age_min' => 71, 'age_max' => 75, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Top', 'premium_value' => 712500],
            ['age_min' => 71, 'age_max' => 75, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'Bic Mega', 'premium_value' => 976146],
            ['age_min' => 71, 'age_max' => 75, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'Bic Mega', 'premium_value' => 976146],
        ];
    }
}
