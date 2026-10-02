<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PDFController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    //contar();
    return view('welcome');
});
Route::get('/cae_upload', [PDFController::class, 'testsnappy']);

Route::get('/test-pdf', function () {
    //contar();
    Route::get('/', [PDFController::class, 'testsnappy']);

    return view('welcome');
});

// routes/web.php
Route::get('/auth/callback', [AuthController::class, 'handleCallback']);


/*
|--------------------------------------------------------------------------
| ROTAS DE TESTE — gerar os PDFs (colar no routes/web.php)
|--------------------------------------------------------------------------
| Cada PDF tem a sua função/rota independente e auto-contida:
| configura o mPDF, carrega o CSS do ficheiro separado e devolve o PDF.
|
| Ficheiros usados:
|   resources/views/pdf/certificado-mercadorias/index.blade.php
|   resources/views/pdf/condicoes-particulares/index.blade.php
|   public/css/pdf/quotation-generate/cargo-certificate.css
|   public/css/pdf/quotation-generate/conditions-ap.css
|
| URLs:
|   GET /teste/certificado-mercadorias
|   GET /teste/condicoes-ap
|--------------------------------------------------------------------------
*/
Route::get('/teste/certificado-mercadorias', function () {
 
    // Dados de exemplo (substituir por dados reais quando ligares à BD)
    $cert = (object) [
        'segurado'        => 'NOVA SOTECMA, S.A.',
        'morada'          => 'AV. DEOLINDA RODRIGUES, 399 RANGEL, LUANDA - ANGOLA',
        'apolice'         => '80.00000031',
        'certificado'     => '2025.0000996',
        'descricao'       => 'De acordo com as facturas a seguir referidas e anexas ao presente Certificado.',
        'empresa_factura' => 'Empresa: Kumpel - Factura nº 1',
        'moeda'           => 'EUR',
        'valor_seguro'    => '96.534,00',
        'soma'            => '96.534,00',
        'despesas'        => '',
        'total'           => '96.534,00',
        'claims_dest'     => 'Sinistros pagos em Angola',
        'viagem'          => 'Antuérpia, Bélgica para Luanda, Angola',
        'transbordo'      => '',
        'vessel'          => 'SPIRIT OF SYDNEY',
        'cobertura'       => 'Cláusula de seguros de cargas A (ICC A); Cláusula de Exclusão de Contaminação Radioactiva; Cláusula de riscos de guerra; Cláusula de riscos de greves; Institute Classification Clause',
        'emitido_local'   => 'Luanda',
        'emitido_data'    => '15/01/2025',
    ];
 
    $tempDir = storage_path('app/mpdf');
    if (!is_dir($tempDir)) {
        mkdir($tempDir, 0775, true);
    }
 
    $mpdf = new \Mpdf\Mpdf([
        'mode'              => 'utf-8',
        'format'            => 'A4',
        'margin_top'        => 22,   // espaço para o logótipo do header
        'margin_header'     => 5,
        'margin_bottom'     => 24,
        'margin_footer'     => 6,
        'margin_left'       => 20,
        'margin_right'      => 16,
        'default_font'      => 'calibri',
        'default_font_size' => 8,
        'autoScriptToLang'  => true,
        'autoLangToFont'    => true,
        'tempDir'           => $tempDir,
    ]);
 
    // ---- LOGÓTIPO NO HEADER (alinhado à direita, como no certificado original)
    $headerLogoPath = public_path('img/cotacao/bic_image-header.png');
    if (file_exists($headerLogoPath)) {
        $mpdf->DefHTMLHeaderByName(
            'main_header',
            '<div style="text-align:right;padding-top:2px;">
                <img src="' . $headerLogoPath . '" style="height:64px;" alt="BIC Seguros">
            </div>'
        );
        $mpdf->SetHTMLHeaderByName('main_header', 'O');
        $mpdf->SetHTMLHeaderByName('main_header', 'E');
    }
 
    // Rodapé do certificado de carga: telefone 226443670 + numeração de páginas
    $mpdf->SetHTMLFooter(
        '<div style="text-align:center;color:#c4161c;font-size:6pt;line-height:1.25;border-top:0.5px solid #999;padding-top:3px;">
            BIC SEGUROS, S.A.; Matriculada na Conservatória de Registo Comercial de Luanda 205/2014<br>
            Capital Social 6.000.000.000 kwanzas&nbsp;&nbsp;&nbsp;NIF: 5402163774<br>
            Sede Social: Rua N\'Gola M\'Bandi, R/C, Município da Maianga, Província de Luanda<br>
            Telefone Geral: 226443670&nbsp;&nbsp;&nbsp;www.bicseguros.ao<br>
            <div style="text-align:right">
            Pág. {PAGENO}/{nbpg}</div>
        </div>'
    );
 
    $cssPath = public_path('css/pdf/quotation-generate/cargo-certificate.css');
    $mpdf->WriteHTML(file_get_contents($cssPath), \Mpdf\HTMLParserMode::HEADER_CSS);
 
    $html = view('pdf.certificado-mercadorias.index', ['cert' => $cert])->render();
    $mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);
 
    return response($mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN), 200)
        ->header('Content-Type', 'application/pdf')
        ->header('Content-Disposition', 'inline; filename="certificado_seguro_mercadorias.pdf"');
});
 
 
// =========================================================================
// 2) CONDIÇÕES PARTICULARES — ACIDENTES PESSOAIS GRUPO
// =========================================================================
Route::get('/teste/condicoes-ap', function () {
 
    // Dados de exemplo (substituir por dados reais quando ligares à BD)
    $cp = (object) [
        'ramo'             => 'AP GRP.PROF/EXTRA PROFISSIONAL',
        'apolice'          => '14-00000048',
        'acta'             => '',
        'tomador_num'      => '002825',
        'tipo_doc'         => 'SEGURO NOVO',
 
        'dest_nome'        => 'BIC SEGUROS, S.A',
        'dest_morada'      => "RUA N'GOLA M'BANDI, R/C",
        'dest_local'       => 'MAIANGA',
        'dest_nif'         => '5402163774',
 
        'inicio'           => '28/02/2026',
        'modalidade'       => 'TEMPORÁRIO',
        'termo'            => '28/02/2026',
        'premio_comercial' => '31.502,00 Kz',
        'fraccionamento'   => 'ÚNICO',
        'capital_seguro'   => '27.000.000,00 Kz',
 
        'riscos_garantidos' => 'PROFISSIONAL E EXTRAPROFISSIONAL',
 
        'coberturas' => [
            ['nome' => 'MORTE OU INVALIDEZ PERMANENTE',         'valor' => '25.000.000,00 Kz', 'franquia' => 'SEM FRANQUIA'],
            ['nome' => 'DESPESAS FUNERAL',                      'valor' => '500.000,00 Kz',    'franquia' => 'SEM FRANQUIA'],
            ['nome' => 'DESPESAS DE TRATAMENTO E REPATRIAMENTO', 'valor' => '1.500.000,00 Kz', 'franquia' => 'SEM FRANQUIA'],
        ],
 
        'pessoas_seguras' => 'TODOS OS PARTICIPANTES NO EVENTO 2ª ORDEM DE MÉRITO GOLF MANGAIS.',
        'local_data'      => 'LUANDA, 27 de Fevereiro de 2026',
        'pagina'          => '01/última',
    ];
 
    $tempDir = storage_path('app/mpdf');
    if (!is_dir($tempDir)) {
        mkdir($tempDir, 0775, true);
    }
 
    $mpdf = new \Mpdf\Mpdf([
        'mode'              => 'utf-8',
        'format'            => 'A4',
        'margin_top'        => 26,   // espaço para o logótipo + título do header
        'margin_header'     => 6,
        'margin_bottom'     => 30,
        'margin_footer'     => 6,
        'margin_left'       => 12,
        'margin_right'      => 25,
        'default_font'      => 'calibri',
        'default_font_size' => 8,
        'autoScriptToLang'  => true,
        'autoLangToFont'    => true,
        'tempDir'           => $tempDir,
    ]);
 
    // ---- LOGÓTIPO (esquerda) + TÍTULO (direita) NO HEADER
    $headerLogoPath = public_path('img/cotacao/bic_image-header.png');
    if (file_exists($headerLogoPath)) {
        $mpdf->DefHTMLHeaderByName(
            'main_header',
            '<table style="width:100%;border-collapse:collapse;">
                <tr>
                    <td style="width:50%;vertical-align:middle;">
                        <img src="' . $headerLogoPath . '" style="height:90px;" alt="BIC Seguros">
                    </td>
                    <td style="width:50%;text-align:right;vertical-align:middle;color:#f60a12;font-weight:bold;font-size:16pt;">
                        CONDIÇÕES PARTICULARES
                    </td>
                </tr>
            </table>'
        );
        $mpdf->SetHTMLHeaderByName('main_header', 'O');
        $mpdf->SetHTMLHeaderByName('main_header', 'E');
    }
 
    // Rodapé das CP: telefone 923 120 900 + email, sem numeração de páginas
    $mpdf->SetHTMLFooter(
        '<div style="text-align:center;;font-size:6pt;line-height:1.25;padding-top:3px;">
            BIC SEGUROS, S.A.; Matriculada na Conservatória de Registo Comercial de Luanda 205/2014<br>
            Capital Social 6.000.000.000 kwanzas&nbsp;&nbsp;&nbsp;NIF: 5402163774<br>
            Sede Social: Rua N\'Gola M\'Bandi, R/C, Município da Maianga, Província de Luanda<br>
            Telefone Geral: 923 120 900&nbsp;&nbsp;&nbsp;www.bicseguros.ao&nbsp;&nbsp;&nbsp;email: geral@bicseguros.ao
        </div>'
    );
 
    $cssPath = public_path('css/pdf/quotation-generate/conditions-ap.css');
    $mpdf->WriteHTML(file_get_contents($cssPath), \Mpdf\HTMLParserMode::HEADER_CSS);
 
    $html = view('pdf.condicoes-particulares.index', ['cp' => $cp])->render();
    $mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);
 
    return response($mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN), 200)
        ->header('Content-Type', 'application/pdf')
        ->header('Content-Disposition', 'inline; filename="condicoes_particulares_ap_grupo.pdf"');
});