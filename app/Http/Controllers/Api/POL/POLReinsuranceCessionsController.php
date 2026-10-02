<?php

namespace App\Http\Controllers\Api\POL;

use App\Http\Controllers\Controller;
use App\Models\POL\POL_reinsurance_cessions;
use App\Services\POL\POLReinsuranceCessionsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class POLReinsuranceCessionsController extends Controller
{
    public function __construct(private POLReinsuranceCessionsService $service) {}

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
            'object_risk_id' => 'nullable|exists:pol_object_risk,id',
            'reinsurer_id' => 'required|exists:ent_entities,id',
            'treaty_reference' => 'nullable|string|max:100',
            'treaty_type' => 'required|in:Quota_Share,Surplus,Excess_of_Loss,Facultative',
            'ceded_percent' => 'nullable|numeric|min:0|max:100',
            'ceded_capital' => 'nullable|numeric|min:0',
            'ceded_premium' => 'nullable|numeric|min:0',
            'retention_percent' => 'nullable|numeric|min:0|max:100',
            'retention_capital' => 'nullable|numeric|min:0',
        ]);

        $result = $this->service->create(array_merge($validated, [
            'created_at' => now(),
        ]));
        return response()->json($result, $result['code']);
    }

    public function show(int $id): JsonResponse
    {
        $record = POL_reinsurance_cessions::with(['policyVersion', 'reinsurer', 'objectRisk'])->find($id);
        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'Cessão de resseguro não encontrada.',
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
        $validated = $request->validate([
            'object_risk_id' => 'nullable|exists:pol_object_risk,id',
            'reinsurer_id' => 'nullable|exists:ent_entities,id',
            'treaty_reference' => 'nullable|string|max:100',
            'treaty_type' => 'nullable|in:Quota_Share,Surplus,Excess_of_Loss,Facultative',
            'ceded_percent' => 'nullable|numeric|min:0|max:100',
            'ceded_capital' => 'nullable|numeric|min:0',
            'ceded_premium' => 'nullable|numeric|min:0',
            'retention_percent' => 'nullable|numeric|min:0|max:100',
            'retention_capital' => 'nullable|numeric|min:0',
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
