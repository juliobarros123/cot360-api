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
            $controlDisplayeCoverages = [];
            $controlDisplayeCoveragesCCS = [];
            $controlDisplayeCoveragesFDS = [];
          
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
            @isset($channel['description'])
                <h5 class="area-corrector">{{ $channel['description'] }}</h5>
            @else
                <h5 class="area-corrector" style="color: white">-----------</h5>
            @endisset
        </div>
        <br>

        <div class="box-cover">
            @php
                $cover = '';
                if ($selectedBranch['code'] == 10) {
                    $cover = 'img/cotacao/quote_AT_branch.jpeg';
                } elseif ($selectedBranch['code'] == 90 || $selectedBranch['code'] == 91) {
                    $cover = 'img/cotacao/quote_AUTO_branch.jpg';
                }
            @endphp

            @if ($cover)
                <img class="cover" src="{{ $cover }}" alt="">
            @endif
        </div>
        @php
            $cotNumber = '-----';
           
            if (isset($quotation_number) && $quotation_number) {
                $cotNumber = formatarNumeroCotacao($quotation_number);
            } elseif (isset($quotation)) {
                $cotNumber = formatarNumeroCotacao($quotation_number);
            }
        @endphp

        <div class="proposal-title">
            <h1 class="proposal-title-title color-black fw-b">
                <strong>Proposta de Seguro {{ ucsentences($selectedBranch['description'] ?? '' )}}</strong>
            </h1>
            <h5 class="color-black">
                Cotação nº {{ $cotNumber }}@if ($version_number > 1)
                    _V{{ $version_number }}
                @endif/{{ dataPortugues() }}
            </h5>

        </div>

        <div class="intro-text">
            <p>
                Agradecemos a oportunidade concedida à BIC Seguros para apresentar esta proposta de colaboração, a
                qual fica sujeita a eventuais reajustamentos que sejam julgados convenientes. A nossa sugestão é que
                considerem o conjunto dos serviços oferecidos, e dos meios técnicos e humanos que colocamos à vossa
                disposição, como as ferramentas que permitirão obter um serviço de Excelência, dando cumprimento às
                vossas expectativas.
                <br><br>
                Será para nós uma honra a colaboração com uma tão prestigiada entidade na implementação e eventual
                redefinição da vossa política de seguros e, portanto, empenhar-nos-emos a fundo para que todos os
                objectivos definidos possam ser alcançados.
            </p>
        </div>
        @php
            use Carbon\Carbon;

            // Define a data atual
            $data = Carbon::now()->locale('pt_BR');

            // Formata com zero à esquerda no dia e inicial maiúscula no mês
            $dataFormatada = $data->translatedFormat('d \d\e F \d\e Y');

            // Força a primeira letra do mês em maiúscula
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
        @php

        @endphp
        <div class="page-title color-black">PROOSTA DE SEGURO DE {{ $selectedBranch['description'] ?? '' }}</div>
        <div class="page-sub-title red" style="margin-top: 3px;">

            COTAÇÃO Nº {{ $cotNumber }}/{{ dataPortugues() }}

        </div>
        <br><br>

        <table class="text-block">
            <tr>
                <td class="left-text bold">Tomador do Seguro</td>
                <td class="right-text">
                    <p><strong>{{ $entity['name'] ?? '' }}</strong>
                        <br>
                        @if (!$caeSelected)
                            Luanda, Angola<br>
                        @endif

                        NIF: {{ $entity['nif'] ?? 'A indicar' }}
                    </p>
                </td>
            </tr>
        </table>

        @if ($caeSelected)
            <table class="text-block mt-text-block ">
                <tr>
                    <td class="left-text bold">Segurados</td>
                    <td class="right-text">
                        @php
                            $objectsNumber = $objects[0]['employee_count'];
                        @endphp
                        <p><strong>{{ $objectsNumber > 0 ? numberToWords($objectsNumber) : 'Todos os' }}
                                trabalhador{{ $objectsNumber != 1 ? 'es' : '' }}</strong> da
                            {{ $entity['name'] ?? '' }}
                        </p>
                        <p>compreendendo:</p>
                        <p>&nbsp;&nbsp;&nbsp;&nbsp;- &nbsp;&nbsp;os vinculados por contrato de trabalho ou contrato
                            legalmente <span class="span-space">
                                @for ($b = 0; $b < 12; $b++)
                                    {{ '-' }}
                                @endfor
                            </span> equiparado</p>
                        <p>&nbsp;&nbsp;&nbsp;&nbsp;- &nbsp;&nbsp;os aprendizes, praticantes, estagiários e demais
                            situações que <span class="span-space">
                                @for ($b = 0; $b < 12; $b++)
                                    {{ '-' }}
                                @endfor
                            </span>devam considerar-se de formação prática.</p>
                        <p>&nbsp;&nbsp;&nbsp;&nbsp;- &nbsp;&nbsp;todo aquele que, considerando-se na dependência
                            económica do <span class="span-space">
                                @for ($b = 0; $b < 10; $b++)
                                    {{ '-' }}
                                @endfor
                            </span> tomador de seguro, preste em conjunto ou isoladamente determinado <span
                                class="span-space">
                                @for ($b = 0; $b < 6; $b++)
                                    {{ '-' }}
                                @endfor
                            </span> serviço.
                        </p>
                    </td>
                </tr>
            </table>
        @endif
        @if ($caeSelected)
            <table class="text-block mt-text-block">
                <tr>
                    <td class="left-text bold">Código de Actividade <br> Económica (CAE)</td>
                    <td class="right-text">
                        <p>{{ $caeSelected['cae'] ?? '' }} - {{ $caeSelected['description'] ?? '' }}</p>
                    </td>
                </tr>
            </table>
        @endif

        <table class="text-block mt-text-block">
            <tr>
                <td class="left-text bold">Local de Risco</td>
                <td class="right-text">
                    <p class="bold">Angola</p>
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
                    <p>{{ $selectedBranch['description'] }}</p>
                </td>
            </tr>
        </table>

        <table class="text-block mt-text-block">
            <tr>
                <td class="left-text bold">Âmbito da Cobertura</td>
                <td class="right-text">
                    @if ($caeSelected)
                        <p>Todos os acidentes que se verifique no exercício da actividade laboral ao serviço da empresa
                            e/ou doenças profissionais que provoquem lesão ou danos corporais ao trabalhador.</p>
                        <p>Considera-se também acidente de trabalho, o ocorrido no trajecto habitual de ida e de
                            regresso do local de trabalho (in itinere).</p>
                        <p>Os sinistrados têm direito ao tratamento e cura da lesão, adequadas ao estabelecimento do
                            estado de saúde e da capacidade de trabalho ou de ganho e à sua recuperação para a vida
                            activa.</p>
                    @else
                        <p>Proporcionar a defesa dos legítimos interesses dos lesados em acidentes de viação assim como,
                            a segurança pessoal e patrimonial dos proprietários de veículos.</p>
                    @endif
                </td>
            </tr>
        </table>

        <!-- Page 3 -->
        @if (!$caeSelected)
         <table class="text-block mt-text-block">
    <tr>
        <td class="left-text bold" >Coberturas e Capitais Seguros</td>
        <td class="right-text">
        <table class="tbl-cobertura-seguros">
    <!-- Cabeçalho -->
    <thead>
        <tr>
            <th class="tbl-cobertura-seguros-header text-left " style="width: 60%; border-left-color: transparent !important;padding-left: 5px;">Coberturas</th>
            <th class="tbl-cobertura-seguros-header" style="width: 40%; border-right-color: transparent !important;">Capitais Seguros</th>
        </tr>
    </thead>
    
    <tbody>
        @php
            // Calcular total de linhas para detectar a última
            $totalLines = 2; // Responsabilidade Civil + Facultativa
            foreach ($coverages as $coverage) {
                if (!$coverage['group_id'] && !in_array($coverage['id'], $controlDisplayeCoveragesCCS) && selectedCoverage($QLI, $coverage['id']) && $coverage['short_name'] != 'R.CIVIL') {
                    $subcoverages = groupCoverages($coverage['id'], $coverages);
                    foreach ($subcoverages as $sub) {
                        if ($coverage['short_name'] != 'R.CIVIL OBR.' && (selectedCoverage($QLI, $sub['id']) || $sub['rate_type'] == 'ZERO')) {
                            $isQIV = strtoupper($sub['short_name']) == 'QIV';
                            if ($isQIV) {
                                if (isset($capitaisQIV[0]) && $capitaisQIV[0] == 500000) $totalLines++;
                                if (isset($capitaisQIV[1]) && $capitaisQIV[1] == 750000) $totalLines++;
                            } else {
                                $totalLines++;
                            }
                        }
                    }
                }
            }
            $currentLine = 0;
        @endphp
        
        <!-- Cobertura Obrigatória (Lei 35/09) -->
        <tr>
            <td class="tbl-cobertura-seguros-cell tbl-cobertura-secao-titulo" style="border-left: none !important; border-top: none !important; border-bottom: none !important;" colspan="1">
                <strong>Cobertura Obrigatória (Lei 35/09)</strong>
            </td>
        </tr>
        
        @php $currentLine++; @endphp
        <tr>
            <td class="tbl-cobertura-seguros-cell tbl-cobertura-item" style="border-left: none !important; border-top: none !important; {{ $currentLine == $totalLines ? '' : 'border-bottom: none !important;' }}">
                <img src="img/cotacao/icone-item.jpg" class="icone_item" alt=""> Responsabilidade Civil
            </td>
            <td class="tbl-cobertura-seguros-cell tbl-cobertura-capital" style="border-top: none !important; border-right: none !important; {{ $currentLine == $totalLines ? '' : 'border-bottom: none !important;' }}">
                Conforme anexo A
            </td>
        </tr>
        
        @php $currentLine++; @endphp
        <tr>
            <td class="tbl-cobertura-seguros-cell tbl-cobertura-item" style="border-left: none !important; border-top: none !important; {{ $currentLine == $totalLines ? '' : 'border-bottom: none !important;' }}">
                <img src="img/cotacao/icone-item.jpg" class="icone_item" alt=""> Responsabilidade Civil Facultativa
            </td>
            <td class="" style="text-align:center;border-top: none !important; border-right: none !important; {{ $currentLine == $totalLines ? '' : 'border-bottom: none !important;' }}">
                Conforme anexos B e C
            </td>
        </tr>
        
        <!-- Outras coberturas dinâmicas -->
        @foreach ($coverages as $coverage)
            @if (!$coverage['group_id'] &&
                 !in_array($coverage['id'], $controlDisplayeCoveragesCCS) &&
                 selectedCoverage($QLI, $coverage['id']) && 
                 $coverage['short_name'] != 'R.CIVIL')
                
                @php
                    $subcoverages = groupCoverages($coverage['id'], $coverages);
                    $hasValidSubs = false;
                    
                    // Verificar se há subcoberturas válidas
                    foreach ($subcoverages as $sub) {
                        if ($coverage['short_name'] != 'R.CIVIL OBR.' && 
                            (selectedCoverage($QLI, $sub['id']) || $sub['rate_type'] == 'ZERO')) {
                            $hasValidSubs = true;
                            break;
                        }
                    }
                @endphp
                
                @if ($hasValidSubs)
                    <!-- Título da seção/grupo -->
                    <tr>
                        <td class="tbl-cobertura-seguros-cell tbl-cobertura-secao-titulo" style="border-left: none !important; border-bottom: none !important;" colspan="1">
                            <strong>{{ $coverage['name'] }}</strong>
                        </td>
                          <td class="tbl-cobertura-seguros-cell tbl-cobertura-secao-titulo" style="border-left: none !important; border-bottom: none !important;border-right: none !important;" colspan="1">
                          
                        </td>
                    </tr>


                    
                    <!-- Itens da seção -->
                    @foreach ($subcoverages as $sub)
                     
                        @if ($coverage['short_name'] != 'R.CIVIL OBR.' && 
                             (selectedCoverage($QLI, $sub['id']) || $sub['rate_type'] == 'ZERO'))
                            
                            @php 
                                $isQIV = strtoupper($sub['short_name']) == 'QIV';
                                $qli = findQLI($QLI, $sub);
                            @endphp
                            
                            @if ($isQIV)
                         
                                <!-- QIV Ligeiros -->
                                @if((isset($capitaisQIV[0]) && $capitaisQIV[0] == 500000 )|| (isset($capitaisQIV[1]) && $capitaisQIV[1] == 500000))
                                    @php $currentLine++; @endphp
                                    <tr>
                                        <td class="tbl-cobertura-seguros-cell tbl-cobertura-item" style="border-left: none !important; border-top: none !important; {{ $currentLine == $totalLines ? '' : 'border-bottom: none !important;' }}">
                                            <img src="img/cotacao/icone-item.jpg" class="icone_item" alt=""> {{ ucsentences($sub['name']) }}-Ligeiros
                                        </td>
                                        <td class="tbl-cobertura-seguros-cell tbl-cobertura-capital" style="border-top: none !important; border-right: none !important; {{ $currentLine == $totalLines ? '' : 'border-bottom: none !important;' }}">
                                            {{ number_format(500000, 0, ',', '.') }} kz
                                        </td>
                                    </tr>
                                @endif
                                
                                @php
                                    
                                @endphp
                                <!-- QIV Pesados -->
                                @if((isset($capitaisQIV[1]) && $capitaisQIV[1] == 750000 || temPesados($objects)) || (isset($capitaisQIV[0]) && $capitaisQIV[0] == 750000))
                                    @php $currentLine++; @endphp
                                    <tr>
                                        <td class="tbl-cobertura-seguros-cell tbl-cobertura-item" style="border-left: none !important; border-top: none !important; {{ $currentLine == $totalLines ? '' : 'border-bottom: none !important;' }}">
                                            <img src="img/cotacao/icone-item.jpg" class="icone_item" alt="">{{  ucsentences( $sub['name']) }}-Pesados
                                        </td>
                                        <td class="tbl-cobertura-seguros-cell tbl-cobertura-capital" style="border-top: none !important; border-right: none !important; {{ $currentLine == $totalLines ? '' : 'border-bottom: none !important;' }}">
                                             
                                            {{ number_format(750000, 0, ',', '.') }} kz
                                        </td>
                                    </tr>
                                @endif
                            @else
                                <!-- Outras coberturas -->
                                @php $currentLine++; @endphp
                                <tr>
                                    <td class="tbl-cobertura-seguros-cell tbl-cobertura-item" style="border-left: none !important; border-top: none !important; {{ $currentLine == $totalLines ? '' : 'border-bottom: none !important;' }}">
                                        <img src="img/cotacao/icone-item.jpg" class="icone_item" alt="">   {{ ucsentences($sub['name']) }}
                                    </td>
                                    <td class="tbl-cobertura-seguros-cell tbl-cobertura-capital" style="border-top: none !important; border-right: none !important; {{ $currentLine == $totalLines ? '' : 'border-bottom: none !important;' }}">
                                        @if ($sub['rate_type'] !== 'ZERO')
                                            {{ (isset($qli['coverage_perc']) && count($objects) && $selectedBranch['code'] == 90 < 2) ? $qli['coverage_perc'] . ' Kz' : 'Conforme anexos' }}
                                        @elseif($coverage['short_name'] == "OC VIATURAS")
                                            @php
                                                $qliGroup = findQLI($QLI, $coverage);
                                                $capitalOV = getCapitalToCoverageTypeZero($sub, $qliGroup['ratesObject'], $qliGroup["premium_value"], $qliGroup["rate_id"]);
                                            @endphp
                                            {{ number_format(arredondarBase5($capitalOV), 0, ',', '.') }} kz
                                        @elseif($coverage['rate_type'] == 'ZERO' && $sub['rate_type'] == 'ZERO')
                                            {{ isset($qli['coverage_perc']) ? number_format($qli['coverage_perc'], 0, ',', '.') . ' kz' : '0 kz' }}
                                        @endif
                                    </td>
                                </tr>
                            @endif
                        @endif
                    @endforeach
                @endif
            @endif
            @php array_push($controlDisplayeCoveragesCCS, $coverage['id']); @endphp
        @endforeach
    </tbody>
