<?php
// ============================================================
// POLPoliciesService.php — App\Services\POL\
// ============================================================
namespace App\Services\POL;

class POLObjectRiskService extends \App\Services\ServiceBase
{
    public function getByVersion(int $versionId): array
    {
        return $this->successResponse(
            \App\Models\POL\POL_object_risk::with(['object.vehicle', 'object.person', 'object.property', 'coverage', 'capitals'])
                ->where('policy_version_id', $versionId)->get()
        );
    }
    public function getById(int $id): array
    {
        $r = \App\Models\POL\POL_object_risk::with(['object', 'coverage', 'capitals', 'movementDetails'])->find($id);
        if (!$r) return $this->errorResponse('Risco não encontrado.', 404);
        return $this->successResponse($r);
    }
    public function updateStatus(int $id, string $status): array
    {
        $r = \App\Models\POL\POL_object_risk::find($id);
        if (!$r) return $this->errorResponse('Risco não encontrado.', 404);
        $r->update(['status' => $status]);
        return $this->successResponse($r, 'Estado do risco actualizado.');
    }
}
