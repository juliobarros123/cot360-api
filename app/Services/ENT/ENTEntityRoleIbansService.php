<?php

class ENTEntityRoleIbansService extends \App\Services\ServiceBase
{
    public function getByRole(int $roleId): array
    {
        return $this->successResponse(\App\Models\ENT\ENT_entity_role_ibans::where('entity_role_id', $roleId)->get());
    }
    public function create(array $data): array
    {
        if (!empty($data['is_default'])) {
            \App\Models\ENT\ENT_entity_role_ibans::where('entity_role_id', $data['entity_role_id'])->update(['is_default' => false]);
        }
        return $this->successResponse(\App\Models\ENT\ENT_entity_role_ibans::create($data), 'IBAN criado.', 201);
    }
    public function update(int $id, array $data): array
    {
        $r = \App\Models\ENT\ENT_entity_role_ibans::find($id);
        if (!$r) return $this->errorResponse('IBAN não encontrado.', 404);
        if (!empty($data['is_default'])) {
            \App\Models\ENT\ENT_entity_role_ibans::where('entity_role_id', $r->entity_role_id)->update(['is_default' => false]);
        }
        $r->update($data);
        return $this->successResponse($r, 'IBAN actualizado.');
    }
    public function delete(int $id): array
    {
        $r = \App\Models\ENT\ENT_entity_role_ibans::find($id);
        if (!$r) return $this->errorResponse('IBAN não encontrado.', 404);
        $r->delete();
        return $this->successResponse(null, 'IBAN eliminado.');
    }
}
