<?php

namespace App\Services\CFG;

use App\Models\CFG\CFG_rates_health;
use App\Models\CFG\CFG_coverages;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CFGRatesHealthService
{
    /**
     * Listar todas as tarifas de saúde com filtros
     */
    public function getAll(array $filters = []): array
    {
        try {
            $query = CFG_rates_health::with(['branch', 'coverage']);

            // Aplicar filtros
            if (!empty($filters['insurance_branch_id'])) {
                $query->where('insurance_branch_id', $filters['insurance_branch_id']);
            }

            if (!empty($filters['coverage_id'])) {
                $query->where('coverage_id', $filters['coverage_id']);
            }

            if (!empty($filters['gender'])) {
                $query->where('gender', $filters['gender']);
            }

            if (!empty($filters['type'])) {
                $query->where('type', $filters['type']);
            }

            if (!empty($filters['package'])) {
                $query->where('package', 'like', "%{$filters['package']}%");
            }

            if (isset($filters['active']) && $filters['active'] !== '') {
                $query->where('active', filter_var($filters['active'], FILTER_VALIDATE_BOOLEAN));
            }

            // Filtro por idade
            if (!empty($filters['age'])) {
                $query->where(function($q) use ($filters) {
                    $q->where('age_min', '<=', $filters['age'])
                      ->where('age_max', '>=', $filters['age']);
                });
            }

            // Filtro por range de idades
            if (!empty($filters['age_min'])) {
                $query->where('age_min', '>=', $filters['age_min']);
            }
            if (!empty($filters['age_max'])) {
                $query->where('age_max', '<=', $filters['age_max']);
            }

            // Busca por texto
            if (!empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function($q) use ($search) {
                    $q->where('package', 'like', "%{$search}%")
                      ->orWhereHas('coverage', function($cq) use ($search) {
                          $cq->where('name', 'like', "%{$search}%")
                             ->orWhere('code', 'like', "%{$search}%");
                      });
                });
            }

            // RETORNA SEM PAGINAÇÃO
            $data = $query->orderBy('id', 'desc')->get();

            return [
                'success' => true,
                'message' => 'Tarifas de saúde recuperadas com sucesso',
                'data' => $data,
                'code' => 200
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao listar tarifas de saúde: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erro ao recuperar tarifas de saúde',
                'error' => $e->getMessage(),
                'code' => 500
            ];
        }
    }

    /**
     * Criar uma nova tarifa de saúde
     */
    public function create(array $data): array
    {
        try {
            DB::beginTransaction();

            // Gerar slug único
            $data['slug'] = $this->generateUniqueSlug(
                'saude-' . ($data['package'] ?? '') . '-' . ($data['gender'] ?? '') . '-' . uniqid()
            );
            
            // Garantir que active seja boolean
            $data['active'] = filter_var($data['active'] ?? true, FILTER_VALIDATE_BOOLEAN);

            // Validar ranges de idade
            $this->validateAgeRange($data);

            // Validar se a cobertura existe
            if (!$this->coverageExists($data['coverage_id'])) {
                throw new \InvalidArgumentException('Cobertura não encontrada');
            }

            $rate = CFG_rates_health::create($data);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Tarifa de saúde criada com sucesso',
                'data' => $rate->load(['branch', 'coverage']),
                'code' => 201
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao criar tarifa de saúde: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erro ao criar tarifa de saúde',
                'error' => $e->getMessage(),
                'code' => 500
            ];
        }
    }

    /**
     * Buscar tarifa por ID
     */
    public function getById(int $id): array
    {
        try {
            $rate = CFG_rates_health::with(['branch', 'coverage'])->find($id);

            if (!$rate) {
                return [
                    'success' => false,
                    'message' => 'Tarifa de saúde não encontrada',
                    'code' => 404
                ];
            }

            return [
                'success' => true,
                'message' => 'Tarifa de saúde recuperada com sucesso',
                'data' => $rate,
                'code' => 200
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao buscar tarifa de saúde: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erro ao recuperar tarifa de saúde',
                'error' => $e->getMessage(),
                'code' => 500
            ];
        }
    }

    /**
     * Atualizar tarifa
     */
    public function update(int $id, array $data): array
    {
        try {
            DB::beginTransaction();

            $rate = CFG_rates_health::find($id);

            if (!$rate) {
                return [
                    'success' => false,
                    'message' => 'Tarifa de saúde não encontrada',
                    'code' => 404
                ];
            }

            // Validar ranges de idade se fornecidos
            if (isset($data['age_min']) || isset($data['age_max'])) {
                $this->validateAgeRange(array_merge($rate->toArray(), $data));
            }

            // Validar cobertura se fornecida
            if (isset($data['coverage_id']) && !$this->coverageExists($data['coverage_id'])) {
                throw new \InvalidArgumentException('Cobertura não encontrada');
            }

            // Garantir que active seja boolean se estiver presente
            if (isset($data['active'])) {
                $data['active'] = filter_var($data['active'], FILTER_VALIDATE_BOOLEAN);
            }

            $rate->update($data);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Tarifa de saúde atualizada com sucesso',
                'data' => $rate->fresh(['branch', 'coverage']),
                'code' => 200
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao atualizar tarifa de saúde: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erro ao atualizar tarifa de saúde',
                'error' => $e->getMessage(),
                'code' => 500
            ];
        }
    }

    /**
     * Deletar tarifa (soft delete)
     */
    public function delete(int $id): array
    {
        try {
            DB::beginTransaction();

            $rate = CFG_rates_health::find($id);

            if (!$rate) {
                return [
                    'success' => false,
                    'message' => 'Tarifa de saúde não encontrada',
                    'code' => 404
                ];
            }

            $rate->delete();

            DB::commit();

            return [
                'success' => true,
                'message' => 'Tarifa de saúde deletada com sucesso',
                'code' => 200
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao deletar tarifa de saúde: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erro ao deletar tarifa de saúde',
                'error' => $e->getMessage(),
                'code' => 500
            ];
        }
    }

    /**
     * Buscar tarifas por gênero
     */
    public function getByGender(string $gender): array
    {
        try {
            $rates = CFG_rates_health::where('gender', $gender)
                ->where('active', true)
                ->with(['branch', 'coverage'])
                ->orderBy('age_min')
                ->get();

            return [
                'success' => true,
                'message' => 'Tarifas recuperadas com sucesso',
                'data' => $rates,
                'code' => 200
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao buscar tarifas por gênero: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erro ao recuperar tarifas',
                'error' => $e->getMessage(),
                'code' => 500
            ];
        }
    }

    /**
     * Buscar tarifas por cobertura
     */
    public function getByCoverage(int $coverageId): array
    {
        try {
            $rates = CFG_rates_health::where('coverage_id', $coverageId)
                ->where('active', true)
                ->with(['branch', 'coverage'])
                ->orderBy('age_min')
                ->get();

            return [
                'success' => true,
                'message' => 'Tarifas recuperadas com sucesso',
                'data' => $rates,
                'code' => 200
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao buscar tarifas por cobertura: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erro ao recuperar tarifas',
                'error' => $e->getMessage(),
                'code' => 500
            ];
        }
    }

    /**
     * Buscar tarifa aplicável para uma pessoa específica
     */
    public function findApplicableRate(int $branchId, int $coverageId, int $age, string $gender, ?string $type = null): array
    {
        try {
            $query = CFG_rates_health::where('insurance_branch_id', $branchId)
                ->where('coverage_id', $coverageId)
                ->where('gender', $gender)
                ->where('active', true)
                ->where('age_min', '<=', $age)
                ->where('age_max', '>=', $age);

            if ($type) {
                $query->where('type', $type);
            }

            $rate = $query->first();

            if (!$rate) {
                return [
                    'success' => false,
                    'message' => 'Nenhuma tarifa aplicável encontrada para o perfil informado',
                    'code' => 404
                ];
            }

            return [
                'success' => true,
                'message' => 'Tarifa aplicável encontrada',
                'data' => $rate->load(['branch', 'coverage']),
                'code' => 200
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao buscar tarifa aplicável: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erro ao buscar tarifa aplicável',
                'error' => $e->getMessage(),
                'code' => 500
            ];
        }
    }

    /**
     * Calcular prêmio para uma pessoa
     */
    public function calculatePremium(int $branchId, int $coverageId, int $age, string $gender, float $baseAmount, ?string $type = null): array
    {
        try {
            $rateResult = $this->findApplicableRate($branchId, $coverageId, $age, $gender, $type);
            
            if (!$rateResult['success']) {
                return $rateResult;
            }

            $rate = $rateResult['data'];
            
            // Cálculo do prêmio (exemplo: percentual sobre o valor base)
            $premium = $baseAmount * ($rate->premium_value / 100);

            return [
                'success' => true,
                'message' => 'Prêmio calculado com sucesso',
                'data' => [
                    'rate' => $rate,
                    'base_amount' => $baseAmount,
                    'premium_percentage' => $rate->premium_value,
                    'calculated_premium' => round($premium, 2),
                    'age' => $age,
                    'gender' => $gender
                ],
                'code' => 200
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao calcular prêmio: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erro ao calcular prêmio',
                'error' => $e->getMessage(),
                'code' => 500
            ];
        }
    }

    /**
     * Listar gêneros disponíveis
     */
    public function getGenders(): array
    {
        try {
            $genders = CFG_rates_health::select('gender')
                ->distinct()
                ->whereNotNull('gender')
                ->orderBy('gender')
                ->pluck('gender');

            return [
                'success' => true,
                'message' => 'Gêneros recuperados com sucesso',
                'data' => $genders,
                'code' => 200
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao buscar gêneros: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erro ao recuperar gêneros',
                'error' => $e->getMessage(),
                'code' => 500
            ];
        }
    }

    /**
     * Listar tipos disponíveis
     */
    public function getTypes(): array
    {
        try {
            $types = CFG_rates_health::select('type')
                ->distinct()
                ->whereNotNull('type')
                ->orderBy('type')
                ->pluck('type');

            return [
                'success' => true,
                'message' => 'Tipos recuperados com sucesso',
                'data' => $types,
                'code' => 200
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao buscar tipos: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erro ao recuperar tipos',
                'error' => $e->getMessage(),
                'code' => 500
            ];
        }
    }

    /**
     * Listar pacotes disponíveis
     */
    public function getPackages(): array
    {
        try {
            $packages = CFG_rates_health::select('package')
                ->distinct()
                ->whereNotNull('package')
                ->orderBy('package')
                ->pluck('package');

            return [
                'success' => true,
                'message' => 'Pacotes recuperados com sucesso',
                'data' => $packages,
                'code' => 200
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao buscar pacotes: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erro ao recuperar pacotes',
                'error' => $e->getMessage(),
                'code' => 500
            ];
        }
    }

    /**
     * Gerar slug único
     */
    private function generateUniqueSlug(string $text, ?int $ignoreId = null): string
    {
        $slug = Str::slug($text);
        $originalSlug = $slug;
        $count = 1;

        while ($this->slugExists($slug, $ignoreId)) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }

        return $slug;
    }

    /**
     * Verificar se slug já existe
     */
    private function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        $query = CFG_rates_health::where('slug', $slug);
        
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    /**
     * Validar range de idade
     */
    private function validateAgeRange(array $data): void
    {
        $ageMin = $data['age_min'] ?? null;
        $ageMax = $data['age_max'] ?? null;
        
        if ($ageMin !== null && $ageMax !== null && $ageMin > $ageMax) {
            throw new \InvalidArgumentException('A idade mínima não pode ser maior que a idade máxima');
        }

        if ($ageMin !== null && ($ageMin < 0 || $ageMin > 120)) {
            throw new \InvalidArgumentException('Idade mínima inválida');
        }

        if ($ageMax !== null && ($ageMax < 0 || $ageMax > 120)) {
            throw new \InvalidArgumentException('Idade máxima inválida');
        }
    }

    /**
     * Verificar se cobertura existe
     */
    private function coverageExists(int $coverageId): bool
    {
        return CFG_coverages::where('id', $coverageId)->exists();
    }

    /**
     * Buscar tarifas ativas para dropdown
     */
    public function getActiveList(): array
    {
        try {
            $rates = CFG_rates_health::where('active', true)
                ->with(['branch', 'coverage'])
                ->orderBy('package')
                ->get(['id', 'insurance_branch_id', 'coverage_id', 'package', 'gender', 'type', 'premium_value']);

            return [
                'success' => true,
                'message' => 'Lista de tarifas ativas recuperada com sucesso',
                'data' => $rates,
                'code' => 200
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao buscar lista de tarifas ativas: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erro ao recuperar lista de tarifas',
                'error' => $e->getMessage(),
                'code' => 500
            ];
        }
    }
}