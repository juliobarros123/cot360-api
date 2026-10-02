<?php

namespace App\Http\Controllers\Api\PublicPortal;

use App\Http\Controllers\Controller;
use App\Services\PublicPortal\WorkAccidentSimulationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkAccidentSimulationController extends Controller
{
    public function __construct(private WorkAccidentSimulationService $service) {}

    public function simulate(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'cae_id' => 'required_without:cae|nullable|integer',
            'cae' => 'required_without:cae_id|nullable|string|max:20',
            'annual_salary' => 'nullable|numeric|min:0',
            'capital_value' => 'nullable|numeric|min:0',
            'salary' => 'nullable|numeric|min:0',
            'total_base_salary' => 'nullable|numeric|min:0',
            'total_addons_salary' => 'nullable|numeric|min:0',
            'total_avg_variable_salary' => 'nullable|numeric|min:0',
            'number_of_months' => 'nullable|integer|min:1|max:24',
            'employee_count' => 'nullable|integer|min:1',
        ]);

        $result = $this->service->simulate($payload);
        return response()->json($result, $result['code']);
    }

    public function caes(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'search' => 'nullable|string|max:100',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $result = $this->service->searchCae($payload);
        return response()->json($result, $result['code']);
    }
}
