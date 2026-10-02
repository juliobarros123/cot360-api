<?php

namespace App\Http\Controllers\Api\ENT;

use App\Http\Controllers\Controller;
use App\Services\ENT\ENTSalesChannelsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ENTSalesChannelsController extends Controller
{
    public function __construct(
        private ENTSalesChannelsService $service
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $result = $this->service->getAll(
            $request->only(['active', 'channel_type', 'responsible', 'search', 'per_page'])
        );
        
        return response()->json($result, $result['code'] ?? 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'channel_type' => 'required|string|max:100',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'responsible' => 'nullable|string|max:255',
            'active' => 'boolean'
        ]);

        $result = $this->service->create($validated);
        
        return response()->json($result, $result['code'] ?? 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $result = $this->service->getById((int) $id);
        
        return response()->json($result, $result['code'] ?? 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'channel_type' => 'sometimes|string|max:100',
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'responsible' => 'nullable|string|max:255',
            'active' => 'boolean'
        ]);

        $result = $this->service->update((int) $id, $validated);
        
        return response()->json($result, $result['code'] ?? 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $result = $this->service->delete((int) $id);
        
        return response()->json($result, $result['code'] ?? 200);
    }

    /**
     * Get channels by type
     */
    public function byType(string $type): JsonResponse
    {
        $result = $this->service->getByType($type);
        
        return response()->json($result, $result['code'] ?? 200);
    }

    /**
     * Toggle channel active status
     */
    public function toggleStatus(string $id): JsonResponse
    {
        $result = $this->service->toggleStatus((int) $id);
        
        return response()->json($result, $result['code'] ?? 200);
    }

    /**
     * Get active channels list for dropdown
     */
    public function activeList(): JsonResponse
    {
        $result = $this->service->getActiveList();
        
        return response()->json($result, $result['code'] ?? 200);
    }
}