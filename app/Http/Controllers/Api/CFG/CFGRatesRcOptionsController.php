<?php

namespace App\Http\Controllers\Api\CFG;

use App\Http\Controllers\Controller;
use App\Services\CFG\CFGRatesRcOptionsService;
use App\Http\Requests\CFG\{StoreCFGRatesRcOptionsRequest, UpdateCFGRatesRcOptionsRequest};
use Illuminate\Http\{Request, JsonResponse};

class CFGRatesRcOptionsController extends Controller
{
    public function __construct(private CFGRatesRcOptionsService $service) {}

    public function index(Request $request): JsonResponse
    {
        $result = $this->service->getAll($request->only(['rc_base', 'rc_fac']));
        return response()->json($result, $result['code']);
    }

    public function store(StoreCFGRatesRcOptionsRequest $request): JsonResponse
    {
        $result = $this->service->create($request->validated());
        return response()->json($result, $result['code']);
    }

    public function show(int $id): JsonResponse
    {
        $result = $this->service->getById($id);
        return response()->json($result, $result['code']);
    }

    public function update(UpdateCFGRatesRcOptionsRequest $request, int $id): JsonResponse
    {
        $result = $this->service->update($id, $request->validated());
        return response()->json($result, $result['code']);
    }

    public function destroy(int $id): JsonResponse
    {
        $result = $this->service->delete($id);
        return response()->json($result, $result['code']);
    }
}
