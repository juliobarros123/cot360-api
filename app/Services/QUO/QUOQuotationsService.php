<?php

namespace App\Services\QUO;

use App\Models\QUO\QUO_quotations;
use App\Models\QUO\QUO_quotation_versions;
use App\Models\QUO\QUO_object_risk;
use App\Models\QUO\QUO_object_risk_deductibles;
use App\Models\QUO\QUO_object_risk_capitals;
use App\Models\QUO\QUO_premiums;
use App\Models\QUO\QUO_special_conditions;
use App\Models\QUO\QUO_insurance_adjustments;
use App\Models\QUO\QUO_quotation_discount_controls;
use App\Models\CFG\CFG_insurance_branches;
use App\Services\ServiceBase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QUOQuotationsService extends ServiceBase
{
    public function __construct(
        private QUOObjectUniqueManager $objectUniqueManager
    ) {}

    /**
     * ============================================================
     * GET ALL - Listagem de Cotações (APENAS DADOS ESSENCIAIS)
     * ============================================================
     */
 /**
 * ============================================================
 * GET ALL - Listagem de Cotações (APENAS DADOS ESSENCIAIS)
 * ============================================================
 */
/**
 * ============================================================
 * GET ALL - Listagem de Cotações (APENAS DADOS ESSENCIAIS)
 * SEM PAGINAÇÃO - Retorna todos os registros
 * ============================================================
 */
public function getAll(array $filters = []): array
{
    $query = QUO_quotations::query()
        ->select([
            'quo_quotations.id',
            'quo_quotations.quotation_number',
            'quo_quotations.customer_id',
            'quo_quotations.insurance_branch_id',
            'quo_quotations.agent_id',
            'quo_quotations.status',
            'quo_quotations.slug',
            'quo_quotations.created_at',
            'quo_quotations.updated_at'
        ])
        ->with([
            'customer:*',
            'branch:*',
            'agent:*',
        ]);

    // Filtros
    if (isset($filters['status'])) {
        $query->where('quo_quotations.status', $filters['status']);
    }

    if (isset($filters['customer_id'])) {
        $query->where('quo_quotations.customer_id', $filters['customer_id']);
    }

    if (isset($filters['insurance_branch_id'])) {
        $query->where('quo_quotations.insurance_branch_id', $filters['insurance_branch_id']);
    }

    if (isset($filters['search'])) {
        $query->where(function($q) use ($filters) {
            $q->where('quo_quotations.quotation_number', 'like', '%' . $filters['search'] . '%')
              ->orWhereHas('customer', function($customerQuery) use ($filters) {
                  $customerQuery->where('name', 'like', '%' . $filters['search'] . '%')
                               ->orWhere('nif', 'like', '%' . $filters['search'] . '%');
              });
        });
    }

    if (isset($filters['date_from'])) {
        $query->whereDate('quo_quotations.created_at', '>=', $filters['date_from']);
    }

    if (isset($filters['date_to'])) {
        $query->whereDate('quo_quotations.created_at', '<=', $filters['date_to']);
    }

    // Buscar todas as cotações sem paginação
    $quotations = $query->orderByDesc('quo_quotations.created_at')->get();

    // Transformar cada cotação
    $items = $quotations->map(function ($quotation) {
        // Buscar a versão mais recente manualmente
        $latestVersion = QUO_quotation_versions::where('quotation_id', $quotation->id)
            ->orderByDesc('version')
            ->select([
                'id',
                'quotation_id',
                'version',
                'total_premium',
                'currency',
                'valid_until',
                'status'
            ])
            ->first();

        $data = [
            'id' => $quotation->id,
            'quotation_number' => $quotation->quotation_number,
            'status' => $quotation->status,
            'created_at' => $quotation->created_at,
            'customer' => $quotation->customer ? [
                'id' => $quotation->customer->id,
                'name' => $quotation->customer->name,
                'nif' => $quotation->customer->nif,
                'email' => $quotation->customer->email
            ] : null,
            'branch' => $quotation->branch ? [
                'id' => $quotation->branch->id,
                'name' => $quotation->branch->name,
                'code' => $quotation->branch->code
            ] : null,
            'agent' => $quotation->agent ? [
                'id' => $quotation->agent->id,
                'name' => $quotation->agent->name
            ] : null,
        ];

        // Dados da versão mais recente
        if ($latestVersion) {
            $objectsCount = QUO_object_risk::where('quotation_version_id', $latestVersion->id)
                ->distinct('object_id')
                ->count('object_id');

            $data['version'] = [
                'id' => $latestVersion->id,
                'number' => $latestVersion->version,
                'total_premium' => $latestVersion->total_premium,
                'currency' => $latestVersion->currency,
                'valid_until' => $latestVersion->valid_until,
                'status' => $latestVersion->status,
                'objects_count' => $objectsCount
            ];
        }

        return $data;
    });

    return $this->successResponse([
        'items' => $items,
        'total' => $items->count()
    ]);
}

    /**
     * ============================================================
     * GET BY ID - Cotação Completa com TODOS os dados
     * (ESTRUTURA OBJECTS-WITH-RISKS)
     * ============================================================
     */
    public function getById(int $id): array
    {
        // Buscar cotação com dados básicos
        $quotation = QUO_quotations::with([
            'customer:*',
            'branch:*',
            'agent:*'
        ])->find($id);

        if (!$quotation) {
            return $this->errorResponse('Cotação não encontrada.', 404);
        }

        // Buscar todas as versões da cotação
        $versions = QUO_quotation_versions::where('quotation_id', $id)
            ->orderByDesc('version')
            ->select([
                'id',
                'version',
                'table_code',
                'sales_channel_id',
                'cae_rate_id',
                'total_premium',
                'discount_percent',
                'currency',
                'exchange_rate',
                'exchange_rate_usd_aoa',
                'total_premium_usd',
                'status',
                'created_date',
                'valid_until',
                'template_code',
                'created_at'
            ])
            ->get();

        // Para cada versão, buscar seus dados completos (sem objetos ainda)
        $versionsData = [];
        foreach ($versions as $version) {
            $versionsData[] = $this->hydrateVersionBasic($version);
        }

        // Montar resposta final
        $response = [
            'id' => $quotation->id,
            'quotation_number' => $quotation->quotation_number,
            'status' => $quotation->status,
            'slug' => $quotation->slug,
            'created_at' => $quotation->created_at,
            'updated_at' => $quotation->updated_at,
            'customer' => $quotation->customer,
            'branch' => $quotation->branch,
            'agent' => $quotation->agent,
            'versions' => $versionsData
        ];

        return $this->successResponse($response);
    }

    public function getDiscountStatus(int $quotationId): array
    {
        $quotation = QUO_quotations::find($quotationId);
        if (!$quotation) {
            return $this->errorResponse('Cotação não encontrada.', 404);
        }

        $versions = QUO_quotation_versions::where('quotation_id', $quotationId)
            ->select(['id', 'quotation_id', 'version', 'table_code', 'discount_percent'])
            ->orderBy('version')
            ->orderBy('table_code')
            ->get();

        $controls = QUO_quotation_discount_controls::where('quotation_id', $quotationId)
            ->select(['quotation_version_id', 'version_code', 'table_code', 'discount_percent', 'status', 'applied_at'])
            ->get()
            ->keyBy(fn ($row) => $row->version_code . '|' . ($row->table_code ?? ''));

        $rows = $versions->map(function ($version) use ($controls) {
            $key = (string) $version->version . '|' . ((string) ($version->table_code ?? ''));
            $control = $controls->get($key);

            return [
                'quotation_version_id' => (int) $version->id,
                'version' => (string) $version->version,
                'table_code' => $version->table_code,
                'discount_percent' => (float) ($version->discount_percent ?? 0),
                'control_discount_percent' => $control ? (float) $control->discount_percent : null,
                'status' => $control?->status ?? 'missing',
                'is_consistent' => $control
                    ? (abs((float) $control->discount_percent - (float) ($version->discount_percent ?? 0)) < 0.0001)
                    : false,
                'applied_at' => $control?->applied_at,
            ];
        })->values();

        $allConsistent = $rows->every(fn ($r) => $r['is_consistent'] === true);

        return $this->successResponse([
            'quotation_id' => $quotationId,
            'all_consistent' => $allConsistent,
            'items' => $rows,
        ]);
    }

    /**
     * ============================================================
     * GET VERSION BY ID - Versão Específica Completa
     * (ESTRUTURA OBJECTS-WITH-RISKS)
     * ============================================================
     */
    public function getVersionById(int $id): array
    {
        $version = QUO_quotation_versions::with([
            'quotation:*',
            'quotation.customer:*',
            'quotation.branch:*'
        ])->find($id);

        if (!$version) {
            return $this->errorResponse('Versão não encontrada.', 404);
        }

        // Hidratar versão com todos os dados (incluindo objetos com riscos)
        $response = $this->hydrateVersionComplete($version);
        
        // Incluir dados da cotação
        $response['quotation'] = [
            'id' => $version->quotation->id,
            'number' => $version->quotation->quotation_number,
            'status' => $version->quotation->status,
            'slug' => $version->quotation->slug,
            'customer' => $version->quotation->customer,
            'branch' => $version->quotation->branch
        ];

        return $this->successResponse($response);
    }

    /**
     * ============================================================
     * MÉTODOS AUXILIARES - HIDRATAÇÃO BÁSICA (SEM OBJETOS)
     * ============================================================
     */

    /**
     * Hidrata uma versão com dados básicos (sem objetos/riscos)
     */
    private function hydrateVersionBasic($version): array
    {
        // Dados base da versão
        $data = [
            'id' => $version->id,
            'version' => $version->version,
            'table_code' => $version->table_code,
            'total_premium' => $version->total_premium,
            'discount_percent' => $version->discount_percent,
            'currency' => $version->currency,
            'exchange_rate' => $version->exchange_rate,
            'exchange_rate_usd_aoa' => $version->exchange_rate_usd_aoa,
            'total_premium_usd' => $version->total_premium_usd,
            'status' => $version->status,
            'created_date' => $version->created_date,
            'valid_until' => $version->valid_until,
            'template_code' => $version->template_code,
            'created_at' => $version->created_at,
        ];

        // Sales Channel
        if ($version->sales_channel_id) {
            $data['sales_channel'] = DB::table('ent_sales_channels')
                ->where('id', $version->sales_channel_id)
                ->select(['id', 'channel_type', 'name', 'description'])
                ->first();
        }

        // CAE Rate
        if ($version->cae_rate_id) {
            $data['cae_rate'] = DB::table('cfg_rates_cae')
                ->where('id', $version->cae_rate_id)
                ->select(['id', 'cae', 'description', 'rate_mre_building', 'rate_mre_content'])
                ->first();
        }

        // Prêmios
        $data['premiums'] = QUO_premiums::where('quotation_version_id', $version->id)
            ->select(['id', 'type', 'value'])
            ->orderBy('id')
            ->get();

        // Condições Especiais
        $data['special_conditions'] = QUO_special_conditions::where('quotation_version_id', $version->id)
            ->with('condition:id,code,short_text,condition_type')
            ->select(['id', 'special_condition_id', 'custom_text', 'display_order'])
            ->orderBy('display_order')
            ->get()
            ->map(function ($sc) {
                return [
                    'id' => $sc->id,
                    'display_order' => $sc->display_order,
                    'custom_text' => $sc->custom_text,
                    'condition' => $sc->condition ? [
                        'code' => $sc->condition->code,
                        'short_text' => $sc->condition->short_text,
                        'type' => $sc->condition->condition_type
                    ] : null
                ];
            });

        // Apenas contagem de objetos (não os objetos em si)
        $data['objects_count'] = QUO_object_risk::where('quotation_version_id', $version->id)
            ->distinct('object_id')
            ->count('object_id');

        return $data;
    }

    /**
     * ============================================================
     * MÉTODOS AUXILIARES - HIDRATAÇÃO COMPLETA (COM OBJETOS E RISCOS)
     * ============================================================
     */

    /**
     * Hidrata uma versão com todos os dados (incluindo objetos e riscos)
     */
    private function hydrateVersionComplete($version): array
    {
        // Começar com dados básicos
        $data = $this->hydrateVersionBasic($version);

        // Adicionar objetos com seus riscos (ESTRUTURA PRINCIPAL)
        $data['objects'] = $this->getObjectsWithRisks($version->id);

        return $data;
    }

    /**
     * Busca objetos com seus riscos para uma versão
     */
    private function getObjectsWithRisks(int $versionId): array
    {
        $objects = [];
        $branchCode = (int) DB::table('quo_quotation_versions as qv')
            ->join('quo_quotations as qq', 'qq.id', '=', 'qv.quotation_id')
            ->join('cfg_insurance_branches as b', 'b.id', '=', 'qq.insurance_branch_id')
            ->where('qv.id', $versionId)
            ->value('b.code');
        
        // Buscar todos os riscos da versão
        $risks = QUO_object_risk::where('quotation_version_id', $versionId)
            ->select([
                'id',
                'object_id',
                'coverage_id',
                'object_risk_capital_id',
                'capital',
                'applied_rate',
                'coverage_premium',
                'coverage_perc',
                'rate_origin',
                'rate_id',
                'discount_percent',
                'surcharge_percent',
                'manual_rate'
            ])
            ->with([
                'coverage:id,short_name,name,description,type_cov'
            ])
            ->get();

        if ($risks->isEmpty()) {
            return $this->getObjectsFromSnapshot($versionId);
        }

        foreach ($risks as $risk) {
            $objectId = $risk->object_id;
            
            // Se objeto ainda não foi carregado, buscar seus dados
            if (!isset($objects[$objectId])) {
                $objects[$objectId] = $this->loadObjectData($objectId, $branchCode);
                $objects[$objectId]['risks'] = [];
            }

            // Adicionar risco ao objeto
            $riskData = [
                'id' => $risk->id,
                'capital' => $risk->capital,
                'applied_rate' => $risk->applied_rate,
                'premium' => $risk->coverage_premium,
                'coverage_perc' => $risk->coverage_perc,
                'rate_origin' => $risk->rate_origin,
                'rate_id' => $risk->rate_id,
                'discount_percent' => $risk->discount_percent,
                'surcharge_percent' => $risk->surcharge_percent,
                'manual_rate' => $risk->manual_rate,
                'coverage' => $risk->coverage ? [
                    'id' => $risk->coverage->id,
                    'short_name' => $risk->coverage->short_name,
                    'name' => $risk->coverage->name,
                    'description' => $risk->coverage->description,
                    'type' => $risk->coverage->type_cov
                ] : null,
                'deductibles' => $this->getRiskDeductibles($risk->id),
                'adjustments' => $this->getRiskAdjustments($risk->id)
            ];

            // Se tem capital específico, buscar detalhes
            if ($risk->object_risk_capital_id) {
                $riskData['capital_details'] = QUO_object_risk_capitals::where('id', $risk->object_risk_capital_id)
                    ->select(['id', 'capital_type', 'capital'])
                    ->first();
            }

            $objects[$objectId]['risks'][] = $riskData;
        }

        return array_values($objects);
    }

    private function getObjectsFromSnapshot(int $versionId): array
    {
        $version = QUO_quotation_versions::select(['id', 'version', 'snapshot_json'])->find($versionId);
        if (!$version) {
            return [];
        }

        return $this->extractVersionObjectsFromSnapshot(
            $version->snapshot_json,
            (int) $version->id,
            (string) $version->version
        );
    }

    private function enrichSnapshotWithObjects(array $snapshot, int $versionId, array $objects): array
    {
        if (empty($objects)) {
            return $snapshot;
        }

        if (isset($snapshot['versions']) && is_array($snapshot['versions'])) {
            foreach ($snapshot['versions'] as $index => $snapshotVersion) {
                if (!is_array($snapshotVersion)) {
                    continue;
                }

                if ((int) ($snapshotVersion['id'] ?? 0) !== $versionId) {
                    continue;
                }

                if (!isset($snapshotVersion['objects']) || !is_array($snapshotVersion['objects']) || empty($snapshotVersion['objects'])) {
                    $snapshot['versions'][$index]['objects'] = $objects;
                }
                return $snapshot;
            }
        }

        if (!isset($snapshot['objects']) || !is_array($snapshot['objects']) || empty($snapshot['objects'])) {
            $snapshot['objects'] = $objects;
        }

        return $snapshot;
    }

    private function extractVersionObjectsFromSnapshot(mixed $snapshot, int $versionId, string $versionCode): array
    {
        if (!is_array($snapshot)) {
            return [];
        }

        if (isset($snapshot['objects']) && is_array($snapshot['objects'])) {
            return $snapshot['objects'];
        }

        if (!isset($snapshot['versions']) || !is_array($snapshot['versions'])) {
            return [];
        }

        foreach ($snapshot['versions'] as $snapshotVersion) {
            if (!is_array($snapshotVersion)) {
                continue;
            }

            $idMatches = isset($snapshotVersion['id']) && (int) $snapshotVersion['id'] === $versionId;
            $codeMatches = isset($snapshotVersion['version']) && (string) $snapshotVersion['version'] === $versionCode;

            if (($idMatches || $codeMatches) && isset($snapshotVersion['objects']) && is_array($snapshotVersion['objects'])) {
                return $snapshotVersion['objects'];
            }
        }

        return [];
    }

    /**
     * Carrega dados completos de um objeto (veículo, pessoa, propriedade, etc)
     */
    private function loadObjectData(int $objectId, ?int $branchCode = null): array
    {
       
        // Dados base do objeto
        $object = DB::table('obj_objects')
            ->where('id', $objectId)
            ->select(['id', 'type', 'description', 'entity_id'])
            ->first();

        if (!$object) {
            return [];
        }

        $data = [
            'id' => $object->id,
            'type' => $object->type,
            'description' => $object->description,
            'entity_id' => $object->entity_id
        ];

        // Carregar capitals do objeto
        $data['capitals'] = QUO_object_risk_capitals::where('obj_object_id', $objectId)
            ->select(['id', 'capital_type', 'capital'])
            ->get();

        // Carregar detalhes específicos conforme o tipo
        switch ($object->type) {
            case 'Vehicle':
                $vehicle = DB::table('obj_vehicles')
                    ->where('object_id', $objectId)
                    ->select([
                        'plate', 'chassis_number', 'brand', 'model', 'version',
                        'color', 'type as vehicle_type', 'subcategory', 'cylinder_cc',
                        'number_seats', 'weight', 'first_plate_date',
                        'dp_capital', 'dp_initial_capital', 'rc_capital'
                    ])
                    ->first();
                
                if ($vehicle) {
                    $data['details'] = [
                        'plate' => $vehicle->plate,
                        'brand' => $vehicle->brand,
                        'model' => $vehicle->model,
                        'version' => $vehicle->version,
                        'type' => $vehicle->vehicle_type,
                        'subcategory' => $vehicle->subcategory,
                        'cylinder_cc' => $vehicle->cylinder_cc,
                        'seats' => $vehicle->number_seats,
                        'number_seats' => $vehicle->number_seats,
                        'engine_min' => $vehicle->cylinder_cc,
                        'weight' => $vehicle->weight,
                        'first_plate_date' => $vehicle->first_plate_date,
                        'dp_capital' => $vehicle->dp_capital,
                        'rc_capital' => $vehicle->rc_capital
                    ];
                }
                break;

            case 'Property':
                $property = DB::table('obj_properties')
                    ->where('object_id', $objectId)
                    ->select([
                        'address', 'city', 'province', 'property_type',
                        'plan_name', 'risk_class', 'insured_role', 'residence_type',
                        'total_area', 'number_of_floors', 'construction_year',
                        'fire_protection', 'security_system', 'theft_protection',
                        'building_capital', 'content_capital', 'exploration_capital'
                    ])
                    ->first();

                if ($property) {
                    $details = [
                        'address' => $property->address,
                        'city' => $property->city,
                        'province' => $property->province,
                        'type' => $property->property_type,
                        'property_type' => $property->property_type,
                        'plan_name' => $property->plan_name,
                        'risk_class' => $property->risk_class,
                        'insured_role' => $property->insured_role,
                        'residence_type' => $property->residence_type,
                        'total_area' => $property->total_area,
                        'floors' => $property->number_of_floors,
                        'number_of_floors' => $property->number_of_floors,
                        'construction_year' => $property->construction_year,
                        'fire_protection' => (bool) $property->fire_protection,
                        'security_system' => (bool) $property->security_system,
                        'theft_protection' => (bool) ($property->theft_protection ?? $property->security_system),
                        'building_capital' => $property->building_capital,
                        'content_capital' => $property->content_capital,
                        'exploration_capital' => $property->exploration_capital,
                        'building_capital_value' => $property->building_capital,
                        'content_capital_value' => $property->content_capital,
                        'exploration_capital_value' => $property->exploration_capital,
                        'capitals' => [
                            'building' => $property->building_capital,
                            'content' => $property->content_capital,
                            'exploration' => $property->exploration_capital
                        ]
                    ];

                    if ($branchCode === 32) {
                        unset(
                            $details['city'],
                            $details['province'],
                            $details['total_area'],
                            $details['floors'],
                            $details['number_of_floors'],
                            $details['fire_protection'],
                            $details['exploration_capital'],
                            $details['exploration_capital_value']
                        );
                        if (isset($details['capitals']) && is_array($details['capitals'])) {
                            unset($details['capitals']['exploration']);
                        }
                    }

                    $data['details'] = $details;
                }
                break;

            case 'Person':
                
                $person = DB::table('obj_persons')
                    ->where('object_id', $objectId)
                    ->select([
                        'name', 'gender', 'birthdate', 'age',
                        'dependent', 'relationship', 'job_category', 'salary'
                    ])
                    ->first();

                if ($person) {
                    $data['details'] = [
                        'name' => $person->name,
                        'gender' => $person->gender,
                        'birthdate' => $person->birthdate,
                        'age' => $person->age,
                        'dependent' => (bool) $person->dependent,
                        'relationship' => $person->relationship,
                        'job_category' => $person->job_category,
                        'salary' => $person->salary
                    ];
                }
                break;

            case 'SalaryAggregate':
                $salary = DB::table('obj_salary_aggregates')
                    ->where('object_id', $objectId)
                    ->select([
                        'total_base_salary', 'total_addons_salary',
                        'total_avg_variable_salary', 'number_of_months',
                        'employee_count'
                    ])
                    ->first();

                if ($salary) {
                    $data['details'] = [
                        'total_base_salary' => $salary->total_base_salary,
                        'total_addons_salary' => $salary->total_addons_salary,
                        'total_avg_variable_salary' => $salary->total_avg_variable_salary,
                        'number_of_months' => $salary->number_of_months,
                        'employee_count' => $salary->employee_count
                    ];
                }
                break;
        }

        return $data;
    }

    /**
     * Busca deductibles de um risco
     */
    private function getRiskDeductibles(int $riskId): array
    {
        return DB::table('quo_object_risk_deductibles')
            ->where('object_risk_id', $riskId)
            ->join('cfg_deductibles', 'quo_object_risk_deductibles.deductible_id', '=', 'cfg_deductibles.id')
            ->select([
                'quo_object_risk_deductibles.id',
                'quo_object_risk_deductibles.deductible_id',
                'quo_object_risk_deductibles.applied_value',
                'quo_object_risk_deductibles.applied_percent',
                'cfg_deductibles.deduct_type',
                'cfg_deductibles.deduct_object',
                'cfg_deductibles.deduct_percent',
                'cfg_deductibles.deduct_value',
                'cfg_deductibles.deduct_min',
                'cfg_deductibles.deduct_max',
                'cfg_deductibles.details'
            ])
            ->get()
            ->toArray();
    }

    /**
     * Busca adjustments de um risco
     */
    private function getRiskAdjustments(int $riskId): array
    {
        return QUO_insurance_adjustments::where('object_risk_id', $riskId)
            ->select(['id', 'type', 'value_type', 'value', 'rate', 'reason', 'notes', 'rate_id'])
            ->get()
            ->map(function ($adj) {
                return [
                    'id' => $adj->id,
                    'type' => strtolower((string) $adj->type),
                    'value_type' => $adj->value_type,
                    'value' => $adj->value,
                    'rate' => $adj->rate,
                    'reason' => $adj->reason,
                    'notes' => $adj->notes,
                    'rate_id' => $adj->rate_id,
                ];
            })
            ->toArray();
    }

    /**
     * ============================================================
     * MÉTODOS DE CRIAÇÃO/ATUALIZAÇÃO (mantidos como estavam)
     * ============================================================
     */
    public function createFull(array $data): array
    {
        // dd($data);
        return DB::transaction(function () use ($data) {
            $this->objectUniqueManager->clearMap();
// dd($data['version']['sales_channel_id']);
            $number =generateQuotationNumber($data['insurance_branch_id'], $data['version']['sales_channel_id']);
// dd($number);
            $quotation = QUO_quotations::create([
                'quotation_number'    => $number,
                'customer_id'         => $data['customer_id'],
                'insurance_branch_id' => $data['insurance_branch_id'],
                'agent_id'            => $data['agent_id'] ?? null,
                'opportunity_id'      => $data['opportunity_id'] ?? null,
                'lead_source'         => $data['lead_source'] ?? null,
                'status'              => 'Draft',
                'slug'                => Str::slug($number . '-' . uniqid()),
                'created_by'          => $data['created_by'] ?? auth()->id(),
            ]);

            $versionData = $data['version'];
            $version = QUO_quotation_versions::create([
                'quotation_id'             => $quotation->id,
                'version'                  => 'v1',
                'table_code'               => $versionData['table_code'] ?? null,
                'commercial_agent_role_id' => $versionData['commercial_agent_role_id'] ?? null,
                'sales_channel_id'         => $versionData['sales_channel_id'] ?? null,
                'cae_rate_id'              => $versionData['cae_rate_id'] ?? null,
                'total_premium'            => $versionData['total_premium'],
                'discount_percent'         => $versionData['discount_percent'] ?? 0,
                'currency'                 => $versionData['currency'] ?? 'AOA',
                'exchange_rate'            => $versionData['exchange_rate'] ?? 1,
                'exchange_rate_usd_aoa'    => $versionData['exchange_rate_usd_aoa'] ?? null,
                'total_premium_usd'        => $versionData['total_premium_usd'] ?? null,
                'status'                   => 'Pending',
                'created_date'             => now()->toDateString(),
                'template_code'            => $versionData['template_code'] ?? null,
                'valid_until'              => $versionData['valid_until'],
                'created_by'               => $data['created_by'] ?? auth()->id(),
            ]);

            foreach ($this->resolveUniqueRisks($versionData['object_risks'] ?? []) as $resolvedRisk) {
                $riskData = $resolvedRisk['data'];
                $objectId = $resolvedRisk['object_id'];
                $objectRiskCapitalId = $resolvedRisk['object_risk_capital_id'];

                $baseCoveragePremium = $this->revertCoveragePremiumFromAdjustments(
                    $riskData['coverage_premium'] ?? 0,
                    $riskData['adjustments'] ?? []
                );

                $risk = QUO_object_risk::create([
                    'quotation_version_id' => $version->id,
                    'object_id'            => $objectId,
                    'object_risk_capital_id' => $objectRiskCapitalId,
                    'coverage_id'          => $riskData['coverage_id'],
                    'dp_capital'           => $riskData['dp_capital'] ?? null,
                    'rc_capital'           => $riskData['rc_capital'] ?? null,
                    'building_capital'     => $riskData['building_capital'] ?? null,
                    'content_capital'      => $riskData['content_capital'] ?? null,
                    'exploration_capital'  => $riskData['exploration_capital'] ?? null,
                    'capital'              => $riskData['capital'] ?? null,
                    'coverage_perc'        => $riskData['coverage_perc'],
                    'applied_rate'         => $riskData['applied_rate'],
                    'coverage_premium'     => $baseCoveragePremium,
                    'rate_origin'          => $riskData['rate_origin'] ?? null,
                    'rate_id'              => $riskData['rate_id'] ?? null,
                    'discount_percent'     => $riskData['discount_percent'] ?? 0,
                    'surcharge_percent'    => $riskData['surcharge_percent'] ?? 0,
                    'manual_rate'          => $riskData['manual_rate'] ?? null,
                    'slug'                 => Str::slug('or-' . $quotation->id . '-' . uniqid()),
                ]);

                foreach ($riskData['deductibles'] ?? [] as $ded) {
                    QUO_object_risk_deductibles::create([
                        'object_risk_id'  => $risk->id,
                        'deductible_id'   => $ded['deductible_id'],
                        'applied_value'   => $ded['applied_value'] ?? null,
                        'applied_percent' => $ded['applied_percent'] ?? null,
                        'slug'            => Str::slug('ded-' . $risk->id . '-' . uniqid()),
                    ]);
                }

                foreach ($riskData['adjustments'] ?? [] as $adj) {
                    QUO_insurance_adjustments::create([
                        'object_risk_id' => $risk->id,
                        'type'           => $adj['type'],
                        'value_type'     => $adj['value_type'],
                        'value'          => $adj['value'],
                        'rate'           => $adj['rate'] ?? null,
                        'reason'         => $adj['reason'] ?? null,
                        'notes'          => $adj['notes'] ?? null,
                        'rate_id'        => $adj['rate_id'] ?? null,
                    ]);
                }
            }

            foreach ($versionData['premiums'] ?? [] as $premium) {
                QUO_premiums::create([
                    'quotation_version_id' => $version->id,
                    'type'                 => $premium['type'],
                    'value'                => $premium['value'],
                    'status'               => true,
                ]);
            }

            foreach ($versionData['special_conditions'] ?? [] as $sc) {
                QUO_special_conditions::create([
                    'quotation_version_id' => $version->id,
                    'special_condition_id' => $sc['special_condition_id'],
                    'custom_text'          => $sc['custom_text'] ?? null,
                    'display_order'        => $sc['display_order'] ?? 1,
                ]);
            }

            $snapshot = $quotation->load([
                'versions.objectRisks.object.vehicle',
                'versions.objectRisks.object.person',
                'versions.objectRisks.object.property',
                'versions.objectRisks.object.salaryAggregate',
                'versions.objectRisks.deductibles',
                'versions.objectRisks.adjustments',
                'versions.objectRisks.capitals',
                'versions.premiums',
                'versions.specialConditions',
            ])->toArray();

            $snapshot = $this->enrichSnapshotWithObjects(
                $snapshot,
                (int) $version->id,
                is_array($versionData['objects'] ?? null) ? $versionData['objects'] : []
            );
            
            $version->update(['snapshot_json' => $snapshot]);

            return $this->successResponse(
                $quotation->fresh(['customer', 'branch', 'versions']),
                'Cotação criada com sucesso.',
                201
            );
        });
    }

    public function updateStatus(int $id, string $status): array
    {
        $quotation = QUO_quotations::find($id);
        
        if (!$quotation) {
            return $this->errorResponse('Cotação não encontrada.', 404);
        }
        
        $quotation->update([
            'status' => $status,
            'updated_by' => auth()->id(),
        ]);
        
        return $this->successResponse($quotation, 'Estado actualizado.');
    }

    public function destroyQuotation(int $id, bool $confirmed = false): array
    {
        $quotation = QUO_quotations::with(['versions:id,quotation_id,policy_id'])->find($id);

        if (!$quotation) {
            return $this->errorResponse('Cotação não encontrada.', 404);
        }

        $versionsCount = (int) $quotation->versions->count();
        $linkedToPolicyCount = (int) $quotation->versions->filter(
            fn ($version) => !empty($version->policy_id)
        )->count();

        if ($linkedToPolicyCount > 0) {
            return $this->errorResponse(
                'Não é possível eliminar completamente: existem versões vinculadas a apólices.',
                409
            );
        }

        if (!$confirmed) {
            return $this->errorResponse(
                "Confirmação obrigatória para eliminação completa. Reenvie com confirm=true (versões afectadas: {$versionsCount}).",
                409
            );
        }

        return DB::transaction(function () use ($quotation, $versionsCount) {
            $quotationId = (int) $quotation->id;

            // Hard delete para acionar cascade no banco e remover versões/filhos de uma só vez.
            $quotation->forceDelete();

            return $this->successResponse(
                [
                    'id' => $quotationId,
                    'deleted_versions' => $versionsCount,
                    'mode' => 'full_delete',
                ],
                'Cotação e versões eliminadas com sucesso.'
            );
        });
    }

    public function createVersion(int $id, array $versionData): array
    {
        $quotation = QUO_quotations::find($id);
        
        if (!$quotation) {
            return $this->errorResponse('Cotação não encontrada.', 404);
        }

        return DB::transaction(function () use ($quotation, $versionData) {
            $payload = is_array($versionData['version'] ?? null) ? $versionData['version'] : $versionData;

            if (!is_array($payload)) {
                return $this->errorResponse('Payload inválido para criação de nova versão.', 422);
            }

            $quotation->update([
                'customer_id' => $versionData['customer_id'] ?? $quotation->customer_id,
                'insurance_branch_id' => $versionData['insurance_branch_id'] ?? $quotation->insurance_branch_id,
                'agent_id' => $versionData['agent_id'] ?? $quotation->agent_id,
                'opportunity_id' => $versionData['opportunity_id'] ?? $quotation->opportunity_id,
                'lead_source' => $versionData['lead_source'] ?? $quotation->lead_source,
                'updated_by' => auth()->id(),
            ]);

            $requestedVersionNumber = isset($payload['version_number']) && (int) $payload['version_number'] > 0
                ? (int) $payload['version_number']
                : null;
            $requestedVersion = $requestedVersionNumber ? ('v' . $requestedVersionNumber) : null;
            $hasSameVersion = $requestedVersion
                ? QUO_quotation_versions::where('quotation_id', $quotation->id)
                    ->where('version', $requestedVersion)
                    ->exists()
                : false;
            $isAnnex = $requestedVersion && $hasSameVersion;

            if (!$isAnnex) {
                QUO_quotation_versions::where('quotation_id', $quotation->id)
                    ->where('status', 'Pending')
                    ->update(['status' => 'Alt_Version']);
            }

            $lastVersion = QUO_quotation_versions::where('quotation_id', $quotation->id)
                ->max('version');

            $nextVersion = $requestedVersionNumber
                ? ('v' . $requestedVersionNumber)
                : ('v' . (((int) str_replace('v', '', $lastVersion ?? 'v0')) + 1));

            $existingVersion = QUO_quotation_versions::where('quotation_id', $quotation->id)
                ->where('version', $nextVersion)
                ->where('table_code', $payload['table_code'] ?? null)
                ->exists();

            if ($existingVersion) {
                return $this->errorResponse('Já existe uma versão com este número e anexo.', 422);
            }

            $version = QUO_quotation_versions::create([
                'quotation_id'             => $quotation->id,
                'version'                  => $nextVersion,
                'table_code'               => $payload['table_code'] ?? null,
                'commercial_agent_role_id' => $payload['commercial_agent_role_id'] ?? null,
                'sales_channel_id'         => $payload['sales_channel_id'] ?? null,
                'cae_rate_id'              => $payload['cae_rate_id'] ?? null,
                'total_premium'            => $payload['total_premium'] ?? 0,
                'discount_percent'         => $payload['discount_percent'] ?? 0,
                'currency'                 => $payload['currency'] ?? 'AOA',
                'exchange_rate'            => $payload['exchange_rate'] ?? 1,
                'exchange_rate_usd_aoa'    => $payload['exchange_rate_usd_aoa'] ?? null,
                'total_premium_usd'        => $payload['total_premium_usd'] ?? null,
                'status'                   => 'Pending',
                'created_date'             => now()->toDateString(),
                'template_code'            => $payload['template_code'] ?? null,
                'valid_until'              => $payload['valid_until'] ?? now()->addDays(30),
                'created_by'               => auth()->id(),
            ]);

            $this->objectUniqueManager->clearMap();

            foreach ($this->resolveUniqueRisks($payload['object_risks'] ?? []) as $resolvedRisk) {
                $riskData = $resolvedRisk['data'];
                $objectId = $resolvedRisk['object_id'];
                $objectRiskCapitalId = $resolvedRisk['object_risk_capital_id'];

                $baseCoveragePremium = $this->revertCoveragePremiumFromAdjustments(
                    $riskData['coverage_premium'] ?? 0,
                    $riskData['adjustments'] ?? []
                );

                $risk = QUO_object_risk::create([
                    'quotation_version_id' => $version->id,
                    'object_id'            => $objectId,
                    'object_risk_capital_id' => $objectRiskCapitalId,
                    'coverage_id'          => $riskData['coverage_id'],
                    'dp_capital'           => $riskData['dp_capital'] ?? null,
                    'rc_capital'           => $riskData['rc_capital'] ?? null,
                    'building_capital'     => $riskData['building_capital'] ?? null,
                    'content_capital'      => $riskData['content_capital'] ?? null,
                    'exploration_capital'  => $riskData['exploration_capital'] ?? null,
                    'capital'              => $riskData['capital'] ?? null,
                    'coverage_perc'        => $riskData['coverage_perc'],
                    'applied_rate'         => $riskData['applied_rate'],
                    'coverage_premium'     => $baseCoveragePremium,
                    'rate_origin'          => $riskData['rate_origin'] ?? null,
                    'rate_id'              => $riskData['rate_id'] ?? null,
                    'discount_percent'     => $riskData['discount_percent'] ?? 0,
                    'surcharge_percent'    => $riskData['surcharge_percent'] ?? 0,
                    'manual_rate'          => $riskData['manual_rate'] ?? null,
                    'slug'                 => Str::slug('or-' . $version->id . '-' . uniqid()),
                ]);

                foreach ($riskData['deductibles'] ?? [] as $ded) {
                    QUO_object_risk_deductibles::create([
                        'object_risk_id'  => $risk->id,
                        'deductible_id'   => $ded['deductible_id'],
                        'applied_value'   => $ded['applied_value'] ?? null,
                        'applied_percent' => $ded['applied_percent'] ?? null,
                        'slug'            => Str::slug('ded-' . $risk->id . '-' . uniqid()),
                    ]);
                }

                foreach ($riskData['adjustments'] ?? [] as $adj) {
                    QUO_insurance_adjustments::create([
                        'object_risk_id' => $risk->id,
                        'type'           => $adj['type'],
                        'value_type'     => $adj['value_type'],
                        'value'          => $adj['value'],
                        'rate'           => $adj['rate'] ?? null,
                        'reason'         => $adj['reason'] ?? null,
                        'notes'          => $adj['notes'] ?? null,
                        'rate_id'        => $adj['rate_id'] ?? null,
                    ]);
                }
            }

            foreach ($payload['premiums'] ?? [] as $premium) {
                QUO_premiums::create([
                    'quotation_version_id' => $version->id,
                    'type'                 => $premium['type'],
                    'value'                => $premium['value'],
                    'status'               => true,
                ]);
            }

            foreach ($payload['special_conditions'] ?? [] as $sc) {
                QUO_special_conditions::create([
                    'quotation_version_id' => $version->id,
                    'special_condition_id' => $sc['special_condition_id'],
                    'custom_text'          => $sc['custom_text'] ?? null,
                    'display_order'        => $sc['display_order'] ?? 1,
                ]);
            }

            $this->upsertDiscountControl($version, (float) ($payload['discount_percent'] ?? 0), 'applied');

            $this->syncDiscountAcrossAnnexes(
                (int) $quotation->id,
                (string) $nextVersion,
                (float) ($payload['discount_percent'] ?? 0),
                (int) $version->id
            );

            $snapshot = $quotation->fresh([
                'versions.objectRisks.object.vehicle',
                'versions.objectRisks.object.person',
                'versions.objectRisks.object.property',
                'versions.objectRisks.object.salaryAggregate',
                'versions.objectRisks.deductibles',
                'versions.objectRisks.adjustments',
                'versions.objectRisks.capitals',
                'versions.premiums',
                'versions.specialConditions',
            ])->toArray();

            $snapshot = $this->enrichSnapshotWithObjects(
                $snapshot,
                (int) $version->id,
                is_array($payload['objects'] ?? null) ? $payload['objects'] : []
            );

            $version->update(['snapshot_json' => $snapshot]);

            return $this->successResponse(
                $version->fresh([
                    'quotation.customer',
                    'quotation.branch',
                    'objectRisks.object.vehicle',
                    'objectRisks.object.person',
                    'objectRisks.object.property',
                    'objectRisks.object.salaryAggregate',
                    'objectRisks.coverage',
                    'objectRisks.deductibles.deductible',
                    'objectRisks.adjustments',
                    'objectRisks.capitals',
                    'premiums',
                    'specialConditions.condition',
                    'salesChannel',
                    'caeRate',
                ]),
                'Nova versão criada.',
                201
            );
        });
    }

    /**
     * Mantém desconto consistente entre anexos da mesma cotação e mesma versão.
     */
    private function syncDiscountAcrossAnnexes(
        int $quotationId,
        string $versionCode,
        float $discountPercent,
        int $currentVersionId
    ): void {
        QUO_quotation_versions::where('quotation_id', $quotationId)
            ->where('version', $versionCode)
            ->where('id', '<>', $currentVersionId)
            ->update([
                'discount_percent' => $discountPercent,
                'updated_by' => auth()->id(),
                'updated_at' => now(),
            ]);

        $siblings = QUO_quotation_versions::where('quotation_id', $quotationId)
            ->where('version', $versionCode)
            ->get(['id', 'quotation_id', 'version', 'table_code']);

        foreach ($siblings as $sibling) {
            if ((int) $sibling->id !== $currentVersionId) {
                $this->syncCoverageDiscountAdjustmentsAcrossAnnexes($currentVersionId, (int) $sibling->id);
                $this->syncVersionPremiumsAcrossAnnexes($currentVersionId, (int) $sibling->id);
            }
            $this->upsertDiscountControl($sibling, $discountPercent, 'applied');
        }
    }

    private function syncCoverageDiscountAdjustmentsAcrossAnnexes(int $sourceVersionId, int $targetVersionId): void
    {
        $sourceRisks = QUO_object_risk::with('adjustments')
            ->where('quotation_version_id', $sourceVersionId)
            ->get();

        if ($sourceRisks->isEmpty()) {
            return;
        }

        $sourceByRiskKey = [];
        foreach ($sourceRisks as $risk) {
            $riskKey = ((int) $risk->object_id) . '|' . ((int) $risk->coverage_id);
            $sourceByRiskKey[$riskKey] = [
                'adjustments' => collect($risk->adjustments ?? [])
                    ->filter(fn ($adj) => $this->isCoverageDiscountAdjustment($adj))
                    ->values(),
            ];
        }

        $targetRisks = QUO_object_risk::with('adjustments')
            ->where('quotation_version_id', $targetVersionId)
            ->get();

        foreach ($targetRisks as $targetRisk) {
            $riskKey = ((int) $targetRisk->object_id) . '|' . ((int) $targetRisk->coverage_id);
            $sourceRiskData = $sourceByRiskKey[$riskKey] ?? null;
            if (!$sourceRiskData) {
                continue;
            }

            foreach (($targetRisk->adjustments ?? []) as $existingAdj) {
                if ($this->isCoverageDiscountAdjustment($existingAdj)) {
                    $existingAdj->delete();
                }
            }

            $sourceAdjustments = $sourceRiskData['adjustments'] ?? collect();
            foreach ($sourceAdjustments as $adj) {
                QUO_insurance_adjustments::create([
                    'object_risk_id' => $targetRisk->id,
                    'type' => $adj->type,
                    'value_type' => $adj->value_type,
                    'value' => $adj->value,
                    'rate' => $adj->rate,
                    'reason' => $adj->reason,
                    'notes' => $adj->notes,
                    'rate_id' => $adj->rate_id,
                ]);
            }

        }
    }

    private function syncVersionPremiumsAcrossAnnexes(int $sourceVersionId, int $targetVersionId): void
    {
        $sourcePremiums = QUO_premiums::where('quotation_version_id', $sourceVersionId)->get();
        if ($sourcePremiums->isEmpty()) {
            return;
        }

        QUO_premiums::where('quotation_version_id', $targetVersionId)->delete();

        foreach ($sourcePremiums as $premium) {
            QUO_premiums::create([
                'quotation_version_id' => $targetVersionId,
                'type' => $premium->type,
                'value' => $premium->value,
                'status' => $premium->status,
            ]);
        }

        $sourceVersion = QUO_quotation_versions::find($sourceVersionId);
        if ($sourceVersion) {
            QUO_quotation_versions::where('id', $targetVersionId)->update([
                'total_premium' => $sourceVersion->total_premium,
                'updated_by' => auth()->id(),
                'updated_at' => now(),
            ]);
        }
    }

    private function isCoverageDiscountAdjustment(object $adjustment): bool
    {
        $type = strtolower(trim((string) ($adjustment->type ?? '')));
        $rate = (float) ($adjustment->rate ?? 0);
        return in_array($type, ['discount', 'bonus', 'commercial', 'loyalty'], true) || $rate < 0;
    }

    private function upsertDiscountControl(QUO_quotation_versions $version, float $discountPercent, string $status): void
    {
        QUO_quotation_discount_controls::updateOrCreate(
            [
                'quotation_id' => (int) $version->quotation_id,
                'version_code' => (string) $version->version,
                'table_code' => $version->table_code,
            ],
            [
                'quotation_version_id' => (int) $version->id,
                'discount_percent' => $discountPercent,
                'status' => $status,
                'applied_at' => now(),
                'updated_by' => auth()->id(),
                'created_by' => auth()->id(),
            ]
        );
    }

    private function generateQuotationNumber(int $branchId): string
    {
        $branch = CFG_insurance_branches::find($branchId);
        $code = $branch?->code ?? 'GEN';
        $year = now()->year;
        
        $count = QUO_quotations::whereYear('created_at', $year)
            ->where('insurance_branch_id', $branchId)
            ->count() + 1;
            
        return sprintf('QUO-%s-%d-%05d', $code, $year, $count);
    }

    private function normalizeRiskCapitals(array $riskData): array
    {
        $capitals = [];

        $push = function (string $type, $value) use (&$capitals): void {
            $capitalType = strtoupper(trim($type));
            $capital = (float) ($value ?? 0);
            
            if (!$capitalType || $capital <= 0) {
                return;
            }

            foreach ($capitals as $existing) {
                if (($existing['capital_type'] ?? '') === $capitalType) {
                    return;
                }
            }

            $capitals[] = [
                'capital_type' => $capitalType,
                'capital' => $capital,
            ];
        };

        foreach (($riskData['capitals'] ?? []) as $cap) {
            $push((string) ($cap['capital_type'] ?? ''), $cap['capital'] ?? 0);
        }

        if (!empty($capitals)) {
            return $capitals;
        }

        $branchCode = (int) ($riskData['branch_code'] ?? 0);

        if (in_array($branchCode, [90, 91], true)) {
            $push('DP', $riskData['dp_capital'] ?? null);
            $push('RC', $riskData['rc_capital'] ?? null);
            return $capitals;
        }

        if (in_array($branchCode, [34, 32], true)) {
            $building = (float) ($riskData['building_capital'] ?? 0);
            $content = (float) ($riskData['content_capital'] ?? 0);
            $exploration = (float) ($riskData['exploration_capital'] ?? 0);

            $push('BUILDING', $building);
            $push('CONTENT', $content);
            if ($branchCode === 34) {
                $push('EXPLORATION', $exploration);
                $push('SUM_INSURED', $building + $content + $exploration);
            } else {
                $push('SUM_INSURED', $building + $content);
            }
            return $capitals;
        }

        if ($branchCode === 10) {
            $push('SALARY', $riskData['capital'] ?? null);
            return $capitals;
        }

        if (in_array($branchCode, [18, 20], true)) {
            $push('PERSON', $riskData['capital'] ?? null);
            return $capitals;
        }

        return $capitals;
    }

    private function resolveUniqueRisks(array $riskEntries): array
    {
        $uniqueRisks = [];

        foreach ($riskEntries as $riskData) {
            if (!is_array($riskData)) {
                continue;
            }

            $riskData = $this->objectUniqueManager->normalizeRiskData($riskData);
            $objectId = $this->objectUniqueManager->resolveObjectId($riskData);
            $coverageId = (int) ($riskData['coverage_id'] ?? 0);

            if (!$objectId || !$coverageId) {
                continue;
            }

            $key = $objectId . '|' . $coverageId;

            if (isset($uniqueRisks[$key])) {
                $existing = $uniqueRisks[$key]['data'];
                $merged = array_replace($existing, $riskData);
                $merged['deductibles'] = array_values(array_merge($existing['deductibles'] ?? [], $riskData['deductibles'] ?? []));
                $merged['adjustments'] = $this->deduplicateRiskAdjustments(
                    array_values(array_merge($existing['adjustments'] ?? [], $riskData['adjustments'] ?? []))
                );
                $merged['capitals'] = array_values(array_merge($existing['capitals'] ?? [], $riskData['capitals'] ?? []));
                $uniqueRisks[$key]['data'] = $merged;
                continue;
            }

            $uniqueRisks[$key] = [
                'object_id' => $objectId,
                'data' => $riskData,
            ];
        }

        foreach ($uniqueRisks as $key => $entry) {
            $uniqueRisks[$key]['object_risk_capital_id'] = $this->resolveObjectRiskCapitalId(
                $entry['object_id'],
                $entry['data']
            );
        }

        return array_values($uniqueRisks);
    }

    private function resolveObjectRiskCapitalId(int $objectId, array $riskData): ?int
    {
        $capitals = $this->normalizeRiskCapitals($riskData);

        if (empty($capitals)) {
            return null;
        }

        $capitalIdsByType = [];

        foreach ($capitals as $cap) {
            $row = QUO_object_risk_capitals::updateOrCreate(
                [
                    'obj_object_id' => $objectId,
                    'capital_type' => $cap['capital_type'],
                ],
                [
                    'capital' => $cap['capital'],
                ]
            );

            $capitalIdsByType[$row->capital_type] = $row->id;
        }

        foreach (['SUM_INSURED', 'DP', 'RC', 'SALARY', 'PERSON', 'BUILDING', 'CONTENT', 'EXPLORATION'] as $priorityType) {
            if (isset($capitalIdsByType[$priorityType])) {
                return $capitalIdsByType[$priorityType];
            }
        }

        return reset($capitalIdsByType) ?: null;
    }

    private function deduplicateRiskAdjustments(array $adjustments): array
    {
        $seen = [];
        $result = [];

        foreach ($adjustments as $adj) {
            if (!is_array($adj)) {
                continue;
            }

            $key = implode('|', [
                strtolower(trim((string) ($adj['type'] ?? ''))),
                strtolower(trim((string) ($adj['value_type'] ?? ''))),
                number_format((float) ($adj['value'] ?? 0), 4, '.', ''),
                trim((string) ($adj['rate'] ?? '')),
                strtolower(trim((string) ($adj['reason'] ?? ''))),
                strtolower(trim((string) ($adj['notes'] ?? ''))),
                (string) ($adj['rate_id'] ?? ''),
            ]);

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $result[] = $adj;
        }

        return $result;
    }

    private function revertCoveragePremiumFromAdjustments($premium, array $adjustments): float
    {
        $currentPremium = (float) ($premium ?? 0);
        if ($currentPremium <= 0 || empty($adjustments)) {
            return $currentPremium;
        }

        foreach (array_reverse($adjustments) as $adjustment) {
            if (!$this->shouldApplyAdjustmentToPremium($adjustment)) {
                continue;
            }

            $rate = (float) ($adjustment['rate'] ?? 0);
            if ($rate === 0.0) {
                continue;
            }

            if ($rate > 0) {
                $currentPremium /= (1 + ($rate / 100));
                continue;
            }

            $absoluteRate = abs($rate);
            if ($absoluteRate >= 100) {
                continue;
            }

            $currentPremium /= (1 - ($absoluteRate / 100));
        }

        return max(0, round($currentPremium, 2));
    }

    private function shouldApplyAdjustmentToPremium(array $adjustment): bool
    {
        $rate = (float) ($adjustment['rate'] ?? 0);
        if ($rate === 0.0) {
            return false;
        }

        $normalizedType = $this->normalizeAdjustmentTypeForPremium($adjustment['type'] ?? null);
        $normalizedReason = $this->normalizeAdjustmentReasonForPremium($adjustment['reason'] ?? null);

        return !($rate > 0 && $normalizedType === 'surcharge' && $normalizedReason === 'sinistralidade');
    }

    private function normalizeAdjustmentTypeForPremium($value): string
    {
        $normalized = $this->normalizeAdjustmentTokenForPremium($value);

        if (in_array($normalized, ['surcharge', 'surcharge rc', 'malus', 'agravamento', 'agravamento rc'], true)) {
            return 'surcharge';
        }

        if (in_array($normalized, ['discount', 'desconto', 'commercial', 'loyalty', 'bonus', 'bonificacao', 'bonificacao comercial'], true)) {
            return 'discount';
        }

        if (in_array($normalized, ['deductible', 'franquia'], true)) {
            return 'deductible';
        }

        return $normalized;
    }

    private function normalizeAdjustmentReasonForPremium($value): string
    {
        $normalized = $this->normalizeAdjustmentTokenForPremium($value);

        if (in_array($normalized, ['sinistralidade', 'sinistro', 'sinistros'], true)) {
            return 'sinistralidade';
        }

        if (in_array($normalized, ['nao sinistralidade', 'sem sinistralidade'], true)) {
            return 'nao sinistralidade';
        }

        return $normalized;
    }

    private function normalizeAdjustmentTokenForPremium($value): string
    {
        $normalized = strtolower(trim((string) ($value ?? '')));
        $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalized);
        if ($transliterated !== false) {
            $normalized = $transliterated;
        }

        return preg_replace('/[\s_-]+/', ' ', $normalized) ?? '';
    }
}
