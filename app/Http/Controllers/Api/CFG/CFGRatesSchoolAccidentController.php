<?php

namespace App\Http\Controllers\Api\CFG;

use App\Http\Controllers\Controller;
use App\Services\CFG\CFGRatesSchoolAccidentService;
use App\Http\Requests\CFG\{StoreCFGRatesSchoolAccidentRequest, UpdateCFGRatesSchoolAccidentRequest};
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CFGRatesSchoolAccidentController extends Controller
{
    public function __construct(private CFGRatesSchoolAccidentService $service) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'insurance_branch_id', 'coverage_id', 'type', 'package', 'active'
        ]);
        $result = $this->service->getAll($filters);
        return response()->json($result, $result['code']);
    }

    public function store(StoreCFGRatesSchoolAccidentRequest $request): JsonResponse
    {
        $result = $this->service->create($request->validated());
        return response()->json($result, $result['code']);
    }

    public function show(int $id): JsonResponse
    {
        $result = $this->service->getById($id);
        return response()->json($result, $result['code']);
    }

    public function update(UpdateCFGRatesSchoolAccidentRequest $request, int $id): JsonResponse
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