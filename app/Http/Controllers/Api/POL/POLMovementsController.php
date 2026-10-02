<?php

namespace App\Http\Controllers\Api\POL;

use App\Http\Controllers\Controller;
use App\Http\Requests\POL\StorePOLMovementsRequest;
use App\Models\POL\POL_movements;
use App\Services\POL\POLMovementsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class POLMovementsController extends Controller
{
    public function __construct(private POLMovementsService $service) {}

    public function index(Request $request): JsonResponse
    {
        $policyId = (int) $request->query('policy_id');
        if (!$policyId) {
            return response()->json([
                'success' => false,
                'message' => 'Parâmetro policy_id é obrigatório.',
                'errors' => ['policy_id' => ['O parâmetro policy_id é obrigatório.']],
            ], 422);
        }

        $result = $this->service->getByPolicy($policyId);
        return response()->json($result, $result['code']);
    }

    public function store(StorePOLMovementsRequest $request): JsonResponse
    {
        $movement = POL_movements::create(array_merge(
            $request->validated(),
            [
                'status' => 'Pending',
                'created_by' => auth()->id(),
                'created_at' => now(),
            ]
        ));

        return response()->json([
            'success' => true,
            'message' => 'Movimento criado com sucesso.',
            'data' => $movement,
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $result = $this->service->getById($id);
        return response()->json($result, $result['code']);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $movement = POL_movements::find($id);
        if (!$movement) {
            return response()->json([
                'success' => false,
                'message' => 'Movimento não encontrado.',
            ], 404);
        }

        $validated = $request->validate([
            'movement_subtype' => 'nullable|string|max:50',
            'effective_date' => 'nullable|date',
            'premium_difference' => 'nullable|numeric',
            'reason' => 'nullable|string|max:1000',
            'notes' => 'nullable|string',
            'status' => 'nullable|in:Pending,Approved,Rejected,Processed',
        ]);

        $movement->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Movimento actualizado com sucesso.',
            'data' => $movement,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $movement = POL_movements::find($id);
        if (!$movement) {
            return response()->json([
                'success' => false,
                'message' => 'Movimento não encontrado.',
            ], 404);
        }

        $movement->delete();

        return response()->json([
            'success' => true,
            'message' => 'Movimento removido com sucesso.',
        ]);
    }

    public function approve(int $id): JsonResponse
    {
        $result = $this->service->approve($id);
        return response()->json($result, $result['code']);
    }
}
