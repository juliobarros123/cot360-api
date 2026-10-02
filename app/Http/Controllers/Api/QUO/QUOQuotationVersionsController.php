<?php

namespace App\Http\Controllers\Api\QUO;

use App\Http\Controllers\Controller;
use App\Http\Requests\QUO\UpdateQUOQuotationVersionsRequest;
use App\Services\QUO\QUOQuotationVersionsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QUOQuotationVersionsController extends Controller
{
    public function __construct(private QUOQuotationVersionsService $service) {}

    public function index(Request $request): JsonResponse
    {
        $quotationId = (int) $request->query('quotation_id');
        if (!$quotationId) {
            return response()->json([
                'success' => false,
                'message' => 'Parâmetro quotation_id é obrigatório.',
                'errors' => ['quotation_id' => ['O parâmetro quotation_id é obrigatório.']],
            ], 422);
        }


        $result = $this->service->getByQuotation($quotationId);
        return response()->json($result, $result['code']);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Use POST /api/quo/quotations/{id}/versions para criar nova versão.',
        ], 405);
    }

    public function show(int $id): JsonResponse
    {
        
        $result = $this->service->getById($id);
        return response()->json($result, $result['code']);
    }

    public function update(UpdateQUOQuotationVersionsRequest $request, int $id): JsonResponse
    {
        $result = $this->service->replaceFull($id, $request->validated());
        return response()->json($result, $result['code']);
    }

    public function destroy(int $id): JsonResponse
    {
        $result = $this->service->destroy($id);
        return response()->json($result, $result['code']);
    }
}
