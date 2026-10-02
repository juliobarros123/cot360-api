<?php

// ============================================================
// QUOObjectRiskService.php
// ============================================================

namespace App\Services\QUO;

use App\Models\QUO\QUO_insurance_adjustments;

class QUOInsuranceAdjustmentsService extends \App\Services\ServiceBase
{
    public function getByObjectRisk(int $objectRiskId): array
    {
        return $this->successResponse(QUO_insurance_adjustments::where('object_risk_id', $objectRiskId)->get());
    }
    public function getById(int $id): array
    {
        $r = QUO_insurance_adjustments::find($id);
        if (!$r) return $this->errorResponse('Ajuste não encontrado.', 404);
        return $this->successResponse($r);
    }
    public function create(array $data): array
    {
        return $this->successResponse(QUO_insurance_adjustments::create($data), 'Ajuste criado.', 201);
    }
    public function update(int $id, array $data): array
    {
        $r = QUO_insurance_adjustments::find($id);
        if (!$r) return $this->errorResponse('Ajuste não encontrado.', 404);
        $r->update($data);
        return $this->successResponse($r);
    }
    public function delete(int $id): array
    {
        $r = QUO_insurance_adjustments::find($id);
        if (!$r) return $this->errorResponse('Ajuste não encontrado.', 404);
        $r->delete();
        return $this->successResponse(null, 'Ajuste eliminado.');
    }
}
