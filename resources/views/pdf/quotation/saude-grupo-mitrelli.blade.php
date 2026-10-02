<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">


</head>

<body>
@php
    $templates = collect($templates)->where("name", "MITRELLI")->toArray();

@endphp
    <div class="logo-container"><img src="img/cotacao/icone_capa.jpeg" class="icone_capa" alt=""></div>
    <div class="pdf-container" id="pdfContent">
        <!-- Page 1 -->
        <div class="page-border"></div>
        <div class="header">
            <img class="title-img" src="img/cotacao/title.png" alt="">
            <h5 class="area-corrector">ÁREA DE SAÚDE</h5>
        </div>

        <div class="box-cover">
            <img class="cover" src="img/cotacao/quote_SAUDE_branch.png" alt="">
        </div>
        @php
            $cotNumber = '-----';
            $quotationNumberValue = $quotation['quotation_number'] ?? $quotation_number ?? null;
            if ($quotationNumberValue) {
                $cotNumber = formatarNumeroCotacao($quotationNumberValue);
            }
        @endphp

        <div class="proposal-title">
            <h1 class="proposal-title-title color-black fw-b">
                <strong>COTAÇÃO DE SEGURO {{ $selectedBranch['description'] ?? '' }}</strong>
            </h1>
            <h5 class="color-black"> Cotação nº {{ $cotNumber . 'A' }}@if ($version_number > 1)
                _V{{ $version_number }}
            @endif/{{ dataPortugues() }}</h5>
        </div>

        <div class="intro-text">
            <p>
                Agradecemos a oportunidade concedida, ao BIC Seguros, SA para apresentar esta proposta de colaboração, a
                qual fica sujeita a eventuais reajustamentos que sejam julgados convenientes. A nossa sugestão é que
                considerem o conjunto dos serviços oferecidos, e dos meios técnicos e humanos que colocamos à vossa
                disposição, como as ferramentas que permitirão obter um serviço de Excelência, dando assim cumprimento
                às vossas expectativas.
                <br><br>
                Será para nós uma honra colaborar com tão prestigiada entidade na implementação e/ou eventual
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
            <p class="fw-b color-black" style="text-align: right"> Luanda, {{ $dataFormatada }}</p>
        </div>

        <!-- Page 2 -->
        <div class="page-title color-black">COTAÇÃO DE SEGURO {{ $selectedBranch['description'] ?? '' }}</div>
        <div class="page-sub-title red" style="margin-top: 3px;">COTAÇÃO Nº {{ $cotNumber }}/{{ dataPortugues() }}</div>

        <table class="text-block">
            <tr>
                <td class="left-text bold">Tomador do Seguro</td>
                <td class="right-text">
                    <p><strong>{{ $entity['name'] ?? '' }}.</strong><br>
                        Luanda, Angola<br>
                        NIF: {{ $entity['nif'] ?? 'A indicar' }}.
                    </p>
                </td>
            </tr>
        </table>

        <table class="text-block">
            <tr>
                <td class="left-text bold">Código de Actividade Económica (CAE)</td>
                <td class="right-text">
                    <p>A indicar</p>
                </td>
            </tr>
        </table>

        <table class="text-block">
            <tr>
                <td class="left-text bold">Pessoas Seguras</td>
                <td class="right-text">
                    <p>Ficam incluídos nesta Apólice, todos os empregados de acordo com o estipulado nas Condições
                        Gerais do BIC Seguros Saúde, enquanto se encontrarem ao serviço da empresa.</p>
                </td>
            </tr>
        </table>

        <table class="text-block">
            <tr>
                <td class="left-text bold">Agregados Familiares</td>
                <td class="right-text">
                    <p>Caso aplicável, fica também incluído o Agregado Familiar dos empregados, constituído pelos seus
                        Filhos, nos termos no estabelecido nas Condições Gerais deste Seguro.</p>
                    <p>Os Filhos dos empregados, incluindo adoptados ou tutelados só poderão ser admitidos e permanecer
                        ao abrigo do Seguro, enquanto solteiros e menores ou, se forem estudantes, até aos 25 anos de
                        idade, desde que vivam com os pais.</p>
                </td>
            </tr>
        </table>

        <table class="text-block">
            <tr>
                <td class="left-text bold">Limite idade para inclusão no Seguro</td>
                <td class="right-text">
                    <p>Idade mínima de Adesão dos empregados: 16 anos inclusive;</p>
                    <p>Idade limite de adesão de empregados e cônjuges: 65 anos, inclusive;</p>
                    <p>Idade limite de adesão dos descendentes: até 24 anos inclusive, sem prejuízo do referido no
                        quadro anterior.</p>
                </td>
            </tr>
        </table>

        <table class="text-block">
            <tr>
                <td class="left-text bold">Limite de idade para permanência no Seguro</td>
                <td class="right-text">
                    <p>Empregados e cônjuges: 70 anos inclusive</p>
                    <p>Descendentes: até 25 anos</p>
                    <p>Relativamente aos empregados e cônjuges o prazo de permanência no seguro pode ser alargado até ao
                        final da anuidade em que completarem 70 anos de idade, desde que solicitado pelo Tomador de
                        Seguro e desde que o empregado mantenha o vínculo laboral.</p>
                </td>
            </tr>
        </table>

        <!-- Planos e Coberturas -->

        <table class="text-block">
            <tr>
                <td class="left-text bold">
                    Planos e Coberturas

                </td>
                <td colspan="2" class="right-text">
                    O BIC Seguros disponibiliza <strong> 1 Planos de Saúde de acordo a solicitação do GRUPO MITRELLI,</strong>, cujas coberturas e capitais garantidos são os
                    seguintes:
                    <br>
                    <br>
                    <div> Plano: <span class="color-danger"> <strong>MITRELLI</strong> </span> 
                        <br>
                        <br>
                    </div>

                    <table class="table-border">
                        <tr>
                            <th class="th-border" colspan="1">PLANOS de SAÚDE BIC Seguros</th>
                            <th class="th-border" >Limite de Responsabilidade por pessoa/anuidade <br>
                                (Moeda: Kwanzas)</th>

                        </tr>

                        <tr>
                            <th class="td-border">COBERTURAS</th>
                            <th class="td-border ">MITRELLI</th>
                          
                        </tr>
                        <tr>
                            <td class="td-border">ÁREA DE COBERTURA</td>
                          
                            <td class="td-border text-center"> <strong>MUNDIAL</strong> (EXCEPTO EUA e MÉDIO ORIENTE)
                            </td>
                          
                        </tr>
                  
                     
                        @foreach ( sortCoverages($coverages) as $coverage)
                            @if (
                                    !$coverage['group_id'] &&

                                    selectedCoverage($QLI, $coverage['id'])
                                )
                                <tr>
                                 @php 
                                 $qli = findQLI($QLI, $coverage);

                                   $capital_min_value = number_format($qli["capital_min_value"], 0, ',', '.');
                                                  
                                                         @endphp
                                    <td class="td-border text-uppercase" > <strong>{{ $coverage['description'] }}</strong></td>
                                    <td class="td-border  text-center fw-b">{{ $capital_min_value }}</td>
                                
                                </tr>
                                @foreach ( sortCoverages(groupCoverages($coverage['id'], $coverages)) as $sub)
                                    @if ( selectedCoverage($QLI, $sub['id']) )
                                        <tr>

                                            <td class="td-border " style="text-align: justify;">{{$sub['name']}}</td>
                                            <td class="td-border "></td>
                                         
                                        </tr>
                                    @endif
                                @endforeach
                            @endif
                        @endforeach
                 
                    </table>
                    <br>
                    <p><strong>Observações:</strong></p>
                    <p>(1) Inclui Vacinas</p>
                </td>
            </tr>
        </table>

        <!--  Planos e
