<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RatesAutomobile;
use App\Services\RateReplicationService;
use Illuminate\Support\Facades\Validator;

class RateManager extends Command
{
    protected $signature = 'rate:manager 
                            {action : Operação (list|show|create|update|delete|replicate|bulk-replicate|bulk-update|bulk-delete)}
                            {id? : ID do registro (para show, update, delete, replicate)}
                            {--ids= : IDs para operações em lote (ex: 1,2,3 ou 1-5)}
                            {--insurance_branch_id= : insurance_branch_id}
                            {--code= : Código}
                            {--description= : Descrição}
                            {--vehicle_type= : Tipo de veículo}
                            {--category= : Categoria}
                            {--engine_min= : Motor mínimo}
                            {--engine_max= : Motor máximo}
                            {--seat_min= : Lugares mínimo}
                            {--seat_max= : Lugares máximo}
                            {--weight_min= : Peso mínimo}
                            {--weight_max= : Peso máximo}
                            {--min_rc_legal_ucf= : Mínimo RC Legal UCF}
                            {--min_rc_limit_kz= : Mínimo RC Limite KZ}
                            {--rc_premium_kz= : Prémio RC KZ}
                            {--ccc_percent= : CCC Percent}
                            {--ire_percent= : IRE Percent}
                            {--fr_percent= : FR Percent}
                            {--av_percent= : AV Percent}
                            {--fn_percent= : FN Percent}
                            {--gtaop_percent= : GTAOP Percent}
                            {--active= : Ativo (true/false)}
                            {--prefix= : Prefixo para descrição}
                            {--suffix= : Sufixo para descrição}
                            {--force : Forçar exclusão sem confirmação}';
    
    protected $description = 'Gerenciamento completo de rates_automobiles (CRUD + Replicação)';

    public function handle()
    {
        $action = $this->argument('action');
        
        switch ($action) {
            case 'list':
                return $this->listRates();
            case 'show':
                return $this->showRate();
            case 'create':
                return $this->createRate();
            case 'update':
                return $this->updateRate();
            case 'delete':
                return $this->deleteRate();
            case 'replicate':
                return $this->replicateRate();
            case 'bulk-replicate':
                return $this->bulkReplicate();
            case 'bulk-update':
                return $this->bulkUpdate();
            case 'bulk-delete':
                return $this->bulkDelete();
            default:
                $this->error("Ação não reconhecida: {$action}");
                $this->info("Ações disponíveis: list, show, create, update, delete, replicate, bulk-replicate, bulk-update, bulk-delete");
                return 1;
        }
    }

    /**
     * Listar todos os registros
     */
    private function listRates()
    {
        $rates = RatesAutomobile::select('id', 'code', 'description', 'insurance_branch_id', 'fn_percent', 'active', 'created_at')
            ->get()
            ->map(function ($rate) {
                return [
                    'ID' => $rate->id,
                    'Código' => $rate->code,
                    'Descrição' => $rate->description,
                    'Branch ID' => $rate->insurance_branch_id,
                    'FN %' => $rate->fn_percent,
                    'Ativo' => $rate->active ? '✅' : '❌',
                    'Criado em' => $rate->created_at ? $rate->created_at->format('d/m/Y') : 'N/A'
                ];
            })->toArray();

        $this->table(['ID', 'Código', 'Descrição', 'Branch ID', 'FN %', 'Ativo', 'Criado em'], $rates);
        $this->info("Total de registros: " . count($rates));
        return 0;
    }

    /**
     * Mostrar detalhes de um registro
     */
    private function showRate()
    {
        $id = $this->argument('id');
        
        if (!$id) {
            $this->error("ID é obrigatório para show");
            return 1;
        }

        $rate = RatesAutomobile::find($id);
        
        if (!$rate) {
            $this->error("Registro com ID {$id} não encontrado!");
            return 1;
        }

        $this->info("📋 Detalhes do Registro #{$id}");
        $this->line("----------------------------------------");
        
        $details = [
            'Código' => $rate->code,
            'Descrição' => $rate->description,
            'Branch ID' => $rate->insurance_branch_id,
            'Tipo Veículo' => $rate->vehicle_type,
            'Categoria' => $rate->category,
            'Motor (min-max)' => "{$rate->engine_min} - {$rate->engine_max}",
            'Lugares (min-max)' => "{$rate->seat_min} - {$rate->seat_max}",
            'Peso (min-max)' => "{$rate->weight_min} - {$rate->weight_max}",
            'FN Percent' => $rate->fn_percent,
            'CCC Percent' => $rate->ccc_percent,
            'Ativo' => $rate->active ? 'Sim' : 'Não',
            'Slug' => $rate->slug,
            'Criado em' => $rate->created_at->format('d/m/Y H:i'),
            'Atualizado em' => $rate->updated_at->format('d/m/Y H:i')
        ];

        foreach ($details as $key => $value) {
            $this->line("{$key}: {$value}");
        }

        return 0;
    }

