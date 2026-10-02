<?php

namespace App\Services\PublicPortal;

use App\Models\CFG\CFG_rates_automobile;
use App\Services\ServiceBase;

class MotorSimulationService extends ServiceBase
{
    public function simulate(array $payload): array
    {
        $rate = $this->findApplicableRate($payload);
        if (!$rate) {
            return $this->errorResponse('Não foi encontrada tarifa automóvel para os dados informados.', 404);
        }

        $vehicleValue = $this->toFloat($payload['vehicle_value'] ?? 0);
        $includeOwnDamage = (bool) ($payload['include_own_damage'] ?? true);

        $rcPremium = $this->roundMoney($this->toFloat($rate->rc_premium_kz));
        $ownDamageRate = $this->ownDamageRate($rate);
        $ownDamagePremium = $includeOwnDamage && $vehicleValue > 0
            ? $this->roundMoney($vehicleValue * ($ownDamageRate / 100))
            : 0.0;

        $premiumSimple = $this->roundMoney($rcPremium + $ownDamagePremium);
        $premiums = $this->calculatePremiums($premiumSimple);

        return $this->successResponse([
            'product' => 'motor',
            'currency' => 'AOA',
            'vehicle' => [
                'brand' => $payload['brand'] ?? null,
                'model' => $payload['model'] ?? null,
                'year' => isset($payload['year']) ? (int) $payload['year'] : null,
                'vehicle_type' => $payload['vehicle_type'],
                'category' => $payload['category'],
                'vehicle_value' => $vehicleValue,
                'engine_cc' => isset($payload['engine_cc']) ? (int) $payload['engine_cc'] : null,
                'number_seats' => isset($payload['number_seats']) ? (int) $payload['number_seats'] : null,
                'weight' => $this->nullableFloat($payload['weight'] ?? null),
            ],
            'rate' => [
                'id' => $rate->id,
                'code' => $rate->code,
                'description' => $rate->description,
                'vehicle_type' => $rate->vehicle_type,
                'category' => $rate->category,
                'rc_limit' => $this->toFloat($rate->min_rc_limit_kz),
                'rc_premium' => $rcPremium,
                'own_damage_rate' => $ownDamageRate,
            ],
            'premium_simple' => $premiumSimple,
            'premiums' => $premiums,
            'coverages' => [
                [
                    'short_name' => 'R.CIVIL OBR.',
                    'name' => 'RESPONSABILIDADE CIVIL OBRIGATÓRIA',
                    'capital' => $this->toFloat($rate->min_rc_limit_kz),
                    'premium' => $rcPremium,
                    'mandatory' => true,
                ],
                [
                    'short_name' => 'DP_FROTA',
                    'name' => 'DANOS PRÓPRIOS',
                    'capital' => $vehicleValue,
                    'applied_rate' => $ownDamageRate,
                    'premium' => $ownDamagePremium,
                    'mandatory' => false,
                ],
            ],
        ]);
    }

    public function options(): array
    {
        $baseQuery = CFG_rates_automobile::query()->where('active', true);

        return $this->successResponse([
            'vehicle_types' => (clone $baseQuery)
                ->whereNotNull('vehicle_type')
                ->distinct()
                ->orderBy('vehicle_type')
                ->pluck('vehicle_type')
                ->values(),
            'categories' => (clone $baseQuery)
                ->whereNotNull('category')
                ->distinct()
                ->orderBy('category')
                ->pluck('category')
                ->values(),
        ]);
    }

    private function findApplicableRate(array $payload): ?CFG_rates_automobile
    {
        $vehicleType = mb_strtolower(trim((string) $payload['vehicle_type']));
        $category = mb_strtolower(trim((string) $payload['category']));
        $engine = isset($payload['engine_cc']) ? (int) $payload['engine_cc'] : null;
        $seats = isset($payload['number_seats']) ? (int) $payload['number_seats'] : null;
        $weight = $this->nullableFloat($payload['weight'] ?? null);

        $query = CFG_rates_automobile::query()
            ->where('active', true)
            ->whereRaw('LOWER(vehicle_type) = ?', [$vehicleType])
            ->whereRaw('LOWER(category) = ?', [$category]);

        if ($engine !== null && $engine > 0) {
            $query->where(function ($q) use ($engine) {
                $q->whereNull('engine_min')->orWhere('engine_min', '<=', $engine);
            })->where(function ($q) use ($engine) {
                $q->whereNull('engine_max')->orWhere('engine_max', '>=', $engine);
            });
        }

        if ($seats !== null && $seats > 0) {
            $query->where(function ($q) use ($seats) {
                $q->whereNull('seat_min')->orWhere('seat_min', '<=', $seats);
            })->where(function ($q) use ($seats) {
                $q->whereNull('seat_max')->orWhere('seat_max', '>=', $seats);
            });
        }

        if ($weight !== null && $weight > 0) {
            $query->where(function ($q) use ($weight) {
                $q->whereNull('weight_min')->orWhere('weight_min', '<=', $weight);
            })->where(function ($q) use ($weight) {
                $q->whereNull('weight_max')->orWhere('weight_max', '>=', $weight);
            });
        }

        return $query
            ->orderByRaw('CASE WHEN engine_min IS NULL THEN 1 ELSE 0 END')
            ->orderByRaw('CASE WHEN seat_min IS NULL THEN 1 ELSE 0 END')
            ->orderBy('engine_min')
            ->orderBy('seat_min')
            ->first();
    }

    private function ownDamageRate(CFG_rates_automobile $rate): float
    {
        return $this->roundMoney(
            $this->toFloat($rate->ccc_percent)
            + $this->toFloat($rate->ire_percent)
            + $this->toFloat($rate->fr_percent)
            + $this->toFloat($rate->av_percent)
            + $this->toFloat($rate->fn_percent)
            + $this->toFloat($rate->gtaop_percent)
        );
    }

    private function calculatePremiums(float $premiumSimple): array
    {
        $charges = $this->roundMoney($premiumSimple * 0.20);
        $commercial = $this->roundMoney($premiumSimple + $charges);
        $iva = $this->roundMoney($commercial * 0.14);
        $annual = $this->roundMoney($premiumSimple + $charges + $iva);

        return [
            'net' => $premiumSimple,
            'charges' => $charges,
            'commercial' => $commercial,
            'iva' => $iva,
            'annual_total' => $annual,
            'semester_total' => $this->roundMoney($annual / 2),
            'quarter_total' => $this->roundMoney($annual / 4),
            'monthly_total' => $this->roundMoney($annual / 12),
        ];
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
