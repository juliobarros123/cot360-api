<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class UpdateRatesAutomobileBranchId extends Command
{
    protected $signature = 'migrate:update-rates-auto-branch 
                            {--dry-run : Simula a atualização sem gravar nada}
                            {--chunk=200 : Número de registos por lote}
                            {--branch-id= : Filtrar por branch ID específico (12 ou 13)}
                            {--force : Força atualização mesmo com códigos problemáticos}';

    protected $description = 'Atualiza o campo insurance_branch_id na tabela cfg_rates_automobile baseado na ordem dos registros';

    private string $OLD = 'mysql_old';
    private string $NEW = 'mysql';
    
    private array $branchMap = [];
    private array $stats = [
        'updated' => 0,
        'not_found' => 0,
        'multiple_matches' => 0,
        'no_match' => 0,
        'errors' => 0
    ];

    public function handle(): int
    {
        $this->info('');
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║   ATUALIZAR INSURANCE_BRANCH_ID - RATES AUTOMOBILE           ║');
        $this->info('║   (Baseado na ordem de criação - branch 12 e 13)             ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->info('');

        if ($this->option('dry-run')) {
            $this->warn('⚠  MODO DRY-RUN — nenhuma alteração será gravada.');
            $this->info('');
        }

        if (!$this->testConnections()) {
            return self::FAILURE;
        }

        // Carregar mapeamento de branches
        $this->loadBranchMap();

        // Primeiro, mostrar exemplo de como os dados estão organizados
        $this->showDataSample();

        // Atualizar os registros
        $this->updateRatesAutomobile();

        $this->printSummary();

        return self::SUCCESS;
    }

    private function loadBranchMap(): void
    {
        $this->info('Carregando mapeamento de branches...');

        $branches = DB::connection($this->NEW)
            ->table('cfg_insurance_branches')
            ->get(['id', 'code']);

        foreach ($branches as $branch) {
            $this->branchMap[$branch->code] = $branch->id;
        }

        $this->info(sprintf('✓ %d branches carregados', count($this->branchMap)));
        $this->info('');
    }

    private function showDataSample(): void
    {
        $this->info('Exemplo de como os dados estão organizados:');
        
        // Mostrar alguns códigos com seus registros no banco novo
        $samples = DB::connection($this->NEW)
            ->table('cfg_rates_automobile')
            ->select('code', DB::raw('COUNT(*) as count'))
            ->whereNotNull('code')
            ->groupBy('code')
            ->having('count', '>', 1)
            ->orderBy('code')
            ->limit(5)
            ->get();

        foreach ($samples as $sample) {
            $this->line("  Código: '{$sample->code}' - {$sample->count} registros");
            
            $records = DB::connection($this->NEW)
                ->table('cfg_rates_automobile')
                ->where('code', $sample->code)
                ->orderBy('created_at')
                ->get(['id', 'created_at', 'insurance_branch_id']);

            foreach ($records as $i => $record) {
                $this->line(sprintf(
                    "    → [%d] ID: %d | Criado: %s | Branch atual: %s",
                    $i + 1,
                    $record->id,
                    $record->created_at,
                    $record->insurance_branch_id ?? 'NULL'
                ));
            }
        }
        $this->info('');
    }

    private function updateRatesAutomobile(): void
    {
        $dryRun = (bool) $this->option('dry-run');
        $chunkSize = (int) $this->option('chunk');
        $specificBranchId = $this->option('branch-id');
        $force = (bool) $this->option('force');

        // Buscar todos os códigos únicos no banco NOVO
        $query = DB::connection($this->NEW)
            ->table('cfg_rates_automobile')
            ->select('code')
            ->whereNotNull('code')
            ->groupBy('code');

        if (!$force) {
            // Por padrão, só processar códigos com exatamente 2 registros
            $query->havingRaw('COUNT(*) = 2');
        } else {
            // Com force, processar todos os códigos
            $query->havingRaw('COUNT(*) IN (1,2)');
        }

        $codes = $query->pluck('code');

        $total = $codes->count();
        
        if ($total === 0) {
            $this->warn('Nenhum código com registros encontrado!');
            return;
        }

        $this->info(sprintf('Códigos a processar: %d', $total));
        $this->info('');

        $bar = $this->output->createProgressBar($total);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');
        $bar->start();

        foreach ($codes as $code) {
            try {
                // Limpar o código (remover espaços extras)
                $cleanCode = trim($code);
                $bar->setMessage("Código: {$cleanCode}");

                // Buscar os registros do banco NOVO para este código
                $newRates = DB::connection($this->NEW)
                    ->table('cfg_rates_automobile')
                    ->where('code', $code)
                    ->orderBy('created_at', 'asc')
                    ->get();

                // Buscar os registros correspondentes no banco ANTIGO
                $oldRates = DB::connection($this->OLD)
                    ->table('rates_automobiles')
                    ->where('code', $cleanCode)
                    ->orderBy('created_at', 'asc')
                    ->get();

                // Se não encontrar com código limpo, tentar like para casos especiais
                if ($oldRates->isEmpty() && $force) {
                    $oldRates = DB::connection($this->OLD)
                        ->table('rates_automobiles')
                        ->where('code', 'LIKE', '%' . $cleanCode . '%')
                        ->orderBy('created_at', 'asc')
                        ->get();
                }

                // Verificar se o número de registros corresponde
                if ($oldRates->count() !== $newRates->count()) {
                    if (!$force) {
                        $this->stats['multiple_matches']++;
                        $bar->advance();
                        continue;
                    }
                    
                    // Com force, processar apenas os que têm correspondência
                    $min = min($newRates->count(), $oldRates->count());
                    if ($min === 0) {
                        $this->stats['not_found']++;
                        $bar->advance();
                        continue;
                    }
                    
                    // Truncar para o mínimo
                    $newRates = $newRates->slice(0, $min);
                    $oldRates = $oldRates->slice(0, $min);
                }

                // Se foi especificado um branch ID, filtrar os registros antigos
                if ($specificBranchId) {
                    $oldRates = $oldRates->filter(function ($rate) use ($specificBranchId) {
                        return $rate->insurance_branch_id == $specificBranchId;
                    });
                    
                    if ($oldRates->isEmpty()) {
                        $this->stats['not_found']++;
                        $bar->advance();
                        continue;
                    }
                }

                // Fazer o matching baseado na ordem de criação
                foreach ($newRates as $index => $newRate) {
                    if (!isset($oldRates[$index])) {
                        continue;
                    }

                    $oldRate = $oldRates[$index];
                    
                    // Buscar o branch_code no banco antigo
                    $branch = DB::connection($this->OLD)
                        ->table('insurance_branches')
                        ->where('id', $oldRate->insurance_branch_id)
                        ->first();

                    if (!$branch || empty($branch->code)) {
                        $this->stats['no_match']++;
                        continue;
                    }

                    // Encontrar o novo branch_id pelo código
                    $newBranchId = $this->branchMap[$branch->code] ?? null;

                    if (!$newBranchId) {
                        $this->stats['no_match']++;
                        continue;
                    }

                    // Log para debug (só mostrar alguns exemplos em dry-run)
                    if ($this->option('dry-run') && $index < 5) {
                        $this->line(sprintf(
                            "\n  Code: %s | Old ID: %d (branch: %s, created: %s) → New ID: %d (created: %s)",
                            $cleanCode,
                            $oldRate->id,
                            $branch->code,
                            $oldRate->created_at,
                            $newRate->id,
                            $newRate->created_at
                        ));
                    }

                    // Atualizar o registro no novo banco
                    if (!$dryRun) {
                        DB::connection($this->NEW)
                            ->table('cfg_rates_automobile')
                            ->where('id', $newRate->id)
                            ->update([
                                'insurance_branch_id' => $newBranchId,
                                'updated_at' => now()
                            ]);
                    }

                    $this->stats['updated']++;
                }

            } catch (Throwable $e) {
                $this->stats['errors']++;
                $this->newLine();
                $this->error("   Erro ao processar código {$code}: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
    }

    private function printSummary(): void
    {
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║   RESUMO DA ATUALIZAÇÃO                                      ║');
        $this->info('╠══════════════════════════════════════════════════════════════╣');
        $this->info("║  Atualizados:            " . str_pad($this->stats['updated'], 20, ' ', STR_PAD_LEFT) . " ║");
        $this->info("║  Não encontrados:        " . str_pad($this->stats['not_found'], 20, ' ', STR_PAD_LEFT) . " ║");
        $this->info("║  Múltiplos matches:      " . str_pad($this->stats['multiple_matches'], 20, ' ', STR_PAD_LEFT) . " ║");
        $this->info("║  Sem match:              " . str_pad($this->stats['no_match'], 20, ' ', STR_PAD_LEFT) . " ║");
        $this->info("║  Erros:                  " . str_pad($this->stats['errors'], 20, ' ', STR_PAD_LEFT) . " ║");
        $this->info('╚══════════════════════════════════════════════════════════════╝');

        if ($this->option('dry-run')) {
            $this->warn('⚠  DRY-RUN: nenhuma alteração foi gravada.');
        }

        // Se houver códigos problemáticos, sugerir usar --force
        if ($this->stats['not_found'] > 0 || $this->stats['multiple_matches'] > 0) {
            $this->info('');
            $this->warn('Dica: Use --force para processar códigos com número diferente de registros');
            $this->line('  php artisan migrate:update-rates-auto-branch --force');
        }
    }

    private function testConnections(): bool
    {
        $this->line('Testando conexões...');

        try {
            DB::connection($this->OLD)->getPdo();
            $this->line('  <fg=green>✓ Banco ANTIGO: ' . config("database.connections.{$this->OLD}.database") . '</>');
        } catch (Throwable $e) {
            $this->error('  ✗ Banco ANTIGO falhou: ' . $e->getMessage());
            return false;
        }

        try {
            DB::connection($this->NEW)->getPdo();
            $this->line('  <fg=green>✓ Banco NOVO: ' . config("database.connections.{$this->NEW}.database") . '</>');
        } catch (Throwable $e) {
            $this->error('  ✗ Banco NOVO falhou: ' . $e->getMessage());
            return false;
        }

        $this->info('');
        return true;
    }
}