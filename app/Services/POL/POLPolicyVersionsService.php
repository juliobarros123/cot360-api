<?php

namespace App\Services\POL;

use App\Models\POL\POL_policy_versions;
use App\Services\ServiceBase;

class POLPolicyVersionsService extends ServiceBase
{
    public function getAll(array $filters = []): array
    {
        $query = POL_policy_versions::query()->with([
            'policy',
            'objectRisks.object.vehicle',
            'objectRisks.object.person',
            'objectRisks.object.property',
            'objectRisks.coverage',
            'specialConditions.condition',
            'coinsuranceShares.insurer',
            'reinsuranceCessions.reinsurer',
        ]);

        if (!empty($filters['policy_id'])) {
            $query->where('policy_id', (int) $filters['policy_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $this->successResponse(
            $query->orderByDesc('policy_id')->orderByDesc('version')->get()
        );
    }

    public function getById(int $id): array
    {
        $version = POL_policy_versions::with([
            'policy.customer',
            'policy.branch',
            'policy.agent',
            'objectRisks.object.vehicle',
            'objectRisks.object.person',
            'objectRisks.object.property',
            'objectRisks.coverage',
            'specialConditions.condition',
            'coinsuranceShares.insurer',
            'reinsuranceCessions.reinsurer',
            'reinsuranceCessions.objectRisk',
            'movementsTo.details',
        ])->find($id);

        if (!$version) {
            return $this->errorResponse('Versão de apólice não encontrada.', 404);
        }

        return $this->successResponse($version);
    }
}

