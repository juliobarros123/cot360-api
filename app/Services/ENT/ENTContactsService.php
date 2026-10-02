<?php
class ENTContactsService extends \App\Services\ServiceBase
{
    public function getByEntity(int $entityId): array
    {
        return $this->successResponse(\App\Models\ENT\ENT_contacts::where('entity_id', $entityId)->get());
    }
    public function create(array $data): array
    {
        if (!empty($data['is_default'])) {
            \App\Models\ENT\ENT_contacts::where('entity_id', $data['entity_id'])->update(['is_default' => false]);
        }
        $data['slug'] = \Illuminate\Support\Str::slug('ct-' . uniqid());
        return $this->successResponse(\App\Models\ENT\ENT_contacts::create($data), 'Contacto criado.', 201);
    }
    public function update(int $id, array $data): array
    {
        $r = \App\Models\ENT\ENT_contacts::find($id);
        if (!$r) return $this->errorResponse('Contacto não encontrado.', 404);
        if (!empty($data['is_default'])) {
            \App\Models\ENT\ENT_contacts::where('entity_id', $r->entity_id)->update(['is_default' => false]);
        }
        $r->update($data);
        return $this->successResponse($r, 'Contacto actualizado.');
    }
    public function delete(int $id): array
    {
        $r = \App\Models\ENT\ENT_contacts::find($id);
        if (!$r) return $this->errorResponse('Contacto não encontrado.', 404);
        $r->delete();
        return $this->successResponse(null, 'Contacto eliminado.');
    }
}
