<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
</head>

<body>

    @php
    // Helper para JSON / arrays seguros (compatível com formato Angular)
    function fixJsonField($value)
    {
        if (is_null($value) || $value === '') return [];
        if (is_array($value)) return $value;
        if (is_string($value)) {
            $value = trim($value, '"');
            $value = stripslashes($value);
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) return $decoded;
        }
        return [];
    }

    // Helper para checkbox booleano
    function checkBool($value) {
        return ($value === true || $value === 'true' || $value === 1 || $value === '1' || $value === 'sim')
            ? 'check-prenchido.png'
            : 'check-proposta.png';
    }

    // Helper para checkbox por valor (radio)
    function checkRadio($current, $value) {
        return ($current == $value) ? 'check-prenchido.png' : 'check-proposta.png';
    }
    @endphp

    {{-- ============================================================
         PÁGINA 1 — Dados Iniciais, Tomador, Segurado, Pessoas Seguras
         ============================================================ --}}
    <div class="page">
        <div class="box-logo">
            <img src="{{ public_path('img/cotacao/bic_image-header.png') }}" height="60" alt="">
        </div>

        <table class="form-table">
            <tr>
                <td width="24%" style="border: 1px solid black; padding-top:8px; padding-left:8px; font-size:11px">
                    <span>
                        <strong>
                            Data de Entrada: <br>
                            {{ $proposal->data_entrada ? \Carbon\Carbon::parse($proposal->data_entrada)->format('d/m/Y') : '' }}
                        </strong>
                    </span>
                    <br><br><br>
                    <span>
                        <strong>
                            Ag. Produtor: {{ $proposal->agente_produtor ?? '' }}
                        </strong>
                    </span>
                    <br><br>
                    <p style="color: white;">jj</p>
                </td>
                <td width="46%" style="text-align: center;">
                    <div class="title">PROPOSTA SEGURO<br>ACIDENTES PESSOAIS<br><br></div>
                    <div style="margin-bottom: 15px;">
                        <p>
                            <span style="vertical-align: middle;">SEGURO NOVO</span>
                            &nbsp;&nbsp;&nbsp;
                            <img src="{{ public_path('img/cotacao/' . checkRadio($proposal->tipo_proposta ?? '', 'novo')) }}"
                                style="height: 16px; vertical-align: middle;" alt="">
                        </p>
                        <p>
                            <span style="vertical-align: middle;">ALTERAÇÃO</span>
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            <img src="{{ public_path('img/cotacao/' . checkRadio($proposal->tipo_proposta ?? '', 'alteracao')) }}"
                                style="height: 16px; vertical-align: middle;" alt="">
                        </p>
                    </div>
                </td>
                <td width="27%" style="border: 1px solid black; padding-top:23px; padding-left:15px; color:red">
                    <span><strong>É Cliente do Banco BIC?</strong></span>
                    <br><br>
                    <span>
                        <span>
                            <strong>NÃO:</strong>
                            <span class="checkbox">
                                <img src="{{ public_path('img/cotacao/' . checkRadio($proposal->cliente_bic ?? '', 'nao')) }}"
                                    style="height: 16px;" alt="">
                            </span>
                        </span>
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        <span>
                            <strong>SIM:</strong>
                            <span class="checkbox">
                                <img src="{{ public_path('img/cotacao/' . checkRadio($proposal->cliente_bic ?? '', 'sim')) }}"
                                    style="height: 16px;" alt="">
                            </span>
                        </span>
                    </span>
                    <p style="color: white;">jj</p>
                </td>
            </tr>
        </table>
        <br>

        <div style="margin: 5px 0;">Apólice Nº: {{ $proposal->apolice_num ?? '' }}</div>

        <table class="form-table">
            <tr>
                <td width="40%">INÍCIO DO CONTRATO:
                    {{ $proposal->inicio_contrato ? \Carbon\Carbon::parse($proposal->inicio_contrato)->format('d/m/Y') : '' }}
                </td>
                <td width="20%">Horas:
                    {{ $proposal->hora_inicio ? \Carbon\Carbon::parse($proposal->hora_inicio)->format('H:i') : '' }}
                </td>
                <td width="40%">FIM DO CONTRATO:
                    {{ $proposal->fim_contrato ? \Carbon\Carbon::parse($proposal->fim_contrato)->format('d/m/Y') : '' }}
                </td>
            </tr>
        </table>

        <div style="margin: 10px 0; font-weight:bold;">
            DURAÇÃO DO CONTRATO:
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkRadio($proposal->duracao_contrato ?? '', 'anual')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> ANO E SEGUINTES &nbsp;&nbsp;&nbsp;&nbsp;
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkRadio($proposal->duracao_contrato ?? '', 'temporario')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> TEMPORÁRIO (Mínimo 30 dias)
        </div>

        <div style="margin-bottom: 15px;">
            <strong>FORMA DE PAGAMENTO:</strong> &nbsp;&nbsp;&nbsp;
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkRadio($proposal->forma_pagamento ?? '', 'anual')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> <strong>ANUAL</strong> &nbsp;&nbsp;
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkRadio($proposal->forma_pagamento ?? '', 'semestral')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> <strong>SEMESTRAL</strong> &nbsp;&nbsp;
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkRadio($proposal->forma_pagamento ?? '', 'trimestral')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> <strong>TRIMESTRAL</strong> &nbsp;&nbsp;
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkRadio($proposal->forma_pagamento ?? '', 'mensal')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> <strong>MENSAL</strong> &nbsp;&nbsp;
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkRadio($proposal->forma_pagamento ?? '', 'unico')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> <strong>ÚNICO</strong>
        </div>

        {{-- TOMADOR --}}
        <div class="section-title">TOMADOR DE SEGURO</div>
        <div style="margin: 5px 0;">NOME/SIGLA: {{ $proposal->tomador_nome ?? '' }}</div>
        <div style="margin: 5px 0;">MORADA: {{ $proposal->tomador_morada ?? '' }}</div>
        <table class="form-table">
            <tr>
                <td width="50%">MUNICÍPIO: {{ $proposal->tomador_municipio ?? '' }}</td>
                <td width="50%">LOCALIDADE: {{ $proposal->tomador_localidade ?? '' }}</td>
            </tr>
        </table>
        <table class="form-table">
            <tr>
                <td width="33%">TELEFONE: {{ $proposal->tomador_telefone ?? '' }}</td>
                <td width="33%">TELEMÓVEL: {{ $proposal->tomador_telemovel ?? '' }}</td>
                <td width="34%">FAX: {{ $proposal->tomador_fax ?? '' }}</td>
            </tr>
        </table>
        <table class="form-table">
            <tr>
                <td width="50%">E-MAIL: {{ $proposal->tomador_email ?? '' }}</td>
                <td width="25%">B.I. Nº: {{ $proposal->tomador_bi ?? '' }}</td>
                <td width="25%">N I F: {{ $proposal->tomador_nif ?? '' }}</td>
            </tr>
        </table>
        <table class="form-table">
            <tr>
                <td width="50%">PROFISSÃO: {{ $proposal->tomador_profissao ?? '' }}</td>
                <td width="50%">DATA DE NASCIMENTO:
                    {{ $proposal->tomador_data_nascimento ? \Carbon\Carbon::parse($proposal->tomador_data_nascimento)->format('d/m/Y') : '' }}
                </td>
            </tr>
        </table>
        <table class="form-table">
            <tr>
                <td width="35%">NACIONALIDADE: {{ $proposal->tomador_nacionalidade ?? '' }}</td>
                <td width="30%" style="text-align: center;">
                    SEXO: <strong>F</strong>
                    <span class="checkbox">
                        <img src="{{ public_path('img/cotacao/' . checkRadio($proposal->tomador_sexo ?? '', 'F')) }}"
                            style="height: 16px; vertical-align: middle;" alt="">
                    </span>
                    &nbsp; <strong>M</strong>
                    <span class="checkbox">
                        <img src="{{ public_path('img/cotacao/' . checkRadio($proposal->tomador_sexo ?? '', 'M')) }}"
                            style="height: 16px; vertical-align: middle;" alt="">
                    </span>
                </td>
                <td width="35%">ESTADO CIVIL: {{ $proposal->tomador_estado_civil ?? '' }}</td>
            </tr>
        </table>

        {{-- SEGURADO --}}
        <div class="section-title">SEGURADO <span class="light paren-text">(Preencher se diferente do Tomador de Seguro)</span></div>
        <div style="margin: 5px 0;">NOME: {{ $proposal->segurado_nome ?? '' }}</div>
        <div style="margin: 5px 0;">MORADA: {{ $proposal->segurado_morada ?? '' }}</div>
        <table class="form-table">
            <tr>
                <td width="50%">MUNICÍPIO: {{ $proposal->segurado_municipio ?? '' }}</td>
                <td width="50%">LOCALIDADE: {{ $proposal->segurado_localidade ?? '' }}</td>
            </tr>
        </table>
        <table class="form-table">
            <tr>
                <td width="33%">TELEFONE: {{ $proposal->segurado_telefone ?? '' }}</td>
                <td width="33%">TELEMÓVEL: {{ $proposal->segurado_telemovel ?? '' }}</td>
                <td width="34%">FAX: {{ $proposal->segurado_fax ?? '' }}</td>
            </tr>
        </table>
        <table class="form-table">
            <tr>
                <td width="50%">E-MAIL: {{ $proposal->segurado_email ?? '' }}</td>
                <td width="25%">B.I. Nº: {{ $proposal->segurado_bi ?? '' }}</td>
                <td width="25%">N I F: {{ $proposal->segurado_nif ?? '' }}</td>
            </tr>
        </table>
        <table class="form-table">
            <tr>
                <td width="50%">PROFISSÃO: {{ $proposal->segurado_profissao ?? '' }}</td>
                <td width="50%">DATA DE NASCIMENTO:
                    {{ $proposal->segurado_data_nascimento ? \Carbon\Carbon::parse($proposal->segurado_data_nascimento)->format('d/m/Y') : '' }}
                </td>
            </tr>
        </table>
        <table class="form-table">
            <tr>
                <td width="35%">NACIONALIDADE: {{ $proposal->segurado_nacionalidade ?? '' }}</td>
                <td width="30%" style="text-align: center;">
                    SEXO: <strong>F</strong>
                    <span class="checkbox">
                        <img src="{{ public_path('img/cotacao/' . checkRadio($proposal->segurado_sexo ?? '', 'F')) }}"
                            style="height: 16px; vertical-align: middle;" alt="">
                    </span>
                    &nbsp; <strong>M</strong>
                    <span class="checkbox">
                        <img src="{{ public_path('img/cotacao/' . checkRadio($proposal->segurado_sexo ?? '', 'M')) }}"
                            style="height: 16px; vertical-align: middle;" alt="">
                    </span>
                </td>
                <td width="35%">ESTADO CIVIL: {{ $proposal->segurado_estado_civil ?? '' }}</td>
            </tr>
        </table>

        {{-- LOCAL DE COBRANÇA --}}
        <div class="section-title">LOCAL DE COBRANÇA <span class="light paren-text">(Se diferente do Tomador de Seguro)</span></div>
        <div style="margin: 5px 0;">MORADA: {{ $proposal->local_cobranca_morada ?? '' }}</div>
        <table class="form-table">
            <tr>
                <td width="50%">MUNICÍPIO: {{ $proposal->local_cobranca_municipio ?? '' }}</td>
                <td width="50%">LOCALIDADE: {{ $proposal->local_cobranca_localidade ?? '' }}</td>
            </tr>
        </table>

        {{-- SEGURO INDIVIDUAL — Pessoa Segura 1 --}}
        <div class="section-title">SEGURO INDIVIDUAL — Pessoa Segura 1 <span class="light paren-text">(Preencher apenas se diferente do Tomador)</span></div>
        <div style="margin: 5px 0;">NOME: {{ $proposal->ps1_nome ?? '' }}</div>
        <div style="margin: 5px 0;">MORADA: {{ $proposal->ps1_morada ?? '' }}</div>
        <table class="form-table">
            <tr>
                <td width="50%">MUNICÍPIO: {{ $proposal->ps1_municipio ?? '' }}</td>
                <td width="50%">LOCALIDADE: {{ $proposal->ps1_localidade ?? '' }}</td>
            </tr>
        </table>
        <table class="form-table">
            <tr>
                <td width="33%">TELEFONE: {{ $proposal->ps1_telefone ?? '' }}</td>
                <td width="33%">TELEMÓVEL: {{ $proposal->ps1_telemovel ?? '' }}</td>
                <td width="34%">FAX: {{ $proposal->ps1_fax ?? '' }}</td>
            </tr>
        </table>
        <table class="form-table">
            <tr>
                <td width="50%">E-MAIL: {{ $proposal->ps1_email ?? '' }}</td>
                <td width="25%">B.I. Nº: {{ $proposal->ps1_bi ?? '' }}</td>
                <td width="25%">N I F: {{ $proposal->ps1_nif ?? '' }}</td>
            </tr>
        </table>
        <table class="form-table">
            <tr>
                <td width="50%">PROFISSÃO: {{ $proposal->ps1_profissao ?? '' }}</td>
                <td width="50%">DATA DE NASCIMENTO:
                    {{ $proposal->ps1_data_nascimento ? \Carbon\Carbon::parse($proposal->ps1_data_nascimento)->format('d/m/Y') : '' }}
                </td>
            </tr>
        </table>
        <table class="form-table">
            <tr>
                <td width="35%">NACIONALIDADE: {{ $proposal->ps1_nacionalidade ?? '' }}</td>
                <td width="30%" style="text-align: center;">
                    SEXO: <strong>F</strong>
                    <span class="checkbox">
                        <img src="{{ public_path('img/cotacao/' . checkRadio($proposal->ps1_sexo ?? '', 'F')) }}"
                            style="height: 16px; vertical-align: middle;" alt="">
                    </span>
                    &nbsp; <strong>M</strong>
                    <span class="checkbox">
                        <img src="{{ public_path('img/cotacao/' . checkRadio($proposal->ps1_sexo ?? '', 'M')) }}"
                            style="height: 16px; vertical-align: middle;" alt="">
                    </span>
                </td>
                <td width="35%">ESTADO CIVIL: {{ $proposal->ps1_estado_civil ?? '' }}</td>
            </tr>
        </table>

        {{-- Pessoa Segura 2 --}}
        <div class="section-title">Pessoa Segura 2 <span class="paren-text">(PS2)</span> — Cônjuge ou Equiparado</div>
        <div style="margin: 5px 0;">NOME: {{ $proposal->ps2_nome ?? '' }}</div>
        <div style="margin: 5px 0;">MORADA: {{ $proposal->ps2_morada ?? '' }}</div>
        <table class="form-table">
            <tr>
                <td width="50%">MUNICÍPIO: {{ $proposal->ps2_municipio ?? '' }}</td>
                <td width="50%">LOCALIDADE: {{ $proposal->ps2_localidade ?? '' }}</td>
            </tr>
        </table>
        <table class="form-table">
            <tr>
                <td width="33%">TELEFONE: {{ $proposal->ps2_telefone ?? '' }}</td>
                <td width="33%">TELEMÓVEL: {{ $proposal->ps2_telemovel ?? '' }}</td>
                <td width="34%">FAX: {{ $proposal->ps2_fax ?? '' }}</td>
            </tr>
        </table>
        <table class="form-table">
            <tr>
                <td width="50%">E-MAIL: {{ $proposal->ps2_email ?? '' }}</td>
                <td width="25%">B.I. Nº: {{ $proposal->ps2_bi ?? '' }}</td>
                <td width="25%">N I F: {{ $proposal->ps2_nif ?? '' }}</td>
            </tr>
        </table>
        <table class="form-table">
            <tr>
                <td width="50%">PROFISSÃO: {{ $proposal->ps2_profissao ?? '' }}</td>
                <td width="50%">DATA DE NASCIMENTO:
                    {{ $proposal->ps2_data_nascimento ? \Carbon\Carbon::parse($proposal->ps2_data_nascimento)->format('d/m/Y') : '' }}
                </td>
            </tr>
        </table>
        <table class="form-table">
            <tr>
                <td width="35%">NACIONALIDADE: {{ $proposal->ps2_nacionalidade ?? '' }}</td>
                <td width="30%" style="text-align: center;">
                    SEXO: <strong>F</strong>
                    <span class="checkbox">
                        <img src="{{ public_path('img/cotacao/' . checkRadio($proposal->ps2_sexo ?? '', 'F')) }}"
                            style="height: 16px; vertical-align: middle;" alt="">
                    </span>
                    &nbsp; <strong>M</strong>
                    <span class="checkbox">
                        <img src="{{ public_path('img/cotacao/' . checkRadio($proposal->ps2_sexo ?? '', 'M')) }}"
                            style="height: 16px; vertical-align: middle;" alt="">
                    </span>
                </td>
                <td width="35%">ESTADO CIVIL: {{ $proposal->ps2_estado_civil ?? '' }}</td>
            </tr>
        </table>
    </div>

    {{-- ============================================================
         PÁGINA 2 — Declarações, Coberturas, Capitais
         ============================================================ --}}
    <div class="page">
        <div class="section-title">DECLARAÇÕES COMPLEMENTARES</div>
        <div style="font-size: 9pt; margin: 5px 0;">CASO SE VERIFIQUE ALGUMA DAS SEGUINTES SITUAÇÕES, ASSINALE A MESMA COM UM (X)</div>

        <div style="margin: 10px 0; font-size: 9pt;">
            <strong>1.</strong> SOFRE OU SOFREU DE ENFERMIDADES SUSCEPTÍVEIS DE DETERMINAR ACIDENTES, DE AGRAVAR AS SUAS CONSEQUÊNCIAS OU
            ATRASAR A CURA DAS LESÕES (POR EXEMPLO: REUMATISMO, PARALISIA, DIMINUIÇÃO DAS FACULDADES VISUAIS OU AUDITIVAS,
            VERTIGENS, SINCOPES, DIABETES, ALCOOLISMO, EPILEPSIA, DOENÇAS DA ESPINHA OU MEDULARES, DOENÇAS DO FORO PSÍQUICO):
        </div>
        <div style="margin: 5px 0; font-size: 9pt;">
            PESSOA SEGURA 1: NÃO
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkRadio($proposal->enfermidades_ps1 ?? '', 'nao')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span>
            &nbsp; SIM
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkRadio($proposal->enfermidades_ps1 ?? '', 'sim')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span>
            &nbsp;&nbsp;&nbsp;&nbsp;
            PESSOA SEGURA 2: NÃO
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkRadio($proposal->enfermidades_ps2 ?? '', 'nao')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span>
            &nbsp; SIM
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkRadio($proposal->enfermidades_ps2 ?? '', 'sim')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span>
        </div>
        <div style="margin: 5px 0;">SE SIM, QUAIS: {{ $proposal->enfermidades_quais ?? '' }}</div>

        <div style="margin: 10px 0;">
            <strong>2.</strong> FOI-LHE ATRIBUÍDO ALGUM GRAU DE INVALIDEZ?
            NÃO
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkRadio($proposal->invalidez ?? '', 'nao')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span>
            &nbsp; SIM
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkRadio($proposal->invalidez ?? '', 'sim')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span>
            &nbsp;&nbsp; ESPECIFIQUE: {{ $proposal->invalidez_grau ?? '' }}
        </div>

        <div style="margin: 10px 0;">
            <strong>3.</strong> É CANHOTO?
            NÃO
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkRadio($proposal->canhoto ?? '', 'nao')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span>
            &nbsp; SIM
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkRadio($proposal->canhoto ?? '', 'sim')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span>
        </div>

        <div style="margin: 10px 0;">
            <strong>4.</strong> PRATICA ALGUM DESPORTO?
            NÃO
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkRadio($proposal->desporto ?? '', 'nao')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span>
            &nbsp; SIM
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkRadio($proposal->desporto ?? '', 'sim')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span>
            &nbsp;&nbsp; QUAL? {{ $proposal->desporto_qual ?? '' }}
        </div>

        {{-- ÂMBITO DE COBERTURA --}}
        <div class="section-title">ÂMBITO DE COBERTURA</div>
        <div style="margin: 10px 0;">
            PROFISSIONAL
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkRadio($proposal->ambito_cobertura ?? '', 'profissional')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span>
            &nbsp;&nbsp;&nbsp;&nbsp;
            EXTRA-PROFISSIONAL
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkRadio($proposal->ambito_cobertura ?? '', 'extra')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span>
            &nbsp;&nbsp;&nbsp;&nbsp;
            PROFISSIONAL E EXTRA-PROFISSIONAL
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkRadio($proposal->ambito_cobertura ?? '', 'ambos')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span>
        </div>

        {{-- COBERTURAS / CAPITAIS --}}
        <div class="section-title">COBERTURAS</div>
        <table class="bordered-table">
            <tr>
                <th style="text-align: left; width: 60%;">COBERTURA</th>
                <th>CAPITAL A SEGURAR (Kz)</th>
            </tr>
            <tr>
                <td style="text-align: left;"><strong>MORTE</strong></td>
                <td class="text-center">{{ $proposal->cap_morte ? number_format($proposal->cap_morte, 2, ',', '.') : '' }}</td>
            </tr>
            <tr>
                <td style="text-align: left;"><strong>INVALIDEZ PERMANENTE</strong></td>
                <td class="text-center">{{ $proposal->cap_invalidez_permanente ? number_format($proposal->cap_invalidez_permanente, 2, ',', '.') : '' }}</td>
            </tr>
            <tr>
                <td style="text-align: left;"><strong>MORTE OU INVALIDEZ PERMANENTE</strong></td>
                <td class="text-center">{{ $proposal->cap_morte_invalidez ? number_format($proposal->cap_morte_invalidez, 2, ',', '.') : '' }}</td>
            </tr>
            <tr>
                <td style="text-align: left;"><strong>INCAPACIDADE TEMPORÁRIA</strong></td>
                <td class="text-center">{{ $proposal->cap_inc_temporaria ? number_format($proposal->cap_inc_temporaria, 2, ',', '.') : '' }}</td>
            </tr>
            <tr>
                <td style="text-align: left;"><strong>INCAPACIDADE TEMPORÁRIA SÓ EM CASO DE INTERNAMENTO HOSPITALAR</strong></td>
                <td class="text-center">{{ $proposal->cap_inc_temp_internamento ? number_format($proposal->cap_inc_temp_internamento, 2, ',', '.') : '' }}</td>
            </tr>
            <tr>
                <td style="text-align: left;"><strong>DESPESAS DE TRATAMENTO E REPATRIAMENTO</strong></td>
                <td class="text-center">{{ $proposal->cap_despesas_tratamento ? number_format($proposal->cap_despesas_tratamento, 2, ',', '.') : '' }}</td>
            </tr>
            <tr>
                <td style="text-align: left;"><strong>DESPESAS DE FUNERAL</strong></td>
                <td class="text-center">{{ $proposal->cap_despesas_funeral ? number_format($proposal->cap_despesas_funeral, 2, ',', '.') : '' }}</td>
            </tr>
        </table>

        {{-- OBSERVAÇÕES --}}
        <div class="section-title">OBSERVAÇÕES</div>
        <div style="margin: 5px 0; min-height: 60px; border: 1px solid #ccc; padding: 5px; font-size: 9pt;">
            {{ $proposal->observacoes ?? '' }}
        </div>

        {{-- SEGUROS EXISTENTES BIC --}}
        <div class="section-title">SEGUROS EXISTENTES NA BIC SEGUROS</div>
        <div style="margin: 10px 0;">
            É OU FOI CLIENTE DA BIC SEGUROS?
            &nbsp;&nbsp;NÃO
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkRadio($proposal->bic_cliente ?? '', 'nao')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span>
            &nbsp;&nbsp; SIM
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkRadio($proposal->bic_cliente ?? '', 'sim')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span>
            &nbsp;&nbsp; EM QUE PRODUTO(S)? {{ $proposal->bic_produtos ?? '' }}
        </div>

        {{-- CONTRATOS ANTERIORES --}}
        <div class="section-title">CONTRATOS ANTERIORES EM OUTRAS SEGURADORAS</div>
        <div style="margin: 10px 0;">
            O risco que pretende cobrir está ou esteve seguro, no seu todo ou em parte, noutra Seguradora?
            &nbsp;&nbsp;NÃO
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkRadio($proposal->anterior_segurado ?? '', 'nao')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span>
            &nbsp;&nbsp; SIM
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkRadio($proposal->anterior_segurado ?? '', 'sim')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span>
        </div>
        <div style="margin: 5px 0;">
            Se sim, indique qual a Seguradora? {{ $proposal->anterior_seguradora ?? '' }} &nbsp;&nbsp;
            Nº. Apólice: {{ $proposal->anterior_apolice_num ?? '' }} &nbsp;&nbsp;
            Prémio: {{ $proposal->anterior_premio ?? '' }}
        </div>

        {{-- PRÉMIOS EM DÍVIDA --}}
        <div class="section-title">PRÉMIOS EM DÍVIDA</div>
        <div style="margin: 10px 0;">
            Relativamente aos mesmos contratos de seguros, existem quaisquer débitos por liquidar à anterior Seguradora?
            &nbsp;&nbsp;NÃO
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkRadio($proposal->premios_divida ?? '', 'nao')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span>
            &nbsp;&nbsp; SIM
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkRadio($proposal->premios_divida ?? '', 'sim')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span>
        </div>
        <div style="margin: 5px 0;">Se sim, especifique: {{ $proposal->premios_divida_detalhes ?? '' }}</div>
    </div>

    {{-- ============================================================
         PÁGINA 3 — Declaração, Assinatura, Autorização Débito
         ============================================================ --}}
    <div class="page">
        <div class="section-title">DECLARAÇÃO</div>
        <div style="margin: 10px 0; text-align: justify; line-height: 1.6; font-size: 10px;">
            Para efeitos da celebração do presente contrato de seguro, declaro que:<br>
            a) Respondi completamente e com veracidade a todas as perguntas e verifiquei o preenchimento desta proposta.<br>
            b) São exactas e completas as declarações por mim prestadas e tomei conhecimento de todas as informações necessárias à celebração do presente contrato, tendo sido colocadas à minha disposição as respectivas Condições Gerais e Especiais, para delas tomar integral conhecimento, e prestados todos os esclarecimentos sobre as mesmas, nomeadamente sobre garantias e exclusões, com as quais estou de acordo.<br>
            c) Autorizo a consulta dos dados pessoais disponibilizados, sob regime de absoluta confidencialidade, pelas empresas que integram o grupo desde que compatível com a finalidade da recolha dos mesmos.<br>
            d) Autorizo a BIC Seguros, S.A a proceder à recolha de dados pessoais complementares junto de organismos públicos, empresas especializadas e outras unidades económicas com vista à confirmação ou complemento dos elementos recolhidos e necessários à gestão da relação contratual.
        </div>

        <div class="signature-area" style="margin-top: 20px; font-size: 10px; text-align:left ">
            Feito, em {{ $proposal->local_assinatura ?? '' }} a
            {{ $proposal->data_assinatura ? \Carbon\Carbon::parse($proposal->data_assinatura)->format('d') : '' }} de
            {{ $proposal->data_assinatura ? \Carbon\Carbon::parse($proposal->data_assinatura)->locale('pt')->format('F') : '' }} de
            {{ $proposal->data_assinatura ? \Carbon\Carbon::parse($proposal->data_assinatura)->format('Y') : '' }}<br><br><br>
        </div>

        <div class="signature-area" style="margin-top: 5px; text-align:right ; font-size: 10px;">
            ____________________________________ <br>
            <br>
            <br>
            (Assinatura do Tomador de Seguro)
            <br>
            <br>
        </div>

        {{-- AUTORIZAÇÃO DE DÉBITO --}}
        <div style="border: 1px solid #000; padding-left: 5px; padding-right: 5px;">
            <table style="margin: 10px 0; border-collapse: collapse; width: 100%; margin-bottom: 0px;">
                <tr>
                    <th colspan="6" style="font-size:12px; text-align: right; padding-top:10px; padding-bottom:10px;">
                        Autorização de Débito em Conta<br>
                    </th>
                </tr>
                <tr>
                    <td style="width: 15%; padding: 8px; font-size: 10px;">Banco</td>
                    <td style="width: 20%; background-color: #d0d0d0; padding: 8px; font-size: 10px;">
                        {{ $proposal->aut_banco ?? 'Banco BIC' }}
                    </td>
                    <td style="width: 15%; padding: 8px; font-size: 10px;">Agência</td>
                    <td style="width: 20%; background-color: #d0d0d0; padding: 8px; font-size: 10px;">
                        {{ $proposal->aut_agencia ?? '' }}
                    </td>
                    <td style="width: 15%; padding: 8px; font-size: 10px;">Data</td>
                    <td style="width: 15%; background-color: #d0d0d0; padding: 8px; font-size: 10px;">
                        {{ $proposal->aut_data ? \Carbon\Carbon::parse($proposal->aut_data)->format('d/m/Y') : '' }}
                    </td>
                </tr>
                <tr>
                    <td colspan="4" style="padding: 8px; font-size: 10px; font-weight: bold; vertical-align: top; border-right: 1px solid #000;">
                        <strong>Mutuário</strong>
                    </td>
                    <td colspan="2" style="padding: 8px; font-size: 10px; vertical-align: top;">Referência</td>
                </tr>
                <tr>
                    <td style="padding: 8px; font-size: 10px; vertical-align: top;">Nome</td>
                    <td colspan="3" style="background-color: #d0d0d0; padding: 8px; font-size: 10px; border-right: 1px solid #000;">
                        {{ $proposal->aut_mutuario_nome ?? $proposal->tomador_nome ?? '' }}
                    </td>
                    <td colspan="2" style="background-color: #d0d0d0; padding: 8px; font-size: 10px;">
                        {{ $proposal->aut_referencia ?? '' }}
                    </td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #000; font-size: 10px; vertical-align: top;">Morada</td>
                    <td colspan="3" style="background-color: #d0d0d0; padding: 8px; border-bottom: 1px solid #000; border-right: 1px solid #000; font-size: 10px;">
                        {{ $proposal->aut_mutuario_morada ?? $proposal->tomador_morada ?? '' }}
                    </td>
                </tr>
            </table>

            <div style="margin: 15px 0; margin-top: 0px; text-align: justify; font-size: 10px; line-height: 1.3; padding: 10px;">
                Venho pela presente autorizar V. Exas. a pagar os prémios de seguro emitidos pela BIC Seguros, ao abrigo
                do contrato de seguro em vigor, por débito directo da minha conta a seguir indicada:<br><br>

                <table style="border-collapse: collapse;">
                    <tr>
                        <td style="width: 50px; vertical-align: middle; font-size: 10px; font-weight: bold; background-color: #d0d0d0">
                            IBAN :
                        </td>
                        <td style="width: 20px; height: 20px; border: 1px solid #000; text-align: center; vertical-align: middle; font-size: 10px; background-color: #d0d0d0">A</td>
                        <td style="width: 20px; height: 20px; border: 1px solid #000; text-align: center; vertical-align: middle; font-size: 10px; background-color: #d0d0d0">O</td>
                        <td style="width: 20px; height: 20px; border: 1px solid #000; text-align: center; vertical-align: middle; font-size: 10px; background-color: #d0d0d0">0</td>
                        <td style="width: 20px; height: 20px; border: 1px solid #000; text-align: center; vertical-align: middle; font-size: 10px; background-color: #d0d0d0">6</td>
                        @php
                            $iban = $proposal->aut_iban ?? '';
                            $iban_clean = preg_replace('/[^A-Z0-9]/i', '', $iban);
                            // remove AO06 prefix if present
                            if (strpos(strtoupper($iban_clean), 'AO06') === 0) {
                                $iban_clean = substr($iban_clean, 4);
                            }
                            $iban_digits = str_split(str_pad($iban_clean, 21, ' '));
                        @endphp
                        @foreach(range(0, 20) as $i)
                            <td style="width: 20px; height: 20px; border: 1px solid #000; text-align: center; vertical-align: middle; font-size: 10px; background-color: #d0d0d0">
                                {{ $iban_digits[$i] ?? '' }}
                            </td>
                        @endforeach
                    </tr>
                </table>
            </div>

            <div style="padding-left: 8px; text-align: justify; font-size: 10px; line-height: 1.3;">
                Para o efeito, comprometo-me a manter a referida conta devidamente provisionada. Autorizo igualmente
                que nos casos de alteração do prémio de seguro, assim como do pagamento de moras, as referidas
                prestações sejam alteradas.
            </div>

            <div style="margin-top: 10px; font-size: 10px; padding-left: 8px;">
                Valor máximo de autorização:
                {{ number_format($proposal->aut_valor_maximo ?? 0, 2, ',', '.') }} Kz
            </div>

            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td colspan="4" style="padding-left: 8px; font-size: 10px;">Periodicidade dos pagamentos:</td>
                </tr>
                <tr>
                    <td style="width: 25%; text-align: center; padding: 8px; font-size: 10px;">
                        <span class="checkbox">
                            <img src="{{ public_path('img/cotacao/' . checkRadio($proposal->aut_periodicidade ?? '', 'anual')) }}"
                                style="height: 16px; vertical-align: middle;" alt="">
                        </span> ANUAL
                    </td>
                    <td style="width: 25%; text-align: center; padding: 8px; font-size: 10px;">
                        <span class="checkbox">
                            <img src="{{ public_path('img/cotacao/' . checkRadio($proposal->aut_periodicidade ?? '', 'semestral')) }}"
                                style="height: 16px; vertical-align: middle;" alt="">
                        </span> SEMESTRAL
                    </td>
                    <td style="width: 25%; text-align: center; padding: 8px; font-size: 10px;">
                        <span class="checkbox">
                            <img src="{{ public_path('img/cotacao/' . checkRadio($proposal->aut_periodicidade ?? '', 'trimestral')) }}"
                                style="height: 16px; vertical-align: middle;" alt="">
                        </span> TRIMESTRAL
                    </td>
                    <td style="width: 25%; text-align: center; padding: 8px; font-size: 10px;">
                        <span class="checkbox">
                            <img src="{{ public_path('img/cotacao/' . checkRadio($proposal->aut_periodicidade ?? '', 'mensal')) }}"
                                style="height: 16px; vertical-align: middle;" alt="">
                        </span> MENSAL
                    </td>
                </tr>
            </table>

            <div style="text-align: center; margin-top: 30px; font-size: 10px;">
                <br><br>
                ______________________________________________________________________________<br>
                (Assinatura de acordo com a ficha bancária)<br><br><br>
            </div>
        </div>
    </div>

</body>
</html>
