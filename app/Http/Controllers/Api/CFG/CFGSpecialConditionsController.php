<?php

namespace App\Http\Controllers\Api\CFG;

use App\Http\Controllers\Controller;
use App\Services\CFG\CFGSpecialConditionsService;
use App\Http\Requests\CFG\{StoreCFGSpecialConditionsRequest, UpdateCFGSpecialConditionsRequest};
use Illuminate\Http\{Request, JsonResponse};

class CFGSpecialConditionsController extends Controller
{
    public function __construct(private CFGSpecialConditionsService $service) {}

    public function index(Request $request): JsonResponse
    {
        $result = $this->service->getAll($request->only([
            'insurance_branch_id', 'condition_type', 'active', 'search'
        ]));
        return response()->json($result, $result['code']);
    }

    public function store(StoreCFGSpecialConditionsRequest $request): JsonResponse
    {
        $result = $this->service->create($request->validated());
        return response()->json($result, $result['code']);
    }

    public function show(int $id): JsonResponse
    {
        $result = $this->service->getById($id);
        return response()->json($result, $result['code']);
    }

    public function update(UpdateCFGSpecialConditionsRequest $request, int $id): JsonResponse
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
