<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RatesAutomobile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Calculation\Calculation;
use Illuminate\Support\Str;

class ImportAutoFrotaTarifa extends Command
{
    protected $signature = 'import:autofrota {file} {--branch_id=}';
    protected $description = 'Importa dados da tarifa de automóvel frota a partir de um arquivo Excel';

    public function handle()
    {
        $filePath = $this->argument('file');
        $branchId = $this->option('branch_id');

        $fullPath = storage_path('app/' . $filePath);
        if (!file_exists(filename: $fullPath)) {
            $this->error("Arquivo não encontrado: $fullPath");
            return 1;
        }

        try {
            $spreadsheet = IOFactory::load($fullPath);
            $worksheet = $spreadsheet->getActiveSheet();
            
            // Força o cálculo das fórmulas
            $calculation = Calculation::getInstance($spreadsheet);
            $calculation->enableBranchPruning();
            
            $data = [];
            $highestRow = $worksheet->getHighestRow();
            $highestCol = $worksheet->getHighestColumn();
            
            for ($row = 1; $row <= $highestRow; $row++) {
                $rowData = [];
                for ($col = 'A'; $col <= $highestCol; $col++) {
                    $cell = $worksheet->getCell($col . $row);
                    $value = $cell->getCalculatedValue();
                    $rowData[] = $value;
                }
                $data[] = $rowData;
            }
            
            // Pular as duas primeiras linhas (cabeçalho)
            $rows = array_slice($data, 2);
            
            $this->info("Total de linhas no Excel: " . count($rows));
            
            $count = 0;
            $skipped = 0;

            foreach ($rows as $rowIndex => $row) {
                // Pular linhas vazias (verifica se a coluna B (código) está vazia)
                if (empty($row[1]) || trim($row[1]) === '') {
                    continue;
                }

                $code = trim($row[1]);
                $description = trim($row[2] ?? '');
                
                // Debug: mostrar dados da linha
                $this->info("Processando linha " . ($rowIndex + 3) . ": $code - $description");
                
                // Valores numéricos - manter EXATAMENTE como no Excel
                $minRcLegalUcf = $this->getExactNumericValue($row[3] ?? null);
                $minRcLimitKz = $this->getExactNumericValue($row[4] ?? null);
                $ucfPerCategory = $this->getExactNumericValue($row[5] ?? null);
                $rcPremiumSimpleLegalKz = $this->getExactNumericValue($row[6] ?? null);
                $rcDiscountBic = $this->getExactNumericValue($row[7] ?? null);
                $rcPremiumKz = $this->getExactNumericValue($row[8] ?? null);
                
                // Valores percentuais - manter EXATAMENTE como no Excel, sem conversão
                $cccPercent = $this->getExactNumericValue($row[9] ?? null)*100; // Ex: 0.0181 permanece 0.0181
                $irePercent = $this->getExactNumericValue($row[10] ?? null)*100; // Ex: 0.0020 permanece 0.0020
                $frPercent = $this->getExactNumericValue($row[11] ?? null)*100;  // Ex: 0.0035 permanece 0.0035
                $avPercent = $this->getExactNumericValue($row[12] ?? null)*100;  // Ex: 0.0012 permanece 0.0012
                $fnPercent = $this->getExactNumericValue($row[13] ?? null)*100;  // Ex: 0.0007 permanece 0.0007
                $gtaopPercent = $this->getExactNumericValue($row[14] ?? null)*100; // Ex: 0.0007 permanece 0.0007

                // Debug: mostrar valores extraídos
                $this->info("Valores extraídos:");
                $this->info("  minRcLegalUcf: " . var_export($minRcLegalUcf, true));
                $this->info("  minRcLimitKz: " . var_export($minRcLimitKz, true));
                $this->info("  ucfPerCategory: " . var_export($ucfPerCategory, true));
                $this->info("  rcPremiumSimpleLegalKz: " . var_export($rcPremiumSimpleLegalKz, true));
                $this->info("  rcDiscountBic: " . var_export($rcDiscountBic, true));
                $this->info("  rcPremiumKz: " . var_export($rcPremiumKz, true));
                $this->info("  cccPercent: " . var_export($cccPercent, true));
                $this->info("  irePercent: " . var_export($irePercent, true));
                $this->info("  frPercent: " . var_export($frPercent, true));
                $this->info("  avPercent: " . var_export($avPercent, true));
                $this->info("  fnPercent: " . var_export($fnPercent, true));
                $this->info("  gtaopPercent: " . var_export($gtaopPercent, true));

                // Extração da descrição
                $vehicleType = $this->extractVehicleType($description);
                $category = $this->extractCategory($description);
                $engineMin = $this->extractEngineMin($description);
                $engineMax = $this->extractEngineMax($description);
                $seatMin = $this->extractSeatMin($description);
                $seatMax = $this->extractSeatMax($description);
                $weightMin = $this->extractWeightMin($description);
                $weightMax = $this->extractWeightMax($description);
                $slug = Str::slug($description, '_', 'pt');

                // VERIFICAÇÃO DUPLA: Por código E por slug + branch_id
                $existsByCode = RatesAutomobile::where('code', $code)
                    ->where('insurance_branch_id', $branchId)
                    ->exists();

                $existsBySlug = RatesAutomobile::where('slug', $slug)
                    ->where('insurance_branch_id', $branchId)
                    ->exists();

                if ($existsByCode || $existsBySlug) {
                    $skipped++;
                    $this->warn("Registro duplicado ignorado - Código: $code, Descrição: $description");
                    continue;
                }

                // Cria novo registro
                RatesAutomobile::create([
                    'code' => $code,
                    'description' => $description,
                    'vehicle_type' => $vehicleType,
                    'category' => $category,
                    'engine_min' => $engineMin,
                    'engine_max' => $engineMax,
                    'seat_min' => $seatMin,
                    'seat_max' => $seatMax,
                    'weight_min' => $weightMin,
                    'weight_max' => $weightMax,
                    'min_rc_legal_ucf' => $minRcLegalUcf,
                    'min_rc_limit_kz' => $minRcLimitKz,
                    'ucf_per_category' => $ucfPerCategory,
                    'rc_premium_simple_legal_kz' => $rcPremiumSimpleLegalKz,
                    'rc_discount_bic' => $rcDiscountBic,
                    'rc_premium_kz' => $rcPremiumKz,
                    'ccc_percent' => $cccPercent,
                    'ire_percent' => $irePercent,
                    'fr_percent' => $frPercent,
                    'av_percent' => $avPercent,
                    'fn_percent' => $fnPercent,
                    'gtaop_percent' => $gtaopPercent,
                    'active' => true,
                    'slug' => $slug,
                    'insurance_branch_id' => $branchId,
                ]);

                $count++;
                
                $this->info("Registro inserido: $description");
            }

            $this->info("Importação concluída com sucesso!");
            $this->info("$count registros inseridos.");
            $this->info("$skipped registros já existentes e ignorados.");
            
            return 0;
        } catch (\Exception $e) {
            $this->error("Erro durante a importação: " . $e->getMessage());
            $this->error("Linha: " . $e->getLine());
            $this->error("Trace: " . $e->getTraceAsString());
            return 1;
        }
    }

