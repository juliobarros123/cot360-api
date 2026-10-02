<?php
// ============================================================
// app/Services/QUO/QUOObjectRiskManager.php
// ============================================================

namespace App\Services\QUO;

use App\Services\OBJ\OBJObjectsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QUOObjectRiskManager
{
    private array $objectsMap = [];
    private array $createdObjects = [];
    
    public function __construct(
        private OBJObjectsService $objObjectsService
    ) {}

    /**
     * Resolve ou cria um objeto único baseado nos dados
     */
    public function resolveObjectId(array $riskData): int
    {
        // Se já tem object_id, usar diretamente
        if (!empty($riskData['object_id'])) {
            return $riskData['object_id'];
        }
        
        // Se não tem object_data, erro
        if (!isset($riskData['object_data'])) {
            throw new \InvalidArgumentException(
                'Cada object_risk deve ter "object_id" ou "object_data".'
            );
        }
        
        // Gerar chave única para o objeto
        $objectKey = $this->generateObjectKey($riskData['object_data']);
        
        // Se já criamos este objeto, reutilizar
        if (isset($this->objectsMap[$objectKey])) {
            return $this->objectsMap[$objectKey];
        }
        
        // Verificar se objeto já existe no banco (opcional - para evitar duplicatas)
        $existingObjectId = $this->findExistingObject($riskData['object_data']);
        if ($existingObjectId) {
            $this->objectsMap[$objectKey] = $existingObjectId;
            return $existingObjectId;
        }
        
        // Criar novo objeto
        $objectId = $this->createObject($riskData['object_data']);
        $this->objectsMap[$objectKey] = $objectId;
        $this->createdObjects[] = $objectId;
        
        return $objectId;
    }

    /**
     * Verifica se o objeto já existe no banco
     */
    private function findExistingObject(array $objectData): ?int
    {
        $type = $objectData['type'] ?? null;
        $details = $objectData['details'] ?? [];
        
        if (!$type) {
            return null;
        }
        
        // Para Vehicle, buscar por placa
        if ($type === 'Vehicle' && !empty($details['plate'])) {
            $vehicle = \App\Models\OBJ\OBJ_vehicles::where('plate', $details['plate'])->first();
            if ($vehicle) {
                return $vehicle->object_id;
            }
        }
        
        // Para Vehicle, buscar por chassis
        if ($type === 'Vehicle' && !empty($details['chassis_number'])) {
            $vehicle = \App\Models\OBJ\OBJ_vehicles::where('chassis_number', $details['chassis_number'])->first();
            if ($vehicle) {
                return $vehicle->object_id;
            }
        }
        
        return null;
    }

    /**
     * Cria o objeto usando OBJObjectsService
     */
    private function createObject(array $objectData): int
    {
        if (empty($objectData['type'])) {
            throw new \InvalidArgumentException('object_data.type é obrigatório.');
        }

        $createData = [
            'type'        => $objectData['type'],
            'entity_id'   => $objectData['entity_id'] ?? null,
            'description' => $objectData['description'] ?? null,
            'detail'      => $objectData['details'] ?? [],
            'created_by'  => $objectData['created_by'] ?? auth()->id(),
        ];

        // Adicionar campos específicos baseados no tipo
        if ($objectData['type'] === 'Vehicle') {
            $createData['detail'] = array_merge(
                $objectData['details'] ?? [],
                [
                    'dp_initial_capital' => $objectData['details']['dp_capital'] ?? null,
                ]
            );
        }

        $objectResult = $this->objObjectsService->create($createData);

        if (!($objectResult['success'] ?? false)) {
            throw new \RuntimeException($objectResult['message'] ?? 'Erro ao criar objecto.');
        }

        $object = $objectResult['data'] ?? null;
        $objectId = is_array($object) ? ($object['id'] ?? null) : ($object?->id ?? null);

        if (!$objectId) {
            throw new \RuntimeException('Não foi possível obter o ID do objecto criado.');
        }

        return $objectId;
    }

    /**
     * Gera uma chave única para identificar um objeto
     */
    private function generateObjectKey(array $objectData): string
    {
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
                // Fallback para veículo sem identificação única
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

    /**
     * Retorna todos os objetos criados nesta transação
     */
    public function getCreatedObjects(): array
    {
        return $this->createdObjects;
    }

    /**
     * Limpa o mapa de objetos
     */
    public function clear(): void
    {
        $this->objectsMap = [];
        $this->createdObjects = [];
    }
}