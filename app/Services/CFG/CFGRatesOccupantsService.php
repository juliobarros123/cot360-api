<?php
// ---- CFGRatesOccupantsService ----

namespace App\Services\CFG;

use App\Models\CFG\CFG_rates_occupants;
use App\Services\ServiceBase;
use Illuminate\Support\Str;

class CFGRatesOccupantsService extends ServiceBase
{
    public function getAll(array $f = []): array
    {
        $q = CFG_rates_occupants::orderBy('seat_min');
        if (isset($f['package'])) $q->where('package', $f['package']);
        if (isset($f['seats'])) {
            $q->where('seat_min', '<=', $f['seats'])->where('seat_max', '>=', $f['seats']);
        }
        return $this->successResponse($q->get());
    }
    public function getById(int $id): array
    {
       
        $r = CFG_rates_occupants::find($id);
        if (!$r) return $this->errorResponse('Tarifa ocupantes não encontrada.', 404);
        return $this->successResponse($r);
    }
    public function findBySeats(int $seats, string $package): array
    {
        $r = CFG_rates_occupants::where('package', $package)
            ->where('seat_min', '<=', $seats)->where('seat_max', '>=', $seats)->first();
        if (!$r) return $this->errorResponse('Tarifa não encontrada para estes assentos.', 404);
        return $this->successResponse($r);
    }
    public function create(array $data): array
    {
        $data['slug'] = Str::slug('occ-' . uniqid());
        return $this->successResponse(CFG_rates_occupants::create($data), 'Tarifa criada.', 201);
    }
    public function update(int $id, array $data): array
    {
        $r = CFG_rates_occupants::find($id);
        if (!$r) return $this->errorResponse('Tarifa não encontrada.', 404);
        $r->update($data);
        return $this->successResponse($r, 'Tarifa actualizada.');
    }
    public function delete(int $id): array
    {
        $r = CFG_rates_occupants::find($id);
        if (!$r) return $this->errorResponse('Tarifa não encontrada.', 404);
        $r->delete();
        return $this->successResponse(null, 'Tarifa eliminada.');
    }
}
