<?php

namespace App\Http\Controllers\Api\POL;

use App\Http\Controllers\Controller;
use App\Models\POL\POL_coinsurance_shares;
use App\Services\POL\POLCoinsuranceSharesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class POLCoinsuranceSharesController extends Controller
{
    public function __construct(private POLCoinsuranceSharesService $service) {}

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
            'insurer_id' => 'required|exists:ent_entities,id',
            'is_leader' => 'nullable|boolean',
            'share_percent' => 'required|numeric|min:0|max:100',
            'share_premium' => 'required|numeric|min:0',
            'share_premium_usd' => 'nullable|numeric|min:0',
            'external_reference' => 'nullable|string|max:100',
        ]);

        $result = $this->service->create(array_merge($validated, [
            'is_leader' => $validated['is_leader'] ?? false,
            'created_at' => now(),
        ]));
        return response()->json($result, $result['code']);
    }

    public function show(int $id): JsonResponse
    {
        $record = POL_coinsurance_shares::with(['policyVersion', 'insurer'])->find($id);
        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'Co-seguro não encontrado.',
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
            'insurer_id' => 'nullable|exists:ent_entities,id',
            'is_leader' => 'nullable|boolean',
            'share_percent' => 'nullable|numeric|min:0|max:100',
            'share_premium' => 'nullable|numeric|min:0',
            'share_premium_usd' => 'nullable|numeric|min:0',
            'external_reference' => 'nullable|string|max:100',
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
