<?php
// ============================================================
// app/Services/QUO/QUOObjectUniqueManager.php
// ============================================================

namespace App\Services\QUO;

use App\Services\OBJ\OBJObjectsService;
use App\Models\OBJ\OBJ_objects;
use App\Models\OBJ\OBJ_vehicles;
use App\Models\OBJ\OBJ_persons;
use App\Models\OBJ\OBJ_properties;
use App\Models\OBJ\OBJ_salary_aggregates;

class QUOObjectUniqueManager
{
    private array $objectsMap = [];

    public function __construct(
        private OBJObjectsService $objObjectsService
    ) {}

    public function resolveObjectId(array $riskData): int
    {
        $riskData = $this->normalizeRiskData($riskData);

        if (!empty($riskData['object_id'])) {
            $objectId = (int) $riskData['object_id'];

            if (isset($riskData['object_data']) && is_array($riskData['object_data'])) {
                $this->syncExistingObject($objectId, $riskData['object_data']);
            }

            return $objectId;
        }

        if (!isset($riskData['object_data'])) {
            throw new \InvalidArgumentException('Cada object_risk deve ter "object_id" ou "object_data".');
        }

        $objectKey = $this->generateObjectKey($riskData['object_data']);

        if (isset($this->objectsMap[$objectKey])) {
            return $this->objectsMap[$objectKey];
        }

        $existingObjectId = $this->findExistingObject($riskData['object_data']);
        if ($existingObjectId) {
            $this->syncExistingObject($existingObjectId, $riskData['object_data']);
            $this->objectsMap[$objectKey] = $existingObjectId;
            return $existingObjectId;
        }

        $objectId = $this->createNewObject($riskData['object_data']);
        $this->objectsMap[$objectKey] = $objectId;

        return $objectId;
    }

    private function createNewObject(array $objectData): int
    {
        $objectData = $this->normalizeObjectData($objectData);

        if (empty($objectData['type'])) {
            throw new \InvalidArgumentException('object_data.type é obrigatório.');
        }

        $result = $this->objObjectsService->create([
            'type'        => $objectData['type'],
            'entity_id'   => $objectData['entity_id'] ?? null,
            'description' => $objectData['description'] ?? null,
            'detail'      => $objectData['details'] ?? [],
            'created_by'  => $objectData['created_by'] ?? auth()->id(),
        ]);

        if (!($result['success'] ?? false)) {
            throw new \RuntimeException($result['message'] ?? 'Erro ao criar objecto.');
        }

        $object = $result['data'] ?? null;
        $objectId = is_array($object) ? ($object['id'] ?? null) : ($object?->id ?? null);

        if (!$objectId) {
            throw new \RuntimeException('Não foi possível obter o ID do objecto criado.');
        }

        return $objectId;
    }

    private function findExistingObject(array $objectData): ?int
    {
        $objectData = $this->normalizeObjectData($objectData);

        $type = $objectData['type'] ?? null;
        $details = $objectData['details'] ?? [];

        if (!$type) {
            return null;
        }

        if ($type === 'Vehicle') {
            if (!empty($details['plate'])) {
                $vehicle = OBJ_vehicles::where('plate', $details['plate'])->first();
                if ($vehicle) {
                    return $vehicle->object_id;
                }
            }
            if (!empty($details['chassis_number'])) {
                $vehicle = OBJ_vehicles::where('chassis_number', $details['chassis_number'])->first();
                if ($vehicle) {
                    return $vehicle->object_id;
                }
            }
        }

        if ($type === 'Person' && !empty($details['name']) && !empty($details['birthdate'])) {
            $person = OBJ_persons::where('name', $details['name'])
                ->where('birthdate', $details['birthdate'])
                ->first();
            if ($person) {
                return $person->object_id;
            }
        }

        if ($type === 'Property' && !empty($details['address'])) {
            $property = OBJ_properties::where('address', $details['address'])->first();
            if ($property) {
                return $property->object_id;
            }
        }

        if ($type === 'SalaryAggregate' && !empty($objectData['entity_id'])) {
            $salary = OBJ_salary_aggregates::whereHas('object', function($q) use ($objectData) {
                $q->where('entity_id', $objectData['entity_id']);
            })->first();
            if ($salary) {
                return $salary->object_id;
            }
        }

        return null;
    }

    private function generateObjectKey(array $objectData): string
    {
        $objectData = $this->normalizeObjectData($objectData);

        $type = $objectData['type'] ?? 'unknown';
        $details = $objectData['details'] ?? [];

        switch ($type) {
            case 'Vehicle':
                if (!empty($details['plate'])) {
                    return 'vehicle_' . $details['plate'];
                }
                if (!empty($details['chassis_number'])) {
                    return 'vehicle_' . $details['chassis_number'];
                }
                return 'vehicle_' . md5(json_encode($details));

            case 'Person':
                $name = $details['name'] ?? '';
                $birthdate = $details['birthdate'] ?? '';
                return 'person_' . md5($name . $birthdate);

            case 'Property':
                $address = $details['address'] ?? '';
                return 'property_' . md5($address);

            case 'SalaryAggregate':
                $entityId = $objectData['entity_id'] ?? '';
                return 'salary_' . $entityId;

            default:
                return md5($type . json_encode($details));
        }
    }

    public function clearMap(): void
    {
        $this->objectsMap = [];
    }

    public function normalizeRiskData(array $riskData): array
    {
        if (!isset($riskData['object_data']) && isset($riskData['object']) && is_array($riskData['object'])) {
            $riskData['object_data'] = $riskData['object'];
        }

        if (isset($riskData['object_data']) && is_array($riskData['object_data'])) {
            $riskData['object_data'] = $this->normalizeObjectData($riskData['object_data']);
        }

        return $riskData;
    }

