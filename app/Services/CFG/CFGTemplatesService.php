<?php
// ============================================================
// CFGTemplatesService.php
// ============================================================

namespace App\Services\CFG;

use App\Models\CFG\CFG_templates;
use App\Services\ServiceBase;
use Illuminate\Support\Str;

class CFGTemplatesService extends ServiceBase
{
    public function getAll(array $filters = []): array
    {
        $query = CFG_templates::with('branch');
        if (isset($filters['insurance_branch_id'])) $query->where('insurance_branch_id', $filters['insurance_branch_id']);
        if (isset($filters['active'])) $query->where('active', $filters['active']);
        if (isset($filters['code'])) $query->where('code', $filters['code']);
        if (isset($filters['package'])) $query->where('package', $filters['package']);
        if (isset($filters['object_type'])) $query->where('object_type', $filters['object_type']);
        if (isset($filters['coverage_id'])) {
            $query->whereHas('coverages', function ($q) use ($filters) {
                $q->where('cfg_coverages.id', $filters['coverage_id']);
            });
        }
        $query
            ->orderByRaw('CASE WHEN sequence IS NULL THEN 1 ELSE 0 END')
            ->orderBy('sequence')
            ->orderBy('code');

        return $this->successResponse($query->get());
    }
    public function getById(int $id): array
    {
        $record = CFG_templates::with(['branch', 'coverages'])->find($id);
        if (!$record) return $this->errorResponse('Template não encontrado.', 404);
        return $this->successResponse($record);
    }
    public function create(array $data): array
    {
        $data['slug'] = Str::slug($data['name'] . '-' . uniqid());
        $record = CFG_templates::create($data);
        return $this->successResponse($record, 'Template criado.', 201);
    }
    public function update(int $id, array $data): array
    {
        $record = CFG_templates::find($id);
        if (!$record) return $this->errorResponse('Template não encontrado.', 404);
        $record->update($data);
        return $this->successResponse($record, 'Template actualizado.');
    }
    public function delete(int $id): array
    {
        $record = CFG_templates::find($id);
        if (!$record) return $this->errorResponse('Template não encontrado.', 404);
        $record->delete();
        return $this->successResponse(null, 'Template eliminado.');
    }
    public function syncCoverages(int $id, array $coverageIds): array
    {
        $record = CFG_templates::find($id);
        if (!$record) return $this->errorResponse('Template não encontrado.', 404);
        $record->coverages()->sync($coverageIds);
        return $this->successResponse($record->load('coverages'), 'Coberturas sincronizadas.');
    }
}
