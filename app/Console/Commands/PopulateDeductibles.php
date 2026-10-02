<?php

namespace App\Console\Commands;

use App\Models\CFG\CFG_deductibles;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PopulateDeductibles extends Command
{
    protected $signature = 'deductibles:populate';
    protected $description = 'Popula os deductibles para cada cobertura baseado nos percentuais de dedução';

    public function handle()
    {
        $this->info('Iniciando população de deductibles...');

        // Obtém os dados das coberturas
        $coverages = $this->templatesData();

        if ($coverages->isEmpty()) {
            $this->error('Nenhuma cobertura encontrada!');
            return;
        }

        $this->info("Encontradas {$coverages->count()} coberturas para processar");

        $deductiblesCreated = 0;
        $processedCoverages = []; // Para controlar coberturas já processadas

        foreach ($coverages as $coverage) {
            // Evita processar a mesma cobertura múltiplas vezes
            if (in_array($coverage->id, $processedCoverages)) {
                continue;
            }

            $deductiblesCreated += $this->createDeductiblesForCoverage($coverage);
            $processedCoverages[] = $coverage->id;
        }

        $this->info("Processo concluído! {$deductiblesCreated} deductibles criados.");
    }

    private function templatesData()
    {
        return DB::table('cfg_templates')
            ->select(
                'cfg_coverages.id',
                'cfg_coverages.short_name',
                'cfg_coverages.name',
                'cfg_coverages.insurance_branch_id',
                'cfg_insurance_branches.name as branch_name'
            )
            ->join('cfg_insurance_branches', 'cfg_templates.insurance_branch_id', '=', 'cfg_insurance_branches.id')
            ->join('cfg_template_coverage', 'cfg_templates.id', '=', 'cfg_template_coverage.template_id')
            ->join('cfg_coverages', 'cfg_template_coverage.coverage_id', '=', 'cfg_coverages.id')
            ->join('cfg_insurance_branches as coverage_branches', 'cfg_coverages.insurance_branch_id', '=', 'coverage_branches.id')
            ->where('cfg_templates.active', true)
            ->where('cfg_coverages.active', true)
            ->where('cfg_insurance_branches.active', true)
            ->where('coverage_branches.active', true)
            ->whereIn('cfg_insurance_branches.code', [90, 91])
            ->whereIn('cfg_coverages.short_name', ['C.C.C.', 'I.R.E.', 'ACTOS VAND.'])
            ->whereNull('cfg_templates.deleted_at')
            ->whereNull('cfg_coverages.deleted_at')
            ->whereNull('cfg_insurance_branches.deleted_at')
            ->whereNull('coverage_branches.deleted_at')
            ->distinct()
            ->get();
    }

    private function createDeductiblesForCoverage($coverage)
    {
        // ELIMINA os deductibles existentes para esta cobertura
        $deletedCount = DB::table('cfg_deductibles')
            ->where('coverage_id', $coverage->id)
            ->delete();

        if ($deletedCount > 0) {
            $this->warn("Removidos {$deletedCount} deductibles existentes para cobertura ID: {$coverage->id}");
        }

        $deductiblesData = [];

        // Gera os deductibles de 2 em 2 até 16 para deduct_percent
        for ($deductPercent = 2; $deductPercent <= 16; $deductPercent += 2) {
            // Calcula o discount: a cada 2% de deduct_percent, aumenta 5% no discount
            $discount = (($deductPercent / 2) - 1) * 5;

            $deductiblesData[] = [
                'coverage_id' => $coverage->id,
                'deduct_percent' => $deductPercent,
                'deduct_object' => 'CLAIM',
                'deduct_min' => null,
                'deduct_max' => null,
                'deduct_value' => null,
                'discount' => $discount,
                'deduct_type' => 'PERCENTAGE',
                'details' => "{$deductPercent}% do valor Sinistro",
                'slug' => $coverage->id . '-' . $deductPercent . '-' . Str::random(8),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Insere os novos deductibles no banco de dados
        try {
            DB::table('cfg_deductibles')->insert($deductiblesData);
            $this->info("Criados " . count($deductiblesData) . " deductibles para cobertura ID: {$coverage->id} ({$coverage->short_name})");
            return count($deductiblesData);
        } catch (\Exception $e) {
            $this->error("Erro ao criar deductibles para cobertura ID: {$coverage->id} - " . $e->getMessage());
            return 0;
        }
    }
}
