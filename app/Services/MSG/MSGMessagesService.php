<?php
// ============================================================
// POLPoliciesService.php — App\Services\POL\
// ============================================================

namespace App\Services\MSG;

class MSGMessagesService extends \App\Services\ServiceBase
{
    public function getAll(array $f = []): array
    {
        $q = \App\Models\MSG\MSG_messages::with('recipient');
        if (isset($f['entity_type'])) $q->where('entity_type', $f['entity_type']);
        if (isset($f['entity_id'])) $q->where('entity_id', $f['entity_id']);
        if (isset($f['status'])) $q->where('status', $f['status']);
        if (isset($f['channel'])) $q->where('channel', $f['channel']);
        return $this->successResponse($q->orderByDesc('created_at')->paginate($f['per_page'] ?? 15));
    }
    public function getById(int $id): array
    {
        $r = \App\Models\MSG\MSG_messages::with('recipient')->find($id);
        if (!$r) return $this->errorResponse('Mensagem não encontrada.', 404);
        return $this->successResponse($r);
    }
    public function create(array $data): array
    {
        $r = \App\Models\MSG\MSG_messages::create($data);
        return $this->successResponse($r, 'Mensagem criada.', 201);
    }
    public function send(int $id): array
    {
        $r = \App\Models\MSG\MSG_messages::find($id);
        if (!$r) return $this->errorResponse('Mensagem não encontrada.', 404);
        // Aqui integraria com o canal de envio (Email, SMS, WhatsApp)
        $r->update(['status' => 'Sent', 'sent_at' => now(), 'sent_by' => auth()->id()]);
        return $this->successResponse($r, 'Mensagem enviada.');
    }
    public function delete(int $id): array
    {
        $r = \App\Models\MSG\MSG_messages::find($id);
        if (!$r) return $this->errorResponse('Mensagem não encontrada.', 404);
        if (!in_array($r->status, ['Draft', 'Failed'])) return $this->errorResponse('Só é possível eliminar mensagens em Draft ou Failed.', 422);
        $r->delete();
        return $this->successResponse(null, 'Mensagem eliminada.');
    }
}
