<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
</head>

<body>
    @php
        function p_checked_ref($current, $value) {
            return ($current == $value) ? 'check-prenchido.png' : 'check-proposta.png';
        }

        function p_date_ref($value) {
            if (!$value) {
                return '';
            }
            try {
                return \Carbon\Carbon::parse($value)->format('d/m/Y');
            } catch (\Throwable $e) {
                return (string) $value;
            }
        }

        function p_hour_ref($value) {
            if (!$value) {
                return '';
            }
            try {
                return \Carbon\Carbon::parse($value)->format('H');
            } catch (\Throwable $e) {
                return '';
            }
        }

        function p_minute_ref($value) {
            if (!$value) {
                return '';
            }
            try {
                return \Carbon\Carbon::parse($value)->format('i');
            } catch (\Throwable $e) {
                return '';
            }
        }

        // Função helper para corrigir campos JSON
        function fixJsonField($value)
        {
            if (is_null($value) || $value === '') {
                return [];
            }
            
            if (is_array($value)) {
                return $value;
            }
            
            if (is_string($value)) {
                // Remove aspas duplas extras
                $value = trim($value, '"');
                
                // Remove barras invertidas de escape
                $value = stripslashes($value);
                
                // Tenta decodificar JSON
                $decoded = json_decode($value, true);
                
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $decoded;
                }
            }
            
            return [];
        }

        // Corrige todos os campos JSON
        $tomadorCategorias = fixJsonField($proposal->tomador_categoria_carta);
        $condutorCategorias = fixJsonField($proposal->condutor_categoria_carta);
        $conheceuBic = fixJsonField($proposal->conheceu_bic);
        $preferenciaBic = fixJsonField($proposal->preferencia_bic);
    @endphp

    <!-- Página 1 -->
    <div class="page" style="position:relative; min-height:1500px; padding-bottom:120px;">
       
        <table class="header-grid" style="margin-bottom:4px;">
            <tr>
                <td width="27%" class="date" style="text-align:center; ">
                    <div class="small-box" style="padding-top:8px; padding-bottom:8px;">
                        <div style="font-weight:700; font-size:8.6pt;">Data de Entrada:</div>
                        <div style="margin-top:6px;">
                            <span class="inline-field" style="min-width:122px;">{{ p_date_ref($proposal->data_entrada ?? null) }}</span>
                        </div>
                        <div style="margin-top:10px; font-weight:700;">Ag. Produtor:</div>
                        <div style="margin-top:6px;">
                            <span class="inline-field" style="min-width:150px;">{{ $proposal->agente_produtor ?? '' }}</span>
                        </div>
                    </div>
                </td>
                <td width="48%" style="padding:8px 16px 0; ">
                    <div class="proposal-heading" style="font-size:12.8pt; line-height:1.25; text-align:center;">
                        PROPOSTA DE SEGURO<br>
                        AUTOMÓVEL
                    </div>
                    <div class="sub-options" style="margin-top:18px; font-size:8.2pt;">
                        <table style="width:100%; border-collapse:collapse;">
                            <tr>
                                <td  style="text-align:right;"  width="40%">SEGURO NOVO</td>
                                <td width="10%"><img src="{{ public_path('img/cotacao/' . p_checked_ref($proposal->tipo_proposta ?? '', 'novo')) }}" height="14" alt=""></td>
                                <td style="text-align:right;" width="40%">ALTERAÇÃO</td>
                                <td width="10%"><img src="{{ public_path('img/cotacao/' . p_checked_ref($proposal->tipo_proposta ?? '', 'alteracao')) }}" height="14" alt=""></td>
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
                            NÃO <img src="{{ public_path('img/cotacao/' . p_checked_ref($proposal->cliente_bic ?? '', 'nao')) }}" height="14" alt="">
                            &nbsp;&nbsp;
                            SIM <img src="{{ public_path('img/cotacao/' . p_checked_ref($proposal->cliente_bic ?? '', 'sim')) }}" height="14" alt="">
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        <div class="line-row" style="margin-top:10px;">
            INÍCIO CONTRATO <span class="inline-field" style="min-width:180px;">{{ p_date_ref($proposal->inicio_contrato ?? null) }}</span>
            &nbsp;&nbsp; ÀS <span class="inline-field" style="min-width:46px;">{{ p_hour_ref($proposal->hora_inicio ?? null) }}</span> H
            <span class="inline-field" style="min-width:46px;">{{ p_minute_ref($proposal->hora_inicio ?? null) }}</span> M
            &nbsp;&nbsp; FIM CONTRATO <span class="inline-field" style="min-width:170px;">{{ p_date_ref($proposal->fim_contrato ?? null) }}</span>
        </div>

        <div class="line-row">
            DURAÇÃO DO CONTRATO:
            <img src="{{ public_path('img/cotacao/' . p_checked_ref($proposal->duracao_contrato ?? '', 'anual')) }}" height="14" alt=""> ANO E SEGUINTES
            &nbsp;&nbsp;&nbsp;&nbsp;
            <img src="{{ public_path('img/cotacao/' . p_checked_ref($proposal->duracao_contrato ?? '', 'temporario')) }}" height="14" alt=""> TEMPORÁRIO (Mínimo 30 dias)
        </div>

        <div class="line-row">
            FORMA DE PAGAMENTO:
            <img src="{{ public_path('img/cotacao/' . p_checked_ref($proposal->forma_pagamento ?? '', 'anual')) }}" height="14" alt=""> ANUAL
            &nbsp;&nbsp;&nbsp;&nbsp;
            <img src="{{ public_path('img/cotacao/' . p_checked_ref($proposal->forma_pagamento ?? '', 'semestral')) }}" height="14" alt=""> SEMESTRAL
            &nbsp;&nbsp;&nbsp;&nbsp;
            <img src="{{ public_path('img/cotacao/' . p_checked_ref($proposal->forma_pagamento ?? '', 'trimestral')) }}" height="14" alt=""> TRIMESTRAL
            &nbsp;&nbsp;&nbsp;&nbsp;
            <img src="{{ public_path('img/cotacao/' . p_checked_ref($proposal->forma_pagamento ?? '', 'mensal')) }}" height="14" alt=""> MENSAL
            &nbsp;&nbsp;&nbsp;&nbsp;
            <img src="{{ public_path('img/cotacao/' . p_checked_ref($proposal->forma_pagamento ?? '', 'unico')) }}" height="14" alt=""> ÚNICO
        </div>

        <div class="section-bar">TOMADOR DE SEGURO <span class="light paren-text">(Segurado)</span></div>

        <div class="line-row">NOME/SIGLA: <span class="fill-line" style="width: 845px;">{{ $proposal->tomador_nome ?? '' }}</span></div>
        <div class="line-row">MORADA: <span class="fill-line" style="width: 885px;">{{ $proposal->tomador_morada ?? '' }}</span></div>
        <div class="line-row">
            MUNICÍPIO: <span class="fill-line" style="width: 295px;">{{ $proposal->tomador_municipio ?? '' }}</span>
            &nbsp;&nbsp; LOCALIDADE: <span class="fill-line" style="width: 270px;">{{ $proposal->tomador_localidade ?? '' }}</span>
            &nbsp;&nbsp; TELEMÓVEL: <span class="fill-line" style="width: 190px;">{{ $proposal->tomador_telemovel ?? '' }}</span>
        </div>
        <div class="line-row">
            FAX: <span class="fill-line" style="width: 250px;">{{ $proposal->tomador_fax ?? '' }}</span>
            &nbsp;&nbsp; E-MAIL: <span class="fill-line" style="width: 390px;">{{ $proposal->tomador_email ?? '' }}</span>
            &nbsp;&nbsp; N I F: <span class="fill-line" style="width: 175px;">{{ $proposal->tomador_nif ?? '' }}</span>
        </div>
        <div class="line-row">
            PROFISSÃO/ACTIVIDADE PREDOMINANTE: <span class="fill-line" style="width: 385px;">{{ $proposal->tomador_profissao ?? '' }}</span>
            &nbsp;&nbsp; DATA DE NASCIMENTO: <span class="fill-line" style="width: 190px;">{{ p_date_ref($proposal->tomador_data_nascimento ?? null) }}</span>
        </div>
        <div class="line-row">
            SEXO:
            <img src="{{ public_path('img/cotacao/' . p_checked_ref($proposal->tomador_sexo ?? '', 'F')) }}" height="14" alt=""> F
            &nbsp;
            <img src="{{ public_path('img/cotacao/' . p_checked_ref($proposal->tomador_sexo ?? '', 'M')) }}" height="14" alt=""> M
            &nbsp;&nbsp; NACIONALIDADE: <span class="fill-line" style="width: 300px;">{{ $proposal->tomador_nacionalidade ?? '' }}</span>
            &nbsp;&nbsp; ESTADO CIVIL: <span class="fill-line" style="width: 300px;">{{ $proposal->tomador_estado_civil ?? '' }}</span>
        </div>

        <table class="form-table">
            <tr>
                <td width="39%" style="padding-right:0px">N.º CARTA DE CONDUÇÃO: {{ $proposal->tomador_carta_conducao }}</td>
                <td width="28%">DATA DA CARTA:
                    {{ $proposal->tomador_data_carta ? \Carbon\Carbon::parse($proposal->tomador_data_carta)->format('d/m/Y') : '' }}
                </td>
                <td width="35%">
                    CATEGORIA: A <span class="checkbox">
                        <img src="{{ public_path('img/cotacao/' . (in_array("A", $tomadorCategorias, true) ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                            style="height: 16px; vertical-align: middle;" alt="">
                    </span>
                    &nbsp; B <span class="checkbox">
                        <img src="{{ public_path('img/cotacao/' . (in_array("B", $tomadorCategorias, true) ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                            style="height: 16px; vertical-align: middle;" alt="">
                    </span>
                    <span class="checkbox">
                        <img src="{{ public_path('img/cotacao/' . (in_array("C", $tomadorCategorias, true) ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                            style="height: 16px; vertical-align: middle;" alt="">C</span>
                    <span class="checkbox">
                        <img src="{{ public_path('img/cotacao/' . (in_array("PROF", $tomadorCategorias, true) ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                            style="height: 16px; vertical-align: middle;" alt="">PROF</span>
                </td>
            </tr>
        </table>

        <div style="margin: 5px 0;">PROVÍNCIA ONDE CIRCULA COM MAIS FREQUÊNCIA?
            {{ $proposal->tomador_provincia_frequencia }}
        </div>

        <div class="section-title">LOCAL DE COBRANÇA <span class="light paren-text">(Se diferente do Tomador de Seguro)</span></div>
        <div style="padding-right:0px">
            MORADA: {{ $proposal->local_cobranca_morada ?? '' }}
        </div>

        <table class="form-table">
            <tr>
                <td width="50%">MUNICÍPIO: {{ $proposal->local_cobranca_municipio ?? '' }}</td>
                <td width="50%">LOCALIDADE: {{ $proposal->local_cobranca_localidade ?? '' }}</td>
            </tr>
        </table>

        <div class="section-title">DÉBITO EM CONTA</div>
        <div style="margin: 5px 0;">Se preferir pagar por débito em conta por favor indique:</div>
        <div style="margin: 5px 0;">Banco: {{ $proposal->debito_banco ?? '' }} BAN/NIB {{ $proposal->debito_nib ?? '' }}</div>

        <div class="section-title">CONDUTOR HABITUAL <span class="light paren-text">(Preencher se for diferente do Tomador de Seguro)</span></div>
        <div style="margin: 5px 0;">
            <span>NOME/SIGLA: {{ $proposal->condutor_nome ?? '' }}</span>
        </div>
        <div style="margin: 5px 0;">
            MORADA: {{ $proposal->condutor_morada ?? '' }}<span class="field-line" style="min-width: 470px;"></span>
        </div>

        <table class="form-table">
            <tr>
                <td width="33%">MUNICÍPIO: {{ $proposal->condutor_municipio ?? '' }}</td>
                <td width="27%">LOCALIDADE: {{ $proposal->condutor_localidade ?? '' }}</td>
                <td width="17%">TELEMÓVEL: {{ $proposal->condutor_telemovel ?? '' }}</td>
                <td width="17%">FAX: {{ $proposal->condutor_fax ?? '' }}</td>
            </tr>
        </table>

        <table class="form-table">
            <tr>
                <td width="50%">E-MAIL: {{ $proposal->condutor_email ?? '' }}</td>
                <td width="50%">N I F: {{ $proposal->condutor_nif ?? '' }}</td>
            </tr>
        </table>

        <table class="form-table">
            <tr>
                <td width="67%">PROFISSÃO/ACTIVIDADE PREDOMINANTE: {{ $proposal->condutor_profissao ?? '' }}</td>
                <td width="33%">DATA DE NASCIMENTO:
                    {{ $proposal->condutor_data_nascimento ? \Carbon\Carbon::parse($proposal->condutor_data_nascimento)->format('d/m/Y') : '' }}
                </td>
            </tr>
        </table>

        <table class="form-table">
            <tr>
                <td width="30%">NACIONALIDADE: {{ $proposal->condutor_nacionalidade ?? '' }}</td>
                <td width="40%" style="text-align: center;">
                    SEXO: <strong>F</strong>
                    <span class="checkbox">
                        <img src="{{ public_path('img/cotacao/' . ($proposal->condutor_sexo == 'F' ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                            style="height: 16px; vertical-align: middle;" alt="">
                    </span>
                    &nbsp; <strong>M</strong>
                    <span class="checkbox">
                        <img src="{{ public_path('img/cotacao/' . ($proposal->condutor_sexo == 'M' ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                            style="height: 16px; vertical-align: middle;" alt="">
                    </span>
                </td>
                <td width="30%">ESTADO CIVIL: {{ $proposal->condutor_estado_civil ?? '' }}</td>
            </tr>
        </table>

        <table class="form-table">
            <tr>
                <td width="39%" style="padding-right:0px">N.º CARTA DE CONDUÇÃO: {{ $proposal->condutor_carta_conducao ?? '' }}</td>
                <td width="28%">DATA DA CARTA: {{ $proposal->condutor_data_carta ? \Carbon\Carbon::parse($proposal->condutor_data_carta)->format('d/m/Y') : '' }}</td>
                <td width="35%">
                    CATEGORIA: A <span class="checkbox">
                        <img src="{{ public_path('img/cotacao/' . (in_array('A', $condutorCategorias, true) ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                            style="height: 16px; vertical-align: middle;" alt="">
                    </span>
                    &nbsp; B <span class="checkbox">
                        <img src="{{ public_path('img/cotacao/' . (in_array('B', $condutorCategorias, true) ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                            style="height: 16px; vertical-align: middle;" alt="">
                    </span>
                    <span class="checkbox">
                        <img src="{{ public_path('img/cotacao/' . (in_array('C', $condutorCategorias, true) ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                            style="height: 16px; vertical-align: middle;" alt="">C</span>
                    <span class="checkbox">
                        <img src="{{ public_path('img/cotacao/' . (in_array('PROF', $condutorCategorias, true) ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                            style="height: 16px; vertical-align: middle;" alt="">PROF</span>
                </td>
            </tr>
        </table>

        <div style="margin: 5px 0;">PROVÍNCIA ONDE CIRCULA COM MAIS FREQUÊNCIA?
            {{ $proposal->condutor_provincia_frequencia ?? '' }}
        </div>

        <div style="margin: 10px 0;">LIGAÇÃO AO TOMADOR DE SEGURO: CÔNJUGE <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . ($proposal->ligacao_tomador == 'conjugue' ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span>
            &nbsp; &nbsp;FILHO (A) <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . ($proposal->ligacao_tomador == 'filho' ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span>
            &nbsp; &nbsp; PAI/MÃE <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . ($proposal->ligacao_tomador == 'pai_mae' ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span>
            &nbsp; &nbsp;EMPREGADO <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . ($proposal->ligacao_tomador == 'empregado' ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span>
            &nbsp; &nbsp;OUTRO <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . ($proposal->ligacao_tomador == 'outro' ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span>
        </div>
    </div>

    <!-- Página 2 -->
    <div class="page">
        <div class="section-title">QUALIDADE EM QUE FAZ O SEGURO</div>

        <div style="margin: 10px 0;">
            PROPRIETÁRIO <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . ($proposal->qualidade_seguro == 'proprietario' ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> &nbsp;&nbsp;&nbsp;&nbsp;
            LOCATÁRIO <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . ($proposal->qualidade_seguro == 'locatario' ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> &nbsp;&nbsp;&nbsp;&nbsp;
            USUFRUTUÁRIO <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . ($proposal->qualidade_seguro == 'usufrutuário' ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> &nbsp;&nbsp;&nbsp;&nbsp;
            RESERVA DE PROPRIEDADE - CREDORA E/OU LOCADORA <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . ($proposal->qualidade_seguro == 'reserva_propriedade' ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span>
        </div>

        <div class="section-title">ENTIDADE CREDORA E/OU LOCADORA</div>

        <div style="margin-top: 5px ;">NOME/SIGLA: {{ $proposal->entidade_credora_nome ?? '' }} AGÊNCIA
            {{ $proposal->entidade_credora_agencia ?? '' }}
        </div>
        <div style="margin-top: 5px ;">LOCALIDADE: {{ $proposal->entidade_credora_localidade ?? '' }} TELEM:
            {{ $proposal->entidade_credora_telem ?? '' }} E-MAIL: {{ $proposal->entidade_credora_email ?? '' }}
        </div>

        <div class="section-title">CARACTERÍSTICAS E USO DO VEÍCULO</div>

        <table class="form-table">
            <tr>
                <td width="33%">MARCA: {{ $proposal->veiculo_marca ?? '' }}</td>
                <td width="40%">MODELO/VERSÃO: {{ $proposal->veiculo_modelo ?? '' }}</td>
                <td width="27%">CILINDRADA: {{ $proposal->veiculo_cilindrada ?? '' }}</td>
            </tr>
        </table>

        <table class="form-table">
            <tr>
                <td width="20%">COR: {{ $proposal->veiculo_cor }}</td>
                <td width="20%">LOTAÇÃO: {{ $proposal->veiculo_lotacao }}</td>
                <td width="30%">MATRÍCULA: {{ $proposal->veiculo_matricula }}</td>
                <td width="30%" style="padding-right:0px;">PESO BRUTO: {{ $proposal->veiculo_peso_bruto }}</td>
            </tr>
        </table>

        <table class="form-table">
            <tr>
                <td width="40%">Nº CHASSIS: {{ $proposal->veiculo_chassis }}</td>
                <td width="30%" style="padding-right:0px;">Nº MOTOR: {{ $proposal->veiculo_motor }}</td>
                <td width="30%" style="padding-right:0px;">ANO DE FABRICO: {{ $proposal->veiculo_ano_fabrico }}</td>
            </tr>
        </table>

        <table class="form-table">
            <tr>
                <td width="15%" style="padding-right:0px;">Nº KM {{ $proposal->veiculo_km }}</td>
                <td width="15%" style="padding-right:0px;">Nº PORTAS: {{ $proposal->veiculo_portas }}</td>
                <td width="20%" style="padding-right:0px;">COMBUSTÍVEL: {{ $proposal->veiculo_combustivel }}</td>
                <td width="50%" style="padding-right:0px;">DATA DA 1.ª MATRÍCULA (Ano/Mês/Dia):
                    {{ $proposal->veiculo_data_matricula ? \Carbon\Carbon::parse($proposal->veiculo_data_matricula)->format('Y/m/d') : '' }}
                </td>
            </tr>
        </table>

        <div style="margin: 10px 0;">
            USO DO VEÍCULO:
            PARTICULAR <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . ($proposal->veiculo_uso == 'particular' ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> &nbsp;&nbsp;&nbsp;&nbsp;
            EMPRESA <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . ($proposal->veiculo_uso == 'empresa' ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> &nbsp;&nbsp;&nbsp;&nbsp;
            ALUGUER <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . ($proposal->veiculo_uso == 'aluguer' ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> &nbsp;&nbsp;&nbsp;&nbsp;
            TRANSPORTE DE MATÉRIAS PERIGOSAS <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . ($proposal->transporte_perigosas ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> &nbsp;&nbsp;&nbsp;&nbsp;
            SERVIÇO DE REBOQUE <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . ($proposal->servico_reboque ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span>
        </div>

        <div style="margin: 10px 0;">
            CATEGORIAS:<br>
            LIGEIRO <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . ($proposal->veiculo_categoria == 'ligeiro' ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> &nbsp;&nbsp;
            MISTO <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . ($proposal->veiculo_categoria == 'misto' ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> &nbsp;&nbsp;
            CAMIONETA <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . ($proposal->veiculo_categoria == 'camioneta' ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> &nbsp;&nbsp;
            CAMIÃO <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . ($proposal->veiculo_categoria == 'camiao' ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> &nbsp;&nbsp;
            AUTOCARRO <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . ($proposal->veiculo_categoria == 'autocararo' ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> &nbsp;&nbsp;
            TAXI <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . ($proposal->veiculo_categoria == 'taxi' ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> &nbsp;&nbsp;
            ARTICULADO <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . ($proposal->veiculo_categoria == 'articulado' ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> &nbsp;&nbsp;
            MOTOCICLO <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . ($proposal->veiculo_categoria == 'motociclo' ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span><br>
            CICLOMOTOR <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . ($proposal->veiculo_categoria == 'ciclomotor' ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> &nbsp;&nbsp;
            BICICLETA <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . ($proposal->veiculo_categoria == 'bicicleta' ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> &nbsp;&nbsp;
            LIGEIRO DE INSTRUÇÃO <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . ($proposal->veiculo_categoria == 'ligeiro_instrucao' ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> &nbsp;&nbsp;
            PESADO DE INSTRUÇÃO <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . ($proposal->veiculo_categoria == 'pesado_instrucao' ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> &nbsp;&nbsp;
            OUTRO, <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . ($proposal->veiculo_categoria == 'outro' ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> QUAL? {{ $proposal->veiculo_categoria_outro }}
        </div>

        <div class="section-title">GARANTIAS E SEUS LIMITES</div>

        <div style="font-weight: bold; margin: 5px 0;">RESPONSABILIDADE CIVIL (Danos materiais e corporais causados pelo Veículo Seguro):</div>

        <table class="bordered-table">
            <tr>
                <td class="text-center"><strong>152.000,00 UCF</strong><br>(13.376.000 Kz)<img
                        src="{{ public_path('img/cotacao/' . ($proposal->responsabilidade_civil == '152000' ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                        style="height: 16px;" alt=""></td>
                <td class="text-center"><strong>304.000,00 UCF</strong><br>(26.752.000 Kz)<img
                        src="{{ public_path('img/cotacao/' . ($proposal->responsabilidade_civil == '304000' ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                        style="height: 16px;" alt=""></td>
                <td class="text-center"><strong>456.000,00 UCF</strong><br>(40.128.000 Kz)<img
                        src="{{ public_path('img/cotacao/' . ($proposal->responsabilidade_civil == '456000' ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                        style="height: 16px;" alt=""></td>
                <td class="text-center"><strong>912.000,00 UCF</strong><br>(80.256.000 Kz)<img
                        src="{{ public_path('img/cotacao/' . ($proposal->responsabilidade_civil == '912000' ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                        style="height: 16px;" alt=""></td>
                <td class="text-center"><strong>1.520.000,00 UCF</strong><br>(133.760.000 Kz)<img
                        src="{{ public_path('img/cotacao/' . ($proposal->responsabilidade_civil == '1520000' ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                        style="height: 16px;" alt=""></td>
                <td class="text-center"><strong>76.000,00 UCF (MOTOCICLOS)</strong><br>(6.688.000 Kz)<img
                        src="{{ public_path('img/cotacao/' . ($proposal->responsabilidade_civil == '76000' ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                        style="height: 16px;" alt=""></td>
            </tr>
        </table>

        <div class="section-title">OUTRAS COBERTURAS COMPLEMENTARES</div>

        <div class="guarantee-options">
            <div><span class="checkbox">
                    <img src="{{ public_path('img/cotacao/' . ($proposal->cobertura_choque ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                        style="height: 16px; vertical-align: middle;" alt="">
                </span> <strong>CHOQUE, COLISÃO E CAPOTAMENTO</strong></div>
            <div><span class="checkbox">
                    <img src="{{ public_path('img/cotacao/' . ($proposal->cobertura_incendio ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                        style="height: 16px; vertical-align: middle;" alt="">
                </span> <strong>INCÊNDIO, RAIO E EXPLOSÃO</strong></div>
            <div><span class="checkbox">
                    <img src="{{ public_path('img/cotacao/' . ($proposal->cobertura_furto ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                        style="height: 16px; vertical-align: middle;" alt="">
                </span> <strong>FURTO OU ROUBO</strong></div>
            <div><span class="checkbox">
                    <img src="{{ public_path('img/cotacao/' . ($proposal->cobertura_acidentes ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                        style="height: 16px; vertical-align: middle;" alt="">
                </span> <strong>ACIDENTES PESSOAIS - OCUPANTES</strong></div>
        </div>

        <table class="bordered-table">
            <tr>
                <th></th>
                <th class="text-center">Morte ou Invalidez Permanente (MIP)</th>
                <th class="text-center">Despesas de Tratamento (DT)</th>
                <th class="text-center">Despesas de Funeral (DF)</th>
            </tr>
            <tr>
                <td><strong>Plano A</strong><img
                        src="{{ public_path('img/cotacao/' . ($proposal->plano_acidentes == 'A' ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                        style="height: 16px;" alt=""></td>
                <td class="text-center">250.000 AKZ</td>
                <td class="text-center">50.000 AKZ</td>
                <td class="text-center">50.000 AKZ</td>
            </tr>
            <tr>
                <td><strong>Plano B</strong><img
                        src="{{ public_path('img/cotacao/' . ($proposal->plano_acidentes == 'B' ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                        style="height: 16px;" alt=""></td>
                <td class="text-center">1.000.000 AKZ</td>
                <td class="text-center">200.000 AKZ</td>
                <td class="text-center">200.000 AKZ</td>
            </tr>
            <tr>
                <td><strong>Plano C</strong><img
                        src="{{ public_path('img/cotacao/' . ($proposal->plano_acidentes == 'C' ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                        style="height: 16px;" alt=""></td>
                <td class="text-center">5.500.000 AKZ</td>
                <td class="text-center">500.000 AKZ</td>
                <td class="text-center">500.000 AKZ</td>
            </tr>
        </table>

        <div class="section-title">FRANQUIAS DE DANOS PRÓPRIOS <span class="light paren-text">(Aplicável somente às coberturas Choque, Colisão e Capotamento e Incêndio, Raio e Explosão)</span>:</div>

        <div class="franquia-text">
            => Condutor com idade igual ou superior a 25 anos: Franquia de 5% do valor do sinistro, com o mínimo 25.000 AKZ<br>
            => Condutor com idade inferior 25 anos: Franquia de 7,5% do valor do sinistro, avec o mínimo de 35.000 AKZ
        </div>

        <div class="note-text">
            Nota: A franquia a aplicar em caso de sinistro incide sobre o valor da perda (valor do sinistro).
        </div>
    </div>
    <!-- Página 3 -->
    <div class="page">

        <div class="section-title">VISTORIA DO VEÍCULO SEGURO</div>

        <div style="margin: 10px 0;">Vistoria obrigatória para efeitos das coberturas de danos no veículo seguro, de acordo com a política de subscrição em vigor.</div>

        <div style="margin: 10px 0;">
            QUALIDADE EM QUE FAZ A VISTORIA: MEDIADOR/CORRETOR <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . ($proposal->qualidade_vistoria == 'mediador' ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> &nbsp;&nbsp;&nbsp;&nbsp;
            COLABORADOR <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . ($proposal->qualidade_vistoria == 'colaborador' ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> &nbsp;&nbsp;&nbsp;&nbsp;
            PERITO <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . ($proposal->qualidade_vistoria == 'perito' ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span>
        </div>

        <div style="margin: 10px 0;">
            DATA : {{ $proposal->data_vistoria ? \Carbon\Carbon::parse($proposal->data_vistoria)->format('d/m/Y') : '' }} HORA :
            {{ $proposal->hora_vistoria ? \Carbon\Carbon::parse($proposal->hora_vistoria)->format('H:i') : '' }} &nbsp;&nbsp;&nbsp;&nbsp;
            ASSINATURA {{ $proposal->assinatura_vistoria ?? '' }}
        </div>

        <div style="margin: 10px 0;">
            DECLARO QUE O VEÍCULO QUE SE PRETENDE SEGURAR FOI POR MIM VISTORIADO, TENDO VERIFICADO:
        </div>

        <div style="margin: 10px 0;">
            Nº. DE MOTOR <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . ($proposal->verificou_motor ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> &nbsp;&nbsp;&nbsp;&nbsp; Nº. DE QUADRO / CHASSIS <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . ($proposal->verificou_chassis ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> &nbsp;&nbsp;&nbsp;&nbsp; DOCUMENTAÇÃO DO VEÍCULO <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . ($proposal->verificou_documentacao ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span>
        </div>

        <div style="margin: 10px 0;">
            O VEÍCULO APRESENTA A SEGUINTE QUILOMETRAGEM {{ $proposal->quilometragem_vistoria ?? '' }} KMS
            &nbsp;&nbsp;&nbsp;&nbsp; O VEÍCULO NÃO APRESENTA DANOS <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . ($proposal->veiculo_sem_danos ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span>
        </div>

        <div style="margin: 5px 0;">
            O VEÍCULO APRESENTA OS SEGUINTES DANOS: {{ $proposal->danos_veiculo ?? '' }}
        </div>

        <div style="margin: 5px 0;">
            O VEÍCULO APRESENTA OS SEGUINTES EXTRAS: {{ $proposal->extras_veiculo ?? '' }}
        </div>

        <div class="section-title">DANOS PRÓPRIOS <span class="paren-text">(KZ)</span> <small class="paren-text">(De acordo com escolhido)</small></div>

        <table class="form-table">
            <tr>
                <td width="50%">VALOR DO VEÍCULO EM NOVO: {{ $proposal->valor_veiculo_novo }}</td>
                <td width="50%">VALOR VENAL DO VEÍCULO: {{ $proposal->valor_venal }}</td>
            </tr>
            <tr>
                <td>VALOR A SEGURAR: {{ $proposal->valor_segurar }}</td>
                <td>VALOR DE ACESSÓRIOS EXTRA A DECLARAR: {{ $proposal->valor_acessorios }}</td>
            </tr>
        </table>

        <div style="margin: 5px 0;">
            ACESSÓRIOS EXTRA A DECLARAR: {{ $proposal->acessorios_declarar }}
        </div>

        <div style="margin: 5px 0;">
            {{ $proposal->acessorios_extra_adicional ?? '' }}
        </div>

        <div class="section-title">SEGUROS EXISTENTES NA BIC SEGUROS</div>

        <div style="margin: 10px 0;">
            É OU FOI CLIENTE DA BIC SEGUROS? &nbsp;&nbsp;&nbsp;&nbsp;
            NÃO <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . ($proposal->bic_cliente == 'nao' ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> &nbsp;&nbsp;&nbsp;&nbsp;
            SIM <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . ($proposal->bic_cliente == 'sim' ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> &nbsp;&nbsp;&nbsp;&nbsp;
            EM QUE PRODUTO(S)? {{ $proposal->produtos_bic_seguros ?? '' }}
        </div>

        <div class="section-title">CONTRATOS ANTERIORES EM OUTRAS SEGURADORAS</div>

        <div style="margin: 10px 0;">
            O risco que pretende cobrir está ou esteve seguro, no seu todo ou em parte, noutra Seguradora?
            &nbsp;&nbsp;&nbsp;&nbsp;
            NÃO <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . ($proposal->anterior_segurado_outro == 'nao' ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> &nbsp;&nbsp;&nbsp;&nbsp;
            SIM <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . ($proposal->anterior_segurado_outro == 'sim' ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span>
        </div>

        <div style="margin: 5px 0;">
            Se sim, indique qual a Seguradora? {{ $proposal->seguradora_anterior ?? '' }} Nº. Apólice:
            {{ $proposal->apolice_anterior ?? '' }} Prémio {{ $proposal->premio_anterior ?? '' }}
        </div>

        <div style="margin: 5px 0;">
            A apólice está anulada? &nbsp;&nbsp;&nbsp;&nbsp;
            NÃO <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . ($proposal->anterior_apolice_anulada == 'nao' ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> &nbsp;&nbsp;&nbsp;&nbsp;
            SIM <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . ($proposal->anterior_apolice_anulada == 'sim' ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> &nbsp;&nbsp;&nbsp;&nbsp;
            Se sim qual o motivo? {{ $proposal->anterior_motivo_anulada ?? '' }}
        </div>

        <div style="margin: 5px 0;">
            Quantos sinistros automóvel teve nos últimos 2 anos? {{ $proposal->num_sinistros_2anos ?? '' }}
        </div>

        <div class="section-title">PRÉMIOS EM DÍVIDA</div>

        <div style="margin: 10px 0;">
            Relativamente aos mesmos contratos de seguros, existem quaisquer débitos por liquidar à anterior Seguradora?
            &nbsp;&nbsp;&nbsp;&nbsp;
            NÃO <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . ($proposal->premios_divida == 'nao' ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> &nbsp;&nbsp;&nbsp;&nbsp;
            SIM <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . ($proposal->premios_divida == 'sim' ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span>
        </div>

        <div style="margin: 5px 0;">
            Se sim, especifique: {{ $proposal->detalhes_debitos ?? '' }}
        </div>

        <div class="section-title">OUTRAS INFORMAÇÕES <span class="light paren-text">(Pode ser selecionada mais do que uma opção)</span></div>

        <div style="margin: 10px 0; font-size:9px">
            Como conheceu a BIC Seguros?<br>
            TV <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . (in_array('tv', $conheceuBic, true) ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> &nbsp;&nbsp;&nbsp;&nbsp;
            RÁDIO <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . (in_array('radio', $conheceuBic, true) ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> &nbsp;&nbsp;&nbsp;&nbsp;
            FOLHETOS <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . (in_array('folhetos', $conheceuBic, true) ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> &nbsp;&nbsp;&nbsp;&nbsp;
            ATRAVÉS DO BANCO BIC <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . (in_array('banco_bic', $conheceuBic, true) ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> &nbsp;&nbsp;&nbsp;&nbsp;
            ATRAVÉS DE UM CORRETOR/MEDIADOR <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . (in_array('corretor', $conheceuBic, true) ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> &nbsp;&nbsp;&nbsp;&nbsp;
            ATRAVÉS DE UM AMIGO <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . (in_array('amigo', $conheceuBic, true) ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span><br>
            OUTROS <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . (in_array('outros', $conheceuBic, true) ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> &nbsp;&nbsp;&nbsp;&nbsp;
            Quais? {{ $proposal->conheceu_outros ?? '' }}
        </div>

        <div style="margin: 10px 0; font-size:9px">
            A sua preferência pela BIC Seguros, deve-se a:<br>
            IMAGEM CORPORATIVA (BIC) <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . (in_array('imagem_corporativa', $preferenciaBic, true) ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> &nbsp;&nbsp;&nbsp;&nbsp;
            CONFIANÇA <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . (in_array('confianca', $preferenciaBic, true) ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> &nbsp;&nbsp;&nbsp;&nbsp;
            ATENDIMENTO <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . (in_array('atendimento', $preferenciaBic, true) ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> &nbsp;&nbsp;&nbsp;&nbsp;
            QUALIDADE DE SERVIÇO <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . (in_array('qualidade_servico', $preferenciaBic, true) ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> &nbsp;&nbsp;&nbsp;&nbsp;
            PRODUTOS <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . (in_array('produtos', $preferenciaBic, true) ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> &nbsp;&nbsp;&nbsp;&nbsp;
            COMODIDADE <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . (in_array('comodidade', $preferenciaBic, true) ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span><br>
            OUTROS <span class="checkbox">
                <img src="{{ public_path('img/cotacao/' . (in_array('outros', $preferenciaBic, true) ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                    style="height: 16px; vertical-align: middle;" alt="">
            </span> &nbsp;&nbsp;&nbsp;&nbsp;
            Quais? {{ $proposal->preferencia_outros ?? '' }}
        </div>
    </div>

    <!-- Página 4 -->
    <div class="page">
        <!-- DECLARAÇÃO -->
        <div class="section-title">DECLARAÇÃO</div>

        <div style="margin: 10px 0; text-align: justify; line-height: 1.8; font-size: 10px;">
            Para efeitos da celebração do presente contrato de seguro, declaro que:<br>
            a) Respondi completamente e com veracidade a todas as perguntas e verifiquei o preenchimento desta
            proposta.<br>
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
        <div class="signature-area" style="margin-top: 20px ; font-size: 10px; text-align:left ">
            Feito, em {{ $proposal->local_assinatura }} a
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

        <!-- Autorização de Débito em Conta -->
        <div style="border: 1px solid #000; padding-left: 5px; padding-right: 5px;">
            <table style="margin: 10px 0; border-collapse: collapse; width: 100%; margin-bottom:0px;">
                <tr>
                    <th colspan="6" style="font-size:12px; text-align: right; padding-top:10px; padding-bottom:10px;">
                        Autorização de Débito em Conta
                        <br>
                    </th>
                </tr>
                <tr>
                    <td style="width: 15%; padding: 8px; font-size: 10px;">
                        Banco
                    </td>
                    <td style="width: 20%; background-color: #d0d0d0; padding: 8px; font-size: 10px;">
                        {{ $proposal->autorizacao_banco ?? 'Banco BIC' }}
                    </td>
                    <td style="width: 15%; padding: 8px; font-size: 10px;">
                        Agência
                    </td>
                    <td style="width: 20%; background-color: #d0d0d0; padding: 8px; font-size: 10px;">
                        {{ $proposal->autorizacao_agencia ?? '' }}
                    </td>
                    <td style="width: 15%; padding: 8px; font-size: 10px;">
                        Data
                    </td>
                    <td style="width: 15%; background-color: #d0d0d0; padding: 8px; font-size: 10px;">
                        {{ $proposal->autorizacao_data ?? '' }}
                    </td>
                </tr>
                <tr>
                    <td colspan="4"
                        style="padding: 8px; font-size: 10px; font-weight: bold; vertical-align: top; border-right: 1px solid #000;">
                       <strong>Mutuário</strong>
                    </td>
                    <td colspan="2" style="padding: 8px; font-size: 10px; vertical-align: top;">
                      Referência
                    </td>
                </tr>
                <tr>
                    <td style="padding: 8px; font-size: 10px; vertical-align: top;">
                      Nome
                    </td>
                    <td colspan="3"
                        style="background-color: #d0d0d0; padding: 8px; font-size: 10px; border-right: 1px solid #000;">
                        {{ $proposal->autorizacao_mutuario_nome ?? $proposal->tomador_nome }}
                    </td>
                    <td colspan="2" style="background-color: #d0d0d0; padding: 8px; font-size: 10px;">
                        {{ $proposal->autorizacao_referencia ?? '' }}
                    </td>
                </tr>
                <tr>
                    <td
                        style="padding: 8px; border-bottom: 1px solid #000; font-size: 10px; vertical-align: top;">
                      Morada
                    </td>
                    <td colspan="3"
                        style="background-color: #d0d0d0; padding: 8px; border-bottom: 1px solid #000; border-right: 1px solid #000; font-size: 10px;">
                        {{ $proposal->autorizacao_mutuario_morada ?? $proposal->tomador_morada }}
                    </td>
                </tr>
            </table>

            <div
                style="margin:15px 0; margin-top:0px; text-align: justify; font-size: 10px; line-height: 1.3; padding: 10px;">

                Venho pela presente autorizar V. Exas. a pagar os prémios de seguro emitidos pela BIC Seguros, ao
                abrigo
                do contrato de seguro em vigor, por débito directo da minha conta a seguir indicada:<br><br>


                <table style="border-collapse: collapse;">
                    <tr>
                        <td
                            style="width: 50px; vertical-align: middle; font-size: 10px; font-weight: bold; background-color: #d0d0d0">
                            IBAN :
                        </td>
                        <td
                            style="width: 20px; height: 20px; border: 1px solid #000; text-align: center; vertical-align: middle; font-size: 10px; background-color: #d0d0d0">
                            A
                        </td>
                        <td
                            style="width: 20px; height: 20px; border: 1px solid #000; text-align: center; vertical-align: middle; font-size: 10px; background-color: #d0d0d0">
                            O
                        </td>
                        <td
                            style="width: 20px; height: 20px; border: 1px solid #000; text-align: center; vertical-align: middle; font-size: 10px; background-color: #d0d0d0">
                            0
                        </td>
                        <td
                            style="width: 20px; height: 20px; border: 1px solid #000; text-align: center; vertical-align: middle; font-size: 10px; background-color: #d0d0d0">
                            6
                        </td>
                        <?php
                        $iban = $proposal->autorizacao_iban ?? '';
                        $iban_digits = str_split(str_pad($iban, 21, ' '));
                        ?>
                        @foreach(range(0, 20) as $i)
                            <td
                                style="width: 20px; height: 20px; border: 1px solid #000; text-align: center; vertical-align: middle; font-size: 10px; background-color: #d0d0d0">
                                {{ $iban_digits[$i] ?? '' }}
                            </td>
                        @endforeach
                    </tr>
                </table>
            </div>

            <div style="padding-left: 8px; text-align: justify; font-size: 10px; line-height: 1.3;">

                Para o efeito, comprometo-me a manter a referida conta devidamente provisionada. Autorizo igualmente
                que
                nos casos de alteração do prémio de seguro, assim como do pagamento de moras, as referidas
                prestações
                sejam alteradas.

            </div>

            <div style="margin-top: 10px; font-size: 10px; padding-left: 8px;">
                Valor máximo de autorização:
                {{ number_format($proposal->autorizacao_valor_maximo ?? 0, 2, ',', '.') }} Kz
            </div>

            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td colspan="4" style="padding-left: 8px; font-size: 10px;">
                        Periodicidade dos pagamentos:
                    </td>
                </tr>
                <tr>
                    <td style="width: 10%; text-align: center; padding: 8px; font-size: 10px;">
                        <span class="checkbox">
                            <img src="{{ public_path('img/cotacao/' . ($proposal->autorizacao_periodicidade == 'anual' ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                                style="height: 16px; vertical-align: middle;" alt="">
                        </span> ANUAL
                    </td>
                    <td style="width: 25%; text-align: center; padding: 8px; font-size: 10px;">
                        <span class="checkbox">
                            <img src="{{ public_path('img/cotacao/' . ($proposal->autorizacao_periodicidade == 'semestral' ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                                style="height: 16px; vertical-align: middle;" alt="">
                        </span> SEMESTRAL
                    </td>
                    <td style="width: 25%; text-align: center; padding: 8px; font-size: 10px;">
                        <span class="checkbox">
                            <img src="{{ public_path('img/cotacao/' . ($proposal->autorizacao_periodicidade == 'trimestral' ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                                style="height: 16px; vertical-align: middle;" alt="">
                        </span> TRIMESTRAL
                    </td>
                    <td style="width: 25%; text-align: center; padding: 8px; font-size: 10px;">
                        <span class="checkbox">
                            <img src="{{ public_path('img/cotacao/' . ($proposal->autorizacao_periodicidade == 'mensal' ? 'check-prenchido.png' : 'check-proposta.png')) }}"
                                style="height: 16px; vertical-align: middle;" alt="">
                        </span> MENSAL
                    </td>
                </tr>
            </table>

            <div style="text-align: center; margin-top: 30px; font-size: 10px;">
                <br><br>
                ______________________________________________________________________________<br>
                (Assinatura de acordo com a ficha bancária)
                <br>
                <br>
                <br>
            </div>
        </div>
        <!-- RESERVADO AOS SERVIÇOS DA COMPANHIA -->
        <div class="section-title">RESERVADO AOS SERVIÇOS DA COMPANHIA</div>
        <br>
        <div style="font-size: 10px;">
            <hr><br>
            <hr><br>
            <hr><br>
            <hr><br>
            <hr><br>
        </div>
    </div>
</body>
</html>
