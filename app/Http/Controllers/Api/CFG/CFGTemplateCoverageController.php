<?php

namespace App\Http\Controllers\Api\CFG;

use App\Http\Controllers\Controller;
use App\Services\CFG\CFGTemplateCoverageService;
use App\Http\Requests\CFG\{StoreCFGTemplateCoverageRequest, UpdateCFGTemplateCoverageRequest};
use Illuminate\Http\{Request, JsonResponse};

class CFGTemplateCoverageController extends Controller
{
    public function __construct(private CFGTemplateCoverageService $service) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['template_id', 'coverage_id', 'insurance_branch_id']);
        $result = $this->service->getAll($filters);
        return response()->json($result, $result['code']);
    }

    public function store(StoreCFGTemplateCoverageRequest $request): JsonResponse
    {
        $result = $this->service->create($request->validated());
        return response()->json($result, $result['code']);
    }

    public function show(int $id): JsonResponse
    {
        $result = $this->service->getById($id);
        return response()->json($result, $result['code']);
    }

    public function update(UpdateCFGTemplateCoverageRequest $request, int $id): JsonResponse
    {
        $result = $this->service->update($id, $request->validated());
        return response()->json($result, $result['code']);
    }

    public function destroy(int $id): JsonResponse
    {
        $result = $this->service->delete($id);
        return response()->json($result, $result['code']);
    }
    
    // Método adicional específico para buscar por branch
    public function byBranch(int $branchId): JsonResponse
    {
        $result = $this->service->getByBranch($branchId);
        return response()->json($result, $result['code']);
    }
}