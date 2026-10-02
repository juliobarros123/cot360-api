<?php
// ============================================================
// CFGCoveragesService.php
// ============================================================

namespace App\Services\CFG;

use App\Models\CFG\CFG_coverages;
use App\Services\ServiceBase;
use Illuminate\Support\Str;

class CFGCoveragesService extends ServiceBase
{
    public function getAll(array $filters = []): array
    {
        $query = CFG_coverages::with('branch', 'parent');
        if (isset($filters['insurance_branch_id'])) $query->where('insurance_branch_id', $filters['insurance_branch_id']);
        if (isset($filters['group_id'])) $query->where('group_id', $filters['group_id']);
        if (isset($filters['active'])) $query->where('active', $filters['active']);
        if (isset($filters['template_id'])) {
            $query->whereHas('templates', function ($q) use ($filters) {
                $q->where('cfg_templates.id', $filters['template_id']);
            });
        }
        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('short_name', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('name', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('type_cov', 'like', '%' . $filters['search'] . '%');
            });
        }
        return $this->successResponse($query->orderBy('seq')->get());
    }
    public function getById(int $id): array
    {
        $record = CFG_coverages::with(['branch', 'parent', 'children', 'deductibles'])->find($id);
        if (!$record) return $this->errorResponse('Cobertura não encontrada.', 404);
        return $this->successResponse($record);
    }
    public function create(array $data): array
    {
        $data['slug'] = Str::slug(($data['short_name'] ?? $data['type_cov']) . '-' . uniqid());
        $record = CFG_coverages::create($data);
        return $this->successResponse($record, 'Cobertura criada.', 201);
    }
    public function update(int $id, array $data): array
    {
        $record = CFG_coverages::find($id);
        if (!$record) return $this->errorResponse('Cobertura não encontrada.', 404);
        $record->update($data);
        return $this->successResponse($record, 'Cobertura actualizada.');
    }
    public function delete(int $id): array
    {
        $record = CFG_coverages::find($id);
        if (!$record) return $this->errorResponse('Cobertura não encontrada.', 404);
        $record->delete();
        return $this->successResponse(null, 'Cobertura eliminada.');
    }
}
