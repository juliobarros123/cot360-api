<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CaeRatesBranch;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Str;

class ImportCaeRates extends Command
{
    protected $signature = 'import:cae-rates {file}';
    protected $description = 'Import CAE rates from Excel file (insert only if not exists)';

    public function handle()
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return;
        }

        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getSheetByName('Taxas_AT');

        if (!$worksheet) {
            $this->error("Worksheet 'Taxas_AT' not found in the file");
            return;
        }

        $rows = $worksheet->toArray();
        $header = array_shift($rows); // Remove header row

        $total = 0;
        $skipped = 0;
        $exists = 0;
        // dd(collect(value: $rows)->take(20));
        foreach ($rows as $row) {
            // Skip empty rows
            if (empty($row[0])) {
                continue;
            }

            // Skip rows with "Tarifação" or "Aceitação" headers
            if (in_array($row[0], ['Tarifação', 'Aceitação', 'Nivel I', 'AD'])) {
                continue;
            }

            $cae = trim($row[0]);
            $description = trim($row[1]);
            $rate = $this->parseRate($row[2]);

            // Skip if rate is not numeric or is "Tarifa Própria"
            if (!is_numeric($rate) && $rate !== 'Tarifa Própria') {
                $skipped++;
                continue;
            }

            // Check if record already exists
            if (CaeRatesBranch::where('cae', $cae)->exists()) {
                $exists++;
                continue;
            }

            // Special handling for "Tarifa Própria"
            $rateValue = ($rate === 'Tarifa Própria') ? null : (float) $rate;

            try {
                CaeRatesBranch::create([
                    'cae' => $cae,
                    'description' => $description,
                    'rate_mre_building' => null,
                    'rate_mre_content' => null,
                    'work_accident' => $rateValue,
                    'active' => true,
                    'slug' => Str::slug($cae . '-' . $description),
                ]);
                $total++;
            } catch (\Exception $e) {
                $this->error("Error importing CAE {$cae}: " . $e->getMessage());
                $skipped++;
            }
        }

        $this->info("Import completed. {$total} new records inserted, {$exists} already existed, {$skipped} skipped.");
    }

    private function parseRate($rate)
    {
        if (empty($rate) || in_array($rate, ['AD', 'R/D'])) {
            return null;
        }
        return $rate;
    }

    private function getWorkAccidentRate($description)
    {
        // Special cases for work accident rates
        if (Str::contains($description, 'Empregadas Domésticas')) {
            return 1.5;
        } elseif (Str::contains($description, 'Jardineiros')) {
            return 2.0;
        } elseif (Str::contains($description, 'Motoristas')) {
            return 3.0;
        } elseif (Str::contains($description, 'Seguranças privados')) {
            return 6.5;
        }

        return null; // Default case
    }
}