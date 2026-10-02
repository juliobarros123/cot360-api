<?php

namespace App\Http\Controllers\Api\PublicPortal;

use App\Http\Controllers\Controller;
use App\Services\PublicPortal\MotorSimulationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MotorSimulationController extends Controller
{
    public function __construct(private MotorSimulationService $service) {}

    public function simulate(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'vehicle_type' => 'required|string|max:50',
            'category' => 'required|string|max:50',
            'vehicle_value' => 'required|numeric|min:0',
            'engine_cc' => 'nullable|integer|min:0',
            'number_seats' => 'nullable|integer|min:1',
            'weight' => 'nullable|numeric|min:0',
            'brand' => 'nullable|string|max:80',
            'model' => 'nullable|string|max:80',
            'year' => 'nullable|integer|min:1900|max:2100',
            'include_own_damage' => 'nullable|boolean',
        ]);

        $result = $this->service->simulate($payload);
        return response()->json($result, $result['code']);
    }

    public function options(): JsonResponse
    {
        $result = $this->service->options();
        return response()->json($result, $result['code']);
    }
}
