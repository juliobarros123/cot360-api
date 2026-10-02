<?php

namespace App\Http\Requests\QUO;

use App\Models\OBJ\OBJ_objects;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreQUOQuotationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Cabeçalho (já existentes)
            'customer_id' => 'required|exists:ent_entities,id',
            'insurance_branch_id' => 'required|exists:cfg_insurance_branches,id',
            'agent_id' => 'nullable|exists:ent_entities,id',
            'opportunity_id' => 'nullable|string|max:50',
            'lead_source' => 'nullable|string|max:50',

            // Versão (já existentes)
            'version' => 'required|array',
            'version.commercial_agent_role_id' => 'nullable|exists:ent_entity_roles,id',
            'version.sales_channel_id' => 'nullable|exists:ent_sales_channels,id',
            'version.cae_rate_id' => 'nullable|exists:cfg_rates_cae,id',
            'version.table_code' => 'nullable|string|max:50',
            'version.version_number' => 'nullable|integer|min:1',
            'version.total_premium' => 'required|numeric|min:0',
            'version.discount_percent' => 'nullable|numeric|min:-100|max:100',
            'version.currency' => 'nullable|string|size:3',
            'version.exchange_rate' => 'nullable|numeric|min:0',
            'version.exchange_rate_usd_aoa' => 'nullable|numeric|min:0',
            'version.total_premium_usd' => 'nullable|numeric|min:0',
            'version.valid_until' => 'required|date|after:today',
            'version.template_code' => 'required|numeric|max:50',

            // Object Risks - estrutura básica
            'version.object_risks' => 'nullable|array',
            'version.object_risks.*.object_id' => 'nullable|exists:obj_objects,id|required_without:version.object_risks.*.object_data',
            'version.object_risks.*.object_data' => 'nullable|array|required_without:version.object_risks.*.object_id',
            'version.object_risks.*.object_data.type' => 'required_with:version.object_risks.*.object_data|string|in:Vehicle,Property,Person,SalaryAggregate,studentCount,Other',
            'version.object_risks.*.object_data.entity_id' => 'nullable|exists:ent_entities,id',
            'version.object_risks.*.object_data.description' => 'nullable|string|max:255',

            // --- CAMPOS CRÍTICOS PARA VEHICLE ---
            // Primeiro, permita que details seja um array
            'version.object_risks.*.object_data.details' => 'nullable|array',

            // Campos específicos de Vehicle (TODOS ELES)
            'version.object_risks.*.object_data.details.plate' => 'nullable|string|max:20',
            'version.object_risks.*.object_data.details.chassis_number' => 'nullable|string|max:50',
            'version.object_risks.*.object_data.details.brand' => 'nullable|string|max:100',
            'version.object_risks.*.object_data.details.model' => 'nullable|string|max:100',
            'version.object_risks.*.object_data.details.version' => 'nullable|string|max:100',
            'version.object_risks.*.object_data.details.color' => 'nullable|string|max:50',
            'version.object_risks.*.object_data.details.type' => 'nullable|string|max:50',
            'version.object_risks.*.object_data.details.subcategory' => 'nullable|string|max:100',
            'version.object_risks.*.object_data.details.cylinder_cc' => 'nullable|integer|min:0',
            'version.object_risks.*.object_data.details.number_seats' => 'nullable|integer|min:0',
            'version.object_risks.*.object_data.details.weight' => 'nullable|numeric|min:0',
            'version.object_risks.*.object_data.details.first_plate_date' => 'nullable|date',
            'version.object_risks.*.object_data.details.dp_capital' => 'nullable|numeric|min:0',
            'version.object_risks.*.object_data.details.rc_capital' => 'nullable|numeric|min:0',

            // --- CAMPOS PARA SALARY AGGREGATE (já existentes) ---
            'version.object_risks.*.object_data.details.total_base_salary' => 'nullable|numeric|min:0',
            'version.object_risks.*.object_data.details.total_addons_salary' => 'nullable|numeric|min:0',
            'version.object_risks.*.object_data.details.total_avg_variable_salary' => 'nullable|numeric|min:0',
            'version.object_risks.*.object_data.details.number_of_months' => 'nullable|integer|min:1',
            'version.object_risks.*.object_data.details.employee_count' => 'nullable',

            // --- CAMPOS PARA PROPERTY (se precisar) ---
            'version.object_risks.*.object_data.details.address' => 'nullable|string',
            'version.object_risks.*.object_data.details.construction_year' => 'nullable|integer|min:1800|max:' . date('Y'),
            'version.object_risks.*.object_data.details.construction_type' => 'nullable|string|max:100',
            'version.object_risks.*.object_data.details.property_type' => 'nullable|string|max:100',
            'version.object_risks.*.object_data.details.plan_name' => 'nullable|string|max:100',
            'version.object_risks.*.object_data.details.risk_class' => 'nullable|string|max:100',
            'version.object_risks.*.object_data.details.insured_role' => 'nullable|string|max:100',
            'version.object_risks.*.object_data.details.residence_type' => 'nullable|string|max:100',
            'version.object_risks.*.object_data.details.theft_protection' => 'nullable|boolean',
            'version.object_risks.*.object_data.details.building_capital' => 'nullable|numeric|min:0',
            'version.object_risks.*.object_data.details.content_capital' => 'nullable|numeric|min:0',

            // --- CAMPOS PARA PERSON (se precisar) ---
            'version.object_risks.*.object_data.details.name' => 'nullable|string|max:255',
            'version.object_risks.*.object_data.details.birthdate' => 'nullable|date',
            'version.object_risks.*.object_data.details.age' => 'nullable|integer|min:0|max:120',
            'version.object_risks.*.object_data.details.gender' => 'nullable|string|in:Male,Female,M,F',
            'version.object_risks.*.object_data.details.dependent' => 'nullable|boolean',
            'version.object_risks.*.object_data.details.relationship' => 'nullable|string|max:50',
            'version.object_risks.*.object_data.details.job_category' => 'nullable|string|max:100',
            'version.object_risks.*.object_data.details.salary' => 'nullable|numeric|min:0',
            // aliases aceitos para compatibilidade
            'version.object_risks.*.object_data.details.full_name' => 'nullable|string|max:255',
            'version.object_risks.*.object_data.details.birth_date' => 'nullable|date',
            'version.object_risks.*.object_data.details.profession' => 'nullable|string|max:100',


            // --- CAMPOS PARA studentCount (NOVO) ---
            'version.object_risks.*.object_data.details.student_count' => 'nullable|integer|min:0',

            // Campos existentes do risk (já estão aí)
            'version.object_risks.*.branch_code' => 'nullable|integer',
            'version.object_risks.*.coverage_id' => 'required|exists:cfg_coverages,id',
            'version.object_risks.*.coverage_perc' => 'required|numeric',
            'version.object_risks.*.applied_rate' => 'required|numeric|min:0',
            'version.object_risks.*.coverage_premium' => 'required|numeric|min:0',
            'version.object_risks.*.dp_capital' => 'nullable|numeric|min:0',
            'version.object_risks.*.rc_capital' => 'nullable|numeric|min:0',
            'version.object_risks.*.building_capital' => 'nullable|numeric|min:0',
            'version.object_risks.*.content_capital' => 'nullable|numeric|min:0',
            'version.object_risks.*.exploration_capital' => 'nullable|numeric|min:0',
            'version.object_risks.*.capital' => 'nullable|numeric|min:0',
            'version.object_risks.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'version.object_risks.*.surcharge_percent' => 'nullable|numeric|min:0|max:100',
            'version.object_risks.*.rate_origin' => 'nullable|string|max:100',
            'version.object_risks.*.rate_id' => 'nullable|integer|min:1',
            'version.object_risks.*.manual_rate' => 'nullable|numeric|min:0',

            // Dedutíveis, ajustes, capitais (já existentes)
            'version.object_risks.*.deductibles' => 'nullable|array',
            'version.object_risks.*.deductibles.*.deductible_id' => 'required_with:version.object_risks.*.deductibles|exists:cfg_deductibles,id',
            'version.object_risks.*.deductibles.*.applied_value' => 'nullable|numeric|min:0',
            'version.object_risks.*.deductibles.*.applied_percent' => 'nullable|numeric|min:0|max:100',

            'version.object_risks.*.adjustments' => 'nullable|array',
            'version.object_risks.*.adjustments.*.type' => 'required_with:version.object_risks.*.adjustments|in:Bonus,Surcharge,Discount,Deductible',
            'version.object_risks.*.adjustments.*.value_type' => 'required_with:version.object_risks.*.adjustments|in:Percentage,Fixed',
            'version.object_risks.*.adjustments.*.value' => 'required_with:version.object_risks.*.adjustments|numeric|min:0',
            'version.object_risks.*.adjustments.*.rate' => 'nullable|string|max:20',
            'version.object_risks.*.adjustments.*.reason' => 'nullable|string|max:255',
            'version.object_risks.*.adjustments.*.notes' => 'nullable|string',
            'version.object_risks.*.adjustments.*.rate_id' => 'nullable|integer|min:1',

            'version.object_risks.*.capitals' => 'nullable|array',
            'version.object_risks.*.capitals.*.capital_type' => 'required_with:version.object_risks.*.capitals|string',
            'version.object_risks.*.capitals.*.capital' => 'required_with:version.object_risks.*.capitals|numeric|min:0',

            // Prémios
            'version.premiums' => 'required|array|min:1',
            'version.premiums.*.type' => 'required|string|max:50',
            'version.premiums.*.value' => 'required|numeric|min:0',

            // Condições especiais
            'version.special_conditions' => 'nullable|array',
            'version.special_conditions.*.special_condition_id' => 'required_with:version.special_conditions|exists:cfg_special_conditions,id',
            'version.special_conditions.*.custom_text' => 'nullable|string',
            'version.special_conditions.*.display_order' => 'nullable|integer|min:1',
        ];
    }

    protected function prepareForValidation(): void
    {
        $input = $this->all();
        $risks = data_get($input, 'version.object_risks');

        if (!is_array($risks) || empty($risks)) {
            $objects = data_get($input, 'version.objects');
            if (is_array($objects) && !empty($objects)) {
                $risks = [];

                foreach ($objects as $object) {
                    if (!is_array($object)) {
                        continue;
                    }

                    $objectId = $object['id'] ?? null;
                    $objectData = [
                        'type' => $object['type'] ?? null,
                        'entity_id' => $object['entity_id'] ?? null,
                        'description' => $object['description'] ?? null,
                        'details' => $object['details'] ?? [],
                    ];

                    $objectRisks = is_array($object['risks'] ?? null) ? $object['risks'] : [];
                    foreach ($objectRisks as $risk) {
                        if (!is_array($risk)) {
                            continue;
                        }

                        $capitals = [];
                        if (is_array($risk['capitals'] ?? null)) {
                            $capitals = $risk['capitals'];
                        } elseif (is_array($risk['capital_details'] ?? null)) {
                            $capitals = [$risk['capital_details']];
                        }

                        $risks[] = [
                            'object_id' => $objectId,
                            'object_data' => $objectData,
                            'coverage_id' => $risk['coverage_id'] ?? ($risk['coverage']['id'] ?? null),
                            'coverage_perc' => $risk['coverage_perc'] ?? null,
                            'applied_rate' => $risk['applied_rate'] ?? null,
                            'coverage_premium' => $risk['coverage_premium'] ?? ($risk['premium'] ?? null),
                            'rate_origin' => $risk['rate_origin'] ?? null,
                            'rate_id' => $risk['rate_id'] ?? null,
                            'discount_percent' => $risk['discount_percent'] ?? null,
                            'surcharge_percent' => $risk['surcharge_percent'] ?? null,
                            'manual_rate' => $risk['manual_rate'] ?? null,
                            'dp_capital' => $risk['dp_capital'] ?? null,
                            'rc_capital' => $risk['rc_capital'] ?? null,
                            'building_capital' => $risk['building_capital'] ?? null,
                            'content_capital' => $risk['content_capital'] ?? null,
                            'exploration_capital' => $risk['exploration_capital'] ?? null,
                            'capital' => $risk['capital'] ?? null,
                            'deductibles' => $risk['deductibles'] ?? [],
                            'adjustments' => $risk['adjustments'] ?? [],
                            'capitals' => $capitals,
                            'branch_code' => $risk['branch_code'] ?? null,
                        ];
                    }
                }

                data_set($input, 'version.object_risks', $risks);
            }
        }

        if (!is_array($risks)) {
            return;
        }

        foreach ($risks as $index => $risk) {
            if (!isset($risk['object_data']) && isset($risk['object']) && is_array($risk['object'])) {
                $risk['object_data'] = $risk['object'];
            }

            if (isset($risk['adjustments']) && is_array($risk['adjustments'])) {
                foreach ($risk['adjustments'] as $adjIndex => $adj) {
                    if (!is_array($adj)) {
                        continue;
                    }

                    $rateId = $adj['rate_id'] ?? null;
                    if ($rateId === '' || $rateId === 0 || $rateId === '0') {
                        $risk['adjustments'][$adjIndex]['rate_id'] = null;
                    }
                }
            }

            $hasObjectData = isset($risk['object_data']) && is_array($risk['object_data']);
            $objectId = data_get($risk, 'object_id');
            $objectIdExists = !empty($objectId) && OBJ_objects::whereKey($objectId)->exists();

            // Quando o front envia IDs locais (não persistidos) junto com object_data,
            // limpamos object_id para permitir criação/resolução correta do objeto.
            if ($hasObjectData && !$objectIdExists) {
                $risk['object_id'] = null;
            }

            if ($hasObjectData && $objectIdExists) {
                $incomingType = data_get($risk, 'object_data.type');
                $currentType = OBJ_objects::whereKey($objectId)->value('type');
                if ($incomingType && $currentType && $incomingType !== $currentType) {
                    $risk['object_id'] = null;
                }
            }

            $type = data_get($risk, 'object_data.type');
            $details = data_get($risk, 'object_data.details', []);

            if ($type === 'Person' && is_array($details)) {
                if (!isset($details['name']) && !empty($details['full_name'])) {
                    $details['name'] = $details['full_name'];
                }
                if (!isset($details['birthdate']) && !empty($details['birth_date'])) {
                    $details['birthdate'] = $details['birth_date'];
                }
                if (!isset($details['job_category']) && !empty($details['profession'])) {
                    $details['job_category'] = $details['profession'];
                }
                if (array_key_exists('dependent', $details)) {
                    $details['dependent'] = $this->normalizeDependentValue($details['dependent']);
                }

                data_set($risk, 'object_data.details', $details);
            }

            // Multirriscos: rate_id pode não existir (manual/por capital). Se vier 0, normaliza para null.
            $riskBranchCode = (int) data_get($risk, 'branch_code', 0);
            $requestBranchCode = (int) data_get($input, 'insurance_branch_code', 0);
            $requestBranchId = (int) data_get($input, 'insurance_branch_id', 0);

            $isMrEmpresasOrHabitacaoByRisk = in_array($riskBranchCode, [34, 32], true);
            $isMrEmpresasOrHabitacaoByRequestCode = in_array($requestBranchCode, [34, 32], true);
            // fallback antigo: branch_id 6 era usado para MR Empresas no payload legado
            $isLegacyMrByRequestId = $requestBranchId === 6;

            if ($isMrEmpresasOrHabitacaoByRisk || $isMrEmpresasOrHabitacaoByRequestCode || $isLegacyMrByRequestId) {
                $rateId = data_get($risk, 'rate_id');
                if ($rateId !== null && is_numeric($rateId) && (int) $rateId <= 0) {
                    data_set($risk, 'rate_id', null);
                }
            }

            // Habitação (branch 32): remover campos herdados de MR Empresas
            // que não fazem parte da estrutura alvo da simulação.
            $isMrHabitacaoByRisk = $riskBranchCode === 32;
            $isMrHabitacaoByRequestCode = $requestBranchCode === 32;
            if (($isMrHabitacaoByRisk || $isMrHabitacaoByRequestCode) && $type === 'Property' && is_array($details)) {
                unset(
                    $details['exploration_capital'],
                    $details['exploration_capital_value'],
                    $details['fire_protection'],
                    $details['number_of_floors'],
                    $details['floors'],
                    $details['total_area'],
                    $details['latitude'],
                    $details['longitude'],
                    $details['city'],
                    $details['province'],
                    $details['postal_code']
                );

                data_set($risk, 'object_data.details', $details);
                data_set($risk, 'exploration_capital', null);
            }

            $risks[$index] = $risk;
        }

        data_set($input, 'version.object_risks', $risks);
        $this->replace($input);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $risks = data_get($this->all(), 'version.object_risks', []);

            if (!is_array($risks)) {
                return;
            }

            foreach ($risks as $index => $risk) {
                $hasObjectId = !empty(data_get($risk, 'object_id'));
                $hasObjectData = is_array(data_get($risk, 'object_data'));

                if (!$hasObjectId && !$hasObjectData) {
                    $validator->errors()->add(
                        "version.object_risks.$index.object_id",
                        'Cada object_risk deve informar object_id ou object_data.'
                    );
                }
            }
        });
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
}
