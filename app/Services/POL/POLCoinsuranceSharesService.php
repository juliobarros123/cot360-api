<?php
// ============================================================
// POLPoliciesService.php — App\Services\POL\
// ============================================================
namespace App\Services\POL;


class POLCoinsuranceSharesService extends \App\Services\ServiceBase
{
    public function getByVersion(int $versionId): array
    {
        return $this->successResponse(
            \App\Models\POL\POL_coinsurance_shares::with('insurer')
                ->where('policy_version_id', $versionId)->get()
        );
    }
    public function create(array $data): array
    {
        return $this->successResponse(\App\Models\POL\POL_coinsurance_shares::create($data), 'Co-seguro criado.', 201);
    }
    public function update(int $id, array $data): array
    {
        $r = \App\Models\POL\POL_coinsurance_shares::find($id);
        if (!$r) return $this->errorResponse('Co-seguro não encontrado.', 404);
        $r->update($data);
        return $this->successResponse($r);
    }
    public function delete(int $id): array
    {
        $r = \App\Models\POL\POL_coinsurance_shares::find($id);
        if (!$r) return $this->errorResponse('Co-seguro não encontrado.', 404);
        $r->delete();
        return $this->successResponse(null, 'Co-seguro eliminado.');
    }
}