</table>
        </td>
    </tr>
</table>
        @endif

        @if (!$caeSelected)

            <table class="text-block mt-text-block" >
                <tr>
                    <th style="color: white">.</th>
                </tr>
              
                 <tr>
                    <th style="color: white">.</th>
                        
                </tr>
                  <tr>
                    <th style="color: white">.</th>
                        
                </tr>
                <tr>
                    <td>
                        <p  style="color: white">.</p>
                        *O valor seguro deverá ser automaticamente alterado de acordo com a tabela de desvalorização
                        existente para o seguro Danos Próprios sendo o respectivo prémio ajustado à desvalorização do
                        valor seguro.</td>
                </tr>
            </table>
        @endif

        @if (!$caeSelected)
        {{-- @php
            dd($coverages)
        @endphp --}}
        {{-- @if(count($coverages)>=3  ) --}}
        {{-- <br>
             <br>
                  <br> --}}
                 
{{-- <small style="color:white">----</small>
<small style="color:white">----</small> --}}
{{-- @endif --}}
            <table class="text-block mt-text-block" >
                
                <tr><td style="color:white">.</td></tr>
                <tr><td style="color:white" >.</td></tr>
                <tr><td style="color:white">.</td></tr>
                <tr><td style="color:white">.</td></tr>



                <tr>
                    <td class="left-text bold">Franquias Deduzíveis por Sinistros</td>

                    <td class="right-text">
                   <table class="tbl-cobertura-seguros">
    <thead>
        <tr>
            <th class="tbl-cobertura-seguros-header text-left" style="width: 70%; border-left-color: transparent !important;;background:red;   padding-left: 5px;">
                Coberturas
            </th>
            <th class="tbl-cobertura-seguros-header" style="width: 30%; border-right-color: transparent !important;">
                Capitais Seguros
            </th>
        </tr>
    </thead>
    
    <tbody>
        @php
            // Calcular total de linhas para detectar a última
            $totalLines = 2; // Responsabilidade Civil + Facultativa
            foreach ($coverages as $coverage) {
                if (!$coverage['group_id'] && !in_array($coverage['id'], $controlDisplayeCoveragesFDS) && selectedCoverage($QLI, $coverage['id']) && $coverage['short_name'] != 'R.CIVIL') {
                    $subcoverages = groupCoverages($coverage['id'], $coverages);
                    foreach ($subcoverages as $sub) {
                        if (selectedCoverage($QLI, $sub['id']) || $sub['rate_type'] == 'ZERO') {
                            $totalLines++;
                        }
                    }
                }
            }
            $currentLine = 0;
        @endphp
        
        <!-- Cobertura Obrigatória (Lei 35/09) -->
        <tr>
            <td class="tbl-cobertura-seguros-cell tbl-cobertura-secao-titulo" style="border-left: none !important; border-top: none !important; border-bottom: none !important;" colspan="1">
                <strong>Cobertura Obrigatória (Lei 35/09)</strong>
            </td>
        </tr>
        
        @php $currentLine++; @endphp
        <tr>
            <td class="tbl-cobertura-seguros-cell tbl-cobertura-item" style="border-left: none !important; border-top: none !important; {{ $currentLine == $totalLines ? '' : 'border-bottom: none !important;' }}">
                <img src="img/cotacao/icone-item.jpg" class="icone_item" alt=""> Responsabilidade Civil
            </td>
            <td class="tbl-cobertura-seguros-cell tbl-cobertura-capital" style="border-top: none !important; border-right: none !important; {{ $currentLine == $totalLines ? '' : 'border-bottom: none !important;' }}">
                Sem franquia
            </td>
        </tr>
        
        @php $currentLine++; @endphp
        <tr>
            <td class="tbl-cobertura-seguros-cell tbl-cobertura-item" style="border-left: none !important; border-top: none !important; {{ $currentLine == $totalLines ? '' : 'border-bottom: none !important;' }}">
                <img src="img/cotacao/icone-item.jpg" class="icone_item" alt=""> Responsabilidade Civil Facultativa
            </td>
            <td class="tbl-cobertura-seguros-cell tbl-cobertura-capital" style="border-top: none !important; border-right: none !important; {{ $currentLine == $totalLines ? '' : 'border-bottom: none !important;' }}">
                Sem franquia
            </td>
        </tr>
        
        <!-- Outras coberturas dinâmicas -->
        @foreach ($coverages as $coverage)
            @if (!$coverage['group_id'] &&
                 !in_array($coverage['id'], $controlDisplayeCoveragesFDS) &&
                 selectedCoverage($QLI, $coverage['id']) && 
                 $coverage['short_name'] != 'R.CIVIL')
                
                @php
                    $subcoverages = groupCoverages($coverage['id'], $coverages);
                    $hasValidSubs = false;
                    
                    foreach ($subcoverages as $sub) {
                        if (selectedCoverage($QLI, $sub['id']) || $sub['rate_type'] == 'ZERO') {
                            $hasValidSubs = true;
                            break;
                        }
                    }
                @endphp
                
                @if ($hasValidSubs)
                    <!-- Título da seção -->
                    <tr>
                        <td class="tbl-cobertura-seguros-cell tbl-cobertura-secao-titulo" style="border-left: none !important;  border-bottom: none !important;" colspan="1">
                            <strong>{{ ucsentences($coverage['name']) }}</strong>
                        </td>
                           <td class="tbl-cobertura-seguros-cell tbl-cobertura-secao-titulo" style="border-left: none !important; border-bottom: none !important;border-right: none !important;" colspan="1">
                          
                        </td>
                    </tr>
                    
                    <!-- Itens da seção -->
                    @foreach ($subcoverages as $sub)
                        @if (selectedCoverage($QLI, $sub['id']) || $sub['rate_type'] == 'ZERO')
                            @php
                                $currentLine++;
                                $deductible = null;
                                $qli = findQLI($QLI, $sub);
                                if (isset($qli['deductible_id']) && $qli['deductible_id']) {
                                    $deductible = findDeductible($qli['deductible_id']);
                                }
                            @endphp
                            
                            <tr>
                                <td class="tbl-cobertura-seguros-cell tbl-cobertura-item" style="border-left: none !important; border-top: none !important; {{ $currentLine == $totalLines ? '' : 'border-bottom: none !important;' }}">
                                    <img src="img/cotacao/icone-item.jpg" class="icone_item" alt=""> {{   ucsentences($sub['name'] )}}
                                </td>
                                <td class="tbl-cobertura-seguros-cell tbl-cobertura-capital" style="border-top: none !important; border-right: none !important; {{ $currentLine == $totalLines ? '' : 'border-bottom: none !important;' }}">
                                    @if ($deductible)
                                        {{ \Illuminate\Support\Str::limit($deductible->details, 20) }}
                                    @else
                                        Sem franquia
                                    @endif
                                </td>
                            </tr>
                        @endif
                    @endforeach
                @endif
            @endif
            @php array_push($controlDisplayeCoveragesFDS, $coverage['id']); @endphp
        @endforeach
    </tbody>
