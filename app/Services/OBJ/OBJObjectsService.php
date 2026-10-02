<?php
// app/Services/OBJ/OBJObjectsService.php

namespace App\Services\OBJ;

use App\Services\ServiceBase;
use App\Models\OBJ\{
    OBJ_objects,
    OBJ_vehicles,
    OBJ_persons,
    OBJ_properties,
    OBJ_salary_aggregates,
    OBJ_student_counts  // NOVO
};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OBJObjectsService extends ServiceBase
{
    private const SLUG_MAX_LENGTH = 100;

    /**
     * Tipos suportados pelo sistema
     */
    private const SUPPORTED_TYPES = [
        'Vehicle',
        'Person',
        'Property',
        'SalaryAggregate',
        'studentCount'  // NOVO
    ];

    private function makeLimitedSlug(string $baseText): string
    {
        $base = Str::slug($baseText);
        $suffix = Str::lower(Str::random(8));

        $maxBaseLength = self::SLUG_MAX_LENGTH - strlen($suffix) - 1;
        $maxBaseLength = max($maxBaseLength, 1);

        $base = trim(substr($base, 0, $maxBaseLength), '-');
        if ($base === '') {
            $base = 'obj';
        }

        return $base . '-' . $suffix;
    }

    /**
     * Obtém todos os objetos com filtros
     */
    public function getAll(array $filters = []): array
    {
        $query = OBJ_objects::with([
            'vehicle',
            'person',
            'property',
            'salaryAggregate',
            'studentCount',  // NOVO
            'owner'
        ]);

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['entity_id'])) {
            $query->where('entity_id', $filters['entity_id']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('description', 'LIKE', "%{$filters['search']}%")
                    ->orWhere('slug', 'LIKE', "%{$filters['search']}%");
            });
        }

        return $this->successResponse(
            $query->paginate($filters['per_page'] ?? 15)
        );
    }

    /**
     * Obtém um objeto específico com todos os relacionamentos
     */
    public function getById(int $id): array
    {
        $record = OBJ_objects::with([
            'vehicle',
            'person',
            'property',
            'salaryAggregate',
            'studentCount',  // NOVO
            'owner'
        ])->find($id);

        if (!$record) {
            return $this->errorResponse('Objecto não encontrado.', 404);
        }

        return $this->successResponse($record);
    }

    /**
     * Cria objecto + sub-tipo numa única transacção
     */
    public function create(array $data): array
    {
        // dd($data);
        // Validação do tipo
        if (!in_array($data['type'], self::SUPPORTED_TYPES)) {
            return $this->errorResponse(
                "Tipo '{$data['type']}' não suportado. Tipos permitidos: " . implode(', ', self::SUPPORTED_TYPES),
                422
            );
        }

        return DB::transaction(function () use ($data) {
            $slug = $this->makeLimitedSlug(
                (string) ($data['description'] ?? $data['type'] ?? 'object')
            );

            $object = OBJ_objects::create([
                'entity_id'   => $data['entity_id'] ?? null,
                'type'        => $data['type'],
                'description' => $data['description'] ?? null,
                'slug'        => $slug,
                'created_by'  => $data['created_by'] ?? auth()->id(),
            ]);

            $this->createSubType($object, $data);

            return $this->successResponse(
                $object->load([
                    'vehicle',
                    'person',
                    'property',
                    'salaryAggregate',
                    'studentCount'  // NOVO
                ]),
                'Objecto criado com sucesso.',
                201
            );
        });
    }

    /**
     * Atualiza um objeto existente
     */
    public function update(int $id, array $data): array
    {
        $object = OBJ_objects::find($id);
        if (!$object) {
            return $this->errorResponse('Objecto não encontrado.', 404);
        }

        // Não permite alterar o tipo após criação
        if (isset($data['type']) && $data['type'] !== $object->type) {
            return $this->errorResponse('Não é permitido alterar o tipo do objeto.', 422);
        }

        return DB::transaction(function () use ($object, $data) {
            $object->update(array_intersect_key($data, array_flip([
                'entity_id',
                'description',
                'updated_by'
            ])));

            $this->updateSubType($object, $data);

            return $this->successResponse(
                $object->fresh([
                    'vehicle',
                    'person',
                    'property',
                    'salaryAggregate',
                    'studentCount'  // NOVO
                ]),
                'Objecto actualizado com sucesso.'
            );
        });
    }

    /**
     * Remove um objeto (soft delete)
     */
    public function delete(int $id): array
    {
        $object = OBJ_objects::find($id);
        if (!$object) {
            return $this->errorResponse('Objecto não encontrado.', 404);
        }

        // Verifica se está vinculado a cotações ou apólices
        if ($object->quotationRisks()->exists() || $object->policyRisks()->exists()) {
            return $this->errorResponse(
                'Objecto está associado a cotações ou apólices activas e não pode ser removido.',
                422
            );
        }

        // Remove o subtipo associado
        $this->deleteSubType($object);

        $object->delete();

        return $this->successResponse(null, 'Objecto eliminado com sucesso.');
    }

    /**
     * Cria o subtipo específico baseado no tipo do objeto
     */
    private function createSubType(OBJ_objects $object, array $data): void
    {
        $sub = $data['detail'] ?? $data;
        $slug = $this->makeLimitedSlug((string) ($object->type . '-' . $object->id));

        match ($object->type) {
            'Vehicle' => OBJ_vehicles::create(
                array_merge(
                    array_intersect_key($sub, array_flip([
                        'plate',
                        'chassis_number',
                        'brand',
                        'model',
                        'version',
                        'color',
                        'type',
                        'subcategory',
                        'cylinder_cc',
                        'number_seats',
                        'weight',
                        'first_plate_date',
                        'dp_capital',
                        'dp_initial_capital',
                        'rc_capital'
                    ])),
                    ['object_id' => $object->id, 'slug' => $slug]
                )
            ),

            'Person' => OBJ_persons::create(
                array_merge(
                    array_intersect_key($sub, array_flip([
                        'entity_id',
                        'name',
                        'gender',
                        'birthdate',
                        'age',
                        'dependent',
                        'relationship',
                        'job_category',
                        'salary'
                    ])),
                    ['object_id' => $object->id, 'slug' => $slug]
                )
            ),

            'Property' => OBJ_properties::create(
                array_merge(
                    array_intersect_key($sub, array_flip([
                        'address',
                        'city',
                        'province',
                        'postal_code',
                        'latitude',
                        'longitude',
                        'property_type',
                        'plan_name',
                        'risk_class',
                        'insured_role',
                        'residence_type',
                        'total_area',
                        'number_of_floors',
                        'construction_year',
                        'fire_protection',
                        'security_system',
                        'theft_protection',
                        'building_capital',
                        'content_capital',
                        'exploration_capital'
                    ])),
                    ['object_id' => $object->id, 'slug' => $slug]
                )
            ),

            'SalaryAggregate' => OBJ_salary_aggregates::create(
                array_merge(
                    [
                        'total_addons_salary' => 0,
                        'total_avg_variable_salary' => 0,
                        'number_of_months' => 12,
                    ],
                    array_intersect_key($sub, array_flip([
                        'total_base_salary',
                        'total_addons_salary',
                        'total_avg_variable_salary',
                        'number_of_months',
                        'employee_count'
                    ])),
                    ['object_id' => $object->id, 'slug' => $slug]
                )
            ),

            'studentCount' => OBJ_student_counts::create(  // NOVO
                array_merge(
                    array_intersect_key($sub, array_flip([
                        'student_count'
                    ])),
                    ['object_id' => $object->id, 'slug' => $slug]
                )
            ),

            default => null,
        };
    }

    /**
     * Atualiza o subtipo específico
     */
    private function updateSubType(OBJ_objects $object, array $data): void
    {
        // O campo correto é 'details' (plural)
        $sub = $data['details'] ?? $data['detail'] ?? [];
        if (empty($sub)) {
            return;
        }

        match ($object->type) {
            'Vehicle' => $object->vehicle?->update($sub),
            'Person' => $object->person?->update($sub),
            'Property' => $object->property?->update($sub),
            'SalaryAggregate' => $object->salaryAggregate?->update($sub),
            'studentCount' => $object->studentCount?->update($sub),  // NOVO
            default => null,
        };
    }

    /**
     * Remove o subtipo específico
     */
    private function deleteSubType(OBJ_objects $object): void
    {
        match ($object->type) {
            'Vehicle' => $object->vehicle?->delete(),
            'Person' => $object->person?->delete(),
            'Property' => $object->property?->delete(),
            'SalaryAggregate' => $object->salaryAggregate?->delete(),
            'studentCount' => $object->studentCount?->delete(),  // NOVO
            default => null,
        };
    }

    /**
     * Obtém estatísticas dos objetos
     */
    public function getStats(): array
    {
        $stats = [
            'total' => OBJ_objects::count(),
            'by_type' => [],
            'by_entity' => [],
        ];

        foreach (self::SUPPORTED_TYPES as $type) {
            $stats['by_type'][$type] = OBJ_objects::ofType($type)->count();
        }

        $stats['by_entity'] = OBJ_objects::select('entity_id', DB::raw('count(*) as total'))
            ->groupBy('entity_id')
            ->orderBy('total', 'desc')
            ->limit(10)
            ->get()
            ->toArray();

        return $this->successResponse($stats);
    }

    /**
     * Verifica se um tipo é suportado
     */
    public function isTypeSupported(string $type): bool
    {
        return in_array($type, self::SUPPORTED_TYPES);
    }
}
