<?php

namespace App\Http\Controllers\Api\OBJ;

use App\Http\Controllers\Controller;

use App\Services\OBJ\OBJObjectsService;
use App\Http\Requests\OBJ\{StoreOBJObjectsRequest, UpdateOBJObjectsRequest};
use Illuminate\Http\{Request, JsonResponse};

class OBJObjectsController extends Controller {
    public function __construct(private OBJObjectsService $service) {}

    public function index(Request $request): JsonResponse {
        $result = $this->service->getAll($request->only(['type','entity_id','per_page']));
        return response()->json($result, $result['code']);
    }
    public function store(StoreOBJObjectsRequest $request): JsonResponse {
        $result = $this->service->create($request->validated());
        return response()->json($result, $result['code']);
    }
    public function show(int $id): JsonResponse {
        $result = $this->service->getById($id);
        return response()->json($result, $result['code']);
    }
    public function update(UpdateOBJObjectsRequest $request, int $id): JsonResponse {
        $result = $this->service->update($id, $request->validated());
        return response()->json($result, $result['code']);
    }
    public function destroy(int $id): JsonResponse {
        $result = $this->service->delete($id);
        return response()->json($result, $result['code']);
    }
}