</table>
                    </td>

                </tr>
            </table>
            <br><br>
        @endif
        @if (!empty($selectedConditions))
            <table class="text-block mt-text-block">
                <tr>
                    <td class="left-text bold">Condições Especiais</td>
                    <td class="right-text">
                        @foreach ($selectedConditions as $condition)
                            <p class="condition-item">
                                <span class="condition-order"> - </span>
                                {{ $condition['long_text'] }}
                                @if (!empty($condition['long_text']))
                                    {{-- <br> --}}
                                    {{-- <small class="text-muted">{{ $condition['long_text'] }}</small> --}}
                                @endif
                            </p>
                            {{-- @if (!$loop->last)
                                <hr class="condition-divider">
                            @endif --}}
                        @endforeach
                    </td>
                </tr>
            </table>
        @else
            {{-- <table class="text-block mt-text-block">
                <tr>
                    <td class="left-text bold">Condições Especiais</td>
                    <td class="right-text">
                        <p>Não aplicável</p>
                    </td>
                </tr>
            </table> --}}
        @endif

        @if ($caeSelected)
            <table class="text-block mt-text-block">
                <tr>
                    <td class="left-text bold">Capitais a Segurar</td>
                    <td class="right-text">
                        @php
                            $insurance_capital = 0;
                            if (count($objects) === 1) {
                                $insurance_capital = isset($objects[0]['capital_value'])
                                    ? $objects[0]['capital_value']
                                    : $objects[0]['premium_value'];
                            } else {
                                $insurance_capital = array_reduce(
                                    $objects,
                                    function ($total, $item) {
                                        return $total + ($item['capital_value'] ?? 0);
                                    },
                                    0,
                                );
                            }
                        @endphp

                        <p class="bold">{{ number_format($insurance_capital, 0, ',', '.') }}
                            KZ ({{ $objects[0]['number_of_months'] }} meses)</p>
                        <p>A determinação da <strong><u>retribuição segura</u></strong> (salário seguro), valor sobre o
                            qual são calculadas as responsabilidades da seguradora, é sempre da responsabilidade do
                            Tomador e deverá corresponder, tanto na data da celebração do contrato como em qualquer
                            momento da sua vigência, a tudo o que a loi considera como elemento integrante do salário,
                            incluindo subsídio de alimentação, bem como outras prestações em espécie ou dinheiro que
                            revistam carácter de regularidade1 e ainda os subsídios de férias e natal.</p>
                    </td>
                </tr>
            </table>
        @endif
        @if ($caeSelected)
            <table class="text-block mt-text-block">
                <tr>
                    <td class="left-text bold">Franquias Deduzíveis por Sinistros</td>

                    <td class="right-text">
                        <p>Neste tipo de contrato não são aplicadas franquias</p>
                    </td>

                </tr>
            </table>
        @endif
        
        <table class="text-block mt-text-block">
            <tr>
                <td class="left-text bold">Taxa e Prémio Total</td>
                <td class="right-text">
                    @if ($caeSelected)
                        @php
                            $simpleRate =
                                $caeSelected['work_accident'] - $caeSelected['work_accident'] * ($discountPerc / 100);
                            $simpleRate = $simpleRate / 100;
                        @endphp
                        <p>Para cobertura dos riscos acima indicados e sujeito aos períodos e capitais acima referidos,
                            será aplicada a seguinte taxa simples:</p>
                        <br>
                        <p>
                            &nbsp;&nbsp;<img src="img/cotacao/icone-item.jpg" class="icone_item" alt="">
                            <strong>{{ number_format($simpleRate * 100, 2, ',', '') }}% </strong>
                            a incidir sobre o Capital a Segurar (Salários Seguros)
                        </p>

                        <br>
                        <p>Ao prémio simples assim calculado serão acrescidos os encargos legais em vigor à data da
                            emissão, sendo, actualmente, de 20% e dos impostos legais quando aplicável.</p>
                        <br>
                        <table>
                            <tr>
                                <th style="text-align: right;font-weight:normal">Moeda: Kz</th>
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
                                                    @php
                                                        $premioTotalAnual = 'Anual';
                                                    @endphp
                                                    <strong>Prémio Total Anual</strong>
                                                @break

                                                @case('premioTotalSemestre')
                                                    @php
                                                        $premioTotalSemestre = 'Semestral';
                                                    @endphp
                                                    <strong>Prémio Total Semestral</strong>
                                                @break

                                                @case('premioTotalTrimestre')
                                                    @php
                                                        $premioTotalTrimestre = 'Trimestral';
                                                    @endphp
                                                    <strong>Prémio Total Trimestral</strong>
                                                @break

                                                @case('premioTotalMensal')
                                                    @php
                                                        $premioTotalMensal = 'Mensal';
                                                    @endphp
                                                    <strong>Prémio Total Mensal</strong>
                                                @break

                                                @default
                                                    {{ $key }}
                                            @endswitch
                                        </td>
                                        <td class="text-right">
                                            @if (Str::startsWith($key, 'premioTotal'))
                                                <strong>{{ number_format(arredondarBase5($item['value']), 0, ',', ' ') }}</strong>
                                            @else
                                                {{ number_format(arredondarBase5($item['value']), 0, ',', ' ') }}
                                            @endif
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </table>
                    @else
                        <p>Conforme anexos A, B e C, à presente Cotação</p>
                    @endif
                </td>
            </tr>
        </table>

        <table class="text-block mt-text-block" style="">
            <tr>
                <td class="left-text bold">Modalidade de Pagamento</td>
                <td class="right-text">
                    @if (!$caeSelected)
                        <p>Anual Renovável, sendo que caso se opte por fraccionar o prémio serão imputados os respectivos
                            encargos de fraccionamento.</p>
                    @else
                        @php
                            $modalidade = '';
                        @endphp
                        <p>
                            @isset($premioTotalAnual)
                                @php
                                    $modalidade .= 'Anual';
                                @endphp
                            @endisset
                            @isset($premioTotalSemestre)
                                @php
                                    $modalidade .= '/Semestral';
                                @endphp
                            @endisset
                            @isset($premioTotalTrimestre)
                                @php
                                    $modalidade .= '/Trimestral';
                                @endphp
                            @endisset

                            @isset($premioTotalMensal)
                                @php
                                    $modalidade .= '/Mensal';
                                @endphp
                            @endisset
                            {{ $modalidade }}
                        </p>
                    @endif
                </td>
            </tr>
        </table>

        @if (!$caeSelected)
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
        @endif

        <br>
        <table class="text-block mt-text-block" style="">
            <tr>
                <td class="left-text bold">Outras Condições Aplicáveis</td>
                <td class="right-text">
                    <p>Estas condições são válidas por 30 dias.</p>
                </td>
            </tr>
        </table>

        @for ($countBr = 1; $countBr <= 100; $countBr++)
            <br>
        @endfor

        <div class="important-info">
            <h5 style="margin-bottom:0px">INFORMAÇÃO IMPORTANTE</h5>
            <p class="info-importante" style="margin-top:0px">
                Os valores da presente cotação assentam num conjunto de pressupostos, como sejam os dados que nos
                indicou e a inexistência de factores de agravamento de risco, pressupostos estes sujeitos a confirmação
                por
                parte da Seguradora. <br><br>
                A Seguradora pode solicitar informação adicional e/ou confirmar os dados indicados, podendo daqui
                resultar
                a não verificação, no caso concreto, dos pressupostos sobre os quais assentou a presente cotação e,
                consequentemente, a não-aceitação do seguro nas condições referidas na mesma. Deste modo, importa notar
                que o prémio desta cotação e a verificação dos respectivos pressupostos carecem de confirmação e
                aceitação
                por parte da Seguradora. <br><br>
                Para os dados indicados, apurou-se o prémio acima que inclui cargas fiscais e parafiscais em vigor à
                data da
                cotação. Qualquer alteração legal posterior às cargas fiscais ou parafiscais aplicáveis, terá de ser
                considerada
                pela Seguradora aquando da emissão do contrato. Esta cotação tem a validade acima indicada, sem prejuízo
                da
                possibilidade, aquando da emissão do contrato, de alteração do prémio apurado caso ocorra uma alteração
                legal posterior à data da cotação que determine a modificação das cargas fiscais ou parafiscais
                aplicáveis. <br><br>

                A informação constante deste documento não dispensa a consulta da informação pré-contratual e contratual
                legalmente exigida.
            </p>
            <h5> BIC Seguros, S. A.</h5>
        </div>

        @if (!$caeSelected )
        @for ($countBr = 1; $countBr <= 20; $countBr++)
                <br>
            @endfor
      @php
       $coverages = sortCoverages($coverages);
       $cotNumber= ltrim($cotNumber, '0');
       $hasAnnexA=0;
    @endphp
    


