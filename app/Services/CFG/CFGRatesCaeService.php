<?php

namespace App\Services\CFG;

use App\Models\CFG\CFG_rates_cae;
use App\Services\ServiceBase;
use Illuminate\Support\Str;

class CFGRatesCaeService extends ServiceBase
{
    public function getAll(array $f = []): array
    {
        $q = CFG_rates_cae::query();
        if (isset($f['active'])) $q->where('active', $f['active']);
        if (isset($f['search'])) $q->where('cae', 'like', '%' . $f['search'] . '%');
        if (isset($f['cae'])) $q->where('cae', $f['cae']);
        return $this->successResponse($q->get());
    }
    public function getById(int $id): array
    {
        $r = CFG_rates_cae::find($id);
        if (!$r) return $this->errorResponse('Tarifa CAE não encontrada.', 404);
        return $this->successResponse($r);
    }
    public function findByCae(string $cae): array
    {
        $r = CFG_rates_cae::where('cae', $cae)->where('active', true)->first();
        if (!$r) return $this->errorResponse("CAE '$cae' não encontrado.", 404);
        return $this->successResponse($r);
    }
    public function create(array $data): array
    {
        $data['slug'] = Str::slug('cae-' . $data['cae'] . '-' . uniqid());
        return $this->successResponse(CFG_rates_cae::create($data), 'CAE criado.', 201);
    }
    public function update(int $id, array $data): array
    {
        $r = CFG_rates_cae::find($id);
        if (!$r) return $this->errorResponse('CAE não encontrado.', 404);
        $r->update($data);
        return $this->successResponse($r, 'CAE actualizado.');
    }
    public function delete(int $id): array
    {
        $r = CFG_rates_cae::find($id);
        if (!$r) return $this->errorResponse('CAE não encontrado.', 404);
        $r->delete();
        return $this->successResponse(null, 'CAE eliminado.');
    }
}
