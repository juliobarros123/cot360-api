<?php

namespace App\Http\Controllers\Api\QUO;

use App\Http\Controllers\Controller;
use App\Services\QUO\QUOPremiumsService;
use Illuminate\Http\{JsonResponse, Request};

class QUOPremiumsController extends Controller
{
    public function __construct(private QUOPremiumsService $service) {}

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
            'type'                 => 'required|string|max:100',
            'value'                => 'required|numeric',
            'status'               => 'sometimes|boolean',
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
            'type'   => 'sometimes|string|max:100',
            'value'  => 'sometimes|numeric',
            'status' => 'sometimes|boolean',
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
