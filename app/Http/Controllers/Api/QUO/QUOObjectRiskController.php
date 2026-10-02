<?php

namespace App\Http\Controllers\Api\QUO;

use App\Http\Controllers\Controller;
use App\Services\QUO\QUOObjectRiskService;
use Illuminate\Http\{JsonResponse, Request};

class QUOObjectRiskController extends Controller
{
    public function __construct(private QUOObjectRiskService $service) {}

    public function index(Request $request): JsonResponse
    {
        $versionId = (int) $request->query('quotation_version_id');
        if (!$versionId) {
            return response()->json([
                'success' => false,
                'message' => 'Parâmetro quotation_version_id é obrigatório.',
                'errors' => ['quotation_version_id' => ['O parâmetro quotation_version_id é obrigatório.']],
            ], 422);
        }

        $result = $this->service->getByVersion($versionId);
        return response()->json($result, $result['code']);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'quotation_version_id' => 'required|integer|exists:quo_quotation_versions,id',
            'object_id'            => 'required|integer|exists:obj_objects,id',
            'coverage_id'          => 'required|integer|exists:cfg_coverages,id',
            'dp_capital'           => 'nullable|numeric',
            'rc_capital'           => 'nullable|numeric',
            'building_capital'     => 'nullable|numeric',
            'content_capital'      => 'nullable|numeric',
            'exploration_capital'  => 'nullable|numeric',
            'capital'              => 'nullable|numeric',
            'coverage_perc'        => 'required|numeric',
            'applied_rate'         => 'required|numeric',
            'coverage_premium'     => 'required|numeric',
            'rate_origin'          => 'nullable|string|max:100',
            'rate_id'              => 'nullable|integer',
            'discount_percent'     => 'nullable|numeric',
            'surcharge_percent'    => 'nullable|numeric',
            'manual_rate'          => 'nullable|numeric',
        ]);

        $result = $this->service->addRisk($validated);
        return response()->json($result, $result['code']);
    }

    public function show(int $id): JsonResponse
    {
        $result = $this->service->getById($id);
        return response()->json($result, $result['code']);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'object_id'           => 'sometimes|integer|exists:obj_objects,id',
            'coverage_id'         => 'sometimes|integer|exists:cfg_coverages,id',
            'dp_capital'          => 'sometimes|nullable|numeric',
            'rc_capital'          => 'sometimes|nullable|numeric',
            'building_capital'    => 'sometimes|nullable|numeric',
            'content_capital'     => 'sometimes|nullable|numeric',
            'exploration_capital' => 'sometimes|nullable|numeric',
            'capital'             => 'sometimes|nullable|numeric',
            'coverage_perc'       => 'sometimes|numeric',
            'applied_rate'        => 'sometimes|numeric',
            'coverage_premium'    => 'sometimes|numeric',
            'rate_origin'         => 'sometimes|nullable|string|max:100',
            'rate_id'             => 'sometimes|nullable|integer',
            'discount_percent'    => 'sometimes|nullable|numeric',
            'surcharge_percent'   => 'sometimes|nullable|numeric',
            'manual_rate'         => 'sometimes|nullable|numeric',
        ]);

        $result = $this->service->updateRisk($id, $validated);
        return response()->json($result, $result['code']);
    }

    public function destroy(int $id): JsonResponse
    {
        $result = $this->service->deleteRisk($id);
        return response()->json($result, $result['code']);
    }
}