@php
    $quotationTable = $quotation['table'] ?? $quotation['table_code'] ?? null;
@endphp
@if(!isset($quotation) || isset($quotationTable) && $quotationTable=='A')
@php
    $hasAnnexA=1;
@endphp
 <br>
 <br>
  <br>
 <br>
  <br>
 <br>
  <br>
  <br>
  <br>
 <br>
           {{--  <th colspan="{{ $colspan }}" class="text-left" style="font-size: 14pt; padding-bottom: 30px;padding-top: 10px;">  --}}
               <div class="bold sublinhado color-black" >
  ANEXO {{ $quotationTable ?? 'A' }}
</div>

                 {{--  <br>  --}}
  
               {{--  </th>  --}}
        <table class="tb_ccs2"
       style="
            width: 100%;
            border-collapse: collapse;
            margin-top: -7px;
            table-layout: fixed;
       "
       cellpadding="4"
       cellspacing="0">

    <thead>

        <!-- ESPAÇAMENTO SUPERIOR (MANTIDO) -->
        <tr rowspan="2">
            <th colspan="20"
                style="
                    border: none;
                    padding: 0;
                    height: 8px;
                    line-height: 8px;
                    font-size: 1px;
                ">
                &nbsp;
            </th>
        </tr>

        <tr rowspan="2">
            <th colspan="20"
                style="
                    border: none;
                    padding: 0;
                    height: 8px;
                    line-height: 8px;
                    font-size: 1px;
                ">
                &nbsp;
            </th>
        </tr>

        <!-- CÁLCULO DE COLSPAN -->
        @php
            $colspan = 10 + (isset($coverages) ? count($coverages) : 0);
            $coverages = sortCoverages($coverages);
        @endphp

        <!-- TÍTULO -->
        <tr>
            <th colspan="{{ $colspan + 2 }}"
                class="cell-font-8"
                style="
                    width: 100%;
                    max-width: 100%;
                    overflow: hidden;
                    white-space: normal;
                    word-wrap: break-word;
                    text-align: center;
                ">
                <strong>
                    {{ generateQuoteTitle($entity, $coverages, $cotNumber, $version_number, $QLI) }}
                </strong>
            </th>
        </tr>

        <!-- CABEÇALHO DA TABELA -->
        @if (isset($attributesTablePDF['titlesObjectsHeadPDF']) && isset($coverages) && isset($objects))
        <tr>
            <th>Nº</th>

            @foreach ($attributesTablePDF['titlesObjectsHeadPDF'] as $td)
                <th class="cell-font-8">
                    <strong><?php echo $td['valuetd'] ?? '' ?></strong>
                </th>
            @endforeach

            <th class="cell-font-8">
                <strong>Limite RC <br>(KZ)</strong>
            </th>

            @foreach (filterCoverages($coverages) as $coverage)
                @if (
                    $coverage['short_name'] != 'R.CIVIL' &&
                    (selectedCoverage($QLI, $coverage['id']) && !$coverage['group_id']
                    || $coverage['short_name'] == 'R.CIVIL OBR.')
                )
                    <th class="cell-font-8 text-center">
                        <strong>
                            <?php echo getPremioOuLimite($coverage['name']) ?? ''; ?>
                        </strong>
                    </th>
                @endif
            @endforeach

            <th class="cell-font-8">
                <strong>Prémio Total <br>(KZ)</strong>
            </th>

            @if(isset($premiosGlobais['premioTotalSemestre']['status']) && $premiosGlobais['premioTotalSemestre']['status'])
                <th class="cell-font-8">
                    <strong>Prémio Semestral <br>(KZ)</strong>
                </th>
            @endif

            @if(isset($premiosGlobais['premioTotalTrimestre']['status']) && $premiosGlobais['premioTotalTrimestre']['status'])
                <th class="cell-font-8">
                    <strong>Prémio Trimestral <br>(KZ)</strong>
                </th>
            @endif
        </tr>
        @endif

    </thead>

    <tbody>

        @if (isset($attributesTablePDF['titlesObjectsHeadPDF']) && isset($coverages) && isset($objects))
        @foreach ($objects as $index => $object)

        <tr>
            <td>{{ $index + 1 }}</td>

            @foreach ($attributesTablePDF['titlesObjectsHeadPDF'] as $td)
                <td class="text-center cell-font-8">
                    @if ($td['name'] === 'capital_value' || $td['name'] === 'engine_min')
                        @if ($td['name'] === 'engine_min' && isset($object['cilinder_cc']))
                            {{ number_format($object['cilinder_cc'], 0, ',', '.') }}
                        @else
                            {{ number_format($object[$td['name']] ?? 0, 0, ',', '.') }}
                        @endif
                    @elseif($td['name'] === 'brand_model')
                        {{ $object['brand'] ?? '' }} {{ $object[$td['name']] ?? '' }}
                    @else
                        {{ $object[$td['name']] ?? '' }}
                    @endif
                </td>
            @endforeach

            <td class="text-center cell-font-8">
                {{ number_format(checkLimitRC($object, $QLI, $annexsAdjustments), 0, ',', ' ') }}
            </td>

	            @foreach (filterCoverages($coverages) as $coverage)
	                @if (
	                    $coverage['short_name'] != 'R.CIVIL' &&
	                    (selectedCoverage($QLI, $coverage['id']) && !$coverage['group_id']
	                    || $coverage['short_name'] == 'R.CIVIL OBR.')
	                )
	                    <td class="text-center cell-font-8">
	                        {{ number_format(calculatePremiumWithSurcharge(
	                            $object,
	                            $coverage,
	                            $QLI,
	                            $annexsAdjustments,
	                            $quotationTable ?? 'A',
	                            $quotation['quotation']['id'] ?? $quotation['id'] ?? null,
	                            'v' . ((int) ($version_number ?? 1))
	                        ), 0, ',', ' ') }}
	                    </td>
	                @endif
	            @endforeach

            @php
                $premiumByCoverage = checkPremiumTotal(
                    $object,
                    filterCoverages($coverages),
                    $QLI,
                    $annexsAdjustments
                );
            @endphp

            <td class="text-center cell-font-8">
                {{ number_format($premiumByCoverage, 0, ',', ' ') }}
            </td>

            @if(isset($premiosGlobais['premioTotalSemestre']['status']) && $premiosGlobais['premioTotalSemestre']['status'])
                <td class="text-center cell-font-8">
                    {{ number_format(calcularPremios($premiumByCoverage,'total_semestre'), 0, ',', ' ') }}
                </td>
            @endif

            @if(isset($premiosGlobais['premioTotalTrimestre']['status']) && $premiosGlobais['premioTotalTrimestre']['status'])
                <td class="text-center cell-font-8">
                    {{ number_format(calcularPremios($premiumByCoverage,'total_trimestre'), 0, ',', ' ') }}
                </td>
            @endif
        </tr>

        @endforeach
        @endif

    </tbody>

    <tfoot>

        @if (isset($coverages))
        <tr>
            <td colspan="{{ (isset($attributesTablePDF['titlesObjectsHeadPDF']) ? count($attributesTablePDF['titlesObjectsHeadPDF']) : 0) + 2 }}"
                class="cell-font-8"
                style="text-align: right;">
                <strong>Total Anual</strong>
            </td>

            @php
                $totalGeral = 0;
                $annexA = $quotationTable ?? 'A';
            @endphp

            @foreach (filterCoverages($coverages) as $coverage)
                @if (
                    $coverage['short_name'] != 'R.CIVIL' &&
                    (selectedCoverage($QLI, $coverage['id']) && !$coverage['group_id']
                    || $coverage['short_name'] == 'R.CIVIL OBR.')
                )
                    @php
	                        $totalCoverage = calculateTotalCoverage(
	                            $objects,
	                            $coverage,
	                            $QLI,
	                            $annexsAdjustments,
	                            $annexA,
	                            $quotation['quotation']['id'] ?? $quotation['id'] ?? null,
	                            'v' . ((int) ($version_number ?? 1))
	                        );
                        $totalGeral += $totalCoverage;
                    @endphp

                    <td class="text-center cell-font-8">
                        <strong>{{ number_format($totalCoverage, 0, ',', ' ') }}</strong>
                    </td>
                @endif
            @endforeach

            <td class="text-center cell-font-8">
                <strong>{{ number_format($totalGeral, 0, ',', ' ') }}</strong>
            </td>

            @if(isset($premiosGlobais['premioTotalSemestre']['status']) && $premiosGlobais['premioTotalSemestre']['status'])
                <td class="text-center cell-font-8">
                    <strong>{{ number_format(calcularPremios($totalGeral,'total_semestre'), 0, ',', ' ') }}</strong>
                </td>
            @endif

            @if(isset($premiosGlobais['premioTotalTrimestre']['status']) && $premiosGlobais['premioTotalTrimestre']['status'])
                <td class="text-center cell-font-8">
                    <strong>{{ number_format(calcularPremios($totalGeral,'total_trimestre'), 0, ',', ' ') }}</strong>
                </td>
            @endif
        </tr>
        @endif

    </tfoot>

