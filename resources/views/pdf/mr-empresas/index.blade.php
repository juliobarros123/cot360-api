<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
</head>

<body>

    @php
    function checkBoolMr($value) {
        return ($value === true || $value === 'true' || $value === 1 || $value === '1' || $value === 'sim')
            ? 'check-prenchido.png'
            : 'check-proposta.png';
    }
    function checkRadioMr($current, $value) {
        return ($current == $value) ? 'check-prenchido.png' : 'check-proposta.png';
    }
    function fmtKz($v) {
        return ($v !== null && $v !== '') ? number_format($v, 2, ',', '.') : '';
    }
    function mrDate($value) {
        if (!$value) return '';
        try {
            return \Carbon\Carbon::parse($value)->format('d/m/Y');
        } catch (\Throwable $e) {
            return '';
        }
    }
    function mrTime($value) {
        if (!$value) return '';
        try {
            return \Carbon\Carbon::parse($value)->format('H:i');
        } catch (\Throwable $e) {
            return '';
        }
    }
    function mrDay($value) {
        if (!$value) return '';
        try {
            return \Carbon\Carbon::parse($value)->format('d');
        } catch (\Throwable $e) {
            return '';
        }
    }
    function mrMonthPt($value) {
        if (!$value) return '';
        try {
            return \Carbon\Carbon::parse($value)->locale('pt')->format('F');
        } catch (\Throwable $e) {
            return '';
        }
    }
    function mrYear($value) {
        if (!$value) return '';
        try {
            return \Carbon\Carbon::parse($value)->format('Y');
        } catch (\Throwable $e) {
            return '';
        }
    }
    $branchCode = (int) data_get($proposal, 'quotationVersion.quotation.branch.code', 0);
    $isMrHabitacao = $branchCode === 32;
    $vinculadoRaw = data_get($proposal, 'seguro_vinculado');
    $isVinculado = in_array(strtolower((string) $vinculadoRaw), ['1', 'true', 'sim', 'yes', 'y', 'vinculado'], true)
        || $vinculadoRaw === true
        || strtolower((string) data_get($proposal, 'tipo_mrh', '')) === 'vinculado'
        || strtolower((string) data_get($proposal, 'modalidade_mrh', '')) === 'vinculado';
    $tituloProduto = $isMrHabitacao ? 'MULTI-RISCOS HABITAÇÃO' : 'MULTI-RISCOS EMPRESAS';
    @endphp

    {{-- ============================================================
         PÁGINA 1 — Dados Iniciais, Tomador, Segurado, Cobrança, Débito,
                    Qualidade, Hipoteca
         ============================================================ --}}
    <div class="page">
        <table class="header-grid" style="margin-bottom:4px;">
            <tr>
                <td width="27%" class="date" style="text-align:center;">
                    <div class="small-box" style="padding-top:8px; padding-bottom:8px;">
                        <div style="font-weight:700; font-size:8.6pt;">Data de Entrada:</div>
                        <div style="margin-top:6px;">
                            <span class="inline-field" style="min-width:122px;">{{ mrDate($proposal->data_entrada ?? null) }}</span>
                        </div>
                        <div style="margin-top:10px; font-weight:700;">Ag. Produtor:</div>
                        <div style="margin-top:6px;">
                            <span class="inline-field" style="min-width:150px;">{{ $proposal->agente_produtor ?? '' }}</span>
                        </div>
                    </div>
                </td>
                <td width="48%" style="padding:8px 16px 0;">
                    <div class="proposal-heading" style="font-size:12.8pt; line-height:1.25;">
                        PROPOSTA DE SEGURO<br>
                        {{ $tituloProduto }}
                    </div>
                    @if($isMrHabitacao && $isVinculado)
                        <div style="margin-top: 6px; font-weight: 700; color:#b00000; text-align:center;">SEGURO VINCULADO</div>
                    @endif
                    <div class="sub-options" style="margin-top:18px; font-size:8.2pt;">
                        <table style="width:100%; border-collapse:collapse;">
                            <tr>
                                <td style="text-align:right;" width="40%">SEGURO NOVO</td>
                                <td width="10%"><img src="{{ public_path('img/cotacao/' . checkRadioMr($proposal->tipo_proposta ?? '', 'novo')) }}" height="14" alt=""></td>
                                <td style="text-align:right;" width="40%">ALTERAÇÃO</td>
                                <td width="10%"><img src="{{ public_path('img/cotacao/' . checkRadioMr($proposal->tipo_proposta ?? '', 'alteracao')) }}" height="14" alt=""></td>
                            </tr>
                            <tr>
                                <td style="text-align:right;" width="40%">APÓLICE Nº <span>{{ $proposal->apolice_num ?? '-----' }}</span></td>
                            </tr>
                            <tr>
                                <td style="text-align:center;" width="40%"></td>
                                <td colspan="3"></td>
                            </tr>
                        </table>
                    </div>
                </td>
                @if(!$isVinculado)
                    <td width="25%" class="client">
                        <div class="small-box" style="padding-top:8px; padding-bottom:8px; color:#ff0000;">
                            <div style="font-weight:700; font-size:8.6pt; text-align:center;">É Cliente do Banco BIC?</div>
                            <div style="text-align:center; margin-top:22px; font-weight:700;">
                                NÃO <img src="{{ public_path('img/cotacao/' . checkRadioMr($proposal->cliente_bic ?? '', 'nao')) }}" height="14" alt="">
                                &nbsp;&nbsp;
                                SIM <img src="{{ public_path('img/cotacao/' . checkRadioMr($proposal->cliente_bic ?? '', 'sim')) }}" height="14" alt="">
                            </div>
                        </div>
                    </td>
                @else
                    <td width="25%"></td>
                @endif
            </tr>
        </table>

        <table class="form-table">
            <tr>
                <td width="40%">INÍCIO CONTRATO:
                    {{ mrDate($proposal->inicio_contrato ?? null) }}
                </td>
                <td width="20%">Horas:
                    {{ mrTime($proposal->hora_inicio ?? null) }}
                </td>
                <td width="40%">FIM CONTRATO:
                    {{ mrDate($proposal->fim_contrato ?? null) }}
                </td>
            </tr>
        </table>

        <div style="margin: 10px 0; font-weight:bold;">
            DURAÇÃO DO CONTRATO:
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkRadioMr($proposal->duracao_contrato ?? '', 'anual')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> ANO E SEGUINTES &nbsp;&nbsp;&nbsp;&nbsp;
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkRadioMr($proposal->duracao_contrato ?? '', 'temporario')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> TEMPORÁRIO
        </div>

        <div style="margin-bottom: 15px;">
            <strong>FORMA DE PAGAMENTO:</strong> &nbsp;
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkRadioMr($proposal->forma_pagamento ?? '', 'anual')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> ANUAL &nbsp;
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkRadioMr($proposal->forma_pagamento ?? '', 'semestral')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> SEMESTRAL &nbsp;
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkRadioMr($proposal->forma_pagamento ?? '', 'trimestral')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> TRIMESTRAL &nbsp;
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkRadioMr($proposal->forma_pagamento ?? '', 'mensal')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> MENSAL &nbsp;
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkRadioMr($proposal->forma_pagamento ?? '', 'unico')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> ÚNICO
        </div>

        {{-- TOMADOR --}}
        <div class="section-title">TOMADOR DE SEGURO</div>
        <div style="margin: 5px 0;">NOME / SIGLA: {{ $proposal->tomador_nome ?? '' }}</div>
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
                <td width="60%">E-MAIL: {{ $proposal->tomador_email ?? '' }}</td>
                <td width="40%">N I F: {{ $proposal->tomador_nif ?? '' }}</td>
            </tr>
        </table>
        <table class="form-table">
            <tr>
                <td width="65%">ACTIVIDADE PREDOMINANTE: {{ $proposal->tomador_actividade ?? '' }}</td>
                <td width="35%">CÓDIGO DA ACTIVIDADE: {{ $proposal->tomador_cod_actividade ?? '' }}</td>
            </tr>
        </table>

        {{-- SEGURADO --}}
        <div class="section-title">SEGURADO <span class="light paren-text">(Preencher se for diferente do Tomador de Seguro)</span></div>
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
                <td width="60%">E-MAIL: {{ $proposal->segurado_email ?? '' }}</td>
                <td width="40%">N I F: {{ $proposal->segurado_nif ?? '' }}</td>
            </tr>
        </table>
        <div style="margin: 5px 0;">ACTIVIDADE PREDOMINANTE: {{ $proposal->segurado_actividade ?? '' }}</div>

        {{-- LOCAL COBRANÇA --}}
        <div class="section-title">LOCAL DE COBRANÇA <span class="light paren-text">(Se diferente do Tomador de Seguro)</span></div>
        <div style="margin: 5px 0;">MORADA: {{ $proposal->local_cobranca_morada ?? '' }}</div>
        <table class="form-table">
            <tr>
                <td width="50%">MUNICÍPIO: {{ $proposal->local_cobranca_municipio ?? '' }}</td>
                <td width="50%">LOCALIDADE: {{ $proposal->local_cobranca_localidade ?? '' }}</td>
            </tr>
        </table>

        {{-- DÉBITO EM CONTA --}}
        @if(!$isVinculado)
            <div class="section-title">DÉBITO EM CONTA</div>
            <div style="margin: 5px 0;">Se preferir pagar por débito em conta por favor indique:</div>
            <div style="margin: 5px 0;">Banco: {{ $proposal->debito_banco ?? '' }} &nbsp;&nbsp;&nbsp; IBAN/NIB: {{ $proposal->debito_iban ?? '' }}</div>
        @endif

        {{-- QUALIDADE --}}
        <div class="section-title">QUALIDADE EM QUE SEGURA</div>
        <div style="margin: 10px 0;">
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkRadioMr($proposal->qualidade_seguro ?? '', 'proprietario')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> PROPRIETÁRIO &nbsp;&nbsp;
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkRadioMr($proposal->qualidade_seguro ?? '', 'usufrutuario')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> USUFRUTUÁRIO &nbsp;&nbsp;
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkRadioMr($proposal->qualidade_seguro ?? '', 'inquilino')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> INQUILINO &nbsp;&nbsp;
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkRadioMr($proposal->qualidade_seguro ?? '', 'outro')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> OUTRO: {{ $proposal->qualidade_outro_detalhes ?? '' }}
        </div>

        {{-- INTERESSE HIPOTECÁRIO --}}
        <div class="section-title">INTERESSE HIPOTECÁRIO / CREDOR / LEASING</div>
        <div style="margin: 5px 0;">ENTIDADE: {{ $isVinculado ? 'BANCO BIC SA' : ($proposal->hipoteca_entidade ?? '') }}</div>
        <div style="margin: 5px 0;">MORADA: {{ $proposal->hipoteca_morada ?? '' }}</div>
        <table class="form-table">
            <tr>
                <td width="25%">MUNICÍPIO: {{ $proposal->hipoteca_municipio ?? '' }}</td>
                <td width="25%">LOCALIDADE: {{ $proposal->hipoteca_localidade ?? '' }}</td>
                <td width="25%">CONTRATO Nº: {{ $proposal->hipoteca_contrato_num ?? '' }}</td>
                <td width="25%">VALOR: {{ fmtKz($proposal->hipoteca_valor ?? null) }} Kz</td>
            </tr>
        </table>
    </div>

    {{-- ============================================================
         PÁGINA 2 — Características do Risco, Meios de Protecção, Riscos
         ============================================================ --}}
    <div class="page">
        <div class="section-title">CARACTERÍSTICAS DO RISCO</div>

        <div style="margin: 10px 0;">
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkRadioMr($proposal->morada_risco ?? '', 'tomador')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> Na morada do Tomador do Seguro &nbsp;&nbsp;&nbsp;&nbsp;
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkRadioMr($proposal->morada_risco ?? '', 'outro')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> Outro
        </div>

        <div style="margin: 5px 0;">MORADA: {{ $proposal->risco_morada ?? '' }}</div>
        <table class="form-table">
            <tr>
                <td width="33%">MUNICÍPIO: {{ $proposal->risco_municipio ?? '' }}</td>
                <td width="33%">LOCALIDADE: {{ $proposal->risco_localidade ?? '' }}</td>
                <td width="34%">PROVÍNCIA: {{ $proposal->risco_provincia ?? '' }}</td>
            </tr>
        </table>
        <table class="form-table">
            <tr>
                <td width="50%">CONFRONTAÇÃO NORTE: {{ $proposal->confront_norte ?? '' }}</td>
                <td width="50%">SUL: {{ $proposal->confront_sul ?? '' }}</td>
            </tr>
            <tr>
                <td>NASCENTE: {{ $proposal->confront_nascente ?? '' }}</td>
                <td>POENTE: {{ $proposal->confront_poente ?? '' }}</td>
            </tr>
        </table>

        <div style="margin: 10px 0;">
            <strong>LOCALIZAÇÃO:</strong> &nbsp;
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkBoolMr($proposal->loc_zona_urbana ?? false)) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> ZONA URBANA &nbsp;
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkBoolMr($proposal->loc_zona_florestal ?? false)) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> ZONA FLORESTAL &nbsp;
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkBoolMr($proposal->loc_rc_cave ?? false)) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> R/C OU CAVE &nbsp;
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkBoolMr($proposal->loc_500m ?? false)) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> A MAIS DE 500M DE IMÓVEIS OCUPADOS
        </div>

        <p><strong>IMÓVEL (materiais de construção):</strong></p>
        <table class="form-table">
            <tr>
                <td width="50%">ESTRUTURA DO IMÓVEL: {{ $proposal->estrutura_imovel ?? '' }}</td>
                <td width="50%">PAREDES: {{ $proposal->paredes ?? '' }}</td>
            </tr>
            <tr>
                <td>ESTRUTURA DA COBERTURA: {{ $proposal->estrutura_cobertura ?? '' }}</td>
                <td>COBERTURA: {{ $proposal->cobertura ?? '' }}</td>
            </tr>
            <tr>
                <td>PORTAS: {{ $proposal->portas ?? '' }}</td>
                <td>JANELAS/MONTRAS: {{ $proposal->janelas_montras ?? '' }}</td>
            </tr>
        </table>

        <table class="form-table">
            <tr>
                <td width="40%">
                    ESCADAS:
                    <span class="checkbox">
                        <img src="{{ public_path('img/cotacao/' . checkRadioMr($proposal->escadas ?? '', 'exteriores')) }}"
                            style="height: 16px; vertical-align: middle;" alt="">
                    </span> EXTERIORES &nbsp;
                    <span class="checkbox">
                        <img src="{{ public_path('img/cotacao/' . checkRadioMr($proposal->escadas ?? '', 'interiores')) }}"
                            style="height: 16px; vertical-align: middle;" alt="">
                    </span> INTERIORES
                </td>
                <td width="60%">MATERIAL: {{ $proposal->escadas_material ?? '' }}</td>
            </tr>
        </table>

        <table class="form-table">
            <tr>
                <td width="25%">Nº TOTAL DE PISOS: {{ $proposal->num_pisos ?? '' }}</td>
                <td width="25%">PISOS ABAIXO DO SOLO: {{ $proposal->pisos_abaixo_solo ?? '' }}</td>
                <td width="25%">ÁREA TOTAL: {{ $proposal->area_total ?? '' }} m²</td>
                <td width="25%">ALTURA TOTAL: {{ $proposal->altura_total ?? '' }} m</td>
            </tr>
        </table>

        <table class="form-table">
            <tr>
                <td width="33%">ANO DE CONSTRUÇÃO: {{ $proposal->ano_construcao ?? '' }}</td>
                <td width="33%">
                    Líquidos inflamáveis (>500kg/L)?
                    <span class="checkbox">
                        <img src="{{ public_path('img/cotacao/' . checkRadioMr($proposal->liquidos_inflamaveis ?? '', 'nao')) }}"
                            style="height: 16px; vertical-align: middle;" alt="">
                    </span> NÃO &nbsp;
                    <span class="checkbox">
                        <img src="{{ public_path('img/cotacao/' . checkRadioMr($proposal->liquidos_inflamaveis ?? '', 'sim')) }}"
                            style="height: 16px; vertical-align: middle;" alt="">
                    </span> SIM
                </td>
                <td width="34%">
                    Explosivos?
                    <span class="checkbox">
                        <img src="{{ public_path('img/cotacao/' . checkRadioMr($proposal->explosivos ?? '', 'nao')) }}"
                            style="height: 16px; vertical-align: middle;" alt="">
                    </span> NÃO &nbsp;
                    <span class="checkbox">
                        <img src="{{ public_path('img/cotacao/' . checkRadioMr($proposal->explosivos ?? '', 'sim')) }}"
                            style="height: 16px; vertical-align: middle;" alt="">
                    </span> SIM
                </td>
            </tr>
        </table>
        <div style="font-size: 9pt; font-style: italic; margin: 5px 0;">
            Nota: Caso existam mais locais de risco, deverá ser preenchida uma proposta para cada local.
        </div>

        {{-- MEIOS DE PROTECÇÃO --}}
        <div class="section-title">MEIOS DE PROTECÇÃO E SEGURANÇA</div>
        <p><strong>Contra intrusão:</strong></p>
        <div style="margin: 5px 0;">
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkBoolMr($proposal->prot_alarme ?? false)) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> Detecção de intrusão (alarme) &nbsp;
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkBoolMr($proposal->prot_grades ?? false)) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> Grades &nbsp;
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkBoolMr($proposal->prot_cctv ?? false)) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> CCTV &nbsp;
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkBoolMr($proposal->prot_fechaduras ?? false)) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> Fechaduras de segurança &nbsp;
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkBoolMr($proposal->prot_porta_blindada ?? false)) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> Porta blindada &nbsp;
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkBoolMr($proposal->prot_guarda ?? false)) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> Guarda permanente
        </div>

        <p><strong>Contra incêndio:</strong></p>
        <table class="form-table">
            <tr>
                <td width="25%">
                    <span class="checkbox">
                        <img src="{{ public_path('img/cotacao/' . checkBoolMr($proposal->extintores ?? false)) }}"
                            style="height: 16px; vertical-align: middle;" alt="">
                    </span> Extintores portáteis
                </td>
                <td width="25%">Tipo: {{ $proposal->extintores_tipo ?? '' }}</td>
                <td width="25%">Quantidade: {{ $proposal->extintores_qtd ?? '' }}</td>
                <td width="25%">Revistos até:
                    {{ mrDate($proposal->extintores_revistos ?? null) }}
                </td>
            </tr>
        </table>
        <div style="margin: 5px 0;">
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkBoolMr($proposal->rede_incendio ?? false)) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> Rede de incêndio &nbsp;
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkBoolMr($proposal->sprinklers ?? false)) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> Sprinklers &nbsp;
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkBoolMr($proposal->deteccao_incendio ?? false)) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> Detecção de incêndio
        </div>

        <div style="margin: 10px 0;">
            <strong>Sistemas cobrem totalidade das instalações?</strong>
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkRadioMr($proposal->sistemas_totalidade ?? '', 'nao')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> NÃO &nbsp;
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkRadioMr($proposal->sistemas_totalidade ?? '', 'sim')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> SIM
        </div>

        <table class="form-table">
            <tr>
                <td width="33%">Capacidade depósito de água: {{ $proposal->deposito_capacidade ?? '' }} Lts</td>
                <td width="33%">
                    Reserva combate a incêndios?
                    <span class="checkbox">
                        <img src="{{ public_path('img/cotacao/' . checkRadioMr($proposal->reserva_combate ?? '', 'nao')) }}"
                            style="height: 16px; vertical-align: middle;" alt="">
                    </span> NÃO &nbsp;
                    <span class="checkbox">
                        <img src="{{ public_path('img/cotacao/' . checkRadioMr($proposal->reserva_combate ?? '', 'sim')) }}"
                            style="height: 16px; vertical-align: middle;" alt="">
                    </span> SIM
                </td>
                <td width="34%">Quantidade reserva: {{ $proposal->reserva_quantidade ?? '' }} Lts</td>
            </tr>
        </table>

        {{-- RISCOS COBERTOS --}}
        <div class="section-title">RISCOS COBERTOS</div>
        @if($isMrHabitacao && $isVinculado)
            @php
                $plano = strtolower((string) ($proposal->plano_cobertura ?? $proposal->plano ?? ''));
            @endphp
            <p><strong>Plano de Cobertura (Vinculado)</strong></p>
            <div style="font-size: 9pt; line-height: 1.8;">
                <span class="checkbox">
                    <img src="{{ public_path('img/cotacao/' . checkBoolMr(in_array($plano, ['base'], true))) }}"
                        style="height: 16px; vertical-align: middle;" alt="">
                </span> BASE (Edifício) &nbsp;&nbsp;
                <span class="checkbox">
                    <img src="{{ public_path('img/cotacao/' . checkBoolMr(in_array($plano, ['completo', 'complete'], true))) }}"
                        style="height: 16px; vertical-align: middle;" alt="">
                </span> COMPLETO (Edifício + Conteúdo)
            </div>
        @else
            <p><strong>Cobertura base</strong></p>
            <div style="font-size: 9pt; line-height: 1.8;">
                <span class="checkbox">
                    <img src="{{ public_path('img/cotacao/' . checkBoolMr($proposal->r_incendio ?? false)) }}"
                        style="height: 16px; vertical-align: middle;" alt="">
                </span> Incêndio, Queda de Raio e Explosão &nbsp;
                <span class="checkbox">
                    <img src="{{ public_path('img/cotacao/' . checkBoolMr($proposal->r_tempestades ?? false)) }}"
                        style="height: 16px; vertical-align: middle;" alt="">
                </span> Tempestades &nbsp;
                <span class="checkbox">
                    <img src="{{ public_path('img/cotacao/' . checkBoolMr($proposal->r_inundacoes ?? false)) }}"
                        style="height: 16px; vertical-align: middle;" alt="">
                </span> Inundações &nbsp;
                <span class="checkbox">
                    <img src="{{ public_path('img/cotacao/' . checkBoolMr($proposal->r_demolicao ?? false)) }}"
                        style="height: 16px; vertical-align: middle;" alt="">
                </span> Demolição/escombros &nbsp;
                <span class="checkbox">
                    <img src="{{ public_path('img/cotacao/' . checkBoolMr($proposal->r_aeronaves ?? false)) }}"
                        style="height: 16px; vertical-align: middle;" alt="">
                </span> Queda de aeronaves<br>
                <span class="checkbox">
                    <img src="{{ public_path('img/cotacao/' . checkBoolMr($proposal->r_choque ?? false)) }}"
                        style="height: 16px; vertical-align: middle;" alt="">
                </span> Choque ou impacto &nbsp;
                <span class="checkbox">
                    <img src="{{ public_path('img/cotacao/' . checkBoolMr($proposal->r_quebra_vidros ?? false)) }}"
                        style="height: 16px; vertical-align: middle;" alt="">
                </span> Quebra de vidros &nbsp;
                <span class="checkbox">
                    <img src="{{ public_path('img/cotacao/' . checkBoolMr($proposal->r_rc_proprietario ?? false)) }}"
                        style="height: 16px; vertical-align: middle;" alt="">
                </span> RC Proprietário/Inquilino/Ocupante &nbsp;
                <span class="checkbox">
                    <img src="{{ public_path('img/cotacao/' . checkBoolMr($proposal->r_antenas ?? false)) }}"
                        style="height: 16px; vertical-align: middle;" alt="">
                </span> Quebra/queda de antenas<br>
                <span class="checkbox">
                    <img src="{{ public_path('img/cotacao/' . checkBoolMr($proposal->r_paineis_solares ?? false)) }}"
                        style="height: 16px; vertical-align: middle;" alt="">
                </span> Quebra/queda painéis solares &nbsp;
                <span class="checkbox">
                    <img src="{{ public_path('img/cotacao/' . checkBoolMr($proposal->r_sismicos ?? false)) }}"
                        style="height: 16px; vertical-align: middle;" alt="">
                </span> Fenómenos sísmicos &nbsp;
                <span class="checkbox">
                    <img src="{{ public_path('img/cotacao/' . checkBoolMr($proposal->r_derrame_pci ?? false)) }}"
                        style="height: 16px; vertical-align: middle;" alt="">
                </span> Derrame de sistemas PCI
            </div>
        @endif

        @if(!$isMrHabitacao || !$isVinculado)
        <p><strong>Coberturas complementares</strong></p>
        <div style="font-size: 9pt; line-height: 1.8;">
            @php
                $coberturas = [
                    ['c_danos_agua', 'Danos por água'],
                    ['c_aluimento', 'Aluimento de terras'],
                    ['c_furto', 'Furto qualificado/Roubo'],
                    ['c_privacao', 'Privação temporária do uso'],
                    ['c_greves', 'Greves e tumultos'],
                    ['c_vandalismo', 'Actos de vandalismo'],
                    ['c_terrorismo', 'Terrorismo'],
                    ['c_extrav_fusao', 'Extravasamento materiais em fusão'],
                    ['c_derrame_oleo', 'Derrame de óleo'],
                    ['c_derrame', 'Derrame'],
                    ['c_riscos_electricos', 'Riscos eléctricos'],
                    ['c_reconst_docs', 'Reconstituição de documentos'],
                    ['c_danos_esteticos', 'Danos estéticos'],
                    ['c_combustao', 'Combustão espontânea'],
                    ['c_perda_rendas', 'Perda de rendas'],
                    ['c_sacrificio', 'Sacrifício em bens adjacentes'],
                    ['c_danos_empregados', 'Danos em bens de empregados'],
                    ['c_remocao_temp', 'Remoção temporária'],
                    ['c_fumos', 'Fumos, fuligem e cinzas'],
                    ['c_deter_refrig', 'Deterioração de bens refrigerados'],
                    ['c_dinheiro_caixa', 'Dinheiro em caixa'],
                    ['c_dinheiro_cofre', 'Dinheiro em cofre'],
                    ['c_dinheiro_transito', 'Dinheiro em trânsito'],
                    ['c_infidelidade', 'Infidelidade de funcionários'],
                    ['c_canalizacoes', 'Danos em canalizações subterrâneas'],
                    ['c_obras_arte', 'Danos em obras de arte'],
                    ['c_bens_ar_livre', 'Bens ao ar livre'],
                    ['c_fechaduras', 'Substituição de fechaduras'],
                    ['c_insuficiencia', 'Insuficiência de capital/omissões'],
                    ['c_valor_subst_novo', 'Valor substituição em novo'],
                    ['c_todos_riscos', 'Extensão Todos os Riscos'],
                    ['c_equip_electronico', 'Equipamento electrónico'],
                    ['c_avaria_maquinas', 'Avaria de máquinas'],
                    // MR Habitação não usa coberturas de exploração
                    ['c_danos_jardins', 'Danos em jardins'],
                    ['c_guarda_vigil', 'Despesas de guarda/vigilância'],
                    ['c_honorarios', 'Honorários de profissionais'],
                    ['c_transportes', 'Transportes'],
                    ['c_pesquisa', 'Pesquisa de avarias'],
                ];
                if (!$isMrHabitacao) {
                    $coberturas[] = ['c_perdas_exploracao', 'Perdas de exploração'];
                    $coberturas[] = ['c_rc_exploracao', 'RC de exploração'];
                }
            @endphp
            @foreach($coberturas as $i => $c)
                <span class="checkbox">
                    <img src="{{ public_path('img/cotacao/' . checkBoolMr($proposal->{$c[0]} ?? false)) }}"
                        style="height: 16px; vertical-align: middle;" alt="">
                </span> {{ $c[1] }}
                @if(($i + 1) % 4 == 0)<br>@else &nbsp;@endif
            @endforeach
        </div>
        @endif
    </div>

    {{-- ============================================================
         PÁGINA 3 — Valores a Segurar, Capitais Adicionais, Seguros Anteriores
         ============================================================ --}}
    <div class="page">
        <div class="section-title">VALORES A SEGURAR</div>
        <table class="bordered-table" style="font-size: 9pt;">
            <thead>
                <tr>
                    <th style="text-align: left;">Item</th>
                    <th>Capital (Kz)</th>
                    <th style="width: 100px;">Taxa</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="text-align: left;">Edifício</td>
                    <td>{{ fmtKz($proposal->cap_edificio ?? null) }}</td>
                    <td>{{ $proposal->taxa_edificio ?? '' }}</td>
                </tr>
                <tr>
                    <td style="text-align: left;">Benfeitorias</td>
                    <td>{{ fmtKz($proposal->cap_benfeitorias ?? null) }}</td>
                    <td>{{ $proposal->taxa_benfeitorias ?? '' }}</td>
                </tr>
                <tr style="background-color: #f0f0f0;">
                    <td style="text-align: left;"><strong>1 — Sub-Total Edifício</strong></td>
                    <td><strong>{{ fmtKz($proposal->subtotal_edificio ?? null) }}</strong></td>
                    <td></td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align: left; font-weight: bold; padding-top: 8px;">Conteúdo</td>
                </tr>
                <tr>
                    <td style="text-align: left;">- Mobiliário</td>
                    <td>{{ fmtKz($proposal->cap_mobiliario ?? null) }}</td>
                    <td>{{ $proposal->taxa_mobiliario ?? '' }}</td>
                </tr>
                <tr>
                    <td style="text-align: left;">- Máquinas e equipamentos</td>
                    <td>{{ fmtKz($proposal->cap_maquinas ?? null) }}</td>
                    <td>{{ $proposal->taxa_maquinas ?? '' }}</td>
                </tr>
                <tr>
                    <td style="text-align: left;">- Matérias-primas, produtos e mercadorias</td>
                    <td>{{ fmtKz($proposal->cap_materias_primas ?? null) }}</td>
                    <td>{{ $proposal->taxa_materias_primas ?? '' }}</td>
                </tr>
                <tr>
                    <td style="text-align: left;">- Material de embalagem, consumíveis e similares</td>
                    <td>{{ fmtKz($proposal->cap_embalagem ?? null) }}</td>
                    <td>{{ $proposal->taxa_embalagem ?? '' }}</td>
                </tr>
                <tr style="background-color: #f0f0f0;">
                    <td style="text-align: left;"><strong>2 — Sub-Total Conteúdo</strong></td>
                    <td><strong>{{ fmtKz($proposal->subtotal_conteudo ?? null) }}</strong></td>
                    <td></td>
                </tr>
                @if(!$isMrHabitacao)
                    <tr>
                        <td style="text-align: left;">3 — Perdas de exploração</td>
                        <td>{{ fmtKz($proposal->cap_perdas_exploracao ?? null) }}</td>
                        <td>{{ $proposal->taxa_perdas_exploracao ?? '' }}</td>
                    </tr>
                @endif
                <tr style="background-color: #f0f0f0;">
                    <td style="text-align: left;"><strong>{{ $isMrHabitacao ? 'Total Geral (1+2)' : 'Total Geral (1+2+3)' }}</strong></td>
                    <td><strong>{{ fmtKz($proposal->total_geral ?? null) }}</strong></td>
                    <td></td>
                </tr>
            </tbody>
        </table>

        <div style="margin: 10px 0;">
            <strong>ACTUALIZAÇÃO AUTOMÁTICA DO CAPITAL SEGURO:</strong>
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkRadioMr($proposal->actualizacao_automatica ?? '', 'nao')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> NÃO &nbsp;&nbsp;
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkRadioMr($proposal->actualizacao_automatica ?? '', 'sim')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> SIM &nbsp;&nbsp;
            {{ $proposal->actualizacao_percentagem ?? '' }} %
        </div>

        <h4 style="font-size: 11pt; margin-top: 14px;">Capitais por cobertura adicional</h4>
        <table class="bordered-table" style="font-size: 9pt;">
            <tbody>
                <tr><td style="text-align:left;">Riscos eléctricos</td><td>{{ fmtKz($proposal->cap_riscos_elec ?? null) }} Kz</td></tr>
                <tr><td style="text-align:left;">Equipamento electrónico</td><td>{{ fmtKz($proposal->cap_equip_elec ?? null) }} Kz</td></tr>
                <tr><td style="text-align:left;">Avaria de máquinas</td><td>{{ fmtKz($proposal->cap_avaria_maq ?? null) }} Kz</td></tr>
                <tr><td style="text-align:left;">Deterioração de bens refrigerados</td><td>{{ fmtKz($proposal->cap_deter_refrig ?? null) }} Kz</td></tr>
                <tr><td style="text-align:left;">Dinheiro em caixa</td><td>{{ fmtKz($proposal->cap_dinheiro_caixa ?? null) }} Kz</td></tr>
                <tr><td style="text-align:left;">Dinheiro em cofre</td><td>{{ fmtKz($proposal->cap_dinheiro_cofre ?? null) }} Kz</td></tr>
                <tr><td style="text-align:left;">Dinheiro em trânsito</td><td>{{ fmtKz($proposal->cap_dinheiro_transito ?? null) }} Kz</td></tr>
                <tr><td style="text-align:left;">Infidelidade de funcionários</td><td>{{ fmtKz($proposal->cap_infidelidade ?? null) }} Kz</td></tr>
                <tr><td style="text-align:left;">Derrame</td><td>{{ fmtKz($proposal->cap_derrame_val ?? null) }} Kz</td></tr>
                <tr><td style="text-align:left;">Danos em jardins</td><td>{{ fmtKz($proposal->cap_danos_jardins ?? null) }} Kz</td></tr>
                <tr><td style="text-align:left;">Insuficiência de capital e/ou omissões (até)</td><td>{{ fmtKz($proposal->cap_insuficiencia_val ?? null) }} Kz</td></tr>
                @if(!$isMrHabitacao)
                    <tr>
                        <td style="text-align:left;">RC de exploração — Volume anual de facturação: {{ fmtKz($proposal->rc_volume_facturacao ?? null) }} Kz</td>
                        <td>{{ fmtKz($proposal->cap_rc_exploracao ?? null) }} Kz</td>
                    </tr>
                @endif
                <tr><td style="text-align:left;">RC proprietário/inquilino/ocupante</td><td>5.000.000,00 Kz</td></tr>
            </tbody>
        </table>

        {{-- SEGUROS BIC --}}
        <div class="section-title">SEGUROS EXISTENTES NA BIC SEGUROS</div>
        <div style="margin: 10px 0;">
            É OU FOI CLIENTE DA BIC SEGUROS?
            &nbsp;&nbsp;NÃO
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkRadioMr($proposal->bic_cliente ?? '', 'nao')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span>
            &nbsp; SIM
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkRadioMr($proposal->bic_cliente ?? '', 'sim')) }}"
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
                <img src="{{ public_path('img/cotacao/' . checkRadioMr($proposal->anterior_segurado ?? '', 'nao')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span>
            &nbsp; SIM
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkRadioMr($proposal->anterior_segurado ?? '', 'sim')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span>
        </div>
        <div style="margin: 5px 0;">
            Se sim, indique qual a Seguradora? {{ $proposal->anterior_seguradora ?? '' }} &nbsp;&nbsp;
            Nº. Apólice: {{ $proposal->anterior_apolice_num ?? '' }} &nbsp;&nbsp;
            Prémio: {{ $proposal->anterior_premio ?? '' }}
        </div>
        <div style="margin: 5px 0;">
            A apólice está anulada?
            &nbsp;&nbsp;NÃO
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkRadioMr($proposal->apolice_anulada ?? '', 'nao')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span>
            &nbsp; SIM
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkRadioMr($proposal->apolice_anulada ?? '', 'sim')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span>
            &nbsp;&nbsp; Qual o motivo? {{ $proposal->apolice_anulada_motivo ?? '' }}
        </div>

        {{-- PRÉMIOS EM DÍVIDA --}}
        <div class="section-title">PRÉMIOS EM DÍVIDA</div>
        <div style="margin: 10px 0;">
            Existem débitos por liquidar à anterior Seguradora?
            &nbsp;&nbsp;NÃO
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkRadioMr($proposal->premios_divida ?? '', 'nao')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span>
            &nbsp; SIM
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkRadioMr($proposal->premios_divida ?? '', 'sim')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span>
        </div>
        <div style="margin: 5px 0;">Se sim, especifique: {{ $proposal->premios_divida_detalhes ?? '' }}</div>

        @if(!$isVinculado)
        {{-- OUTRAS INFORMAÇÕES --}}
        <div class="section-title">OUTRAS INFORMAÇÕES</div>
        <div style="margin: 10px 0; font-weight: bold;">Como conheceu a BIC Seguros?</div>
        <div style="margin: 5px 0; font-size: 9pt;">
            TV
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkBoolMr($proposal->conheceu_tv ?? false)) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> &nbsp;
            RÁDIO
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkBoolMr($proposal->conheceu_radio ?? false)) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> &nbsp;
            FOLHETOS
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkBoolMr($proposal->conheceu_folhetos ?? false)) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> &nbsp;
            BANCO BIC
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkBoolMr($proposal->conheceu_banco ?? false)) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> &nbsp;
            CORRETOR/MEDIADOR
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkBoolMr($proposal->conheceu_corretor ?? false)) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> &nbsp;
            AMIGO
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkBoolMr($proposal->conheceu_amigo ?? false)) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span><br>
            OUTROS
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkBoolMr($proposal->conheceu_outros ?? false)) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> &nbsp;
            Quais? {{ $proposal->conheceu_outros_detalhes ?? '' }}
        </div>

        <div style="margin: 10px 0; font-weight: bold;">A sua preferência pela BIC Seguros, deve-se a:</div>
        <div style="margin: 5px 0; font-size: 9pt;">
            IMAGEM CORPORATIVA
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkBoolMr($proposal->pref_imagem ?? false)) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> &nbsp;
            CONFIANÇA
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkBoolMr($proposal->pref_confianca ?? false)) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> &nbsp;
            ATENDIMENTO
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkBoolMr($proposal->pref_atendimento ?? false)) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> &nbsp;
            QUALIDADE
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkBoolMr($proposal->pref_qualidade ?? false)) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> &nbsp;
            PRODUTOS
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkBoolMr($proposal->pref_produtos ?? false)) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> &nbsp;
            COMODIDADE
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkBoolMr($proposal->pref_comodidade ?? false)) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span><br>
            OUTROS
            <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . checkBoolMr($proposal->pref_outros ?? false)) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> &nbsp;
            Quais? {{ $proposal->pref_outros_detalhes ?? '' }}
        </div>
        @endif
    </div>

    {{-- ============================================================
         PÁGINA 4 — Observações, Declaração, Assinatura, Autorização Débito
         ============================================================ --}}
    <div class="page">
        <div class="section-title">OBSERVAÇÕES</div>
        <div style="margin: 5px 0; min-height: 80px; border: 1px solid #ccc; padding: 5px; font-size: 9pt;">
            {{ $proposal->observacoes ?? '' }}
        </div>

        <div class="section-title">DECLARAÇÃO</div>
        <div style="margin: 10px 0; text-align: justify; line-height: 1.6; font-size: 10px;">
            Para efeitos da celebração do presente contrato de seguro, declaro que:<br>
            a) Respondi completamente e com veracidade a todas as perguntas e verifiquei o preenchimento desta proposta.<br>
            b) São exactas e completas as declarações por mim prestadas e tomei conhecimento de todas as informações
            necessárias à celebração do presente contrato, tendo sido colocadas à minha disposição as respectivas
            Condições Gerais e Especiais, para delas tomar integral conhecimento, e prestados todos os esclarecimentos
            sobre as mesmas, nomeadamente sobre garantias e exclusões, com as quais estou de acordo.<br>
            c) Autorizo a consulta dos dados pessoais disponibilizados, sob regime de absoluta confidencialidade, pelas
            empresas que integram o grupo desde que compatível com a finalidade da recolha dos mesmos.<br>
            d) Autorizo a BIC Seguros, S.A a proceder à recolha de dados pessoais complementares junto de organismos
            públicos, empresas especializadas e outras unidades económicas com vista à confirmação ou complemento dos
            elementos recolhidos e necessários à gestão da relação contratual.
        </div>

        <div class="signature-area" style="margin-top: 20px; font-size: 10px; text-align: left;">
            Feito em {{ $proposal->local_assinatura ?? '' }} a
            {{ mrDay($proposal->data_assinatura ?? null) }} de
            {{ mrMonthPt($proposal->data_assinatura ?? null) }} de
            {{ mrYear($proposal->data_assinatura ?? null) }}<br><br><br>
        </div>

        <div class="signature-area" style="margin-top: 5px; text-align: right; font-size: 10px;">
            ____________________________________ <br><br><br>
            (Assinatura do Tomador de Seguro)<br><br><br>
        </div>

        {{-- AUTORIZAÇÃO DÉBITO --}}
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
                        {{ mrDate($proposal->aut_data ?? null) }}
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
                        <td style="width: 50px; vertical-align: middle; font-size: 10px; font-weight: bold; background-color: #d0d0d0">IBAN :</td>
                        <td style="width: 20px; height: 20px; border: 1px solid #000; text-align: center; vertical-align: middle; font-size: 10px; background-color: #d0d0d0">A</td>
                        <td style="width: 20px; height: 20px; border: 1px solid #000; text-align: center; vertical-align: middle; font-size: 10px; background-color: #d0d0d0">O</td>
                        <td style="width: 20px; height: 20px; border: 1px solid #000; text-align: center; vertical-align: middle; font-size: 10px; background-color: #d0d0d0">0</td>
                        <td style="width: 20px; height: 20px; border: 1px solid #000; text-align: center; vertical-align: middle; font-size: 10px; background-color: #d0d0d0">6</td>
                        @php
                            $iban = $proposal->aut_iban ?? '';
                            $iban_clean = preg_replace('/[^A-Z0-9]/i', '', $iban);
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
                            <img src="{{ public_path('img/cotacao/' . checkRadioMr($proposal->aut_periodicidade ?? '', 'anual')) }}"
                                style="height: 16px; vertical-align: middle;" alt="">
                        </span> ANUAL
                    </td>
                    <td style="width: 25%; text-align: center; padding: 8px; font-size: 10px;">
                        <span class="checkbox">
                            <img src="{{ public_path('img/cotacao/' . checkRadioMr($proposal->aut_periodicidade ?? '', 'semestral')) }}"
                                style="height: 16px; vertical-align: middle;" alt="">
                        </span> SEMESTRAL
                    </td>
                    <td style="width: 25%; text-align: center; padding: 8px; font-size: 10px;">
                        <span class="checkbox">
                            <img src="{{ public_path('img/cotacao/' . checkRadioMr($proposal->aut_periodicidade ?? '', 'trimestral')) }}"
                                style="height: 16px; vertical-align: middle;" alt="">
                        </span> TRIMESTRAL
                    </td>
                    <td style="width: 25%; text-align: center; padding: 8px; font-size: 10px;">
                        <span class="checkbox">
                            <img src="{{ public_path('img/cotacao/' . checkRadioMr($proposal->aut_periodicidade ?? '', 'mensal')) }}"
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
