<?php

class ENTRoleTypesService extends \App\Services\ServiceBase
{
    public function getAll(array $f = []): array
    {
        $q = \App\Models\ENT\ENT_role_types::with('subtypes');
        if (isset($f['active'])) $q->where('active', $f['active']);
        return $this->successResponse($q->orderBy('name')->get());
    }
    public function getById(int $id): array
    {
        $r = \App\Models\ENT\ENT_role_types::with('subtypes')->find($id);
        if (!$r) return $this->errorResponse('Tipo de papel não encontrado.', 404);
        return $this->successResponse($r);
    }
    public function create(array $data): array
    {
        return $this->successResponse(\App\Models\ENT\ENT_role_types::create($data), 'Tipo criado.', 201);
    }
    public function update(int $id, array $data): array
    {
        $r = \App\Models\ENT\ENT_role_types::find($id);
        if (!$r) return $this->errorResponse('Tipo não encontrado.', 404);
        $r->update($data);
        return $this->successResponse($r, 'Tipo actualizado.');
    }
}