</table>

@endif

  @for ($countBr = 1; $countBr <= 50; $countBr++)
                <br>
            @endfor

@foreach ($annexsAdjustments as  $annex =>$annexsAdjustment)
@php
    $length=$hasAnnexA?5:40;
@endphp

@if($annex != 'A')

  

                 {{--  <br>  --}}
  
               {{--  </th>  --}}
       
               <div class="bold sublinhado color-black" style="padding-bottom: 4px;">
ANEXO {{ isset($quotation) && $quotationTable != 'A' ? $quotationTable : $annex }}
</div>
<table class="tb_ccs2"
       style="
            width: 100%;
            border-collapse: collapse;
            margin-top: -7px;
            table-layout: fixed;
       "
       cellpadding="4"
       cellspacing="0">

    <thead>

        <!-- ESPAÇAMENTO SUPERIOR CONTROLADO -->
        <tr rowspan="2">
            <th colspan="20"
                style="
                    border: none;
                    padding: 0;
                    height: 8px;
                    line-height: 8px;
                    font-size: 1px;
                ">
                &nbsp;
            </th>
        </tr>

        <tr rowspan="2">
            <th colspan="20"
                style="
                    border: none;
                    padding: 0;
                    height: 8px;
                    line-height: 8px;
                    font-size: 1px;
                ">
                &nbsp;
            </th>
        </tr>

        @php
      
            $colspan = 10 + (isset($coverages) ? count($coverages) : 0);
            $coverages = sortCoverages($coverages);
        @endphp

        <!-- TÍTULO (CORRIGIDO) -->
        <tr>
            <th colspan="{{ $colspan + 2 }}"
                class="cell-font-8"
                style="
                    width: 100%;
                    max-width: 100%;
                    overflow: hidden;
                    white-space: normal;
                    word-wrap: break-word;
                    text-align: center;
                ">
                <strong>
                    {{ generateQuoteTitle($entity, $coverages, $cotNumber, $version_number, $QLI) }}
                </strong>
            </th>
        </tr>

        <!-- CABEÇALHO -->
        @if (isset($attributesTablePDF['titlesObjectsHeadPDF']) && isset($coverages) && isset($objects))
        <tr>
            <th class="text-center py-1 cell-font-8">Nº</th>

            @foreach ($attributesTablePDF['titlesObjectsHeadPDF'] as $td)
                <th class="text-center py-1 cell-font-8">
                    <strong><?php echo $td['valuetd'] ?? '' ?></strong>
                </th>
            @endforeach

            <th class="text-center py-1 cell-font-8">
                <strong>Limite RC <br>(KZ)</strong>
            </th>

            @foreach (filterCoverages($coverages) as $coverage)
                @if (
                    $coverage['short_name'] != 'R.CIVIL' &&
                    (selectedCoverage($QLI, $coverage['id']) && !$coverage['group_id']
                    || $coverage['short_name'] == 'R.CIVIL OBR.')
                )
                    <th class="text-center py-1 cell-font-8">
                        <strong><?php echo getPremioOuLimite($coverage['name']) ?? '' ?></strong>
                    </th>
                @endif
            @endforeach

            <th class="text-center py-1 cell-font-8">
                <strong>Prémio Total <br>(KZ)</strong>
            </th>

            @if(isset($premiosGlobais['premioTotalSemestre']['status']) && $premiosGlobais['premioTotalSemestre']['status'])
                <th class="text-center py-1 cell-font-8">
                    <strong>Prémio Semestral <br>(KZ)</strong>
                </th>
            @endif

            @if(isset($premiosGlobais['premioTotalTrimestre']['status']) && $premiosGlobais['premioTotalTrimestre']['status'])
                <th class="text-center py-1 cell-font-8">
                    <strong>Prémio Trimestral <br>(KZ)</strong>
                </th>
            @endif
        </tr>
        @endif

    </thead>

    <tbody>

        @if (isset($attributesTablePDF['titlesObjectsHeadPDF']) && isset($coverages) && isset($objects))
        @foreach ($objects as $index => $object)

        @php
	            $premiumTotal = calculateObjectPremiumTotal(
	                $object,
	                $coverages,
	                $QLI,
	                $annexsAdjustments,
	                $annex,
	                $quotation['quotation']['id'] ?? $quotation['id'] ?? null,
	                'v' . ((int) ($version_number ?? 1))
	            );
        @endphp

        <tr>
            <td class="cell-font-8">{{ $index + 1 }}</td>

            @foreach ($attributesTablePDF['titlesObjectsHeadPDF'] as $td)
                <td class="text-center cell-font-8">
                    {{ formatObjectValue($object, $td) }}
                </td>
            @endforeach

            <td class="text-center py-1 cell-font-8">
	                {{ number_format(rcFacBC($object,
	                            $coverage,
	                            $annex,
	                            $quotation['quotation']['id'],
	                            'v' . ((int) ($version_number ?? 1))), 0, ',', ' ') }}
	            </td>

            @foreach (filterCoverages($coverages) as $coverage)
                @if (
                    $coverage['short_name'] != 'R.CIVIL' &&
                    (selectedCoverage($QLI, $coverage['id']) && !$coverage['group_id']
                    || $coverage['short_name'] == 'R.CIVIL OBR.')
                )
                    @php
           
	                        $premium = calculatePremiumWithSurcharge(
	                            $object,
	                            $coverage,
	                            $QLI,
	                            $annexsAdjustments,
	                            $annex,
	                            $quotation['quotation']['id'],
	                            'v' . ((int) ($version_number ?? 1))
	                        );
                       
                    @endphp

                    <td class="text-center cell-font-8 py-1">
                        {{ number_format($premium, 0, ',', ' ') }}
                    </td>
                @endif
            @endforeach

            <td class="text-center cell-font-8 py-1">
                {{ number_format($premiumTotal, 0, ',', ' ') }}
            </td>

            @if(isset($premiosGlobais['premioTotalSemestre']['status']) && $premiosGlobais['premioTotalSemestre']['status'])
                <td class="text-center cell-font-8 py-1">
                    {{ number_format(calcularPremios($premiumTotal,'total_semestre'), 0, ',', ' ') }}
                </td>
            @endif

            @if(isset($premiosGlobais['premioTotalTrimestre']['status']) && $premiosGlobais['premioTotalTrimestre']['status'])
                <td class="text-center cell-font-8 py-1">
                    {{ number_format(calcularPremios($premiumTotal,'total_trimestre'), 0, ',', ' ') }}
                </td>
            @endif
        </tr>

        @endforeach
        @endif

    </tbody>

    <tfoot>

        @if (isset($coverages))
        <tr>
            <td colspan="{{ (isset($attributesTablePDF['titlesObjectsHeadPDF']) ? count($attributesTablePDF['titlesObjectsHeadPDF']) : 0) + 2 }}"
                class="cell-font-8"
                style="text-align: right;">
                <strong>Total Anual</strong>
            </td>

            @php $totalGeral = 0; @endphp

            @foreach (filterCoverages($coverages) as $coverage)
                @if (
                    $coverage['short_name'] != 'R.CIVIL' &&
                    (selectedCoverage($QLI, $coverage['id']) && !$coverage['group_id']
                    || $coverage['short_name'] == 'R.CIVIL OBR.')
                )
                    @php
	                        $totalCoverage = calculateTotalCoverage(
	                            $objects,
	                            $coverage,
	                            $QLI,
	                            $annexsAdjustments,
	                            $annex,
	                            $quotation['quotation']['id'] ?? $quotation['id'] ?? null,
	                            'v' . ((int) ($version_number ?? 1))
	                        );
                        $totalGeral += $totalCoverage;
                    @endphp

                    <td class="text-center cell-font-8">
                        <strong>{{ number_format($totalCoverage, 0, ',', ' ') }}</strong>
                    </td>
                @endif
            @endforeach

            <td class="text-center cell-font-8">
                <strong>{{ number_format($totalGeral, 0, ',', ' ') }}</strong>
            </td>

            @if(isset($premiosGlobais['premioTotalSemestre']['status']) && $premiosGlobais['premioTotalSemestre']['status'])
                <td class="text-center cell-font-8">
                    <strong>{{ number_format(calcularPremios($totalGeral,'total_semestre'), 0, ',', ' ') }}</strong>
                </td>
            @endif

            @if(isset($premiosGlobais['premioTotalTrimestre']['status']) && $premiosGlobais['premioTotalTrimestre']['status'])
                <td class="text-center cell-font-8">
                    <strong>{{ number_format(calcularPremios($totalGeral,'total_trimestre'), 0, ',', ' ') }}</strong>
                </td>
            @endif
        </tr>
        @endif

    </tfoot>

</table>

@endif
@if($annex!='C')
     @for ($countBr = 1; $countBr <= 54; $countBr++)
                <br>
            @endfor
   @endif
@endforeach
        @endif
    </div>
</body>
</html>
