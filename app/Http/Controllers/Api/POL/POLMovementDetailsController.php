<?php

namespace App\Http\Controllers\Api\POL;

use App\Http\Controllers\Controller;
use App\Models\POL\POL_movement_details;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class POLMovementDetailsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $movementId = (int) $request->query('movement_id');
        if (!$movementId) {
            return response()->json([
                'success' => false,
                'message' => 'Parâmetro movement_id é obrigatório.',
                'errors' => ['movement_id' => ['O parâmetro movement_id é obrigatório.']],
            ], 422);
        }

        $details = POL_movement_details::with(['movement', 'objectRisk'])
            ->where('movement_id', $movementId)
            ->orderBy('id')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data' => $details,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'movement_id' => 'required|exists:pol_movements,id',
            'change_type' => 'required|in:Object_Added,Object_Removed,Object_Modified,Coverage_Added,Coverage_Removed,Coverage_Modified,Data_Changed,Premium_Changed',
            'object_risk_id' => 'nullable|exists:pol_object_risk,id',
            'coverage_id' => 'nullable|exists:cfg_coverages,id',
            'field_name' => 'nullable|string|max:100',
            'old_value' => 'nullable|string',
            'new_value' => 'nullable|string',
            'premium_impact' => 'nullable|numeric',
        ]);

        $detail = POL_movement_details::create(array_merge($validated, ['created_at' => now()]));

        return response()->json([
            'success' => true,
            'message' => 'Detalhe de movimento criado com sucesso.',
            'data' => $detail,
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $detail = POL_movement_details::with(['movement', 'objectRisk'])->find($id);
        if (!$detail) {
            return response()->json([
                'success' => false,
                'message' => 'Detalhe de movimento não encontrado.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data' => $detail,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $detail = POL_movement_details::find($id);
        if (!$detail) {
            return response()->json([
                'success' => false,
                'message' => 'Detalhe de movimento não encontrado.',
            ], 404);
        }

        $validated = $request->validate([
            'change_type' => 'nullable|in:Object_Added,Object_Removed,Object_Modified,Coverage_Added,Coverage_Removed,Coverage_Modified,Data_Changed,Premium_Changed',
            'object_risk_id' => 'nullable|exists:pol_object_risk,id',
            'coverage_id' => 'nullable|exists:cfg_coverages,id',
            'field_name' => 'nullable|string|max:100',
            'old_value' => 'nullable|string',
            'new_value' => 'nullable|string',
            'premium_impact' => 'nullable|numeric',
        ]);

        $detail->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Detalhe de movimento actualizado com sucesso.',
            'data' => $detail,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $detail = POL_movement_details::find($id);
        if (!$detail) {
            return response()->json([
                'success' => false,
                'message' => 'Detalhe de movimento não encontrado.',
            ], 404);
        }

        $detail->delete();

        return response()->json([
            'success' => true,
            'message' => 'Detalhe de movimento removido com sucesso.',
        ]);
    }
}
