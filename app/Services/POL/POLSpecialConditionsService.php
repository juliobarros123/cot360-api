<?php
// ============================================================
// POLPoliciesService.php — App\Services\POL\
// ============================================================
namespace App\Services\POL;

class POLSpecialConditionsService extends \App\Services\ServiceBase
{
    public function getByVersion(int $versionId): array
    {
        return $this->successResponse(
            \App\Models\POL\POL_special_conditions::with('condition')
                ->where('policy_version_id', $versionId)->orderBy('display_order')->get()
        );
    }
    public function create(array $data): array
    {
        return $this->successResponse(\App\Models\POL\POL_special_conditions::create($data), 'Condição adicionada.', 201);
    }
    public function delete(int $id): array
    {
        $r = \App\Models\POL\POL_special_conditions::find($id);
        if (!$r) return $this->errorResponse('Condição não encontrada.', 404);
        $r->delete();
        return $this->successResponse(null, 'Condição removida.');
    }
}
