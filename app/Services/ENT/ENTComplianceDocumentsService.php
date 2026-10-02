<?php

class ENTComplianceDocumentsService extends \App\Services\ServiceBase
{
    public function getByEntity(int $entityId): array
    {
        return $this->successResponse(\App\Models\ENT\ENT_compliance_documents::where('entity_id', $entityId)->get());
    }
    public function create(array $data): array
    {
        return $this->successResponse(\App\Models\ENT\ENT_compliance_documents::create($data), 'Documento criado.', 201);
    }
    public function update(int $id, array $data): array
    {
        $r = \App\Models\ENT\ENT_compliance_documents::find($id);
        if (!$r) return $this->errorResponse('Documento não encontrado.', 404);
        $r->update($data);
        return $this->successResponse($r, 'Documento actualizado.');
    }
    public function delete(int $id): array
    {
        $r = \App\Models\ENT\ENT_compliance_documents::find($id);
        if (!$r) return $this->errorResponse('Documento não encontrado.', 404);
        $r->delete();
        return $this->successResponse(null, 'Documento eliminado.');
    }
    public function review(int $id, string $status, ?string $notes = null): array
    {
        $r = \App\Models\ENT\ENT_compliance_documents::find($id);
        if (!$r) return $this->errorResponse('Documento não encontrado.', 404);
        $r->update(['status' => $status, 'reviewed_at' => now(), 'reviewed_by' => auth()->id(), 'review_notes' => $notes]);
        return $this->successResponse($r, 'Documento revisto.');
    }
}
