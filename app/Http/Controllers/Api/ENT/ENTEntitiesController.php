<?php

namespace App\Http\Controllers\Api\ENT;

// ============================================================
// ENTEntitiesController
// ============================================================
namespace App\Http\Controllers\Api\ENT;
use App\Http\Controllers\Controller;
use App\Services\ENT\ENTEntitiesService;
use App\Http\Requests\ENT\{StoreENTEntitiesRequest, UpdateENTEntitiesRequest};
use Illuminate\Http\{Request, JsonResponse};

class ENTEntitiesController extends Controller {
    public function __construct(private ENTEntitiesService $service) {}

    public function index(Request $request): JsonResponse {
        $result = $this->service->getAll($request->only([
            'entity_type','status','kyc_status','search'
        ]));
        return response()->json($result, $result['code']);
    }
    public function store(StoreENTEntitiesRequest $request): JsonResponse {
        // dd("olll");
        $result = $this->service->create($request->validated());
        return response()->json($result, $result['code']);
    }
    public function show(int $id): JsonResponse {
        $result = $this->service->getById($id);
        return response()->json($result, $result['code']);
    }
    public function update(UpdateENTEntitiesRequest $request, int $id): JsonResponse {
        $result = $this->service->update($id, $request->validated());
        return response()->json($result, $result['code']);
    }
    public function destroy(int $id): JsonResponse {
        $result = $this->service->delete($id);
        return response()->json($result, $result['code']);
    }
    public function updateKyc(Request $request, int $id): JsonResponse {
        $request->validate(['kyc_status' => 'required|string']);
        $result = $this->service->updateKycStatus($id, $request->kyc_status, $request->notes);
        return response()->json($result, $result['code']);
    }
}
