<?php
// ============================================================
// POLPoliciesService.php — App\Services\POL\
// ============================================================
namespace App\Services\POL;


class POLMovementsService extends \App\Services\ServiceBase
{
    public function getByPolicy(int $policyId): array
    {
        return $this->successResponse(
            \App\Models\POL\POL_movements::with(['details', 'toVersion'])
                ->where('policy_id', $policyId)->orderByDesc('created_at')->get()
        );
    }
    public function getById(int $id): array
    {
        $r = \App\Models\POL\POL_movements::with(['policy', 'fromVersion', 'toVersion', 'details'])->find($id);
        if (!$r) return $this->errorResponse('Movimento não encontrado.', 404);
        return $this->successResponse($r);
    }
    public function approve(int $id): array
    {
        $r = \App\Models\POL\POL_movements::find($id);
        if (!$r) return $this->errorResponse('Movimento não encontrado.', 404);
        if ($r->status !== 'Pending') return $this->errorResponse('Movimento não está pendente.', 422);
        $r->update(['status' => 'Approved', 'approved_by' => auth()->id(), 'approved_at' => now()]);
        return $this->successResponse($r, 'Movimento aprovado.');
    }
    public function reject(int $id): array
    {
        $r = \App\Models\POL\POL_movements::find($id);
        if (!$r) return $this->errorResponse('Movimento não encontrado.', 404);
        $r->update(['status' => 'Rejected']);
        return $this->successResponse($r, 'Movimento rejeitado.');
    }
}
