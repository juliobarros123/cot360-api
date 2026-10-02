<?php

namespace App\Services\CFG;

use App\Models\CFG\CFG_rates_rc_options;
use App\Services\ServiceBase;
use Illuminate\Support\Str;

class CFGRatesRcOptionsService extends ServiceBase
{
    public function getAll(array $f = []): array
    {
        $q = CFG_rates_rc_options::orderBy('rc_base');
        if (isset($f['rc_base'])) $q->where('rc_base', $f['rc_base']);
        if (isset($f['rc_fac'])) $q->where('rc_fac', $f['rc_fac']);
        return $this->successResponse($q->get());
    }
    public function getById(int $id): array
    {
        $r = CFG_rates_rc_options::find($id);
        if (!$r) return $this->errorResponse('RC Option não encontrada.', 404);
        return $this->successResponse($r);
    }
    public function create(array $data): array
    {
        $data['slug'] = Str::slug('rc-' . uniqid());
        return $this->successResponse(CFG_rates_rc_options::create($data), 'RC Option criada.', 201);
    }
    public function update(int $id, array $data): array
    {
        $r = CFG_rates_rc_options::find($id);
        if (!$r) return $this->errorResponse('RC Option não encontrada.', 404);
        $r->update($data);
        return $this->successResponse($r, 'RC Option actualizada.');
    }
    public function delete(int $id): array
    {
        $r = CFG_rates_rc_options::find($id);
        if (!$r) return $this->errorResponse('RC Option não encontrada.', 404);
        $r->delete();
        return $this->successResponse(null, 'RC Option eliminada.');
    }
}
