<?php

namespace App\Http\Controllers\Api\QUO;

use App\Http\Controllers\Controller;
use App\Services\QUO\QUOInsuranceAdjustmentsService;
use Illuminate\Http\{JsonResponse, Request};

class QUOInsuranceAdjustmentsController extends Controller
{
    public function __construct(private QUOInsuranceAdjustmentsService $service) {}

    public function index(Request $request): JsonResponse
    {
        $objectRiskId = (int) $request->query('object_risk_id');
        if (!$objectRiskId) {
            return response()->json([
                'success' => false,
                'message' => 'Parâmetro object_risk_id é obrigatório.',
                'errors' => ['object_risk_id' => ['O parâmetro object_risk_id é obrigatório.']],
            ], 422);
        }

        $result = $this->service->getByObjectRisk($objectRiskId);
        return response()->json($result, $result['code']);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'object_risk_id' => 'required|integer|exists:quo_object_risk,id',
            'type'           => 'required|string|max:100',
            'value_type'     => 'required|string|max:100',
            'value'          => 'required|numeric',
            'rate'           => 'nullable|numeric',
            'reason'         => 'nullable|string|max:255',
            'notes'          => 'nullable|string',
            'rate_id'        => 'nullable|integer',
        ]);

        $result = $this->service->create($validated);
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
            'type'       => 'sometimes|string|max:100',
            'value_type' => 'sometimes|string|max:100',
            'value'      => 'sometimes|numeric',
            'rate'       => 'sometimes|nullable|numeric',
            'reason'     => 'sometimes|nullable|string|max:255',
            'notes'      => 'sometimes|nullable|string',
            'rate_id'    => 'sometimes|nullable|integer',
        ]);

        $result = $this->service->update($id, $validated);
        return response()->json($result, $result['code']);
    }

    public function destroy(int $id): JsonResponse
    {
        $result = $this->service->delete($id);
        return response()->json($result, $result['code']);
    }
}
