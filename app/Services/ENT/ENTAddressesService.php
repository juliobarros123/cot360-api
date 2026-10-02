<?php

namespace App\Services\ENT;

class ENTAddressesService extends \App\Services\ServiceBase
{
    public function getByEntity(int $entityId): array
    {
        $r = \App\Models\ENT\ENT_addresses::where('entity_id', $entityId)->get();
        return $this->successResponse($r);
    }
    public function create(array $data): array
    {
        // Se is_default=true, remove default dos outros
        if (!empty($data['is_default'])) {
            \App\Models\ENT\ENT_addresses::where('entity_id', $data['entity_id'])->update(['is_default' => false]);
        }
        $r = \App\Models\ENT\ENT_addresses::create($data);
        return $this->successResponse($r, 'Endereço criado.', 201);
    }
    public function update(int $id, array $data): array
    {
        $r = \App\Models\ENT\ENT_addresses::find($id);
        if (!$r) return $this->errorResponse('Endereço não encontrado.', 404);
        if (!empty($data['is_default'])) {
            \App\Models\ENT\ENT_addresses::where('entity_id', $r->entity_id)->update(['is_default' => false]);
        }
        $r->update($data);
        return $this->successResponse($r, 'Endereço actualizado.');
    }
    public function delete(int $id): array
    {
        $r = \App\Models\ENT\ENT_addresses::find($id);
        if (!$r) return $this->errorResponse('Endereço não encontrado.', 404);
        $r->delete();
        return $this->successResponse(null, 'Endereço eliminado.');
    }
}
