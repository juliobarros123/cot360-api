<?php

namespace App\Services\QUO;

class QUOSpecialConditionsService extends \App\Services\ServiceBase
{
    public function getByVersion(int $versionId): array
    {
        return $this->successResponse(
            \App\Models\QUO\QUO_special_conditions::with('condition')
                ->where('quotation_version_id', $versionId)
                ->orderBy('display_order')->get()
        );
    }
    public function getById(int $id): array
    {
        $r = \App\Models\QUO\QUO_special_conditions::with('condition')->find($id);
        if (!$r) return $this->errorResponse('Condição não encontrada.', 404);
        return $this->successResponse($r);
    }
    public function create(array $data): array
    {
        return $this->successResponse(\App\Models\QUO\QUO_special_conditions::create($data), 'Condição adicionada.', 201);
    }
    public function update(int $id, array $data): array
    {
        $r = \App\Models\QUO\QUO_special_conditions::find($id);
        if (!$r) return $this->errorResponse('Condição não encontrada.', 404);
        $r->update($data);
        return $this->successResponse($r, 'Condição actualizada.');
    }
    public function delete(int $id): array
    {
        $r = \App\Models\QUO\QUO_special_conditions::find($id);
        if (!$r) return $this->errorResponse('Condição não encontrada.', 404);
        $r->delete();
        return $this->successResponse(null, 'Condição removida.');
    }
}
