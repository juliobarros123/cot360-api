<?php

namespace App\Services\CFG;

use App\Models\CFG\Cfg_RatesSchoolAccident;
use Illuminate\Http\JsonResponse;

class CFGRatesSchoolAccidentService
{
    public function getAll(array $filters = []): array
    {
        try {
          
            $query = Cfg_RatesSchoolAccident::with(['insuranceBranch', 'coverage']);

            if (isset($filters['insurance_branch_id'])) {
                $query->where('insurance_branch_id', $filters['insurance_branch_id']);
            }

            if (isset($filters['coverage_id'])) {
                $query->where('coverage_id', $filters['coverage_id']);
            }

            if (isset($filters['type'])) {
                $query->where('type', $filters['type']);
            }

            if (isset($filters['package'])) {
                $query->where('package', $filters['package']);
            }

            if (isset($filters['active'])) {
                $query->where('active', filter_var($filters['active'], FILTER_VALIDATE_BOOLEAN));
            }

            $data = $query->orderBy('package')->orderBy('type')->get();

            return [
                'success' => true,
                'data' => $data,
                'code' => JsonResponse::HTTP_OK
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao buscar tarifas: ' . $e->getMessage(),
                'code' => JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            ];
        }
    }

    public function create(array $data): array
    {
        try {
            $rate = CFGRatesSchoolAccident::create($data);
            return [
                'success' => true,
                'data' => $rate->load(['insuranceBranch', 'coverage']),
                'code' => JsonResponse::HTTP_CREATED
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao criar tarifa: ' . $e->getMessage(),
                'code' => JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            ];
        }
    }

    public function getById(int $id): array
    {
        try {
            $rate = CFGRatesSchoolAccident::with(['insuranceBranch', 'coverage'])->find($id);

            if (!$rate) {
                return [
                    'success' => false,
                    'message' => 'Tarifa não encontrada',
                    'code' => JsonResponse::HTTP_NOT_FOUND
                ];
            }

            return [
                'success' => true,
                'data' => $rate,
                'code' => JsonResponse::HTTP_OK
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao buscar tarifa: ' . $e->getMessage(),
                'code' => JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            ];
        }
    }

    public function update(int $id, array $data): array
    {
        try {
            $rate = CFGRatesSchoolAccident::find($id);

            if (!$rate) {
                return [
                    'success' => false,
                    'message' => 'Tarifa não encontrada',
                    'code' => JsonResponse::HTTP_NOT_FOUND
                ];
            }

            $rate->update($data);

            return [
                'success' => true,
                'data' => $rate->load(['insuranceBranch', 'coverage']),
                'code' => JsonResponse::HTTP_OK
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao atualizar tarifa: ' . $e->getMessage(),
                'code' => JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            ];
        }
    }

    public function delete(int $id): array
    {
        try {
            $rate = CFGRatesSchoolAccident::find($id);

            if (!$rate) {
                return [
                    'success' => false,
                    'message' => 'Tarifa não encontrada',
                    'code' => JsonResponse::HTTP_NOT_FOUND
                ];
            }

            $rate->delete();

            return [
                'success' => true,
                'message' => 'Tarifa eliminada com sucesso',
                'code' => JsonResponse::HTTP_OK
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao eliminar tarifa: ' . $e->getMessage(),
                'code' => JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            ];
        }
    }
}