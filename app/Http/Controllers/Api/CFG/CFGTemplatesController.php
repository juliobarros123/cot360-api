<?php

namespace App\Http\Controllers\Api\CFG;

use App\Http\Controllers\Controller;
use App\Services\CFG\CFGTemplatesService;
use App\Http\Requests\CFG\{StoreCFGTemplatesRequest, UpdateCFGTemplatesRequest};
use Illuminate\Http\{Request, JsonResponse};

class CFGTemplatesController extends Controller
{
    public function __construct(private CFGTemplatesService $service) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'insurance_branch_id', 'active', 'code', 'package', 'object_type', 'coverage_id'
        ]);
        $result = $this->service->getAll($filters);
        return response()->json($result, $result['code']);
    }

    public function store(StoreCFGTemplatesRequest $request): JsonResponse
    {
        $result = $this->service->create($request->validated());
        return response()->json($result, $result['code']);
    }

    public function show(int $id): JsonResponse
    {
        $result = $this->service->getById($id);
        return response()->json($result, $result['code']);
    }

    public function update(UpdateCFGTemplatesRequest $request, int $id): JsonResponse
    {
        $result = $this->service->update($id, $request->validated());
        return response()->json($result, $result['code']);
    }

    public function destroy(int $id): JsonResponse
    {
        $result = $this->service->delete($id);
        return response()->json($result, $result['code']);
    }
    public function syncCoverages(Request $request, int $id): JsonResponse
    {
        $payload = $request->validate([
            'coverage_ids' => 'required|array',
            'coverage_ids.*' => 'integer|exists:cfg_coverages,id',
        ]);
        $result = $this->service->syncCoverages($id, $payload['coverage_ids']);
        return response()->json($result, $result['code']);
    }
}
