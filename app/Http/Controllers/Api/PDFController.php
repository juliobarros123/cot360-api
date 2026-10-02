<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\QuotationResource;
use App\Models\Coverage;
use App\Models\CFG\CFG_rates_health as RatesHealth;
use App\Models\QUO\QUO_quotation_versions as QuotationVersion;
use App\Models\CFG\CFG_rates_automobile as RatesAutomobile;
use Illuminate\Http\Request;
use Knp\Snappy\Pdf;
use function Laravel\Prompts\select;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class PDFController extends Controller
{

    public function __construct() {}
    // public function generate_quatation(Request $request)
    // {
    //     try {

    //         // 1. Configuração inicial do mPDF
    //         $mpdf = new \Mpdf\Mpdf([
    //             'mode' => 'utf-8',
    //             'format' => 'A4',
    //             'margin_top' => 30,
    //             'margin_header' => 10,
    //             'margin_bottom' => 14,
    //             'margin_left' => 18,
    //             'margin_right' => 22,
    //             'default_font_size' => 10,
    //             'default_font' => 'arial',
    //             'autoScriptToLang' => true,
    //             'autoLangToFont' => true,
    //         ]);

    //         if (ob_get_length()) {
    //             ob_end_clean();
    //         }

    //         // 2. Configurar o cabeçalho
    //         ini_set('memory_limit', '1500000M');
    //         ini_set("pcre.backtrack_limit", "3000000");
    //         $mpdf->DefHTMLHeaderByName(
    //             'main_header',
    //             '<div class="box-img-cab"> 
    //             <img class="img-cab" src="' . public_path('img/cotacao/logo.jpeg') . '" style="height: 38px;">
    //         </div>'
    //         );
    //         $mpdf->SetHTMLFooter('<div class="footer-page-number">Página {PAGENO} de {nbpg}</div>');

    //         // 3. Obter os dados
    //         $data = $request['data'][0];
    //         $annexs = [];
    //         $rate_ids = [];
    //         $cods_array = getRateCodes($data["objects"]);

    //         if (!count($cods_array)) {
    //             foreach ($data['QLI'] as $qli) {
    //                 if ($qli['coverageObject']['short_name'] == 'R.CIVIL OBR.') {
    //                     $rate_id = null;
    //                     if (isset($qli['rate_id'])) {
    //                         $rate_id = $qli['rate_id'];
    //                     } else {
    //                         if (isset($qli['ratesObject'][0]['id'])) {
    //                             $rate_id = $qli['ratesObject'][0]['id'];
    //                             $qli['rate_id'] = $rate_id;
    //                         }
    //                     }
    //                     if (!in_array($qli['rate_id'], $rate_ids)) {
    //                         $rate_ids[] = $qli['rate_id'];
    //                     }
    //                 }
    //             }
    //         }

    //         $annexsAdjustments = [];
    //         $rates = RatesAutomobile::whereIn('code', $cods_array)->where('insurance_branch_id', $data['selectedBranch']['id'])->get();

    //         if (!count($rates)) {
    //             $rates = RatesAutomobile::whereIn('id', $rate_ids)->get();
    //         }

    //         $capitaisQIV = getValoresPorTipo($rates);
    //         $quotationsLine = [];

    //         // Pega os adjustments uma vez só, já filtrados
    //         $filteredAdjustments = collect($data['adjustments'])->where("type", "surcharge")->values()->toArray();

    //         if (isset($data["quotation"])) {
    //             $annexsAdjustments = [];
    //             $version = QuotationVersion::with('lineItems:*', 'lineItems.surcharges:*')
    //                 ->find($data["quotation"]["id"]);

    //             $quotationsLine = $version->lineItems;

    //             foreach ($filteredAdjustments as $fa) {
    //                 $qli_id = $fa["qli_id"];
    //                 $quotationLine = $version->lineItems->where("id", $qli_id);
    //                 if ($quotationLine) {
    //                     $annexsAdjustments[$version->table][] = $fa;
    //                 }
    //             }

    //             if ($version->table == 'A') {
    //                 $versions = QuotationVersion::with(['lineItems', 'lineItems.surcharges'])
    //                     ->where('quotation_id', $version->quotation_id)
    //                     ->where('table', '!=', 'A')
    //                     ->get();

    //                 foreach ($versions as $version) {
    //                     $quotationsLine = $version->lineItems;
    //                     foreach ($filteredAdjustments as $fa) {
    //                         $qli_id = $fa["qli_id"];
    //                         $quotationLine = $version->lineItems->where("id", $qli_id);
    //                         if ($quotationLine) {
    //                             $annexsAdjustments[$version->table][] = $fa;
    //                         }
    //                     }
    //                 }
    //             }
    //         }

    //         // 4. CONFIGURAR CSS E HTML POR RAMO
    //         $branchCode = $data['selectedBranch']['code'];

    //         // CSS base (comum para todos)
    //         $css = file_get_contents(public_path('css/pdf/quotation-generate/estilo.css'));

    //         // CSS e View específicos por ramo
    //         switch ($branchCode) {
    //             case 18: // Saúde Família
    //                 // $cssAdditional = file_get_contents(public_path('css/pdf/quotation-generate/saude-familia.css'));
    //                 // $css .= $cssAdditional;
    //                 $html = view("pdf.quotation.saude-familia", $data, compact('annexs', 'annexsAdjustments', 'capitaisQIV', 'quotationsLine'))->render();
    //                 break;

    //             case 20: // Saúde Grupo
    //                 if ($data['selectedTemplate']['name'] == "MITRELLI") {
    //                     // $cssAdditional = file_get_contents(public_path('css/pdf/quotation-generate/saude-grupo-mitrelli.css'));
    //                     // $css .= $cssAdditional;
    //                     $html = view("pdf.quotation.saude-grupo-mitrelli", $data, compact('annexs', 'annexsAdjustments', 'capitaisQIV', 'quotationsLine'))->render();
    //                 } else {
    //                     // $cssAdditional = file_get_contents(public_path('css/pdf/quotation-generate/saude-grupo.css'));
    //                     // $css .= $cssAdditional;
    //                     $html = view("pdf.quotation.saude-grupo", $data, compact('annexs', 'annexsAdjustments', 'capitaisQIV', 'quotationsLine'))->render();
    //                 }
    //                 break;

    //             case 34: // Multirriscos Empresa
    //                 $cssAdditional = file_get_contents(public_path('css/pdf/quotation-generate/multirrisco-empresa.css'));
    //                 $css .= $cssAdditional;
    //                 $html = view("pdf.quotation.multirrisco-empresa", $data, compact('annexs', 'annexsAdjustments', 'capitaisQIV', 'quotationsLine'))->render();
    //                 break;

    //             default: // Automóvel e outros
    //                 // $cssAdditional = file_get_contents(public_path('css/pdf/quotation-generate/default.css'));
    //                 // $css .= $cssAdditional;
    //                 $html = view("pdf.quotation.index", $data, compact('annexs', 'annexsAdjustments', 'capitaisQIV', 'quotationsLine'))->render();
    //                 break;
    //         }

    //         // 5. Aplicar o CSS e HTML
    //         $mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);
    //         $mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);

    //         // 6. Forçar a aplicação do cabeçalho
    //         $mpdf->SetHTMLHeaderByName('main_header', 'O');

    //         // 7. Gerar nome personalizado para o arquivo
    //         $fileName = 'quotation_' . date('YmdHis') . '.pdf';
    //         if (!empty($data['quotation_number'])) {
    //             $fileName = 'quotation_' . preg_replace('/[^A-Za-z0-9\-]/', '_', $data['quotation_number']) . '_v' . $data['version_number'] . '.pdf';
    //         } elseif (!empty($data['quotation']['id'])) {
    //             $fileName = 'quotation_' . preg_replace('/[^A-Za-z0-9\-]/', '_', $data['quotation']['id']) . '.pdf';
    //         }

    //         // Logs para depuração
    //         \Log::info('Dados recebidos: ' . json_encode($data));
    //         \Log::info('Nome do arquivo gerado: ' . $fileName);
    //         \Log::info('Cabeçalho Content-Disposition: attachment; filename="' . $fileName . '"');

    //         // 8. Saída do PDF com nome personalizado
    //         $pdfContent = $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);

    //         return response($pdfContent, 200)
    //             ->header('Content-Type', 'application/pdf')
    //             ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'error' => $e->getMessage(),
    //             'trace' => env('APP_DEBUG') ? $e->getTrace() : null
    //         ], 500);
    //     }
    // }

    public function renewal_terms_proposal($id)
    {
        try {
            // ============================================
            // 1. PREPARAÇÃO DO AMBIENTE
            // ============================================

            // Configurar diretório temporário para mPDF
            $tempDir = storage_path('mpdf/temp');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            // Limpar buffer de output
            if (ob_get_length()) {
                ob_end_clean();
            }

            // Configurar limites para arquivos grandes
            ini_set('memory_limit', '512M');
            ini_set("pcre.backtrack_limit", "5000000");
            ini_set('max_execution_time', 300);


            // ============================================
            // 2. CONFIGURAÇÃO DO mPDF CORRIGIDA
            // ============================================

            // Primeiro, vamos encontrar o diretório das fontes do mPDF
            $vendorFontDir = base_path('vendor/mpdf/mpdf/data/fonts');

            if (!is_dir($vendorFontDir)) {
                // Tente outro caminho comum
                $vendorFontDir = base_path('vendor/mpdf/mpdf/ttfonts');

                if (!is_dir($vendorFontDir)) {
                    throw new \Exception("Diretório de fontes do mPDF não encontrado. Verifique se o mPDF está instalado corretamente.");
                }
            }

            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_top' => 30,
                'margin_header' => 10,
                'margin_bottom' => 14,
                'margin_left' => 18,
                'margin_right' => 22,
                'default_font_size' => 10,
                'tempDir' => $tempDir,
                // Configurar o diretório de fontes para onde o mPDF já tem as fontes
                'fontDir' => [
                    $vendorFontDir,
                ],

                'default_font' => 'sans-serif'
            ]);


            // ============================================
            // 3. CARREGAMENTO DE CSS
            // ============================================

            $cssPath = public_path('css/pdf/quotation-generate/renewal-terms-proposal.css');

            if (!file_exists($cssPath)) {
                throw new \Exception("Arquivo CSS não encontrado: " . $cssPath);
            }

            $css = file_get_contents($cssPath);


            // ============================================
            // 4. CARREGAMENTO DE HTML
            // ============================================

            $html = view("pdf.renewal-terms-proposal.index")->render();

            if (empty($html)) {
                throw new \Exception("A view HTML está vazia");
            }


            // ============================================
            // 5. CONSTRUÇÃO DO PDF
            // ============================================

            // Adicionar CSS ao PDF
            $mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);

            // Adicionar HTML ao PDF
            $mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);


            // ============================================
            // 6. GERAÇÃO DO CONTEÚDO
            // ============================================

            // Gerar conteúdo do PDF
            $pdfContent = $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);

            // Gerar nome do arquivo
            $fileName = 'renewal-terms-proposal-' . $id . '-' . date('Ymd-His') . '.pdf';


            // ============================================
            // 7. RETORNO DA RESPOSTA - MODIFICADO PARA ABRIR NO NAVEGADOR
            // ============================================

            return response($pdfContent, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="' . $fileName . '"') // Alterado de 'attachment' para 'inline'
                ->header('Content-Length', strlen($pdfContent))
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
        } catch (\Mpdf\MpdfException $e) {
            \Log::error('Erro mPDF: ' . $e->getMessage(), [
                'id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro ao gerar PDF: ' . $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTrace() : null
            ], 500);
        } catch (\Exception $e) {
            \Log::error('Erro ao gerar proposta de renovação: ' . $e->getMessage(), [
                'id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro ao processar solicitação: ' . $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTrace() : null
            ], 500);
        }
    }
    public function generate_quatation(Request $request)
    {
        try {

            if (ob_get_length()) {
                ob_end_clean();
            }

            ini_set('memory_limit', '1500000M');
            ini_set("pcre.backtrack_limit", "3000000");

            // 1. Dados recebidos (antes do mPDF para parametrizar por ramo)
            $data = $request['data'][0];
            $data = $this->normalizeHealthPayloadForPdf($data);
            $branchCode = (int) ($data['selectedBranch']['code'] ?? 0);
            $isMrHabitacao = $branchCode === 32;

            $branchCodeRaw      = (string) ($data['selectedBranch']['code'] ?? '');
            $policyPrefix       = (string) ($data['selectedBranch']['policy_number_prefix'] ?? '');
            $isAcidentesEscolar = ($branchCodeRaw === '14-02') || ($policyPrefix === 'APE');
            // 2. Configuração inicial do mPDF
            $mpdfConfig = [
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_top' => 30,
                'margin_header' => 15,
                'margin_bottom' => 14,
                'margin_left' => 18,
                'margin_right' => 22,
                'default_font_size' => 10,
                'default_font' => 'arial',
                'autoScriptToLang' => true,
                'autoLangToFont' => true,
                'tempDir' => storage_path('app/mpdf')
            ];
            $branchCode = $data['selectedBranch']['code'];
            if ($isMrHabitacao) {
                $mpdfConfig['margin_top'] = 20;
                $mpdfConfig['margin_header'] = 12;
                $mpdfConfig['margin_bottom'] = 40;
                $mpdfConfig['margin_footer'] = 5;
                $mpdfConfig['margin_left'] = 20;
                $mpdfConfig['margin_right'] = 20;
                $mpdfConfig['default_font_size'] = 10;
                $mpdfConfig['default_font'] = 'calibri';
            }

            $mpdf = new \Mpdf\Mpdf($mpdfConfig);

            // 3. Cabeçalho e rodapé
            $mpdf->DefHTMLHeaderByName(
                'main_header',
                '<div class="box-img-cab">
                <img class="img-cab" src="' . public_path('img/cotacao/bic_title_img.png') . '" style="height: ' . ($isMrHabitacao ? 70 : 85) . 'px; margin-top: ' . ($isMrHabitacao ? 10 : 0) . 'px;">
            </div>'
            );
            if ($isMrHabitacao) {

                // dd("ola",$isMrHabitacao);

                $mpdf->DefHTMLHeaderByName(
                    'main_header',
                    '<div class="box-img-cab">
                <img class="img-cab" src="' . public_path('img/cotacao/bic_title_img.png') . '" style="height: ' . ($isMrHabitacao ? 70 : 85) . 'px; margin-top: ' . ($isMrHabitacao ? 10 : 0) . 'px;">
            </div>'
                );
            } else {
                $mpdf->DefHTMLHeaderByName(
                    'main_header',
                    '<div class="box-img-cab">
                <img class="img-cab" src="' . public_path('img/cotacao/bic_title_img.png') . '" style="height: 40px; margin-top: ' . ($isMrHabitacao ? 10 : 0) . 'px;">
            </div>'
                );
            }
            if ($isMrHabitacao) {
                $mpdf->DefHTMLFooterByName(
                    'compliance_footer',
                    '<div style="text-align: center; font-size: 6pt; line-height: 1.2;">
        BIC SEGUROS, S.A.; Matriculada na Conservatória de Registo Comercial de Luanda 205/2014<br>
        Capital Social 1.500.000.000 kwanzas&nbsp;&nbsp;&nbsp;NIF: 5402183774<br>
        Sede Social: Rua N\'Gola M\'Bandi, R/C, Município da Maianga, Província de Luanda<br>
        Telefone Geral: 923 120 900&nbsp;&nbsp;&nbsp;<a href="https://www.bicseguros.ao/" style="color:black;">www.bicseguros.ao</a>&nbsp;&nbsp;&nbsp;email: <a href="mailto:geral@bicseguros.ao" style="color:black;">geral@bicseguros.ao</a>
    </div>
    <table style="width:100%; font-size:1pt; border-collapse:collapse">
        <tr>
            <td style="text-align:left; width:50%;font-size:8pt;"></td>
            <td style="text-align:right; width:50%;font-size:6pt;color:black;">Página {PAGENO} de {nbpg}</td>
        </tr>
    </table>'
                );
                $mpdf->SetHTMLFooterByName('compliance_footer', 'O');
                $mpdf->SetHTMLFooterByName('compliance_footer', 'E');
            } else {
                $mpdf->SetHTMLFooter(
                    '<div class="footer-page-number">Página {PAGENO} de {nbpg}</div>'
                );
            }

            // dd($data);

            $annexs = [];
            $rate_ids = [];
            $cods_array = getRateCodes($data["objects"]);
// dd($cods_array,$data["objects"]);
            if (!count($cods_array)  && $data['selectedBranch']['code'] == 91 && $data['selectedBranch']['code'] == 90) {
                foreach ($data['QLI'] as $qli) {
                    if ($qli['coverageObject']['short_name'] === 'R.CIVIL OBR.') {
                        if (isset($qli['rate_id'])) {
                            $rate_ids[] = $qli['rate_id'];
                        } elseif (isset($qli['ratesObject'][0]['id'])) {
                            $rate_ids[] = $qli['ratesObject'][0]['id'];
                            $qli['rate_id'] = $qli['ratesObject'][0]['id'];
                        }
                    }
                }
            }

            $annexsAdjustments = [];
            $rates = RatesAutomobile::whereIn('code', $cods_array)
                ->where('insurance_branch_id', $data['selectedBranch']['id'])
                ->get();
         
            if (!count($rates)) {
                $rates = RatesAutomobile::whereIn('id', $rate_ids)->get();
            }

            $capitaisQIV = getValoresPorTipo($rates);
            // dd($capitaisQIV,$rates);
            $quotationsLine = [];

            $filteredAdjustments = collect($data['adjustments'] ?? [])
                ->filter(function ($adj) {
                    return $this->isPricingAdjustmentForPdf((array) $adj);
                })
                ->values()
                ->toArray();

            // Buscar ajustes de anexos (B/C) já gravados, sempre na mesma versão da cotação
            $dbAnnexAdjustments = [];
            $currentTable = $data['quotation']['table'] ?? $data['quotation']['table_code'] ?? null;
            $quotationId = $data['quotation']['quotation_id']
                ?? $data['quotation']['id']
                ?? $data['quotationId']
                ?? null;
            $currentVersionCode = $data['quotation']['version']
                ?? $data['version']
                ?? null;
            if (!$currentVersionCode && isset($data['version_number'])) {
                $versionNum = (int) preg_replace('/\D+/', '', (string) $data['version_number']);
                $currentVersionCode = $versionNum > 0 ? ('v' . $versionNum) : null;
            }

            if ($quotationId) {
                $annexVersionsQuery = QuotationVersion::with('objectRisks.adjustments')
                    ->where('quotation_id', $quotationId)
                    ->where('table_code', '!=', 'A');

                // Evita mistura entre versões (v1/v2/...) no PDF dos anexos.
                if ($currentVersionCode) {
                    $annexVersionsQuery->where('version', (string) $currentVersionCode);
                }

                $annexVersions = $annexVersionsQuery->get();

                foreach ($annexVersions as $ver) {
                    $annexCode = $ver->table_code;
                    foreach ($ver->objectRisks as $risk) {
                        foreach ($risk->adjustments as $adj) {
                            if (!$this->isPricingAdjustmentForPdf([
                                'type' => $adj->type,
                                'rate' => $adj->rate,
                            ])) {
                                continue;
                            }
                            $dbAnnexAdjustments[$annexCode][] = [
                                'qli_id' => $risk->id,
                                'rate_id' => $adj->rate_id,
                                'rate' => $adj->rate,
                                'type' => $adj->type,
                                'value' => $adj->value,
                                'reason' => $adj->reason,
                                'notes' => $adj->notes,
                            ];
                        }
                    }
                }
            }

            // Garante QLI de RC FACULTAT. com anexos (B/C) a partir das sobretaxas
            if (
                isset($data['QLI'], $data['surcharges'], $data['objects']) &&
                is_array($data['QLI']) &&
                is_array($data['surcharges']) &&
                is_array($data['objects'])
            ) {
                $hasAnnexQli = collect($data['QLI'])->contains(function ($qli) {
                    return ($qli['coverageObject']['short_name'] ?? null) === 'RC FACULTAT.'
                        && !empty($qli['annex']);
                });

                if (!$hasAnnexQli && count($data['surcharges'])) {
                    $newQlis = [];

                    foreach ($data['objects'] as $object) {
                        $objectId = $object['id'] ?? null;
                        if (!$objectId) {
                            continue;
                        }

                        $qliRc = collect($data['QLI'])->first(function ($qli) use ($objectId) {
                            $objId = $qli['object_id'] ?? $qli['insured_object_id'] ?? null;
                            return $objId === $objectId
                                && ($qli['coverageObject']['short_name'] ?? null) === 'R.CIVIL OBR.';
                        });

                        $qliFacTemplate = collect($data['QLI'])->first(function ($qli) use ($objectId) {
                            $objId = $qli['object_id'] ?? $qli['insured_object_id'] ?? null;
                            return $objId === $objectId
                                && ($qli['coverageObject']['short_name'] ?? null) === 'RC FACULTAT.';
                        });

                        if (!$qliRc || !$qliFacTemplate) {
                            continue;
                        }

                        $surcharges = collect($data['surcharges'])
                            ->where('object_id', $objectId)
                            ->values();

                        foreach ($surcharges as $surcharge) {
                            $rateId = $surcharge['surcharge_id'] ?? null;
                            $rateObj = null;
                            if (isset($qliFacTemplate['ratesObject']) && is_array($qliFacTemplate['ratesObject'])) {
                                $rateObj = collect($qliFacTemplate['ratesObject'])->firstWhere('id', $rateId);
                            }

                            $rcBase = $qliRc['premium_value']
                                ?? $qliRc['premiunComercial']
                                ?? $qliRc['coverage_premium']
                                ?? 0;

                            $surchargePerc = $surcharge['surcharge_perc'] ?? 0;
                            $premiumValue = $rcBase * ($surchargePerc / 100);

                            $newQlis[] = [
                                'id' => count($data['QLI']) + count($newQlis) + 1,
                                'quotation_id' => $qliFacTemplate['quotation_id'] ?? null,
                                'coverage_id' => $qliFacTemplate['coverageObject']['id'] ?? null,
                                'object_id' => $objectId,
                                'created_date' => now(),
                                'rate_type' => $qliFacTemplate['coverageObject']['rate_type'] ?? null,
                                'capital_min_value' => null,
                                'premium_value' => $premiumValue,
                                'coverage_premium' => $premiumValue,
                                'premiunComercial' => $premiumValue,
                                'rate' => $rateObj['rc_fac'] ?? $qliFacTemplate['rate'] ?? null,
                                'rate_id' => $rateId,
                                'status' => true,
                                'coverageObject' => $qliFacTemplate['coverageObject'],
                                'ratesObject' => $qliFacTemplate['ratesObject'] ?? [],
                                'annex' => $surcharge['annex'] ?? null,
                            ];
                        }
                    }

                    $data['QLI'] = array_values(array_filter($data['QLI'], function ($qli) {
                        return ($qli['coverageObject']['short_name'] ?? null) !== 'RC FACULTAT.';
                    }));
                    $data['QLI'] = array_merge($data['QLI'], $newQlis);
                }
            }

            // Agrupar anexos a partir dos QLI (RC FACULTAT.) quando disponíveis
            $annexsAdjustments = [];
            if (isset($data['QLI']) && is_array($data['QLI'])) {
                foreach ($data['QLI'] as $qli) {
                    $annex = $qli['annex'] ?? null;
                    if ($annex) {
                        $annexsAdjustments[$annex][] = $qli;
                    }
                }
            }

            // Merge dos ajustes vindos da BD (Anexos B/C)
            if (!empty($dbAnnexAdjustments)) {
                foreach ($dbAnnexAdjustments as $annex => $items) {
                    if (!isset($annexsAdjustments[$annex])) {
                        $annexsAdjustments[$annex] = [];
                    }
                    $annexsAdjustments[$annex] = array_merge($annexsAdjustments[$annex], $items);
                }
            }
            // dd($annexsAdjustments);
            // Se ainda não há anexos, usar as sobretaxas (anexos B/C)
            if (!$annexsAdjustments && isset($data['surcharges']) && is_array($data['surcharges'])) {
                foreach ($data['surcharges'] as $surcharge) {
                    $annex = $surcharge['annex'] ?? null;
                    if ($annex) {
                        $annexsAdjustments[$annex][] = $surcharge;
                    }
                }
            }

            // Fallback: usa ajustes por QLI (caso não existam anexos nos QLI)
            if (!$annexsAdjustments && $filteredAdjustments) {
                $tableCode = $data['quotation']['table'] ?? $data['quotation']['table_code'] ?? 'A';
                $annexsAdjustments = groupAdjustmentsByQli($filteredAdjustments, $tableCode);
            }

            // Último fallback: garantir pelo menos o anexo atual
            if (!$annexsAdjustments) {
                $tableCode = $data['quotation']['table'] ?? $data['quotation']['table_code'] ?? null;
                if ($tableCode && $tableCode !== 'A') {
                    $annexsAdjustments[$tableCode] = [];
                }
            }

            // Quando imprimir anexo isolado (B/C), não pode misturar nem repetir outros anexos.
            if ($currentTable && $currentTable !== 'A') {
                $annexsAdjustments = [
                    $currentTable => array_values($annexsAdjustments[$currentTable] ?? [])
                ];
            }
            // dd("ola");
            if ($isMrHabitacao) {
                // 4. CSS base
                // dd("olabbb");
                $css = file_get_contents(
                    public_path('css/pdf/quotation-generate/estilo-mr-habitacao.css')
                );
            } else if ($isAcidentesEscolar) {                                   // <-- NOVO
                $css = file_get_contents(public_path('css/pdf/quotation-generate/school-accident-quotation.css'));
            } else if ($branchCode == 18) {
                // dd("ola");
                $css = file_get_contents(
                    public_path('css/pdf/quotation-generate/health-quotation.css')
                );
            } else {
                $css = file_get_contents(
                    public_path('css/pdf/quotation-generate/estilo.css')
                );
            }
            // 5. HTML por ramo
            if ($isAcidentesEscolar) {                                          // <-- NOVO
                $html = view(
                    "pdf.quotation.acidentes-escolar",
                    $data,
                    compact('annexs', 'annexsAdjustments', 'capitaisQIV', 'quotationsLine')
                )->render();
            } else {

                switch ($branchCode) {

                    case 18: // Saúde Família
                        $html = view(
                            "pdf.quotation.saude-familia",
                            $data,
                            compact('annexs', 'annexsAdjustments', 'capitaisQIV', 'quotationsLine')
                        )->render();
                        break;

                    case 20: // Saúde Grupo
                        if ($data['selectedTemplate']['name'] === "MITRELLI") {
                            $html = view(
                                "pdf.quotation.saude-grupo-mitrelli",
                                $data,
                                compact('annexs', 'annexsAdjustments', 'capitaisQIV', 'quotationsLine')
                            )->render();
                        } else {
                            $html = view(
                                "pdf.quotation.saude-grupo",
                                $data,
                                compact('annexs', 'annexsAdjustments', 'capitaisQIV', 'quotationsLine')
                            )->render();
                        }
                        break;

                    case 34: // Multirriscos Empresa
                        $css .= file_get_contents(
                            public_path('css/pdf/quotation-generate/multirrisco-empresa.css')
                        );

                        $html = view(
                            "pdf.quotation.multirrisco-empresa",
                            $data,
                            compact('annexs', 'annexsAdjustments', 'capitaisQIV', 'quotationsLine')
                        )->render();
                        break;

                    case 32: // Multirriscos Habitação
                        $css .= file_get_contents(
                            public_path('css/pdf/quotation-generate/multirrisco-habitacao.css')
                        );

                        $html = view(
                            "pdf.quotation.multirrisco-habitacao",
                            $data,
                            compact('annexs', 'annexsAdjustments', 'capitaisQIV', 'quotationsLine')
                        )->render();
                        break;

                    default: // Automóvel e outros
                        // dd("ola");
                        $html = view(
                            "pdf.quotation.index",
                            $data,
                            compact('annexs', 'annexsAdjustments', 'capitaisQIV', 'quotationsLine')
                        )->render();
                        break;
                }
            }
            // 6. Guardar HTML + CSS (PARTE NOVA)
            $quotationRootId = (int) ($data['quotation']['quotation_id'] ?? $data['quotation']['id'] ?? 0);
            $versionNumber = (int) preg_replace('/\D+/', '', (string) ($data['version_number'] ?? '0'));
            $tableCode = strtoupper((string) ($data['quotation']['table'] ?? $data['quotation']['table_code'] ?? 'A'));
            if ($quotationRootId > 0 && $versionNumber > 0) {
                $this->storeQuotationHtml(
                    $quotationRootId,
                    $versionNumber,
                    $tableCode,
                    $html,
                    $css
                );
            }

            // 7. Gerar PDF
            $mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);
            $mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);
            $mpdf->SetHTMLHeaderByName('main_header', 'O');
            $mpdf->SetHTMLHeaderByName('main_header', 'E');
            // 8. Nome do ficheiro
            if (!empty($data['quotation'])) {
                $fileName = 'quotation_' .
                    preg_replace('/[^A-Za-z0-9\-]/', '_', $data['quotation_number']) .
                    '_v' . $data['version_number'] . '.pdf';
            } else {
                $fileName = 'quotation_' . date('YmdHis') . '.pdf';
            }

            // 9. Output
            $pdfContent = $mpdf->Output(
                '',
                \Mpdf\Output\Destination::STRING_RETURN
            );

            return response($pdfContent, 200)
                ->header('Content-Type', 'application/pdf')
                ->header(
                    'Content-Disposition',
                    'attachment; filename="' . $fileName . '"'
                );
        } catch (\Exception $e) {
            Log::error('Erro ao gerar PDF de cotação', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTrace() : null
            ], 500);
        }
    }
    public function reprintQuotation(Request $request, int $quotationId, int $version)
    {
        $tableCode = strtoupper((string) $request->query('table', 'A'));
        $basePath = "quotations/html/{$quotationId}/v{$version}/{$tableCode}";
        $tempDir = storage_path('app/mpdf');

        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0775, true);
        }

        if (!Storage::exists("{$basePath}/content.html")) {
            return response()->json([
                'error' => 'HTML da cotação não encontrado'
            ], 404);
        }

        $html = Storage::get("{$basePath}/content.html");
        $css = Storage::get("{$basePath}/style.css");

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_top' => 30,
            'margin_bottom' => 14,
            'margin_left' => 18,
            'margin_right' => 22,
            'default_font_size' => 10,
            'tempDir' => $tempDir,
        ]);

        $mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);
        $mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);

        return response(
            $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN),
            200,
            ['Content-Type' => 'application/pdf']
        );
    }

    private function storeQuotationHtml(
        int $quotationId,
        int $versionNumber,
        string $tableCode,
        string $html,
        string $css
    ): void {
        $tableCode = strtoupper(trim($tableCode ?: 'A'));
        $basePath = "quotations/html/{$quotationId}/v{$versionNumber}/{$tableCode}";
        Storage::put("{$basePath}/content.html", $html);
        Storage::put("{$basePath}/style.css", $css);
    }

    private function normalizeHealthPayloadForPdf(array $data): array
    {
        $branchCode = (int) ($data['selectedBranch']['code'] ?? 0);
        if (!in_array($branchCode, [18, 20], true)) {
            return $data;
        }

        $branchId = (int) ($data['selectedBranch']['id'] ?? 0);

        $objects = collect($data['objects'] ?? [])
            ->values()
            ->map(function ($object, int $index) {
                return $this->normalizeHealthObjectForPdf((array) $object, $index);
            })
            ->toArray();

        $coverageMap = collect($data['coverages'] ?? [])
            ->mapWithKeys(function ($coverage) {
                $item = (array) $coverage;
                return [(int) ($item['id'] ?? 0) => $item];
            })
            ->toArray();

        $qliList = collect($data['QLI'] ?? [])->values();
        $objectsById = collect($objects)->keyBy('id');

        $qli = $qliList->map(function ($item, int $index) use ($objectsById, $coverageMap, $branchId) {
            $qliItem = (array) $item;

            $objectId = $qliItem['object_id'] ?? $qliItem['insured_object_id'] ?? null;
            if (!$objectId && isset($qliItem['object_index'])) {
                $objectId = $qliItem['object_index'];
            }
            if (!$objectId && isset($qliItem['object_data']['id'])) {
                $objectId = $qliItem['object_data']['id'];
            }
            if (!$objectId && isset($objectsById->values()[$index]['id'])) {
                $objectId = $objectsById->values()[$index]['id'];
            }

            $objectId = is_numeric($objectId) ? (int) $objectId : $objectId;
            $qliItem['object_id'] = $objectId;

            $coverageId = $qliItem['coverage_id'] ?? $qliItem['coverageObject']['id'] ?? null;
            $coverageId = is_numeric($coverageId) ? (int) $coverageId : $coverageId;
            $qliItem['coverage_id'] = $coverageId;

            if (empty($qliItem['coverageObject']) && $coverageId && isset($coverageMap[$coverageId])) {
                $qliItem['coverageObject'] = $coverageMap[$coverageId];
            }

            $ratesObject = $qliItem['ratesObject'] ?? [];
            if (!is_array($ratesObject) || empty($ratesObject)) {
                $object = $objectsById->get($objectId, []);
                $ratesObject = $this->loadHealthRatesForPdf($branchId, $coverageId, (array) $object);
            }

            $qliItem['ratesObject'] = is_array($ratesObject) ? array_values($ratesObject) : [];
            $qliItem['status'] = isset($qliItem['status']) ? (bool) $qliItem['status'] : true;

            return $qliItem;
        })->toArray();

        if (empty($qli) && !empty($objects)) {
            $selectedPackage = strtoupper(trim((string) ($data['selectedTemplate']['name'] ?? $data['selectedTemplate']['package'] ?? '')));
            $qli = collect($objects)->values()->map(function ($object, int $index) use ($branchId, $selectedPackage) {
                $ratesObject = $this->loadHealthRatesForPdf($branchId, null, (array) $object);
                $selectedRate = collect($ratesObject)->first(function ($rate) use ($selectedPackage) {
                    $package = strtoupper(trim((string) ($rate['package'] ?? '')));
                    return $selectedPackage !== '' && $package === $selectedPackage;
                });

                return [
                    'id' => $index + 1,
                    'object_id' => $object['id'] ?? null,
                    'coverage_id' => null,
                    'premium_value' => (float) ($selectedRate['premium_value'] ?? 0),
                    'rate' => 0,
                    'status' => true,
                    'ratesObject' => $ratesObject,
                ];
            })
                ->filter(fn($item) => !empty($item['object_id']) && !empty($item['ratesObject']))
                ->values()
                ->toArray();
        }

        $ratesByObject = collect($qli)
            ->groupBy('object_id')
            ->map(function ($items) {
                return collect($items)
                    ->flatMap(function ($qliItem) {
                        return collect($qliItem['ratesObject'] ?? [])->map(function ($rate) {
                            return [
                                'id' => $rate['id'] ?? null,
                                'package' => $rate['package'] ?? null,
                                'premium_value' => $rate['premium_value'] ?? null,
                            ];
                        });
                    })
                    ->filter(fn($rate) => !empty($rate['package']))
                    ->unique(fn($rate) => ($rate['id'] ?? '') . '|' . ($rate['package'] ?? ''))
                    ->values()
                    ->toArray();
            });

        $objectsWithRates = collect($objects)->map(function ($object) use ($ratesByObject) {
            $objectId = $object['id'] ?? null;
            $object['pdf_rates'] = $ratesByObject->get($objectId, []);
            return $object;
        })->toArray();

        $data['objects'] = $objectsWithRates;
        $data['QLI'] = $qli;

        Log::info('PDF Saúde - payload normalizado', [
            'branch_code' => $branchCode,
            'objects_count' => count($objectsWithRates),
            'qli_count' => count($qli),
            'objects_with_rates' => collect($objectsWithRates)
                ->map(fn($object) => [
                    'id' => $object['id'] ?? null,
                    'name' => $object['name'] ?? null,
                    'rates_count' => count($object['pdf_rates'] ?? []),
                ])
                ->values()
                ->toArray(),
        ]);

        return $data;
    }

    private function normalizeHealthObjectForPdf(array $object, int $index): array
    {
        $id = $object['id'] ?? null;
        $object['id'] = is_numeric($id) ? (int) $id : ($index + 1);

        $object['name'] = $object['name']
            ?? $object['person']['name']
            ?? $object['details']['name']
            ?? ('Pessoa ' . $object['id']);

        $object['relationship'] = $object['relationship']
            ?? $object['person']['relationship']
            ?? $object['details']['relationship']
            ?? 'Titular';

        $birthdate = $object['birthdate']
            ?? $object['person']['birthdate']
            ?? $object['details']['birthdate']
            ?? null;

        if (is_string($birthdate) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthdate)) {
            try {
                $birthdate = \Carbon\Carbon::parse($birthdate)->format('d/m/Y');
            } catch (\Throwable $e) {
                // Mantém o valor original se não conseguir converter.
            }
        }
        $object['birthdate'] = $birthdate;

        $object['gender'] = $object['gender']
            ?? $object['person']['gender']
            ?? $object['details']['gender']
            ?? null;

        if ((!array_key_exists('age', $object) || $object['age'] === null || $object['age'] === '') && !empty($birthdate)) {
            try {
                $object['age'] = \Carbon\Carbon::parse($birthdate)->age;
            } catch (\Throwable $e) {
                $object['age'] = null;
            }
        }

        return $object;
    }

    private function loadHealthRatesForPdf(int $branchId, ?int $coverageId, array $object): array
    {
        if ($branchId <= 0) {
            return [];
        }

        $query = RatesHealth::query()
            ->where('insurance_branch_id', $branchId)
            ->where(function ($q) {
                $q->whereNull('active')->orWhere('active', true);
            });

        if ($coverageId) {
            $query->where(function ($q) use ($coverageId) {
                $q->where('coverage_id', $coverageId)->orWhereNull('coverage_id');
            });
        }

        $gender = strtoupper((string) ($object['gender'] ?? ''));
        if ($gender !== '') {
            $query->where(function ($q) use ($gender) {
                $q->whereNull('gender')
                    ->orWhere('gender', '')
                    ->orWhereRaw('UPPER(gender) = ?', [$gender])
                    ->orWhereRaw("UPPER(gender) IN ('ALL', 'BOTH', 'M/F', 'MF')");
            });
        }

        $age = null;
        if (isset($object['age']) && is_numeric($object['age'])) {
            $age = (int) $object['age'];
        } elseif (!empty($object['birthdate'])) {
            try {
                $age = \Carbon\Carbon::parse($object['birthdate'])->age;
            } catch (\Throwable $e) {
                $age = null;
            }
        }

        if ($age !== null) {
            $query->where(function ($q) use ($age) {
                $q->where(function ($q2) use ($age) {
                    $q2->whereNull('age_min')->orWhere('age_min', '<=', $age);
                })->where(function ($q2) use ($age) {
                    $q2->whereNull('age_max')->orWhere('age_max', '>=', $age);
                });
            });
        }

        return $query
            ->orderBy('package')
            ->get(['id', 'package', 'premium_value', 'age_min', 'age_max', 'gender', 'type'])
            ->map(function ($rate) {
                return [
                    'id' => $rate->id,
                    'package' => $rate->package,
                    'premium_value' => (float) $rate->premium_value,
                    'age_min' => $rate->age_min,
                    'age_max' => $rate->age_max,
                    'gender' => $rate->gender,
                    'type' => $rate->type,
                ];
            })
            ->values()
            ->toArray();
    }

    private function isPricingAdjustmentForPdf(array $adjustment): bool
    {
        $type = strtolower(trim((string) ($adjustment['type'] ?? '')));
        $rate = (float) ($adjustment['rate'] ?? 0);

        if (in_array($type, ['surcharge', 'malus', 'discount', 'bonus', 'commercial', 'loyalty'], true)) {
            return true;
        }

        return $rate !== 0.0;
    }

    public function generate_quotation_by_id($id)
    {
        try {
            // 1. Configuração inicial do mPDF
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_top' => 30,       // Margem maior na primeira página
                'margin_header' => 10,    // Espaço para o cabeçalho
                'margin_bottom' => 14,
                'margin_left' => 18,
                'margin_right' => 22,
                'default_font_size' => 10,
                'default_font' => 'arial',
                'autoScriptToLang' => true,
                'autoLangToFont' => true,
            ]);

            // 2. Configurar o cabeçalho PRIMEIRO (antes de qualquer conteúdo)
            $mpdf->DefHTMLHeaderByName(
                'main_header',
                ' <div class=" box-img-cab"> 
                <img class="img-cab" src="' . public_path('img/cotacao/logo.jpeg') . '" style="height: 40px;">
            </div>'
            );
            $mpdf->SetHTMLFooter('<div class="footer-page-number">Página {PAGENO} de {nbpg}</div>');
            // 3. Configurar o estilo CSS
            $css = file_get_contents(public_path('css/pdf/quotation-generate/estilo.css'));
            $css .= '
       ';
            $filters = ["id" => $id];

            $quotations = $this->quotationService->getAllQuotations($filters);
            $quotation = QuotationResource::collection($quotations)->resolve()[0];
            // dd($quotation);
            $coverages = Coverage::where('insurance_branch_id', $quotation['insurance_branch']->id)->get();
            // dd($coverages->toArray()[0]->id);
            $titlesObjectsHeadPDF = [
                [
                    'name' => 'plate',
                    'valuetd' => 'Matrícula',
                ],
                [
                    'name' => 'brand',
                    'valuetd' => 'Marca',
                ],
                [
                    'name' => 'model',
                    'valuetd' => 'Modelo',
                ],
                [
                    'name' => 'engine_min',
                    'valuetd' => 'Cilindrada',
                ],
                [
                    'name' => 'number_seats',
                    'valuetd' => 'Lotação',
                ],
            ];

            $data = [


                'quotation' => $this->quotationService->find($id)->toArray(), // ou você pode personalizar campo a campo
                'channel' => $quotation['sales_channel'],
                'selectedBranch' => $quotation['insurance_branch'],
                'entity' => $quotation['customer'],
                'coverages' => $coverages, // array normal
                'premiosGlobais' => $quotation['premiosGlobais'], // array ou valor
                'attributesTablePDF' => ['titlesObjectsHeadPDF' => $titlesObjectsHeadPDF], // array ou string
                'objects' => $quotation['insured_objects'], // array ou objeto, pode fazer ['toArray()
                'QLI' => $quotation['line_items'], // idem
                'caeSelected' => $quotation['cae_rates_branch'],
                'discountPerc' => $quotation['discountPerc'],

            ];
            // dd($quotations);
            // 4. Obter os dados e renderizar o HTML

            // dd($data);
            $html = view("pdf.quotation.index", $data)->render();

            // 5. Aplicar o CSS e HTML
            $mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);
            $mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);

            // 6. Forçar a aplicação do cabeçalho (truque importante)
            $mpdf->SetHTMLHeaderByName('main_header', 'O');
            // $mpdf->AddPage(); // Força a criação de nova página se necessário

            // 7. Saída do PDF
            $pdfContent = $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);

            return response($pdfContent, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="quotation.pdf"');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => env('APP_DEBUG') ? $e->getTrace() : null
            ], 500);
        }
    }

    public function generate_test()
    {

        try {
            // Validate the request data
            // $validatedData = $request->validate([
            //     'data' => 'required|array',
            //     'data.0' => 'required|array'
            // ]);

            // Initialize mPDF with configuration
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_top' => 20,
                'margin_bottom' => 14,
                'margin_left' => 18,
                'margin_right' => 18,
                'allow_output_buffering' => true,
                'showImageErrors' => true,
                'list_indent_default_style' => 'none'
            ]);

            // $data = $request['data'][0];
            // return response()->json([
            //     'success' => true,
            //     'url' => '',
            //     'filename' => '',
            //     'message' => $data
            // ]);
            // dd($data);
            // Get CSS content
            $cssPath = public_path('css/pdf/quotation-generate/estilo.css');
            if (!file_exists($cssPath)) {
                throw new \Exception("CSS file not found at: {$cssPath}");
            }

            $style = file_get_contents($cssPath);
            $html = view("pdf.quotation.test")->render();

            // Write to PDF
            $mpdf->WriteHTML($style, \Mpdf\HTMLParserMode::HEADER_CSS);
            $mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);

            // Prepare file storage
            $filename = 'quotation_' . time() . '.pdf';
            $directory = storage_path('app/public/pdfs/');
            $filePath = $directory . $filename;

            // Create directory if not exists
            if (!file_exists($directory)) {
                if (!mkdir($directory, 0755, true)) {
                    throw new \Exception("Failed to create directory: {$directory}");
                }
            }


            // Save PDF
            $mpdf->Output($filePath, 'I');

            // Verify file was created
            if (!file_exists($filePath)) {
                throw new \Exception("PDF generation failed, file not created");
            }

            return response()->json([
                'success' => true,
                'url' => asset('storage/pdfs/' . $filename),
                'filename' => $filename,
                'message' => 'Quotation generated successfully'
            ]);
        } catch (\Mpdf\MpdfException $e) {
            return response()->json([
                'success' => false,
                'error' => 'PDF generation error',
                'message' => $e->getMessage(),
                'trace' => env('APP_DEBUG') ? $e->getTrace() : null
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Processing error',
                'message' => $e->getMessage(),
                'trace' => env('APP_DEBUG') ? $e->getTrace() : null
            ], 500);
        }
    }

    public function testsnappy()
    {

        $data = ['title' => 'Meu PDF Laravel'];


        // $pdf = PDF::loadHTML('<h1>Teste</h1>');
        // // Para visualizar no navegador
        // return $pdf->stream('documento.pdf');

        // // Para download
        // return $pdf->download('documento.pdf');

        // // Para salvar no servidor
        // $pdf->save(storage_path('app/public/documento.pdf'));

    }
    public function generate_auto_proposal_pdf()
    {
        try {
            // 1. Configuração inicial do mPDF (igual ao seu código existente)
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_top' => 2,
                'margin_header' => 10,
                'margin_bottom' => 14,
                'margin_left' => 15,
                'margin_right' => 15,
                'default_font_size' => 10,
                'default_font' => 'calibri',
                'autoScriptToLang' => true,
                'autoLangToFont' => true,
            ]);

            // 2. Configurar o cabeçalho
            $mpdf->DefHTMLHeaderByName(
                'main_header',
                '<div style="text-align: center; font-size: 9pt; border-bottom: 1px solid #000; padding-bottom: 5px;">
            BIC SEGUROS, S.A.; Matriculada na Conservatória de Registo Comercial de Luanda 205/2014<br>
            Capital Social 1.500.000.000 kwanzas NIF: 5402163774<br>
            Sede Social: Rua N\'Gola M\'Bandi, R/C, Município da Maianga, Província de Luanda<br>
            Telefone Geral: 226443670 www.bicseguros.ao
        </div>'
            );

            // Configurar rodapé com o texto específico e numeração de páginas
            $mpdf->SetHTMLFooter('
        <div style="text-align: center;color:red;  font-size: 6pt;
    line-height: 1.2;">
         <hr style="margin-top:0px;padding-top:0px;width:80%;color:black; line-height:0px;">
            BIC SEGUROS, S.A.; Matriculada na Conservatória de Registo Comercial de Luanda 205/2014<br>
            Capital Social 1.500.000.000 kwanzas NIF: 5402163774<br>
            Sede Social: Rua N\'Gola M\'Bandi, R/C, Município da Maianga, Província de Luanda<br>
            Telefone Geral: 226443670 <a href="https://www.bicseguros.ao/" style="color:red">www.bicseguros.ao</a><br>
            Página {PAGENO} de {nbpg}
        </div>');

            // 3. Configurar o estilo CSS específico para este documento
            $cssPath = public_path('css/pdf/quotation-generate/proposta-unica.css');
            if (!file_exists($cssPath)) {
                throw new \Exception("CSS file not found at: {$cssPath}");
            }

            $css = file_get_contents($cssPath);

            // 4. Renderizar a view existente
            $html = view('pdf.proposal.index')->render();

            // 5. Aplicar o CSS e HTML
            $mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);
            $mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);

            // 6. Forçar a aplicação do cabeçalho
            $mpdf->SetHTMLHeaderByName('main_header', 'O');

            // 7. Gerar nome para o arquivo
            $fileName = 'proposta_seguro_auto_' . date('YmdHis') . '.pdf';

            // 8. Saída do PDF para visualização no navegador (inline)
            $pdfContent = $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);

            return response($pdfContent, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="' . $fileName . '"');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => env('APP_DEBUG') ? $e->getTrace() : null
            ], 500);
        }
    }
}
