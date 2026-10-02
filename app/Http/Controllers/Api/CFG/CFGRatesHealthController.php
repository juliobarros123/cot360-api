<?php

namespace App\Http\Controllers\Api\CFG;

use App\Http\Controllers\Controller;
use App\Services\CFG\CFGRatesHealthService;
use App\Http\Requests\CFG\{StoreCFGRatesHealthRequest, UpdateCFGRatesHealthRequest};
use Illuminate\Http\{Request, JsonResponse};

class CFGRatesHealthController extends Controller
{
    public function __construct(private CFGRatesHealthService $service) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'insurance_branch_id', 'coverage_id', 'gender', 'type', 'package', 'active', 'age','currency'
        ]);
        $result = $this->service->getAll($filters);
        return response()->json($result, $result['code']);
    }

    public function store(StoreCFGRatesHealthRequest $request): JsonResponse
    {
        $result = $this->service->create($request->validated());
        return response()->json($result, $result['code']);
    }

    public function show(int $id): JsonResponse
    {
        $result = $this->service->getById($id);
        return response()->json($result, $result['code']);
    }

    public function update(UpdateCFGRatesHealthRequest $request, int $id): JsonResponse
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
