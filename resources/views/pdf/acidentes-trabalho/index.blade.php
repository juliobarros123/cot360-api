<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
</head>
<body>
    @php
        function at_checked_ref($current, $value) {
            return ($current == $value) ? 'check-prenchido.png' : 'check-proposta.png';
        }

        function at_bool_ref($value) {
            return ($value === true || $value === 'true' || $value === 1 || $value === '1' || $value === 'sim')
                ? 'check-prenchido.png'
                : 'check-proposta.png';
        }

        function at_date_ref($value) {
            if (!$value) {
                return '';
            }

            try {
                return \Carbon\Carbon::parse($value)->format('d/m/Y');
            } catch (\Throwable $e) {
                return (string) $value;
            }
        }

        function at_hour_ref($value) {
            if (!$value) {
                return '';
            }

            try {
                return \Carbon\Carbon::parse($value)->format('H');
            } catch (\Throwable $e) {
                return '';
            }
        }

        function at_minute_ref($value) {
            if (!$value) {
                return '';
            }

            try {
                return \Carbon\Carbon::parse($value)->format('i');
            } catch (\Throwable $e) {
                return '';
            }
        }

        function at_footer($page) {
            return '';
        }

        $iban = $proposal->aut_iban ?? $proposal->debito_iban ?? '';
        $ibanClean = preg_replace('/[^A-Z0-9]/i', '', $iban);
        if (strpos(strtoupper($ibanClean), 'AO06') === 0) {
            $ibanClean = substr($ibanClean, 4);
        }
        $ibanDigits = str_split(str_pad($ibanClean, 21, ' '));

        // Lista de pessoas a segurar (pode vir como JSON ou array)
        $pessoasSeguras = $proposal->pessoas ?? [];
        if (is_string($pessoasSeguras)) {
            $decoded = json_decode($pessoasSeguras, true);
            $pessoasSeguras = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
        }
        if (!is_array($pessoasSeguras)) $pessoasSeguras = [];
    @endphp

    {{-- ============================================================
         PÁGINA 1 — Cabeçalho, Contrato, Tomador, Local Cobrança, Débito, Informações Gerais
         ============================================================ --}}
    <div  style="position:relative; min-height:1500px; ">
    
        <table class="header-grid" style="margin-bottom:4px;">
            <tr>
                <td width="27%" class="date" style="text-align:center;">
                    <div class="small-box" style="padding-top:8px; padding-bottom:8px;">
                        <div style="font-weight:700; font-size:8.6pt;">Data de Entrada:</div>
                        <div style="margin-top:6px;">
                            <span class="inline-field" style="min-width:122px;">{{ at_date_ref($proposal->data_entrada ?? null) }}</span>
                        </div>
                        <div style="margin-top:10px; font-weight:700;">Ag. Produtor:</div>
                        <div style="margin-top:6px;">
                            <span class="inline-field" style="min-width:150px;">{{ $proposal->agente_produtor ?? '' }}</span>
                        </div>
                    </div>
                </td>
                <td width="48%" style="padding:8px 16px 0;">
                    <div class="proposal-heading" style="font-size:12.8pt; line-height:1.25;">
                        PROPOSTA<br>
                        ACIDENTES DE TRABALHO
                    </div>
                    <div class="sub-options" style="margin-top:18px; font-size:8.2pt;">
                        <table style="width:100%; border-collapse:collapse;" >
                            <tr style="">
                                <td style="text-align:right;" width="20%" >SEGURO NOVO</td>
                                <td   style="text-align:left" width="30%" ><img src="{{ public_path('img/cotacao/' . at_checked_ref($proposal->tipo_proposta ?? '', 'novo')) }}" height="14" alt=""></td>
                                
                            </tr>
                             <tr >
                                <td style="text-align:right;" width="20%" >ALTERAÇÃO</td>
                                <td   style="text-align:left" width="30%" ><img src="{{ public_path('img/cotacao/' . at_checked_ref($proposal->tipo_proposta ?? '', 'novo')) }}" height="14" alt=""></td>
                            </tr>
                            {{-- <tr>
                                <td style="text-align:right;width:70%" >ALTERAÇÃO</td>
                                <td ><img src="{{ public_path('img/cotacao/' . at_checked_ref($proposal->tipo_proposta ?? '', 'alteracao')) }}" height="14" alt=""></td>
                            </tr> --}}
                          
                            {{-- <tr>
                                <td style="text-align:center;" width="40%">&nbsp;</td>
                                <td colspan="3"></td>
                            </tr> --}}
                        </table>
                    </div>
                </td>
                <td width="25%" class="client">
                    <div class="small-box" style="padding-top:8px; padding-bottom:8px; color:#ff0000;">
                        <div style="font-weight:700; font-size:8.6pt; text-align:center;">É Cliente do Banco BIC?</div>
                        <div style="text-align:center; margin-top:22px; font-weight:700;">
                            NÃO <img src="{{ public_path('img/cotacao/' . at_checked_ref($proposal->cliente_bic ?? '', 'nao')) }}" height="14" alt="">
                            &nbsp;&nbsp;
                            SIM <img src="{{ public_path('img/cotacao/' . at_checked_ref($proposal->cliente_bic ?? '', 'sim')) }}" height="14" alt="">
                        </div>
                    </div>
                </td>
            </tr>
        </table>
