<?php
// ============================================================
// ENTEntityRolesService.php
// ============================================================

use App\Services\ServiceBase;

class ENTEntityRolesService extends ServiceBase
{
    public function getByEntity(int $entityId): array
    {
        $roles = \App\Models\ENT\ENT_entity_roles::with(['roleType', 'roleSubtype', 'ibans', 'branchCommissions'])
            ->where('entity_id', $entityId)->get();
        return $this->successResponse($roles);
    }
    public function create(array $data): array
    {
        // Verifica unicidade (entity_id, role_type_id, role_subtype_id)
        $exists = \App\Models\ENT\ENT_entity_roles::where([
            'entity_id' => $data['entity_id'],
            'role_type_id' => $data['role_type_id'],
            'role_subtype_id' => $data['role_subtype_id'] ?? null,
        ])->exists();
        if ($exists) return $this->errorResponse('Este papel já existe para esta entidade.', 422);
        $record = \App\Models\ENT\ENT_entity_roles::create($data);
        return $this->successResponse($record->load(['roleType', 'roleSubtype']), 'Papel criado.', 201);
    }
    public function update(int $id, array $data): array
    {
        $record = \App\Models\ENT\ENT_entity_roles::find($id);
        if (!$record) return $this->errorResponse('Papel não encontrado.', 404);
        $record->update($data);
        return $this->successResponse($record, 'Papel actualizado.');
    }
    public function delete(int $id): array
    {
        $record = \App\Models\ENT\ENT_entity_roles::find($id);
        if (!$record) return $this->errorResponse('Papel não encontrado.', 404);
        $record->delete();
        return $this->successResponse(null, 'Papel eliminado.');
    }
}
