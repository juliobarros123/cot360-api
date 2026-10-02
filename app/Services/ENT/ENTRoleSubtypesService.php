<?php

class ENTRoleSubtypesService extends \App\Services\ServiceBase
{
    public function getAll(array $f = []): array
    {
        $q = \App\Models\ENT\ENT_role_subtypes::with('roleType');
        if (isset($f['role_type_id'])) $q->where('role_type_id', $f['role_type_id']);
        return $this->successResponse($q->get());
    }
    public function create(array $data): array
    {
        return $this->successResponse(\App\Models\ENT\ENT_role_subtypes::create($data), 'Subtipo criado.', 201);
    }
    public function update(int $id, array $data): array
    {
        $r = \App\Models\ENT\ENT_role_subtypes::find($id);
        if (!$r) return $this->errorResponse('Subtipo não encontrado.', 404);
        $r->update($data);
        return $this->successResponse($r, 'Subtipo actualizado.');
    }
}
