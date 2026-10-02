<?php
// ============================================================
// app/Http/Controllers/Api/QUO/QUOQuotationsController.php
// ============================================================

namespace App\Http\Controllers\Api\QUO;

use App\Http\Controllers\Controller;
use App\Services\QUO\QUOQuotationsService;
use App\Http\Requests\QUO\StoreQUOQuotationsRequest;
use App\Http\Requests\QUO\UpdateQUOQuotationsRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class QUOQuotationsController extends Controller
{
    public function __construct(
        private QUOQuotationsService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'status',
            'customer_id',
            'insurance_branch_id',
            'search',
            'per_page'
        ]);
       
        $result = $this->service->getAll($filters);
        return response()->json($result, $result['code']);
    }

    public function store(StoreQUOQuotationsRequest $request): JsonResponse
    {
        // dd($request->all());
        $result = $this->service->createFull($request->validated());
        return response()->json($result, $result['code']);
    }

    public function show(int $id): JsonResponse
    {
        
        $result = $this->service->getById($id);
        return response()->json($result, $result['code']);
    }

    public function update(UpdateQUOQuotationsRequest $request, int $id): JsonResponse
    {
        $result = $this->service->updateStatus($id, $request->status);
        return response()->json($result, $result['code']);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $fullDelete = filter_var($request->query('full_delete', false), FILTER_VALIDATE_BOOLEAN);
        $confirmed = filter_var($request->query('confirm', false), FILTER_VALIDATE_BOOLEAN);

        $result = $fullDelete
            ? $this->service->destroyQuotation($id, $confirmed)
            : $this->service->updateStatus($id, 'Rejected');

        return response()->json($result, $result['code']);
    }

    public function newVersion(Request $request, int $id): JsonResponse
    {
        $result = $this->service->createVersion($id, $request->all());
        return response()->json($result, $result['code']);
    }

    public function accept(int $id): JsonResponse
    {
        $result = $this->service->updateStatus($id, 'Accepted');
        return response()->json($result, $result['code']);
    }

    public function reject(int $id): JsonResponse
    {
        $result = $this->service->updateStatus($id, 'Rejected');
        return response()->json($result, $result['code']);
    }

    public function discountStatus(int $id): JsonResponse
    {
        $result = $this->service->getDiscountStatus($id);
        return response()->json($result, $result['code']);
    }
}