    private function syncExistingObject(int $objectId, array $objectData): void
    {
        $objectData = $this->normalizeObjectData($objectData);

        if (!$objectId) {
            return;
        }

        $object = OBJ_objects::with(['vehicle', 'person', 'property', 'salaryAggregate'])->find($objectId);
        if (!$object) {
            return;
        }

        $currentDetails = match ($object->type) {
            'Vehicle' => $object->vehicle?->toArray() ?? [],
            'Person' => $object->person?->toArray() ?? [],
            'Property' => $object->property?->toArray() ?? [],
            'SalaryAggregate' => $object->salaryAggregate?->toArray() ?? [],
            default => [],
        };

        $incomingDetails = is_array($objectData['details'] ?? null) ? $objectData['details'] : [];
        foreach ($incomingDetails as $key => $value) {
            if ($value === null || $value === '') {
                unset($incomingDetails[$key]);
            }
        }

        if ($object->type === 'Vehicle') {
            $incomingSeats = $incomingDetails['number_seats'] ?? null;
            if ($incomingSeats !== null && (int) $incomingSeats <= 0) {
                unset($incomingDetails['number_seats']);
            }
        }

        $mergedDetails = array_replace($currentDetails, $incomingDetails);
        foreach (['id', 'object_id', 'created_at', 'updated_at', 'deleted_at', 'slug'] as $internalField) {
            unset($mergedDetails[$internalField]);
        }

        $payload = [
            'entity_id' => $objectData['entity_id'] ?? $object->entity_id,
            'description' => $objectData['description'] ?? $object->description,
            'detail' => $mergedDetails,
            'updated_by' => auth()->id(),
        ];

        $this->objObjectsService->update($objectId, $payload);
    }

    private function normalizeObjectData(array $objectData): array
    {
        if (!isset($objectData['details']) && isset($objectData['detail']) && is_array($objectData['detail'])) {
            $objectData['details'] = $objectData['detail'];
        }

        $details = is_array($objectData['details'] ?? null) ? $objectData['details'] : [];

        if (($objectData['type'] ?? null) === 'Person') {
            if (!isset($details['name']) && !empty($details['full_name'])) {
                $details['name'] = $details['full_name'];
            }
            if (!isset($details['birthdate']) && !empty($details['birth_date'])) {
                $details['birthdate'] = $details['birth_date'];
            }
            if (!isset($details['job_category']) && !empty($details['profession'])) {
                $details['job_category'] = $details['profession'];
            }
            if (array_key_exists('gender', $details)) {
                $details['gender'] = $this->normalizeGenderValue($details['gender']);
            }
            if (array_key_exists('dependent', $details)) {
                $details['dependent'] = $this->normalizeDependentValue($details['dependent']);
            }
            if (!isset($details['job_category']) || $details['job_category'] === null || $details['job_category'] === '') {
                $details['job_category'] = 'Not specified';
            }
        }

        if (($objectData['type'] ?? null) === 'Vehicle') {
            if (!isset($details['number_seats']) && isset($details['seats'])) {
                $details['number_seats'] = $details['seats'];
            }
            if (!isset($details['cylinder_cc']) && isset($details['engine_min'])) {
                $details['cylinder_cc'] = $details['engine_min'];
            }
        }

        if (($objectData['type'] ?? null) === 'Property') {
            if (!isset($details['property_type']) && isset($details['type'])) {
                $details['property_type'] = $details['type'];
            }
            if (!isset($details['number_of_floors']) && isset($details['floors'])) {
                $details['number_of_floors'] = $details['floors'];
            }
            if (!isset($details['building_capital']) && isset($details['building_capital_value'])) {
                $details['building_capital'] = $details['building_capital_value'];
            }
            if (!isset($details['content_capital']) && isset($details['content_capital_value'])) {
                $details['content_capital'] = $details['content_capital_value'];
            }
            if (!isset($details['exploration_capital']) && isset($details['exploration_capital_value'])) {
                $details['exploration_capital'] = $details['exploration_capital_value'];
            }
            if (!isset($details['plan_name']) && isset($details['plan'])) {
                $details['plan_name'] = $details['plan'];
            }
            if (!isset($details['risk_class']) && isset($details['class_risk'])) {
                $details['risk_class'] = $details['class_risk'];
            }
            if (!isset($details['insured_role']) && isset($details['insured_as'])) {
                $details['insured_role'] = $details['insured_as'];
            }
            if (!isset($details['theft_protection']) && isset($details['meios_seguranca_roubo'])) {
                $details['theft_protection'] = $details['meios_seguranca_roubo'];
            }
        }

        $objectData['details'] = $details;

        return $objectData;
    }

    private function normalizeDependentValue(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        $normalized = strtolower(trim((string) $value));

        if (in_array($normalized, ['dependent', 'dependente', 'child', 'filho', 'sim', 'yes', 'y', 'true', '1'], true)) {
            return true;
        }

        if (in_array($normalized, ['adult', 'adulto', 'titular', 'holder', 'não', 'nao', 'no', 'n', 'false', '0', ''], true)) {
            return false;
        }

        return false;
    }

    private function normalizeGenderValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = strtolower(trim((string) $value));

        if (in_array($normalized, ['m', 'male', 'masculino'], true)) {
            return 'Male';
        }

        if (in_array($normalized, ['f', 'female', 'feminino'], true)) {
            return 'Female';
        }

        return null;
    }
}
