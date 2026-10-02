<?php

namespace App\Http\Controllers\Api\CFG;

use App\Http\Controllers\Controller;
use App\Services\CFG\CFGRatesCaeService;
use App\Http\Requests\CFG\{StoreCFGRatesCaeRequest, UpdateCFGRatesCaeRequest};
use Illuminate\Http\{Request, JsonResponse};

class CFGRatesCaeController extends Controller
{
    public function __construct(private CFGRatesCaeService $service) {}

    public function index(Request $request): JsonResponse
    {
        $result = $this->service->getAll($request->only(['active', 'search', 'cae']));
        return response()->json($result, $result['code']);
    }

    public function store(StoreCFGRatesCaeRequest $request): JsonResponse
    {
        $result = $this->service->create($request->validated());
        return response()->json($result, $result['code']);
    }

    public function show(int $id): JsonResponse
    {
        $result = $this->service->getById($id);
        return response()->json($result, $result['code']);
    }

    public function update(UpdateCFGRatesCaeRequest $request, int $id): JsonResponse
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
