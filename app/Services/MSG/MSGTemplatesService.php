<?php
// ============================================================
// POLPoliciesService.php — App\Services\POL\
// ============================================================

namespace App\Services\MSG;


class MSGTemplatesService extends \App\Services\ServiceBase
{
    public function getAll(array $f = []): array
    {
        $q = \App\Models\MSG\MSG_templates::query();
        if (isset($f['message_type'])) $q->where('message_type', $f['message_type']);
        if (isset($f['channel'])) $q->where('channel', $f['channel']);
        if (isset($f['active'])) $q->where('active', $f['active']);
        return $this->successResponse($q->orderBy('name')->get());
    }
    public function getById(int $id): array
    {
        $r = \App\Models\MSG\MSG_templates::find($id);
        if (!$r) return $this->errorResponse('Template não encontrado.', 404);
        return $this->successResponse($r);
    }
    public function create(array $data): array
    {
        return $this->successResponse(\App\Models\MSG\MSG_templates::create($data), 'Template criado.', 201);
    }
    public function update(int $id, array $data): array
    {
        $r = \App\Models\MSG\MSG_templates::find($id);
        if (!$r) return $this->errorResponse('Template não encontrado.', 404);
        $r->update($data);
        return $this->successResponse($r, 'Template actualizado.');
    }
    public function delete(int $id): array
    {
        $r = \App\Models\MSG\MSG_templates::find($id);
        if (!$r) return $this->errorResponse('Template não encontrado.', 404);
        $r->delete();
        return $this->successResponse(null, 'Template eliminado.');
    }
}
