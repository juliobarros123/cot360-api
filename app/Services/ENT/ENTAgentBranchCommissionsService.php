<?php

class ENTAgentBranchCommissionsService extends \App\Services\ServiceBase
{
    public function getByRole(int $roleId): array
    {
        return $this->successResponse(
            \App\Models\ENT\ENT_agent_branch_commissions::with('branch')
                ->where('entity_role_id', $roleId)->get()
        );
    }
    public function getActive(int $roleId, int $branchId): array
    {
        $r = \App\Models\ENT\ENT_agent_branch_commissions::where('entity_role_id', $roleId)
            ->where('insurance_branch_id', $branchId)
            ->where('valid_from', '<=', now())
            ->where(fn($q) => $q->whereNull('valid_until')->orWhere('valid_until', '>=', now()))
            ->orderByDesc('valid_from')->first();
        if (!$r) return $this->errorResponse('Comissão não encontrada.', 404);
        return $this->successResponse($r);
    }
    public function create(array $data): array
    {
        return $this->successResponse(\App\Models\ENT\ENT_agent_branch_commissions::create($data), 'Comissão criada.', 201);
    }
    public function update(int $id, array $data): array
    {
        $r = \App\Models\ENT\ENT_agent_branch_commissions::find($id);
        if (!$r) return $this->errorResponse('Comissão não encontrada.', 404);
        $r->update($data);
        return $this->successResponse($r, 'Comissão actualizada.');
    }
    public function delete(int $id): array
    {
        $r = \App\Models\ENT\ENT_agent_branch_commissions::find($id);
        if (!$r) return $this->errorResponse('Comissão não encontrada.', 404);
        $r->delete();
        return $this->successResponse(null, 'Comissão eliminada.');
    }
}