Coberturas
(Cont.) -->



        <!--  Comparticipação
pelo BIC
Seguros -->
        <table class="text-block">
            <tr>
                <td class="left-text bold">
                    Comparticipação
                    pelo BIC Seguros
                </td>
                <td colspan="2" class="right-text">
                    A comparticipação pelo BIC Seguros, nas despesas médicas efectuadas pelas Pessoas Seguras desta
                    Apólice, independentemente do Plano Contratado, será a seguinte:
                    <br>
                    <br>

                    <table class="table-border">
                        <tr>
                            <th class="th-border" style="width: 50%" rowspan="2">PLANOS de SAÚDE BIC Seguros
                                (COBERTURAS)</th>
                            <th class="th-border" colspan="2">Copagamentos a cargo das Pessoas Seguras</th>
                        </tr>
                        <tr>

                            <th class="td-border text-danger">Dentro da Rede</th>
                            <th class="td-border text-danger">Fora da Rede</th>
                        </tr>
                        <tr>
                            <td class="td-border"><strong>INTERNAMENTO HOSPITALAR</strong></td>
                            <td class="td-border text-center ">0%</td>
                            <td class="td-border text-center ">30%</td>
                        </tr>

                        <tr>
                            <td class="td-border p-item"
                                style="border-top: none !important;border-bottom: none !important;"><span
                                    class="text-white">---</span><strong> TRATAMENTOS, CONSULTAS E EXAMES</strong> <br> CONSULTAS</td>
                            <td class="td-border text-center ">2.400 Kz</td>
                            <td class="td-border text-center ">30%</td>
                        </tr>
                        <tr>
                            <td class="td-border p-item"
                                style="border-top: none !important;border-bottom: none !important;"><span
                                    class="text-white">---</span> EXAMES E TRATAMENTOS</td>
                            <td class="td-border text-center ">10%</td>
                            <td class="td-border text-center ">30%</td>
                        </tr>
                        <tr>
                            <td class="td-border p-item"
                                style="border-top: none !important;border-bottom: none !important;"><span
                                    class="text-white">---</span> FISIOTERAPIA</td>
                            <td class="td-border text-center ">1.700 Kz</td>
                            <td class="td-border text-center ">30%</td>
                        </tr>
                        <tr>
                            <td class="td-border"><strong>MEDICAMENTOS</strong></td>
                            <td class="td-border text-center ">10%</td>
                            <td class="td-border text-center ">10%</td>
                        </tr>
                     
                        <tr>
                            <td class="td-border"><strong>PARTO</strong></td>
                            <td class="td-border text-center ">10%</td>
                            <td class="td-border text-center ">30%</td>
                        </tr>
                       
                    </table>
                </td>
            </tr>
        </table>


        <!--  Períodos de
