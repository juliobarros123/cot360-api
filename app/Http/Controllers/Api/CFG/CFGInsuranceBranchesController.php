<?php
// ============================================================
// CONTROLLERS — PADRÃO BASE
// Cada controller segue este padrão exacto.
// ============================================================
// App\Http\Controllers\Api\CFG\CFGInsuranceBranchesController

namespace App\Http\Controllers\Api\CFG;

use App\Http\Controllers\Controller;
use App\Services\CFG\CFGInsuranceBranchesService;
use App\Http\Requests\CFG\{StoreCFGInsuranceBranchesRequest, UpdateCFGInsuranceBranchesRequest};
use Illuminate\Http\{Request, JsonResponse};

class CFGInsuranceBranchesController extends Controller
{
    public function __construct(private CFGInsuranceBranchesService $service) {}

    public function index(Request $request): JsonResponse
    {
        $result = $this->service->getAll($request->only(['active']));
        return response()->json($result, $result['code']);
    }
    public function store(StoreCFGInsuranceBranchesRequest $request): JsonResponse
    {
        $result = $this->service->create($request->validated());
        return response()->json($result, $result['code']);
    }
    public function show(int $id): JsonResponse
    {
        $result = $this->service->getById($id);
        return response()->json($result, $result['code']);
    }
    public function update(UpdateCFGInsuranceBranchesRequest $request, int $id): JsonResponse
    {
        $result = $this->service->update($id, $request->validated());
        return response()->json($result, $result['code']);
    }
    public function destroy(int $id): JsonResponse
    {
        $result = $this->service->delete($id);
        return response()->json($result, $result['code']);
    }
    public function toggle(int $id): JsonResponse
    {
        $result = $this->service->toggle($id);
        return response()->json($result, $result['code']);
    }
}