    private function getExactNumericValue($value)
    {
        if (is_null($value) || $value === '') {
            return null; // Mantém vazio como null
        }
        
        if (is_numeric(value: $value)) {
            return $value; // Retorna o valor EXATO, sem qualquer conversão
        }
        
        if (is_string($value)) {
            // Valida como float, mas sem alterar o valor (ex: "0.0181" vira 0.0181)
            $floatValue = filter_var($value, FILTER_VALIDATE_FLOAT);
            if ($floatValue !== false) {
                return $floatValue; // Retorna o valor EXATO como float
            }
        }
        
        $this->warn("Valor não numérico encontrado: " . var_export($value, true));
        return null; // Retorna null para valores não numéricos
    }

    private function extractVehicleType($description)
    {
        $desc = strtoupper($description);
        if (str_contains($desc, 'LIGEIRO')) return 'Ligeiro';
        if (str_contains($desc, 'TAXI')) return 'Taxi';
        if (str_contains($desc, 'MISTO')) return 'Misto';
        if (str_contains($desc, 'CAMIONETA')) return 'Camioneta';
        if (str_contains($desc, 'CAMIAO') || str_contains($desc, 'CAMIÃO')) return 'Camião';
        if (str_contains($desc, 'AUTOCARRO')) return 'Autocarro';
        if (str_contains($desc, 'MOTOCICLO') || str_contains($desc, 'DUAS RODAS')) return 'DuasRodas';
        if (str_contains($desc, 'ATRELADOS') || str_contains($desc, 'REBOQUES')) return 'Atrelados';
        if (str_contains($desc, 'DIVERSOS')) return 'Diversos';
        return 'Outro';
    }

