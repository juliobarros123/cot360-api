<?php

namespace App\Http\Controllers\Api\CFG;

use App\Http\Controllers\Controller;
use App\Services\CFG\CFGRatesOccupantsService;
use App\Http\Requests\CFG\{StoreCFGRatesOccupantsRequest, UpdateCFGRatesOccupantsRequest};
use Illuminate\Http\{Request, JsonResponse};

class CFGRatesOccupantsController extends Controller
{
    public function __construct(private CFGRatesOccupantsService $service) {}

    public function index(Request $request): JsonResponse
    {
        $result = $this->service->getAll($request->only(['package', 'seats']));
        return response()->json($result, $result['code']);
    }

    public function store(StoreCFGRatesOccupantsRequest $request): JsonResponse
    {
        $result = $this->service->create($request->validated());
        return response()->json($result, $result['code']);
    }

    public function show(int $id): JsonResponse
    {
        $result = $this->service->getById($id);
        return response()->json($result, $result['code']);
    }

    public function update(UpdateCFGRatesOccupantsRequest $request, int $id): JsonResponse
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
