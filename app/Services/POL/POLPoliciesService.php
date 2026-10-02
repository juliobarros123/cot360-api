<?php
// ============================================================
// POLPoliciesService.php — App\Services\POL\
// ============================================================
namespace App\Services\POL;

use App\Services\ServiceBase;
use App\Models\POL\{POL_policies, POL_policy_versions, POL_object_risk, POL_movements, POL_movement_details};
use App\Models\QUO\{QUO_quotations, QUO_quotation_versions, QUO_object_risk as QuoRisk};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class POLPoliciesService extends ServiceBase
{
    public function getAll(array $filters = []): array
    {
        $query = POL_policies::with(['customer', 'branch', 'activeVersion']);
        if (isset($filters['status'])) $query->where('status', $filters['status']);
        if (isset($filters['customer_id'])) $query->where('customer_id', $filters['customer_id']);
        if (isset($filters['insurance_branch_id'])) $query->where('insurance_branch_id', $filters['insurance_branch_id']);
        if (isset($filters['search'])) $query->where('policy_number', 'like', '%' . $filters['search'] . '%');
        return $this->successResponse($query->orderByDesc('created_at')->paginate($filters['per_page'] ?? 15));
    }

    public function getById(int $id): array
    {
        $policy = POL_policies::with([
            'customer',
            'branch',
            'agent',
            'quotation',
            'versions.objectRisks.object.vehicle',
            'versions.objectRisks.object.person',
            'versions.objectRisks.object.property',
            'versions.objectRisks.object.salaryAggregate',
            'versions.objectRisks.capitals',
            'versions.objectRisks.coverage',
            'versions.specialConditions',
            'versions.coinsuranceShares',
            'versions.reinsuranceCessions',
            'movements.details',
        ])->find($id);
        if (!$policy) return $this->errorResponse('Apólice não encontrada.', 404);
        return $this->successResponse($policy);
    }

    /**
     * Converte cotação aceite em apólice
     * Copia todos os riscos da versão da cotação → versão da apólice
     */
    public function convertFromQuotation(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $quotation = QUO_quotations::with(['versions.objectRisks.deductibles'])->find($data['quotation_id']);
            if (!$quotation) throw new \Exception('Cotação não encontrada.');

            $quoVersion = QUO_quotation_versions::find($data['quotation_version_id']);
            if (!$quoVersion) throw new \Exception('Versão da cotação não encontrada.');

            if ($quotation->status === 'Converted') throw new \Exception('Cotação já foi convertida.');

            // Número da apólice
            $policyNumber = $this->generatePolicyNumber($quotation->insurance_branch_id);

            // 1. Cria a apólice
            $policy = POL_policies::create([
                'policy_number'        => $policyNumber,
                'quotation_id'         => $quotation->id,
                'quotation_version_id' => $quoVersion->id,
                'insurance_branch_id'  => $quotation->insurance_branch_id,
                'customer_id'          => $quotation->customer_id,
                'agent_id'             => $quotation->agent_id,
                'previous_policy_id'   => $data['previous_policy_id'] ?? null,
                'renewal_count'        => $data['renewal_count'] ?? 0,
                'original_policy_id'   => $data['original_policy_id'] ?? null,
                'inception_date'       => $data['inception_date'],
                'current_expiry_date'  => $data['expiry_date'],
                'status'               => 'Active',
                'slug'                 => Str::slug($policyNumber . '-' . uniqid()),
                'created_by'           => $data['created_by'] ?? auth()->id(),
            ]);

            // 2. Primeira versão da apólice
            $policyVersion = POL_policy_versions::create([
                'policy_id'                => $policy->id,
                'version'                  => 1,
                'effective_date'           => $data['inception_date'],
                'expiry_date'              => $data['expiry_date'],
                'commercial_agent_role_id' => $quoVersion->commercial_agent_role_id,
                'sales_channel_id'         => $quoVersion->sales_channel_id,
                'cae_rate_id'              => $quoVersion->cae_rate_id,
                'net_premium'              => $data['net_premium'],
                'total_taxes'              => $data['total_taxes'],
                'gross_premium'            => $data['gross_premium'],
                'currency'                 => $quoVersion->currency ?? 'AOA',
                'exchange_rate'            => $quoVersion->exchange_rate ?? 1,
                'gross_premium_usd'        => $data['gross_premium_usd'] ?? null,
                'commission_rate'          => $data['commission_rate'] ?? null,
                'commission_value'         => $data['commission_value'] ?? null,
                'discount_percent'         => $quoVersion->discount_percent ?? 0,
                'status'                   => 'Active',
                'snapshot_json'            => [],
                'created_by'               => $data['created_by'] ?? auth()->id(),
                'created_at'               => now(),
            ]);

            // 3. Copia objectos de risco da cotação para a apólice
            foreach ($quoVersion->objectRisks as $quoRisk) {
                POL_object_risk::create([
                    'policy_version_id'       => $policyVersion->id,
                    'object_id'               => $quoRisk->object_id,
                    'coverage_id'             => $quoRisk->coverage_id,
                    'source_quo_object_risk_id' => $quoRisk->id,
                    'dp_capital'              => $quoRisk->dp_capital,
                    'rc_capital'              => $quoRisk->rc_capital,
                    'building_capital'        => $quoRisk->building_capital,
                    'content_capital'         => $quoRisk->content_capital,
                    'exploration_capital'     => $quoRisk->exploration_capital,
                    'capital'                 => $quoRisk->capital,
                    'coverage_perc'           => $quoRisk->coverage_perc,
                    'applied_rate'            => $quoRisk->applied_rate,
                    'coverage_premium'        => $quoRisk->coverage_premium,
                    'rate_origin'             => $quoRisk->rate_origin,
                    'rate_id'                 => $quoRisk->rate_id,
                    'discount_percent'        => $quoRisk->discount_percent,
                    'surcharge_percent'       => $quoRisk->surcharge_percent,
                    'status'                  => 'Active',
                    'effective_date'          => $data['inception_date'],
                    'slug'                    => Str::slug('pr-' . $policy->id . '-' . uniqid()),
                    'created_at'              => now(),
                ]);
            }

            // 4. Movimento inicial
            $movement = POL_movements::create([
                'policy_id'        => $policy->id,
                'from_version_id'  => null,
                'to_version_id'    => $policyVersion->id,
                'movement_type'    => 'New',
                'movement_date'    => now()->toDateString(),
                'effective_date'   => $data['inception_date'],
                'premium_difference' => $data['gross_premium'],
                'reason'           => 'Emissão inicial.',
                'status'           => 'Approved',
                'approved_by'      => $data['created_by'] ?? auth()->id(),
                'approved_at'      => now(),
                'created_by'       => $data['created_by'] ?? auth()->id(),
                'created_at'       => now(),
            ]);

            // 5. Snapshot imutável
            $snapshot = $policy->load(['versions.objectRisks.object', 'versions.specialConditions'])->toArray();
            $policyVersion->update(['snapshot_json' => $snapshot]);

            // 6. Atualiza cotação como convertida
            $quotation->update(['status' => 'Converted']);
            $quoVersion->update(['policy_id' => $policy->id, 'status' => 'Accepted']);

            return $this->successResponse(
                $policy->fresh(['customer', 'branch', 'versions']),
                'Apólice criada com sucesso.',
                201
            );
        });
    }

    /**
     * Endosso — cria nova versão da apólice
     */
    public function createAmendment(int $policyId, array $data): array
    {
        $policy = POL_policies::with(['versions'])->find($policyId);
        if (!$policy) return $this->errorResponse('Apólice não encontrada.', 404);
        if ($policy->status !== 'Active') return $this->errorResponse('Apólice não está activa.', 422);

        return DB::transaction(function () use ($policy, $data) {
            $activeVersion = $policy->versions->where('status', 'Active')->last();

            // Nova versão
            $newVersionNum = ($policy->versions->max('version') ?? 0) + 1;
            $newVersion = POL_policy_versions::create([
                'policy_id'       => $policy->id,
                'version'         => $newVersionNum,
                'effective_date'  => $data['effective_date'],
                'expiry_date'     => $activeVersion->expiry_date,
                'net_premium'     => $data['net_premium'] ?? $activeVersion->net_premium,
                'total_taxes'     => $data['total_taxes'] ?? $activeVersion->total_taxes,
                'gross_premium'   => $data['gross_premium'] ?? $activeVersion->gross_premium,
                'currency'        => $activeVersion->currency,
                'exchange_rate'   => $activeVersion->exchange_rate,
                'status'          => 'Active',
                'snapshot_json'   => [],
                'created_by'      => $data['created_by'] ?? auth()->id(),
                'created_at'      => now(),
            ]);

            $sourceRisks = !empty($data['policy_risks']) && is_array($data['policy_risks'])
                ? collect($data['policy_risks'])
                : $activeVersion->objectRisks;

            foreach ($sourceRisks as $risk) {
                $riskData = $risk instanceof POL_object_risk ? $risk->toArray() : (array) $risk;
                // dd($riskData);
                if (!$riskData['applied_rate'] && !$riskData['coverage_premium'] ) {
                    break;
                }
                POL_object_risk::create([
                    'policy_version_id'         => $newVersion->id,
                    'object_id'                 => $riskData['object_id'] ?? null,
                    'coverage_id'               => $riskData['coverage_id'] ?? null,
                    'source_quo_object_risk_id' => $riskData['source_quo_object_risk_id'] ?? null,
                    'dp_capital'                => $riskData['dp_capital'] ?? null,
                    'rc_capital'                => $riskData['rc_capital'] ?? null,
                    'building_capital'          => $riskData['building_capital'] ?? null,
                    'content_capital'           => $riskData['content_capital'] ?? null,
                    'exploration_capital'       => $riskData['exploration_capital'] ?? null,
                    'capital'                   => $riskData['capital'] ?? null,
                    'coverage_perc'             => $riskData['coverage_perc'] ?? 0,
                    'applied_rate'              => $riskData['applied_rate'] ?? null,
                    'coverage_premium'          => $riskData['coverage_premium'] ?? null,
                    'rate_origin'               => $riskData['rate_origin'] ?? null,
                    'rate_id'                   => $riskData['rate_id'] ?? null,
                    'discount_percent'          => $riskData['discount_percent'] ?? null,
                    'surcharge_percent'         => $riskData['surcharge_percent'] ?? null,
                    'status'                    => $riskData['status'] ?? 'Active',
                    'effective_date'            => $riskData['effective_date'] ?? $data['effective_date'],
                    'end_date'                  => $riskData['end_date'] ?? $activeVersion->expiry_date,
                    'slug'                      => Str::slug('pr-' . $policy->id . '-' . uniqid()),
                    'created_at'                => now(),
                ]);
            }

            // Supersede versão anterior
            $activeVersion->update(['status' => 'Superseded']);

            // Movimento de endosso
            POL_movements::create([
                'policy_id'          => $policy->id,
                'from_version_id'    => $activeVersion->id,
                'to_version_id'      => $newVersion->id,
                'movement_type'      => 'Amendment',
                'movement_subtype'   => $data['subtype'] ?? null,
                'movement_date'      => now()->toDateString(),
                'effective_date'     => $data['effective_date'],
                'premium_difference' => $data['premium_difference'] ?? 0,
                'reason'             => $data['reason'] ?? null,
                'status'             => 'Pending',
                'created_by'         => $data['created_by'] ?? auth()->id(),
                'created_at'         => now(),
            ]);

            $snapshot = $policy->load('versions.objectRisks')->toArray();
            $newVersion->update(['snapshot_json' => $snapshot]);

            return $this->successResponse($newVersion, 'Endosso criado.', 201);
        });
    }

    public function cancelPolicy(int $id, array $data): array
    {
        $policy = POL_policies::find($id);
        if (!$policy) return $this->errorResponse('Apólice não encontrada.', 404);
        $policy->update(['status' => 'Cancelled']);
        $activeVersion = $policy->activeVersion;
        if ($activeVersion) $activeVersion->update(['status' => 'Cancelled']);
        return $this->successResponse($policy, 'Apólice cancelada.');
    }

    private function generatePolicyNumber(int $branchId): string
    {
        $branch = \App\Models\CFG\CFG_insurance_branches::find($branchId);
        $prefix = $branch?->policy_number_prefix ?? $branch?->code ?? 'POL';
        $year   = now()->year;
        $count  = POL_policies::whereYear('created_at', $year)
            ->where('insurance_branch_id', $branchId)->count() + 1;
        return sprintf('%s-%d-%05d', $prefix, $year, $count);
    }
}