    /**
     * Criar novo registro
     */
    private function createRate()
    {
        $data = $this->getOptionsData();
        
        $validator = Validator::make($data, [
            'code' => 'required|unique:rates_automobiles,code',
            'description' => 'required',
            'insurance_branch_id' => 'required|exists:insurance_branches,id'
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return 1;
        }

        try {
            $rate = RatesAutomobile::create($data);
            $this->info("✅ Registro criado com sucesso! ID: {$rate->id}");
            return 0;
        } catch (\Exception $e) {
            $this->error("❌ Erro ao criar registro: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Atualizar registro
     */
    private function updateRate()
    {
        $id = $this->argument('id');
        
        if (!$id) {
            $this->error("ID é obrigatório para update");
            return 1;
        }

        $rate = RatesAutomobile::find($id);
        
        if (!$rate) {
            $this->error("Registro com ID {$id} não encontrado!");
            return 1;
        }

        $data = $this->getOptionsData();
        
        if (empty($data)) {
            $this->error("Nenhum dado fornecido para atualização");
            return 1;
        }

        try {
            $rate->update($data);
            $this->info("✅ Registro #{$id} atualizado com sucesso!");
            return 0;
        } catch (\Exception $e) {
            $this->error("❌ Erro ao atualizar registro: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Deletar registro
     */
    private function deleteRate()
    {
        $id = $this->argument('id');
        
        if (!$id) {
            $this->error("ID é obrigatório para delete");
            return 1;
        }

        $rate = RatesAutomobile::find($id);
        
        if (!$rate) {
            $this->error("Registro com ID {$id} não encontrado!");
            return 1;
        }

        if (!$this->option('force') && !$this->confirm("⚠️  Confirma exclusão do registro #{$id} - {$rate->code}?", false)) {
            $this->info("Operação cancelada");
            return 0;
        }

        try {
            $rate->delete();
            $this->info("✅ Registro #{$id} excluído com sucesso!");
            return 0;
        } catch (\Exception $e) {
            $this->error("❌ Erro ao excluir registro: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Replicar registro
     */
    private function replicateRate()
    {
        $id = $this->argument('id');
        
        if (!$id) {
            $this->error("ID é obrigatório para replicate");
            return 1;
        }

        $rate = RatesAutomobile::find($id);
        
        if (!$rate) {
            $this->error("Registro com ID {$id} não encontrado!");
            return 1;
        }

        $changes = $this->getOptionsData();
        $service = new RateReplicationService();

        try {
            $replica = $service->replicateRate($rate, $changes);
            $this->info("✅ Registro replicado com sucesso! Novo ID: {$replica->id}");
            return 0;
        } catch (\Exception $e) {
            $this->error("❌ Erro ao replicar registro: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Replicação em massa
     */
    private function bulkReplicate()
    {
        $idsInput = $this->option('ids'); // CORREÇÃO: usar option() em vez de argument()
        
        if (!$idsInput) {
            $this->error("IDs são obrigatórios para bulk-replicate. Use --ids=\"1-82\"");
            return 1;
        }

        $ids = $this->parseIds($idsInput);
        $changes = $this->getOptionsData();
        $service = new RateReplicationService();

        $this->info("🔄 Replicando " . count($ids) . " registros...");
        $bar = $this->output->createProgressBar(count($ids));

        $successCount = 0;
        $failedIds = [];

        foreach ($ids as $id) {
            try {
                $rate = RatesAutomobile::find($id);
                
                if ($rate) {
                    $service->replicateRate($rate, $changes);
                    $successCount++;
                } else {
                    $failedIds[] = $id;
                }
            } catch (\Exception $e) {
                $failedIds[] = $id;
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);

        $this->info("✅ {$successCount} registros replicados com sucesso!");
        
        if (!empty($failedIds)) {
            $this->warn("❌ Falha em " . count($failedIds) . " registros: " . implode(', ', $failedIds));
        }

        return 0;
    }

    /**
     * Atualização em massa
     */
    private function bulkUpdate()
    {
        $idsInput = $this->option('ids'); // CORREÇÃO: usar option() em vez de argument()
        
        if (!$idsInput) {
            $this->error("IDs são obrigatórios para bulk-update. Use --ids=\"1,2,3\"");
            return 1;
        }

        $ids = $this->parseIds($idsInput);
        $data = $this->getOptionsData();
        
        if (empty($data)) {
            $this->error("Nenhum dado fornecido para atualização");
            return 1;
        }

        $this->info("🔄 Atualizando " . count($ids) . " registros...");
        $bar = $this->output->createProgressBar(count($ids));

        $successCount = 0;
        $failedIds = [];

        foreach ($ids as $id) {
            try {
                $rate = RatesAutomobile::find($id);
                
                if ($rate) {
                    $rate->update($data);
                    $successCount++;
                } else {
                    $failedIds[] = $id;
                }
            } catch (\Exception $e) {
                $failedIds[] = $id;
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);

        $this->info("✅ {$successCount} registros atualizados com sucesso!");
        
        if (!empty($failedIds)) {
            $this->warn("❌ Falha em " . count($failedIds) . " registros: " . implode(', ', $failedIds));
        }

        return 0;
    }

    /**
     * Exclusão em massa
     */
    private function bulkDelete()
    {
        $idsInput = $this->option('ids'); // CORREÇÃO: usar option() em vez de argument()
        
        if (!$idsInput) {
            $this->error("IDs são obrigatórios para bulk-delete. Use --ids=\"1-5\"");
            return 1;
        }

        $ids = $this->parseIds($idsInput);

        if (!$this->option('force') && !$this->confirm("⚠️  Confirma exclusão de " . count($ids) . " registros?", false)) {
            $this->info("Operação cancelada");
            return 0;
        }

        $this->info("🗑️  Excluindo " . count($ids) . " registros...");
        $bar = $this->output->createProgressBar(count($ids));

        $successCount = 0;
        $failedIds = [];

        foreach ($ids as $id) {
            try {
                $rate = RatesAutomobile::find($id);
                
                if ($rate) {
                    $rate->delete();
                    $successCount++;
                } else {
                    $failedIds[] = $id;
                }
            } catch (\Exception $e) {
                $failedIds[] = $id;
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);

        $this->info("✅ {$successCount} registros excluídos com sucesso!");
        
        if (!empty($failedIds)) {
            $this->warn("❌ Falha em " . count($failedIds) . " registros: " . implode(', ', $failedIds));
        }

        return 0;
    }

    /**
     * Métodos auxiliares
     */
    private function parseIds(string $idsInput): array
    {
        if (str_contains($idsInput, ',')) {
            return array_map('intval', explode(',', $idsInput));
        }
        
        if (str_contains($idsInput, '-')) {
            [$start, $end] = explode('-', $idsInput);
            return range((int)$start, (int)$end);
        }
        
        return [(int)$idsInput];
    }

    private function getOptionsData(): array
    {
        $data = [];
        $options = [
            'insurance_branch_id', 'code', 'description', 'vehicle_type', 'category',
            'engine_min', 'engine_max', 'seat_min', 'seat_max', 'weight_min', 'weight_max',
            'min_rc_legal_ucf', 'min_rc_limit_kz', 'rc_premium_kz', 'ccc_percent', 'ire_percent',
            'fr_percent', 'av_percent', 'fn_percent', 'gtaop_percent', 'active'
        ];
        
        foreach ($options as $option) {
            if ($this->option($option) !== null) {
                $value = $this->option($option);
                
                if ($option === 'active') {
                    $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                }
                
                if (str_contains($option, '_percent')) {
                    $value = (float)$value;
                }
                
                if (in_array($option, ['engine_min', 'engine_max', 'seat_min', 'seat_max'])) {
                    $value = (int)$value;
                }
                
                $data[$option] = $value;
            }
        }
        
        return $data;
    }
}