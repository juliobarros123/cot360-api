<?php

namespace App\Services\PublicPortal;

use App\Models\CFG\CFG_rates_health;
use App\Services\ServiceBase;

class HealthFamilySimulationService extends ServiceBase
{
    private const BRANCH_ID = 14;

    private const PACKAGES = [
        'bic_familia_plus' => 'BIC Plus',
        'bic_familia_top' => 'BIC Top',
        'bic_familia_mega' => 'Bic Mega',
    ];

    public function simulate(array $payload): array
    {
        $people = $this->normalizePeople($payload['people'] ?? []);
        if (!$people) {
            return $this->errorResponse('Informe pelo menos uma pessoa segura válida.', 422);
        }

        $plans = [];
        $missing = [];

        foreach (self::PACKAGES as $code => $package) {
            $items = [];
            $annualTotal = 0.0;

            foreach ($people as $index => $person) {
                $rate = $this->findRate($package, $person);
                if (!$rate) {
                    $missing[] = [
                        'package' => $package,
                        'person_index' => $index,
                        'age' => $person['age'],
                        'gender' => $person['gender'],
                        'type' => $person['type'],
                    ];
                    continue;
                }

                $premium = $this->roundMoney((float) $rate->premium_value);
                $annualTotal += $premium;

                $items[] = [
                    'name' => $person['name'],
                    'age' => $person['age'],
                    'gender' => $person['gender'],
                    'type' => $person['type'],
                    'premium' => $premium,
                    'rate_id' => $rate->id,
                ];
            }

            $plans[] = [
                'code' => $code,
                'package' => $package,
                'label' => $this->packageLabel($package),
                'currency' => 'AOA',
                'annual_total' => $this->roundMoney($annualTotal),
                'semester_total' => $this->roundMoney($annualTotal / 2),
                'quarter_total' => $this->roundMoney($annualTotal / 4),
                'monthly_total' => $this->roundMoney($annualTotal / 12),
                'people' => $items,
            ];
        }

        if ($missing) {
            return $this->errorResponse('Existem pessoas sem tarifa configurada para um ou mais planos.', 422, $missing);
        }

        return $this->successResponse([
            'product' => 'health_family',
            'currency' => 'AOA',
            'people_count' => count($people),
            'plans' => $plans,
        ]);
    }

    private function findRate(string $package, array $person): ?CFG_rates_health
    {
        return CFG_rates_health::query()
            ->where('active', true)
            ->where('insurance_branch_id', self::BRANCH_ID)
            ->where('package', $package)
            ->where('type', $person['type'])
            ->where('gender', $person['gender'])
            ->where('age_min', '<=', $person['age'])
            ->where('age_max', '>=', $person['age'])
            ->first();
    }

    private function normalizePeople(array $people): array
    {
        return array_values(array_filter(array_map(function (array $person) {
            $age = isset($person['age']) ? (int) $person['age'] : null;
            if ($age === null || $age < 0) {
                return null;
            }

            return [
                'name' => trim((string) ($person['name'] ?? '')),
                'age' => $age,
                'gender' => $this->normalizeGender($person['gender'] ?? ''),
                'type' => $this->normalizeType($person['type'] ?? ''),
            ];
        }, $people)));
    }

    private function normalizeGender(string $gender): string
    {
        $gender = mb_strtolower(trim($gender));
        return in_array($gender, ['f', 'female'], true) ? 'Female' : 'Male';
    }

    private function normalizeType(string $type): string
    {
        $type = mb_strtolower(trim($type));
        return in_array($type, ['dependent', 'filho'], true) ? 'Dependent' : 'Adult';
    }

    private function packageLabel(string $package): string
    {
        return match ($package) {
            'BIC Plus' => 'BIC Família Plus',
            'BIC Top' => 'BIC Família Top',
            'Bic Mega' => 'BIC Família Mega',
            default => $package,
        };
    }

    private function roundMoney(float $value): float
    {
        return round($value, 2);
    }
}
