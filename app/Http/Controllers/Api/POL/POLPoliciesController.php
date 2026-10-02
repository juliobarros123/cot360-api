<?php

namespace App\Http\Controllers\Api\POL;

use App\Http\Controllers\Controller;
use App\Services\POL\POLPoliciesService;
use App\Http\Requests\POL\{StorePOLPoliciesRequest, UpdatePOLPoliciesRequest};
use Illuminate\Http\{Request, JsonResponse};

class POLPoliciesController extends Controller {
    public function __construct(private POLPoliciesService $service) {}

    public function index(Request $request): JsonResponse {
        $result = $this->service->getAll($request->only([
            'status','customer_id','insurance_branch_id','search','per_page'
        ]));
        return response()->json($result, $result['code']);
    }

    /**
     * POST /api/pol/policies
     * Converte cotação aceite em apólice
     */
    public function store(StorePOLPoliciesRequest $request): JsonResponse {
        $result = $this->service->convertFromQuotation($request->validated());
        return response()->json($result, $result['code']);
    }

    public function show(int $id): JsonResponse {
        $result = $this->service->getById($id);
        return response()->json($result, $result['code']);
    }

    public function update(UpdatePOLPoliciesRequest $request, int $id): JsonResponse {
        // Endosso — cria nova versão
        $result = $this->service->createAmendment($id, $request->validated());
        return response()->json($result, $result['code']);
    }

    public function destroy(int $id): JsonResponse {
        $result = $this->service->cancelPolicy($id, []);
        return response()->json($result, $result['code']);
    }

    public function cancel(Request $request, int $id): JsonResponse {
        $result = $this->service->cancelPolicy($id, $request->all());
        return response()->json($result, $result['code']);
    }

    public function amendment(Request $request, int $id): JsonResponse {
        $result = $this->service->createAmendment($id, $request->all());
        return response()->json($result, $result['code']);
    }
}