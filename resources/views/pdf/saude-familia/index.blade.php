<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
</head>
<body>
    @php
        function sf_checked_ref($current, $value) {
            return ($current == $value) ? 'check-prenchido.png' : 'check-proposta.png';
        }

        function sf_date_ref($value) {
            if (!$value) return '';
            try {
                return \Carbon\Carbon::parse($value)->format('d/m/Y');
            } catch (\Throwable $e) {
                return (string) $value;
            }
        }

        function sf_digits($value, $length = null) {
            $text = preg_replace('/\s+/', ' ', trim((string) $value));
            if ($length) {
                return mb_substr($text, 0, $length);
            }
            return $text;
        }

        $pessoasSeguras = $proposal->pessoas_seguras ?? [];
        if (is_string($pessoasSeguras)) {
            $decoded = json_decode($pessoasSeguras, true);
            $pessoasSeguras = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
        }
        if (!is_array($pessoasSeguras)) {
            $pessoasSeguras = [];
        }

        $slots = [
            ['label' => 'Titular (Não necessita preencher se respondeu “Sim” em Tomador do Seguro)', 'item' => $pessoasSeguras[0] ?? []],
            ['label' => 'Cônjuge', 'item' => $pessoasSeguras[1] ?? []],
            ['label' => 'Filho (a)', 'item' => $pessoasSeguras[2] ?? []],
            ['label' => 'Filho (a)', 'item' => $pessoasSeguras[3] ?? []],
            ['label' => 'Filho (a)', 'item' => $pessoasSeguras[4] ?? []],
        ];

        $iban = $proposal->aut_iban ?? '';
        $ibanClean = preg_replace('/[^A-Z0-9]/i', '', $iban);
        if (strpos(strtoupper($ibanClean), 'AO06') === 0) {
            $ibanClean = substr($ibanClean, 4);
        }
        $ibanDigits = str_split(str_pad($ibanClean, 21, ' '));
    @endphp

    <div class="page">
        

        <table class="header-grid">
            <tr>
                <td width="27%" style="text-align:center" class="date">
                    <div class="small-box"  >
                        <div style="font-weight:700; font-size:8.5pt; text-align:center;">Data de Entrada:</div>
                        <div style="text-align:center; margin-top:8px;">
                            <span class="inline-field" style="min-width:110px;">{{ sf_date_ref($proposal->data_entrada ?? null) }}</span>
                        </div>
                        <div style="margin-top:14px; font-weight:700;">Ag. Produtor:
                            <span class="inline-field" style="min-width:140px;">{{ $proposal->agente_produtor ?? '' }}</span>
                        </div>
                    </div>
                </td>
                <td width="48%" style="padding: 6px 18px 0;">
                    <div class="proposal-heading">
                        PROPOSTA DE SEGURO<br>
                        BIC SEGUROS FAMÍLIA SEGURA
                    </div>
                    <div class="sub-options">
                        <table style="width:100%; border-collapse:collapse;">
                            <tr>
                                <td style="text-align: left" >SEGURO NOVO</td>
                                <td width="20" ><img src="{{ public_path('img/cotacao/' . sf_checked_ref($proposal->tipo_proposta ?? '', 'novo')) }}" height="14" alt=""></td>
                                <td>INDIVIDUAL</td>
                                <td width="20"><img src="{{ public_path('img/cotacao/' . sf_checked_ref($proposal->tipo_seguro ?? '', 'individual')) }}" height="14" alt=""></td>
                            </tr>
                            <tr>
                                <td style="" style="text-align: left">ALTERAÇÃO</td>
                                <td style=""><img src="{{ public_path('img/cotacao/' . sf_checked_ref($proposal->tipo_proposta ?? '', 'alteracao')) }}" height="14" alt=""></td>
                                <td style="">FAMILIAR</td>
                                <td style=""><img src="{{ public_path('img/cotacao/' . sf_checked_ref($proposal->tipo_seguro ?? '', 'familiar')) }}" height="14" alt=""></td>
                            </tr>
                            <tr>
                                <td colspan="4" style="text-align: left">APÓLICE Nº <span class="inline-field" style="min-width:120px;">{{ $proposal->apolice_num ?? '' }}</span></td>
                            </tr>
                        </table>
                    </div>
                </td>
                <td width="25%" class="client">
                    <div class="small-box" style="color:#ff0000;">
                        <div style="font-weight:700; font-size:8.5pt; text-align:center;">É Cliente do Banco BIC?</div>
                        <div style="text-align:center; margin-top:16px; font-weight:700;">
                            NÃO <img src="{{ public_path('img/cotacao/' . sf_checked_ref($proposal->cliente_bic ?? '', 'nao')) }}" height="14" alt="">
                            &nbsp;
                            SIM <img src="{{ public_path('img/cotacao/' . sf_checked_ref($proposal->cliente_bic ?? '', 'sim')) }}" height="14" alt="">
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        <div class="line-row">
            INÍCIO CONTRATO <span class="inline-field" style="min-width:180px;">{{ sf_date_ref($proposal->inicio_contrato ?? null) }}</span>
            &nbsp;&nbsp; ÀS <span class="inline-field" style="min-width:52px;">{{ $proposal->hora_inicio ? \Carbon\Carbon::parse($proposal->hora_inicio)->format('H') : '' }}</span> H
            <span class="inline-field" style="min-width:52px;">{{ $proposal->hora_inicio ? \Carbon\Carbon::parse($proposal->hora_inicio)->format('i') : '' }}</span> M
            &nbsp;&nbsp; FIM CONTRATO <span class="inline-field" style="min-width:180px;">{{ sf_date_ref($proposal->fim_contrato ?? null) }}</span>
        </div>

        <div class="line-row">
            DURAÇÃO DO CONTRATO:
            <img src="{{ public_path('img/cotacao/' . sf_checked_ref($proposal->duracao_contrato ?? '', 'anual')) }}" height="14" alt="">
            ANO E SEGUINTES
        </div>

        <div class="line-row">
            FORMA DE PAGAMENTO:
            <img src="{{ public_path('img/cotacao/' . sf_checked_ref($proposal->forma_pagamento ?? '', 'anual')) }}" height="14" alt=""> ANUAL
            &nbsp;&nbsp;
            <img src="{{ public_path('img/cotacao/' . sf_checked_ref($proposal->forma_pagamento ?? '', 'semestral')) }}" height="14" alt=""> SEMESTRAL
            &nbsp;&nbsp;
            <img src="{{ public_path('img/cotacao/' . sf_checked_ref($proposal->forma_pagamento ?? '', 'trimestral')) }}" height="14" alt=""> TRIMESTRAL
            &nbsp;&nbsp;
            <img src="{{ public_path('img/cotacao/' . sf_checked_ref($proposal->forma_pagamento ?? '', 'mensal')) }}" height="14" alt=""> MENSAL
        </div>

        <div class="section-bar">TOMADOR DE SEGURO</div>

        <div class="line-row">NOME: <span class="fill-line" style="width: 900px;">{{ $proposal->tomador_nome ?? '' }}</span></div>
        <div class="line-row">MORADA: <span class="fill-line" style="width: 875px;">{{ $proposal->tomador_morada ?? '' }}</span></div>
        <div class="line-row">
            MUNICÍPIO: <span class="fill-line" style="width: 330px;">{{ $proposal->tomador_municipio ?? '' }}</span>
            &nbsp;&nbsp;
            LOCALIDADE: <span class="fill-line" style="width: 355px;">{{ $proposal->tomador_localidade ?? '' }}</span>
        </div>
        <div class="line-row">
            TELEFONE: <span class="fill-line" style="width: 220px;">{{ $proposal->tomador_telefone ?? '' }}</span>
            &nbsp;&nbsp;
            TELEMÓVEL: <span class="fill-line" style="width: 210px;">{{ $proposal->tomador_telemovel ?? '' }}</span>
            &nbsp;&nbsp;
            EMAIL: <span class="fill-line" style="width: 285px;">{{ $proposal->tomador_email ?? '' }}</span>
        </div>
        <div class="line-row">
            N I F: <span class="fill-line" style="width: 420px;">{{ $proposal->tomador_nif ?? '' }}</span>
            &nbsp;&nbsp;
            B.I. <span class="fill-line" style="width: 365px;">{{ $proposal->tomador_bi ?? '' }}</span>
        </div>
        <div class="line-row">
            PROFISSÃO: <span class="fill-line" style="width: 330px;">{{ $proposal->tomador_profissao ?? '' }}</span>
            &nbsp;&nbsp;
            DATA DE NASCIMENTO: <span class="fill-line" style="width: 170px;">{{ sf_date_ref($proposal->tomador_data_nascimento ?? null) }}</span>
            &nbsp;&nbsp;
            SEXO:
            <img src="{{ public_path('img/cotacao/' . sf_checked_ref($proposal->tomador_sexo ?? '', 'F')) }}" height="14" alt=""> F
            &nbsp;
            <img src="{{ public_path('img/cotacao/' . sf_checked_ref($proposal->tomador_sexo ?? '', 'M')) }}" height="14" alt=""> M
        </div>
        <div class="line-row">
            NACIONALIDADE: <span class="fill-line" style="width: 345px;">{{ $proposal->tomador_nacionalidade ?? '' }}</span>
            &nbsp;&nbsp;
            ESTADO CIVIL: <span class="fill-line" style="width: 430px;">{{ $proposal->tomador_estado_civil ?? '' }}</span>
        </div>

        <div class="section-bar">LOCAL DE COBRANÇA <span class="light paren-text">(Se diferente do Tomador de Seguro)</span></div>
        <div class="line-row">MORADA: <span class="fill-line" style="width: 875px;">{{ $proposal->local_cobranca_morada ?? '' }}</span></div>
        <div class="line-row">
            MUNICÍPIO: <span class="fill-line" style="width: 345px;">{{ $proposal->local_cobranca_municipio ?? '' }}</span>
            LOCALIDADE: <span class="fill-line" style="width: 555px;">{{ $proposal->local_cobranca_localidade ?? '' }}</span>
        </div>

        <div class="section-bar">PESSOAS SEGURAS <span class="light paren-text">(Se houver mais pessoas seguras, por favor utilize outra proposta com estes dados)</span></div>

        <div class="people-box">
            <div class="people-row">
                O Tomador do Seguro é Pessoa Segura? SIM
                <img src="{{ public_path('img/cotacao/' . sf_checked_ref($proposal->tomador_eh_pessoa_segura ?? '', 'sim')) }}" height="14" alt="">
                &nbsp; NÃO
                <img src="{{ public_path('img/cotacao/' . sf_checked_ref($proposal->tomador_eh_pessoa_segura ?? '', 'nao')) }}" height="14" alt="">
            </div>
            @foreach($slots as $slot)
                @php
                    $p = $slot['item'];
                    $name = $p['nome'] ?? '';
                    $birth = sf_date_ref($p['dataNascimento'] ?? ($p['data_nascimento'] ?? null));
                    $sex = $p['sexo'] ?? '';
                    $bi = $p['bi'] ?? '';
                    $nif = $p['nif'] ?? '';
                @endphp
                <div class="people-row">
                    <div class="line-row">NOME DA PESSOA SEGURA:
                        <span class="fill-line" style="width: 810px;">{{ $name }}</span>
                    </div>
                    <div class="people-label">{{ $slot['label'] }}</div>
                    <div class="line-row">
                        DATA DE NASCIMENTO:
                        <span class="fill-line" style="width: 150px;">{{ $birth }}</span>
                        &nbsp;&nbsp; SEXO:
                        <img src="{{ public_path('img/cotacao/' . sf_checked_ref($sex, 'F')) }}" height="14" alt=""> F
                        &nbsp;
                        <img src="{{ public_path('img/cotacao/' . sf_checked_ref($sex, 'M')) }}" height="14" alt=""> M
                        &nbsp;&nbsp; BI nº <span class="fill-line" style="width: 120px;">{{ $bi }}</span>
                        &nbsp;&nbsp; N I F: <span class="fill-line" style="width: 210px;">{{ $nif }}</span>
                    </div>
                </div>
            @endforeach
        </div>

        <div style="margin-top:6px; font-size:8pt;">
            Caso os campos acima sejam insuficientes, pf anexe uma listagem de todas as Pessoas Seguras contendo os elementos solicitados.
        </div>
    </div>

    <div class="page">
        

        <div class="section-bar">PLANOS DE BENEFÍCIOS <span class="light paren-text">(Indique a opção escolhida)</span></div>

        <table class="table-ref" style="font-size:8pt;">
            <tr>
                <th class="left" style="width:33%;">PLANOS de SAÚDE FAMÍLIA SEGURA</th>
                <th colspan="3" class="center">Limite de Responsabilidade por pessoa/anuidade- Kwanzas</th>
            </tr>
            <tr>
                <th class="left">COBERTURAS</th>
                <th class="center" style="color:#ff0000;">BIC Família Plus <img src="{{ public_path('img/cotacao/' . sf_checked_ref($proposal->plano_saude ?? '', 'bic_familia_plus')) }}" height="14" alt=""></th>
                <th class="center" style="color:#ff0000;">BIC Família Top <img src="{{ public_path('img/cotacao/' . sf_checked_ref($proposal->plano_saude ?? '', 'bic_familia_top')) }}" height="14" alt=""></th>
                <th class="center" style="color:#ff0000;">BIC Família Mega <img src="{{ public_path('img/cotacao/' . sf_checked_ref($proposal->plano_saude ?? '', 'bic_familia_mega')) }}" height="14" alt=""></th>
            </tr>
            <tr>
                <td class="left"><strong>ÁREA DE COBERTURA</strong></td>
                <td class="center"><strong>ANGOLA<br>ÁFRICA DO SUL<br>NAMÍBIA<br>PORTUGAL</strong></td>
                <td class="center"><strong>MUNDIAL</strong> (EXCEPTO EUA e MÉDIO ORIENTE)</td>
                <td class="center"><strong>MUNDIAL</strong> (EXCEPTO EUA e MÉDIO ORIENTE)</td>
            </tr>
            <tr><td class="left"><strong>LIMITE DE COBERTURA DO PLANO</strong></td><td class="center"><strong>20.000.000</strong></td><td class="center"><strong>50.000.000</strong></td><td class="center"><strong>100.000.000</strong></td></tr>
            <tr><td class="left"><strong>INTERNAMENTO HOSPITALAR</strong></td><td class="center"><strong>20.000.000</strong></td><td class="center"><strong>50.000.000</strong></td><td class="center"><strong>100.000.000</strong></td></tr>
            <tr>
                <td class="left"><strong>ASSISTÊNCIA AMBULATÓRIA</strong><div style="padding-left:42px; margin-top:6px;">CONSULTAS<br>EXAMES E TRATAMENTOS<br>FISIOTERAPIA</div></td>
                <td class="center" style="vertical-align:top; padding-top:20px;"><strong>250.000</strong></td>
                <td class="center" style="vertical-align:top; padding-top:20px;"><strong>750.000</strong><div style="margin-top:48px;">10 Sessões</div></td>
                <td class="center" style="vertical-align:top;
                 padding-top:20px;"><strong>2.500.000</strong><div style="margin-top:48px;">20 Sessões</div></td>
            </tr>
            <tr><td class="left"><strong>MEDICAMENTOS</strong> <sup>(1)</sup></td><td class="gray-cell"></td><td class="center">150.000</td><td class="center">250.000</td></tr>
            <tr><td class="left"><strong>ESTOMATOLOGIA (Consultas e Tratamentos)</strong></td><td class="gray-cell"></td><td class="center">150.000</td><td class="center">300.000</td></tr>
            <tr><td class="left"><strong>ÓCULOS E OUTRAS PRÓTESES</strong></td><td class="gray-cell"></td><td class="center">75.000</td><td class="center">100.000</td></tr>
            <tr>
                <td class="left"><strong>PARTO</strong><div style="padding-left:42px; margin-top:6px;">Parto normal<br>Cesariana justificada</div></td>
                <td class="gray-cell"></td>
                <td class="center"><div style="margin-top:20px;">1.000.000<br>1.250.000</div></td>
                <td class="center"><div style="margin-top:20px;">1.500.000<br>2.000.000</div></td>
            </tr>
            <tr><td class="left"><strong>SERVIÇO DE EVACUAÇÃO</strong></td><td class="center">10.000.000</td><td class="center">20.000.000</td><td class="center">20.000.000</td></tr>
        </table>

        <div style="margin-top:6px;">
            <strong>Observações:</strong><br>
            (1) &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Inclui Vacinas
        </div>

        <div class="section-bar" style="margin-top:4px;">CO-PAGAMENTOS</div>

        <table class="table-ref" style="font-size:8pt;">
            <tr>
                <th class="left" rowspan="2" style="width:40%;">PLANOS de SAÚDE BIC Seguros<br>(COBERTURAS)</th>
                <th colspan="2" class="center" style="font-size:10pt;">Co-pagamentos pela Pessoa Segura</th>
            </tr>
            <tr>
                <th class="center" style="color:#ff0000;">Dentro da Rede</th>
                <th class="center" style="color:#ff0000;">Fora da Rede</th>
            </tr>
            <tr><td class="left"><strong>INTERNAMENTO HOSPITALAR</strong></td><td class="center">0%</td><td class="center">10%</td></tr>
            <tr>
                <td class="left"><strong>TRATAMENTOS, CONSULTAS E EXAMES</strong><div style="padding-left:90px; margin-top:22px;">CONSULTAS<br><br>EXAMES E TRATAMENTOS<br><br>FISIOTERAPIA</div></td>
                <td class="center" style="vertical-align:middle;">10%<div style="margin-top:52px;">10%</div><div style="margin-top:30px;">10%</div></td>
                <td class="center" style="vertical-align:middle;">30%<div style="margin-top:52px;">30%</div><div style="margin-top:30px;">30%</div></td>
            </tr>
            <tr><td class="left"><strong>MEDICAMENTOS</strong></td><td class="center">10%</td><td class="center">10%</td></tr>
            <tr><td class="left"><strong>ESTOMATOLOGIA (Consulta e Tratamentos)</strong></td><td class="center">15%</td><td class="center">30%</td></tr>
            <tr><td class="left"><strong>ÓCULOS E OUTRAS PRÓTESES</strong></td><td class="center">15%</td><td class="center">30%</td></tr>
            <tr><td class="left"><strong>PARTO</strong></td><td class="center">10%</td><td class="center">25%</td></tr>
            <tr><td class="left"><strong>SERVIÇO DE EVACUAÇÃO</strong></td><td class="center">0%</td><td class="center">0%</td></tr>
        </table>
    </div>

    <div class="page">
        

        <div class="section-bar">PERÍODOS DE CARÊNCIA</div>
        <table class="table-ref" style="font-size:8pt;">
            <tr>
                <th class="left" style="width:87%;">COBERTURAS</th>
                <th class="center" style="width:13%;">Período de Carência</th>
            </tr>
            <tr><td class="left">INTERNAMENTO HOSPITALAR</td><td class="center">60 dias</td></tr>
            <tr><td class="left">TRATAMENTOS, CONSULTAS E EXAMES, MEDICAMENTOS, ÓCULOS E OUTRAS PRÓTESES</td><td class="center">60 dias</td></tr>
            <tr><td class="left">Estomatologia</td><td class="center">120 dias</td></tr>
            <tr><td class="left">Tratamento cirúrgico ou por outros métodos invasivos da hipertrofia benigna da próstata, tratamento cirúrgico ou por outros métodos invasivos de patologia benigna do útero, tratamento cirúrgico de cistocelo e rectocelo</td><td class="center">180 dias</td></tr>
            <tr><td class="left">PARTO, tratamento cirúrgico de varizes dos membros inferiores, tratamento cirúrgico de hérnia discal, hemorroidectomia e outros tratamentos da doença hemorroidária bem como tratamento cirúrgico da fistula perianal, tratamento de patologia articular por via artroscópica, septoplastia, amigdalectomia, adenoidectomia, miringotomias com ou sem aplicação de tubos de ventilação, rinoseptoplastia, excisão cirúrgica de lesões benignas da pele e tecido celular subcutâneo, tratamentos com laser a lesões benignas da pele, tratamento cirúrgico da apneia do sono, tratamentos psiquiátricos.</td><td class="center">365 dias</td></tr>
            <tr><td class="left">HIV/SIDA</td><td class="center">60 meses</td></tr>
        </table>

        <div class="section-bar" style="margin-top:22px;">OBSERVAÇÕES</div>
        <div class="obs-lines">
            @for($i = 0; $i < 9; $i++)
                <div class="obs-line">@if($i === 0){{ $proposal->observacoes ?? '' }}@endif</div>
            @endfor
        </div>

        <div class="section-bar" style="margin-top:18px;">SEGUROS EXISTENTES NA BIC SEGUROS</div>
        <div class="line-row">
            É OU FOI CLIENTE DA BIC SEGUROS?
            NÃO <img src="{{ public_path('img/cotacao/' . sf_checked_ref($proposal->bic_cliente ?? '', 'nao')) }}" height="14" alt="">
            &nbsp; SIM <img src="{{ public_path('img/cotacao/' . sf_checked_ref($proposal->bic_cliente ?? '', 'sim')) }}" height="14" alt="">
            EM QUE PRODUTO (S)? <span class="fill-line" style="width: 460px;">{{ $proposal->bic_produtos ?? '' }}</span>
        </div>

        <div class="section-bar" style="margin-top:18px;">CONTRATOS ANTERIORES EM OUTRAS SEGURADORAS</div>
        <div class="line-row">
            O risco que pretende cobrir está ou esteve seguro, no seu todo ou em parte, noutra seguradora?
            NÃO <img src="{{ public_path('img/cotacao/' . sf_checked_ref($proposal->anterior_segurado ?? '', 'nao')) }}" height="14" alt="">
            &nbsp; SIM <img src="{{ public_path('img/cotacao/' . sf_checked_ref($proposal->anterior_segurado ?? '', 'sim')) }}" height="14" alt="">
        </div>
        <div class="line-row">
            Se sim, indique qual a seguradora?
            <span class="fill-line" style="width: 260px;">{{ $proposal->anterior_seguradora ?? '' }}</span>
            &nbsp;&nbsp; N°. Apólice?
            <span class="fill-line" style="width: 375px;">{{ $proposal->anterior_apolice_num ?? '' }}</span>
        </div>

        <div class="section-bar" style="margin-top:18px;">PRÉMIOS EM DÍVIDA</div>
        <div class="line-row">
            Relativamente aos mesmos contratos de seguros, existem quaisquer débitos por liquidar à anterior seguradora?
        </div>
        <div class="line-row">
            NÃO <img src="{{ public_path('img/cotacao/' . sf_checked_ref($proposal->premios_divida ?? '', 'nao')) }}" height="14" alt="">
            &nbsp; SIM <img src="{{ public_path('img/cotacao/' . sf_checked_ref($proposal->premios_divida ?? '', 'sim')) }}" height="14" alt="">
            &nbsp;&nbsp; Se sim, especifique
            <span class="fill-line" style="width: 700px;">{{ $proposal->premios_divida_detalhes ?? '' }}</span>
        </div>
    </div>

    <div class="page">
        

        <div class="section-bar">DECLARAÇÃO</div>
        <div style="font-size:8.6pt; line-height:1.65;">
            Para efeitos da celebração do presente contrato de seguro, declaro que:
            <br>a) &nbsp;&nbsp;&nbsp; Respondi completamente e com veracidade a todas as perguntas e verifiquei o preenchimento desta proposta.
            <br>b) &nbsp;&nbsp;&nbsp; São exactas e completas as declarações por mim prestadas e tomei conhecimento de todas as informações necessárias à celebração do presente contrato, tendo sido colocadas à minha disposição as respectivas Condições Gerais e Especiais, para delas tomar integral conhecimento, e prestados todos os esclarecimentos sobre as mesmas, nomeadamente sobre garantias e exclusões, com as quais estou de acordo.
            <br>c) &nbsp;&nbsp;&nbsp; Autorizo a consulta dos dados pessoais disponibilizados, sob regime de absoluta confidencialidade, pelas empresas que integram o grupo desde que compatível com a finalidade da recolha dos mesmos.
            <br>d) &nbsp;&nbsp;&nbsp; Autorizo a BIC Seguros, S.A a proceder à recolha de dados pessoais complementares junto de organismos públicos, empresas especializadas e outras unidades económicas com vista à confirmação ou complemento dos elementos recolhidos e necessários à gestão da relação contratual.
        </div>

        <div style="margin-top:26px;">
            Feito, em <span class="fill-line" style="width: 110px;">{{ $proposal->local_assinatura ?? '' }}</span>
            a <span class="fill-line" style="width: 40px;"></span> de
            <span class="fill-line" style="width: 180px;"></span> de
            <span class="fill-line" style="width: 60px;"></span>
        </div>

        <div style="margin-top:40px; text-align:right; padding-right:40px;">
            <div class="fill-line" style="width: 290px;"></div><br>
            (Assinatura do Tomador de Seguro)
        </div>

        <div class="debit-panel">
            <div style="text-align:right; font-size:11pt; font-weight:700;">Autorização de Débito em Conta</div>
            <table style="width:100%; border-collapse:collapse; margin-top:6px; font-size:8.4pt;">
                <tr>
                    <td width="7%">Banco</td>
                    <td width="38%" class="muted-fill" style="padding:6px 10px;">{{ $proposal->aut_banco ?? 'Banco BIC' }}</td>
                    <td width="9%" style="text-align:right;">Agência</td>
                    <td width="16%" class="muted-fill"></td>
                    <td width="9%" style="text-align:right;">Data</td>
                    <td width="21%">
                        <table style="border-collapse:collapse; width:100%;">
                            <tr>
                                <td style="border:1px solid #111; width:33%; height:20px;"></td>
                                <td style="border:1px solid #111; width:33%;"></td>
                                <td style="border:1px solid #111; width:34%;" class="muted-fill"></td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <table style="width:100%; border-collapse:collapse; margin-top:2px; font-size:8.4pt;">
                <tr>
                    <td width="65%" style="border-right:1px solid #111; vertical-align:top;">
                        <div style="font-weight:700; margin:2px 0 6px 22px;">Mutuário</div>
                        <table style="width:100%; border-collapse:collapse;">
                            <tr>
                                <td width="16%" style="padding:6px 0 6px 22px;">Nome</td>
                                <td width="84%" class="muted-fill">{{ $proposal->aut_mutuario_nome ?? ($proposal->tomador_nome ?? '') }}</td>
                            </tr>
                            <tr>
                                <td style="padding:8px 0 24px 22px; vertical-align:top;">Morada</td>
                                <td class="muted-fill" style="height:54px; vertical-align:top; padding:6px 8px;">{{ $proposal->aut_mutuario_morada ?? ($proposal->tomador_morada ?? '') }}</td>
                            </tr>
                        </table>
                    </td>
                    <td width="35%" style="vertical-align:top; padding-left:22px;">
                        <div style="text-align:center;">Referência</div>
                        <div class="muted-fill" style="height:26px; margin-top:10px;">{{ $proposal->aut_referencia ?? '' }}</div>
                    </td>
                </tr>
            </table>

            <div style="margin-top:8px; font-size:8.6pt; line-height:1.5;">
                Venho pela presente autorizar V. Exas. a pagar os prémios de seguro emitidos pelo BIC Seguros, ao abrigo do contrato de seguro em vigor, por débito directo da minha conta a seguir indicada:
            </div>

            <table style="border-collapse:collapse; margin-top:6px; font-size:8.6pt;">
                <tr>
                    <td style="border:1px solid #111; padding:4px 10px;">IBAN :</td>
                    <td style="border:1px solid #111; width:24px; text-align:center;">A</td>
                    <td style="border:1px solid #111; width:24px; text-align:center;">O</td>
                    <td style="border:1px solid #111; width:24px; text-align:center;">0</td>
                    <td style="border:1px solid #111; width:24px; text-align:center;">6</td>
                    @foreach(range(0, 20) as $i)
                        <td class="muted-fill" style="border:1px solid #111; width:24px; text-align:center;">{{ $ibanDigits[$i] ?? '' }}</td>
                    @endforeach
                </tr>
            </table>

            <div style="margin-top:6px; font-size:8.6pt; line-height:1.5;">
                Para o efeito, comprometo-me a manter a referida conta devidamente provisionada. Autorizo igualmente que nos casos de alteração do prémio de seguro, assim como do pagamento de moras, as referidas prestações sejam alteradas.
            </div>

            <div style="margin-top:14px;">
                Valor máximo de autorização:
                <span class="fill-line" style="width: 260px;">{{ $proposal->aut_valor_maximo ?? '' }}</span>
                Kz
            </div>

            <div style="margin-top:14px;">Periodicidade dos pagamentos:</div>
            <div style="margin-top:12px;">
                <img src="{{ public_path('img/cotacao/' . sf_checked_ref($proposal->aut_periodicidade ?? '', 'anual')) }}" height="14" alt=""> ANUAL
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <img src="{{ public_path('img/cotacao/' . sf_checked_ref($proposal->aut_periodicidade ?? '', 'semestral')) }}" height="14" alt=""> SEMESTRAL
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <img src="{{ public_path('img/cotacao/' . sf_checked_ref($proposal->aut_periodicidade ?? '', 'trimestral')) }}" height="14" alt=""> TRIMESTRAL
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <img src="{{ public_path('img/cotacao/' . sf_checked_ref($proposal->aut_periodicidade ?? '', 'mensal')) }}" height="14" alt=""> MENSAL
            </div>

            <div style="text-align:center; margin-top:44px;">
                <div class="fill-line" style="width: 620px;"></div><br>
                (Assinatura de acordo com a ficha bancária)
            </div>
        </div>
    </div>
</body>
</html>
