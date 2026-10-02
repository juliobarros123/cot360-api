<?php

namespace App\Console\Commands;

use App\Models\CFG\CFG_insurance_branches as InsuranceBranch;
use App\Models\CFG\CFG_rates_health as RatesHealth;
use Illuminate\Console\Command;

class InsertRateSaudeGrupo extends Command
{
    protected $signature = 'app:insert-rate-saude-grupo
                            {--branch-code=20 : Código do ramo alvo}
                            {--branch-name=SAÚDE GRUPO : Nome do ramo alvo}
                            {--currency= : Moeda específica (AOA, USD). Vazio = ambas}
                            {--reset : Remove antes as tarifas existentes do ramo alvo}';

    protected $description = 'Insere tarifas PRÉMIO RISCO base (Excel oficial 22-02-2017) — valores 100% sem ajustes pré-aplicados. O sistema aplica margem (1,25) e fator de grupo dinamicamente.';

    public function handle()
    {
        $branchCode = trim((string) $this->option('branch-code'));
        $branchName = trim((string) $this->option('branch-name'));
        $currencyFilter = strtoupper(trim((string) $this->option('currency')));

        $branch = InsuranceBranch::query()
            ->when($branchCode !== '', fn($q) => $q->where('code', $branchCode))
            ->first();

        if (!$branch && $branchName !== '') {
            $branch = InsuranceBranch::firstWhere('name', $branchName);
        }

        if (!$branch) {
            $this->error('Ramo SAÚDE GRUPO não encontrado!');
            return 1;
        }

        if ($this->option('reset')) {
            $query = RatesHealth::where('insurance_branch_id', $branch->id);
            if ($currencyFilter !== '') {
                $query->where('currency', $currencyFilter);
            }
            $deleted = $query->withTrashed()->forceDelete();
            $this->info("{$deleted} tarifas antigas removidas permanentemente.");
        }

        $this->info('Inserindo tarifas Prémio Risco base (Excel oficial 22-02-2017)...');

        // Tarifas em AOA (Kwanzas) — 136 registos do Excel oficial
        $ratesAOA = [
            ['age_min' => 0, 'age_max' => 5, 'gender' => 'Male', 'type' => 'Dependent', 'package' => 'BIC Pleno', 'premium_value' => 128267.16, 'currency' => 'AOA'],
            ['age_min' => 0, 'age_max' => 5, 'gender' => 'Female', 'type' => 'Dependent', 'package' => 'BIC Pleno', 'premium_value' => 118765.89, 'currency' => 'AOA'],
            ['age_min' => 6, 'age_max' => 10, 'gender' => 'Male', 'type' => 'Dependent', 'package' => 'BIC Pleno', 'premium_value' => 57007.63, 'currency' => 'AOA'],
            ['age_min' => 6, 'age_max' => 10, 'gender' => 'Female', 'type' => 'Dependent', 'package' => 'BIC Pleno', 'premium_value' => 47506.36, 'currency' => 'AOA'],
            ['age_min' => 11, 'age_max' => 15, 'gender' => 'Male', 'type' => 'Dependent', 'package' => 'BIC Pleno', 'premium_value' => 43705.85, 'currency' => 'AOA'],
            ['age_min' => 11, 'age_max' => 15, 'gender' => 'Female', 'type' => 'Dependent', 'package' => 'BIC Pleno', 'premium_value' => 45606.10, 'currency' => 'AOA'],
            ['age_min' => 16, 'age_max' => 20, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Pleno', 'premium_value' => 71156.26, 'currency' => 'AOA'],
            ['age_min' => 16, 'age_max' => 20, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Pleno', 'premium_value' => 104851.62, 'currency' => 'AOA'],
            ['age_min' => 16, 'age_max' => 20, 'gender' => 'Male', 'type' => 'Dependent', 'package' => 'BIC Pleno', 'premium_value' => 59382.95, 'currency' => 'AOA'],
            ['age_min' => 16, 'age_max' => 20, 'gender' => 'Female', 'type' => 'Dependent', 'package' => 'BIC Pleno', 'premium_value' => 61758.26, 'currency' => 'AOA'],
            ['age_min' => 21, 'age_max' => 25, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Pleno', 'premium_value' => 125804.27, 'currency' => 'AOA'],
            ['age_min' => 21, 'age_max' => 25, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Pleno', 'premium_value' => 262129.05, 'currency' => 'AOA'],
            ['age_min' => 21, 'age_max' => 25, 'gender' => 'Male', 'type' => 'Dependent', 'package' => 'BIC Pleno', 'premium_value' => 104989.05, 'currency' => 'AOA'],
            ['age_min' => 21, 'age_max' => 25, 'gender' => 'Female', 'type' => 'Dependent', 'package' => 'BIC Pleno', 'premium_value' => 166272.25, 'currency' => 'AOA'],
            ['age_min' => 26, 'age_max' => 30, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Pleno', 'premium_value' => 99618.77, 'currency' => 'AOA'],
            ['age_min' => 26, 'age_max' => 30, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Pleno', 'premium_value' => 183490.34, 'currency' => 'AOA'],
            ['age_min' => 31, 'age_max' => 35, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Pleno', 'premium_value' => 82541.26, 'currency' => 'AOA'],
            ['age_min' => 31, 'age_max' => 35, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Pleno', 'premium_value' => 173005.18, 'currency' => 'AOA'],
            ['age_min' => 36, 'age_max' => 40, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Pleno', 'premium_value' => 93926.26, 'currency' => 'AOA'],
            ['age_min' => 36, 'age_max' => 40, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Pleno', 'premium_value' => 141549.69, 'currency' => 'AOA'],
            ['age_min' => 41, 'age_max' => 45, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Pleno', 'premium_value' => 84439.71, 'currency' => 'AOA'],
            ['age_min' => 41, 'age_max' => 45, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Pleno', 'premium_value' => 120893.80, 'currency' => 'AOA'],
            ['age_min' => 46, 'age_max' => 50, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Pleno', 'premium_value' => 102941.80, 'currency' => 'AOA'],
            ['age_min' => 46, 'age_max' => 50, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Pleno', 'premium_value' => 156471.53, 'currency' => 'AOA'],
            ['age_min' => 51, 'age_max' => 55, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Pleno', 'premium_value' => 113850.02, 'currency' => 'AOA'],
            ['age_min' => 51, 'age_max' => 55, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Pleno', 'premium_value' => 113850.02, 'currency' => 'AOA'],
            ['age_min' => 56, 'age_max' => 60, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Pleno', 'premium_value' => 104694.18, 'currency' => 'AOA'],
            ['age_min' => 56, 'age_max' => 60, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Pleno', 'premium_value' => 85216.19, 'currency' => 'AOA'],
            ['age_min' => 61, 'age_max' => 65, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Pleno', 'premium_value' => 76329.36, 'currency' => 'AOA'],
            ['age_min' => 61, 'age_max' => 65, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Pleno', 'premium_value' => 67077.32, 'currency' => 'AOA'],
            ['age_min' => 66, 'age_max' => 70, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Pleno', 'premium_value' => 71703.34, 'currency' => 'AOA'],
            ['age_min' => 66, 'age_max' => 70, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Pleno', 'premium_value' => 71703.34, 'currency' => 'AOA'],
            ['age_min' => 71, 'age_max' => 75, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Pleno', 'premium_value' => 115650.55, 'currency' => 'AOA'],
            ['age_min' => 71, 'age_max' => 75, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Pleno', 'premium_value' => 115650.55, 'currency' => 'AOA'],
            ['age_min' => 0, 'age_max' => 5, 'gender' => 'Male', 'type' => 'Dependent', 'package' => 'BIC Extra', 'premium_value' => 160739.74, 'currency' => 'AOA'],
            ['age_min' => 0, 'age_max' => 5, 'gender' => 'Female', 'type' => 'Dependent', 'package' => 'BIC Extra', 'premium_value' => 148833.10, 'currency' => 'AOA'],
            ['age_min' => 6, 'age_max' => 10, 'gender' => 'Male', 'type' => 'Dependent', 'package' => 'BIC Extra', 'premium_value' => 71439.89, 'currency' => 'AOA'],
            ['age_min' => 6, 'age_max' => 10, 'gender' => 'Female', 'type' => 'Dependent', 'package' => 'BIC Extra', 'premium_value' => 59533.24, 'currency' => 'AOA'],
            ['age_min' => 11, 'age_max' => 15, 'gender' => 'Male', 'type' => 'Dependent', 'package' => 'BIC Extra', 'premium_value' => 54770.58, 'currency' => 'AOA'],
            ['age_min' => 11, 'age_max' => 15, 'gender' => 'Female', 'type' => 'Dependent', 'package' => 'BIC Extra', 'premium_value' => 57151.91, 'currency' => 'AOA'],
            ['age_min' => 16, 'age_max' => 20, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Extra', 'premium_value' => 85740.81, 'currency' => 'AOA'],
            ['age_min' => 16, 'age_max' => 20, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Extra', 'premium_value' => 131396.24, 'currency' => 'AOA'],
            ['age_min' => 16, 'age_max' => 20, 'gender' => 'Male', 'type' => 'Dependent', 'package' => 'BIC Extra', 'premium_value' => 74416.55, 'currency' => 'AOA'],
            ['age_min' => 16, 'age_max' => 20, 'gender' => 'Female', 'type' => 'Dependent', 'package' => 'BIC Extra', 'premium_value' => 77393.21, 'currency' => 'AOA'],
            ['age_min' => 21, 'age_max' => 25, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Extra', 'premium_value' => 151589.74, 'currency' => 'AOA'],
            ['age_min' => 21, 'age_max' => 25, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Extra', 'premium_value' => 328490.59, 'currency' => 'AOA'],
            ['age_min' => 21, 'age_max' => 25, 'gender' => 'Male', 'type' => 'Dependent', 'package' => 'BIC Extra', 'premium_value' => 131568.46, 'currency' => 'AOA'],
            ['age_min' => 21, 'age_max' => 25, 'gender' => 'Female', 'type' => 'Dependent', 'package' => 'BIC Extra', 'premium_value' => 208366.33, 'currency' => 'AOA'],
            ['age_min' => 26, 'age_max' => 30, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Extra', 'premium_value' => 120037.13, 'currency' => 'AOA'],
            ['age_min' => 26, 'age_max' => 30, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Extra', 'premium_value' => 229943.41, 'currency' => 'AOA'],
            ['age_min' => 31, 'age_max' => 35, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Extra', 'premium_value' => 99459.33, 'currency' => 'AOA'],
            ['age_min' => 31, 'age_max' => 35, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Extra', 'premium_value' => 216803.79, 'currency' => 'AOA'],
            ['age_min' => 36, 'age_max' => 40, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Extra', 'premium_value' => 113177.86, 'currency' => 'AOA'],
            ['age_min' => 36, 'age_max' => 40, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Extra', 'premium_value' => 177384.92, 'currency' => 'AOA'],
            ['age_min' => 41, 'age_max' => 45, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Extra', 'premium_value' => 105893.32, 'currency' => 'AOA'],
            ['age_min' => 41, 'age_max' => 45, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Extra', 'premium_value' => 151609.31, 'currency' => 'AOA'],
            ['age_min' => 46, 'age_max' => 50, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Extra', 'premium_value' => 124041.27, 'currency' => 'AOA'],
            ['age_min' => 46, 'age_max' => 50, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Extra', 'premium_value' => 190353.15, 'currency' => 'AOA'],
            ['age_min' => 51, 'age_max' => 55, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Extra', 'premium_value' => 137185.29, 'currency' => 'AOA'],
            ['age_min' => 51, 'age_max' => 55, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Extra', 'premium_value' => 137185.29, 'currency' => 'AOA'],
            ['age_min' => 56, 'age_max' => 60, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Extra', 'premium_value' => 127515.27, 'currency' => 'AOA'],
            ['age_min' => 56, 'age_max' => 60, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Extra', 'premium_value' => 103791.50, 'currency' => 'AOA'],
            ['age_min' => 61, 'age_max' => 65, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Extra', 'premium_value' => 97860.56, 'currency' => 'AOA'],
            ['age_min' => 61, 'age_max' => 65, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Extra', 'premium_value' => 85998.67, 'currency' => 'AOA'],
            ['age_min' => 66, 'age_max' => 70, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Extra', 'premium_value' => 91929.62, 'currency' => 'AOA'],
            ['age_min' => 66, 'age_max' => 70, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Extra', 'premium_value' => 91929.62, 'currency' => 'AOA'],
            ['age_min' => 71, 'age_max' => 75, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Extra', 'premium_value' => 148273.57, 'currency' => 'AOA'],
            ['age_min' => 71, 'age_max' => 75, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Extra', 'premium_value' => 148273.57, 'currency' => 'AOA'],
            ['age_min' => 0, 'age_max' => 5, 'gender' => 'Male', 'type' => 'Dependent', 'package' => 'BIC Total', 'premium_value' => 216000.00, 'currency' => 'AOA'],
            ['age_min' => 0, 'age_max' => 5, 'gender' => 'Female', 'type' => 'Dependent', 'package' => 'BIC Total', 'premium_value' => 200000.00, 'currency' => 'AOA'],
            ['age_min' => 6, 'age_max' => 10, 'gender' => 'Male', 'type' => 'Dependent', 'package' => 'BIC Total', 'premium_value' => 96000.00, 'currency' => 'AOA'],
            ['age_min' => 6, 'age_max' => 10, 'gender' => 'Female', 'type' => 'Dependent', 'package' => 'BIC Total', 'premium_value' => 80000.00, 'currency' => 'AOA'],
            ['age_min' => 11, 'age_max' => 15, 'gender' => 'Male', 'type' => 'Dependent', 'package' => 'BIC Total', 'premium_value' => 73600.00, 'currency' => 'AOA'],
            ['age_min' => 11, 'age_max' => 15, 'gender' => 'Female', 'type' => 'Dependent', 'package' => 'BIC Total', 'premium_value' => 76800.00, 'currency' => 'AOA'],
            ['age_min' => 16, 'age_max' => 20, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Total', 'premium_value' => 100000.00, 'currency' => 'AOA'],
            ['age_min' => 16, 'age_max' => 20, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Total', 'premium_value' => 160000.00, 'currency' => 'AOA'],
            ['age_min' => 16, 'age_max' => 20, 'gender' => 'Male', 'type' => 'Dependent', 'package' => 'BIC Total', 'premium_value' => 100000.00, 'currency' => 'AOA'],
            ['age_min' => 16, 'age_max' => 20, 'gender' => 'Female', 'type' => 'Dependent', 'package' => 'BIC Total', 'premium_value' => 104000.00, 'currency' => 'AOA'],
            ['age_min' => 21, 'age_max' => 25, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Total', 'premium_value' => 176800.00, 'currency' => 'AOA'],
            ['age_min' => 21, 'age_max' => 25, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Total', 'premium_value' => 400000.00, 'currency' => 'AOA'],
            ['age_min' => 21, 'age_max' => 25, 'gender' => 'Male', 'type' => 'Dependent', 'package' => 'BIC Total', 'premium_value' => 176800.00, 'currency' => 'AOA'],
            ['age_min' => 21, 'age_max' => 25, 'gender' => 'Female', 'type' => 'Dependent', 'package' => 'BIC Total', 'premium_value' => 280000.00, 'currency' => 'AOA'],
            ['age_min' => 26, 'age_max' => 30, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Total', 'premium_value' => 140000.00, 'currency' => 'AOA'],
            ['age_min' => 26, 'age_max' => 30, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Total', 'premium_value' => 280000.00, 'currency' => 'AOA'],
            ['age_min' => 31, 'age_max' => 35, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Total', 'premium_value' => 116000.00, 'currency' => 'AOA'],
            ['age_min' => 31, 'age_max' => 35, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Total', 'premium_value' => 264000.00, 'currency' => 'AOA'],
            ['age_min' => 36, 'age_max' => 40, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Total', 'premium_value' => 132000.00, 'currency' => 'AOA'],
            ['age_min' => 36, 'age_max' => 40, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Total', 'premium_value' => 216000.00, 'currency' => 'AOA'],
            ['age_min' => 41, 'age_max' => 45, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Total', 'premium_value' => 124000.00, 'currency' => 'AOA'],
            ['age_min' => 41, 'age_max' => 45, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Total', 'premium_value' => 180000.00, 'currency' => 'AOA'],
            ['age_min' => 46, 'age_max' => 50, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Total', 'premium_value' => 150000.00, 'currency' => 'AOA'],
            ['age_min' => 46, 'age_max' => 50, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Total', 'premium_value' => 228000.00, 'currency' => 'AOA'],
            ['age_min' => 51, 'age_max' => 55, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Total', 'premium_value' => 160000.00, 'currency' => 'AOA'],
            ['age_min' => 51, 'age_max' => 55, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Total', 'premium_value' => 160000.00, 'currency' => 'AOA'],
            ['age_min' => 56, 'age_max' => 60, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Total', 'premium_value' => 172000.00, 'currency' => 'AOA'],
            ['age_min' => 56, 'age_max' => 60, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Total', 'premium_value' => 140000.00, 'currency' => 'AOA'],
            ['age_min' => 61, 'age_max' => 65, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Total', 'premium_value' => 132000.00, 'currency' => 'AOA'],
            ['age_min' => 61, 'age_max' => 65, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Total', 'premium_value' => 116000.00, 'currency' => 'AOA'],
            ['age_min' => 66, 'age_max' => 70, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Total', 'premium_value' => 124000.00, 'currency' => 'AOA'],
            ['age_min' => 66, 'age_max' => 70, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Total', 'premium_value' => 124000.00, 'currency' => 'AOA'],
            ['age_min' => 71, 'age_max' => 75, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Total', 'premium_value' => 200000.00, 'currency' => 'AOA'],
            ['age_min' => 71, 'age_max' => 75, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Total', 'premium_value' => 200000.00, 'currency' => 'AOA'],
            ['age_min' => 0, 'age_max' => 5, 'gender' => 'Male', 'type' => 'Dependent', 'package' => 'Bic Integral', 'premium_value' => 295988.05, 'currency' => 'AOA'],
            ['age_min' => 0, 'age_max' => 5, 'gender' => 'Female', 'type' => 'Dependent', 'package' => 'Bic Integral', 'premium_value' => 274063.01, 'currency' => 'AOA'],
            ['age_min' => 6, 'age_max' => 10, 'gender' => 'Male', 'type' => 'Dependent', 'package' => 'Bic Integral', 'premium_value' => 131550.25, 'currency' => 'AOA'],
            ['age_min' => 6, 'age_max' => 10, 'gender' => 'Female', 'type' => 'Dependent', 'package' => 'Bic Integral', 'premium_value' => 109625.20, 'currency' => 'AOA'],
            ['age_min' => 11, 'age_max' => 15, 'gender' => 'Male', 'type' => 'Dependent', 'package' => 'Bic Integral', 'premium_value' => 100855.19, 'currency' => 'AOA'],
            ['age_min' => 11, 'age_max' => 15, 'gender' => 'Female', 'type' => 'Dependent', 'package' => 'Bic Integral', 'premium_value' => 105240.20, 'currency' => 'AOA'],
            ['age_min' => 16, 'age_max' => 20, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'Bic Integral', 'premium_value' => 133865.53, 'currency' => 'AOA'],
            ['age_min' => 16, 'age_max' => 20, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'Bic Integral', 'premium_value' => 226525.23, 'currency' => 'AOA'],
            ['age_min' => 16, 'age_max' => 20, 'gender' => 'Male', 'type' => 'Dependent', 'package' => 'Bic Integral', 'premium_value' => 137031.51, 'currency' => 'AOA'],
            ['age_min' => 16, 'age_max' => 20, 'gender' => 'Female', 'type' => 'Dependent', 'package' => 'Bic Integral', 'premium_value' => 142512.77, 'currency' => 'AOA'],
            ['age_min' => 21, 'age_max' => 25, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'Bic Integral', 'premium_value' => 236674.26, 'currency' => 'AOA'],
            ['age_min' => 21, 'age_max' => 25, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'Bic Integral', 'premium_value' => 566313.07, 'currency' => 'AOA'],
            ['age_min' => 21, 'age_max' => 25, 'gender' => 'Male', 'type' => 'Dependent', 'package' => 'Bic Integral', 'premium_value' => 242271.70, 'currency' => 'AOA'],
            ['age_min' => 21, 'age_max' => 25, 'gender' => 'Female', 'type' => 'Dependent', 'package' => 'Bic Integral', 'premium_value' => 383688.22, 'currency' => 'AOA'],
            ['age_min' => 26, 'age_max' => 30, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'Bic Integral', 'premium_value' => 187411.74, 'currency' => 'AOA'],
            ['age_min' => 26, 'age_max' => 30, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'Bic Integral', 'premium_value' => 396419.15, 'currency' => 'AOA'],
            ['age_min' => 31, 'age_max' => 35, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'Bic Integral', 'premium_value' => 155284.01, 'currency' => 'AOA'],
            ['age_min' => 31, 'age_max' => 35, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'Bic Integral', 'premium_value' => 373766.63, 'currency' => 'AOA'],
            ['age_min' => 36, 'age_max' => 40, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'Bic Integral', 'premium_value' => 176702.50, 'currency' => 'AOA'],
            ['age_min' => 36, 'age_max' => 40, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'Bic Integral', 'premium_value' => 305809.06, 'currency' => 'AOA'],
            ['age_min' => 41, 'age_max' => 45, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'Bic Integral', 'premium_value' => 165993.26, 'currency' => 'AOA'],
            ['age_min' => 41, 'age_max' => 45, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'Bic Integral', 'premium_value' => 254840.88, 'currency' => 'AOA'],
            ['age_min' => 46, 'age_max' => 50, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'Bic Integral', 'premium_value' => 203856.14, 'currency' => 'AOA'],
            ['age_min' => 46, 'age_max' => 50, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'Bic Integral', 'premium_value' => 314233.81, 'currency' => 'AOA'],
            ['age_min' => 51, 'age_max' => 55, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'Bic Integral', 'premium_value' => 214184.85, 'currency' => 'AOA'],
            ['age_min' => 51, 'age_max' => 55, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'Bic Integral', 'premium_value' => 214184.85, 'currency' => 'AOA'],
            ['age_min' => 56, 'age_max' => 60, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'Bic Integral', 'premium_value' => 235645.16, 'currency' => 'AOA'],
            ['age_min' => 56, 'age_max' => 60, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'Bic Integral', 'premium_value' => 191804.20, 'currency' => 'AOA'],
            ['age_min' => 61, 'age_max' => 65, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'Bic Integral', 'premium_value' => 180843.96, 'currency' => 'AOA'],
            ['age_min' => 61, 'age_max' => 65, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'Bic Integral', 'premium_value' => 158923.48, 'currency' => 'AOA'],
            ['age_min' => 66, 'age_max' => 70, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'Bic Integral', 'premium_value' => 169883.72, 'currency' => 'AOA'],
            ['age_min' => 66, 'age_max' => 70, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'Bic Integral', 'premium_value' => 169883.72, 'currency' => 'AOA'],
            ['age_min' => 71, 'age_max' => 75, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'Bic Integral', 'premium_value' => 274006.01, 'currency' => 'AOA'],
            ['age_min' => 71, 'age_max' => 75, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'Bic Integral', 'premium_value' => 274006.01, 'currency' => 'AOA'],
        ];

        // Tarifas em USD — 136 registos do Excel oficial
        $ratesUSD = [
            ['age_min' => 0, 'age_max' => 5, 'gender' => 'Male', 'type' => 'Dependent', 'package' => 'BIC Pleno', 'premium_value' => 745.74, 'currency' => 'USD'],
            ['age_min' => 0, 'age_max' => 5, 'gender' => 'Female', 'type' => 'Dependent', 'package' => 'BIC Pleno', 'premium_value' => 690.50, 'currency' => 'USD'],
            ['age_min' => 6, 'age_max' => 10, 'gender' => 'Male', 'type' => 'Dependent', 'package' => 'BIC Pleno', 'premium_value' => 331.44, 'currency' => 'USD'],
            ['age_min' => 6, 'age_max' => 10, 'gender' => 'Female', 'type' => 'Dependent', 'package' => 'BIC Pleno', 'premium_value' => 276.20, 'currency' => 'USD'],
            ['age_min' => 11, 'age_max' => 15, 'gender' => 'Male', 'type' => 'Dependent', 'package' => 'BIC Pleno', 'premium_value' => 254.10, 'currency' => 'USD'],
            ['age_min' => 11, 'age_max' => 15, 'gender' => 'Female', 'type' => 'Dependent', 'package' => 'BIC Pleno', 'premium_value' => 265.15, 'currency' => 'USD'],
            ['age_min' => 16, 'age_max' => 20, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Pleno', 'premium_value' => 413.70, 'currency' => 'USD'],
            ['age_min' => 16, 'age_max' => 20, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Pleno', 'premium_value' => 609.60, 'currency' => 'USD'],
            ['age_min' => 16, 'age_max' => 20, 'gender' => 'Male', 'type' => 'Dependent', 'package' => 'BIC Pleno', 'premium_value' => 345.25, 'currency' => 'USD'],
            ['age_min' => 16, 'age_max' => 20, 'gender' => 'Female', 'type' => 'Dependent', 'package' => 'BIC Pleno', 'premium_value' => 359.06, 'currency' => 'USD'],
            ['age_min' => 21, 'age_max' => 25, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Pleno', 'premium_value' => 731.42, 'currency' => 'USD'],
            ['age_min' => 21, 'age_max' => 25, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Pleno', 'premium_value' => 1524.01, 'currency' => 'USD'],
            ['age_min' => 21, 'age_max' => 25, 'gender' => 'Male', 'type' => 'Dependent', 'package' => 'BIC Pleno', 'premium_value' => 610.40, 'currency' => 'USD'],
            ['age_min' => 21, 'age_max' => 25, 'gender' => 'Female', 'type' => 'Dependent', 'package' => 'BIC Pleno', 'premium_value' => 966.70, 'currency' => 'USD'],
            ['age_min' => 26, 'age_max' => 30, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Pleno', 'premium_value' => 579.18, 'currency' => 'USD'],
            ['age_min' => 26, 'age_max' => 30, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Pleno', 'premium_value' => 1066.80, 'currency' => 'USD'],
            ['age_min' => 31, 'age_max' => 35, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Pleno', 'premium_value' => 479.89, 'currency' => 'USD'],
            ['age_min' => 31, 'age_max' => 35, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Pleno', 'premium_value' => 1005.84, 'currency' => 'USD'],
            ['age_min' => 36, 'age_max' => 40, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Pleno', 'premium_value' => 546.08, 'currency' => 'USD'],
            ['age_min' => 36, 'age_max' => 40, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Pleno', 'premium_value' => 822.96, 'currency' => 'USD'],
            ['age_min' => 41, 'age_max' => 45, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Pleno', 'premium_value' => 490.93, 'currency' => 'USD'],
            ['age_min' => 41, 'age_max' => 45, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Pleno', 'premium_value' => 702.87, 'currency' => 'USD'],
            ['age_min' => 46, 'age_max' => 50, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Pleno', 'premium_value' => 598.50, 'currency' => 'USD'],
            ['age_min' => 46, 'age_max' => 50, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Pleno', 'premium_value' => 909.72, 'currency' => 'USD'],
            ['age_min' => 51, 'age_max' => 55, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Pleno', 'premium_value' => 661.92, 'currency' => 'USD'],
            ['age_min' => 51, 'age_max' => 55, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Pleno', 'premium_value' => 661.92, 'currency' => 'USD'],
            ['age_min' => 56, 'age_max' => 60, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Pleno', 'premium_value' => 608.69, 'currency' => 'USD'],
            ['age_min' => 56, 'age_max' => 60, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Pleno', 'premium_value' => 495.44, 'currency' => 'USD'],
            ['age_min' => 61, 'age_max' => 65, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Pleno', 'premium_value' => 443.78, 'currency' => 'USD'],
            ['age_min' => 61, 'age_max' => 65, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Pleno', 'premium_value' => 389.98, 'currency' => 'USD'],
            ['age_min' => 66, 'age_max' => 70, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Pleno', 'premium_value' => 416.88, 'currency' => 'USD'],
            ['age_min' => 66, 'age_max' => 70, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Pleno', 'premium_value' => 416.88, 'currency' => 'USD'],
            ['age_min' => 71, 'age_max' => 75, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Pleno', 'premium_value' => 672.39, 'currency' => 'USD'],
            ['age_min' => 71, 'age_max' => 75, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Pleno', 'premium_value' => 672.39, 'currency' => 'USD'],
            ['age_min' => 0, 'age_max' => 5, 'gender' => 'Male', 'type' => 'Dependent', 'package' => 'BIC Extra', 'premium_value' => 934.53, 'currency' => 'USD'],
            ['age_min' => 0, 'age_max' => 5, 'gender' => 'Female', 'type' => 'Dependent', 'package' => 'BIC Extra', 'premium_value' => 865.31, 'currency' => 'USD'],
            ['age_min' => 6, 'age_max' => 10, 'gender' => 'Male', 'type' => 'Dependent', 'package' => 'BIC Extra', 'premium_value' => 415.35, 'currency' => 'USD'],
            ['age_min' => 6, 'age_max' => 10, 'gender' => 'Female', 'type' => 'Dependent', 'package' => 'BIC Extra', 'premium_value' => 346.12, 'currency' => 'USD'],
            ['age_min' => 11, 'age_max' => 15, 'gender' => 'Male', 'type' => 'Dependent', 'package' => 'BIC Extra', 'premium_value' => 318.43, 'currency' => 'USD'],
            ['age_min' => 11, 'age_max' => 15, 'gender' => 'Female', 'type' => 'Dependent', 'package' => 'BIC Extra', 'premium_value' => 332.28, 'currency' => 'USD'],
            ['age_min' => 16, 'age_max' => 20, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Extra', 'premium_value' => 498.49, 'currency' => 'USD'],
            ['age_min' => 16, 'age_max' => 20, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Extra', 'premium_value' => 763.93, 'currency' => 'USD'],
            ['age_min' => 16, 'age_max' => 20, 'gender' => 'Male', 'type' => 'Dependent', 'package' => 'BIC Extra', 'premium_value' => 432.65, 'currency' => 'USD'],
            ['age_min' => 16, 'age_max' => 20, 'gender' => 'Female', 'type' => 'Dependent', 'package' => 'BIC Extra', 'premium_value' => 449.96, 'currency' => 'USD'],
            ['age_min' => 21, 'age_max' => 25, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Extra', 'premium_value' => 881.34, 'currency' => 'USD'],
            ['age_min' => 21, 'age_max' => 25, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Extra', 'premium_value' => 1909.83, 'currency' => 'USD'],
            ['age_min' => 21, 'age_max' => 25, 'gender' => 'Male', 'type' => 'Dependent', 'package' => 'BIC Extra', 'premium_value' => 764.93, 'currency' => 'USD'],
            ['age_min' => 21, 'age_max' => 25, 'gender' => 'Female', 'type' => 'Dependent', 'package' => 'BIC Extra', 'premium_value' => 1211.43, 'currency' => 'USD'],
            ['age_min' => 26, 'age_max' => 30, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Extra', 'premium_value' => 697.89, 'currency' => 'USD'],
            ['age_min' => 26, 'age_max' => 30, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Extra', 'premium_value' => 1336.88, 'currency' => 'USD'],
            ['age_min' => 31, 'age_max' => 35, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Extra', 'premium_value' => 578.25, 'currency' => 'USD'],
            ['age_min' => 31, 'age_max' => 35, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Extra', 'premium_value' => 1260.49, 'currency' => 'USD'],
            ['age_min' => 36, 'age_max' => 40, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Extra', 'premium_value' => 658.01, 'currency' => 'USD'],
            ['age_min' => 36, 'age_max' => 40, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Extra', 'premium_value' => 1031.31, 'currency' => 'USD'],
            ['age_min' => 41, 'age_max' => 45, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Extra', 'premium_value' => 615.66, 'currency' => 'USD'],
            ['age_min' => 41, 'age_max' => 45, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Extra', 'premium_value' => 881.45, 'currency' => 'USD'],
            ['age_min' => 46, 'age_max' => 50, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Extra', 'premium_value' => 721.17, 'currency' => 'USD'],
            ['age_min' => 46, 'age_max' => 50, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Extra', 'premium_value' => 1106.70, 'currency' => 'USD'],
            ['age_min' => 51, 'age_max' => 55, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Extra', 'premium_value' => 797.59, 'currency' => 'USD'],
            ['age_min' => 51, 'age_max' => 55, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Extra', 'premium_value' => 797.59, 'currency' => 'USD'],
            ['age_min' => 56, 'age_max' => 60, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Extra', 'premium_value' => 741.37, 'currency' => 'USD'],
            ['age_min' => 56, 'age_max' => 60, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Extra', 'premium_value' => 603.44, 'currency' => 'USD'],
            ['age_min' => 61, 'age_max' => 65, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Extra', 'premium_value' => 568.96, 'currency' => 'USD'],
            ['age_min' => 61, 'age_max' => 65, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Extra', 'premium_value' => 499.99, 'currency' => 'USD'],
            ['age_min' => 66, 'age_max' => 70, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Extra', 'premium_value' => 534.47, 'currency' => 'USD'],
            ['age_min' => 66, 'age_max' => 70, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Extra', 'premium_value' => 534.47, 'currency' => 'USD'],
            ['age_min' => 71, 'age_max' => 75, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Extra', 'premium_value' => 862.06, 'currency' => 'USD'],
            ['age_min' => 71, 'age_max' => 75, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Extra', 'premium_value' => 862.06, 'currency' => 'USD'],
            ['age_min' => 0, 'age_max' => 5, 'gender' => 'Male', 'type' => 'Dependent', 'package' => 'BIC Total', 'premium_value' => 1255.81, 'currency' => 'USD'],
            ['age_min' => 0, 'age_max' => 5, 'gender' => 'Female', 'type' => 'Dependent', 'package' => 'BIC Total', 'premium_value' => 1162.79, 'currency' => 'USD'],
            ['age_min' => 6, 'age_max' => 10, 'gender' => 'Male', 'type' => 'Dependent', 'package' => 'BIC Total', 'premium_value' => 558.14, 'currency' => 'USD'],
            ['age_min' => 6, 'age_max' => 10, 'gender' => 'Female', 'type' => 'Dependent', 'package' => 'BIC Total', 'premium_value' => 465.12, 'currency' => 'USD'],
            ['age_min' => 11, 'age_max' => 15, 'gender' => 'Male', 'type' => 'Dependent', 'package' => 'BIC Total', 'premium_value' => 427.91, 'currency' => 'USD'],
            ['age_min' => 11, 'age_max' => 15, 'gender' => 'Female', 'type' => 'Dependent', 'package' => 'BIC Total', 'premium_value' => 446.51, 'currency' => 'USD'],
            ['age_min' => 16, 'age_max' => 20, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Total', 'premium_value' => 581.40, 'currency' => 'USD'],
            ['age_min' => 16, 'age_max' => 20, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Total', 'premium_value' => 930.23, 'currency' => 'USD'],
            ['age_min' => 16, 'age_max' => 20, 'gender' => 'Male', 'type' => 'Dependent', 'package' => 'BIC Total', 'premium_value' => 581.40, 'currency' => 'USD'],
            ['age_min' => 16, 'age_max' => 20, 'gender' => 'Female', 'type' => 'Dependent', 'package' => 'BIC Total', 'premium_value' => 604.65, 'currency' => 'USD'],
            ['age_min' => 21, 'age_max' => 25, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Total', 'premium_value' => 1027.91, 'currency' => 'USD'],
            ['age_min' => 21, 'age_max' => 25, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Total', 'premium_value' => 2325.58, 'currency' => 'USD'],
            ['age_min' => 21, 'age_max' => 25, 'gender' => 'Male', 'type' => 'Dependent', 'package' => 'BIC Total', 'premium_value' => 1027.91, 'currency' => 'USD'],
            ['age_min' => 21, 'age_max' => 25, 'gender' => 'Female', 'type' => 'Dependent', 'package' => 'BIC Total', 'premium_value' => 1627.91, 'currency' => 'USD'],
            ['age_min' => 26, 'age_max' => 30, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Total', 'premium_value' => 813.95, 'currency' => 'USD'],
            ['age_min' => 26, 'age_max' => 30, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Total', 'premium_value' => 1627.91, 'currency' => 'USD'],
            ['age_min' => 31, 'age_max' => 35, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Total', 'premium_value' => 674.42, 'currency' => 'USD'],
            ['age_min' => 31, 'age_max' => 35, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Total', 'premium_value' => 1534.88, 'currency' => 'USD'],
            ['age_min' => 36, 'age_max' => 40, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Total', 'premium_value' => 767.44, 'currency' => 'USD'],
            ['age_min' => 36, 'age_max' => 40, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Total', 'premium_value' => 1255.81, 'currency' => 'USD'],
            ['age_min' => 41, 'age_max' => 45, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Total', 'premium_value' => 720.93, 'currency' => 'USD'],
            ['age_min' => 41, 'age_max' => 45, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Total', 'premium_value' => 1046.51, 'currency' => 'USD'],
            ['age_min' => 46, 'age_max' => 50, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Total', 'premium_value' => 872.09, 'currency' => 'USD'],
            ['age_min' => 46, 'age_max' => 50, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Total', 'premium_value' => 1325.58, 'currency' => 'USD'],
            ['age_min' => 51, 'age_max' => 55, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Total', 'premium_value' => 930.23, 'currency' => 'USD'],
            ['age_min' => 51, 'age_max' => 55, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Total', 'premium_value' => 930.23, 'currency' => 'USD'],
            ['age_min' => 56, 'age_max' => 60, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Total', 'premium_value' => 1000.00, 'currency' => 'USD'],
            ['age_min' => 56, 'age_max' => 60, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Total', 'premium_value' => 813.95, 'currency' => 'USD'],
            ['age_min' => 61, 'age_max' => 65, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Total', 'premium_value' => 767.44, 'currency' => 'USD'],
            ['age_min' => 61, 'age_max' => 65, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Total', 'premium_value' => 674.42, 'currency' => 'USD'],
            ['age_min' => 66, 'age_max' => 70, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Total', 'premium_value' => 720.93, 'currency' => 'USD'],
            ['age_min' => 66, 'age_max' => 70, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Total', 'premium_value' => 720.93, 'currency' => 'USD'],
            ['age_min' => 71, 'age_max' => 75, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'BIC Total', 'premium_value' => 1162.79, 'currency' => 'USD'],
            ['age_min' => 71, 'age_max' => 75, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'BIC Total', 'premium_value' => 1162.79, 'currency' => 'USD'],
            ['age_min' => 0, 'age_max' => 5, 'gender' => 'Male', 'type' => 'Dependent', 'package' => 'Bic Integral', 'premium_value' => 1720.86, 'currency' => 'USD'],
            ['age_min' => 0, 'age_max' => 5, 'gender' => 'Female', 'type' => 'Dependent', 'package' => 'Bic Integral', 'premium_value' => 1593.39, 'currency' => 'USD'],
            ['age_min' => 6, 'age_max' => 10, 'gender' => 'Male', 'type' => 'Dependent', 'package' => 'Bic Integral', 'premium_value' => 764.83, 'currency' => 'USD'],
            ['age_min' => 6, 'age_max' => 10, 'gender' => 'Female', 'type' => 'Dependent', 'package' => 'Bic Integral', 'premium_value' => 637.36, 'currency' => 'USD'],
            ['age_min' => 11, 'age_max' => 15, 'gender' => 'Male', 'type' => 'Dependent', 'package' => 'Bic Integral', 'premium_value' => 586.37, 'currency' => 'USD'],
            ['age_min' => 11, 'age_max' => 15, 'gender' => 'Female', 'type' => 'Dependent', 'package' => 'Bic Integral', 'premium_value' => 611.86, 'currency' => 'USD'],
            ['age_min' => 16, 'age_max' => 20, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'Bic Integral', 'premium_value' => 778.29, 'currency' => 'USD'],
            ['age_min' => 16, 'age_max' => 20, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'Bic Integral', 'premium_value' => 1317.01, 'currency' => 'USD'],
            ['age_min' => 16, 'age_max' => 20, 'gender' => 'Male', 'type' => 'Dependent', 'package' => 'Bic Integral', 'premium_value' => 796.69, 'currency' => 'USD'],
            ['age_min' => 16, 'age_max' => 20, 'gender' => 'Female', 'type' => 'Dependent', 'package' => 'Bic Integral', 'premium_value' => 828.56, 'currency' => 'USD'],
            ['age_min' => 21, 'age_max' => 25, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'Bic Integral', 'premium_value' => 1376.01, 'currency' => 'USD'],
            ['age_min' => 21, 'age_max' => 25, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'Bic Integral', 'premium_value' => 3292.52, 'currency' => 'USD'],
            ['age_min' => 21, 'age_max' => 25, 'gender' => 'Male', 'type' => 'Dependent', 'package' => 'Bic Integral', 'premium_value' => 1408.56, 'currency' => 'USD'],
            ['age_min' => 21, 'age_max' => 25, 'gender' => 'Female', 'type' => 'Dependent', 'package' => 'Bic Integral', 'premium_value' => 2230.75, 'currency' => 'USD'],
            ['age_min' => 26, 'age_max' => 30, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'Bic Integral', 'premium_value' => 1089.60, 'currency' => 'USD'],
            ['age_min' => 26, 'age_max' => 30, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'Bic Integral', 'premium_value' => 2304.76, 'currency' => 'USD'],
            ['age_min' => 31, 'age_max' => 35, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'Bic Integral', 'premium_value' => 902.81, 'currency' => 'USD'],
            ['age_min' => 31, 'age_max' => 35, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'Bic Integral', 'premium_value' => 2173.06, 'currency' => 'USD'],
            ['age_min' => 36, 'age_max' => 40, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'Bic Integral', 'premium_value' => 1027.34, 'currency' => 'USD'],
            ['age_min' => 36, 'age_max' => 40, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'Bic Integral', 'premium_value' => 1777.96, 'currency' => 'USD'],
            ['age_min' => 41, 'age_max' => 45, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'Bic Integral', 'premium_value' => 965.08, 'currency' => 'USD'],
            ['age_min' => 41, 'age_max' => 45, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'Bic Integral', 'premium_value' => 1481.63, 'currency' => 'USD'],
            ['age_min' => 46, 'age_max' => 50, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'Bic Integral', 'premium_value' => 1185.21, 'currency' => 'USD'],
            ['age_min' => 46, 'age_max' => 50, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'Bic Integral', 'premium_value' => 1826.94, 'currency' => 'USD'],
            ['age_min' => 51, 'age_max' => 55, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'Bic Integral', 'premium_value' => 1245.26, 'currency' => 'USD'],
            ['age_min' => 51, 'age_max' => 55, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'Bic Integral', 'premium_value' => 1245.26, 'currency' => 'USD'],
            ['age_min' => 56, 'age_max' => 60, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'Bic Integral', 'premium_value' => 1370.03, 'currency' => 'USD'],
            ['age_min' => 56, 'age_max' => 60, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'Bic Integral', 'premium_value' => 1115.14, 'currency' => 'USD'],
            ['age_min' => 61, 'age_max' => 65, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'Bic Integral', 'premium_value' => 1051.42, 'currency' => 'USD'],
            ['age_min' => 61, 'age_max' => 65, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'Bic Integral', 'premium_value' => 923.97, 'currency' => 'USD'],
            ['age_min' => 66, 'age_max' => 70, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'Bic Integral', 'premium_value' => 987.70, 'currency' => 'USD'],
            ['age_min' => 66, 'age_max' => 70, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'Bic Integral', 'premium_value' => 987.70, 'currency' => 'USD'],
            ['age_min' => 71, 'age_max' => 75, 'gender' => 'Male', 'type' => 'Adult', 'package' => 'Bic Integral', 'premium_value' => 1593.06, 'currency' => 'USD'],
            ['age_min' => 71, 'age_max' => 75, 'gender' => 'Female', 'type' => 'Adult', 'package' => 'Bic Integral', 'premium_value' => 1593.06, 'currency' => 'USD'],
        ];

        $rates = collect();
        if ($currencyFilter === '' || $currencyFilter === 'AOA') {
            $rates = $rates->merge($ratesAOA);
        }
        if ($currencyFilter === '' || $currencyFilter === 'USD') {
            $rates = $rates->merge($ratesUSD);
        }

        $count = 0;
        foreach ($rates as $rate) {
            RatesHealth::create(array_merge($rate, [
                'insurance_branch_id' => $branch->id,
                'active' => true,
                'slug' => $this->buildRateSlug($branch->id, $rate),
            ]));
            $count++;
        }

        $this->info("✅ {$count} tarifas inseridas com sucesso!");
        $this->info('Lembrete: o sistema aplica em runtime: Tarifa × 1,25 (margem) × Fator_Grupo.');

        return 0;
    }

    private function buildRateSlug(int $branchId, array $rate): string
    {
        $parts = [
            'rate-health-sg',
            $branchId,
            $rate['currency'] ?? 'AOA',
            $rate['package'],
            $rate['type'],
            $rate['gender'],
            $rate['age_min'],
            $rate['age_max'],
        ];

        return collect($parts)
            ->filter(fn ($value) => $value !== null && $value !== "")
            ->map(fn ($value) => str((string) $value)->slug("-")->value())
            ->implode("-");
    }
}