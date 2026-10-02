<?php

namespace App\Http\Controllers\Api\PublicPortal;

use App\Http\Controllers\Controller;
use App\Services\PublicPortal\HealthFamilySimulationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HealthFamilySimulationController extends Controller
{
    public function __construct(private HealthFamilySimulationService $service) {}

    public function simulate(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'people' => 'required|array|min:1|max:5',
            'people.*.name' => 'nullable|string|max:120',
            'people.*.age' => 'required|integer|min:0|max:120',
            'people.*.gender' => 'required|string|in:M,F,Male,Female,male,female',
            'people.*.type' => 'required|string|in:Adult,Dependent,adult,dependent,Titular,Conjuge,Filho',
        ]);

        $result = $this->service->simulate($payload);
        return response()->json($result, $result['code']);
    }
}
