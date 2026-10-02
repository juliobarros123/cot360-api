<?php
// ============================================================
// ENTEntitiesService.php — App\Services\ENT\
// ============================================================
namespace App\Services\ENT;
use App\Services\ServiceBase;
use App\Models\ENT\{ENT_entities, ENT_personal_entities, ENT_corporate_entities, ENT_addresses, ENT_contacts};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ENTEntitiesService extends ServiceBase {
    public function getAll(array $filters = []): array {
        $query = ENT_entities::with(['personalEntity','corporateEntity','defaultAddress','defaultContact']);
        if (isset($filters['entity_type'])) $query->where('entity_type', $filters['entity_type']);
        if (isset($filters['status'])) $query->where('status', $filters['status']);
        if (isset($filters['kyc_status'])) $query->where('kyc_status', $filters['kyc_status']);
        if (isset($filters['search'])) {
            $q = $filters['search'];
            $query->where(function ($qb) use ($q) {
                $qb->where('name', 'like', "%$q%")
                   ->orWhere('nif', 'like', "%$q%")
                   ->orWhere('id_card', 'like', "%$q%")
                   ->orWhere('passport', 'like', "%$q%");
            });
        }
        return $this->successResponse($query->orderBy('name')->get());
    }

    public function getById(int $id): array {
        $record = ENT_entities::with([
            'personalEntity','corporateEntity','addresses','contacts',
            'roles.roleType','roles.roleSubtype','roles.ibans',
            'complianceDocuments'
        ])->find($id);
        if (!$record) return $this->errorResponse('Entidade não encontrada.', 404);
        return $this->successResponse($record);
    }

    /**
     * Cria entidade completa numa única transacção.
     * Inclui criação do sub-perfil (Personal/Corporate), endereços e contactos.
     */
    public function create(array $data): array {
        return DB::transaction(function () use ($data) {
            // dd("ola");
            $data['slug'] = Str::slug($data['name'] . '-' . uniqid());
            $entity = ENT_entities::create([
                'entity_type' => $data['entity_type'],
                'name' => $data['name'],
                'nif' => $data['nif'] ?? null,
                'id_card' => $data['id_card'] ?? null,
                'passport' => $data['passport'] ?? null,
                'identifier_status' => $data['identifier_status'] ?? 'Temporary',
                'kyc_status' => $data['kyc_status'] ?? 'Not_Started',
                'pep_status' => $data['pep_status'] ?? 'Not_Checked',
                'us_person' => $data['us_person'] ?? false,
                'status' => $data['status'] ?? 'Active',
                'active' => true,
                'slug' => $data['slug'],
                'created_by' => $data['created_by'] ?? auth()->id(),
            ]);
            // Sub-perfil
            if ($data['entity_type'] === 'Personal' && isset($data['personal'])) {
                ENT_personal_entities::create(array_merge(
                    $data['personal'],
                    ['entity_id' => $entity->id, 'slug' => Str::slug('p-' . $entity->id . '-' . uniqid())]
                ));
            }
            if ($data['entity_type'] === 'Corporate' && isset($data['corporate'])) {
                ENT_corporate_entities::create(array_merge(
                    $data['corporate'],
                    ['entity_id' => $entity->id, 'slug' => Str::slug('c-' . $entity->id . '-' . uniqid())]
                ));
            }
            // Endereços
            if (isset($data['addresses'])) {
                foreach ($data['addresses'] as $idx => $addr) {
                    ENT_addresses::create(array_merge($addr, ['entity_id' => $entity->id]));
                }
            }
            // Contactos
            if (isset($data['contacts'])) {
                foreach ($data['contacts'] as $contact) {
                    ENT_contacts::create(array_merge(
                        $contact,
                        ['entity_id' => $entity->id, 'slug' => Str::slug('ct-' . uniqid())]
                    ));
                }
            }
            return $this->successResponse(
                $entity->load(['personalEntity','corporateEntity','addresses','contacts']),
                'Entidade criada com sucesso.',
                201
            );
        });
    }

    public function update(int $id, array $data): array {
        $entity = ENT_entities::find($id);
        if (!$entity) return $this->errorResponse('Entidade não encontrada.', 404);
        return DB::transaction(function () use ($entity, $data) {
            $entity->update(array_filter($data, fn($k) => in_array($k, (new ENT_entities)->getFillable()), ARRAY_FILTER_USE_KEY));
            // Actualiza identifier_status automaticamente
            if ($entity->nif) $entity->update(['identifier_status' => 'Verified']);
            elseif ($entity->id_card || $entity->passport) $entity->update(['identifier_status' => 'Partial']);
            return $this->successResponse($entity->fresh(['personalEntity','corporateEntity']), 'Entidade actualizada.');
        });
    }

    public function delete(int $id): array {
        $entity = ENT_entities::find($id);
        if (!$entity) return $this->errorResponse('Entidade não encontrada.', 404);
        $entity->delete();
        return $this->successResponse(null, 'Entidade eliminada.');
    }

    public function updateKycStatus(int $id, string $status, ?string $notes = null): array {
        $entity = ENT_entities::find($id);
        if (!$entity) return $this->errorResponse('Entidade não encontrada.', 404);
        $update = ['kyc_status' => $status];
        if ($status === 'Approved') $update['kyc_approved_at'] = now();
        $entity->update($update);
        return $this->successResponse($entity, 'KYC actualizado.');
    }
}
