<?php

namespace App\Services\CFG;

use App\Models\CFG\CFG_rates_automobile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CFGRatesAutomobileService
{
    /**
     * Listar todas as tarifas de automóvel com filtros
     */
  public function getAll(array $filters = []): array
    {
        try {
            $query = CFG_rates_automobile::with('branch');

            // Aplicar filtros
            if (!empty($filters['insurance_branch_id'])) {
                $query->where('insurance_branch_id', $filters['insurance_branch_id']);
            }

            if (!empty($filters['vehicle_type'])) {
                $query->where('vehicle_type', $filters['vehicle_type']);
            }

            if (!empty($filters['category'])) {
                $query->where('category', $filters['category']);
            }

            if (!empty($filters['code'])) {
                $query->where('code', 'like', "%{$filters['code']}%");
            }

            if (isset($filters['active']) && $filters['active'] !== '') {
                $query->where('active', filter_var($filters['active'], FILTER_VALIDATE_BOOLEAN));
            }

            // Filtros de range
            if (!empty($filters['engine'])) {
                $query->where(function($q) use ($filters) {
                    $q->where('engine_min', '<=', $filters['engine'])
                      ->where('engine_max', '>=', $filters['engine']);
                });
            }

            if (!empty($filters['seats'])) {
                $query->where(function($q) use ($filters) {
                    $q->where('seat_min', '<=', $filters['seats'])
                      ->where('seat_max', '>=', $filters['seats']);
                });
            }

            if (!empty($filters['weight'])) {
                $query->where(function($q) use ($filters) {
                    $q->where('weight_min', '<=', $filters['weight'])
                      ->where('weight_max', '>=', $filters['weight']);
                });
            }

            if (!empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function($q) use ($search) {
                    $q->where('code', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('category', 'like', "%{$search}%");
                });
            }

            // RETORNA SEM PAGINAÇÃO
            $data = $query->orderBy('id', 'desc')->get();

            return [
                'success' => true,
                'message' => 'Tarifas de automóvel recuperadas com sucesso',
                'data' => $data,
                'code' => 200
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao listar tarifas de automóvel: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erro ao recuperar tarifas de automóvel',
                'error' => $e->getMessage(),
                'code' => 500
            ];
        }
    }

    /**
     * Criar uma nova tarifa de automóvel
     */
    public function create(array $data): array
    {
        try {
            DB::beginTransaction();

            // Gerar slug único
            $data['slug'] = $this->generateUniqueSlug(
                $data['code'] . ' ' . ($data['description'] ?? '')
            );
            
            // Garantir que active seja boolean
            $data['active'] = filter_var($data['active'] ?? true, FILTER_VALIDATE_BOOLEAN);

            // Validar ranges
            $this->validateRanges($data);

            $rate = CFG_rates_automobile::create($data);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Tarifa de automóvel criada com sucesso',
                'data' => $rate->load('branch'),
                'code' => 201
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao criar tarifa de automóvel: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erro ao criar tarifa de automóvel',
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
            $rate = CFG_rates_automobile::with('branch')->find($id);

            if (!$rate) {
                return [
                    'success' => false,
                    'message' => 'Tarifa de automóvel não encontrada',
                    'code' => 404
                ];
            }

            return [
                'success' => true,
                'message' => 'Tarifa de automóvel recuperada com sucesso',
                'data' => $rate,
                'code' => 200
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao buscar tarifa de automóvel: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erro ao recuperar tarifa de automóvel',
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

            $rate = CFG_rates_automobile::find($id);

            if (!$rate) {
                return [
                    'success' => false,
                    'message' => 'Tarifa de automóvel não encontrada',
                    'code' => 404
                ];
            }

            // Verificar se precisa atualizar o slug
            if ($this->shouldUpdateSlug($rate, $data)) {
                $slugBase = $data['code'] ?? $rate->code . ' ' . 
                           ($data['description'] ?? $rate->description);
                $data['slug'] = $this->generateUniqueSlug($slugBase, $id);
            }

            // Garantir que active seja boolean se estiver presente
            if (isset($data['active'])) {
                $data['active'] = filter_var($data['active'], FILTER_VALIDATE_BOOLEAN);
            }

            // Validar ranges se fornecidos
            $this->validateRanges($data, $rate);

            $rate->update($data);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Tarifa de automóvel atualizada com sucesso',
                'data' => $rate->fresh('branch'),
                'code' => 200
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao atualizar tarifa de automóvel: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erro ao atualizar tarifa de automóvel',
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

            $rate = CFG_rates_automobile::find($id);

            if (!$rate) {
                return [
                    'success' => false,
                    'message' => 'Tarifa de automóvel não encontrada',
                    'code' => 404
                ];
            }

            $rate->delete();

            DB::commit();

            return [
                'success' => true,
                'message' => 'Tarifa de automóvel deletada com sucesso',
                'code' => 200
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao deletar tarifa de automóvel: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erro ao deletar tarifa de automóvel',
                'error' => $e->getMessage(),
                'code' => 500
            ];
        }
    }

    /**
     * Buscar tarifas por tipo de veículo
     */
    public function getByVehicleType(string $vehicleType): array
    {
        try {
            $rates = CFG_rates_automobile::where('vehicle_type', $vehicleType)
                ->where('active', true)
                ->with('branch')
                ->orderBy('code')
                ->get();

            return [
                'success' => true,
                'message' => 'Tarifas recuperadas com sucesso',
                'data' => $rates,
                'code' => 200
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao buscar tarifas por tipo: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erro ao recuperar tarifas',
                'error' => $e->getMessage(),
                'code' => 500
            ];
        }
    }

    /**
     * Buscar tarifa aplicável para um veículo específico
     */
    public function findApplicableRate(int $branchId, string $vehicleType, float $engine, int $seats, float $weight): array
    {
        try {
            $rate = CFG_rates_automobile::where('insurance_branch_id', $branchId)
                ->where('vehicle_type', $vehicleType)
                ->where('active', true)
                ->where('engine_min', '<=', $engine)
                ->where('engine_max', '>=', $engine)
                ->where('seat_min', '<=', $seats)
                ->where('seat_max', '>=', $seats)
                ->where('weight_min', '<=', $weight)
                ->where('weight_max', '>=', $weight)
                ->first();

            if (!$rate) {
                return [
                    'success' => false,
                    'message' => 'Nenhuma tarifa aplicável encontrada para as características do veículo',
                    'code' => 404
                ];
            }

            return [
                'success' => true,
                'message' => 'Tarifa aplicável encontrada',
                'data' => $rate->load('branch'),
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
        $query = CFG_rates_automobile::where('slug', $slug);
        
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    /**
     * Verificar se precisa atualizar o slug
     */
    private function shouldUpdateSlug(CFG_rates_automobile $rate, array $newData): bool
    {
        return (isset($newData['code']) && $newData['code'] !== $rate->code) ||
               (isset($newData['description']) && $newData['description'] !== $rate->description);
    }

    /**
     * Validar ranges (min <= max)
     */
    private function validateRanges(array $data, ?CFG_rates_automobile $existing = null): void
    {
        $ranges = [
            'engine' => ['min' => 'engine_min', 'max' => 'engine_max'],
            'seat' => ['min' => 'seat_min', 'max' => 'seat_max'],
            'weight' => ['min' => 'weight_min', 'max' => 'weight_max']
        ];

        foreach ($ranges as $type => $fields) {
            $min = $data[$fields['min']] ?? ($existing ? $existing->{$fields['min']} : null);
            $max = $data[$fields['max']] ?? ($existing ? $existing->{$fields['max']} : null);
            
            if ($min !== null && $max !== null && $min > $max) {
                throw new \InvalidArgumentException("O valor mínimo de {$type} não pode ser maior que o máximo");
            }
        }
    }

    /**
     * Lista de tipos de veículo disponíveis
     */
    public function getVehicleTypes(): array
    {
        try {
            $types = CFG_rates_automobile::select('vehicle_type')
                ->distinct()
                ->whereNotNull('vehicle_type')
                ->orderBy('vehicle_type')
                ->pluck('vehicle_type');

            return [
                'success' => true,
                'message' => 'Tipos de veículo recuperados com sucesso',
                'data' => $types,
                'code' => 200
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao buscar tipos de veículo: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erro ao recuperar tipos de veículo',
                'error' => $e->getMessage(),
                'code' => 500
            ];
        }
    }

    /**
     * Lista de categorias disponíveis
     */
    public function getCategories(): array
    {
        try {
            $categories = CFG_rates_automobile::select('category')
                ->distinct()
                ->whereNotNull('category')
                ->orderBy('category')
                ->pluck('category');

            return [
                'success' => true,
                'message' => 'Categorias recuperadas com sucesso',
                'data' => $categories,
                'code' => 200
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao buscar categorias: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erro ao recuperar categorias',
                'error' => $e->getMessage(),
                'code' => 500
            ];
        }
    }
}