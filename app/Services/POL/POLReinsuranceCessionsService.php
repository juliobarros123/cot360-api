<?php
// ============================================================
// POLPoliciesService.php — App\Services\POL\
// ============================================================
namespace App\Services\POL;

class POLReinsuranceCessionsService extends \App\Services\ServiceBase
{
    public function getByVersion(int $versionId): array
    {
        return $this->successResponse(
            \App\Models\POL\POL_reinsurance_cessions::with('reinsurer', 'objectRisk')
                ->where('policy_version_id', $versionId)->get()
        );
    }
    public function create(array $data): array
    {
        return $this->successResponse(\App\Models\POL\POL_reinsurance_cessions::create($data), 'Cessão criada.', 201);
    }
    public function update(int $id, array $data): array
    {
        $r = \App\Models\POL\POL_reinsurance_cessions::find($id);
        if (!$r) return $this->errorResponse('Cessão não encontrada.', 404);
        $r->update($data);
        return $this->successResponse($r);
    }
    public function delete(int $id): array
    {
        $r = \App\Models\POL\POL_reinsurance_cessions::find($id);
        if (!$r) return $this->errorResponse('Cessão não encontrada.', 404);
        $r->delete();
        return $this->successResponse(null, 'Cessão eliminada.');
    }
}