<br>
<br>
        <div class="line-row" style="margin-top:10px;">
            INÍCIO CONTRATO <span class="inline-field" style="min-width:180px;">{{ at_date_ref($proposal->inicio_contrato ?? null) }}</span>
            &nbsp;&nbsp; ÀS <span class="inline-field" style="min-width:46px;">{{ at_hour_ref($proposal->hora_inicio ?? null) }}</span> H
            <span class="inline-field" style="min-width:46px;">{{ at_minute_ref($proposal->hora_inicio ?? null) }}</span> M
            &nbsp;&nbsp; FIM CONTRATO <span class="inline-field" style="min-width:170px;">{{ at_date_ref($proposal->fim_contrato ?? null) }}</span>
        </div>

        <div class="line-row">
            DURAÇÃO DO CONTRATO:
            <img src="{{ public_path('img/cotacao/' . at_checked_ref($proposal->duracao_contrato ?? '', 'anual')) }}" height="14" alt=""> ANO E SEGUINTES
            &nbsp;&nbsp;&nbsp;&nbsp;
            <img src="{{ public_path('img/cotacao/' . at_checked_ref($proposal->duracao_contrato ?? '', 'temporario')) }}" height="14" alt=""> TEMPORÁRIO (Mínimo 30 dias)
        </div>

        <div class="line-row">
            FORMA DE PAGAMENTO:
            <img src="{{ public_path('img/cotacao/' . at_checked_ref($proposal->forma_pagamento ?? '', 'anual')) }}" height="14" alt=""> ANUAL
            &nbsp;&nbsp;&nbsp;&nbsp;
            <img src="{{ public_path('img/cotacao/' . at_checked_ref($proposal->forma_pagamento ?? '', 'semestral')) }}" height="14" alt=""> SEMESTRAL
            &nbsp;&nbsp;&nbsp;&nbsp;
            <img src="{{ public_path('img/cotacao/' . at_checked_ref($proposal->forma_pagamento ?? '', 'trimestral')) }}" height="14" alt=""> TRIMESTRAL
            &nbsp;&nbsp;&nbsp;&nbsp;
            <img src="{{ public_path('img/cotacao/' . at_checked_ref($proposal->forma_pagamento ?? '', 'mensal')) }}" height="14" alt=""> MENSAL
            &nbsp;&nbsp;&nbsp;&nbsp;
            <img src="{{ public_path('img/cotacao/' . at_checked_ref($proposal->forma_pagamento ?? '', 'unico')) }}" height="14" alt=""> ÚNICO
        </div>

        <div class="section-bar">TOMADOR DE SEGURO <span class="light paren-text">(Segurado)</span></div>
        <div class="line-row">NOME / SIGLA: <span class="fill-line" style="width: 850px;">{{ $proposal->tomador_nome ?? '' }}</span></div>
        <div class="line-row">
            MORADA: <span class="fill-line" style="width: 540px;">{{ $proposal->tomador_morada ?? '' }}</span>
            &nbsp;&nbsp; MUNICÍPIO: <span class="fill-line" style="width: 250px;">{{ $proposal->tomador_municipio ?? '' }}</span>
        </div>
        <div class="line-row">
            LOCALIDADE: <span class="fill-line" style="width: 380px;">{{ $proposal->tomador_localidade ?? '' }}</span>
            &nbsp;&nbsp; TELEFONE / TELEMÓVEL: <span class="fill-line" style="width: 350px;">{{ $proposal->tomador_telemovel ?? $proposal->tomador_telefone ?? '' }}</span>
        </div>
        <div class="line-row">
            NIF: <span class="fill-line" style="width: 380px;">{{ $proposal->tomador_nif ?? '' }}</span>
            &nbsp;&nbsp; E-MAIL: <span class="fill-line" style="width: 460px;">{{ $proposal->tomador_email ?? '' }}</span>
        </div>
        <div class="line-row">
            ACTIVIDADE PREDOMINANTE: <span class="fill-line" style="width: 760px;">{{ $proposal->tomador_actividade ?? '' }}</span>
        </div>

        <div class="section-bar">LOCAL DE COBRANÇA <span class="light paren-text">(Se diferente do Tomador de Seguro)</span></div>
        <div class="line-row">
            MORADA: <span class="fill-line" style="width: 540px;">{{ $proposal->local_cobranca_morada ?? '' }}</span>
            &nbsp;&nbsp; MUNICÍPIO: <span class="fill-line" style="width: 250px;">{{ $proposal->local_cobranca_municipio ?? '' }}</span>
        </div>
        <div class="line-row">
            LOCALIDADE: <span class="fill-line" style="width: 380px;">{{ $proposal->local_cobranca_localidade ?? '' }}</span>
            &nbsp;&nbsp; TELEFONE / TELEMÓVEL: <span class="fill-line" style="width: 350px;">{{ $proposal->local_cobranca_telemovel ?? '' }}</span>
        </div>

        <div class="section-bar">DÉBITO EM CONTA</div>
        <div style="margin:8px 0 10px;">Se preferir aderir ao débito em conta por favor indique o nome do seu Banco:
            <span class="fill-line" style="width: 500px;">{{ $proposal->debito_banco ?? '' }}</span>
        </div>
        <div class="line-row">
            IBAN/NIB
            <table style="display:inline-table; border-collapse:collapse; vertical-align:middle; margin-left:4px;">
                <tr>
                    @foreach(range(0, 24) as $i)
                        <td style="border-bottom:1px solid #666; width:18px; height:18px; text-align:center; font-size:8pt;">
                            @if($i === 0)A @elseif($i === 1)O @elseif($i === 2)0 @elseif($i === 3)6 @else{{ $ibanDigits[$i - 4] ?? '' }}@endif
                        </td>
                    @endforeach
                </tr>
            </table>
        </div>

        <div class="section-bar">INFORMAÇÕES GERAIS</div>

        <table style="width:100%; border-collapse:collapse; font-size:8.6pt; margin-top:6px;">
            <tr>
                <td style="padding:4px 0; width:85%;"><strong>1.</strong> A EMPRESA ESTÁ REGULARMENTE CONSTITUÍDA?</td>
                <td style="text-align:right; padding:4px 0;">
                    NÃO <img src="{{ public_path('img/cotacao/' . at_checked_ref($proposal->empresa_constituida ?? '', 'nao')) }}" height="14" alt="">
                    &nbsp;&nbsp; SIM <img src="{{ public_path('img/cotacao/' . at_checked_ref($proposal->empresa_constituida ?? '', 'sim')) }}" height="14" alt="">
                </td>
            </tr>
            <tr>
                <td style="padding:4px 0;"><strong>2.</strong> ESTÃO ABRANGIDOS TODOS OS EMPREGADOS?</td>
                <td style="text-align:right; padding:4px 0;">
                    NÃO <img src="{{ public_path('img/cotacao/' . at_checked_ref($proposal->todos_empregados ?? '', 'nao')) }}" height="14" alt="">
                    &nbsp;&nbsp; SIM <img src="{{ public_path('img/cotacao/' . at_checked_ref($proposal->todos_empregados ?? '', 'sim')) }}" height="14" alt="">
                </td>
            </tr>
            <tr>
                <td style="padding:4px 0;"><strong>3.</strong> SÃO CUMPRIDAS TODAS AS NORMAS E REGULAMENTOS DE SEGURANÇA E HIGIENE NO TRABALHO EM VIGOR NA ACTIVIDADE?</td>
                <td style="text-align:right; padding:4px 0;">
                    NÃO <img src="{{ public_path('img/cotacao/' . at_checked_ref($proposal->cumprimento_normas ?? '', 'nao')) }}" height="14" alt="">
                    &nbsp;&nbsp; SIM <img src="{{ public_path('img/cotacao/' . at_checked_ref($proposal->cumprimento_normas ?? '', 'sim')) }}" height="14" alt="">
                </td>
            </tr>
            <tr>
                <td style="padding:4px 0;"><strong>4.</strong> FAZ USO DE MATÉRIAS EXPLOSIVAS, CORROSIVAS, INFLAMÁVEIS, TÓXICAS OU OUTRAS MATÉRIAS PERIGOSAS, INCLUINDO SOLDADURA?</td>
                <td style="text-align:right; padding:4px 0;">
                    NÃO <img src="{{ public_path('img/cotacao/' . at_checked_ref($proposal->materias_perigosas ?? '', 'nao')) }}" height="14" alt="">
                    &nbsp;&nbsp; SIM <img src="{{ public_path('img/cotacao/' . at_checked_ref($proposal->materias_perigosas ?? '', 'sim')) }}" height="14" alt="">
                </td>
            </tr>
            <tr>
                <td style="padding:4px 0;"><strong>5.</strong> EXECUTA TRABALHOS, AINDA QUE ESPORADICAMENTE, A MAIS DE 5 METROS DE ALTURA E 2 METROS DE PROFUNDIDADE?</td>
                <td style="text-align:right; padding:4px 0;">
                    NÃO <img src="{{ public_path('img/cotacao/' . at_checked_ref($proposal->trabalhos_altura ?? '', 'nao')) }}" height="14" alt="">
                    &nbsp;&nbsp; SIM <img src="{{ public_path('img/cotacao/' . at_checked_ref($proposal->trabalhos_altura ?? '', 'sim')) }}" height="14" alt="">
                </td>
            </tr>
        </table>

        <div class="line-row">QUAIS? <span class="fill-line" style="width: 870px;">{{ $proposal->trabalhos_altura_quais ?? '' }}</span></div>
        <div class="line-row"><strong>6.</strong> LOCAL OU LOCAIS ONDE SE EXECUTAM OS TRABALHOS: <span class="fill-line" style="width: 690px;">{{ $proposal->locais_trabalhos ?? '' }}</span></div>
        <div class="line-row"><strong>7.</strong> NATUREZA DOS TRABALHOS DESENVOLVIDOS: <span class="fill-line" style="width: 720px;">{{ $proposal->natureza_trabalhos ?? '' }}</span></div>
        <div class="line-row"><strong>8.</strong> QUAL O MEIO DE TRANSPORTE UTILIZADO COM MAIOR FREQUÊNCIA PARA O LOCAL DE TRABALHO, E VICE-VERSA? <span class="fill-line" style="width: 480px;">{{ $proposal->meio_transporte ?? '' }}</span></div>
        <div class="line-row">
            <strong>9.</strong> TEM POSTO MÉDICO, FARMÁCIA E/OU HOSPITAL, PRÓXIMO DO LOCAL DE TRABALHO?
            &nbsp; NÃO <img src="{{ public_path('img/cotacao/' . at_checked_ref($proposal->posto_medico ?? '', 'nao')) }}" height="14" alt="">
            &nbsp; SIM <img src="{{ public_path('img/cotacao/' . at_checked_ref($proposal->posto_medico ?? '', 'sim')) }}" height="14" alt="">
        </div>
        <div class="line-row">A QUE DISTÂNCIA? <span class="fill-line" style="width: 820px;">{{ $proposal->posto_medico_distancia ?? '' }}</span></div>

        {!! at_footer(1) !!}
    </div>

    {{-- ============================================================
         PÁGINA 2 — Modalidade, Pessoas a Segurar, Capital, Questionário Obrigatório
         ============================================================ --}}
    <div  style="position:relative; min-height:1500px; ">
        {{-- <div class="top-logo-right" style="margin-bottom:24px;">
            <img src="{{ public_path('img/cotacao/bic_seguro.png') }}" height="58" alt="">
        </div> --}}

        <div class="section-bar">MODALIDADE DE SEGURO</div>

        <div class="line-row" style="margin-top:10px;">
            <img src="{{ public_path('img/cotacao/' . at_checked_ref($proposal->modalidade ?? '', 'variavel')) }}" height="14" alt="">
            <strong>PRÉMIO VARIÁVEL</strong>
            <span style="font-size:8.2pt;">(AS FOLHAS DE FÉRIAS DEVEM SER ENVIADAS REGULARMENTE À BIC SEGUROS DE ACORDO COM O PREVISTO NA LEI)</span>
        </div>
        <div class="line-row">
            <img src="{{ public_path('img/cotacao/' . at_checked_ref($proposal->modalidade ?? '', 'fixo')) }}" height="14" alt="">
            <strong>PRÉMIO FIXO</strong>
            <span style="font-size:8.2pt;">(TODAS AS ALTERAÇÕES QUE AFECTEM O CONTRATO DEVERÃO SER COMUNICADAS À SEGURADORA NO PRAZO DE 8 DIAS)</span>
        </div>

        <div class="line-row" style="margin-top:14px;">
            <strong>Nº DE TRABALHADORES:</strong>
            <span class="inline-field" style="min-width:120px;">{{ $proposal->num_trabalhadores ?? '' }}</span>
        </div>

        <div class="section-bar">PESSOAS A SEGURAR <span class="light paren-text">(Anexar lista ou enviar ficheiro electrónico com as Pessoas a Segurar, conforme exemplo)</span></div>

        <table class="table-ref" style="margin-top:10px; font-size:8pt;">
            <tr>
                <th class="center" style="width:4%;">Nº</th>
                <th class="center" style="width:22%;">Nome da Pessoa Segura</th>
                <th class="center" style="width:16%;">Profissão</th>
                <th class="center" style="width:9%;">Categoria*</th>
                <th class="center" style="width:6%;">Idade</th>
                <th class="center" style="width:6%;">Sexo</th>
                <th class="center" style="width:18%;">Salário Mensal Base</th>
                <th class="center" style="width:19%;">Outras Remunerações**</th>
            </tr>
            @forelse($pessoasSeguras as $i => $p)
                <tr>
                    <td class="center">{{ $i + 1 }}</td>
                    <td>{{ $p['nome'] ?? '' }}</td>
                    <td>{{ $p['profissao'] ?? '' }}</td>
                    <td class="center">{{ $p['categoria'] ?? '' }}</td>
                    <td class="center">{{ $p['idade'] ?? '' }}</td>
                    <td class="center">{{ $p['sexo'] ?? '' }}</td>
                    <td class="center">{{ isset($p['salario']) && $p['salario'] !== '' && $p['salario'] !== null ? number_format($p['salario'], 2, ',', '.') : '' }}</td>
                    <td class="center">{{ isset($p['outrasRem']) && $p['outrasRem'] !== '' && $p['outrasRem'] !== null ? number_format($p['outrasRem'], 2, ',', '.') : '' }}</td>
                </tr>
            @empty
                @for($i = 1; $i <= 5; $i++)
                    <tr>
                        <td class="center">{{ $i }}</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                @endfor
            @endforelse
        </table>
        <div style="font-size:8pt; margin:6px 0;">
            *Categoria: A (Aprendizes) / P (Praticante) / E (Estagiário)<br>
            ** Outras Remunerações: Todas as que têm carácter de regularidade mensal.
        </div>

        <div class="section-bar">CAPITAL A SEGURAR</div>
        <div class="line-row" style="margin-top:10px;">
            MONTANTE DE SALÁRIOS ANUAIS:
            <span class="fill-line" style="width: 380px;">{{ isset($proposal->montante_salarios_anuais) && $proposal->montante_salarios_anuais !== '' ? number_format($proposal->montante_salarios_anuais, 2, ',', '.') : '' }}</span>
            &nbsp;&nbsp; NÚMERO SALÁRIOS / ANO: <span class="fill-line" style="width: 200px;">{{ $proposal->num_salarios_ano ?? '' }}</span>
        </div>
        <div style="font-size:8pt; margin:6px 0; font-style:italic;">
            (Indique o valor previsível das remunerações anuais, incluindo o Subsídios de Férias e todas as remunerações que revistam carácter de regularidade. Juntar obrigatoriamente cópia da última folha de férias ou relação completa, se se tratar de entidade em início de actividade).
        </div>

        <div class="section-bar">QUESTIONÁRIO OBRIGATÓRIO</div>

        <table style="width:100%; border-collapse:collapse; font-size:8.6pt; margin-top:6px;">
            <tr>
                <td style="padding:4px 0; width:80%;">• ALGUMAS DAS PESSOAS SEGURAS TÊM MENOS DE 16 ANOS?</td>
                <td style="text-align:right; padding:4px 0;">
                    NÃO <img src="{{ public_path('img/cotacao/' . at_checked_ref($proposal->menores_16 ?? '', 'nao')) }}" height="14" alt="">
                    &nbsp;&nbsp; SIM <img src="{{ public_path('img/cotacao/' . at_checked_ref($proposal->menores_16 ?? '', 'sim')) }}" height="14" alt="">
                </td>
            </tr>
        </table>
        <div class="line-row">Em caso afirmativo, indique o Nº de pessoas: <span class="fill-line" style="width: 700px;">{{ $proposal->menores_16_num ?? '' }}</span></div>

        <table style="width:100%; border-collapse:collapse; font-size:8.6pt; margin-top:6px;">
            <tr>
                <td style="padding:4px 0; width:80%;">• ALGUMAS DAS PESSOAS SEGURAS TRABALHAM AOS DOMINGOS E FERIADOS E FORA DAS HORAS NORMAIS DE EXPEDIENTE (EX. TURNOS)?</td>
                <td style="text-align:right; padding:4px 0;">
                    NÃO <img src="{{ public_path('img/cotacao/' . at_checked_ref($proposal->turnos ?? '', 'nao')) }}" height="14" alt="">
                    &nbsp;&nbsp; SIM <img src="{{ public_path('img/cotacao/' . at_checked_ref($proposal->turnos ?? '', 'sim')) }}" height="14" alt="">
                </td>
            </tr>
        </table>
        <div class="line-row">Em caso afirmativo, indique o Nº de pessoas: <span class="fill-line" style="width: 700px;">{{ $proposal->turnos_num ?? '' }}</span></div>

        <table style="width:100%; border-collapse:collapse; font-size:8.6pt; margin-top:6px;">
            <tr>
                <td style="padding:4px 0; width:80%;">• TEM SERVIÇO DE TRANSPORTE PARA OS COLABORADORES?</td>
                <td style="text-align:right; padding:4px 0;">
                    NÃO <img src="{{ public_path('img/cotacao/' . at_checked_ref($proposal->transporte_colab ?? '', 'nao')) }}" height="14" alt="">
                    &nbsp;&nbsp; SIM <img src="{{ public_path('img/cotacao/' . at_checked_ref($proposal->transporte_colab ?? '', 'sim')) }}" height="14" alt="">
                </td>
            </tr>
        </table>
        <div class="line-row">
            Em caso afirmativo, indique Nº pessoas transportadas: <span class="fill-line" style="width: 200px;">{{ $proposal->transporte_colab_num ?? '' }}</span>
            e categoria de veículos: <span class="fill-line" style="width: 350px;">{{ $proposal->transporte_colab_categoria ?? '' }}</span>
        </div>

        <table style="width:100%; border-collapse:collapse; font-size:8.6pt; margin-top:6px;">
            <tr>
                <td style="padding:4px 0; width:80%;">• ALGUMA DAS PESSOAS SEGURAS SOFRE DE DOENÇAS CONSIDERADAS PROFISSIONAIS?</td>
                <td style="text-align:right; padding:4px 0;">
                    NÃO <img src="{{ public_path('img/cotacao/' . at_checked_ref($proposal->doencas_prof ?? '', 'nao')) }}" height="14" alt="">
                    &nbsp;&nbsp; SIM <img src="{{ public_path('img/cotacao/' . at_checked_ref($proposal->doencas_prof ?? '', 'sim')) }}" height="14" alt="">
                </td>
            </tr>
        </table>
        <div class="line-row">
            Em caso afirmativo, identifique e indique o nº de pessoas: <span class="fill-line" style="width: 200px;">{{ $proposal->doencas_prof_num ?? '' }}</span>
            e quais as doenças: <span class="fill-line" style="width: 400px;">{{ $proposal->doencas_prof_quais ?? '' }}</span>
        </div>

        {!! at_footer(2) !!}
    </div>

    {{-- ============================================================
         PÁGINA 3 — Continuação Questionário, Seguros BIC, Contratos Anteriores, Outras Informações, Declaração
         ============================================================ --}}
    <div  style="position:relative; min-height:1500px; ">
        {{-- <div class="top-logo-right" style="margin-bottom:24px;">
            <img src="{{ public_path('img/cotacao/bic_seguro.png') }}" height="58" alt="">
        </div> --}}

        <div class="line-row" style="font-weight:bold;">• ENTRE OS TRABALHADORES EXISTEM:</div>

        <table style="width:100%; border-collapse:collapse; font-size:8.6pt; margin-top:6px;">
            <tr>
                <td style="padding:4px 0; width:80%;">- Trabalhadores portadores de deficiência?</td>
                <td style="text-align:right; padding:4px 0;">
                    NÃO <img src="{{ public_path('img/cotacao/' . at_checked_ref($proposal->deficiencia ?? '', 'nao')) }}" height="14" alt="">
                    &nbsp;&nbsp; SIM <img src="{{ public_path('img/cotacao/' . at_checked_ref($proposal->deficiencia ?? '', 'sim')) }}" height="14" alt="">
                </td>
            </tr>
        </table>
        <div class="line-row">
            Indique o Nº de pessoas: <span class="fill-line" style="width: 180px;">{{ $proposal->deficiencia_num ?? '' }}</span>
            e o grau / tipo de deficiência: <span class="fill-line" style="width: 420px;">{{ $proposal->deficiencia_grau ?? '' }}</span>
        </div>

        <table style="width:100%; border-collapse:collapse; font-size:8.6pt; margin-top:6px;">
            <tr>
                <td style="padding:4px 0; width:80%;">- Menores, aprendizes ou tirocinantes?</td>
                <td style="text-align:right; padding:4px 0;">
                    NÃO <img src="{{ public_path('img/cotacao/' . at_checked_ref($proposal->menores_aprendizes ?? '', 'nao')) }}" height="14" alt="">
                    &nbsp;&nbsp; SIM <img src="{{ public_path('img/cotacao/' . at_checked_ref($proposal->menores_aprendizes ?? '', 'sim')) }}" height="14" alt="">
                </td>
            </tr>
        </table>
        <div class="line-row">Indique o Nº de pessoas: <span class="fill-line" style="width: 720px;">{{ $proposal->menores_aprendizes_num ?? '' }}</span></div>

        <table style="width:100%; border-collapse:collapse; font-size:8.6pt; margin-top:6px;">
            <tr>
                <td style="padding:4px 0; width:80%;">- Pessoas que se desloquem em serviço dentro ou fora do País, por período superior a 15 dias?</td>
                <td style="text-align:right; padding:4px 0;">
                    NÃO <img src="{{ public_path('img/cotacao/' . at_checked_ref($proposal->deslocacoes ?? '', 'nao')) }}" height="14" alt="">
                    &nbsp;&nbsp; SIM <img src="{{ public_path('img/cotacao/' . at_checked_ref($proposal->deslocacoes ?? '', 'sim')) }}" height="14" alt="">
                </td>
            </tr>
        </table>
        <div class="line-row">
            Indique o Nº de pessoas: <span class="fill-line" style="width: 200px;">{{ $proposal->deslocacoes_num ?? '' }}</span>
            País / Países: <span class="fill-line" style="width: 460px;">{{ $proposal->deslocacoes_paises ?? '' }}</span>
        </div>

        <table style="width:100%; border-collapse:collapse; font-size:8.6pt; margin-top:6px;">
            <tr>
                <td style="padding:4px 0; width:80%;">- Familiares do Tomador do Seguro?</td>
                <td style="text-align:right; padding:4px 0;">
                    NÃO <img src="{{ public_path('img/cotacao/' . at_checked_ref($proposal->familiares ?? '', 'nao')) }}" height="14" alt="">
                    &nbsp;&nbsp; SIM <img src="{{ public_path('img/cotacao/' . at_checked_ref($proposal->familiares ?? '', 'sim')) }}" height="14" alt="">
                </td>
            </tr>
        </table>
        <div class="line-row">
            Indique o Nº de pessoas: <span class="fill-line" style="width: 200px;">{{ $proposal->familiares_num ?? '' }}</span>
            e o grau de parentesco: <span class="fill-line" style="width: 420px;">{{ $proposal->familiares_parentesco ?? '' }}</span>
        </div>

        <div class="section-bar">SEGUROS EXISTENTES NA BIC SEGUROS</div>
        <div class="line-row">
            É ou foi Cliente da BIC SEGUROS?
            &nbsp; NÃO <img src="{{ public_path('img/cotacao/' . at_checked_ref($proposal->bic_cliente ?? '', 'nao')) }}" height="14" alt="">
            &nbsp; SIM <img src="{{ public_path('img/cotacao/' . at_checked_ref($proposal->bic_cliente ?? '', 'sim')) }}" height="14" alt="">
            &nbsp;&nbsp; Em que produto(s)? <span class="fill-line" style="width: 480px;">{{ $proposal->bic_produtos ?? '' }}</span>
        </div>

        <div class="section-bar">CONTRATOS ANTERIORES EM OUTRAS SEGURADORAS</div>
        <div class="line-row" style="font-size:8.6pt;">
            O risco que pretende cobrir está ou esteve seguro, no seu todo ou em parte, noutra seguradora?
            &nbsp; NÃO <img src="{{ public_path('img/cotacao/' . at_checked_ref($proposal->anterior_segurado ?? '', 'nao')) }}" height="14" alt="">
            &nbsp; SIM <img src="{{ public_path('img/cotacao/' . at_checked_ref($proposal->anterior_segurado ?? '', 'sim')) }}" height="14" alt="">
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
            &nbsp; NÃO <img src="{{ public_path('img/cotacao/' . at_checked_ref($proposal->premios_divida ?? '', 'nao')) }}" height="14" alt="">
            &nbsp; SIM <img src="{{ public_path('img/cotacao/' . at_checked_ref($proposal->premios_divida ?? '', 'sim')) }}" height="14" alt="">
        </div>
        <div class="line-row">Se sim, especifique <span class="fill-line" style="width: 785px;">{{ $proposal->premios_divida_detalhes ?? '' }}</span></div>

        <div class="section-bar">OUTRAS INFORMAÇÕES <span class="light paren-text">(Pode ser selecionada mais do que uma opção)</span></div>

        <div class="line-row" style="font-weight:700; margin-top:8px;">Como conheceu a BIC Seguros?</div>
        <div class="line-row" style="font-size:8.6pt;">
            TV <img src="{{ public_path('img/cotacao/' . at_bool_ref($proposal->conheceu_tv ?? false)) }}" height="14" alt="">
            &nbsp;&nbsp; RÁDIO <img src="{{ public_path('img/cotacao/' . at_bool_ref($proposal->conheceu_radio ?? false)) }}" height="14" alt="">
            &nbsp;&nbsp; FOLHETOS <img src="{{ public_path('img/cotacao/' . at_bool_ref($proposal->conheceu_folhetos ?? false)) }}" height="14" alt="">
            &nbsp;&nbsp; ATRAVÉS DO BANCO BIC <img src="{{ public_path('img/cotacao/' . at_bool_ref($proposal->conheceu_banco ?? false)) }}" height="14" alt="">
            &nbsp;&nbsp; ATRAVÉS DE UM CORRETOR/MEDIADOR <img src="{{ public_path('img/cotacao/' . at_bool_ref($proposal->conheceu_corretor ?? false)) }}" height="14" alt="">
            &nbsp;&nbsp; ATRAVÉS DE UM AMIGO <img src="{{ public_path('img/cotacao/' . at_bool_ref($proposal->conheceu_amigo ?? false)) }}" height="14" alt="">
        </div>
        <div class="line-row">
            OUTROS <img src="{{ public_path('img/cotacao/' . at_bool_ref($proposal->conheceu_outros ?? false)) }}" height="14" alt="">
            &nbsp; Quais? <span class="fill-line" style="width: 720px;">{{ $proposal->conheceu_outros_detalhes ?? '' }}</span>
        </div>

        <div class="line-row" style="font-weight:700; margin-top:8px;">A sua preferência pela BIC Seguros, deve-se a:</div>
        <div class="line-row" style="font-size:8.6pt;">
            IMAGEM CORPORATIVA (BIC) <img src="{{ public_path('img/cotacao/' . at_bool_ref($proposal->pref_imagem ?? false)) }}" height="14" alt="">
            &nbsp;&nbsp; CONFIANÇA <img src="{{ public_path('img/cotacao/' . at_bool_ref($proposal->pref_confianca ?? false)) }}" height="14" alt="">
            &nbsp;&nbsp; ATENDIMENTO <img src="{{ public_path('img/cotacao/' . at_bool_ref($proposal->pref_atendimento ?? false)) }}" height="14" alt="">
            &nbsp;&nbsp; QUALIDADE DE SERVIÇO <img src="{{ public_path('img/cotacao/' . at_bool_ref($proposal->pref_qualidade ?? false)) }}" height="14" alt="">
            &nbsp;&nbsp; PRODUTOS <img src="{{ public_path('img/cotacao/' . at_bool_ref($proposal->pref_produtos ?? false)) }}" height="14" alt="">
            &nbsp;&nbsp; COMODIDADE <img src="{{ public_path('img/cotacao/' . at_bool_ref($proposal->pref_comodidade ?? false)) }}" height="14" alt="">
        </div>
        <div class="line-row">
            OUTROS <img src="{{ public_path('img/cotacao/' . at_bool_ref($proposal->pref_outros ?? false)) }}" height="14" alt="">
            &nbsp; Quais? <span class="fill-line" style="width: 720px;">{{ $proposal->pref_outros_detalhes ?? '' }}</span>
        </div>

        <div class="section-bar">DECLARAÇÃO</div>
        <div style="font-size:8.6pt; line-height:1.55; margin-top:10px;">
            Para efeitos da celebração do presente contrato de seguro, declaro que:
            <br>a) &nbsp;&nbsp;&nbsp; Respondi completamente e com veracidade a todas as perguntas e verifiquei o preenchimento desta proposta.
            <br>b) &nbsp;&nbsp;&nbsp; São exactas e completas as declarações por mim prestadas e tomei conhecimento de todas as informações necessárias à celebração do presente contrato, tendo sido colocadas à minha disposição as respectivas Condições Gerais e Especiais, para delas tomar integral conhecimento, e prestados todos os esclarecimentos sobre as mesmas, nomeadamente sobre garantias e exclusões, com as quais estou de acordo.
            <br>c) &nbsp;&nbsp;&nbsp; Autorizo a consulta dos dados pessoais disponibilizados, sob regime de absoluta confidencialidade, pelas empresas que integram o grupo desde que compatível com a finalidade da recolha dos mesmos.
        </div>

        {!! at_footer(3) !!}
    </div>

    {{-- ============================================================
         PÁGINA 4 — Conclusão Declaração, Assinatura, Autorização Débito, Reservado aos Serviços
         ============================================================ --}}
    <div  style="position:relative; min-height:1500px; ">
        {{-- <div class="top-logo-right" style="margin-bottom:12px;">
            <img src="{{ public_path('img/cotacao/bic_seguro.png') }}" height="58" alt="">
        </div> --}}

        <div style="font-size:8.6pt; line-height:1.55;">
            d) &nbsp;&nbsp;&nbsp; Autorizo a BIC Seguros, S.A a proceder à recolha de dados pessoais complementares junto de organismos públicos, empresas especializadas e outras unidades económicas com vista à confirmação ou complemento dos elementos recolhidos e necessários à gestão da relação contratual.
        </div>

        <div style="margin-top:26px; font-size:8.8pt;">
            Feito, em <span class="fill-line" style="width: 210px;">{{ $proposal->local_assinatura ?? '' }}</span>
            a <span class="fill-line" style="width: 40px;">{{ $proposal->data_assinatura ? \Carbon\Carbon::parse($proposal->data_assinatura)->format('d') : '' }}</span> de
            <span class="fill-line" style="width: 210px;">{{ $proposal->data_assinatura ? \Carbon\Carbon::parse($proposal->data_assinatura)->locale('pt')->format('F') : '' }}</span> de
            <span class="fill-line" style="width: 70px;">{{ $proposal->data_assinatura ? \Carbon\Carbon::parse($proposal->data_assinatura)->format('Y') : '' }}</span>
        </div>

        <div style="margin-top:52px; text-align:right; padding-right:20px;">
            <div class="fill-line" style="width: 360px; display:inline-block;"></div><br>
            (Assinatura e Carimbo do Tomador de Seguro)
        </div>

        <div class="debit-panel" style="margin-top:18px; padding:8px 10px 14px;">
            <div style="text-align:right; font-size:11pt; font-weight:700; margin-bottom:4px;">Autorização de Débito em Conta</div>

            <table style="width:100%; border-collapse:collapse; font-size:8.6pt;">
                <tr>
                    <td width="8%" style="padding:2px 4px;">Banco</td>
                    <td width="38%" class="muted-fill" style="padding:2px 10px;">{{ $proposal->aut_banco ?? 'Banco BIC' }}</td>
                    <td width="10%" style="padding:2px 10px; text-align:right;">Agência</td>
                    <td width="18%" class="muted-fill" style="padding:2px 10px;">{{ $proposal->aut_agencia ?? '' }}</td>
                    <td width="8%" style="padding:2px 10px; text-align:right;">Data</td>
                    <td width="18%">
                        <table style="width:100%; border-collapse:collapse;">
                            <tr>
                                <td class="muted-fill" style="border:1px solid #111; height:20px; text-align:center; padding:2px;">
                                    {{ $proposal->aut_data ? \Carbon\Carbon::parse($proposal->aut_data)->format('d/m/Y') : '' }}
                                </td>
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
                <span class="fill-line" style="width: 320px;">{{ isset($proposal->aut_valor_maximo) && $proposal->aut_valor_maximo !== '' ? number_format($proposal->aut_valor_maximo, 2, ',', '.') : '' }}</span>
                Kz
            </div>

            <div style="margin-top:18px;">Periodicidade dos pagamentos:</div>
            <table style="width:100%; border-collapse:collapse; margin-top:10px; font-size:8.7pt;">
                <tr>
                    <td width="25%"><img src="{{ public_path('img/cotacao/' . at_checked_ref($proposal->aut_periodicidade ?? '', 'anual')) }}" height="14" alt=""> ANUAL</td>
                    <td width="25%"><img src="{{ public_path('img/cotacao/' . at_checked_ref($proposal->aut_periodicidade ?? '', 'semestral')) }}" height="14" alt=""> SEMESTRAL</td>
                    <td width="25%"><img src="{{ public_path('img/cotacao/' . at_checked_ref($proposal->aut_periodicidade ?? '', 'trimestral')) }}" height="14" alt=""> TRIMESTRAL</td>
                    <td width="25%"><img src="{{ public_path('img/cotacao/' . at_checked_ref($proposal->aut_periodicidade ?? '', 'mensal')) }}" height="14" alt=""> MENSAL</td>
                </tr>
            </table>

            <div style="text-align:center; margin-top:46px;">
                <div class="fill-line" style="width: 700px;"></div><br>
                (Assinatura de acordo com a ficha bancária)
            </div>
        </div>

        <div class="section-bar" style="margin-top:18px;">RESERVADO AOS SERVIÇOS DA COMPANHIA</div>
        <div class="obs-lines" style="margin-top:12px;">
            @for($i = 0; $i < 5; $i++)
                <div class="obs-line" style="height:24px;"></div>
            @endfor
        </div>

        {!! at_footer(4) !!}
    </div>
</body>
</html>
