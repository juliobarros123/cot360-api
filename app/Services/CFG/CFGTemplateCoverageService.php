<?php

namespace App\Services\CFG;

use App\Models\CFG\CFG_template_coverage;
use App\Services\ServiceBase;
use Illuminate\Database\Eloquent\Builder;

class CFGTemplateCoverageService extends ServiceBase
{
    public function getAll(array $filters = []): array
    {
        $q = CFG_template_coverage::with([
            'template' => function ($query) {
                $query->with('branch');
            },
            'coverage' => function ($query) {
                $query->with('branch');
            }
        ])->orderBy('display_order');
        
        // Filtros básicos
        if (isset($filters['template_id'])) {
            $q->where('template_id', $filters['template_id']);
        }
        
        if (isset($filters['coverage_id'])) {
            $q->where('coverage_id', $filters['coverage_id']);
        }
        
        // Filtro por insurance_branch_id - Melhorado para evitar duplicações
        if (isset($filters['insurance_branch_id'])) {
            $q->whereHas('template', function (Builder $query) use ($filters) {
                $query->where('insurance_branch_id', $filters['insurance_branch_id']);
            });
            
            // Se quiser também filtrar por coverages do mesmo branch, 
            // use where com closure para combinar condições
            if (isset($filters['include_coverage_branch']) && $filters['include_coverage_branch']) {
                $q->orWhereHas('coverage', function (Builder $query) use ($filters) {
                    $query->where('insurance_branch_id', $filters['insurance_branch_id']);
                });
            }
        }
        
        // Filtro adicional para evitar duplicações específicas
        if (isset($filters['template_code']) && isset($filters['insurance_branch_id'])) {
            $q->whereHas('template', function (Builder $query) use ($filters) {
                $query->where('insurance_branch_id', $filters['insurance_branch_id'])
                      ->where('code', $filters['template_code']);
            });
        }
        
        $results = $q->get();
        
        // Se houver duplicatas, remove usando unique
        if ($results->isNotEmpty()) {
            $results = $results->unique(function ($item) {
                return $item->template_id . '-' . $item->coverage_id;
            })->values();
        }
        
        return $this->successResponse($results);
    }
    
    public function getByTemplate(int $templateId): array
    {
        $r = CFG_template_coverage::where('template_id', $templateId)
            ->with([
                'template' => function ($query) {
                    $query->with('branch');
                },
                'coverage' => function ($query) {
                    $query->with('branch');
                }
            ])
            ->orderBy('display_order')
            ->get();
        
        return $this->successResponse($r);
    }
    
    public function getByBranch(int $branchId): array
    {
        // Método específico para buscar por branch
        $r = CFG_template_coverage::with([
                'template' => function ($query) use ($branchId) {
                    $query->where('insurance_branch_id', $branchId)
                          ->with('branch');
                },
                'coverage' => function ($query) {
                    $query->with('branch');
                }
            ])
            ->whereHas('template', function (Builder $query) use ($branchId) {
                $query->where('insurance_branch_id', $branchId);
            })
            ->orderBy('display_order')
            ->get();
        
        // Remove possíveis duplicatas
        $r = $r->unique(function ($item) {
            return $item->template_id . '-' . $item->coverage_id;
        })->values();
        
        return $this->successResponse($r);
    }
    
    public function getByBranchAndTemplate(int $branchId, int $templateCode): array
    {
        // Método específico para replicar a query do MySQL Workbench
        $r = CFG_template_coverage::with([
                'template' => function ($query) use ($branchId, $templateCode) {
                    $query->where('insurance_branch_id', $branchId)
                          ->where('code', $templateCode)
                          ->with('branch');
                },
                'coverage' => function ($query) {
                    $query->with('branch');
                }
            ])
            ->whereHas('template', function (Builder $query) use ($branchId, $templateCode) {
                $query->where('insurance_branch_id', $branchId)
                      ->where('code', $templateCode);
            })
            ->orderBy('display_order')
            ->get();
        
        return $this->successResponse($r);
    }
    
    public function getById(int $id): array
    {
        $r = CFG_template_coverage::with([
                'template' => function ($query) {
                    $query->with('branch');
                },
                'coverage' => function ($query) {
                    $query->with('branch');
                }
            ])
            ->find($id);
        
        if (!$r) {
            return $this->errorResponse('Mapeamento não encontrado.', 404);
        }
        
        return $this->successResponse($r);
    }
    
    public function create(array $data): array
    {
        // Verificar se já existe para evitar duplicatas
        $exists = CFG_template_coverage::where('template_id', $data['template_id'])
            ->where('coverage_id', $data['coverage_id'])
            ->exists();
            
        if ($exists) {
            return $this->errorResponse('Este mapeamento já existe.', 409);
        }
        
        $r = CFG_template_coverage::create($data);
        
        // Carregar relações para o retorno
        $r->load(['template.branch', 'coverage.branch']);
        
        return $this->successResponse($r, 'Mapeamento criado.', 201);
    }
    
    public function update(int $id, array $data): array
    {
        $r = CFG_template_coverage::find($id);
        if (!$r) {
            return $this->errorResponse('Mapeamento não encontrado.', 404);
        }
        
        // Verificar duplicata ao atualizar
        if (isset($data['template_id']) || isset($data['coverage_id'])) {
            $templateId = $data['template_id'] ?? $r->template_id;
            $coverageId = $data['coverage_id'] ?? $r->coverage_id;
            
            $exists = CFG_template_coverage::where('template_id', $templateId)
                ->where('coverage_id', $coverageId)
                ->where('id', '!=', $id)
                ->exists();
                
            if ($exists) {
                return $this->errorResponse('Já existe um mapeamento com este template e cobertura.', 409);
            }
        }
        
        $r->update($data);
        
        // Recarregar relações atualizadas
        $r->load(['template.branch', 'coverage.branch']);
        
        return $this->successResponse($r, 'Mapeamento actualizado.');
    }
    
    public function delete(int $id): array
    {
        $r = CFG_template_coverage::find($id);
        if (!$r) {
            return $this->errorResponse('Mapeamento não encontrado.', 404);
        }
        
        $r->delete();
        return $this->successResponse(null, 'Mapeamento eliminado.');
    }
}