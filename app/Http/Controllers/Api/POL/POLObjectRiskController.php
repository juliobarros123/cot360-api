<?php

namespace App\Http\Controllers\Api\POL;

use App\Http\Controllers\Controller;
use App\Services\POL\POLObjectRiskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class POLObjectRiskController extends Controller
{
    public function __construct(private POLObjectRiskService $service) {}

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
        return response()->json([
            'success' => false,
            'message' => 'Criação de riscos deve ser feita via endosso/fluxo de apólice.',
        ], 405);
    }

    public function show(int $id): JsonResponse
    {
        $result = $this->service->getById($id);
        return response()->json($result, $result['code']);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:Active,Suspended,Removed',
        ]);

        $result = $this->service->updateStatus($id, $validated['status']);
        return response()->json($result, $result['code']);
    }

    public function destroy(int $id): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Remoção directa de riscos não suportada neste endpoint.',
        ], 405);
    }
}