    private function extractCategory($description)
    {
        $desc = strtoupper($description);
        if (str_contains($desc, 'PARTICULAR')) return 'PARTICULAR';
        if (str_contains($desc, 'ALUGUER') || str_contains($desc, 'ALUGUEL')) return 'ALUGUER';
        if (str_contains($desc, 'S/CONDUTOR')) return 'ALUGUER S/CONDUTOR';
        if (str_contains($desc, 'TAXI COLECTIVO') || str_contains($desc, 'USO COLECTIVO')) return 'TAXI COLECTIVO';
        return 'OUTRO';
    }

    private function extractEngineMin($description)
    {
        preg_match('/ATÉ (\d+\.?\d*) CC/i', $description, $matches);
        if ($matches) return (float) str_replace('.', '', $matches[1]);
        
        preg_match('/ENTRE (\d+\.?\d*)CC/i', $description, $matches);
        if ($matches) return (float) str_replace('.', '', $matches[1]);
        
        preg_match('/DE (\d+\.?\d*) A/i', $description, $matches);
        if ($matches) return (float) str_replace('.', '', $matches[1]);
        
        return 0;
    }

    private function extractEngineMax($description)
    {
        preg_match('/ATÉ (\d+\.?\d*) CC/i', $description, $matches);
        if ($matches) return (float) str_replace('.', '', $matches[1]);
        
        preg_match('/ACIMA DE (\d+\.?\d*)CC/i', $description, $matches);
        if ($matches) return 99999;
        
        preg_match('/ENTRE \d+\.?\d*CC E (\d+\.?\d*)CC/i', $description, $matches);
        if ($matches) return (float) str_replace('.', '', $matches[1]);
        
        preg_match('/A (\d+\.?\d*) KGS/i', $description, $matches);
        if ($matches) return (float) str_replace('.', '', $matches[1]);
        
        return 99999;
    }

    private function extractSeatMin($description)
    {
        $desc = strtolower($description);
        if (str_contains($desc, 'até 9 lugares')) return 1;
        if (str_contains($desc, 'até 40 lug')) return 0;
        if (str_contains($desc, 'entre 41 até 90 lug')) return 41;
        if (str_contains($desc, '> 90 lug') || str_contains($desc, 'mais de 90')) return 91;
        return null;
    }

    private function extractSeatMax($description)
    {
        $desc = strtolower($description);
        if (str_contains($desc, 'até 9 lugares')) return 9;
        if (str_contains($desc, 'até 40 lug')) return 40;
        if (str_contains($desc, 'entre 41 até 90 lug')) return 90;
        if (str_contains($desc, '> 90 lug') || str_contains($desc, 'mais de 90')) return 300;
        return null;
    }

    private function extractWeightMin($description)
    {
        preg_match('/DE (\d+\.?\d*) KG/i', $description, $matches);
        if ($matches) return (float) str_replace('.', '', $matches[1]);
        
        preg_match('/DE (\d+\.?\d*) A/i', $description, $matches);
        if ($matches) return (float) str_replace('.', '', $matches[1]);
        
        preg_match('/ATÉ (\d+\.?\d*) KG/i', $description, $matches);
        if ($matches) return (float) str_replace('.', '', $matches[1]);
        
        return null;
    }

    private function extractWeightMax($description)
    {
        preg_match('/ATÉ (\d+\.?\d*) KG/i', $description, $matches);
        if ($matches) return (float) str_replace('.', '', $matches[1]);
        
        preg_match('/A (\d+\.?\d*) KGS/i', $description, $matches);
        if ($matches) return (float) str_replace('.', '', $matches[1]);
        
        preg_match('/ACIMA DE (\d+\.?\d*) KGS/i', $description, $matches);
        if ($matches) return 999999;
        
        preg_match('/DE \d+\.?\d* A (\d+\.?\d*) KGS/i', $description, $matches);
        if ($matches) return (float) str_replace('.', '', $matches[1]);
        
        return null;
    }
}