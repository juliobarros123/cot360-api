<?php

namespace App\Http\Controllers\Api\QUO;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use App\Services\QuotationProposalService;
use App\Models\QUO\QUO_quotation_versions;
use App\Models\QUO\QUO_quotation_proposals;
use App\Jobs\SendQuotationProposalJob;

class QUOQuotationProposalController extends Controller
{
    public function __construct(protected QuotationProposalService $service) {}

    public function index(Request $request)
    {
        $filters = $request->only([
            'quotation_version_id',
            'quotation_id',
            'status',
            'client_email',
            'client_name',
            'date_from',
            'date_to',
            'search',
            'include_expired',
            'sort_by',
            'sort_order'
        ]);

        // Este endpoint retorna sempre lista simples (sem paginação)
        $proposals = $this->service->getAll($filters, 0);

        $transformed = $proposals->map(function ($proposal) {
            $version = $proposal->quotationVersion;
            $quotation = $version?->quotation;

            return [
                'id' => $proposal->id,
                'client_email' => $proposal->client_email,
                'client_name' => $proposal->client_name,
                'status' => $proposal->status,
                'status_formatted' => $this->formatStatus($proposal->status),
                'sent_at' => $proposal->sent_at?->format('d/m/Y H:i'),
                'viewed_at' => $proposal->viewed_at?->format('d/m/Y H:i'),
                'completed_at' => $proposal->completed_at?->format('d/m/Y H:i'),
                'tracking_token' => $proposal->tracking_token,
                'proposal_pdf_url' => $proposal->proposal_pdf_url,
                'proposal_form_url' => $proposal->proposal_form_url,
                'document_urls' => $proposal->document_urls,
                'quotation_version' => [
                    'id' => $version->id ?? null,
                    'version' => $proposal->quotation_version,
                    'table' => $version->table_code ?? null,
                ],
                'quotation' => $quotation ? [
                    'id' => $quotation->id,
                    'quotation_number' => $quotation->quotation_number,
                ] : null,
                'is_expired' => $proposal->isExpired(),
                'days_remaining' => $proposal->isExpired() ? 0 : $proposal->getDaysRemaining(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'proposals' => $transformed->values(),
            ]
        ]);
    }

    public function listSimple(Request $request)
    {
        $filters = $request->only(['quotation_version_id', 'quotation_id', 'only_active']);
        $query = QUO_quotation_proposals::query();

        if ($filters['quotation_version_id'] ?? false) {
            $query->where('quotation_version_id', $filters['quotation_version_id']);
        }
        if ($filters['quotation_id'] ?? false) {
            $query->whereHas('quotationVersion', fn($q) => $q->where('quotation_id', $filters['quotation_id']));
        }
        if ($filters['only_active'] ?? false) {
            $query->where('status', '!=', 'expired')
                ->whereRaw('DATE_ADD(sent_at, INTERVAL 30 DAY) >= NOW()');
        }

        $result = $query->latest('created_at')->get()->map(fn($proposal) => [
            'id' => $proposal->id,
            'label' => $proposal->client_name ?: $proposal->client_email,
            'value' => $proposal->id,
            'status' => $proposal->status,
            'sent_at' => $proposal->sent_at?->format('d/m/Y H:i'),
        ]);

        return response()->json(['success' => true, 'data' => $result]);
    }

    public function sendProposal(Request $request)
    {
        $rules = [
            'quotation_version_id' => 'required|exists:quo_quotation_versions,id',
            'quotation_version' => 'required|integer|min:1',
            'client_email' => 'required|email',
            'client_name' => 'nullable|string|max:255',
            'message' => 'nullable|string',
            'proposal_form_path' => 'nullable|string|max:120',
            'proposal_form_url' => 'nullable|url|max:2048',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();

        try {
            $version = QUO_quotation_versions::findOrFail($request->quotation_version_id);

            $token = QUO_quotation_proposals::generateTrackingToken();
            $urls = QUO_quotation_proposals::generateUrls(
                $version->quotation_id,
                $request->quotation_version,
                $token,
                $request->proposal_form_path,
                $request->proposal_form_url
            );
            // dd($urls);

            $proposal = $this->service->create([
                'quotation_version_id' => $request->quotation_version_id,
                'quotation_version' => $request->quotation_version,
                'client_email' => $request->client_email,
                'client_name' => $request->client_name,
                'message' => $request->message,
                'proposal_pdf_url' => $urls['pdf_url'],
                'proposal_form_url' => $urls['form_url'],
                'tracking_token' => $token,
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            SendQuotationProposalJob::dispatch($proposal->refresh())->onQueue('emails');
            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $proposal->id,
                    'tracking_token' => $proposal->tracking_token,
                    'status' => $proposal->status,
                    'sent_at' => $proposal->sent_at,
                ]
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('proposal send error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function validateToken($token)
    {
        $proposal = $this->service->findByToken($token);
        if (!$proposal || $proposal->isExpired()) {
            return response()->json(['success' => false, 'message' => 'Token inválido ou expirado'], 404);
        }

        return response()->json(['success' => true, 'data' => [
            'id' => $proposal->id,
            'client_name' => $proposal->client_name,
            'client_email' => $proposal->client_email,
            'status' => $proposal->status,
            'quotation_version_id' => $proposal->quotation_version_id,
            'quotation_version' => $proposal->quotation_version,
            'message' => $proposal->message,
            'sent_at' => $proposal->sent_at,
            'is_expired' => false,
        ]]);
    }

    public function trackView($token)
    {
        $proposal = $this->service->findByToken($token);
        if (!$proposal) {
            return response()->json(['success' => false, 'message' => 'Não encontrada'], 404);
        }
        if ($proposal->isExpired()) {
            return response()->json(['success' => false, 'message' => 'Expirada'], 410);
        }
        if ($proposal->status === 'sent') {
            $proposal->markAsViewed();
        }
        return response()->json([
            'success' => true,
            'data' => [
                'pdf_url' => $proposal->proposal_pdf_url,
                'form_url' => $proposal->proposal_form_url,
                'status' => $proposal->status,
            ]
        ]);
    }

    public function getByQuotation($quotationVersionId)
    {
        $proposals = $this->service->getByVersion($quotationVersionId);
        return response()->json(['success' => true, 'data' => $proposals]);
    }

    public function markAsCompleted(Request $request, $token)
    {
        $validator = Validator::make($request->all(), [
            'client_data' => 'required|array',
            'client_data.email' => 'required|email',
            'client_data.name' => 'required|string',
            'client_data.pep_status' => 'nullable|in:Not_Checked,Not_PEP,PEP,Related_PEP',
            'client_data.us_person' => 'nullable|boolean',
            'client_data.pep_role' => 'nullable|string|max:255',
            'client_data.pep_organization' => 'nullable|string|max:255',
            'client_data.pep_country' => 'nullable|string|max:100',
            'client_data.pep_from' => 'nullable|date',
            'client_data.pep_to' => 'nullable|date',
            'client_data.pep_relationship' => 'nullable|string|max:255',
            'client_data.pep_declaration' => 'nullable|array',
            'client_data.pep_declaration.pep_status' => 'nullable|in:Not_Checked,Not_PEP,PEP,Related_PEP',
            'client_data.pep_declaration.us_person' => 'nullable|boolean',
            'client_data.pep_declaration.role' => 'nullable|string|max:255',
            'client_data.pep_declaration.organization' => 'nullable|string|max:255',
            'client_data.pep_declaration.country' => 'nullable|string|max:100',
            'client_data.pep_declaration.from' => 'nullable|date',
            'client_data.pep_declaration.to' => 'nullable|date',
            'client_data.pep_declaration.relationship' => 'nullable|string|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $proposal = $this->service->findByToken($token);
        if (!$proposal) {
            return response()->json(['success' => false, 'message' => 'Proposta não encontrada'], 404);
        }
        if ($proposal->isExpired()) {
            return response()->json(['success' => false, 'message' => 'Proposta expirada'], 410);
        }

        $clientData = $request->client_data;
        if (!isset($clientData['pep_declaration'])) {
            $clientData['pep_declaration'] = [
                'pep_status' => $clientData['pep_status'] ?? null,
                'us_person' => $clientData['us_person'] ?? null,
                'role' => $clientData['pep_role'] ?? null,
                'organization' => $clientData['pep_organization'] ?? null,
                'country' => $clientData['pep_country'] ?? null,
                'from' => $clientData['pep_from'] ?? null,
                'to' => $clientData['pep_to'] ?? null,
                'relationship' => $clientData['pep_relationship'] ?? null,
            ];
        }

        $pepStatus = $clientData['pep_declaration']['pep_status'] ?? null;
        if (!in_array($pepStatus, ['PEP', 'Related_PEP'], true)) {
            $clientData['pep_declaration']['role'] = null;
            $clientData['pep_declaration']['organization'] = null;
            $clientData['pep_declaration']['country'] = null;
            $clientData['pep_declaration']['from'] = null;
            $clientData['pep_declaration']['to'] = null;
            $clientData['pep_declaration']['relationship'] = null;
        } elseif ($pepStatus !== 'Related_PEP') {
            $clientData['pep_declaration']['relationship'] = null;
        }

        $this->notifyExternal($token, $clientData);
        $proposal->markAsCompleted($clientData);

        return response()->json(['success' => true, 'message' => 'Proposta completada', 'data' => ['status' => $proposal->status]]);
    }

    public function print(int $quotationProposalId)
    {
        $proposal = QUO_quotation_proposals::find($quotationProposalId);

        if (!$proposal) {
            return response()->json([
                'success' => false,
                'message' => 'Proposta não encontrada'
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->renderProposalFormPdf($proposal);
    }

    public function printDocument(int $quotationProposalId, string $document)
    {
        $proposal = QUO_quotation_proposals::find($quotationProposalId);

        if (!$proposal) {
            return response()->json([
                'success' => false,
                'message' => 'Proposta não encontrada'
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->renderStandaloneDocumentPdf($proposal, $document);
    }

    public function printByQuotationVersion(int $quotationVersionId)
    {
        $proposal = QUO_quotation_proposals::where('quotation_version_id', $quotationVersionId)
            ->orderByRaw("CASE WHEN status = 'completed' THEN 0 ELSE 1 END")
            ->latest('created_at')
            ->first();

        if (!$proposal) {
            return response()->json([
                'success' => false,
                'message' => 'Nenhuma proposta encontrada para esta versão'
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->renderProposalFormPdf($proposal);
    }

    private function notifyExternal(string $token, array $clientData): void
    {
        try {
            $apiUrl = config('services.proposal_api.url');
            if (!$apiUrl) {
                return;
            }
            \Illuminate\Support\Facades\Http::post($apiUrl, [
                'proposal_token' => $token,
                'client_data' => $clientData,
                'action' => 'completed',
            ]);
        } catch (\Throwable $e) {
            Log::warning('external notify failed: ' . $e->getMessage());
        }
    }

    private function formatStatus(string $status): string
    {
        return match ($status) {
            'sent' => 'Enviada',
            'viewed' => 'Visualizada',
            'completed' => 'Completada',
            'expired' => 'Expirada',
            default => $status,
        };
    }

    private function renderProposalFormPdf(QUO_quotation_proposals $proposal)
    {
        $type = $this->resolveProposalType($proposal);

        // dd($type);
        $viewMap = [
            'auto' => 'pdf.proposal.index',
            'acidentes_pessoais' => 'pdf.acidentes-pessoais.index',
            'acidentes_trabalho' => 'pdf.acidentes-trabalho.index',
            'mr_empresas' => 'pdf.mr-empresas.index',
            'saude_familia' => 'pdf.saude-familia.index',
            'saude_grupo' => 'pdf.saude-grupo.index',
        ];
        $fileNameMap = [
            'auto' => 'proposta_seguro_auto',
            'acidentes_pessoais' => 'proposta_acidentes_pessoais',
            'acidentes_trabalho' => 'proposta_acidentes_trabalho',
            'mr_empresas' => 'proposta_mr_empresas',
            'saude_familia' => 'proposta_saude_familia',
            'saude_grupo' => 'proposta_saude_grupo',
        ];
        $cssPathMap = [
            'auto' => public_path('css/pdf/quotation-generate/proposta-unica.css'),
            'acidentes_pessoais' => public_path('css/pdf/quotation-generate/quotation-proposals.css'),
            'acidentes_trabalho' => public_path('css/pdf/quotation-generate/health-proposals.css'),
            'mr_empresas' => public_path('css/pdf/quotation-generate/proposta-unica.css'),
            'saude_familia' => public_path('css/pdf/quotation-generate/health-proposals.css'),
            'saude_grupo' => public_path('css/pdf/quotation-generate/health-proposals.css'),
        ];

        if (!isset($viewMap[$type])) {
            return response()->json([
                'success' => false,
                'message' => 'Tipo de proposta não suportado',
            ], Response::HTTP_BAD_REQUEST);
        }

        $normalizedProposalData = $this->normalizeClientData($proposal->client_data ?? []);
        // dd("ola");
        return $this->renderPdfView(
            proposal: $proposal,
            view: $viewMap[$type],
            filePrefix: $fileNameMap[$type],
            data: [
                'proposal' => (object) $normalizedProposalData,
                'quotationProposal' => $proposal,
            ],
            options: [
                'textDocFooter' => '<div style="text-align: center;color:red;border-top: 0.1px solid #5a5858;  font-size: 6pt; line-height: 1.2;">
                       
                        BIC SEGUROS, S.A.; Matriculada na Conservatória de Registo Comercial de Luanda 205/2014<br>
                        Capital Social 1.500.000.000 kwanzas NIF: 5402163774<br>
                        Sede Social: Rua N\'Gola M\'Bandi, R/C, Município da Maianga, Província de Luanda<br>
                        Telefone Geral: 226443670 <a href="https://www.bicseguros.ao/" style="color:red">www.bicseguros.ao</a><br>
                    </div>
                        
                    <table style="width:100%; font-size:1pt; border-collapse:collapse">
                        <tr>
                            <td style="text-align:left; width:50%;font-size:8pt;"></td>
                            <td style="text-align:right; width:50%;font-size:6pt;color:red">Pág. {PAGENO}/{nbpg}</td>
                        </tr>
                    </table>
                    ',
                'css_path' => $cssPathMap[$type] ?? public_path('css/pdf/quotation-generate/proposta-unica.css'),
                'font_dir' => [storage_path('fonts')],
                'margin_top'          => 20,
                'margin_header' => 5,
                'margin_bottom'       => 40,
                'margin_left'         => 20,
                'margin_right'        => 20,
                'height_logo' => 60,
                'margin_footer' => 5,
                'margin_left_logo' => 500,
                'font_data' => [
                    'calibri' => [
                        'R'  => 'Carlito-Regular.ttf',
                        'B'  => 'Carlito-Bold.ttf',
                        'I'  => 'Carlito-Italic.ttf',
                        'BI' => 'Carlito-BoldItalic.ttf',
                    ],
                ],
                'default_font'        => 'calibri', // 👈 faltava isto
                'default_font_size'   => 10,
                'html_parser_mode'    => \Mpdf\HTMLParserMode::DEFAULT_MODE,
                'header_logo_path'    => public_path('img/cotacao/bic_seguro.png'),
                'show_page_footer'    => true,
            ]
        );
    }

    private function renderStandaloneDocumentPdf(QUO_quotation_proposals $proposal, string $document)
    {
        $normalizedProposalData = $this->normalizeClientData($proposal->client_data ?? []);

        $documentMap = [
            'pep' => [
                'view' => 'pdf.compliance.pep',
                'file_prefix' => 'declaracao_pessoa_politicamente_exposta',
                'payload' => $this->extractPepDocumentData($normalizedProposalData),
                'css_path' => public_path('css/pdf/quotation-generate/compliance.css'),
            ],
            'beneficiario-efectivo' => [
                'view' => 'pdf.compliance.beneficiario-efectivo',
                'file_prefix' => 'declaracao_beneficiario_efectivo',
                'payload' => $this->extractBeneficiarioEfectivoData($normalizedProposalData),
                'css_path' => public_path('css/pdf/quotation-generate/compliance.css'),
            ],
            'origem-destino-fundos' => [
                'view' => 'pdf.compliance.origem-destino-fundos',
                'file_prefix' => 'declaracao_origem_destino_fundos',
                'payload' => $this->extractOrigemDestinoFundosData($normalizedProposalData),
                'css_path' => public_path('css/pdf/quotation-generate/compliance.css'),
            ],
        ];

        if (!isset($documentMap[$document])) {
            return response()->json([
                'success' => false,
                'message' => 'Documento não suportado',
            ], Response::HTTP_NOT_FOUND);
        }

        $config = $documentMap[$document];

        return $this->renderPdfView(
            proposal: $proposal,
            view: $config['view'],
            filePrefix: $config['file_prefix'],
            data: [
                'proposal' => (object) $config['payload'],
                'quotationProposal' => $proposal,
            ],
            options: [
                'use_default_chrome'  => false,
                'css_path'            => $config['css_path'],
                'margin_top'          => 10,
                'margin_bottom'       => 24,
                'margin_left'         => 24,
                'margin_right'        => 24,
                'font_dir'            => [storage_path('fonts')],
                'font_data'           => [
                    'calibri' => [
                        'R'  => 'Carlito-Regular.ttf',
                        'B'  => 'Carlito-Bold.ttf',
                        'I'  => 'Carlito-Italic.ttf',
                        'BI' => 'Carlito-BoldItalic.ttf',
                    ],
                ],
                'default_font'        => 'calibri', // 👈 faltava isto
                'default_font_size'   => 10,
                'html_parser_mode'    => \Mpdf\HTMLParserMode::DEFAULT_MODE,
                'header_logo_path'    => public_path('img/cotacao/bic_seguro.png'),
                'show_page_footer'    => true,
            ]
        );
    }

    private function renderPdfView(
        QUO_quotation_proposals $proposal,
        string $view,
        string $filePrefix,
        array $data,
        array $options = []
    ) {
        try {
            $tempDir = storage_path('app/mpdf');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0775, true);
            }

            $useDefaultChrome = $options['use_default_chrome'] ?? true;
            // dd( $options['css_path'] );
            $heightLogo = $options['height_logo'] ?? 45;
            $marginLeftLogo = $options['margin_left_logo'] ?? 8;
            $cssPath = $options['css_path'] ?? public_path('css/pdf/quotation-generate/proposta-unica.css');
            // dd("ola");
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_top' => $options['margin_top'] ?? null,
                'margin_header' => $options['margin_header'] ?? 10,
                'margin_bottom' => $options['margin_bottom'] ?? 100,
                'margin_footer' => $options['margin_footer'] ?? 14,
                'margin_left' => $options['margin_left'] ?? 15,
                'margin_right' => $options['margin_right'] ?? 15,
                'default_font_size' => $options['default_font_size'] ?? 10,
                'default_font' => $options['default_font'] ?? 'calibri',
                'autoScriptToLang' => true,
                'autoLangToFont' => true,
                'tempDir' => $tempDir,
            ]);
            // dd("ola");
            if ($useDefaultChrome) {
                $mpdf->DefHTMLHeaderByName(
                    'main_header',
                    '<div style="text-align: center; font-size: 9pt; border-bottom: 1px solid #000; padding-bottom: 5px;">
                        BIC SEGUROS, S.A.; Matriculada na Conservatória de Registo Comercial de Luanda 205/2014<br>
                        Capital Social 1.500.000.000 kwanzas NIF: 5402163774<br>
                        Sede Social: Rua N\'Gola M\'Bandi, R/C, Município da Maianga, Província de Luanda<br>
                        Telefone Geral: 226443670 www.bicseguros.ao
                    </div>'
                );

                $mpdf->SetHTMLFooter('
                    <div style="text-align: center;color:red;  font-size: 6pt; line-height: 1.2;">
                        <hr style="margin-top:0px;padding-top:0px;width:80%;color:black; line-height:0px;">
                        BIC SEGUROS, S.A.; Matriculada na Conservatória de Registo Comercial de Luanda 205/2014<br>
                        Capital Social 1.500.000.000 kwanzas NIF: 5402163774<br>
                        Sede Social: Rua N\'Gola M\'Bandi, R/C, Município da Maianga, Província de Luanda<br>
                        Telefone Geral: 226443670 <a href="https://www.bicseguros.ao/" style="color:red">www.bicseguros.ao</a><br>
                        Página {PAGENO} de {nbpg}
                    </div>');
            }

            if (!empty($options['header_logo_path']) && file_exists($options['header_logo_path'])) {
                $headerLogoPath = $options['header_logo_path'];
                $mpdf->DefHTMLHeaderByName(
                    'main_header',
                    '<div style="padding-top:2px;">
                        <img src="' . $headerLogoPath . '" style="height:' . $heightLogo . 'px;margin-left:' . $marginLeftLogo . 'px" alt="BIC Seguros">
                    </div>'
                );
                $mpdf->SetHTMLHeaderByName('main_header', 'O');
                $mpdf->SetHTMLHeaderByName('main_header', 'E');
            }
            $textDocFooter = $options['textDocFooter'] ?? '<table style="width:100%; font-size:1pt; border-collapse:collapse">
                        <tr>
                            <td style="text-align:left; width:50%;font-size:8pt;">BIC Seguros, SA</td>
                            <td style="text-align:right; width:50%;font-size:8pt;">Pág. {PAGENO}/{nbpg}</td>
                        </tr>
                    </table>';
            // dd($textDocFooter);
            if (!empty($options['show_page_footer'])) {
                $mpdf->DefHTMLFooterByName(
                    'compliance_footer',
                    $textDocFooter . '
                  '
                );
                $mpdf->SetHTMLFooterByName('compliance_footer', 'O');
                $mpdf->SetHTMLFooterByName('compliance_footer', 'E');
            }

            $html = view($view, $data)->render();

            if ($cssPath) {
                if (!file_exists($cssPath)) {
                    throw new \Exception("CSS file not found at: {$cssPath}");
                }

                $css = file_get_contents($cssPath);
                $mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);
            }

            $htmlParserMode = $options['html_parser_mode'] ?? \Mpdf\HTMLParserMode::HTML_BODY;
            $mpdf->WriteHTML($html, $htmlParserMode);

            if ($useDefaultChrome) {
                $mpdf->SetHTMLHeaderByName('main_header', 'O');
            }

            $fileName = $filePrefix . '_' . now()->format('YmdHis') . '_' . $proposal->id . '.pdf';
            $pdfContent = $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);

            return response($pdfContent, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="' . $fileName . '"');
        } catch (\Throwable $e) {
            Log::error('Erro ao gerar PDF de formulário de proposta', [
                'proposal_id' => $proposal->id,
                'view' => $view,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar PDF do formulário',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Determina o tipo da proposta para escolher a view do PDF.
     *
     * Adapta este método à tua modelagem actual. Algumas estratégias possíveis:
     *
     * 1) Se tens um campo directo:
     *    return $proposal->product_type;            // 'auto' | 'acidentes_pessoais' | ...
     *
     * 2) Se vai pela quotation_version → product:
     *    return $proposal->quotationVersion->product->slug ?? 'auto';
     *
     * 3) Se vai pelo client_data:
     *    return $proposal->client_data['productType'] ?? 'auto';
     *
     * Abaixo está uma implementação defensiva que tenta várias hipóteses.
     */
    private function resolveProposalType(QUO_quotation_proposals $proposal): string
    {
        // Regra principal: ramo da cotação (determinístico)
        $proposal->loadMissing('quotationVersion.quotation.branch');
        $branchCode = (int) data_get($proposal, 'quotationVersion.quotation.branch.code', 0);
        if (in_array($branchCode, [90, 91], true)) return 'auto';
        if (in_array($branchCode, [14, 15], true)) return 'acidentes_pessoais';
        if ($branchCode === 10) return 'acidentes_trabalho';
        if (in_array($branchCode, [34, 32], true)) return 'mr_empresas';
        if ($branchCode === 18) return 'saude_familia';   // BIC Família Segura
        if ($branchCode === 20) return 'saude_grupo';     // Saúde Grupo
        // Hipótese 1: campo directo na tabela
        if (!empty($proposal->product_type)) {
            return $this->normalizeProductType($proposal->product_type);
        }

        // Hipótese 2: dentro do client_data
        $clientData = is_array($proposal->client_data)
            ? $proposal->client_data
            : (json_decode($proposal->client_data ?? '[]', true) ?: []);

        if (!empty($clientData['productType'])) {
            return $this->normalizeProductType($clientData['productType']);
        }
        if (!empty($clientData['product_type'])) {
            return $this->normalizeProductType($clientData['product_type']);
        }

        // Hipótese 3: relação com quotation_version → product
        if (method_exists($proposal, 'quotationVersion') && $proposal->quotationVersion) {
            $product = $proposal->quotationVersion->product ?? null;
            if ($product) {
                $slug = $product->slug ?? $product->code ?? $product->name ?? null;
                if ($slug) {
                    return $this->normalizeProductType($slug);
                }
            }
        }

        // Default: auto (mantém compatibilidade)
        return 'auto';
    }

    private function normalizeProductType(string $value): string
    {
        $v = strtolower(trim($value));
        $v = str_replace(['-', ' '], '_', $v);

        $aliases = [
            // auto
            'auto' => 'auto',
            'automovel' => 'auto',
            'automóvel' => 'auto',
            'seguro_auto' => 'auto',
            'seguro_automovel' => 'auto',

            // acidentes pessoais
            'ap' => 'acidentes_pessoais',
            'acidentes_pessoais' => 'acidentes_pessoais',
            'proposal_acidentes_pessoais' => 'acidentes_pessoais',

            // acidentes de trabalho
            'at' => 'acidentes_trabalho',
            'acidentes_trabalho' => 'acidentes_trabalho',
            'acidentes_de_trabalho' => 'acidentes_trabalho',
            'proposal_acidentes_trabalho' => 'acidentes_trabalho',

            // multi-riscos empresas
            'mr' => 'mr_empresas',
            'mr_empresas' => 'mr_empresas',
            'multi_riscos' => 'mr_empresas',
            'multi_riscos_empresas' => 'mr_empresas',
            'multiriscos' => 'mr_empresas',
            'multiriscos_empresas' => 'mr_empresas',
            'proposal_mr_empresas' => 'mr_empresas',
            'mr_habitacao' => 'mr_empresas',
            'multi_riscos_habitacao' => 'mr_empresas',
            'multiriscos_habitacao' => 'mr_empresas',
            'proposal_mr_habitacao' => 'mr_empresas',

            // saude familia
            'saude_familia' => 'saude_familia',
            'saude_familia_segura' => 'saude_familia',
            'familia_segura' => 'saude_familia',
            'bic_familia_segura' => 'saude_familia',
            'proposal_saude_familia' => 'saude_familia',

            // saude grupo
            'saude_grupo' => 'saude_grupo',
            'seguro_saude_grupo' => 'saude_grupo',
            'proposal_saude_grupo' => 'saude_grupo',
        ];

        return $aliases[$v] ?? 'auto';
    }


    private function normalizeClientData(array $clientData): array
    {
        $normalized = [];

        foreach ($clientData as $key => $value) {
            if (is_string($key)) {
                $normalized[Str::snake($key)] = $value;
            }
        }

        // Compatibilidade com nomes de campos usados no template legado
        $normalized['ligacao_tomador'] = $normalized['ligacao_tomador'] ?? ($normalized['condutor_ligacao_tomador'] ?? null);
        $normalized['entidade_credora_nome'] = $normalized['entidade_credora_nome'] ?? ($normalized['credora_nome'] ?? null);
        $normalized['entidade_credora_agencia'] = $normalized['entidade_credora_agencia'] ?? ($normalized['credora_agencia'] ?? null);
        $normalized['entidade_credora_localidade'] = $normalized['entidade_credora_localidade'] ?? ($normalized['credora_localidade'] ?? null);
        $normalized['entidade_credora_telem'] = $normalized['entidade_credora_telem'] ?? ($normalized['credora_telem'] ?? null);
        $normalized['entidade_credora_email'] = $normalized['entidade_credora_email'] ?? ($normalized['credora_email'] ?? null);
        $normalized['veiculo_modelo'] = $normalized['veiculo_modelo'] ?? ($normalized['veiculo_modelo_versao'] ?? null);
        $normalized['debito_nib'] = $normalized['debito_nib'] ?? ($normalized['debito_iban'] ?? null);
        $normalized['produtos_bic_seguros'] = $normalized['produtos_bic_seguros'] ?? ($normalized['bic_produtos'] ?? null);
        $normalized['seguradora_anterior'] = $normalized['seguradora_anterior'] ?? ($normalized['anterior_seguradora'] ?? null);
        $normalized['apolice_anterior'] = $normalized['apolice_anterior'] ?? ($normalized['anterior_apolice_num'] ?? null);
        $normalized['premio_anterior'] = $normalized['premio_anterior'] ?? ($normalized['anterior_premio'] ?? null);
        $normalized['detalhes_debitos'] = $normalized['detalhes_debitos'] ?? ($normalized['premios_divida_detalhes'] ?? null);
        $normalized['conheceu_outros'] = $normalized['conheceu_outros'] ?? ($normalized['conheceu_outros_detalhes'] ?? null);
        $normalized['preferencia_outros'] = $normalized['preferencia_outros'] ?? ($normalized['preferencia_outros_detalhes'] ?? null);

        return $normalized;
    }

    private function extractPepDocumentData(array $normalized): array
    {
        return [
            'pep_status' => $normalized['pep_status'] ?? 'Not_Checked',
            'pep_full_name' => $normalized['pep_full_name'] ?? null,
            'pep_role' => $normalized['pep_role'] ?? null,
            'pep_from' => $normalized['pep_from'] ?? null,
            'pep_to' => $normalized['pep_to'] ?? null,
            'pep_id_document' => $normalized['pep_id_document'] ?? null,
            'pep_id_issued_by' => $normalized['pep_id_issued_by'] ?? null,
            'pep_id_issue_date' => $normalized['pep_id_issue_date'] ?? null,
            'pep_nif' => $normalized['pep_nif'] ?? null,
            'pep_activity_status' => $normalized['pep_activity_status'] ?? null,
            'declaracao_texto' => $normalized['declaracao_texto'] ?? null,
            'declaracao_representante_assinatura' => $normalized['declaracao_representante_assinatura'] ?? null,
            'declaracao_representante_data' => $normalized['declaracao_representante_data'] ?? null,
            'declaracao_operacao_assinatura' => $normalized['declaracao_operacao_assinatura'] ?? null,
            'declaracao_operacao_data' => $normalized['declaracao_operacao_data'] ?? null,
            'declaracao_compliance_assinatura' => $normalized['declaracao_compliance_assinatura'] ?? null,
            'declaracao_compliance_data' => $normalized['declaracao_compliance_data'] ?? null,
        ];
    }

    private function extractBeneficiarioEfectivoData(array $normalized): array
    {
        return [
            'be_entity_denominacao_social' => $normalized['be_entity_denominacao_social'] ?? null,
            'be_entity_endereco_sede' => $normalized['be_entity_endereco_sede'] ?? null,
            'be_entity_representada_por' => $normalized['be_entity_representada_por'] ?? null,
            'be_entity_tipo_sociedade' => $normalized['be_entity_tipo_sociedade'] ?? null,
            'be_entity_socios_accionistas' => $normalized['be_entity_socios_accionistas'] ?? null,
            'be_entity_membros_orgaos_sociais' => $normalized['be_entity_membros_orgaos_sociais'] ?? null,
            'be_entity_beneficiario_efectivo' => $normalized['be_entity_beneficiario_efectivo'] ?? null,
            'be_entity_o_representante' => $normalized['be_entity_o_representante'] ?? null,
            'declaracao_texto' => $normalized['declaracao_texto'] ?? null,
            'declaracao_representante_assinatura' => $normalized['declaracao_representante_assinatura'] ?? null,
            'declaracao_representante_data' => $normalized['declaracao_representante_data'] ?? null,
            'declaracao_operacao_assinatura' => $normalized['declaracao_operacao_assinatura'] ?? null,
            'declaracao_operacao_data' => $normalized['declaracao_operacao_data'] ?? null,
            'declaracao_compliance_assinatura' => $normalized['declaracao_compliance_assinatura'] ?? null,
            'declaracao_compliance_data' => $normalized['declaracao_compliance_data'] ?? null,
        ];
    }

    private function extractOrigemDestinoFundosData(array $normalized): array
    {
        return [
            'be_entity_denominacao_social' => $normalized['be_entity_denominacao_social'] ?? null,
            'be_entity_endereco_sede' => $normalized['be_entity_endereco_sede'] ?? null,
            'odf_registo_comercial' => $normalized['odf_registo_comercial'] ?? null,
            'odf_nif' => $normalized['odf_nif'] ?? null,
            'odf_cae' => $normalized['odf_cae'] ?? null,
            'odf_atividade_economica_principal' => $normalized['odf_atividade_economica_principal'] ?? null,
            'odf_origem_atividade_economica' => !empty($normalized['odf_origem_atividade_economica']),
            'odf_origem_investimento_estrangeiro' => !empty($normalized['odf_origem_investimento_estrangeiro']),
            'odf_origem_rendimentos_capital' => !empty($normalized['odf_origem_rendimentos_capital']),
            'odf_origem_doacoes' => !empty($normalized['odf_origem_doacoes']),
            'odf_origem_emprestimo_bancario' => !empty($normalized['odf_origem_emprestimo_bancario']),
            'odf_origem_solidariedade_social' => !empty($normalized['odf_origem_solidariedade_social']),
            'odf_origem_rendimentos_propriedades' => !empty($normalized['odf_origem_rendimentos_propriedades']),
            'odf_origem_outras' => !empty($normalized['odf_origem_outras']),
            'odf_origem_outras_descricao' => $normalized['odf_origem_outras_descricao'] ?? null,
            'odf_finalidade_valor' => $normalized['odf_finalidade_valor'] ?? null,
            'odf_finalidade_valor_extenso' => $normalized['odf_finalidade_valor_extenso'] ?? null,
            'declaracao_texto' => $normalized['declaracao_texto'] ?? null,
            'declaracao_representante_assinatura' => $normalized['declaracao_representante_assinatura'] ?? null,
            'declaracao_representante_data' => $normalized['declaracao_representante_data'] ?? null,
            'declaracao_operacao_assinatura' => $normalized['declaracao_operacao_assinatura'] ?? null,
            'declaracao_operacao_data' => $normalized['declaracao_operacao_data'] ?? null,
            'declaracao_compliance_assinatura' => $normalized['declaracao_compliance_assinatura'] ?? null,
            'declaracao_compliance_data' => $normalized['declaracao_compliance_data'] ?? null,
        ];
    }


    /*
|--------------------------------------------------------------------------
| ADICIONAR EM: App\Http\Controllers\Api\QUO\QUOQuotationProposalController
|--------------------------------------------------------------------------
|
| Cola estes métodos dentro da classe QUOQuotationProposalController.
| O `use Symfony\Component\HttpFoundation\Response;` já existe no teu ficheiro.
|
| Views esperadas:
|   - resources/views/pdf/certificado-mercadorias/index.blade.php
|   - resources/views/pdf/condicoes-particulares/index.blade.php
|
| Rotas sugeridas (routes/api.php):
|   Route::get('certificados/mercadorias/{id?}', [QUOQuotationProposalController::class, 'printCargoCertificate']);
|   Route::get('condicoes-particulares/ap-grupo/{id?}', [QUOQuotationProposalController::class, 'printApGrupoConditions']);
|
| NOTA: tal como no renderProposalFormPdf, o construtor do mPDF aqui é o mesmo
| do teu pipeline atual (a fonte 'calibri'/Carlito é resolvida pelo teu ambiente).
| A fonte monoespaçada do documento AP usa 'courier' (fonte core do mPDF, sempre
| disponível), por isso não precisa de registo adicional.
|
*/
 
// =========================================================================
// 1) ENDPOINTS PÚBLICOS
// =========================================================================

    /**
     * Certificado de Seguro de Mercadorias (Cargo Insurance Certificate).
     * 2 páginas, bilingue. Logótipo no corpo (controlo total de layout).
     */
    public function printCargoCertificate(Request $request, ?int $id = null)
    {
        $cert = (object) $this->buildCargoCertificateData($request, $id);

        return $this->renderIssuedDocumentPdf(
            view: 'pdf.certificado-mercadorias.index',
            filePrefix: 'certificado_seguro_mercadorias',
            data: ['cert' => $cert],
            options: [
                'margin_top'    => 8,
                'margin_bottom' => 24,
                'margin_footer' => 6,
                'margin_left'   => 14,
                'margin_right'  => 14,
                // Certificado de carga -> telefone 226443670, com numeração de páginas
                'footer_html'   => $this->bicCompanyFooter('226443670', withPageNumber: true),
            ]
        );
    }

    /**
     * Condições Particulares — Acidentes Pessoais Grupo (estilo legado monoespaçado).
     * 1 página.
     */
    public function printApGrupoConditions(Request $request, ?int $id = null)
    {
        $cp = (object) $this->buildApGrupoData($request, $id);

        return $this->renderIssuedDocumentPdf(
            view: 'pdf.condicoes-particulares.index',
            filePrefix: 'condicoes_particulares_ap_grupo',
            data: ['cp' => $cp],
            options: [
                'margin_top'    => 12,
                'margin_bottom' => 30,
                'margin_footer' => 6,
                'margin_left'   => 18,
                'margin_right'  => 18,
                // CP -> telefone 923 120 900 + email, sem numeração de páginas
                'footer_html'   => $this->bicCompanyFooter('923 120 900', withEmail: true),
            ]
        );
    }

    // =========================================================================
    // 2) RENDERER GENÉRICO PARA DOCUMENTOS EMITIDOS (não-proposta)
    // =========================================================================

    private function renderIssuedDocumentPdf(
        string $view,
        string $filePrefix,
        array $data,
        array $options = []
    ) {
        try {
            $tempDir = storage_path('app/mpdf');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0775, true);
            }

            $mpdf = new \Mpdf\Mpdf([
                'mode'              => 'utf-8',
                'format'            => 'A4',
                'margin_top'        => $options['margin_top'] ?? 12,
                'margin_header'     => $options['margin_header'] ?? 5,
                'margin_bottom'     => $options['margin_bottom'] ?? 26,
                'margin_footer'     => $options['margin_footer'] ?? 6,
                'margin_left'       => $options['margin_left'] ?? 15,
                'margin_right'      => $options['margin_right'] ?? 15,
                'default_font'      => $options['default_font'] ?? 'calibri',
                'default_font_size' => $options['default_font_size'] ?? 9,
                'autoScriptToLang'  => true,
                'autoLangToFont'    => true,
                'tempDir'           => $tempDir,
            ]);

            if (!empty($options['header_html'])) {
                $mpdf->SetHTMLHeader($options['header_html']);
            }
            if (!empty($options['footer_html'])) {
                $mpdf->SetHTMLFooter($options['footer_html']);
            }

            // O <style> está embutido no Blade -> escrevemos o documento inteiro.
            $html = view($view, $data)->render();
            $mpdf->WriteHTML($html);

            $fileName   = $filePrefix . '_' . now()->format('YmdHis') . '.pdf';
            $pdfContent = $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);

            return response($pdfContent, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="' . $fileName . '"');
        } catch (\Throwable $e) {
            Log::error('Erro ao gerar PDF de documento emitido', [
                'view'    => $view,
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar PDF do documento',
                'error'   => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Rodapé institucional BIC Seguros (Capital Social 6.000.000.000 kz).
     * - Certificado de carga: telefone 226443670 + numeração de páginas.
     * - Condições Particulares: telefone 923 120 900 + email, sem numeração.
     */
    private function bicCompanyFooter(string $phone, bool $withPageNumber = false, bool $withEmail = false): string
    {
        $email = $withEmail ? '&nbsp;&nbsp;&nbsp;email: geral@bicseguros.ao' : '';
        $page  = $withPageNumber ? '<br>Pág. {PAGENO}/{nbpg}' : '';

        return '<div style="text-align:center;color:#c4161c;font-size:6pt;line-height:1.25;border-top:0.5px solid #999;padding-top:3px;">
        BIC SEGUROS, S.A.; Matriculada na Conservatória de Registo Comercial de Luanda 205/2014<br>
        Capital Social 6.000.000.000 kwanzas&nbsp;&nbsp;&nbsp;NIF: 5402163774<br>
        Sede Social: Rua N\'Gola M\'Bandi, R/C, Município da Maianga, Província de Luanda<br>
        Telefone Geral: ' . $phone . '&nbsp;&nbsp;&nbsp;www.bicseguros.ao' . $email . $page . '
    </div>';
    }

    // =========================================================================
    // 3) FONTES DE DADOS (por agora devolvem os valores do exemplo)
    //    Liga estes métodos à tua BD/modelo quando estiveres pronto.
    // =========================================================================

    private function buildCargoCertificateData(Request $request, ?int $id): array
    {
        // TODO: substituir pelos dados reais (ex.: $cert = MarineCertificate::findOrFail($id);)
        return [
            'segurado'        => 'NOVA SOTECMA, S.A.',
            'morada'          => 'AV. DEOLINDA RODRIGUES, 399 RANGEL, LUANDA - ANGOLA',
            'apolice'         => '80.00000031',
            'certificado'     => '2025.0000996',
            'descricao'       => 'De acordo com as facturas a seguir referidas e anexas ao presente Certificado.',
            'empresa_factura' => 'Empresa: Kumpel – Factura nº 1',
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
            // 'stamp'        => public_path('img/cotacao/carimbo_assinatura.png'),
        ];
    }

    private function buildApGrupoData(Request $request, ?int $id): array
    {
        // TODO: substituir pelos dados reais da apólice / acta adicional.
        return [
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
            // 'stamp'        => public_path('img/cotacao/carimbo_assinatura.png'),
        ];
    }
}
