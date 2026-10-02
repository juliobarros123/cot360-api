<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CFG\CFG_rates_automobile;
use App\Models\CFG\CFG_rates_occupants;
use App\Models\CFG\CFG_rates_rc_options;
use App\Models\QUO\QUO_quotation_versions;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class QuotationExcelController extends Controller
{
    // =========================================================================
    // PUBLIC ENDPOINT
    // =========================================================================

    public function exportFrotaSemCondutor(int $versionId)
    {
        // dd("ola");
        $version = QUO_quotation_versions::with([
            'quotation.customer.addresses',
            'objectRisks.coverage',
            'objectRisks.object.vehicle',
        ])->findOrFail($versionId);

        // Build the spreadsheet 100 % from scratch — no template file required
        $spreadsheet = $this->buildSpreadsheet();
        $sheet = $spreadsheet->getSheetByName('Objectos');

        $escRcMap = $this->loadEscRcMap($spreadsheet);
        $provMap = $this->loadProvinceMap($spreadsheet);

        $quotation = $version->quotation;
        $customer = $quotation?->customer;
        $provinceCode = $this->resolveProvinceCode($customer?->addresses?->first(), $provMap);

        $objects = $version->objectRisks
            ->groupBy('object_id')
            ->map(fn($risks) => $risks->first()?->object)
            ->filter()
            ->values();

        // Data starts at row 6 (rows 1-5 are header / examples / format hints / separator)
        $currentRow = 6;
        foreach ($objects as $index => $object) {
            $vehicle = $object->vehicle;
            if (!$vehicle) {
                continue;
            }

            $risks = $version->objectRisks->where('object_id', $object->id);
            // if($object);
            // dd();

            $rcRisk = $risks->firstWhere('coverage.short_name', 'R.CIVIL OBR.');
            $dpRisk = $risks->firstWhere('coverage.short_name', 'DP_FROTA')
                ?? $risks->firstWhere('coverage.short_name', 'DP');
            $ocRisk = $risks->firstWhere('coverage.short_name', 'OC VIATURAS');
            $rcFacRisk = $risks->firstWhere('coverage.short_name', 'RC FACULTAT.');
            // dd($rcRisk);
            // DEPOIS
            // Resolver a rate uma única vez e guardar o objecto
            $resolvedRate = $this->resolveRate($rcRisk?->rate_id, $vehicle, $quotation?->insurance_branch_id, $object);
            $rateCode = $resolvedRate?->code;
            $uso = $this->resolveUsoCode($resolvedRate?->vehicle_type, $resolvedRate?->category);
            $rcFacRate = $rcFacRisk?->rate_id ? CFG_rates_rc_options::find($rcFacRisk->rate_id) : null;
            $escRc = $this->resolveEscRcCode($rcFacRate?->rc_fac, $escRcMap);
            $ocPackage = $this->resolveOcupantesCode($ocRisk?->rate_id);
            // $uso = $this->resolveUsoCode($vehicle->type, $vehicle->subcategory);

            $entryDate = $this->formatDateYmd($version->created_date ?? $version->created_at);
            $firstPlateDate = $this->formatDateYmd($vehicle->first_plate_date ?? $version->created_date ?? $version->created_at);

            $discount = $version->discount_percent ?? 0;
            $discountFormatted = number_format((float) $discount, 1, '.', '');

            $sheet->setCellValue("A{$currentRow}", $index + 1);
            $sheet->setCellValue("B{$currentRow}", $vehicle->plate ?? '');
            $sheet->setCellValue("C{$currentRow}", trim(($vehicle->brand ?? '') . ' ' . ($vehicle->model ?? '')));
            $sheet->setCellValue("D{$currentRow}", $entryDate);
            $sheet->setCellValue("E{$currentRow}", '');
            $sheet->setCellValue("F{$currentRow}", 0);
            $sheet->setCellValue("G{$currentRow}", 0);
            $sheet->setCellValue("H{$currentRow}", 'N');
            $sheet->setCellValue("I{$currentRow}", 'N');
            $sheet->setCellValue("J{$currentRow}", $ocPackage);
            $sheet->setCellValue("K{$currentRow}", $discountFormatted);
            $sheet->setCellValue("L{$currentRow}", $discountFormatted);
            $sheet->setCellValue("M{$currentRow}", $escRc ?? 'S');
            $sheet->setCellValue("N{$currentRow}", $rateCode ?? '');
            $sheet->setCellValue("O{$currentRow}", $provinceCode);
            $sheet->setCellValue("P{$currentRow}", $vehicle->chassis_number ?? '');
            $sheet->setCellValue("Q{$currentRow}", (float) ($vehicle->dp_capital ?? $vehicle->rc_capital ?? $dpRisk?->dp_capital ?? 0));
            $sheet->setCellValue("R{$currentRow}", '');
            $sheet->setCellValue("S{$currentRow}", $uso);
            $sheet->setCellValue("T{$currentRow}", $firstPlateDate);
            $sheet->setCellValue("U{$currentRow}", (int) ($vehicle->number_seats ?? 0));
            $sheet->setCellValue("V{$currentRow}", (float) ($vehicle->cylinder_cc ?? 0));
            $sheet->setCellValue("W{$currentRow}", (float) ($vehicle->weight ?? 0));
            $sheet->setCellValue("X{$currentRow}", (int) ($vehicle->year ?? 0));

            $currentRow++;
        }

        // Save to a unique temp file and stream to browser
        $tmpPath = storage_path('app/tmp/quotation_export_' . $versionId . '_' . time() . '.xls');
        if (!is_dir(dirname($tmpPath))) {
            mkdir(dirname($tmpPath), 0777, true);
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xls');
        $writer->save($tmpPath);

        $safeNumber = preg_replace('/[\/\\\\\(\) ]/', '_', $quotation?->quotation_number ?? (string) $versionId);

        return response()->download(
            $tmpPath,
            'TemplateFrota_sem_condutor_' . $safeNumber . '.xls',
            ['Content-Type' => 'application/vnd.ms-excel']
        )->deleteFileAfterSend(true);
    }

    // =========================================================================
    // SPREADSHEET BUILDER
    // =========================================================================

    /**
     * Build the entire workbook in memory, replicating the original template
     * structure and exact formatting without reading any file from disk.
     */
    private function buildSpreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        $this->buildSheetObjectos($spreadsheet);
        $this->buildReferenceSheet($spreadsheet, 'Tabela 913', [
            ['Valor', 'Descrição'],
            ['T', 'Taxi'],
            ['P', 'Particular'],
            ['A', 'Aluguer'],
        ]);
        $this->buildReferenceSheet($spreadsheet, 'Tabela 939', [
            ['Valor', 'Descrição'],
            ['C', '26.752.000,00'],
            ['B', '13.376.000,00'],
            ['S', 'Sem escalão'],
            ['A', '6.688.000,00'],
            ['F', '21.000.000,00'],
            ['E', '80.256.000,00'],
            ['D', '40.128.000,00'],
        ]);
        $this->buildReferenceSheet($spreadsheet, 'Tabela 100', [
            ['Valor', 'Descrição'],
            ['45.0', '45%   DESCONTO'],
            ['07.5', '07.5% DESCONTO'],
            ['42.5', '42.5% DESCONTO'],
            ['22.5', '22.5% DESCONTO'],
            ['17.5', '17.5% DESCONTO'],
            ['12.5', '12.5% DESCONTO'],
            ['00.0', 'SEM DESCONTO'],
            ['25.0', '25%   DESCONTO'],
            ['27.5', '27.5% DESCONTO'],
            ['50.0', '50%   DESCONTO'],
            ['05.0', '05%   DESCONTO'],
            ['32.5', '32.5% DESCONTO'],
            ['30.0', '30%   DESCONTO'],
            ['40.0', '40%   DESCONTO'],
            ['15.0', '15%   DESCONTO'],
            ['10.0', '10%   DESCONTO'],
            ['02.5', '02.5% DESCONTO'],
            ['37.5', '37.5% DESCONTO'],
            ['35.0', '35%   DESCONTO'],
            ['20.0', '20%   DESCONTO'],
            ['47.5', '47.5% DESCONTO'],
        ]);
        $this->buildReferenceSheet($spreadsheet, 'Tabela 928', [
            ['Valor', 'Descrição'],
            ['10', 'HUÍLA'],
            ['12', 'LUNDA NORTE'],
            ['03', 'BIÉ'],
            ['11', 'LUANDA'],
            ['02', 'BENGUELA'],
            ['14', 'MALANGE'],
            ['01', 'BENGO'],
            ['13', 'LUNDA SUL'],
            ['16', 'NAMIBE'],
            ['07', 'CUANZA SUL'],
            ['15', 'MOXICO'],
            ['06', 'CUANZA NORTE'],
            ['18', 'ZAIRE'],
            ['05', 'CUANDO CUBANGO'],
            ['17', 'UÍGE'],
            ['04', 'CABINDA'],
            ['09', 'HUAMBO'],
            ['08', 'CUNENE'],
        ]);
        $this->buildReferenceSheet($spreadsheet, 'Tabela 927', [
            ['Valor', 'Descrição'],
            ['S', 'Sem Ocupantes'],
            ['A', 'MIP -  5.500.000;DT -   500.000;DF -   500.000'], // era 5.000.000
            ['F', 'MIP -  1.000.000;DT -   200.000;DF -   200.000'],
            ['E', 'MIP -    500.000;DT -   100.000;DF -   100.000'], // era 250.000
        ]);
        $this->buildReferenceSheet($spreadsheet, 'Tabela 17', [
            ['Valor', 'Descrição'],
            ['N       ', 'Não'],
            ['S       ', 'Sim'],
        ]);
        $this->buildReferenceSheet($spreadsheet, 'Tabela 907', [
            ['Valor', 'Descrição'],
            ['29PX', 'PRONTO-SOCORRO - PESADO'],
            ['10G7', 'CAMIAO ALUGUER ACIMA DE 1.500 CC ATÉ 10.000 KGS'],
            ['11C6', 'PESADO ALUGUER S/CONDUTOR ENTRE 1.501CC E 2.500CC'],
            ['02D4', 'LIGEIRO ALUGUER ACIMA DE 2.500CC (até 9 lugares)'],
            ['08A8', 'CAMIAO PARTICULAR ATÉ 1.500 CC ACIMA DE 10.000 KGS'],
            ['08A7', 'CAMIAO PARTICULAR ATÉ 1.500 CC ATÉ 10.000 KGS'],
            ['27PX', 'AMBULANCIA - PESADO'],
            ['07A3', 'CAMIONETA PARTICULAR ATÉ 1500CC ATÉ 3600KG'],
            ['27LX', 'AMBULANCIA - LIGEIRO'],
            ['32PX', 'VEÍCULO PARA INSTRUÇAO E EXAME DE CONDUÇAO - PESADO'],
            ['05A4', 'TAXI DE USO COLECTIVO ATÉ 1.500CC'],
            ['29LX', 'PRONTO-SOCORRO - LIGEIRO'],
            ['01C4', 'LIGEIRO PARTICULAR ENTRE 1.601CC E 2.500CC (até 9 lugares)'],
            ['32LX', 'VEÍCULO PARA INSTRUÇAO E EXAME DE CONDUÇAO - LIGEIRO'],
            ['06A2', 'MISTO ATÉ 1.500CC, DE 650 KG ATÉ 1.100 KG'],
            ['03D4', 'LIGEIRO ALUGUER S/CONDUTOR ACIMA DE 2.500CC (até 9 lugares)'],
            ['12C6', 'AUTOCARRO PARTICULAR ENTRE 1.501CC E 2.500CC (até 40 lug)'],
            ['310X', 'MOTOCICLO PARA INSTRUÇAO E EXAME DE CONDUÇAO'],
            ['12C7', 'AUTOCARRO PARTICULAR ENTRE 1.501CC E 2.500CC (de 41 a 90 l.)'],
            ['04D4', 'TAXI ACIMA DE 2.500CC (até 9 lugares)'],
            ['12C8', 'AUTOCARRO PARTICULAR ENTRE 1.501CC E 2.500CC (> 90 lug)'],
            ['10C3', 'CAMIÃO DE ALUGUER ACIMA DE 2500CC, ATÉ 3600KG'],
            ['10G8', 'CAMIAO ALUGUER ACIMA DE 1.500 CC ACIMA DE 10.000 KGS'],
            ['350X', 'VEICULO DE BOMBEIROS'],
            ['02C4', 'LIGEIRO ALUGUER ENTRE 1.601CC E 2.500CC (até 9 lugares)'],
            ['190X', 'VEICULO AGRICOLA S/ MOTOR'],
            ['340X', 'VEICULO DE HIGIENE URBANA'],
            ['13C5', 'AUTOCARRO ALUGUER ENTRE 1.501CC E 2.500CC (> 90 lug)'],
            ['13C4', 'AUTOCARRO ALUGUER ENTRE 1.501CC E 2.500CC (de 41 a 90 l.)'],
            ['13C3', 'AUTOCARRO ALUGUER ENTRE 1.501CC E 2.500CC (até 40 lug)'],
            ['01D4', 'LIGEIRO PARTICULAR ACIMA DE 2.500CC (até 9 lugares)'],
            ['230X', 'VELOCÍPEDES'],
            ['04C4', 'TAXI ENTRE 1.501CC E 2.500CC (até 9 lugares)'],
            ['03C4', 'LIGEIRO ALUGUER S/CONDUTOR ENTRE 1.601CC E 2.500CC(até 9 l.)'],
            ['180X', 'VEICULO AGRICOLA C/ MOTOR'],
            ['240X', 'BICICLETA'],
            ['11A6', 'PESADO ALUGUER S/CONDUTOR ATÉ 1.500 CC'],
            ['150X', 'EMPILHADORA'],
            ['05C4', 'TAXI DE USO COLECTIVO ENTRE 1.501CC E 2.500CC'],
            ['02B4', 'LIGEIRO ALUGUER ATÉ 1.600CC (até 9 lugares)'],
            ['160X', 'ESCAVADORA/TERRA PLANADORA'],
            ['01B4', 'LIGEIRO PARTICULAR ATÉ 1.600CC (até 9 lugares)'],
            ['08G7', 'CAMIAO PARTICULAR ACIMA DE 1.500 CC ATÉ 10.000 KGS'],
            ['170X', 'GUINDASTE AUTOMOVEL'],
            ['08G8', 'CAMIAO PARTICULAR ACIMA DE 1.500 CC ACIMA DE 10.000 KGS'],
            ['10A7', 'CAMIAO ALUGUER ATÉ 1.500 CC ATÉ 10.000 KGS'],
            ['10A8', 'CAMIAO ALUGUER ATÉ 1.500 CC ACIMA DE 10.000 KGS'],
            ['07C3', 'CAMIONETA PARTICULAR ENTRE 1501CC E 2500CC ATÉ 3600KG'],
            ['06C2', 'MISTO ENTRE 1.501CC E 2.500CC, DE 650 KG ATÉ 1.100 KG'],
            ['03B4', 'LIGEIRO ALUGUER S/CONDUTOR ATÉ 1.600CC (até 9 lugares)'],
            ['09D3', 'CAMIONETA ALUGUER ACIMA DE 2.500CC ATÉ 3.600KG'],
            ['200X', 'DUMPER'],
            ['260X', 'TRACTOR INDUSTRIAL'],
            ['51LX', 'VEICULO ARTICULADO - LIGEIRO'],
            ['12A7', 'AUTOCARRO PARTICULAR ATÉ 1.500CC (de 41 a 90 lug)'],
            ['12A6', 'AUTOCARRO PARTICULAR ATÉ 1.500 CC (até 40 lug)'],
            ['2105', 'ATRELADOS/REBOQUES ACIMA DE 3.600 KGS'],
            ['13A8', 'AUTOCARRO ALUGUER ATÉ 1.500 CC (> 90 lug)'],
            ['13A7', 'AUTOCARRO ALUGUER ATÉ 1.500 CC (de 41 a 90 lug)'],
            ['05D4', 'TAXI DE USO COLECTIVO ACIMA DE 2.500CC'],
            ['2102', 'ATRELADOS/REBOQES DE 501 A 1.600 KGS'],
            ['51PX', 'VEICULO ARTICULADO - PESADO'],
            ['2101', 'ATRELADOS/REBOQUES ATÉ 500 KGS (REBOQUE)'],
            ['2103', 'ATRELADOS/REBOQUES DE 1.601 A 3.600 KGS'],
            ['07D3', 'CAMIONETA PARTICULAR ACIMA DE 2.500CC ATÉ 3600KG'],
            ['13A6', 'AUTOCARRO ALUGUER ATÉ 1.500 CC (até 40 lug)'],
            ['450X', 'VEICULO P/ TRANSPORTE DE MATERIAS PERIGOSAS'],
            ['11D6', 'PESADO ALUGUER S/CONDUTOR ACIMA DE 2.500CC'],
            ['500X', 'DIVERSOS'],
            ['12A8', 'AUTOCARRO PARTICULAR ATÉ 1.500 CC (> 90 lug)'],
            ['13D8', 'AUTOCARRO ALUGUER ACIMA DE 2500CC (> 90 lug)'],
            ['06D2', 'MISTO ACIMA DE 2.500CC, ATÉ 3.600KG'],
            ['13D6', 'AUTOCARRO ALUGUER ACIMA DE 2500CC (até 40 lug)'],
            ['13D7', 'AUTOCARRO ALUGUER ACIMA DE 2500CC (de 41 a90 lug)'],
            ['220E', 'MOTOCICLO ATÉ 100CC'],
            ['220G', 'MOTOCICLO ACIMA DE 500CC'],
            ['220F', 'MOTOCICLO DE 101CC ATÉ 500CC'],
            ['140X', 'CILINDRO DE ESTRADA'],
            ['09C3', 'CAMIONETA ALUGUER ENTRE 1.501CC E 2.500CC ATÉ 3.600KG'],
            ['09A3', 'CAMIONETA ALUGUER ATÉ 1.500CC ATÉ 3.600KG'],
            ['12D6', 'AUTOCARRO PARTICULAR ACIMA DE 2.500CC(até 40 lug)'],
            ['12D7', 'AUTOCARRO PARTICULAR ACIMA DE 2.500CC (de 41 a 90 lug)'],
            ['04A4', 'TAXI ATÉ 1.500 CC (até 9 lugares)'],
            ['12D8', 'AUTOCARRO PARTICULAR ACIMA DE 2.500CC(> 90 lug)'],
        ]);

        $spreadsheet->setActiveSheetIndexByName('Objectos');

        return $spreadsheet;
    }

    // =========================================================================
    // SHEET BUILDERS
    // =========================================================================

    /**
     * Build the main "Objectos" sheet with the exact formatting of the original:
     *
     *  Row 1 — Header:       Arial 10pt bold, #C0C0C0 fill, thin black borders, centered
     *  Row 2 — Example 1:    Arial 10pt, no fill, no borders
     *  Row 3 — Example 2:    Arial 10pt, no fill, no borders
     *  Row 4 — Format hints: Arial 10pt, no fill, no borders
     *  Row 5 — Separator:    Arial 10pt, #FFFF00 (yellow) fill, no borders, empty
     *  Row 6+ — Data:        written by the caller, no fill, no borders
     */
    private function buildSheetObjectos(Spreadsheet $spreadsheet): void
    {
        $sheet = new Worksheet($spreadsheet, 'Objectos');
        $spreadsheet->addSheet($sheet);

        // -----------------------------------------------------------------
        // Column widths — taken directly from the original template (÷ 256)
        // -----------------------------------------------------------------
        $widths = [
            'A' => 7.00,
            'B' => 12.57,
            'C' => 23.43,
            'D' => 12.00,
            'E' => 14.00,
            'F' => 10.00,
            'G' => 10.00,
            'H' => 10.00,
            'I' => 10.00,
            'J' => 10.00,
            'K' => 10.00,
            'L' => 10.00,
            'M' => 8.00,
            'N' => 10.00,
            'O' => 10.00,
            'P' => 22.14,
            'Q' => 10.00,
            'R' => 10.00,
            'S' => 6.71,
            'T' => 12.29,
            'U' => 10.00,
            'V' => 10.00,
            'W' => 9.00,
            'X' => 10.00,
            'Y' => 9.00,
            'Z' => 9.00,
            'AA' => 9.00,
            'AB' => 9.00,
            'AC' => 9.00,
        ];
        foreach ($widths as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }

        // All rows share the same height as the original (255/20 = 12.75 pt)
        for ($r = 1; $r <= 5; $r++) {
            $sheet->getRowDimension($r)->setRowHeight(12.75);
        }

        // -----------------------------------------------------------------
        // Row 1 — Header
        // Original: Arial 10pt bold, fill #C0C0C0, thin black borders, centered
        // -----------------------------------------------------------------
        $headers = [
            'A' => 'Grupo',
            'B' => 'Matricula',
            'C' => 'Desc.Veiculo',
            'D' => 'DatEntrada',
            'E' => 'CREDOR/LOCAD',
            'F' => 'AGRAV RC',
            'G' => 'AGRAV DP',
            'H' => 'CAP.RC.?',
            'I' => 'P.TOTAL?',
            'J' => 'CAP.OCUP',
            'K' => 'D. RC AP',
            'L' => 'D. DP AP',
            'M' => 'ESC.RC',
            'N' => 'Cat.Veic',
            'O' => 'Prov.Cir',
            'P' => 'Chassis',
            'Q' => 'Val.Nov.',
            'R' => 'Val.Ext.',
            'S' => 'USO',
            'T' => 'DT 1 MAT',
            'U' => 'Nº LUGAR',
            'V' => 'CILINDR.',
            'W' => 'PESO B.',
            'X' => 'ANO CONS',
            'Y' => 'EXTRA_1',
            'Z' => 'EXTRA_2',
            'AA' => 'EXTRA_3',
            'AB' => 'EXTRA_4',
            'AC' => 'EXTRA_5',
        ];
        foreach ($headers as $col => $label) {
            $sheet->setCellValue("{$col}1", $label);
        }
        $sheet->getStyle('A1:AC1')->applyFromArray([
            'font' => [
                'name' => 'Arial',
                'size' => 10,
                'bold' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'C0C0C0'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_BOTTOM,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        // -----------------------------------------------------------------
        // Row 2 — Example row 1
        // Original: Arial 10pt, no fill, no borders
        // -----------------------------------------------------------------
        $example1 = [
            'A' => 1,
            'B' => 'LD-11-22-XX',
            'C' => 'ALFA BIC',
            'D' => 20150701,
            'E' => '',
            'F' => 0,
            'G' => 0,
            'H' => 'N',
            'I' => 'N',
            'J' => 'S',
            'K' => '00.0',
            'L' => '00.0',
            'M' => 'B',
            'N' => '01D4',
            'O' => '11',
            'P' => 'jhjhtjrh59589',
            'Q' => 500000,
            'R' => '',
            'S' => 'A',
            'T' => 20150702,
            'U' => 5,
            'V' => 1500,
            'W' => 2000,
            'X' => 2015,
            'Y' => 'EXTRA 1',
            'Z' => 'EXTRA 2',
        ];
        foreach ($example1 as $col => $val) {
            $sheet->setCellValue("{$col}2", $val);
        }
        $sheet->getStyle('A2:AC2')->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 10, 'bold' => false],
        ]);

        // -----------------------------------------------------------------
        // Row 3 — Example row 2
        // Original: Arial 10pt, no fill, no borders
        // -----------------------------------------------------------------
        $example2 = [
            'A' => 2,
            'B' => 'LD-99-88-ZZ',
            'C' => 'ALFA BIC 2',
            'D' => 20150701,
            'E' => '',
            'F' => 0,
            'G' => 0,
            'H' => 'N',
            'I' => 'N',
            'J' => 'S',
            'K' => '00.0',
            'L' => '00.0',
            'M' => 'B',
            'N' => '01C4',
            'O' => '11',
            'P' => 'jhjhtjrh59589',
            'Q' => 500000,
            'R' => '',
            'S' => 'A',
            'T' => 20150702,
            'U' => 5,
            'V' => 1500,
            'W' => 2000,
            'X' => 2015,
        ];
        foreach ($example2 as $col => $val) {
            $sheet->setCellValue("{$col}3", $val);
        }
        $sheet->getStyle('A3:AC3')->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 10, 'bold' => false],
        ]);

        // -----------------------------------------------------------------
        // Row 4 — Format-hint row
        // Original: Arial 10pt, no fill, no borders
        // -----------------------------------------------------------------
        $formatHints = [
            'A' => 'GERAL',
            'B' => 'GERAL',
            'C' => 'GERAL',
            'D' => 'GERAL',
            'E' => 'GERAL',
            'F' => 'GERAL',
            'G' => 'GERAL',
            'H' => 'TEXTO',
            'I' => 'TEXTO',
            'J' => 'TEXTO',
            'K' => 'TEXTO',
            'L' => 'TEXTO',
            'M' => 'TEXTO',
            'N' => 'TEXTO',
            'O' => 'TEXTO',
            'P' => 'TEXTO',
            'Q' => 'GERAL',
            'R' => 'GERAL',
            'S' => 'TEXTO',
            'T' => 'GERAL',
            'U' => 'GERAL',
            'V' => 'GERAL',
            'W' => 'GERAL',
            'X' => 'GERAL',
            'Y' => 'GERAL',
            'Z' => 'GERAL',
            'AA' => 'GERAL',
            'AB' => 'GERAL',
            'AC' => 'GERAL',
        ];
        foreach ($formatHints as $col => $hint) {
            $sheet->setCellValue("{$col}4", $hint);
        }
        $sheet->getStyle('A4:AC4')->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 10, 'bold' => false],
        ]);

        // -----------------------------------------------------------------
        // Row 5 — Separator
        // Original: empty cells, Arial 10pt, solid #FFFF00 (pure yellow) fill
        // -----------------------------------------------------------------
        $sheet->getStyle('A5:AC5')->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 10, 'bold' => false],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFFF00'],
            ],
        ]);
    }

    /**
     * Build a generic reference sheet.
     * Header row: Arial 10pt bold, #C0C0C0 fill, centered (same as Objectos row 1).
     * Data rows:  Arial 10pt, no fill, no borders.
     */
    private function buildReferenceSheet(Spreadsheet $spreadsheet, string $name, array $rows): void
    {
        $sheet = new Worksheet($spreadsheet, $name);
        $spreadsheet->addSheet($sheet);

        $sheet->getColumnDimension('A')->setWidth(16);
        $sheet->getColumnDimension('B')->setWidth(55);

        foreach ($rows as $rowIndex => $cols) {
            $r = $rowIndex + 1;
            $sheet->setCellValue("A{$r}", $cols[0]);
            $sheet->setCellValue("B{$r}", $cols[1]);
        }

        // Header row
        $sheet->getStyle('A1:B1')->applyFromArray([
            'font' => [
                'name' => 'Arial',
                'size' => 10,
                'bold' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'C0C0C0'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_BOTTOM,
            ],
        ]);

        // Data rows
        $lastRow = count($rows);
        if ($lastRow > 1) {
            $sheet->getStyle("A2:B{$lastRow}")->applyFromArray([
                'font' => ['name' => 'Arial', 'size' => 10, 'bold' => false],
            ]);
        }
    }

    // =========================================================================
    // MAP LOADERS
    // =========================================================================

    private function loadEscRcMap(Spreadsheet $spreadsheet): array
    {
        $map = [];
        $sheet = $spreadsheet->getSheetByName('Tabela 939');
        if (!$sheet)
            return $map;

        $highestRow = $sheet->getHighestRow();
        for ($r = 2; $r <= $highestRow; $r++) {
            $code = trim((string) $sheet->getCell("A{$r}")->getValue());
            $desc = trim((string) $sheet->getCell("B{$r}")->getValue());
            if (!$code || !$desc)
                continue;
            $value = preg_replace('/[^0-9]/', '', $desc);
            if ($value !== '') {
                $map[$value] = $code;
            }
        }
        return $map;
    }

    private function loadProvinceMap(Spreadsheet $spreadsheet): array
    {
        $map = [];
        $sheet = $spreadsheet->getSheetByName('Tabela 928');
        if (!$sheet)
            return $map;

        $highestRow = $sheet->getHighestRow();
        for ($r = 2; $r <= $highestRow; $r++) {
            $code = trim((string) $sheet->getCell("A{$r}")->getValue());
            $desc = strtoupper(trim((string) $sheet->getCell("B{$r}")->getValue()));
            if ($code && $desc) {
                $map[$desc] = $code;
            }
        }
        return $map;
    }

    // =========================================================================
    // BUSINESS LOGIC HELPERS
    // =========================================================================

    private function resolveOcupantesCode(?int $rateId): string
    {
        if (!$rateId)
            return 'S';

        $rate = CFG_rates_occupants::find($rateId);
        if (!$rate)
            return 'S';

        return match (strtolower(trim($rate->package ?? ''))) {
            'max' => 'A',
            'med' => 'F',
            'min' => 'E',
            default => 'S',
        };
    }

    // private function resolveOcupantesCode(?int $rateId): string
    // {
    //     if (!$rateId)
    //         return 'S';

    //     $rate = CFG_rates_occupants::find($rateId);
    //     if (!$rate)
    //         return 'S';

    //     $mip = (float) ($rate->mip ?? 0);
    //     if ($mip >= 5000000)
    //         return 'A';
    //     if ($mip >= 1000000)
    //         return 'F';
    //     if ($mip > 0)
    //         return 'E';

    //     return 'S';
    // }

    // Devolve o objecto Rate (ou null)
    private function resolveRate(?int $rateId, $vehicle, ?int $branchId, $object = null): ?CFG_rates_automobile
    {
        if ($rateId) {
            $rate = CFG_rates_automobile::find($rateId);
            if ($rate?->code) {
                return $rate;
            }
        }

        if (!$vehicle || !$branchId) {
            return null;
        }

        $engine = (int) ($vehicle->cylinder_cc ?? 0);
        $seats = (int) ($vehicle->number_seats ?? 0);
        $weight = (float) ($vehicle->weight ?? 0);

        return CFG_rates_automobile::query()
            ->where('insurance_branch_id', $branchId)
            ->whereRaw('UPPER(vehicle_type) = ?', [strtoupper((string) $vehicle->type)])
            ->whereRaw('UPPER(category) = ?', [strtoupper((string) $vehicle->subcategory)])
            ->get()
            ->filter(function ($rate) use ($engine, $seats, $weight) {
                $engineOk = !$engine || ($rate->engine_min <= $engine && ($rate->engine_max ?? $engine) >= $engine);
                $seatOk = !$seats || ($rate->seat_min <= $seats && ($rate->seat_max ?? $seats) >= $seats);
                $weightOk = !$weight || ($rate->weight_min <= $weight && ($rate->weight_max ?? $weight) >= $weight);
                return $engineOk && $seatOk && $weightOk;
            })
            ->first();
    }

    // Mapeia vehicle_type + category da rate para T / P / A
    private function resolveUsoCode(?string $vehicleType, ?string $category): string
    {
        $vehicleType = strtoupper(trim((string) $vehicleType));
        $category = strtoupper(trim((string) $category));

        // Táxi individual ou colectivo
        if ($vehicleType === 'TAXI' || $category === 'TAXI COLECTIVO') {
            return 'T';
        }

        // Qualquer aluguer
        if ($category === 'ALUGUER') {
            return 'A';
        }

        // PARTICULAR, OUTRO (Misto, DuasRodas, Atrelados, Diversos, etc.) → Particular
        return 'P';
    }

    private function resolveEscRcCode(?float $rcFac, array $map): ?string
    {
        if (!$rcFac || empty($map))
            return null;
        $key = (string) intval(round($rcFac));
        return $map[$key] ?? null;
    }

    private function resolveProvinceCode($address, array $map): string
    {
        $province = strtoupper(trim((string) ($address?->province ?? '')));
        if ($province && isset($map[$province])) {
            return $map[$province];
        }
        return '11'; // Luanda default
    }

    private function formatDateYmd($date): ?string
    {
        if (!$date)
            return null;
        try {
            return \Illuminate\Support\Carbon::parse($date)->format('Ymd');
        } catch (\Throwable $e) {
            return null;
        }
    }
}