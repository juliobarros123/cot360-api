<?php

namespace App\Http\Controllers\Api\CFG;

use App\Http\Controllers\Controller;
use App\Services\CFG\CFGCoveragesService;
use App\Http\Requests\CFG\{StoreCFGCoveragesRequest, UpdateCFGCoveragesRequest};
use Illuminate\Http\{Request, JsonResponse};

class CFGCoveragesController extends Controller
{
    public function __construct(private CFGCoveragesService $service) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'insurance_branch_id', 'group_id', 'template_id', 'active', 'search'
        ]);
        $result = $this->service->getAll($filters);
        return response()->json($result, $result['code']);
    }

    public function store(StoreCFGCoveragesRequest $request): JsonResponse
    {
        $result = $this->service->create($request->validated());
        return response()->json($result, $result['code']);
    }

    public function show(int $id): JsonResponse
    {
        $result = $this->service->getById($id);
        return response()->json($result, $result['code']);
    }

    public function update(UpdateCFGCoveragesRequest $request, int $id): JsonResponse
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
