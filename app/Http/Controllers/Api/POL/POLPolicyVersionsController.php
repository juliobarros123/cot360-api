<?php

namespace App\Http\Controllers\Api\POL;

use App\Http\Controllers\Controller;
use App\Services\POL\POLPolicyVersionsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class POLPolicyVersionsController extends Controller
{
    public function __construct(private POLPolicyVersionsService $service) {}

    public function index(Request $request): JsonResponse
    {
        $result = $this->service->getAll($request->only(['policy_id', 'status']));
        return response()->json($result, $result['code']);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Criação directa de versões não suportada neste endpoint.',
        ], 405);
    }

    public function show(int $id): JsonResponse
    {
        $result = $this->service->getById($id);
        return response()->json($result, $result['code']);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Actualização directa de versões não suportada neste endpoint.',
        ], 405);
    }

    public function destroy(int $id): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Remoção de versões não suportada neste endpoint.',
        ], 405);
    }
}
