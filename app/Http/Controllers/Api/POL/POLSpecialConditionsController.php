<?php

namespace App\Http\Controllers\Api\POL;

use App\Http\Controllers\Controller;
use App\Models\POL\POL_special_conditions;
use App\Services\POL\POLSpecialConditionsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class POLSpecialConditionsController extends Controller
{
    public function __construct(private POLSpecialConditionsService $service) {}

    public function index(Request $request): JsonResponse
    {
        $versionId = (int) $request->query('policy_version_id');
        if (!$versionId) {
            return response()->json([
                'success' => false,
                'message' => 'Parâmetro policy_version_id é obrigatório.',
                'errors' => ['policy_version_id' => ['O parâmetro policy_version_id é obrigatório.']],
            ], 422);
        }

        $result = $this->service->getByVersion($versionId);
        return response()->json($result, $result['code']);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'policy_version_id' => 'required|exists:pol_policy_versions,id',
            'special_condition_id' => 'nullable|exists:cfg_special_conditions,id',
            'condition_type' => 'required|in:Inclusion,Exclusion,Clause,Warranty',
            'short_text' => 'required|string|max:500',
            'long_text' => 'nullable|string',
            'display_order' => 'nullable|integer|min:1',
        ]);

        $result = $this->service->create(array_merge($validated, [
            'display_order' => $validated['display_order'] ?? 1,
            'created_at' => now(),
        ]));
        return response()->json($result, $result['code']);
    }

    public function show(int $id): JsonResponse
    {
        $record = POL_special_conditions::with(['policyVersion', 'condition'])->find($id);
        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'Condição especial não encontrada.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data' => $record,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $record = POL_special_conditions::find($id);
        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'Condição especial não encontrada.',
            ], 404);
        }

        $validated = $request->validate([
            'special_condition_id' => 'nullable|exists:cfg_special_conditions,id',
            'condition_type' => 'nullable|in:Inclusion,Exclusion,Clause,Warranty',
            'short_text' => 'nullable|string|max:500',
            'long_text' => 'nullable|string',
            'display_order' => 'nullable|integer|min:1',
        ]);

        $record->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Condição especial actualizada com sucesso.',
            'data' => $record,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $result = $this->service->delete($id);
        return response()->json($result, $result['code']);
    }
}