Carência-->

        <table class="text-block">
            <tr>
                <td class="left-text bold">
                    Períodos
                    de Carência
                </td>
                <td colspan="2" class="right-text">
                    Os períodos de carência, aplicáveis somente em caso de doença, são os indicados no quadro abaixo. Em
                    caso de acidente, as coberturas são imediatas.
                    <br>
                    <br>

                    <table class="table-border">
                        <tr>
                            <th class="th-border" style="width: 70%">COBERTURAS</th>
                            <th class="th-border">Período de Carência</th>
                        </tr>
                        <tr>
                            <td class="td-border">
                                <div>INTERNAMENTO HOSPITALAR</div>
                            </td>
                            <td class="td-border text-center ">60 dias</td>
                        </tr>
                        <tr>
                            <td class="td-border">
                                <div>TRATAMENTOS, CONSULTAS E EXAMES, MEDICAMENTOS, ÓCULOS E
                                    OUTRAS PRÓTESES</div>
                            </td>
                            <td class="td-border text-center ">60 dias</td>
                        </tr>
                        <tr>
                            <td class="td-border">
                                <div>Estomatologia</div>
                            </td>
                            <td class="td-border text-center ">120 dias</td>
                        </tr>
                        <tr>
                            <td class="td-border">
                                <div>Tratamento cirúrgico ou por outros métodos invasivos da
                                    hipertrofia benigna da próstata, tratamento cirúrgico ou por outros métodos
                                    invasivos de patologia benigna do útero, tratamento cirúrgico de cistocelo e
                                    rectocelo</div>
                            </td>
                            <td class="td-border text-center ">180 dias</td>
                        </tr>
                        <tr>
                            <td class="td-border">
                                <div>PARTO, tratamento cirúrgico de varizes dos membros
                                    inferiores, tratamento cirúrgico de hérnia distal, hemorroidectomia e outros
                                    tratamentos da doença hemorroidista bem como tratamento cirúrgico da fístula
                                    perianal, tratamento de patologia articular por via artroscópica, septoplastia,
                                    amigdalectomia, adenoidectomia, miringotomias com ou sem aplicação de tubos de
                                    ventilação, rinoseptoplastia, excisão cirúrgica de lesões benignas da pele e tecido
                                    celular subcutâneo, tratamentos com laser a lesões benignas da pele, tratamentos
                                    psiquiátricos</div>
                            </td>
                            <td class="td-border text-center ">365 dias</td>
                        </tr>
                        <tr>
                            <td class="td-border">
                                <div>Pré-existências</div>
                            </td>
                            <td class="td-border text-center ">720 dias</td>
                        </tr>
                        <tr>
                            <td class="td-border">
                                <div>HIV/SIDA</div>
                            </td>
                            <td class="td-border text-center ">60 meses</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Page 4 -->









        <table class="text-block">
            <tr>
                <td class="left-text bold">Entrada em vigor das Garantias</td>
                <td class="right-text">
                    <p>A data de início da Apólice.</p>
                </td>
            </tr>
        </table>

           <table class="text-block">
            <tr>
                <td class="left-text bold">Acidentes e Doenças Pré-Existentes</td>
                <td class="right-text">
                    <p>A contagem dos períodos de carência, em relação às Pessoas Seguras que transitem de Seguro de
                        Saúde de outras Seguradoras, será efectuada a partir da data de inclusão na Apólice de Seguro de
                        Saúde na outra Seguradora.</p>
                    <br>
                    <p><strong>Alertamos, no entanto, que as Doenças Pré-existentes que não foram consideradas cobertas
                            ao abrigo da apólice da Seguradora anterior, apenas serão cobertas pela Apólice do BIC
                            Seguros após ter decorrido o período de carência de 720 dias acima mencionado.</strong></p>
                </td>
            </tr>
        </table>

        <table class="text-block">
            <tr>
                <td class="left-text bold">Riscos Excluídos</td>
                <td class="right-text">
                    <p>De acordo com o Art.º 12.º das Condições Gerais deste produto e das Condições Especiais
                        Aplicáveis.</p>
                </td>
            </tr>
        </table>

        <table class="text-block">
            <tr>
                <td class="left-text bold">Cessação das Garantias da Pessoa Segura</td>
                <td class="right-text">
                    <p>Sem prejuízo do definido nas Condições Gerais, as garantias de cada um dos Participantes cessam
                        automaticamente na primeira das seguintes datas:</p>
                    <br>
                    <p><strong><u>Tomador do seguro:</u></strong></p>

                    <p><span class="text-white">--------<img src="img/cotacao/red-point.png">--</span> No fim da
                        anuidade em que o Tomador do Seguro complete a idade limite de
                        permanência no seguro, conforme atrás já referido.
                    </p>

                    <br>
                    <p><strong><u>Agregados Familiares:</u></strong></p>

                    <p><span class="text-white">--------<img src="img/cotacao/red-point.png">--</span>Data da cessação
                        das garantias para o Tomador do Seguro de quem
                        este Agregado Familiar faz parte;
                    </p>
                    <p><span class="text-white">--------<img src="img/cotacao/red-point.png">--</span>No fim da
                        anuidade em que completem a idade limite de
                        permanência no seguro conforme atrás
                        já referido;</p>
                    <p>Imediatamente, em caso de divórcio ou morte.</p>

                </td>
            </tr>
        </table>

        <table class="text-block">
            <tr>
                <td class="left-text bold">Rede Clínica Convencionada</td>
                <td class="right-text">
                    <p>A rede clínica convencionada do Seguro Saúde BIC Seguros é gerida pela ADV ANGOLA que é a
                        Entidade que organiza, gere e contacta com a rede de prestadores, em representação do BIC
                        Seguros.</p>
                    <p>No Anexo 1 à presente cotação informamos a Rede Clínica convencionada à presente data.</p>
                </td>
            </tr>
        </table>


       <table class="text-block">
            <tr>
                <td class="left-text bold">
                    Prémios
                    Totais
                </td>
                <td colspan="2" class="right-text">
                    Para cada um dos Planos BIC Seguros Saúde e para a População Indicada por V. Exas., o valor do
                    <strong><u>Prémio Total Anual</u></strong> (valores em Kwanzas) será o seguinte:
                    <br>
                    <br>

                    <h3 class="text-blue" style="margin-top: 0;margin-bottom:0px"><strong> <u> Número e tipo de
                                Pessoas Seguras:</u></strong></h3>

                    @php
                        $relationships = collect($objects)
                            ->pluck("relationship")
                            ->unique()
                            ->values();

                        $total = 0;

                    @endphp

                    <table class="table-border" style="width: 50%; margin-bottom: 20px;margin-top: 0">
                        <tr>
                            <th class="th-border fw-b">População todo Grupo</th>
                            <th class="th-border">Nº</th>
                        </tr>

                        @foreach ($relationships as $relationship)
                            @php
                                $qt = collect($objects)
                                    ->where("relationship", $relationship)
                                    ->count();
                                $total += $qt;
                            @endphp

                            <tr>
                                <td class="td-border fw-b">{{ $relationship }}</td>
                                <td class="td-border text-center">{{ sprintf('%02d', $qt) }}</td>
                            </tr>
                        @endforeach

                        <tr>
                            <td class="td-border"><strong>Total</strong></td>
                            <td class="td-border text-center fw-b">{{ sprintf('%02d', $total) }}</td>
                        </tr>
                    </table>
                    <h3 class="text-blue" style="margin-top: 0;margin-bottom:0px"><strong> <u> Prémio Total Anual em
                                KWANZAS:</u></strong></h3>

                    <!-- Primeira tabela - Primeiros 4 templates -->
                    <table class="table-border" style="margin-bottom: 20px;">
                        <tr>
                            <th class="th-border">População</th>
                            @php
                                $firstTemplates = array_slice($templates, 0, 4);
                            @endphp
                            @foreach ($firstTemplates as $template)
                                <th class="th-border text-danger text-uppercase">{{ $template['name'] ?? '' }}</th>
                            @endforeach
                        </tr>

                        @php
                            $totalsFirst = array_fill(0, count($firstTemplates), 0);

                        @endphp

                        @foreach ($relationships as $relationship)

                            <tr>
                                <td class="td-border fw-b">{{ $relationship }}</td>

                                @foreach ($firstTemplates as $index => $template)
                                 
                                    @php
                                    
                                     
                                        $premium = findPremiumSaude($objects, $QLI, $relationship, $template);
                                        // CORREÇÃO: Verificar se o prêmio não é null antes de usar
                                      
                                        $premiumValue = $premium ?? 0;
                                        $qt = collect($objects)
                                            ->where("relationship", $relationship)
                                            ->count();
                                        $premiumTotal = $premiumValue * $qt;
                                        $premiumFormatted = number_format($premiumTotal, 0, ',', '.');

                                        $totalsFirst[$index] += $premiumTotal;
                                    @endphp
                                    <td class="td-border text-center">{{ $premiumFormatted }}</td>
                                @endforeach
                            </tr>
                        @endforeach

                        <!-- Linha do Total -->
                        <tr>
                            <td class="td-border"><strong>Total</strong></td>
                            @foreach ($totalsFirst as $total)
                                @php
                                    $totalFormatted = number_format($total, 0, ',', '.');
                                @endphp
                                <td class="td-border text-center fw-b">{{ $totalFormatted }}</td>
                            @endforeach
                        </tr>
                    </table>

                    <!-- Segunda tabela - Templates restantes (se houver) -->
                    @if(count($templates) > 4)
                        <table class="table-border" style="margin-bottom: 20px;">
                            <tr>
                                <th class="th-border">População todo Grupo</th>
                                @php
                                    $remainingTemplates = array_slice($templates, 4);
                                @endphp
                                @foreach ($remainingTemplates as $template)
                                    <th class="th-border text-danger text-uppercase">{{ $template['name'] ?? '' }}</th>
                                @endforeach
                            </tr>

                            @php
                                $totalsRemaining = array_fill(0, count($remainingTemplates), 0);
                            @endphp

                            @foreach ($relationships as $relationship)
                                <tr>
                                    <td class="td-border fw-b">{{ $relationship }}</td>

                                    @foreach ($remainingTemplates as $index => $template)
                                        @php
                                            $premium = findPremiumSaude($objects, $QLI, $relationship, $template);
                                            // CORREÇÃO: Verificar se o prêmio não é null antes de usar
                                            $premiumValue = $premium ?? 0;
                                            $qt = collect($objects)
                                                ->where("relationship", $relationship)
                                                ->count();
                                            $premiumTotal = $premiumValue * $qt;
                                            $premiumFormatted = number_format($premiumTotal, 0, ',', '.');

                                            $totalsRemaining[$index] += $premiumTotal;
                                        @endphp
                                        <td class="td-border text-center">{{ $premiumFormatted }}</td>
                                    @endforeach
                                </tr>
                            @endforeach

                            <!-- Linha do Total -->
                            <tr>
                                <td class="td-border"><strong>Total</strong></td>
                                @foreach ($totalsRemaining as $total)
                                    @php
                                        $totalFormatted = number_format($total, 0, ',', '.');
                                    @endphp
                                    <td class="td-border text-center fw-b">{{ $totalFormatted }}</td>
                                @endforeach
                            </tr>
                        </table>
                    @endif

                    <h3 class="text-blue" style="margin-top: 0;margin-bottom:0px"><strong> <u>Prémio Total Anual em
                                KWANZAS por Pessoa Segura:</u></strong></h3>
                    <!-- Primeira tabela - Primeiros 4 templates -->
                    <table class="table-border" style="margin-bottom: 20px;">
                        <tr>
                            <th class="th-border">População todo Grupo</th>
                            @php
                                $firstTemplates = array_slice($templates, 0, 4);
                            @endphp
                            @foreach ($firstTemplates as $template)
                                <th class="th-border text-danger text-uppercase">{{ $template['name'] ?? '' }}</th>
                            @endforeach
                        </tr>

                        @php
                            $totalsFirst = array_fill(0, count($firstTemplates), 0);
                        @endphp

                        @foreach ($relationships as $relationship)
                            <tr>
                                <td class="td-border fw-b">{{ $relationship }}</td>

                                @foreach ($firstTemplates as $index => $template)
                                    @php
                                        $premium = findPremiumSaude($objects, $QLI, $relationship, $template);
                                        // CORREÇÃO: Verificar se o prêmio não é null antes de usar
                                        $premiumValue = $premium ?? 0;
                                        $premiumFormatted = number_format($premiumValue, 0, ',', '.');

                                        $totalsFirst[$index] += $premiumValue;
                                    @endphp
                                    <td class="td-border text-center">{{ $premiumFormatted }}</td>
                                @endforeach
                            </tr>
                        @endforeach

                        <!-- Linha do Total -->
                        <tr>
                            <td class="td-border"><strong>Total</strong></td>
                            @foreach ($totalsFirst as $total)
                                @php
                                    $totalFormatted = number_format($total, 0, ',', '.');
                                @endphp
                                <td class="td-border text-center fw-b">{{ $totalFormatted }}</td>
                            @endforeach
                        </tr>
                    </table>

                    <!-- Segunda tabela - Templates restantes (se houver) -->
                    @if(count($templates) > 4)
                        <table class="table-border" style="margin-bottom: 20px;">
                            <tr>
                                <th class="th-border">População todo Grupo</th>
                                @php
                                    $remainingTemplates = array_slice($templates, 4);
                                @endphp
                                @foreach ($remainingTemplates as $template)
                                    <th class="th-border text-danger text-uppercase">{{ $template['name'] ?? '' }}</th>
                                @endforeach
                            </tr>

                            @php
                                $totalsRemaining = array_fill(0, count($remainingTemplates), 0);
                            @endphp

                            @foreach ($relationships as $relationship)
                                <tr>
                                    <td class="td-border fw-b">{{ $relationship }}</td>

                                    @foreach ($remainingTemplates as $index => $template)
                                        @php
                                            $premium = findPremiumSaude($objects, $QLI, $relationship, $template);
                                            // CORREÇÃO: Verificar se o prêmio não é null antes de usar
                                            $premiumValue = $premium ?? 0;
                                            $premiumFormatted = number_format($premiumValue, 0, ',', '.');

                                            $totalsRemaining[$index] += $premiumValue;
                                        @endphp
                                        <td class="td-border text-center">{{ $premiumFormatted }}</td>
                                    @endforeach
                                </tr>
                            @endforeach

                            <!-- Linha do Total -->
                            <tr>
                                <td class="td-border"><strong>Total</strong></td>
                                @foreach ($totalsRemaining as $total)
                                    @php
                                        $totalFormatted = number_format($total, 0, ',', '.');
                                    @endphp
                                    <td class="td-border text-center fw-b">{{ $totalFormatted }}</td>
                                @endforeach
                            </tr>
                        </table>
                    @endif
                    <p><strong>Nota Importante:</strong> Qualquer alteração do Censos que deu origem à presente Coração,
                        que se verifique antes da emissão do seguro, poderá implicar alteração do Prémio Total Anual por
                        Pessoa Segura.</p>
                </td>
            </tr>
        </table>
          <table class="text-block">
            <tr>
                <td class="left-text bold">Prémios Por Empresa
                 </td>
                <td class="right-text">
                    <p> <strong>Conforme anexo 1, à presente Cotação.</strong> </p>
                </td>
            </tr>
        </table>
        <table class="text-block">
            <tr>
                <td class="left-text bold">Modalidade
                    de
                    Pagamento</td>
                <td class="right-text">
                    <p>Anual renovável. No caso de optarem por fraccionar o pagamento do prémio, não será
                        imputado qualquer encargo. </p>
                </td>
            </tr>
        </table>

        <table class="text-block">
            <tr>
                <td class="left-text bold">Encargos de
                    Fraccioname
                    nto</td>
                <td class="right-text">
                    <p>Anual renovável. No caso de optarem por fraccionar o pagamento do prémio, não será
                        imputado qualquer encargo. </p>
                </td>
            </tr>
        </table>
        <table class="text-block">
            <tr>
                <td class="left-text bold">Encargos de <br> Fraccionamento</td>
                <th class="right-text" style="font-weight: 100!important;">
                    <div>
                        <span style=""> Mensal</span>
                        {!! str_repeat('&nbsp;', 148) !!}



                        <span style="">0%</span>
                    </div>
                    <hr style="margin-top:0px; margin-bottom:0px;">
                    <div>
                        <span style=""> Trimestral</span>
                        {!! str_repeat('&nbsp;', 143) !!}

                        <span style="">0%</span>
                    </div>

                    <hr style="margin-top:0px; margin-bottom:0px;">
                    <div>
                        <span style=""> Semestral</span>
                        {!! str_repeat('&nbsp;', 143) !!}
                        <span style="">0%</span>
                    </div>
                </th>
            </tr>
        </table>



        <table class="text-block">
            <tr>
                <td class="left-text bold">Forma de
                    Pagamento</td>
                <td class="right-text">
                    <p> O fraccionamento mensal somente é admitido na modalidade de pagamento por débito
                        directo em conta bancária no Banco BIC.</p>
                </td>
            </tr>
        </table>

        <table class="text-block">
            <tr>
                <td class="left-text bold">Condições
                    Especiais</td>
                <td class="right-text">
                    <p> A subscrição do seguro de Saúde apresentado nesta cotação está condicionada à colocação
                        no BIC Seguros do seguro de Acidentes de Trabalho na próxima data de vencimento.</p>
                </td>
            </tr>
        </table>

        <div class="another-application">
            <c>
                <strong> Outras
                    Condições
                    Aplicáveis:</strong>

                </h5>
                <div class="another-application-detail">

                    <h5>1. Validade da presente Cotação:</h5>
                    <p class="valid-note">Estas condições são válidas por <strong>8 dias</strong>.</p>

                    <h5>2. Política de Inclusões:</h5>
                    <p class="another-application-detail-opt"><span>a. Inclusão de novos funcionários:</span></p>
                    <p class="another-application-detail-opt-roman"> I. A inclusão de novos funcionários e
                        agregados familiares (caso pretendam incluir) deverá ser efectuada até 30 dias da data de
                        admissão. Neste caso serão aplicadas as regras de carência em vigor para a apólice (grupo
                        inicial);</p>
                    <p class="another-application-detail-opt-roman"> II. A inclusão de novos funcionários e
                        seus
                        agregados familiares (caso pretendam incluir) que ultrapassem os 30 dias da data de admissão
                        do
                        Trabalhador serão aplicados períodos de carência a todas as coberturas do plano de seguro.
                    </p>

                    <p class="another-application-detail-opt"><span>b. Inclusão de agregado familiar (cônjuge e
                            dependentes):</span></p>
                    <p class="another-application-detail-opt-roman"> I. A inclusão de cônjuges e dependentes
                        deve
                        ser efectuada na mesma apólice (e, portanto, mesmo plano de seguro contratado) do
                        Trabalhador de
                        que são agregados;</p>
                    <p class="another-application-detail-opt-roman"> II. Caso o Trabalhador pretenda incluir
                        o
                        agregado familiar na apólice, devem ser incluídos o cônjuge e todos os dependentes.</p>

                    <p class="another-application-detail-opt"><span>c. Inclusão de cônjuges de Trabalhadores incluídos
                            na
                            apólice:</span></p>
                    <p class="another-application-detail-opt-roman"> I. A inclusão de cônjuges por casamento
                        ocorrido no máximo há 30 dias serão aplicadas as regras de carência em vigor para a apólice
                        (grupo inicial);</p>
                    <p class="another-application-detail-opt-roman"> II. A inclusão de cônjuges por casamento
                        ocorrido há mais de 30 dias serão aplicados períodos de carência a todas as coberturas do
                        plano
                        de seguro.</p>

                    <p class="another-application-detail-opt"><span>d. Inclusão de dependentes de Trabalhadores
                            incluídos
                            na apólice:</span></p>
                    <p class="another-application-detail-opt-roman"> I. A inclusão de dependentes nascidos no
                        máximo há 30 dias serão aplicadas as regras de carência em vigor para a apólice (grupo
                        inicial);
                    </p>
                    <p class="another-application-detail-opt-roman"> II. A inclusão de dependentes nascidos
                        há
                        mais de 30 dias serão aplicados períodos de carência a todas as coberturas do plano de
                        seguro.
                    </p>

                    <p class="another-application-detail-opt"><span>e. A facturação das inclusões</span> será efectuada
                        no método de pró-tata temporis
                        pelo
                        período não decorrido da anuidade em curso.</p>



                    <h5> 3. Política de exclusões:</h5>
                    <p class="another-application-detail-opt"><span>a</span>. As exclusões de pessoas seguras que
                        tiveram consumos durante a anuidade não dará lugar a
                        qualquer devolução de prémio pelo período não decorrido;
                    </p>
                    <p class="another-application-detail-opt"><span>b</span>. As exclusões de pessoas seguras que não
                        tiveram consumos durante a anuidade dará lugar à
                        devolução de 50% do prémio correspondente ao período não decorrido;</p>
                    <p class="another-application-detail-opt"><span>c</span>. As exclusões de Trabalhadores implicam a
                        exclusão de todo o agregado familiar do
                        trabalhador;
                    </p>
                    <p class="another-application-detail-opt"><span>d</span>. A exclusão de cônjuges e/ou dependentes
                        (durante a anuidade) de trabalhadores que se
                        mantêm na apólice só será aceite desde que devidamente justificada.</p>
                    <h5> 4. Cláusula de Flutuação Cambial:</h5>
                    <p class="another-application-detail-opt"><span>a</span>. Fica acordado entre as partes que em caso
                        de flutuação cambial superior a 5% da Moeda
                        Nacional de Angola em relação aos dólares norte americanos, a Seguradora reserva-se o direito
                        de emitir um recibo compensatório desde a data em que ocorra a flutuação até ao término do
                        contrato, em base pró-rata temporis. Os valores de referência a considerar para efeitos da
                        presente cláusula serão aferidos quinzenalmente no primeiro e no décimo sexto dia de cada
                        mês através de análise aos valores médios da quinzena anterior. Os valores de referência
                        utilizados serão os câmbios de venda publicados pelo BNA Banco Nacional de Angola no seu
                        Sítio da Internet.</p>
                    <p class="another-application-detail-opt"><span>b</span>. Caso o câmbio vigente à data de emissão
                        da apólice tenha sofrido uma variação superior a 5%
                        face ao indicado na alínea anterior, a Seguradora reserva-se o direito de alterar os prémios da
                        apólice em função da alteração ocorrida no câmbio acima indicado.</p>

                </div>
        </div>
        @for ($countBr = 1; $countBr <= 100; $countBr++)
            <br>
        @endfor

        <div class="important-info">
            <small style="color: white">d</small>

            <h5 class="" style="margin-bottom:0px;margin-top:10px">INFORMAÇÃO IMPORTANTE:</h5>
            <p class="info-importante" style="margin-top:0px">
                Os valores da presente cotação assentam num conjunto de pressupostos, como sejam os dados que nos
                indicou e a inexistência de factores de agravamento de risco, pressupostos estes sujeitos a
                confirmação por parte do Segurador. <br> <br>
                O Segurador pode solicitar informação adicional e/ou confirmar os dados indicados, podendo daqui
                resultar a não verificação, no caso concreto, dos pressupostos sobre os quais assentou a presente
                cotação e, consequentemente, a não-aceitação do seguro nas condições referidas na mesma. Deste modo,
                importa notar que o prémio desta cotação e a verificação dos respectivos pressupostos carecem de
                confirmação e aceitação por parte do Segurador. <br>
                <br>
                Para os dados indicados, apurou-se o prémio acima que inclui cargas fiscais e parafiscais em vigor à
                data da cotação. Qualquer alteração legal posterior às cargas fiscais ou parafiscais aplicáveis,
                terá que ser considerada pelo Segurador aquando da emissão do contrato. Esta cotação tem validade
                acima indicada, sem prejuízo da possibilidade, aquando da emissão do contrato, de alteração do
                prémio apurado caso ocorra uma alteração legal posterior à data da cotação que determine a
                modificação das cargas fiscais ou parafiscais aplicáveis. <br>
                <br>
            </p>

            <h5 class="" style="color:rgb(22, 22, 22)"> BIC Seguros, S.A.</h5>
            </p>
        </div>
        {{-- @for ($countBr = 0; $countBr < 50; $countBr++) <br>
            @endfor
            <div class="box-pag-anexo"></div> --}}
            @for ($countBr = 12; $countBr <= 18; $countBr++)
                <img src="img/cotacao/anexo-saude/pag-{{ $countBr }}.jpg" style="height: 1500px" alt="">
            @endfor
    </div>

</body>

</html>
