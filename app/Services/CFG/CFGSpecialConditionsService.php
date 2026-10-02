<?php

namespace App\Services\CFG;

use App\Models\CFG\CFG_special_conditions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CFGSpecialConditionsService
{
    /**
     * Listar todas as condições especiais com filtros
     */
    public function getAll(array $filters = []): array
    {
        try {
            $query = CFG_special_conditions::with('branch');

            // Aplicar filtros
            if (!empty($filters['insurance_branch_id'])) {
                $query->where('insurance_branch_id', $filters['insurance_branch_id']);
            }

            if (!empty($filters['condition_type'])) {
                $query->where('condition_type', $filters['condition_type']);
            }

            if (isset($filters['active']) && $filters['active'] !== '') {
                $query->where('active', filter_var($filters['active'], FILTER_VALIDATE_BOOLEAN));
            }

            if (!empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function($q) use ($search) {
                    $q->where('code', 'like', "%{$search}%")
                      ->orWhere('short_text', 'like', "%{$search}%")
                      ->orWhere('long_text', 'like', "%{$search}%");
                });
            }

            // Removida a paginação, agora retorna todos os resultados
            $data = $query->orderBy('id', 'desc')->get();

            return [
                'success' => true,
                'message' => 'Condições especiais recuperadas com sucesso',
                'data' => $data,
                'code' => 200
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao listar condições especiais: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erro ao recuperar condições especiais',
                'error' => $e->getMessage(),
                'code' => 500
            ];
        }
    }

    /**
     * Criar uma nova condição especial
     */
    public function create(array $data): array
    {
        try {
            DB::beginTransaction();

            // Gerar slug único baseado no código e texto curto
            $data['slug'] = $this->generateUniqueSlug($data['code'] . ' ' . $data['short_text']);
            
            // Garantir que active seja boolean
            $data['active'] = filter_var($data['active'] ?? true, FILTER_VALIDATE_BOOLEAN);

            $specialCondition = CFG_special_conditions::create($data);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Condição especial criada com sucesso',
                'data' => $specialCondition->load('branch'),
                'code' => 201
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao criar condição especial: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erro ao criar condição especial',
                'error' => $e->getMessage(),
                'code' => 500
            ];
        }
    }

    /**
     * Buscar condição especial por ID
     */
    public function getById(int $id): array
    {
        try {
            $specialCondition = CFG_special_conditions::with('branch')->find($id);

            if (!$specialCondition) {
                return [
                    'success' => false,
                    'message' => 'Condição especial não encontrada',
                    'code' => 404
                ];
            }

            return [
                'success' => true,
                'message' => 'Condição especial recuperada com sucesso',
                'data' => $specialCondition,
                'code' => 200
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao buscar condição especial: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erro ao recuperar condição especial',
                'error' => $e->getMessage(),
                'code' => 500
            ];
        }
    }

    /**
     * Atualizar condição especial
     */
    public function update(int $id, array $data): array
    {
        try {
            DB::beginTransaction();

            $specialCondition = CFG_special_conditions::find($id);

            if (!$specialCondition) {
                return [
                    'success' => false,
                    'message' => 'Condição especial não encontrada',
                    'code' => 404
                ];
            }

            // Verificar se precisa atualizar o slug
            if ($this->shouldUpdateSlug($specialCondition, $data)) {
                $slugBase = $data['code'] ?? $specialCondition->code . ' ' . 
                           ($data['short_text'] ?? $specialCondition->short_text);
                $data['slug'] = $this->generateUniqueSlug($slugBase, $id);
            }

            // Garantir que active seja boolean se estiver presente
            if (isset($data['active'])) {
                $data['active'] = filter_var($data['active'], FILTER_VALIDATE_BOOLEAN);
            }

            $specialCondition->update($data);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Condição especial atualizada com sucesso',
                'data' => $specialCondition->fresh('branch'),
                'code' => 200
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao atualizar condição especial: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erro ao atualizar condição especial',
                'error' => $e->getMessage(),
                'code' => 500
            ];
        }
    }

    /**
     * Deletar condição especial (soft delete)
     */
    public function delete(int $id): array
    {
        try {
            DB::beginTransaction();

            $specialCondition = CFG_special_conditions::find($id);

            if (!$specialCondition) {
                return [
                    'success' => false,
                    'message' => 'Condição especial não encontrada',
                    'code' => 404
                ];
            }

            $specialCondition->delete();

            DB::commit();

            return [
                'success' => true,
                'message' => 'Condição especial deletada com sucesso',
                'code' => 200
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao deletar condição especial: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erro ao deletar condição especial',
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
        $query = CFG_special_conditions::where('slug', $slug);
        
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    /**
     * Verificar se precisa atualizar o slug
     */
    private function shouldUpdateSlug(CFG_special_conditions $specialCondition, array $newData): bool
    {
        return (isset($newData['code']) && $newData['code'] !== $specialCondition->code) ||
               (isset($newData['short_text']) && $newData['short_text'] !== $specialCondition->short_text);
    }

    /**
     * Listar todas as condições especiais ativas (para selects)
     */
    public function getActiveList(): array
    {
        try {
            $data = CFG_special_conditions::where('active', true)
                ->with('branch')
                ->orderBy('code')
                ->get(['id', 'insurance_branch_id', 'code', 'short_text', 'condition_type']);

            return [
                'success' => true,
                'message' => 'Lista de condições especiais ativas recuperada com sucesso',
                'data' => $data,
                'code' => 200
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao listar condições especiais ativas: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erro ao recuperar lista de condições especiais',
                'error' => $e->getMessage(),
                'code' => 500
            ];
        }
    }

    /**
     * Buscar condições especiais por ramo de seguro
     */
    public function getByBranch(int $branchId): array
    {
        try {
            $data = CFG_special_conditions::where('insurance_branch_id', $branchId)
                ->where('active', true)
                ->orderBy('code')
                ->get();

            return [
                'success' => true,
                'message' => 'Condições especiais do ramo recuperadas com sucesso',
                'data' => $data,
                'code' => 200
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao buscar condições especiais por ramo: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erro ao recuperar condições especiais do ramo',
                'error' => $e->getMessage(),
                'code' => 500
            ];
        }
    }
}