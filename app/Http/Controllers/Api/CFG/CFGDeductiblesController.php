<?php

namespace App\Http\Controllers\Api\CFG;

use App\Http\Controllers\Controller;
use App\Services\CFG\CFGDeductiblesService;
use App\Http\Requests\CFG\{StoreCFGDeductiblesRequest, UpdateCFGDeductiblesRequest};
use Illuminate\Http\{Request, JsonResponse};

class CFGDeductiblesController extends Controller
{
    public function __construct(private CFGDeductiblesService $service) {}

    public function index(Request $request): JsonResponse
    {
        $result = $this->service->getAll($request->only(['coverage_id', 'deduct_type', 'deduct_object']));
        return response()->json($result, $result['code']);
    }

    public function store(StoreCFGDeductiblesRequest $request): JsonResponse
    {
        $result = $this->service->create($request->validated());
        return response()->json($result, $result['code']);
    }

    public function show(int $id): JsonResponse
    {
        $result = $this->service->getById($id);
        return response()->json($result, $result['code']);
    }

    public function update(UpdateCFGDeductiblesRequest $request, int $id): JsonResponse
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
