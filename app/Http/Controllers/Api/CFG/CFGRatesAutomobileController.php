<?php

namespace App\Http\Controllers\Api\CFG;

use App\Http\Controllers\Controller;
use App\Services\CFG\CFGRatesAutomobileService;
use App\Http\Requests\CFG\{StoreCFGRatesAutomobileRequest, UpdateCFGRatesAutomobileRequest};
use Illuminate\Http\{Request, JsonResponse};

class CFGRatesAutomobileController extends Controller
{
    public function __construct(private CFGRatesAutomobileService $service) {}

    public function index(Request $request): JsonResponse
    {
        $result = $this->service->getAll($request->only([
            'insurance_branch_id', 'vehicle_type', 'category', 'code', 'active', 'search', 'engine', 'seats', 'weight'
        ]));
        return response()->json($result, $result['code']);
    }

    public function store(StoreCFGRatesAutomobileRequest $request): JsonResponse
    {
        $result = $this->service->create($request->validated());
        return response()->json($result, $result['code']);
    }

    public function show(int $id): JsonResponse
    {
        $result = $this->service->getById($id);
        return response()->json($result, $result['code']);
    }

    public function update(UpdateCFGRatesAutomobileRequest $request, int $id): JsonResponse
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
