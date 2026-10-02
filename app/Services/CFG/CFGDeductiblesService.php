<?php

namespace App\Services\CFG;

use App\Models\CFG\CFG_deductibles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CFGDeductiblesService
{
    /**
     * Listar todas as franquias/deduções com filtros
     */
    public function getAll(array $filters = []): array
    {
        try {
            $query = CFG_deductibles::with('coverage');

            // Aplicar filtros
            if (!empty($filters['coverage_id'])) {
                $query->where('coverage_id', $filters['coverage_id']);
            }

            if (!empty($filters['deduct_type'])) {
                $query->where('deduct_type', $filters['deduct_type']);
            }

            if (!empty($filters['deduct_object'])) {
                $query->where('deduct_object', $filters['deduct_object']);
            }

            if (!empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function($q) use ($search) {
                    $q->where('deduct_type', 'like', "%{$search}%")
                      ->orWhere('deduct_object', 'like', "%{$search}%")
                      ->orWhere('details', 'like', "%{$search}%")
                      ->orWhereHas('coverage', function($cq) use ($search) {
                          $cq->where('name', 'like', "%{$search}%");
                      });
                });
            }

            // Ordenação
            $orderBy = $filters['order_by'] ?? 'id';
            $orderDir = $filters['order_dir'] ?? 'desc';
            
            // Retorna todos os resultados sem paginação
            $data = $query->orderBy($orderBy, $orderDir)->get();

            return [
                'success' => true,
                'message' => 'Franquias recuperadas com sucesso',
                'data' => $data,
                'code' => 200
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao listar franquias: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erro ao recuperar franquias',
                'error' => $e->getMessage(),
                'code' => 500
            ];
        }
    }

    /**
     * Criar uma nova franquia/dedução
     */
    public function create(array $data): array
    {
        try {
            DB::beginTransaction();

            // Gerar slug único baseado nos dados
            $slugBase = $this->generateSlugBase($data);
            $data['slug'] = $this->generateUniqueSlug($slugBase);
            
            // Garantir que valores numéricos sejam float
            $data['deduct_percent'] = isset($data['deduct_percent']) ? (float) $data['deduct_percent'] : null;
            $data['deduct_value'] = isset($data['deduct_value']) ? (float) $data['deduct_value'] : null;
            $data['deduct_min'] = isset($data['deduct_min']) ? (float) $data['deduct_min'] : null;
            $data['deduct_max'] = isset($data['deduct_max']) ? (float) $data['deduct_max'] : null;
            $data['discount'] = isset($data['discount']) ? (float) $data['discount'] : null;

            $deductible = CFG_deductibles::create($data);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Franquia criada com sucesso',
                'data' => $deductible->load('coverage'),
                'code' => 201
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao criar franquia: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erro ao criar franquia',
                'error' => $e->getMessage(),
                'code' => 500
            ];
        }
    }

    /**
     * Buscar franquia por ID
     */
    public function getById(int $id): array
    {
        try {
            $deductible = CFG_deductibles::with('coverage')->find($id);

            if (!$deductible) {
                return [
                    'success' => false,
                    'message' => 'Franquia não encontrada',
                    'code' => 404
                ];
            }

            return [
                'success' => true,
                'message' => 'Franquia recuperada com sucesso',
                'data' => $deductible,
                'code' => 200
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao buscar franquia: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erro ao recuperar franquia',
                'error' => $e->getMessage(),
                'code' => 500
            ];
        }
    }

    /**
     * Atualizar franquia
     */
    public function update(int $id, array $data): array
    {
        try {
            DB::beginTransaction();

            $deductible = CFG_deductibles::find($id);

            if (!$deductible) {
                return [
                    'success' => false,
                    'message' => 'Franquia não encontrada',
                    'code' => 404
                ];
            }

            // Verificar se precisa atualizar o slug
            if ($this->shouldUpdateSlug($deductible, $data)) {
                $slugBase = $this->generateSlugBase(array_merge($deductible->toArray(), $data));
                $data['slug'] = $this->generateUniqueSlug($slugBase, $id);
            }

            // Converter valores numéricos
            if (isset($data['deduct_percent'])) {
                $data['deduct_percent'] = (float) $data['deduct_percent'];
            }
            if (isset($data['deduct_value'])) {
                $data['deduct_value'] = (float) $data['deduct_value'];
            }
            if (isset($data['deduct_min'])) {
                $data['deduct_min'] = (float) $data['deduct_min'];
            }
            if (isset($data['deduct_max'])) {
                $data['deduct_max'] = (float) $data['deduct_max'];
            }
            if (isset($data['discount'])) {
                $data['discount'] = (float) $data['discount'];
            }

            $deductible->update($data);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Franquia atualizada com sucesso',
                'data' => $deductible->fresh('coverage'),
                'code' => 200
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao atualizar franquia: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erro ao atualizar franquia',
                'error' => $e->getMessage(),
                'code' => 500
            ];
        }
    }

    /**
     * Deletar franquia (soft delete)
     */
    public function delete(int $id): array
    {
        try {
            DB::beginTransaction();

            $deductible = CFG_deductibles::find($id);

            if (!$deductible) {
                return [
                    'success' => false,
                    'message' => 'Franquia não encontrada',
                    'code' => 404
                ];
            }

            $deductible->delete();

            DB::commit();

            return [
                'success' => true,
                'message' => 'Franquia deletada com sucesso',
                'code' => 200
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao deletar franquia: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erro ao deletar franquia',
                'error' => $e->getMessage(),
                'code' => 500
            ];
        }
    }

    /**
     * Gerar base para o slug
     */
    private function generateSlugBase(array $data): string
    {
        $parts = [];
        
        if (!empty($data['coverage_id'])) {
            $parts[] = 'cov-' . $data['coverage_id'];
        }
        
        if (!empty($data['deduct_type'])) {
            $parts[] = $data['deduct_type'];
        }
        
        if (!empty($data['deduct_object'])) {
            $parts[] = $data['deduct_object'];
        }
        
        if (!empty($data['deduct_percent'])) {
            $parts[] = $data['deduct_percent'] . 'p';
        }
        
        if (!empty($data['deduct_value'])) {
            $parts[] = $data['deduct_value'] . 'v';
        }
        
        return implode('-', $parts);
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
        $query = CFG_deductibles::where('slug', $slug);
        
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    /**
     * Verificar se precisa atualizar o slug
     */
    private function shouldUpdateSlug(CFG_deductibles $deductible, array $newData): bool
    {
        $fieldsToCheck = ['coverage_id', 'deduct_type', 'deduct_object', 'deduct_percent', 'deduct_value'];
        
        foreach ($fieldsToCheck as $field) {
            if (isset($newData[$field]) && $newData[$field] != $deductible->{$field}) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Buscar franquias por cobertura
     */
    public function getByCoverage(int $coverageId): array
    {
        try {
            $data = CFG_deductibles::where('coverage_id', $coverageId)
                ->orderBy('deduct_type')
                ->orderBy('deduct_object')
                ->get();

            return [
                'success' => true,
                'message' => 'Franquias da cobertura recuperadas com sucesso',
                'data' => $data,
                'code' => 200
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao buscar franquias por cobertura: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erro ao recuperar franquias da cobertura',
                'error' => $e->getMessage(),
                'code' => 500
            ];
        }
    }

    /**
     * Buscar franquias por tipo
     */
    public function getByType(string $type): array
    {
        try {
            $data = CFG_deductibles::where('deduct_type', $type)
                ->with('coverage')
                ->orderBy('id', 'desc')
                ->get();

            return [
                'success' => true,
                'message' => 'Franquias por tipo recuperadas com sucesso',
                'data' => $data,
                'code' => 200
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao buscar franquias por tipo: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erro ao recuperar franquias por tipo',
                'error' => $e->getMessage(),
                'code' => 500
            ];
        }
    }

    /**
     * Buscar franquias por objeto
     */
    public function getByObject(string $object): array
    {
        try {
            $data = CFG_deductibles::where('deduct_object', $object)
                ->with('coverage')
                ->orderBy('id', 'desc')
                ->get();

            return [
                'success' => true,
                'message' => 'Franquias por objeto recuperadas com sucesso',
                'data' => $data,
                'code' => 200
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao buscar franquias por objeto: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erro ao recuperar franquias por objeto',
                'error' => $e->getMessage(),
                'code' => 500
            ];
        }
    }

    /**
     * Calcular valor da franquia baseado em regras de negócio
     */
    public function calculateDeductible(int $coverageId, string $type, string $object, float $insuredValue): array
    {
        try {
            $deductible = CFG_deductibles::where('coverage_id', $coverageId)
                ->where('deduct_type', $type)
                ->where('deduct_object', $object)
                ->first();

            if (!$deductible) {
                return [
                    'success' => false,
                    'message' => 'Franquia não configurada para esta cobertura/tipo/objeto',
                    'code' => 404
                ];
            }

            $calculatedValue = null;
            $calculationMethod = null;

            // Calcular baseado no tipo de franquia
            if ($deductible->deduct_percent !== null) {
                // Franquia baseada em percentual
                $calculatedValue = $insuredValue * ($deductible->deduct_percent / 100);
                $calculationMethod = 'percentual';
                
                // Aplicar limites mínimo e máximo se existirem
                if ($deductible->deduct_min !== null && $calculatedValue < $deductible->deduct_min) {
                    $calculatedValue = $deductible->deduct_min;
                    $calculationMethod .= ' (aplicado mínimo)';
                }
                if ($deductible->deduct_max !== null && $calculatedValue > $deductible->deduct_max) {
                    $calculatedValue = $deductible->deduct_max;
                    $calculationMethod .= ' (aplicado máximo)';
                }
            } elseif ($deductible->deduct_value !== null) {
                // Franquia com valor fixo
                $calculatedValue = $deductible->deduct_value;
                $calculationMethod = 'valor fixo';
            }

            return [
                'success' => true,
                'message' => 'Cálculo realizado com sucesso',
                'data' => [
                    'deductible' => $deductible,
                    'insured_value' => $insuredValue,
                    'calculated_value' => $calculatedValue,
                    'calculation_method' => $calculationMethod,
                    'applied_discount' => $deductible->discount ?? 0,
                    'details' => $deductible->details
                ],
                'code' => 200
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao calcular franquia: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erro ao calcular franquia',
                'error' => $e->getMessage(),
                'code' => 500
            ];
        }
    }
}