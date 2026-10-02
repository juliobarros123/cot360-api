<?php

namespace App\Services\QUO;

use App\Models\QUO\QUO_quotation_versions;
use App\Models\QUO\QUO_object_risk;
use App\Models\QUO\QUO_object_risk_deductibles;
use App\Models\QUO\QUO_object_risk_capitals;
use App\Models\QUO\QUO_premiums;
use App\Models\QUO\QUO_special_conditions;
use App\Models\QUO\QUO_insurance_adjustments;
use App\Models\QUO\QUO_quotation_discount_controls;
use App\Services\ServiceBase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QUOQuotationVersionsService extends ServiceBase
{
    public function __construct(
        private QUOObjectUniqueManager $objectUniqueManager
    ) {}

    /**
     * ============================================================
     * GET BY QUOTATION - Lista versões de uma cotação
     * (SEM objetos/riscos - apenas dados básicos)
     * ============================================================
     */
    public function getByQuotation(int $quotationId): array
    {
       
        $versions = QUO_quotation_versions::where('quotation_id', $quotationId)
            ->orderByDesc('version')
            ->select([
                'id',
                'quotation_id',
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
            ->get()
            ->map(function ($version) {
                // Dados básicos da versão
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
                        ->select(['id', 'channel_type', 'name'])
                        ->first();
                }

                // CAE Rate
                if ($version->cae_rate_id) {
                    $data['cae_rate'] = DB::table('cfg_rates_cae')
                        ->where('id', $version->cae_rate_id)
                        ->select(['id', 'cae', 'description'])
                        ->first();
                       
                }

                // Apenas contagem de objetos (não os objetos em si)
                $data['objects_count'] = QUO_object_risk::where('quotation_version_id', $version->id)
                    ->distinct('object_id')
                    ->count('object_id');

                return $data;
            });

        return $this->successResponse($versions);
    }

    /**
     * ============================================================
     * GET BY ID - Versão específica com TODOS os dados
     * (ESTRUTURA OBJECTS-WITH-RISKS)
     * ============================================================
     */
    public function getById(int $id): array
    {
       
        $version = QUO_quotation_versions::with([
            'quotation:id,quotation_number,customer_id,insurance_branch_id,status,slug',
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
     * REPLACE FULL - Substitui completamente uma versão
     * ============================================================
     */
    public function replaceFull(int $id, array $payload): array
    {
        $version = QUO_quotation_versions::with('quotation')->find($id);
        
        if (!$version) {
            return $this->errorResponse('Versão não encontrada.', 404);
        }

        $versionData = $payload['version'] ?? null;
        
        if (!is_array($versionData)) {
            return $this->errorResponse('Payload inválido: campo version é obrigatório.', 422);
        }

        return DB::transaction(function () use ($version, $payload, $versionData) {
            $quotation = $version->quotation;
            
            // Atualizar cotação se necessário
            if ($quotation) {
                $quotation->update([
                    'customer_id' => $payload['customer_id'] ?? $quotation->customer_id,
                    'insurance_branch_id' => $payload['insurance_branch_id'] ?? $quotation->insurance_branch_id,
                    'agent_id' => $payload['agent_id'] ?? $quotation->agent_id,
                    'opportunity_id' => $payload['opportunity_id'] ?? $quotation->opportunity_id,
                    'lead_source' => $payload['lead_source'] ?? $quotation->lead_source,
                    'updated_by' => auth()->id(),
                ]);
            }

            // Atualizar versão
            $version->update([
                'table_code' => $versionData['table_code'] ?? null,
                'commercial_agent_role_id' => $versionData['commercial_agent_role_id'] ?? null,
                'sales_channel_id' => $versionData['sales_channel_id'] ?? null,
                'cae_rate_id' => $versionData['cae_rate_id'] ?? null,
                'total_premium' => $versionData['total_premium'] ?? 0,
                'discount_percent' => $versionData['discount_percent'] ?? 0,
                'currency' => $versionData['currency'] ?? 'AOA',
                'exchange_rate' => $versionData['exchange_rate'] ?? 1,
                'exchange_rate_usd_aoa' => $versionData['exchange_rate_usd_aoa'] ?? null,
                'total_premium_usd' => $versionData['total_premium_usd'] ?? null,
                'template_code' => $versionData['template_code'] ?? null,
                'valid_until' => $versionData['valid_until'] ?? $version->valid_until,
                'updated_by' => auth()->id(),
            ]);

            // Substituir todos os filhos (riscos, prêmios, condições)
            $this->replaceVersionChildren($version->id, $versionData);

            $this->syncDiscountAcrossAnnexes(
                (int) $version->quotation_id,
                (string) $version->version,
                (float) ($versionData['discount_percent'] ?? 0),
                (int) $version->id
            );

            // Atualizar snapshot
            if ($quotation) {
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

                $version->update(['snapshot_json' => $snapshot]);
            }

            return $this->successResponse(
                $this->hydrateVersionComplete($version->fresh()),
                'Versão actualizada com sucesso.'
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


    /**
     * ============================================================
     * DESTROY - Remove versão e dependências
     * ============================================================
     */
    public function destroy(int $id): array
    {
        $version = QUO_quotation_versions::with('quotation')->find($id);

        if (!$version) {
            return $this->errorResponse('Versão não encontrada.', 404);
        }

        if (!empty($version->policy_id)) {
            return $this->errorResponse('Não é possível eliminar uma versão já vinculada a apólice.', 409);
        }

        return DB::transaction(function () use ($version) {
            $versionId = (int) $version->id;
            $quotation = $version->quotation;

            $riskRecords = QUO_object_risk::withTrashed()
                ->where('quotation_version_id', $versionId)
                ->get(['id', 'object_risk_capital_id']);

            $riskIds = $riskRecords->pluck('id');
            $capitalIds = $riskRecords->pluck('object_risk_capital_id')->filter()->unique();

            if ($riskIds->isNotEmpty()) {
                QUO_object_risk_deductibles::whereIn('object_risk_id', $riskIds)->delete();
                QUO_insurance_adjustments::whereIn('object_risk_id', $riskIds)->delete();
                QUO_object_risk::withTrashed()->whereIn('id', $riskIds)->forceDelete();
            }

            if ($capitalIds->isNotEmpty()) {
                $stillReferencedCapitalIds = QUO_object_risk::withTrashed()
                    ->whereIn('object_risk_capital_id', $capitalIds)
                    ->pluck('object_risk_capital_id')->filter()->unique();

                $capitalIdsToDelete = $capitalIds->diff($stillReferencedCapitalIds);

                if ($capitalIdsToDelete->isNotEmpty()) {
                    QUO_object_risk_capitals::whereIn('id', $capitalIdsToDelete)->delete();
                }
            }

            QUO_premiums::where('quotation_version_id', $versionId)->delete();
            QUO_special_conditions::where('quotation_version_id', $versionId)->delete();

            $version->forceDelete();

            if ($quotation) {
                $remainingVersions = QUO_quotation_versions::where('quotation_id', $quotation->id)->count();
                if ($remainingVersions === 0) {
                    $quotation->forceDelete();
                }
            }

            return $this->successResponse(['id' => $versionId], 'Versão eliminada com sucesso.');
        });
    }

    /**
     * ============================================================
     * MÉTODOS AUXILIARES - HIDRATAÇÃO
     * ============================================================
     */

    /**
     * Hidrata uma versão com dados básicos
     */
    private function hydrateVersionBasic($version): array
    {
        $data = [
            'id' => $version->id,
            'quotation_id' => $version->quotation_id,
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

        return $data;
    }

    /**
     * Hidrata uma versão com todos os dados (incluindo objetos e riscos)
     */
    private function hydrateVersionComplete($version): array
    {
        // Começar com dados básicos
        $data = $this->hydrateVersionBasic($version);

        // Adicionar objetos com seus riscos
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
     * Carrega dados completos de um objeto
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
                    'total_addons' => $salary->total_addons_salary,
                    'avg_variable' => $salary->total_avg_variable_salary,
                    'months' => $salary->number_of_months,
                    'employee_count' => $salary->employee_count
                ];
            }
            break;

        // NOVO: studentCount
        case 'studentCount':
        // dd("oldddd");
            $studentCount = DB::table('obj_student_counts')
                ->where('object_id', $objectId)
                ->select([
                    'student_count'
                ])
                ->first();

            if ($studentCount) {
                $data['details'] = [
                    'student_count' => (int) $studentCount->student_count
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
     * MÉTODOS DE SUBSTITUIÇÃO (mantidos com pequenas melhorias)
     * ============================================================
     */
    private function replaceVersionChildren(int $versionId, array $versionData): void
    {
        $this->objectUniqueManager->clearMap();

        // Remover registros existentes (incluindo soft deleted) para evitar violar unique index
        $riskIds = QUO_object_risk::withTrashed()
            ->where('quotation_version_id', $versionId)
            ->pluck('id');

        if ($riskIds->isNotEmpty()) {
            QUO_object_risk_deductibles::whereIn('object_risk_id', $riskIds)->delete();
            QUO_insurance_adjustments::whereIn('object_risk_id', $riskIds)->delete();
            QUO_object_risk::withTrashed()->whereIn('id', $riskIds)->forceDelete();
        }

        QUO_premiums::where('quotation_version_id', $versionId)->delete();
        QUO_special_conditions::where('quotation_version_id', $versionId)->delete();

        // Criar novos registros
        foreach ($this->resolveUniqueRisks($versionData['object_risks'] ?? []) as $resolvedRisk) {
            $riskData = $resolvedRisk['data'];
            $objectId = $resolvedRisk['object_id'];
            $objectRiskCapitalId = $resolvedRisk['object_risk_capital_id'];

            $baseCoveragePremium = $this->revertCoveragePremiumFromAdjustments(
                $riskData['coverage_premium'] ?? 0,
                $riskData['adjustments'] ?? []
            );

            $risk = QUO_object_risk::create([
                'quotation_version_id' => $versionId,
                'object_id' => $objectId,
                'object_risk_capital_id' => $objectRiskCapitalId,
                'coverage_id' => $riskData['coverage_id'],
                'dp_capital' => $riskData['dp_capital'] ?? null,
                'rc_capital' => $riskData['rc_capital'] ?? null,
                'building_capital' => $riskData['building_capital'] ?? null,
                'content_capital' => $riskData['content_capital'] ?? null,
                'exploration_capital' => $riskData['exploration_capital'] ?? null,
                'capital' => $riskData['capital'] ?? null,
                'coverage_perc' => $riskData['coverage_perc'],
                'applied_rate' => $riskData['applied_rate'],
                'coverage_premium' => $baseCoveragePremium,
                'rate_origin' => $riskData['rate_origin'] ?? null,
                'rate_id' => $riskData['rate_id'] ?? null,
                'discount_percent' => $riskData['discount_percent'] ?? 0,
                'surcharge_percent' => $riskData['surcharge_percent'] ?? 0,
                'manual_rate' => $riskData['manual_rate'] ?? null,
                'slug' => Str::slug('or-' . $versionId . '-' . uniqid()),
            ]);

            foreach ($riskData['deductibles'] ?? [] as $ded) {
                QUO_object_risk_deductibles::create([
                    'object_risk_id' => $risk->id,
                    'deductible_id' => $ded['deductible_id'],
                    'applied_value' => $ded['applied_value'] ?? null,
                    'applied_percent' => $ded['applied_percent'] ?? null,
                    'slug' => Str::slug('ded-' . $risk->id . '-' . uniqid()),
                ]);
            }

            foreach ($riskData['adjustments'] ?? [] as $adj) {
                QUO_insurance_adjustments::create([
                    'object_risk_id' => $risk->id,
                    'type' => $adj['type'],
                    'value_type' => $adj['value_type'],
                    'value' => $adj['value'],
                    'rate' => $adj['rate'] ?? null,
                    'reason' => $adj['reason'] ?? null,
                    'notes' => $adj['notes'] ?? null,
                    'rate_id' => $adj['rate_id'] ?? null,
                ]);
            }
        }

        foreach ($versionData['premiums'] ?? [] as $premium) {
            QUO_premiums::create([
                'quotation_version_id' => $versionId,
                'type' => $premium['type'],
                'value' => $premium['value'],
                'status' => true,
            ]);
        }

        foreach ($versionData['special_conditions'] ?? [] as $condition) {
            QUO_special_conditions::create([
                'quotation_version_id' => $versionId,
                'special_condition_id' => $condition['special_condition_id'],
                'custom_text' => $condition['custom_text'] ?? null,
                'display_order' => $condition['display_order'] ?? 1,
            ]);
        }
    }

    /**
     * ============================================================
     * MÉTODOS DE NORMALIZAÇÃO (mantidos iguais)
     * ============================================================
     */
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
