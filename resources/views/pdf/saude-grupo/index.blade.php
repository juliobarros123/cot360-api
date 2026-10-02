<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
</head>
<body>
    @php
        function sg_checked_ref($current, $value) {
            return ($current == $value) ? 'check-prenchido.png' : 'check-proposta.png';
        }

        function sg_date_ref($value) {
            if (!$value) {
                return '';
            }

            try {
                return \Carbon\Carbon::parse($value)->format('d/m/Y');
            } catch (\Throwable $e) {
                return (string) $value;
            }
        }

        function sg_hour_ref($value) {
            if (!$value) {
                return '';
            }

            try {
                return \Carbon\Carbon::parse($value)->format('H');
            } catch (\Throwable $e) {
                return '';
            }
        }

        function sg_minute_ref($value) {
            if (!$value) {
                return '';
            }

            try {
                return \Carbon\Carbon::parse($value)->format('i');
            } catch (\Throwable $e) {
                return '';
            }
        }

        function sg_footer($page) {
            return '
               
            ';
        }

        $iban = $proposal->aut_iban ?? $proposal->debito_iban ?? '';
        $ibanClean = preg_replace('/[^A-Z0-9]/i', '', $iban);
        if (strpos(strtoupper($ibanClean), 'AO06') === 0) {
            $ibanClean = substr($ibanClean, 4);
        }
        $ibanDigits = str_split(str_pad($ibanClean, 21, ' '));
    @endphp

    <div class="page" style="position:relative; min-height:1500px; padding-bottom:120px;">
     

        <table class="header-grid" style="margin-bottom:4px;">
            <tr>
                <td width="27%" class="date" style="text-align:center; ">
                    <div class="small-box" style="padding-top:8px; padding-bottom:8px;">
                        <div style="font-weight:700; font-size:8.6pt;">Data de Entrada:</div>
                        <div style="margin-top:6px;">
                            <span class="inline-field" style="min-width:122px;">{{ sg_date_ref($proposal->data_entrada ?? null) }}</span>
                        </div>
                        <div style="margin-top:10px; font-weight:700;">Ag. Produtor:</div>
                        <div style="margin-top:6px;">
                            <span class="inline-field" style="min-width:150px;">{{ $proposal->agente_produtor ?? '' }}</span>
                        </div>
                    </div>
                </td>
                <td width="48%" style="padding:8px 16px 0; ">
                    <div class="proposal-heading" style="font-size:12.8pt; line-height:1.25;">
                        PROPOSTA DE SEGURO<br>
                        SAÚDE GRUPO
                    </div>
                    <div class="sub-options" style="margin-top:18px; font-size:8.2pt;">
                        <table style="width:100%; border-collapse:collapse;">
                            <tr>
                                <td  style="text-align:right;"  width="40%">SEGURO NOVO</td>
                                <td width="10%"><img src="{{ public_path('img/cotacao/' . sg_checked_ref($proposal->tipo_proposta ?? '', 'novo')) }}" height="14" alt=""></td>
                                <td style="text-align:right;" width="40%">ALTERAÇÃO</td>
                                <td width="10%"><img src="{{ public_path('img/cotacao/' . sg_checked_ref($proposal->tipo_proposta ?? '', 'alteracao')) }}" height="14" alt=""></td>
                            </tr>
                             <tr>
                                <td  style="text-align:right;"  width="40%"> APÓLICE Nº <span >{{ $proposal->apolice_num ?? '-----' }}</span></td>
                               
                            </tr>
                            <tr>
                                 <td  style="text-align:center;"  width="40%" >   </td>
                              <td colspan="3"></td>
                            </tr>
                        </table>
                    </div>
                </td>
                <td width="25%" class="client" style="">
                    <div class="small-box" style="padding-top:8px; padding-bottom:8px; color:#ff0000;">
                        <div style="font-weight:700; font-size:8.6pt; text-align:center;">É Cliente do Banco BIC?</div>
                        <div style="text-align:center; margin-top:22px; font-weight:700;">
                            NÃO <img src="{{ public_path('img/cotacao/' . sg_checked_ref($proposal->cliente_bic ?? '', 'nao')) }}" height="14" alt="">
                            &nbsp;&nbsp;
                            SIM <img src="{{ public_path('img/cotacao/' . sg_checked_ref($proposal->cliente_bic ?? '', 'sim')) }}" height="14" alt="">
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        <div class="line-row" style="margin-top:10px;">
            INÍCIO CONTRATO <span class="inline-field" style="min-width:180px;">{{ sg_date_ref($proposal->inicio_contrato ?? null) }}</span>
            &nbsp;&nbsp; ÀS <span class="inline-field" style="min-width:46px;">{{ sg_hour_ref($proposal->hora_inicio ?? null) }}</span> H
            <span class="inline-field" style="min-width:46px;">{{ sg_minute_ref($proposal->hora_inicio ?? null) }}</span> M
            &nbsp;&nbsp; FIM CONTRATO <span class="inline-field" style="min-width:170px;">{{ sg_date_ref($proposal->fim_contrato ?? null) }}</span>
        </div>

        <div class="line-row">
            DURAÇÃO DO CONTRATO:
            <img src="{{ public_path('img/cotacao/' . sg_checked_ref($proposal->duracao_contrato ?? '', 'anual')) }}" height="14" alt=""> ANO E SEGUINTES
            &nbsp;&nbsp;&nbsp;&nbsp;
            <img src="{{ public_path('img/cotacao/' . sg_checked_ref($proposal->duracao_contrato ?? '', 'temporario')) }}" height="14" alt=""> TEMPORÁRIO
        </div>

        <div class="line-row">
            FORMA DE PAGAMENTO:
            <img src="{{ public_path('img/cotacao/' . sg_checked_ref($proposal->forma_pagamento ?? '', 'anual')) }}" height="14" alt=""> ANUAL
            &nbsp;&nbsp;&nbsp;&nbsp;
            <img src="{{ public_path('img/cotacao/' . sg_checked_ref($proposal->forma_pagamento ?? '', 'semestral')) }}" height="14" alt=""> SEMESTRAL
            &nbsp;&nbsp;&nbsp;&nbsp;
            <img src="{{ public_path('img/cotacao/' . sg_checked_ref($proposal->forma_pagamento ?? '', 'trimestral')) }}" height="14" alt=""> TRIMESTRAL
            &nbsp;&nbsp;&nbsp;&nbsp;
            <img src="{{ public_path('img/cotacao/' . sg_checked_ref($proposal->forma_pagamento ?? '', 'mensal')) }}" height="14" alt=""> MENSAL
            &nbsp;&nbsp;&nbsp;&nbsp;
            <img src="{{ public_path('img/cotacao/' . sg_checked_ref($proposal->forma_pagamento ?? '', 'unico')) }}" height="14" alt=""> ÚNICO
        </div>

        <div class="section-bar">TOMADOR DE SEGURO</div>
        <div class="line-row">NOME: <span class="fill-line" style="width: 905px;">{{ $proposal->tomador_nome ?? '' }}</span></div>
        <div class="line-row">MORADA: <span class="fill-line" style="width: 885px;">{{ $proposal->tomador_morada ?? '' }}</span></div>
        <div class="line-row">
            MUNICÍPIO: <span class="fill-line" style="width: 260px;">{{ $proposal->tomador_municipio ?? '' }}</span>
            &nbsp;&nbsp; LOCALIDADE: <span class="fill-line" style="width: 285px;">{{ $proposal->tomador_localidade ?? '' }}</span>
            &nbsp;&nbsp; B I: <span class="fill-line" style="width: 235px;">{{ $proposal->tomador_bi ?? '' }}</span>
        </div>
        <div class="line-row">
            TELEFONE: <span class="fill-line" style="width: 240px;">{{ $proposal->tomador_telefone ?? '' }}</span>
            &nbsp;&nbsp; TELEMÓVEL: <span class="fill-line" style="width: 250px;">{{ $proposal->tomador_telemovel ?? '' }}</span>
            &nbsp;&nbsp; FAX: <span class="fill-line" style="width: 250px;">{{ $proposal->tomador_fax ?? '' }}</span>
        </div>
        <div class="line-row">
            EMAIL: <span class="fill-line" style="width: 610px;">{{ $proposal->tomador_email ?? '' }}</span>
            &nbsp;&nbsp; N I F: <span class="fill-line" style="width: 200px;">{{ $proposal->tomador_nif ?? '' }}</span>
        </div>
        <div class="line-row">
            PROFISSÃO/ACTIVIDADE PRINCIPAL: <span class="fill-line" style="width: 360px;">{{ $proposal->tomador_profissao_actividade ?? ($proposal->tomador_actividade ?? '') }}</span>
            &nbsp;&nbsp; DATA DE NASCIMENTO: <span class="fill-line" style="width: 190px;">{{ sg_date_ref($proposal->tomador_data_nascimento ?? null) }}</span>
        </div>
        <div class="line-row">
            SEXO:
            <img src="{{ public_path('img/cotacao/' . sg_checked_ref($proposal->tomador_sexo ?? '', 'F')) }}" height="14" alt=""> F
            &nbsp;
            <img src="{{ public_path('img/cotacao/' . sg_checked_ref($proposal->tomador_sexo ?? '', 'M')) }}" height="14" alt=""> M
            &nbsp;&nbsp; NACIONALIDADE: <span class="fill-line" style="width: 290px;">{{ $proposal->tomador_nacionalidade ?? '' }}</span>
            &nbsp;&nbsp; ESTADO CIVIL: <span class="fill-line" style="width: 300px;">{{ $proposal->tomador_estado_civil ?? '' }}</span>
        </div>

        <div class="section-bar">SEGURADO <span class="light paren-text">(Preencher se diferente do Tomador de Seguro)</span></div>
        <div class="line-row">NOME: <span class="fill-line" style="width: 905px;">{{ $proposal->segurado_nome ?? '' }}</span></div>
        <div class="line-row">MORADA: <span class="fill-line" style="width: 885px;">{{ $proposal->segurado_morada ?? '' }}</span></div>
        <div class="line-row">
            MUNICÍPIO: <span class="fill-line" style="width: 315px;">{{ $proposal->segurado_municipio ?? '' }}</span>
            &nbsp;&nbsp; LOCALIDADE: <span class="fill-line" style="width: 450px;">{{ $proposal->segurado_localidade ?? '' }}</span>
        </div>
        <div class="line-row">
            TELEFONE: <span class="fill-line" style="width: 260px;">{{ $proposal->segurado_telefone ?? '' }}</span>
            &nbsp;&nbsp; TELEMÓVEL: <span class="fill-line" style="width: 255px;">{{ $proposal->segurado_telemovel ?? '' }}</span>
            &nbsp;&nbsp; FAX: <span class="fill-line" style="width: 255px;">{{ $proposal->segurado_fax ?? '' }}</span>
        </div>
        <div class="line-row">
            EMAIL: <span class="fill-line" style="width: 575px;">{{ $proposal->segurado_email ?? '' }}</span>
            &nbsp;&nbsp; N I F: <span class="fill-line" style="width: 235px;">{{ $proposal->segurado_nif ?? '' }}</span>
        </div>
        <div class="line-row">
            PROFISSÃO/ACTIVIDADE PRINCIPAL: <span class="fill-line" style="width: 355px;">{{ $proposal->segurado_profissao_actividade ?? ($proposal->segurado_actividade ?? '') }}</span>
            &nbsp;&nbsp; DATA DE NASCIMENTO: <span class="fill-line" style="width: 190px;">{{ sg_date_ref($proposal->segurado_data_nascimento ?? null) }}</span>
        </div>
        <div class="line-row">
            SEXO:
            <img src="{{ public_path('img/cotacao/' . sg_checked_ref($proposal->segurado_sexo ?? '', 'F')) }}" height="14" alt=""> F
            &nbsp;
            <img src="{{ public_path('img/cotacao/' . sg_checked_ref($proposal->segurado_sexo ?? '', 'M')) }}" height="14" alt=""> M
            &nbsp;&nbsp; NACIONALIDADE: <span class="fill-line" style="width: 250px;">{{ $proposal->segurado_nacionalidade ?? '' }}</span>
            &nbsp;&nbsp; ESTADO CIVIL: <span class="fill-line" style="width: 320px;">{{ $proposal->segurado_estado_civil ?? '' }}</span>
        </div>

        <div class="section-bar">PESSOAS SEGURAS</div>
        <div style="margin:10px 0 14px; font-size:8.9pt;">Conforme ficheiro anexo.</div>

        <div class="section-bar">LOCAL DE COBRANÇA <span class="light paren-text">(Se diferente do Tomador de Seguro)</span></div>
        <div class="line-row">MORADA: <span class="fill-line" style="width: 885px;">{{ $proposal->local_cobranca_morada ?? '' }}</span></div>
        <div class="line-row">
            MUNICÍPIO: <span class="fill-line" style="width: 430px;">{{ $proposal->local_cobranca_municipio ?? '' }}</span>
            &nbsp;&nbsp; LOCALIDADE: <span class="fill-line" style="width: 390px;">{{ $proposal->local_cobranca_localidade ?? '' }}</span>
        </div>

        <div class="section-bar">DÉBITO EM CONTA</div>
        <div style="margin:8px 0 10px;">Se preferir pagar por débito em conta por favor indique:</div>
        <div class="line-row">
            Banco: <span class="fill-line" style="width: 320px;">{{ $proposal->debito_banco ?? '' }}</span>
            &nbsp;&nbsp; IBAN/NIB
            <table style="display:inline-table; border-collapse:collapse; vertical-align:middle; margin-left:4px;">
                <tr>
                    @foreach(range(0, 24) as $i)
                        <td style="border-bottom:1px solid #666; width:18px; height:18px; text-align:center; font-size:8pt;">
                            @if($i === 0)A @elseif($i === 1) O @elseif($i === 2)0 @elseif($i === 3)6 @else{{ $ibanDigits[$i - 4] ?? '' }}@endif
                        </td>
                    @endforeach
                </tr>
            </table>
        </div>

        <div class="section-bar">CONTRATOS ANTERIORES EM OUTRAS SEGURADORAS</div>
        <div class="line-row" style="font-size:8.6pt;">
            O risco que pretende cobrir está ou esteve seguro, no seu todo ou em parte, noutra seguradora?
            &nbsp; NÃO <img src="{{ public_path('img/cotacao/' . sg_checked_ref($proposal->anterior_segurado ?? '', 'nao')) }}" height="14" alt="">
            &nbsp; SIM <img src="{{ public_path('img/cotacao/' . sg_checked_ref($proposal->anterior_segurado ?? '', 'sim')) }}" height="14" alt="">
        </div>
        <div class="line-row">
            Se sim, indique qual a seguradora?
            <span class="fill-line" style="width: 345px;">{{ $proposal->anterior_seguradora ?? '' }}</span>
            Nº. Apólice?
            <span class="fill-line" style="width: 315px;">{{ $proposal->anterior_apolice_num ?? '' }}</span>
        </div>

        <div class="section-bar">PRÉMIOS EM DÍVIDA</div>
        <div class="line-row" style="font-size:8.6pt;">
            Relativamente aos mesmos contratos de seguros, existem quaisquer débitos por liquidar à anterior seguradora?
            &nbsp; NÃO <img src="{{ public_path('img/cotacao/' . sg_checked_ref($proposal->premios_divida ?? '', 'nao')) }}" height="14" alt="">
            &nbsp; SIM <img src="{{ public_path('img/cotacao/' . sg_checked_ref($proposal->premios_divida ?? '', 'sim')) }}" height="14" alt="">
        </div>
        <div class="line-row">
            Se sim, especifique <span class="fill-line" style="width: 785px;">{{ $proposal->premios_divida_detalhes ?? '' }}</span>
        </div>

        <div class="section-bar">PLANO SELECCIONADO</div>
        <table style="width:100%; border-collapse:collapse; margin-top:10px; font-size:8.8pt;">
            <tr>
                <td width="25%">BIC PLENO <img src="{{ public_path('img/cotacao/' . sg_checked_ref($proposal->plano_saude ?? '', 'bic_pleno')) }}" height="14" alt=""></td>
                <td width="25%">BIC EXTRA <img src="{{ public_path('img/cotacao/' . sg_checked_ref($proposal->plano_saude ?? '', 'bic_extra')) }}" height="14" alt=""></td>
                <td width="25%">BIC TOTAL <img src="{{ public_path('img/cotacao/' . sg_checked_ref($proposal->plano_saude ?? '', 'bic_total')) }}" height="14" alt=""></td>
                <td width="25%">BIC INTEGRAL <img src="{{ public_path('img/cotacao/' . sg_checked_ref($proposal->plano_saude ?? '', 'bic_integral')) }}" height="14" alt=""></td>
            </tr>
            <tr>
                <td style="padding-top:14px;">BIC EXTRA PLUS <img src="{{ public_path('img/cotacao/' . sg_checked_ref($proposal->plano_saude ?? '', 'bic_extra_plus')) }}" height="14" alt=""></td>
                <td style="padding-top:14px;">BIC TOTAL PLUS <img src="{{ public_path('img/cotacao/' . sg_checked_ref($proposal->plano_saude ?? '', 'bic_total_plus')) }}" height="14" alt=""></td>
                <td colspan="2" style="padding-top:14px;">BIC INTEGRAL PLUS <img src="{{ public_path('img/cotacao/' . sg_checked_ref($proposal->plano_saude ?? '', 'bic_integral_plus')) }}" height="14" alt=""></td>
            </tr>
        </table>

        {!! sg_footer(1) !!}
    </div>

    <div class="page" style="position:relative; min-height:1500px; padding:0 12px 120px;">
        

        <div class="section-bar">PLANOS</div>

        <table class="table-ref" style="margin-top:36px; font-size:8.2pt;">
            <tr>
                <th class="left" style="width:40%;">PLANOS de SAÚDE BIC Seguros</th>
                <th class="center" colspan="4" style="font-size:9pt;">Limite de Responsabilidade por pessoa/anuidade</th>
            </tr>
            <tr>
                <th class="left">COBERTURAS</th>
                <th class="center" style="color:#ff0000;">BIC Pleno</th>
                <th class="center" style="color:#ff0000;">BIC Extra</th>
                <th class="center" style="color:#ff0000;">BIC Total</th>
                <th class="center" style="color:#ff0000;">BIC Integral</th>
            </tr>
            <tr>
                <td class="left"><strong>ÁREA DE COBERTURA</strong></td>
                <td class="center" style="font-size:7.8pt; line-height:1.4;"><strong>ANGOLA</strong><br>África do Sul<br>Brasil<br>Namíbia<br>Portugal</td>
                <td class="center" style="font-size:7.8pt; line-height:1.4;"><strong>ANGOLA</strong><br>África do Sul<br>Brasil<br>Namíbia<br>Portugal</td>
                <td class="center" style="font-size:7.8pt; line-height:1.4;"><strong>MUNDIAL</strong><br>(EXCEPTO EUA<br>e MÉDIO<br>ORIENTE)</td>
                <td class="center" style="font-size:7.8pt; line-height:1.4;"><strong>MUNDIAL</strong> (EXCEPTO EUA e MÉDIO ORIENTE)</td>
            </tr>
            <tr><td class="left"><strong>LIMITE DE COBERTURA DO PLANO</strong></td><td class="center"><strong>12.750.000</strong></td><td class="center"><strong>25.500.000</strong></td><td class="center"><strong>51.000.000</strong></td><td class="center"><strong>170.000.000</strong></td></tr>
            <tr><td class="left"><strong>INTERNAMENTO HOSPITALAR</strong></td><td class="center"><strong>12.750.000</strong></td><td class="center"><strong>25.500.000</strong></td><td class="center"><strong>51.000.000</strong></td><td class="center"><strong>170.000.000</strong></td></tr>
            <tr><td class="left" style="padding-left:56px;">ACOMODAÇÃO</td><td class="center">Quarto colectivo</td><td class="center">Quarto colectivo</td><td class="center">Quarto individual</td><td class="center">Quarto individual</td></tr>
            <tr>
                <td class="left"><strong>TRATAMENTOS, CONSULTAS E EXAMES</strong><div style="padding-left:95px; margin-top:18px; line-height:1.7;">CONSULTAS<br>EXAMES E TRATAMENTOS<br>FISIOTERAPIA</div></td>
                <td class="center" style="vertical-align:top; padding-top:18px;"><strong>255.000</strong><div style="margin-top:72px;" class="gray-cell">&nbsp;</div></td>
                <td class="center" style="vertical-align:top; padding-top:18px;"><strong>510.000</strong><div style="margin-top:72px;">10 Sessões</div></td>
                <td class="center" style="vertical-align:top; padding-top:18px;"><strong>680.000</strong><div style="margin-top:72px;">15 Sessões</div></td>
                <td class="center" style="vertical-align:top; padding-top:18px;"><strong>2.040.000</strong><div style="margin-top:72px;">20 Sessões</div></td>
            </tr>
            <tr><td class="left"><strong>MEDICAMENTOS</strong> (1)</td><td class="gray-cell"></td><td class="center">60.000</td><td class="center">75.000</td><td class="center">150.000</td></tr>
            <tr><td class="left"><strong>ESTOMATOLOGIA (Consultas e Tratamentos)</strong></td><td class="gray-cell"></td><td class="center">42.500</td><td class="center">340.000</td><td class="center">425.000</td></tr>
            <tr><td class="left"><strong>ÓCULOS E OUTRAS PRÓTESES</strong></td><td class="gray-cell"></td><td class="gray-cell"></td><td class="center">51.000</td><td class="center">85.000</td></tr>
            <tr><td class="left"><strong>PARTO</strong></td><td class="gray-cell"></td><td class="center">510.000 <sup>(2)</sup></td><td class="center">510.000 <sup>(2)</sup></td><td class="center">680.000 <sup>(3)</sup></td></tr>
            <tr><td class="left"><strong>CHECK-UP (1 por ano)</strong></td><td class="gray-cell"></td><td class="center">85.000</td><td class="center">85.000</td><td class="center">170.000</td></tr>
            <tr><td class="left"><strong>SERVIÇO DE EVACUAÇÃO</strong></td><td class="center">8.500.000</td><td class="center">8.500.000</td><td class="center">12.750.000</td><td class="center">12.750.000</td></tr>
        </table>

        <div style="margin-top:28px; padding-left:44px; font-size:8.8pt; line-height:1.55;">
            <strong>Observações:</strong><br>
            (1) &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Inclui Vacinas<br>
            (2) &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Parto Normal - 510.000 Kz<br>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Parto Cesariana justificada - 935.000 Kz<br>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Complicações na gravidez - até ao limite do internamento<br>
            (3) &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Parto Normal - 680.000 Kz<br>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Parto Cesariana justificada - 1.105.000 Kz<br>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Complicações na gravidez - até ao limite do internamento
        </div>

        {!! sg_footer(2) !!}
    </div>

    <div class="page" style="position:relative; min-height:1500px; padding:0 12px 120px;">
       
        <div class="section-bar">PLANOS PLUS</div>

        <table class="table-ref" style="margin-top:36px; font-size:8.2pt;">
            <tr>
                <th class="left" style="width:40%;">PLANOS PLUS de SAÚDE BIC Seguros</th>
                <th class="center" colspan="3" style="font-size:9pt;">Limite de Responsabilidade por pessoa/anuidade</th>
            </tr>
            <tr>
                <th class="left">COBERTURAS</th>
                <th class="center" style="color:#ff0000;">BIC Extra Plus</th>
                <th class="center" style="color:#ff0000;">BIC Total Plus</th>
                <th class="center" style="color:#ff0000;">BIC Integral Plus</th>
            </tr>
            <tr>
                <td class="left"><strong>ÁREA DE COBERTURA</strong></td>
                <td class="center" style="font-size:7.8pt; line-height:1.4;"><strong>ANGOLA</strong><br>África do Sul<br>Brasil<br>Namíbia<br>Portugal</td>
                <td class="center" style="font-size:7.8pt; line-height:1.4;"><strong>MUNDIAL</strong> (EXCEPTO EUA e MÉDIO ORIENTE)</td>
                <td class="center" style="font-size:7.8pt; line-height:1.4;"><strong>MUNDIAL</strong> (EXCEPTO EUA e MÉDIO ORIENTE)</td>
            </tr>
            <tr><td class="left"><strong>LIMITE DE COBERTURA DO PLANO</strong></td><td class="center"><strong>25.500.000</strong></td><td class="center"><strong>51.000.000</strong></td><td class="center"><strong>170.000.000</strong></td></tr>
            <tr><td class="left"><strong>INTERNAMENTO HOSPITALAR</strong></td><td class="center"><strong>25.500.000</strong></td><td class="center"><strong>51.000.000</strong></td><td class="center"><strong>170.000.000</strong></td></tr>
            <tr><td class="left" style="padding-left:56px;">ACOMODAÇÃO</td><td class="center">Quarto colectivo</td><td class="center">Quarto individual</td><td class="center">Quarto individual</td></tr>
            <tr>
                <td class="left"><strong>TRATAMENTOS, CONSULTAS E EXAMES</strong><div style="padding-left:95px; margin-top:18px; line-height:1.7;">CONSULTAS<br>EXAMES E TRATAMENTOS<br>FISIOTERAPIA</div></td>
                <td class="center" style="vertical-align:top; padding-top:18px;"><strong>600.000</strong><div style="margin-top:72px;">10 Sessões</div></td>
                <td class="center" style="vertical-align:top; padding-top:18px;"><strong>750.000</strong><div style="margin-top:72px;">15 Sessões</div></td>
                <td class="center" style="vertical-align:top; padding-top:18px;"><strong>2.040.000</strong><div style="margin-top:72px;">20 Sessões</div></td>
            </tr>
            <tr><td class="left"><strong>MEDICAMENTOS</strong> (1)</td><td class="center">120.000</td><td class="center">150.000</td><td class="center">300.000</td></tr>
            <tr><td class="left"><strong>ESTOMATOLOGIA (Consultas e Tratamentos)</strong></td><td class="center">42.500</td><td class="center">340.000</td><td class="center">425.000</td></tr>
            <tr><td class="left"><strong>ÓCULOS E OUTRAS PRÓTESES</strong></td><td class="gray-cell"></td><td class="center">51.000</td><td class="center">85.000</td></tr>
            <tr><td class="left"><strong>PARTO</strong></td><td class="center">1.000.000 <sup>(2)</sup></td><td class="center">1.000.000 <sup>(2)</sup></td><td class="center">1.200.000 <sup>(3)</sup></td></tr>
            <tr><td class="left"><strong>CHECK-UP (1 por ano)</strong></td><td class="center">85.000</td><td class="center">85.000</td><td class="center">170.000</td></tr>
            <tr><td class="left"><strong>SERVIÇO DE EVACUAÇÃO</strong></td><td class="center">8.500.000</td><td class="center">12.750.000</td><td class="center">12.750.000</td></tr>
        </table>

        <div style="margin-top:28px; padding-left:44px; font-size:8.8pt; line-height:1.55;">
            <strong>Observações:</strong><br>
            (1) &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Inclui Vacinas<br>
            (2) &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Parto Normal - 1.000.000 Kz<br>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Parto Cesariana justificada - 1.800.000 Kz<br>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Complicações na gravidez - até ao limite do internamento<br>
            (3) &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Parto Normal - 1.200.000 Kz<br>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Parto Cesariana justificada - 1.950.000 Kz<br>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Complicações na gravidez - até ao limite do internamento
        </div>

        {!! sg_footer(3) !!}
    </div>

    <div class="page" style="position:relative; min-height:1500px; padding:0 12px 120px;">
       

        <div class="section-bar">COMPARTICIPAÇÕES</div>

        <table class="table-ref" style="margin-top:34px; font-size:8.1pt;">
            <tr>
                <th class="left" rowspan="2" style="width:37%;">PLANOS de SAÚDE BIC Seguros<br>(COBERTURAS)</th>
                <th class="center" colspan="2" style="font-size:10pt;">Comparticipação pelo BIC Seguros</th>
            </tr>
            <tr>
                <th class="center" style="color:#ff0000;">Dentro da Rede</th>
                <th class="center" style="color:#ff0000;">Fora da Rede</th>
            </tr>
            <tr><td class="left"><strong>INTERNAMENTO HOSPITALAR</strong></td><td class="center">100%</td><td class="center">70%</td></tr>
            <tr>
                <td class="left"><strong>TRATAMENTOS, CONSULTAS E EXAMES</strong><div style="padding-left:40px; margin-top:22px; line-height:2.2;">CONSULTAS<br>EXAMES E TRATAMENTOS<br>FISIOTERAPIA</div></td>
                <td class="center" style="vertical-align:middle; line-height:1.5;">
                    O valor da consulta menos o equivalente a 2.400 Kz que será pago pela Pessoa Segura
                    <div style="margin-top:14px;">90%</div>
                    <div style="margin-top:14px;">O valor da Sessão menos o equivalente a 1.700 Kz que será pago pela Pessoa Segura</div>
                </td>
                <td class="center" style="vertical-align:middle; line-height:1.7;">
                    70%
                    <div style="margin-top:28px;">70%</div>
                    <div style="margin-top:26px;">70%</div>
                </td>
            </tr>
            <tr>
                <td class="left"><strong>MEDICAMENTOS</strong></td>
                <td class="center" style="line-height:1.45;">O valor dos medicamentos menos o equivalente a 10% que será pago pela Pessoa Segura</td>
                <td class="center" style="line-height:1.45;">O valor dos medicamentos menos o equivalente a 10% que será pago pela Pessoa Segura</td>
            </tr>
            <tr><td class="left"><strong>ESTOMATOLOGIA (Consulta e Tratamentos)</strong></td><td class="center">75%</td><td class="center">65%</td></tr>
            <tr><td class="left"><strong>ÓCULOS E OUTRAS PRÓTESES</strong></td><td class="center">80%</td><td class="center">75%</td></tr>
            <tr><td class="left"><strong>PARTO</strong></td><td class="center">90%</td><td class="center">70%</td></tr>
            <tr><td class="left"><strong>CONDIÇÃO MÉDICA CRÓNICA</strong></td><td class="center">100%</td><td class="center">100%</td></tr>
            <tr>
                <td class="left"><strong>CHECK-UP (1 por ano)</strong></td>
                <td class="center" style="line-height:1.45;">O valor do Check-Up menos o equivalente a 3.400 Kz que será pago pela Pessoa Segura</td>
                <td class="center" style="line-height:1.45;">O valor do Check-Up menos o equivalente a 8.500 Kz que será pago pela Pessoa Segura</td>
            </tr>
        </table>

        <div class="section-bar" style="margin-top:34px;">PERÍODOS DE CARÊNCIA</div>

        <table class="table-ref" style="margin-top:22px; font-size:8.2pt;">
            <tr>
                <th class="left" style="width:87%;">COBERTURAS</th>
                <th class="center" style="width:13%;">Período de Carência</th>
            </tr>
            <tr><td class="left">INTERNAMENTO HOSPITALAR</td><td class="center">60 dias</td></tr>
            <tr><td class="left">TRATAMENTOS, CONSULTAS E EXAMES, MEDICAMENTOS, ÓCULOS E OUTRAS PRÓTESES</td><td class="center">60 dias</td></tr>
            <tr><td class="left">Estomatologia</td><td class="center">120 dias</td></tr>
            <tr><td class="left">Tratamento cirúrgico ou por outros métodos invasivos da hipertrofia benigna da próstata, tratamento cirúrgico ou por outros métodos invasivos de patologia benigna do útero, tratamento cirúrgico de cistocelo e rectocelo</td><td class="center">180 dias</td></tr>
            <tr><td class="left">PARTO, tratamento cirúrgico de varizes dos membros inferiores, tratamento cirúrgico de hérnia discal, hemorroidectomia e outros tratamentos da doença hemorroidária bem como tratamento cirúrgico da fistula perianal, tratamento de patologia articular por via artroscópica, septoplastia, amigdalectomia, adenoidectomia, miringotomias com ou sem aplicação de tubos de ventilação, rinoseptoplastia, excisão cirúrgica de lesões benignas da pele e tecido celular subcutâneo, tratamentos com laser a lesões benignas da pele, tratamento cirúrgico da apneia do sono, tratamentos psiquiátricos.</td><td class="center">365 dias</td></tr>
            <tr><td class="left">Pré-existências</td><td class="center">720 dias</td></tr>
            <tr><td class="left">HIV/SIDA</td><td class="center">60 meses</td></tr>
        </table>

        {!! sg_footer(4) !!}
    </div>

    <div class="page" style="position:relative; min-height:1500px; padding-bottom:120px;">
      

        <div class="section-bar">OBSERVAÇÕES</div>
        <div class="obs-lines" style="margin-top:30px;">
            @for($i = 0; $i < 3; $i++)
                <div class="obs-line" style="height:24px;">@if($i === 0){{ $proposal->observacoes ?? '' }}@endif</div>
            @endfor
        </div>

        <div class="section-bar" style="margin-top:28px;">DECLARAÇÃO</div>
        <div style="font-size:8.8pt; line-height:1.55; margin-top:16px;">
            Para efeitos da celebração do presente contrato de seguro, declaro que:
            <br>a) &nbsp;&nbsp;&nbsp; Respondi completamente e com veracidade a todas as perguntas e verifiquei o preenchimento desta proposta.
            <br>b) &nbsp;&nbsp;&nbsp; São exactas e completas as declarações por mim prestadas e tomei conhecimento de todas as informações necessárias à celebração do presente contrato, tendo sido colocadas à minha disposição as respectivas Condições Gerais e Especiais, para delas tomar integral conhecimento, e prestados todos os esclarecimentos sobre as mesmas, nomeadamente sobre garantias e exclusões, com as quais estou de acordo.
            <br>c) &nbsp;&nbsp;&nbsp; Autorizo a consulta dos dados pessoais disponibilizados, sob regime de absoluta confidencialidade, pelas empresas que integram o grupo desde que compatível com a finalidade da recolha dos mesmos.
            <br>d) &nbsp;&nbsp;&nbsp; Autorizo a BIC Seguros, S.A a proceder à recolha de dados pessoais complementares junto de organismos públicos, empresas especializadas e outras unidades económicas com vista à confirmação ou complemento dos elementos recolhidos e necessários à gestão da relação contratual.
        </div>

        <div style="margin-top:26px; font-size:8.8pt;">
            Feito, em <span class="fill-line" style="width: 210px;">{{ $proposal->local_assinatura ?? '' }}</span>
            a <span class="fill-line" style="width: 40px;"></span> de
            <span class="fill-line" style="width: 210px;"></span> de
            <span class="fill-line" style="width: 70px;"></span>
        </div>

        <div style="margin-top:52px; text-align:right; padding-right:20px;">
            <div class="fill-line" style="width: 360px; display:inline-block;"></div><br>
            (Assinatura do Tomador de Seguro)
        </div>

        <div class="debit-panel" style="margin-top:18px; padding:8px 10px 14px;">
            <div style="text-align:right; font-size:11pt; font-weight:700; margin-bottom:4px;">Autorização de Débito em Conta</div>

            <table style="width:100%; border-collapse:collapse; font-size:8.6pt;">
                <tr>
                    <td width="8%" style="padding:2px 4px;">Banco</td>
                    <td width="38%" class="muted-fill" style="padding:2px 10px;">{{ $proposal->aut_banco ?? 'Banco BIC' }}</td>
                    <td width="10%" style="padding:2px 10px; text-align:right;">Agência</td>
                    <td width="18%" class="muted-fill"></td>
                    <td width="8%" style="padding:2px 10px; text-align:right;">Data</td>
                    <td width="18%">
                        <table style="width:100%; border-collapse:collapse;">
                            <tr>
                                <td style="border:1px solid #111; height:20px; width:33%;"></td>
                                <td style="border:1px solid #111; width:33%;"></td>
                                <td style="border:1px solid #111; width:34%;" class="muted-fill"></td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <table style="width:100%; border-collapse:collapse; margin-top:2px; font-size:8.6pt;">
                <tr>
                    <td width="66%" style="border-right:1px solid #111; vertical-align:top;">
                        <div style="font-weight:700; margin-left:10px;">Mutuário</div>
                        <table style="width:100%; border-collapse:collapse;">
                            <tr>
                                <td width="16%" style="padding:3px 4px 3px 10px;">Nome</td>
                                <td width="84%" class="muted-fill" style="padding:3px 8px;">{{ $proposal->aut_mutuario_nome ?? ($proposal->tomador_nome ?? '') }}</td>
                            </tr>
                            <tr>
                                <td style="padding:5px 4px 34px 10px; vertical-align:top;">Morada</td>
                                <td class="muted-fill" style="height:56px; vertical-align:top; padding:5px 8px;">{{ $proposal->aut_mutuario_morada ?? ($proposal->tomador_morada ?? '') }}</td>
                            </tr>
                        </table>
                    </td>
                    <td width="34%" style="vertical-align:top; padding-left:14px;">
                        <div style="text-align:center;">Referência</div>
                        <div class="muted-fill" style="height:22px; margin:4px 12px 0;">{{ $proposal->aut_referencia ?? '' }}</div>
                    </td>
                </tr>
            </table>

            <div style="margin-top:8px; font-size:8.7pt; line-height:1.35;">
                Venho pela presente autorizar V. Exas. a pagar os prémios de seguro emitidos pela BIC Seguros, ao abrigo do contrato de seguro em vigor, por débito directo da minha conta a seguir indicada:
            </div>

            <table style="border-collapse:collapse; margin-top:4px; width:100%; font-size:8.6pt;">
                <tr>
                    <td style="border:1px solid #111; padding:4px 10px; width:80px;">IBAN :</td>
                    <td style="border:1px solid #111; width:24px; text-align:center;">A</td>
                    <td style="border:1px solid #111; width:24px; text-align:center;">O</td>
                    <td style="border:1px solid #111; width:24px; text-align:center;">0</td>
                    <td style="border:1px solid #111; width:24px; text-align:center;">6</td>
                    @foreach(range(0, 20) as $i)
                        <td class="muted-fill" style="border:1px solid #111; width:24px; text-align:center;">{{ $ibanDigits[$i] ?? '' }}</td>
                    @endforeach
                </tr>
            </table>

            <div style="margin-top:6px; font-size:8.7pt; line-height:1.35;">
                Para o efeito, comprometo-me a manter a referida conta devidamente provisionada. Autorizo igualmente que nos casos de alteração do prémio de seguro, assim como do pagamento de moras, as referidas prestações sejam alteradas.
            </div>

            <div style="margin-top:14px;">
                Valor máximo de autorização:
                <span class="fill-line" style="width: 320px;">{{ $proposal->aut_valor_maximo ?? '' }}</span>
                Kz
            </div>

            <div style="margin-top:18px;">Periodicidade dos pagamentos:</div>
            <table style="width:100%; border-collapse:collapse; margin-top:10px; font-size:8.7pt;">
                <tr>
                    <td width="25%"><img src="{{ public_path('img/cotacao/' . sg_checked_ref($proposal->aut_periodicidade ?? '', 'anual')) }}" height="14" alt=""> ANUAL</td>
                    <td width="25%"><img src="{{ public_path('img/cotacao/' . sg_checked_ref($proposal->aut_periodicidade ?? '', 'semestral')) }}" height="14" alt=""> SEMESTRAL</td>
                    <td width="25%"><img src="{{ public_path('img/cotacao/' . sg_checked_ref($proposal->aut_periodicidade ?? '', 'trimestral')) }}" height="14" alt=""> TRIMESTRAL</td>
                    <td width="25%"><img src="{{ public_path('img/cotacao/' . sg_checked_ref($proposal->aut_periodicidade ?? '', 'mensal')) }}" height="14" alt=""> MENSAL</td>
                </tr>
            </table>

            <div style="text-align:center; margin-top:46px;">
                <div class="fill-line" style="width: 700px;"></div><br>
                (Assinatura de acordo com a ficha bancária)
            </div>
        </div>

        {!! sg_footer(5) !!}
    </div>
</body>
</html>
