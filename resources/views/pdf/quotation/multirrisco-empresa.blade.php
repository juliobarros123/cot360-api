<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
   
</head>

<body>

    <div class="logo-container">
        <img src="img/cotacao/icone_capa.jpeg" class="icone_capa" alt="">
    </div>

    <div class="pdf-container" id="pdfContent">

        @php
            $deductiblesID = [];
        @endphp

        @foreach ($QLI as $item)
            @isset($item['deductibles'])
                @php
                    foreach ($item['deductibles'] as $deductibleID) {
                        if (!in_array($deductibleID['deductible_id'], $deductiblesID)) {
                            $deductiblesID[] = $deductibleID['deductible_id'];
                        }
                    }
                @endphp
            @endisset
        @endforeach

        <div class="page-border"></div>

        <div class="header">
            <img class="title-img" src="img/cotacao/title.jpg" alt="">
            @isset($channel['area'])
                <h5 class="area-corrector">{{ $channel['area'] }}</h5>
            @else
                <h5 class="area-corrector" style="color: white">-----------</h5>
            @endisset
        </div>
        <br>

        <div class="box-cover">
            @php
                $cover = 'img/cotacao/multirrisco.png';
            @endphp

            @if ($cover)
                <img class="cover" src="{{ $cover }}" alt="">
            @endif
        </div>

        @php
            $cotNumber = '-----';
            if (isset($quotation['quotation_number']) && $quotation['quotation_number']) {
                $cotNumber = formatarNumeroCotacao($quotation['quotation_number']);
            }
        @endphp

        <div class="proposal-title">
            <h3 class="proposal-title-title color-black fw-b" style=" font-size: 20px;">
                <strong>PROPOSTA DE SEGURO {{ $selectedBranch['description'] ?? '' }}</strong>
            </h3>
            <h5 class="color-black">
                Cotação nº {{ $cotNumber }}@if ($version_number > 1)_V{{ $version_number }}@endif/{{ dataPortugues() }}
            </h5>
        </div>

        <div class="intro-text">
            <p>
                Agradecemos a oportunidade concedida, ao BIC Seguros, SA para apresentar esta proposta de 
                colaboração, a qual fica sujeita a eventuais reajustamentos que sejam julgados convenientes. A 
                nossa sugestão é que considerem o conjunto dos serviços oferecidos, e dos meios técnicos e 
                humanos que colocamos à vossa disposição, como as ferramentas que permitirão obter um 
                serviço de Excelência, dando assim cumprimento às vossas expectativas.
                <br><br>
                Será para nós uma honra colaborar com tão prestigiada entidade na implementação e/ou
                eventual redefinição da vossa política de seguros e, portanto, empenhar-nos-emos a fundo para 
                que todos os objectivos definidos possam ser alcançados.
            </p>
        </div>

        @php
            use Carbon\Carbon;
            $data = Carbon::now()->locale('pt_BR');
            $dataFormatada = $data->translatedFormat('d \d\e F \d\e Y');
            $dataFormatada = preg_replace_callback(
                '/de ([a-z])/',
                function ($matches) {
                    return 'de ' . mb_strtoupper($matches[1]);
                },
                $dataFormatada,
            );
        @endphp

        <div class="date-text">
            <p class="fw-b color-black" style="text-align: right">
                Luanda, {{ $dataFormatada }}
            </p>
        </div>

        <!-- Page 2 -->
        <br><br><br>

        <div class="page-title color-black">PROPOSTA DE SEGURO {{ strtoupper($selectedBranch['description'] ?? '') }}</div>
        <div class="page-sub-title red" style="margin-top: 3px;">
            COTAÇÃO Nº {{ $cotNumber }}/{{ dataPortugues() }}
        </div>
        <br><br>

        <table class="text-block">
            <tr>
                <td class="left-text bold">Tomador do Seguro</td>
                <td class="right-text">
                    <p><strong>{{ $entity['name'] ?? '' }}</strong><br>
                        NIF: {{ $entity['nif'] ?? 'A Indicar' }}
                    </p>
                </td>
            </tr>
        </table>

        {{--  @if(isset($cae) && $cae)  --}}
            <table class="text-block mt-text-block">
                <tr>
                    <td class="left-text bold">Código de Actividade <br> Económica (CAE)</td>
                    <td class="right-text">
                        <p>A Indicar</p>
                    </td>
                </tr>
            </table>
        {{--  @endif  --}}

        <table class="text-block mt-text-block">
            <tr>
                <td class="left-text bold">Locais de Risco</td>
                <td class="right-text">
                    @php
                        $risk_locations = [];
                        foreach($objects as $object) {
                            if(isset($object['address']) && $object['address']) {
                                $risk_locations[] = $object['address'];
                            }
                        }
                    @endphp
                    @if(count($risk_locations) > 0)
                        @foreach($risk_locations as $location)
                            <p>{{ $location }}</p>
                        @endforeach
                    @else
                        <p>Angola</p>
                    @endif
                </td>
            </tr>
        </table>

        <table class="text-block mt-text-block">
            <tr>
                <td class="left-text bold">Períodos e Datas</td>
                <td class="right-text">
                    <p style="padding: 0;margin: 0;">Início do Seguro: &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        às 00h00 de …./…./…… (Em data a indicar)</p>
                    <p style="padding: 0;margin: 0;">
                        <hr style="padding: 0;margin: 0;"> <br> Período do Contrato:
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Anual Renovável
                    </p>
                </td>
            </tr>
        </table>

        <table class="text-block mt-text-block">
            <tr>
                <td class="left-text bold">Tipo de Contrato</td>
                <td class="right-text">
                    <p>Patrimoniais- Seguro Multirriscos Empresa</p>
                </td>
            </tr>
        </table>

        <table class="text-block mt-text-block">
            <tr>
                <td class="left-text bold">Objecto Seguro</td>
                <td class="right-text">
                    <p>Edifício e Conteúdos das instalações do Tomador de Seguro existentes no local
                        de risco acima indicado.</p>
                </td>
            </tr>
        </table>

        {{--  @if (!empty($selectedConditions))  --}}
            <table class="text-block mt-text-block">
                <tr>
                    <td class="left-text bold">Condições Especiais</td>
                    <td class="right-text">
                        <p>A apólice ficará sujeita às Condições Especiais aplicáveis ao presente Contrato, 
                            nomeadamente:</p>
                        @foreach ($selectedConditions as $condition)
                            <p class="condition-item">
                                {{ $condition['long_text'] }}
                            </p>
                        @endforeach
                    </td>
                </tr>
            </table>
        {{--  @endif  --}}

        <table class="text-block mt-text-block">
            <tr>
                <td class="left-text bold">Capitais a Segurar</td>
                <td class="right-text">
                    @php
                        $edificios_capital = 0;
                        $conteudos_capital = 0;
                        $moeda = $selectedCurrency;
                        
                        // Somar os capitais de edifícios e conteúdos
                        foreach($objects as $object) {
                            if(isset($object['building_capital_value'])) {
                                $edificios_capital += $object['building_capital_value'];
                            }
                            if(isset($object['content_capital_value'])) {
                                $conteudos_capital += $object['content_capital_value'];
                            }
                        }
                    @endphp
                    @if($edificios_capital > 0)
                        <p><strong>Edifícios – {{ number_format($edificios_capital, 0, ',', '.') }} {{ $moeda }}</strong></p>
                    @endif
                    @if($conteudos_capital > 0)
                        <p><strong>Conteúdos – {{ number_format($conteudos_capital, 0, ',', '.') }} {{ $moeda }}</strong></p>
                    @endif
                </td>
            </tr>
        </table>

        <table class="text-block mt-text-block" style="margin-top: 100px;">
            <tr>
                <td class="left-text bold">Coberturas e Limites <br> Indemnização</td>
                <td class="right-text">
                    <table class="tbl-cobertura-seguros">
                        <thead>
                            <tr>
                                <th class="tbl-cobertura-seguros-header text-left" style="width: 70%; border-left-color: transparent !important; padding-left: 5px;">
                                    Coberturas
                                </th>
                                <th class="tbl-cobertura-seguros-header" style="width: 30%; border-right-color: transparent !important;">
                                    Limites
                                </th>
                            </tr>
                        </thead>
                        
                        <tbody>
                            @php
                                $totalLines = 0;
                            
                                // Contar apenas subcoberturas (com group_id) ou principais (314, 335)
                                foreach ($coverages as $coverage) {
                                    if ( 
                                        ($coverage['group_id'] )) {
                                        $totalLines++;
                                    }
                                }
                                $currentLine = 0;
                            @endphp
                            @php

                            @endphp
                            @foreach ($coverages as $coverage)
                                @if ( $coverage['group_id'] )
                                    @php 
                                        $currentLine++;
                                        $qli = findQLI($QLI, $coverage);
                                        $limit = '';
                                        
                                        // Determinar o limite baseado no tipo de cobertura
                                  if (isset($coverage['cov_perc']) && ($coverage['cov_perc'] == "1.00" ||  $coverage['cov_perc'] == "1")) {

                                            $limit = 'Capital Seguro';
                                        } else if(isset($coverage['cov_perc']) && $coverage['cov_perc'] > 0) {
                                            // Limite percentual
                                            $perc = floatval($coverage['cov_perc'])*100;
                                            $limit = number_format($perc, 0) . '% do capital seguro';
                                        }  
                                    @endphp
                                    
                                    <tr>
                                        <td class="tbl-cobertura-seguros-cell tbl-cobertura-item" style="border-left: none !important; {{ $currentLine == $totalLines ? '' : 'border-bottom: none !important;' }}">
                                            <img src="img/cotacao/icone-item.jpg" class="icone_item" alt=""> {{ ucsentences($coverage['name']) }}
                                        </td>
                                        <td class="tbl-cobertura-seguros-cell tbl-cobertura-capital" style="border-right: none !important;font-size:10.5px; text-align:right; {{ $currentLine == $totalLines ? '' : 'border-bottom: none !important;' }}">
                                            {{ $limit }}
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>

        <br><br>
        <table class="text-block mt-text-block">
            <tr>
                <td class="left-text bold">Franquias Deduzíveis <br> por Sinistro</td>
                <td class="right-text">
                    <p>Serão consideradas, por sinistro ou série de sinistros provenientes de uma 
                        mesma causa, as seguintes franquias:</p>
                    <br>
                    <table class="tbl-cobertura-seguros">
                        <thead>
                            <tr>
                                <th class="tbl-cobertura-seguros-header text-left" style="width: 65%; border-left-color: transparent !important; padding-left: 5px;">
                                    Coberturas
                                </th>
                                <th class="tbl-cobertura-seguros-header" style="width: 35%; border-right-color: transparent !important;">
                                    Franquias
                                </th>
                            </tr>
                        </thead>
                        
                        <tbody>
                            @php
                                $totalLines = 0;
                                foreach ($coverages as $coverage) {
                                    if ( 
                                        ($coverage['group_id'] )) {
                                        $totalLines++;
                                    }
                                }
                                $currentLine = 0;
                            @endphp
                            
                            @foreach ($coverages as $coverage)
                       
                                @if ( 
                                     ($coverage['group_id'] ))
                                    @php

                                        $currentLine++;
                                        $qli = findQLI($QLI, $coverage);
                                        $deductible = null;
                                        $franquia_text = '';
                                       
                           
                                              
                      
                                        if (isset($qli['deductible_id']) && $qli['deductible_id']) {
                                            $deductible = findDeductible($qli['deductible_id']);
                                        }
                       
        
                                        // Definir franquia baseado no tipo de cobertura
                                        if($deductible) {
                                    
                                                $franquia_text = $deductible['deduct_percent'].'% dos prejuízos indemnizáveis, mínimo '.$deductible['deduct_min'].' <br>'.$moeda.', máximo '.$deductible['deduct_max'].' '.$moeda;

                                        }else {
                                            
                                                $franquia_text='Sem Franquia';
                                        }
                                    @endphp
                                    
                                    <tr>
                                        <td class="tbl-cobertura-seguros-cell tbl-cobertura-item" style="border-left: none !important; ; {{ $currentLine == $totalLines ? '' : 'border-bottom: none !important;' }}">
                                            <img src="img/cotacao/icone-item.jpg" class="icone_item" alt=""> {{ ucsentences($coverage['name']) }}
                                        </td>
                                        <td class="tbl-cobertura-seguros-cell tbl-cobertura-capital" style="border-right: none !important;font-size:10.5px; text-align:right; {{ $currentLine == $totalLines ? '' : 'border-bottom: none !important;' }}">
                                        <?php echo $franquia_text; ?>

                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>

        <br><br>
        <table class="text-block mt-text-block">
            <tr>
                <td class="left-text bold">Taxa e Prémio Total</td>
                <td class="right-text">
                    <p>Para cobertura dos riscos acima indicados e sujeito aos períodos e capitais 
                        acima referidos, será aplicada a seguinte taxa simples:</p>
                    <br>
                    
                    @php
                        // Buscar as taxas dos QLI para edifícios e conteúdos
                        $taxa_edificios = null;
                        $taxa_conteudos = null;
                        
                        foreach($QLI as $qli) {
                            // MULTIRISCOS EDIFÍCIO - id 335
                            if($qli['coverageObject']['short_name'] == "MULTIRISCOS EDIFÍCIO"  && isset($qli['rate'])) {
                                $taxa_edificios = $qli['rate'] * 10; // Converter para ‰ (por mil)
                            }
                            // MULTIRISCOS CONTEÚDO - id 314
                            if($qli['coverageObject']['short_name'] == "MULTIRISCOS CONTEÚDO" && isset($qli['rate'])) {
                                $taxa_conteudos = $qli['rate'] * 10; // Converter para ‰ (por mil)
                            }
                        }
                    @endphp
                    
                    @if($edificios_capital > 0 && $taxa_edificios)
                        <p>
                            &nbsp;&nbsp;<img src="img/cotacao/icone-item.jpg" class="icone_item" alt="">
                            <strong>{{ number_format($taxa_edificios, 3, ',', '') }}‰</strong> (por mil), a incidir sobre os valores seguros do edifício.
                        </p>
                    @endif
                    
                    @if($conteudos_capital > 0 && $taxa_conteudos)
                        <p>
                            &nbsp;&nbsp;<img src="img/cotacao/icone-item.jpg" class="icone_item" alt="">
                            <strong>{{ number_format($taxa_conteudos, 3, ',', '') }}‰</strong> (por mil), a incidir sobre os valores seguros de conteúdos
                        </p>
                    @endif

                    <br>
                    <p>Ao prémio simples assim calculado será acrescido dos encargos legais em 
                        vigor à data da emissão, sendo, actualmente, de 20% e dos impostos legais quando aplicável.</p>
                    <br>
                    
                    <table>
                        <tr>
                            <th style="text-align: right;font-weight:normal">Moeda: {{ $moeda }}</th>
                        </tr>
                    </table>

                    <table>
                        @foreach ($premiosGlobais as $key => $item)
                            @if ($item['status'])
                                <tr class="tr_taxa_premio">
                                    <td>
                                        @switch($key)
                                            @case('premioSimples')
                                                Prémio Simples
                                            @break

                                            @case('premioEncargos')
                                                Encargos (20%)
                                            @break

                                            @case('iva')
                                                IVA (14%)
                                            @break

                                            @case('premioTotalAnual')
                                                <strong>Prémio Total Anual</strong>
                                            @break

                                            @default
                                                {{ $key }}
                                        @endswitch
                                    </td>
                                    <td class="text-right">
                                        @if (Str::startsWith($key, 'premioTotal'))
                                            <strong>{{ number_format(arredondarBase5($item['value']), 2, ',', ' ') }}</strong>
                                        @else
                                            {{ number_format(arredondarBase5($item['value']), 2, ',', ' ') }}
                                        @endif
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </table>
                </td>
            </tr>
        </table>

        <table class="text-block mt-text-block">
            <tr>
                <td class="left-text bold">Modalidade de <br> Pagamento</td>
                <td class="right-text">
                    <p>Anual Renovável, sendo que caso se opte por fraccionar o prémio serão imputados os respectivos
                        encargos de fraccionamento.</p>
                </td>
            </tr>
        </table>

        <table class="text-block mt-text-block">
            <tr>
                <td class="left-text bold">Encargos de <br> Fraccionamento</td>
                <th class="right-text" style="font-weight: 100!important;">
                    <div class="ft-value-cap-insurance">
                        <span> Mensal</span>
                        {!! str_repeat('&nbsp;', 121) !!}
                        <span>7%</span>
                    </div>
                    <hr style="margin-top:0px; margin-bottom:0px;">
                    <div class="ft-value-cap-insurance">
                        <span> Trimestral</span>
                        {!! str_repeat('&nbsp;', 116) !!}
                        <span>5%</span>
                    </div>
                    <hr style="margin-top:0px; margin-bottom:0px;">
                    <div class="ft-value-cap-insurance">
                        <span> Semestral</span>
                        {!! str_repeat('&nbsp;', 116) !!}
                        <span>3%</span>
                    </div>
                </th>
            </tr>
        </table>

        <br>
        <table class="text-block mt-text-block">
            <tr>
                <td class="left-text bold">Outras Condições <br> Aplicáveis</td>
                <td class="right-text">
                    <p>Estas condições são válidas por 30 dias.</p>
                </td>
            </tr>
        </table>

        @for ($countBr = 1; $countBr <= 100; $countBr++)
            <br>
        @endfor

        <div class="important-info">
            <h5 style="margin-bottom:0px">INFORMAÇÃO IMPORTANTE:</h5>
            <p class="info-importante" style="margin-top:0px">
                Os valores da presente cotação assentam num conjunto de pressupostos, como sejam os dados que nos 
                indicou e a inexistência de factores de agravamento de risco, pressupostos estes sujeitos a confirmação por 
                parte do Segurador. <br><br>
                O Segurador pode solicitar informação adicional e/ou confirmar os dados indicados, podendo daqui resultar a 
                não verificação, no caso concreto, dos pressupostos sobre os quais assentou a presente cotação e, 
                consequentemente, a não-aceitação do seguro nas condições referidas na mesma. Deste modo, importa notar 
                que o prémio desta cotação e a verificação dos respectivos pressupostos carecem de confirmação e aceitação 
                por parte do Segurador. <br><br>
                Para os dados indicados, apurou-se o prémio acima que inclui cargas fiscais e parafiscais em vigor à data da 
                cotação. Qualquer alteração legal posterior às cargas fiscais ou parafiscais aplicáveis, terá de ser considerada 
                pelo Segurador aquando da emissão do contrato. Esta cotação tem a validade acima indicada, sem prejuízo da 
                possibilidade, aquando da emissão do contrato, de alteração do prémio apurado caso ocorra uma alteração 
                legal posterior à data da cotação que determine a modificação das cargas fiscais ou parafiscais aplicáveis. <br><br>
                A informação constante deste documento não dispensa a consulta da informação pré contratual e contratual 
                legalmente exigida.
            </p>
            <h5> BIC Seguros, S. A.</h5>
        </div>

    </div>
</body>
</html>
