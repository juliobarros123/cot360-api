<?php
// ============================================================
// CFGInsuranceBranchesService.php — App\Services\CFG\
// ============================================================
namespace App\Services\CFG;

use App\Services\ServiceBase;
use App\Models\CFG\CFG_insurance_branches;
use Illuminate\Support\Str;

class CFGInsuranceBranchesService extends ServiceBase
{
    public function getAll(array $filters = []): array
    {
        $query = CFG_insurance_branches::query();
        if (isset($filters['active'])) $query->where('active', $filters['active']);
        return $this->successResponse($query->orderBy('name')->get());
    }
    public function getById(int $id): array
    {
        $record = CFG_insurance_branches::with(['templates', 'coverages', 'specialConditions'])->find($id);
        if (!$record) return $this->errorResponse('Ramo não encontrado.', 404);
        return $this->successResponse($record);
    }
    public function create(array $data): array
    {
        $data['slug'] = Str::slug($data['code'] . '-' . uniqid());
        $record = CFG_insurance_branches::create($data);
        return $this->successResponse($record, 'Ramo criado com sucesso.', 201);
    }
    public function update(int $id, array $data): array
    {
        $record = CFG_insurance_branches::find($id);
        if (!$record) return $this->errorResponse('Ramo não encontrado.', 404);
        $record->update($data);
        return $this->successResponse($record, 'Ramo actualizado.');
    }
    public function delete(int $id): array
    {
        $record = CFG_insurance_branches::find($id);
        if (!$record) return $this->errorResponse('Ramo não encontrado.', 404);
        $record->delete();
        return $this->successResponse(null, 'Ramo eliminado.');
    }
    public function toggle(int $id): array
    {
        $record = CFG_insurance_branches::find($id);
        if (!$record) return $this->errorResponse('Ramo não encontrado.', 404);
        $record->update(['active' => !$record->active]);
        return $this->successResponse($record, 'Estado actualizado.');
    }
}
