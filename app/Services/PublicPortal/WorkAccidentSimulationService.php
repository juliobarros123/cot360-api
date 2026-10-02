<?php

namespace App\Services\PublicPortal;

use App\Models\CFG\CFG_coverages;
use App\Models\CFG\CFG_insurance_branches;
use App\Models\CFG\CFG_rates_cae;
use App\Services\ServiceBase;

class WorkAccidentSimulationService extends ServiceBase
{
    public function simulate(array $payload): array
    {
        $caeRate = $this->resolveCaeRate($payload);
        if (!$caeRate) {
            return $this->errorResponse('CAE não encontrado ou inactivo.', 404);
        }

        $rate = $this->toFloat($caeRate->work_accident);
        if ($rate <= 0) {
            return $this->errorResponse('O CAE indicado não possui taxa de Acidentes de Trabalho configurada.', 422);
        }

        $salary = $this->resolveAnnualSalary($payload);
        if ($salary <= 0) {
            return $this->errorResponse('Informe uma massa salarial anual válida.', 422);
        }

        $premiumSimple = $this->roundMoney($salary * ($rate / 100));
        $premiums = $this->calculatePremiums($premiumSimple);

        return $this->successResponse([
            'product' => 'work_accident',
            'currency' => 'AOA',
            'cae' => [
                'id' => $caeRate->id,
                'code' => $caeRate->cae,
                'description' => $caeRate->description,
                'work_accident_rate' => $rate,
            ],
            'salary' => [
                'annual_salary' => $salary,
                'total_base_salary' => $this->nullableFloat($payload['total_base_salary'] ?? null),
                'total_addons_salary' => $this->nullableFloat($payload['total_addons_salary'] ?? null),
                'total_avg_variable_salary' => $this->nullableFloat($payload['total_avg_variable_salary'] ?? null),
                'number_of_months' => isset($payload['number_of_months']) ? (int) $payload['number_of_months'] : null,
                'employee_count' => isset($payload['employee_count']) ? (int) $payload['employee_count'] : null,
            ],
            'premium_simple' => $premiumSimple,
            'premiums' => $premiums,
            'coverages' => $this->resolveCoverages($premiumSimple, $salary, $rate),
        ]);
    }

    public function searchCae(array $filters): array
    {
        $query = CFG_rates_cae::query()
            ->where('active', true)
            ->whereNotNull('work_accident');

        if (!empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('cae', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $this->successResponse(
            $query
                ->orderBy('cae')
                ->limit((int) ($filters['limit'] ?? 20))
                ->get(['id', 'cae', 'description', 'work_accident'])
                ->map(fn ($rate) => [
                    'id' => $rate->id,
                    'code' => $rate->cae,
                    'description' => $rate->description,
                    'work_accident_rate' => $this->toFloat($rate->work_accident),
                ])
                ->values()
        );
    }

    private function resolveCaeRate(array $payload): ?CFG_rates_cae
    {
        $query = CFG_rates_cae::query()->where('active', true);

        if (!empty($payload['cae_id'])) {
            return $query->where('id', (int) $payload['cae_id'])->first();
        }

        if (!empty($payload['cae'])) {
            return $query->where('cae', trim((string) $payload['cae']))->first();
        }

        return null;
    }

    private function resolveAnnualSalary(array $payload): float
    {
        foreach (['annual_salary', 'capital_value', 'salary'] as $field) {
            $value = $this->toFloat($payload[$field] ?? null);
            if ($value > 0) {
                return $this->roundMoney($value);
            }
        }

        $base = $this->toFloat($payload['total_base_salary'] ?? 0);
        $addons = $this->toFloat($payload['total_addons_salary'] ?? 0);
        $variable = $this->toFloat($payload['total_avg_variable_salary'] ?? 0);
        $months = (int) ($payload['number_of_months'] ?? 13);

        return $this->roundMoney(($base * $months) + ($addons * 11) + ($variable * 12));
    }

    private function calculatePremiums(float $premiumSimple): array
    {
        $charges = $this->roundMoney($premiumSimple * 0.20);
        $guaranteeFund = $this->roundMoney($premiumSimple * 0.05);
        $commercial = $this->roundMoney($premiumSimple + $charges);
        $iva = $this->roundMoney($commercial * 0.14);
        $annual = $this->roundMoney($premiumSimple + $charges + $iva);

        return [
            'net' => $premiumSimple,
            'charges' => $charges,
            'guarantee_fund' => $guaranteeFund,
            'commercial' => $commercial,
            'iva' => $iva,
            'annual_total' => $annual,
            'semester_total' => $this->roundMoney($annual / 2),
            'quarter_total' => $this->roundMoney($annual / 4),
            'monthly_total' => $this->roundMoney($annual / 12),
        ];
    }

    private function resolveCoverages(float $premiumSimple, float $salary, float $rate): array
    {
        $branch = CFG_insurance_branches::query()
            ->where('code', 10)
            ->orWhere('code', '10')
            ->first();

        $query = CFG_coverages::query()
            ->where('active', true)
            ->where(function ($q) {
                $q->where('risk_rate_origin', 'like', '%WORK_ACCIDENT%')
                    ->orWhere('short_name', 'AC.TR.DOEN.P');
            });

        if ($branch) {
            $query->where('insurance_branch_id', $branch->id);
        }

        $coverages = $query
            ->orderBy('seq')
            ->get(['id', 'short_name', 'name', 'rate_type', 'risk_rate_origin', 'type_covt']);

        if ($coverages->isEmpty()) {
            return [[
                'coverage_id' => null,
                'short_name' => 'AC.TR.DOEN.P',
                'name' => 'ACID.TRABALHO/DOENÇAS PROFISSIONAIS',
                'rate_type' => 'PERCENTAGE',
                'applied_rate' => $rate,
                'capital' => $salary,
                'premium' => $premiumSimple,
                'mandatory' => true,
            ]];
        }

        return $coverages->map(fn ($coverage) => [
            'coverage_id' => $coverage->id,
            'short_name' => $coverage->short_name,
            'name' => $coverage->name,
            'rate_type' => $coverage->rate_type,
            'rate_origin' => $coverage->risk_rate_origin,
            'applied_rate' => $rate,
            'capital' => $salary,
            'premium' => $premiumSimple,
            'mandatory' => $coverage->type_covt === 'O',
        ])->values()->all();
    }

    private function roundMoney(float $value): float
    {
        return round($value, 2);
    }

    private function nullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $this->toFloat($value);
    }

    private function toFloat(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return (float) $value;
    }
}
