<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
</head>

<body>
    <div class="logo-container"><img src="img/cotacao/icone_capa.jpeg" class="icone_capa" alt=""></div>

    <div class="pdf-container" id="pdfContent">

        {{-- ======================= PÁGINA 1 – CAPA ======================= --}}
        <div class="page-border"></div>
        <div class="header">
            <img class="title-img" src="img/cotacao/title.png" alt="">
        </div>
        <br><br><br><br><br><br>

        <div class="box-cover">
            <img class="cover" src="img/cotacao/quote_AP_ESCOLAR_branch.png" alt="">
        </div>

        @php
            $cotNumber = '-----';
            $quotationNumberValue = $quotation_number ?? null ?? ($quotation['quotation_number'] ?? null);
            if ($quotationNumberValue) {
                $cotNumber = formatarNumeroCotacao($quotationNumberValue);
            }

            $branchName = $selectedBranch['name'] ?? 'ACIDENTES PESSOAIS ESCOLAR';
            $versionNumber = (int) ($version_number ?? 1);
        @endphp
        <br><br><br><br>

        <div class="proposal-title">
            <h1 class="proposal-title-title color-black fw-b">
                <strong>PROPOSTA DE SEGURO ACIDENTES PESSOAIS ESCOLAR</strong>
            </h1>

            <h5 class="color-black">Cotação nº {{ $cotNumber }}@if ($versionNumber > 1)
                    _V{{ $versionNumber }}
                @endif/{{ dataPortugues() }}</h5>
        </div>

        <div class="intro-text" style="margin-top:10px!important;padding-top:-30px;">
            <p>
                Agradecemos a oportunidade concedida, à BIC Seguros para apresentar esta proposta de colaboração, a qual
                fica sujeita a eventuais reajustamentos que sejam julgados convenientes. A nossa sugestão é que
                considerem o conjunto dos serviços oferecidos, e dos meios técnicos e humanos que colocamos à vossa
                disposição, como as ferramentas que permitirão obter um serviço de Excelência, dando assim cumprimento
                às vossas expectativas.
            </p>
            <p>
                Será para nós uma honra colaborar com tão prestigiada entidade na implementação e/ou eventual
                redefinição da vossa política de seguros e, portanto, empenhar-nos-emos a fundo para que todos os
                objectivos definidos possam ser alcançados.
            </p>
        </div>

        @php
            use Carbon\Carbon;
            $dataCarbon = Carbon::now()->locale('pt_BR');
            $dataFormatada = $dataCarbon->translatedFormat('d \d\e F \d\e Y');
            $dataFormatada = preg_replace_callback(
                '/de ([a-z])/',
                function ($m) {
                    return 'de ' . mb_strtoupper($m[1]);
                },
                $dataFormatada,
            );
        @endphp
        <div class="date-text">
            <p class="fw-b color-black" style="text-align:right">Luanda, {{ $dataFormatada }}</p>
        </div>
        <br><br><br><br>

        {{-- ======================= PÁGINA 2 – CONDIÇÕES ======================= --}}
        <div class="page-title color-black">PROPOSTA DE SEGURO ACIDENTES PESSOAIS ESCOLAR</div>
        <div class="page-sub-title red" style="margin-top:3px;">COTAÇÃO Nº {{ $cotNumber }}/{{ dataPortugues() }}
        </div>

        {{-- Tomador do Seguro --}}
        <table class="text-block">
            <tr>
                <td class="left-text bold">Tomador do Seguro</td>
                <td class="right-text">
                    <p><strong>{{ $entity['name'] ?? 'INSTITUIÇÃO LUCRÉCIO DOS SANTOS' }}</strong><br>
                        Luanda, Angola<br>
                        NIF: {{ $entity['nif'] ?? 'A indicar' }}
                    </p>
                </td>
            </tr>
        </table>

        {{-- Código de Actividade Económica (CAE) --}}
        <table class="text-block">
            <tr>
                <td class="left-text bold">Código de Actividade Económica (CAE)</td>
                <td class="right-text">
                    <p>{{ $entity['cae'] ?? 'A indicar' }}</p>
                </td>
            </tr>
        </table>

        {{-- Local de Risco --}}
        <table class="text-block">
            <tr>
                <td class="left-text bold">Local de Risco</td>
                <td class="right-text">
                    <p>Luanda - Angola</p>
                </td>
            </tr>
        </table>

        {{-- Períodos e Datas --}}
        <table class="text-block">
            <tr>
                <td class="left-text bold">Períodos e Datas</td>
                <td class="right-text">
                    <p>Início do Seguro: às 00h00 de ..../..../...... (Em data a indicar)</p>
                    <p>Período do Contrato: Anual Renovável</p>
                </td>
            </tr>
        </table>

        {{-- Tipo de Contrato --}}
        <table class="text-block">
            <tr>
                <td class="left-text bold">Tipo de Contrato</td>
                <td class="right-text">
                    <p>Acidentes Pessoais Escolar</p>
                </td>
            </tr>
        </table>

        {{-- Objecto Seguro --}}
        <table class="text-block">
            <tr>
                <td class="left-text bold">Objecto Seguro</td>
                <td class="right-text">
                    <p>Ficam garantidos os acidentes corporais sofridos pelos alunos do estabelecimento de ensino
                        seguro. Caso estas coberturas sejam contratadas, o seguro poderá ainda garantir a
                        Responsabilidade Civil dos alunos, a Responsabilidade Civil do Estabelecimento de Ensino, a
                        Responsabilidade Civil Cruzada para danos causados pelos alunos entre si e a Responsabilidade
                        Civil Cruzada para danos causados pelo Estabelecimento de Ensino aos alunos e por estes ao
                        Estabelecimento de Ensino. Ficam garantidos os sinistros que ocorram durante a permanência dos
                        alunos no Estabelecimento de Ensino e, ainda, os que ocorram no decurso de visitas de estudo ou
                        outras actividades extracurriculares organizadas pelo Estabelecimento de Ensino.</p>
                </td>
            </tr>
        </table>

        {{-- Garantias do contrato --}}
        <table class="text-block">
            <tr>
                <td class="left-text bold">Garantias do contrato</td>
                <td class="right-text">
                    <p>Garantias Obrigatórias:</p>
                    <p>- Invalidez Permanente</p>
                    <p>- Despesas de Tratamento e Repatriamento</p>
                    <p>- Despesas de Funeral</p>
                    <br>
                    <p>Garantias Facultativas:</p>
                    <p>- Responsabilidade civil dos alunos</p>
                    <p>- Responsabilidade civil do estabelecimento</p>
                    <p>- Responsabilidade civil cruzada dos alunos entre si</p>
                    <p>- Responsabilidade civil cruzada dos alunos e do estabelecimento entre si</p>
                </td>
            </tr>
        </table>

        {{-- Exclusões --}}
        <table class="text-block">
            <tr>
                <td class="left-text bold">Exclusões</td>
                <td class="right-text">
                    <p>Conforme Condições Gerais e Especiais da Apólice</p>
                </td>
            </tr>
        </table>

        {{-- ======================= COBERTURAS E CAPITAIS POR OPÇÃO ======================= --}}
        @php
            // ============================================================
            // CAPITAIS POR OPÇÃO - BUSCA DOS DADOS DO JSON
            // ============================================================

            // Mapeia os capitais a partir dos ratesObject
            $capitaisMap = [];
            foreach ($QLI ?? [] as $qli) {
                if (($qli['status'] ?? false) !== true) {
                    continue;
                }

                $coverageName = strtoupper(
                    trim($qli['coverageObject']['name'] ?? ($qli['coverageObject']['short_name'] ?? '')),
                );
                foreach ($qli['ratesObject'] ?? [] as $rate) {
                    $package = trim($rate['package'] ?? '');
                    if (!empty($package)) {
                        $capital = $rate['capital_value'] ?? null;
                        if (!$capital) {
                            $capital = number_format((float) ($rate['premium_value'] ?? 0) * 100, 0, ',', '.');
                        }
                        $capitaisMap[$coverageName][$package] = $capital;
                    }
                }
            }

            // Fallback com valores do Word
            $capitaisFallback = [
                'INVALIDEZ PERMANENTE' => [
                    'Option A' => '500.000',
                    'Option B' => '1.000.000',
                    'Option C' => '1.500.000',
                ],
                'DESPESAS DE TRATAMENTO E REPATRIAMENTO' => [
                    'Option A' => '125.000',
                    'Option B' => '250.000',
                    'Option C' => '375.000',
                ],
                'DESPESAS DE FUNERAL' => ['Option A' => '150.000', 'Option B' => '150.000', 'Option C' => '175.000'],
                'RESPONSABILIDADE CIVIL DOS ALUNOS' => [
                    'Option A' => '250.000',
                    'Option B' => '250.000',
                    'Option C' => '500.000',
                ],
                'RESPONSABILIDADE CIVIL DO ESTABELECIMENTO' => [
                    'Option A' => '500.000',
                    'Option B' => '500.000',
                    'Option C' => '1.000.000',
                ],
                'RC CRUZADA DOS ALUNOS ENTRE SI' => [
                    'Option A' => '150.000',
                    'Option B' => '150.000',
                    'Option C' => '300.000',
                ],
                'RC CRUZADA DOS ALUNOS E DO ESTABELECIMENTO ENTRE SI' => [
                    'Option A' => '300.000',
                    'Option B' => '300.000',
                    'Option C' => '600.000',
                ],
            ];

            $capitalDe = function ($coverageName, $packageKey) use ($capitaisMap, $capitaisFallback) {
                $sn = strtoupper(trim($coverageName));
                if (isset($capitaisMap[$sn][$packageKey])) {
                    return $capitaisMap[$sn][$packageKey];
                }
                foreach ($capitaisFallback as $key => $values) {
                    if (strpos($sn, strtoupper($key)) !== false || strpos(strtoupper($key), $sn) !== false) {
                        return $values[$packageKey] ?? 'A definir';
                    }
                }
                return 'A definir';
            };

            // Opções (templates)
            $opcoes = collect($templates ?? [])
                ->sortBy('code')
                ->values()
                ->all();
            $selectedPkg = trim($selectedTemplate['package'] ?? '');
            $showAllPackages = $showAllPackages ?? true;

            // Filtra as opções baseado no showAllPackages
            if (!$showAllPackages) {
                $opcoes = array_filter($opcoes, function ($op) use ($selectedPkg) {
                    return trim($op['package'] ?? '') === $selectedPkg;
                });
                $opcoes = array_values($opcoes);
            }

            $coberturasOrdem = [
                'INVALIDEZ PERMANENTE',
                'DESPESAS DE TRATAMENTO E REPATRIAMENTO',
                'DESPESAS DE FUNERAL',
                'RESPONSABILIDADE CIVIL DOS ALUNOS',
                'RESPONSABILIDADE CIVIL DO ESTABELECIMENTO',
                'RC CRUZADA DOS ALUNOS ENTRE SI',
                'RC CRUZADA DOS ALUNOS E DO ESTABELECIMENTO ENTRE SI',
            ];
        @endphp
        <table class="text-block">
            <tr>
                <td class="left-text bold">Capital a Segurar</td>
                <td colspan="2" class="right-text">
                    <table class="table-border tb-coberturas">
                        <tr>
                            <th class="th-border" style="width:62%; text-align:left;">COBERTURAS</th>
                            @foreach ($opcoes as $op)
                                @php
                                    $pkg = trim($op['package'] ?? '');
                                    $nome = trim($op['name'] ?? '');
                                @endphp
                                <th class="th-border option-head {{ $pkg === $selectedPkg ? 'option-selected' : '' }}"
                                    style="text-align:center;">
                                    {{ $nome }} (KZ)
                                </th>
                            @endforeach
                        </tr>

                        @foreach ($coberturasOrdem as $nomeCobertura)
                            <tr>
                                <td class="td-border cob-item">{{ $nomeCobertura }}</td>
                                @foreach ($opcoes as $op)
                                    @php
                                        $pkg = trim($op['package'] ?? '');
                                        $nome = trim($op['name'] ?? '');
                                    @endphp
                                    <td class="td-border text-center {{ $pkg === $selectedPkg ? 'option-selected' : '' }}"
                                        style="padding:0!important; margin:0!important;">
                                        {{ $capitalDe($nomeCobertura, $pkg) }}
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </table>
                </td>
            </tr>
        </table>

        {{-- ======================= FRANQUIAS ======================= --}}
        <table class="text-block">
            <tr>
                <td class="left-text bold">Franquia</td>
                <td colspan="2" class="right-text">
                    <table class="table-border tb-franquias" style="width:100%;">
                        <tr>
                            <th class="th-border" style="text-align:left; width:70%;">Franquias</th>
                            <th class="th-border" style="text-align:center; width:30%;">KZ</th>
                        </tr>
                        <tr>
                            <td class="td-border cob-item">DESPESAS DE TRATAMENTO E REPATRIAMENTO</td>
                            <td class="td-border text-center">7.500</td>
                        </tr>
                        <tr>
                            <td class="td-border cob-item">RESPONSABILIDADE CIVIL DOS ALUNOS</td>
                            <td class="td-border text-center">7.500</td>
                        </tr>
                        <tr>
                            <td class="td-border cob-item">RESPONSABILIDADE CIVIL DO ESTABELECIMENTO</td>
                            <td class="td-border text-center">10.000</td>
                        </tr>
                        <tr>
                            <td class="td-border cob-item">RC CRUZADA DOS ALUNOS ENTRE SI</td>
                            <td class="td-border text-center">7.500</td>
                        </tr>
                        <tr>
                            <td class="td-border cob-item">RC CRUZADA DOS ALUNOS E DO ESTABELECIMENTO ENTRE SI</td>
                            <td class="td-border text-center">10.000</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        {{-- ======================= TAXA E PRÉMIO TOTAL ======================= --}}
        @php
            $discountPerc = $discountPerc ?? 0;
            $objCount = collect($objects ?? [])->sum(function ($o) {
                return (int) ($o['student_count'] ?? 0);
            });
            if ($objCount <= 0) {
                $objCount = 750;
            }

            // ============================================================
            // PRÉMIOS DINÂMICOS - USA OS ratesObject DOS GRUPOS
            // ============================================================

            $premiosPorPackage = [];

            foreach ($QLI ?? [] as $qli) {
                // Só processa QLIs ativos
                if (($qli['status'] ?? false) !== true) {
                    continue;
                }

                $coverageName = strtoupper($qli['coverageObject']['name'] ?? '');
                $groupId = $qli['coverageObject']['group_id'] ?? null;

                // Só processa QLIs que são GRUPOS (group_id = null)
                if ($groupId !== null) {
                    continue;
                }

                // Verifica se é obrigatória ou facultativa
                $isMandatory = false;
                $isFacultative = false;

                if (strpos($coverageName, 'OBRIGATÓRIA') !== false || strpos($coverageName, 'OBRIGATORIA') !== false) {
                    $isMandatory = true;
                } elseif (
                    strpos($coverageName, 'FACULTATIVA') !== false ||
                    strpos($coverageName, 'FACULTATIVAS') !== false
                ) {
                    $isFacultative = true;
                }

                if (!$isMandatory && !$isFacultative) {
                    continue;
                }

                foreach ($qli['ratesObject'] ?? [] as $rate) {
                    $package = trim($rate['package'] ?? '');
                    $premium = (float) ($rate['premium_value'] ?? 0);

                    if (empty($package)) {
                        continue;
                    }

                    if (!isset($premiosPorPackage[$package])) {
                        $premiosPorPackage[$package] = [
                            'obrigatorias' => 0,
                            'facultativas' => 0,
                            'total' => 0,
                        ];
                    }

                    if ($isMandatory) {
                        $premiosPorPackage[$package]['obrigatorias'] += $premium;
                    } elseif ($isFacultative) {
                        $premiosPorPackage[$package]['facultativas'] += $premium;
                    }
                    $premiosPorPackage[$package]['total'] += $premium;
                }
            }

            // ============================================================
            // APLICA O DESCONTO NOS PRÊMIOS
            // ============================================================
            $discountFactor = 1 - $discountPerc / 100;

            $premiosPorAluno = [];
            $premiosTotal = [];

            foreach ($opcoes as $op) {
                $package = trim($op['package'] ?? '');
                $nome = trim($op['name'] ?? '');

                if (isset($premiosPorPackage[$package])) {
                    // Aplica o desconto nos valores
                    $obrig = $premiosPorPackage[$package]['obrigatorias'] * $discountFactor;
                    $fac = $premiosPorPackage[$package]['facultativas'] * $discountFactor;
                    $total = $obrig + $fac;

                    $premiosPorAluno[$nome] = [
                        'obrigatorias' => number_format($obrig, 0, ',', '.'),
                        'facultativas' => number_format($fac, 0, ',', '.'),
                        'total' => number_format($total, 0, ',', '.'),
                    ];

                    $premiosTotal[$nome] = [
                        'obrigatorias' => number_format($obrig * $objCount, 0, ',', '.'),
                        'facultativas' => number_format($fac * $objCount, 0, ',', '.'),
                        'total' => number_format($total * $objCount, 0, ',', '.'),
                    ];
                } else {
                    $premiosPorAluno[$nome] = ['obrigatorias' => '0', 'facultativas' => '0', 'total' => '0'];
                    $premiosTotal[$nome] = ['obrigatorias' => '0', 'facultativas' => '0', 'total' => '0'];
                }
            }
        @endphp

        <table class="text-block">
            <tr>
                <td class="left-text bold">Taxa e Prémio Total</td>
                <td colspan="2" class="right-text">
                 {{--    @if ($discountPerc > 0)
                        <p style=" font-size: 12px; margin-bottom: 5px;">
                            <strong>Desconto aplicado: {{ number_format($discountPerc, 0, ',', '.') }}%</strong>
                        </p>
                    @endif --}}
                    <p>Para cobertura dos riscos acima indicados, de acordo com a opção e o nível de coberturas
                        escolhido, serão aplicados os seguintes prémios totais, por aluno.</p>
                    <br>
                    <table style="width:100%;  border-collapse: collapse;">
                        <tr>
                            <td style="text-align: right; ">
                                Moeda: KZ
                            </td>
                        </tr>
                    </table>
                    <table class="table-border tb-premios" style="width:100%;">
                        <tr>
                            <th class="th-border" style="text-align:left; width:30%;" style="background: #eceaea   "></th>
                            @foreach ($opcoes as $op)
                                @php
                                    $pkg = trim($op['package'] ?? '');
                                    $nome = trim($op['name'] ?? '');
                                @endphp
                                <th class="th-border option-head {{ $pkg === $selectedPkg ? 'option-selected' : '' }}" style="background: #eceaea   ">
                                    {{ $nome }}
                                </th>
                            @endforeach
                        </tr>
                        <tr>
                            <td class="td-border">Coberturas Obrigatórias</td>
                            @foreach ($opcoes as $op)
                                @php
                                    $pkg = trim($op['package'] ?? '');
                                    $nome = trim($op['name'] ?? '');
                                @endphp
                                <td
                                    class="td-border text-center {{ $pkg === $selectedPkg ? 'option-selected' : '' }}">
                                    {{ $premiosPorAluno[$nome]['obrigatorias'] ?? '0' }}
                                </td>
                            @endforeach
                        </tr>
                        <tr>
                            <td class="td-border">Coberturas Facultativas</td>
                            @foreach ($opcoes as $op)
                                @php
                                    $pkg = trim($op['package'] ?? '');
                                    $nome = trim($op['name'] ?? '');
                                @endphp
                                <td
                                    class="td-border text-center {{ $pkg === $selectedPkg ? 'option-selected' : '' }}">
                                    {{ $premiosPorAluno[$nome]['facultativas'] ?? '0' }}
                                </td>
                            @endforeach
                        </tr>
                        <tr class="row-total">
                            <td class="td-border"><strong>Total</strong></td>
                            @foreach ($opcoes as $op)
                                @php
                                    $pkg = trim($op['package'] ?? '');
                                    $nome = trim($op['name'] ?? '');
                                @endphp
                                <td
                                    class="td-border text-center {{ $pkg === $selectedPkg ? 'option-selected' : '' }}">
                                    <strong>{{ $premiosPorAluno[$nome]['total'] ?? '0' }}</strong>
                                </td>
                            @endforeach
                        </tr>
                    </table>

                    <br>
                    <p>Para o conjunto de alunos actualmente existentes ({{ number_format($objCount, 0, ',', '.') }}) o
                        prémio total a cobrar será o seguinte:</p>
                    <br>
                    <table style="width:100%;  border-collapse: collapse;">
                        <tr>
                            <td style="text-align: right; ">
                                Moeda: KZ
                            </td>
                        </tr>
                    </table>
                    <table class="table-border tb-premios" style="width:100%;">
                        <tr>
                            <th class="th-border" style="text-align:left; width:30%;" style="background: #eceaea   "></th>
                            @foreach ($opcoes as $op)
                                @php
                                    $pkg = trim($op['package'] ?? '');
                                    $nome = trim($op['name'] ?? '');
                                @endphp
                                <th
                                    class="th-border option-head {{ $pkg === $selectedPkg ? 'option-selected' : '' }}" style="background: #eceaea   ">
                                    {{ $nome }}
                                </th>
                            @endforeach
                        </tr>
                        <tr>
                            <td class="td-border">Coberturas Obrigatórias</td>
                            @foreach ($opcoes as $op)
                                @php
                                    $pkg = trim($op['package'] ?? '');
                                    $nome = trim($op['name'] ?? '');
                                @endphp
                                <td
                                    class="td-border text-center {{ $pkg === $selectedPkg ? 'option-selected' : '' }}">
                                    {{ $premiosTotal[$nome]['obrigatorias'] ?? '0' }}
                                </td>
                            @endforeach
                        </tr>
                        <tr>
                            <td class="td-border">Coberturas Facultativas</td>
                            @foreach ($opcoes as $op)
                                @php
                                    $pkg = trim($op['package'] ?? '');
                                    $nome = trim($op['name'] ?? '');
                                @endphp
                                <td
                                    class="td-border text-center {{ $pkg === $selectedPkg ? 'option-selected' : '' }}">
                                    {{ $premiosTotal[$nome]['facultativas'] ?? '0' }}
                                </td>
                            @endforeach
                        </tr>
                        <tr class="row-total">
                            <td class="td-border"><strong>Total</strong></td>
                            @foreach ($opcoes as $op)
                                @php
                                    $pkg = trim($op['package'] ?? '');
                                    $nome = trim($op['name'] ?? '');
                                @endphp
                                <td
                                    class="td-border text-center {{ $pkg === $selectedPkg ? 'option-selected' : '' }}">
                                    <strong>{{ $premiosTotal[$nome]['total'] ?? '0' }}</strong>
                                </td>
                            @endforeach
                        </tr>
                    </table>
                    {{--             
            @if ($discountPerc > 0)
                <p style="font-size: 10px; color: #666; margin-top: 5px; font-style: italic;">
                    * Desconto de {{ number_format($discountPerc, 0, ',', '.') }}% aplicado sobre os prémios base.
                </p>
            @endif --}}
                </td>
            </tr>
        </table>

        {{-- ======================= MODALIDADE DE PAGAMENTO ======================= --}}
        <table class="text-block">
            <tr>
                <td class="left-text bold">Modalidade de Pagamento</td>
                <td class="right-text">
                    <p>Anual. Caso se opte por fraccionar o prémio, serão imputados os respectivos encargos de
                        fraccionamento.</p>
                </td>
            </tr>
        </table>

        {{-- ======================= ENCARGOS DE FRACCIONAMENTO ======================= --}}
        <table class="text-block">
            <tr>
                <td class="left-text bold">Encargos de Fraccionamento</td>
                <td class="right-text">
                    <table style="width:100%;">
                        <tr>
                            <td style="text-align:left">Mensal</td>
                            <td style="text-align:right">N/A</td>
                        </tr>
                    </table>
                    <hr style="margin-top:0;margin-bottom:0;">
                    <table style="width:100%;">
                        <tr>
                            <td style="text-align:left">Trimestral</td>
                            <td style="text-align:right">5%</td>
                        </tr>
                    </table>
                    <hr style="margin-top:0;margin-bottom:0;">
                    <table style="width:100%;">
                        <tr>
                            <td style="text-align:left">Semestral</td>
                            <td style="text-align:right">3%</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        {{-- ======================= OUTRAS CONDIÇÕES ======================= --}}
        @php
            $validade = (int) ($selectedBranch['default_validity_days'] ?? 30);
        @endphp
        <table class="text-block">
            <tr>
                <td class="left-text bold">Outras Condições Aplicáveis</td>
                <td class="right-text">
                    <p>Estas condições são válidas por{{ $validade }} dias.</p>
                </td>
            </tr>
        </table>

        <br><br>

        {{-- ======================= INFORMAÇÃO IMPORTANTE ======================= --}}
        <div class="important-info">
            <h5 style="margin-bottom:0;margin-top:10px; font-weight:bold;">INFORMAÇÃO IMPORTANTE:</h5>
            <p class="info-importante" style="margin-top:0">
                Os valores da presente cotação assentam num conjunto de pressupostos, como sejam os dados que nos
                indicou e a inexistência de factores de agravamento de risco, pressupostos estes sujeitos a confirmação
                por parte da Seguradora.
            </p>
            <p class="info-importante">
                A Seguradora pode solicitar informação adicional e/ou confirmar os dados indicados, podendo daqui
                resultar a não verificação, no caso concreto, dos pressupostos sobre os quais assentou a presente
                cotação e, consequentemente, a não-aceitação do seguro nas condições referidas na mesma. Deste modo,
                importa notar que o prémio desta cotação e a verificação dos respectivos pressupostos carecem de
                confirmação e aceitação por parte da Seguradora.
            </p>
            <p class="info-importante">
                Para os dados indicados, apurou-se o prémio acima que inclui cargas fiscais e parafiscais em vigor à
                data da cotação. Qualquer alteração legal posterior às cargas fiscais ou parafiscais aplicáveis, terá de
                ser considerada pela Seguradora aquando da emissão do contrato. Esta cotação tem a validade acima
                indicada, sem prejuízo da possibilidade, aquando da emissão do contrato, de alteração do prémio apurado
                caso ocorra uma alteração legal posterior à data da cotação que determine a modificação das cargas
                fiscais ou parafiscais aplicáveis.
            </p>
            <p class="info-importante">
                A informação constante deste documento não dispensa a consulta da informação pré-contratual e contratual
                legalmente exigida.
            </p>

            <h5 style="color:rgb(22,22,22); margin-top:15px;"><strong>BIC Seguros, S. A.</strong></h5>
        </div>

        {{-- ======================= RODAPÉ LEGAL ======================= --}}

    </div>
</body>

</html>
