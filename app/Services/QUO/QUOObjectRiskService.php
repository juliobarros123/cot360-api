<?php

// ============================================================
// QUOObjectRiskService.php
// ============================================================

namespace App\Services\QUO;

use App\Models\QUO\QUO_object_risk;
use App\Services\ServiceBase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QUOObjectRiskService extends ServiceBase
{
    public function getByVersion(int $versionId): array
    {
        $risks = QUO_object_risk::with(['object.vehicle', 'object.person', 'object.property', 'coverage', 'deductibles', 'adjustments'])
            ->where('quotation_version_id', $versionId)->get();
        return $this->successResponse($risks);
    }

    public function getById(int $id): array
    {
        $risk = QUO_object_risk::with(['object.vehicle', 'object.person', 'object.property', 'coverage', 'deductibles', 'adjustments'])
            ->find($id);

        if (!$risk) return $this->errorResponse('Risco não encontrado.', 404);
        return $this->successResponse($risk);
    }

    public function addRisk(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $data['slug'] = Str::slug('or-' . uniqid());
            $risk = QUO_object_risk::create($data);
            return $this->successResponse($risk, 'Risco adicionado.', 201);
        });
    }
    public function updateRisk(int $id, array $data): array
    {
        $risk = QUO_object_risk::find($id);
        if (!$risk) return $this->errorResponse('Risco não encontrado.', 404);
        $risk->update($data);
        return $this->successResponse($risk, 'Risco actualizado.');
    }
    public function deleteRisk(int $id): array
    {
        $risk = QUO_object_risk::find($id);
        if (!$risk) return $this->errorResponse('Risco não encontrado.', 404);
        $risk->delete();
        return $this->successResponse(null, 'Risco eliminado.');
    }
}
