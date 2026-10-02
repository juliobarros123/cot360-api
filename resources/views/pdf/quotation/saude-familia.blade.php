<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">


</head>

<body>
    {{-- <div class="logo-container"><img src="img/cotacao/icone_capa.jpeg" class="icone_capa" alt=""></div> --}}
    <div class="pdf-container" id="pdfContent">
        <!-- Page 1 -->
        <div class="page-border"></div>
        <div class="header">
            <img class="title-img" src="img/cotacao/title.png" alt="">
            {{-- <h5 class="area-corrector">ÁREA DE SAÚDE</h5> --}}
           
        </div>
 <br>
            <br>
             <br>
            <br> <br>
            <br>
        <div class="box-cover">
            <img class="cover" src="img/cotacao/quote_SAUDE_branch.png" alt="">
        </div>
        @php
            $cotNumber = '-----';
            $quotationNumberValue = $quotation['quotation_number'] ?? ($quotation_number ?? null);
            if ($quotationNumberValue) {
                $cotNumber = formatarNumeroCotacao($quotationNumberValue);
            }
            $templates = collect($templates)->sortBy('code')->toArray();
        @endphp

        <div class="proposal-title">
            <h1 class="proposal-title-title color-black fw-b">
                <strong>COTAÇÃO DE SEGURO SAÚDE FAMÍLIA SEGURA</strong>
            </h1>
            <br>
            <h5 class="color-black"> Cotação nº {{ $cotNumber }}@if ($version_number > 1)
                    _V{{ $version_number }}
                @endif/{{ dataPortugues() }}</h5>
        </div>

        <div class="intro-text" style="    margin-top: -50px!important;padding-top:-15px">
           <p>
              Agradecemos a oportunidade concedida, à BIC Seguros para apresentar esta proposta de
colaboração, a qual fica sujeita a eventuais reajustamentos que sejam julgados convenientes. A
nossa sugestão é que considerem o conjunto dos serviços oferecidos, e dos meios técnicos e
humanos que colocamos à vossa disposição, como as ferramentas que permitirão obter um
serviço de Excelência, dando assim cumprimento às vossas expectativas. 
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
        <br>
        <br> <br> <br>
        <!-- Page 2 -->
        <div class="page-title color-black">COTAÇÃO DE SEGURO SAÚDE</div>
        <div class="page-sub-title red" style="margin-top: 3px;">COTAÇÃO Nº {{ $cotNumber }}/{{ dataPortugues() }}
        </div>

        <table class="text-block">
            <tr>
                <td class="left-text bold">Tomador do Seguro</td>
                <td class="right-text">
                    <p><strong>{{ $entity['name'] ?? '' }}</strong><br>
                        Luanda, Angola<br>
                        NIF: {{ $entity['nif'] ?? 'A indicar' }}
                    </p>
                </td>
            </tr>
        </table>

        <table class="text-block">
            <tr>
                <td class="left-text bold">Pessoas Seguras</td>
                <td class="right-text">
                    <p>O Tomador do Seguro acima mencionado e os seus familiares quando indicados.</p>
                </td>
            </tr>
        </table>

        <table class="text-block">
            <tr>
                <td class="left-text bold">Agregados Familiares</td>
                <td class="right-text">
                    <p>Caso aplicável, fica também incluído o Agregado Familiar do Tomador do Seguro, constituído pelos
                        seus
                        Filhos e Cônjuge, nos termos no estabelecido nas Condições Gerais deste Seguro.</p>
<br>

                    <p>Os Filhos, incluindo adoptados ou tutelados só poderão ser admitidos e permanecer
                        ao abrigo do Seguro, enquanto solteiros e menores ou, se forem estudantes, até aos 25 anos de
                        idade, desde que vivam com os pais.</p>
                </td>
            </tr>
        </table>

        <table class="text-block">
            <tr>
                <td class="left-text bold">Limite idade para inclusão no Seguro</td>
                <td class="right-text">
                    <p> <span style="color: red;">▪</span> Idade mínima do Tomador do Seguro à data de início do seguro:
                        16 anos inclusive;</p>
                    <p><span style="color: red;">▪</span> Idade limite do Tomador de Seguro e seu Cônjuge à data de
                        início do seguro: 65 anos, inclusive;</p>
                    <p><span style="color: red;">▪</span> Idade limite dos descendentes à data de inclusão no seguro:
                        até 24 anos inclusive, sem prejuízo do referido no
                        quadro anterior.</p>
                </td>
            </tr>
        </table>

        <table class="text-block">
            <tr>
                <td class="left-text bold">Limite de idade para permanência no Seguro</td>
                <td class="right-text">
                    <p>Tomador de Seguro e cônjuge: 70 anos inclusive</p>
                    <p>Descendentes: até 25 anos</p>
                    <br>
                    <p>Relativamente ao Tomador do Seguro e cônjuge o prazo de permanência no seguro pode ser alargado
                        até ao
                        final da anuidade em que completarem 70 anos de idade, desde que solicitado pelo Tomador de
                        Seguro.</p>
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
                    O BIC Seguros disponibiliza <strong> 3 Planos de Saúde à vossa escolha (PLUS, TOP e MEGA)</strong>,
                    cujas coberturas e capitais garantidos são os
                    seguintes:
                    <br>
                    <br>
                    <div> <span class="color-danger"> <strong>Planos:</strong> </span> <strong>PLUS, TOP e MEGA</strong>
                        <br>
                        <br>
                    </div>

                    <table class="table-border">
                        <tr>
                            <th class="th-border" style="width: 35%" colspan="1">PLANOS de SAÚDE BIC Seguros</th>
                            <th class="th-border" colspan="3">Limite de Responsabilidade por pessoa/anuidade <br>
                                (Moeda: Kwanzas)</th>

                        </tr>

                        <tr>
                            <th class="td-border">COBERTURAS</th>
                            <th class="td-border text-danger">BIC PLUS</th>
                            <th class="td-border text-danger">BIC TOP</th>
                            <th class="td-border text-danger">BIC MEGA</th>
                        </tr>
                        <tr>
                            <td class="td-border"><strong>ÁREA DE COBERTURA</strong></td>
                            <td class="td-border text-center">
                                <strong>
                                    ANGOLA, ÁFRICA DO SUL, NAMÍBIA, PORTUGAL</strong>
                            </td>
                            <td class="td-border text-center">
                                <strong>
                                    MUNDIAL (excepto EUA e Médio Oriente)
                                </strong>
                            </td>
                            <td class="td-border text-center"> <strong>MUNDIAL</strong> (excepto EUA e Médio Oriente)
                            </td>
                        </tr>
                        <tr class="bg-c-light-gray ">
                            <td class="td-border"> <strong>LIMITE DE COBERTURA DO PLANO</strong> </td>
                            <td class="td-border text-center fw-b">20.000.000</td>
                            <td class="td-border text-center fw-b">50.000.000</td>
                            <td class="td-border text-center fw-b">100.000.000</td>
                        </tr>
                        <tr>
                            <td class="td-border"><strong>INTERNAMENTO HOSPITALAR</strong></td>
                            <td class="td-border text-center"> <strong>20.000.000</strong></td>
                            <td class="td-border text-center"><strong>50.000.000</strong></td>
                            <td class="td-border text-center"><strong>100.000.000</strong></td>
                        </tr>
                        <tr>
                            <td class="td-border p-item"><span class="text-white">---</span> ACOMODAÇÃO (Tipo Quarto)
                            </td>
                            <td class="td-border  text-center">Colectivo</td>
                            <td class="td-border  text-center">Individual</td>
                            <td class="td-border  text-center">Individual</td>
                        </tr>
                        <tr>
                            <td class="td-border"> <strong>TRATAMENTOS, CONSULTAS E EXAMES</strong></td>
                            <td class="td-border  text-center fw-b">250.000</td>
                            <td class="td-border text-center fw-b">750.000</td>
                            <td class="td-border text-center fw-b">2.500.000</td>
                        </tr>

                        <tr>

                            <td class="td-border p-item"><span class="text-white">---</span> CONSULTAS</td>
                            <td class="td-border "></td>
                            <td class="td-border"></td>
                            <td class="td-border"></td>
                        </tr>
                        <tr>

                            <td class="td-border p-item"><span class="text-white">---</span> EXAMES E TRATAMENTOS</td>
                            <td class="td-border "></td>
                            <td class="td-border"></td>
                            <td class="td-border"></td>
                        </tr>
                        <tr>

                            <td class="td-border p-item"><span class="text-white">---</span> FISIOTERAPIA</td>
                            <td class="td-border bg-c-light-gray "></td>
                            <td class="td-border  text-center fw-b">10 Sessões</td>
                            <td class="td-border text-center fw-b">20 Sessões</td>
                        </tr>
                        <tr>
                            <td class="td-border"> <strong>MEDICAMENTOS </strong><sup>(1)</sup> </td>
                            <td class="td-border bg-c-light-gray"></td>
                            <td class="td-border text-center fw-b">150.000</td>
                            <td class="td-border text-center fw-b">250.000</td>
                        </tr>
                        <tr>
                            <td class="td-border"> <strong>ESTOMATOLOGIA</strong> (Consultas e Tratamentos)</td>
                            <td class="td-border bg-c-light-gray"></td>
                            <td class="td-border text-center fw-b">150.000</td>
                            <td class="td-border text-center fw-b">300.000</td>
                        </tr>
                        <tr>
                            <td class="td-border fw-b">ÓCULOS E OUTRAS PRÓTESES</td>
                            <td class="td-border bg-c-light-gray"></td>
                            <td class="td-border text-center fw-b">75.000</td>
                            <td class="td-border text-center fw-b">100.000</td>
                        </tr>
                        <tr>
                            <td class="td-border"><strong>PARTO</strong></td>
                            <td class="td-border bg-c-light-gray"></td>
                            <td class="td-border"></td>
                            <td class="td-border"></td>
                        </tr>
                        <tr>
                            <td class="td-border p-item"><span class="text-white">---</span> Parto normal</td>
                            <td class="td-border bg-c-light-gray"></td>
                            <td class="td-border text-center fw-b">1.000.000</td>
                            <td class="td-border text-center fw-b">1.500.000</td>
                        </tr>
                        <tr>
                            <td class="td-border p-item"><span class="text-white">---</span> Cesariana justificada
                            </td>
                            <td class="td-border bg-c-light-gray"></td>
                            <td class="td-border  text-center fw-b">1.250.000</td>
                            <td class="td-border  text-center fw-b">2.000.000</td>
                        </tr>
                        <tr>
                            <td class="td-border p-item"><span class="text-white">---</span> Cobertura RN (sub-limite
                                de
                                internamento)</td>
                            <td class="td-border  bg-c-light-gray"></td>
                            <td class="td-border  text-center fw-b">850.000 Kz até 14 dias</td>
                            <td class="td-border  text-center fw-b">850.000 Kz até 14 dias</td>
                        </tr>
                        <tr>
                            <td class="td-border  fw-b">SERVIÇO DE EVACUAÇÃO</td>
                            <td class="td-border text-center fw-b">20.000.000</td>
                            <td class="td-border text-center fw-b">30.000.000</td>
                            <td class="td-border text-center fw-b">30.000.000</td>
                        </tr>
                    </table>
                    <br>
                    <p><strong>Observações:</strong></p>
                    <p>(1) Inclui Vacinas</p>
                </td>
            </tr>
        </table>

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

                    <table class="table-border" >
                        <tr>
                            <th class="th-border" style="width: 50%" rowspan="2">PLANOS de SAÚDE BIC Seguros
                                (COBERTURAS)</th>
                            <th class="th-border" colspan="2">Copagamentos a cargo das Pessoas Seguras</th>
                        </tr>
                        <tr>

                            <th class="td-border text-danger" >Dentro da Rede</th>
                            <th class="td-border text-danger">Fora da Rede</th>
                        </tr>
                        <tr>
                            <td class="td-border"><strong>INTERNAMENTO HOSPITALAR</strong></td>
                            <td class="td-border text-center ">0%</td>
                            <td class="td-border text-center ">30%</td>
                        </tr>

                        <tr>
                            <td class="td-border">
                                <strong>TRATAMENTOS, CONSULTAS E EXAMES</strong>
                            </td>
                            <td class="td-border"></td>
                            <td class="td-border"></td>
                        </tr>
                        <tr>
                            <td class="td-border p-item"
                                style="border-top: none !important;border-bottom: none !important;"><span
                                    class="text-white">---</span> CONSULTAS</td>
                            <td class="td-border text-center ">10%</td>
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
                            <td class="td-border text-center ">10%</td>
                            <td class="td-border text-center ">30%</td>
                        </tr>
                        <tr>
                            <td class="td-border"><strong>MEDICAMENTOS</strong></td>
                            <td class="td-border text-center ">10%</td>
                            <td class="td-border text-center ">10%</td>
                        </tr>
                        <tr>
                            <td class="td-border"><strong>ESTOMATOLOGIA</strong> (Consulta e Tratamentos)</td>
                            <td class="td-border text-center ">25%</td>
                            <td class="td-border text-center ">35%</td>
                        </tr>
                        <tr>
                            <td class="td-border"><strong>ÓCULOS E OUTRAS PRÓTESES</strong></td>
                            <td class="td-border text-center ">20%</td>
                            <td class="td-border text-center ">25%</td>
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
                                    inferiores, tratamento cirúrgico de hérnia discal, hemorroidectomia e outros
                                    tratamentos da doença hemorroidária bem como tratamento cirúrgico da fístula
                                    perianal, tratamento de patologia articular por via artroscópica, septoplastia,
                                    amigdalectomia, adenoidectomia, miringotomias com ou sem aplicação de tubos de
                                    ventilação, rinoseptoplastia, excisão cirúrgica de lesões benignas da pele e tecido
                                    celular subcutâneo, tratamentos com laser a lesões benignas da pele, tratamento
                                    cirúrgico
                                    da apneia do sono, tratamentos psiquiátricos.</div>
                            </td>
                            <td class="td-border text-center ">365 dias</td>
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
                    <p>À data de início da Apólice.</p>
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
                        <br>
                    <p>No Anexo 1 à presente cotação informamos a Rede Clínica convencionada à presente data.</p>
                </td>
            </tr>
        </table>




        <!-- Prémios Totais -->
        @php

        @endphp
        <table class="text-block">
            <tr>
                <td class="left-text bold">
                    Pessoas seguras
                    e Prémios totais
                </td>
                <td colspan="2" class="right-text">
                    Para cada um dos Planos BIC Seguros Saúde o valor do
                    <strong><u>Prémio Total Anual</u></strong> (valores em Kwanzas) será o seguinte:
                    <br>
                    <br>

                    @php
                        $templates = $templates ?? [];
                        $objects = $objects ?? [];
                        $QLI = $QLI ?? [];
                        $discountPerc = $discountPerc ?? 0;
                        $premiosGlobais = $premiosGlobais ?? [];
                        $modalidadeColumns = [
                            'mensal' => [
                                'label' => 'Prémio Mensal',
                                'divisor' => 12,
                                'status' => $premiosGlobais['premioTotalMensal']['status'] ?? false,
                            ],
                            'semestral' => [
                                'label' => 'Prémio Semestral',
                                'divisor' => 2,
                                'status' => $premiosGlobais['premioTotalSemestre']['status'] ?? false,
                            ],
                            'trimestral' => [
                                'label' => 'Prémio Trimestral',
                                'divisor' => 4,
                                'status' => $premiosGlobais['premioTotalTrimestre']['status'] ?? false,
                            ],
                        ];
                        $modalidadeColumns = collect($modalidadeColumns)
                            ->filter(fn($column) => !empty($column['status']))
                            ->all();

                        // Prioridade: templates com sequence, ordenados por sequence asc;
                        // sem sequence ficam no fim (fallback por code).
                        $templates = collect($templates)
                            ->sort(function ($a, $b) {
                                $aSeq = $a['sequence'] ?? null;
                                $bSeq = $b['sequence'] ?? null;

                                if (is_null($aSeq) && is_null($bSeq)) {
                                    return ($a['code'] ?? 0) <=> ($b['code'] ?? 0);
                                }
                                if (is_null($aSeq)) return 1;
                                if (is_null($bSeq)) return -1;

                                $bySeq = $aSeq <=> $bSeq;
                                return $bySeq !== 0 ? $bySeq : (($a['code'] ?? 0) <=> ($b['code'] ?? 0));
                            })
                            ->values()
                            ->all();
                    @endphp

                    @foreach ($templates as $template)
                        @if (strtoupper($template['package']) === strtoupper($selectedTemplate['package']) ||
                                (strtoupper($template['package']) != strtoupper($selectedTemplate['package']) && $showAllPackages))
                            @php
                                $partes = explode(' ', $template['name'] ?? '');
                                $ultimo = end($partes);
                                if ($ultimo === false) {
                                    $ultimo = '';
                                }
                                $templatePackageKey = strtoupper(trim((string) ($template['package'] ?? $ultimo)));

                                $total = 0;
                                $totalsByModalidade = array_fill_keys(array_keys($modalidadeColumns), 0);
                            @endphp

                            <h3 class="text-blue" style="margin-top: 0; margin-bottom: 0px">
                                <strong><u>Plano <span
                                            style="text-transform: uppercase;">{{ $ultimo }}</span></u></strong>
                            </h3>

                            <table class="table-border tb-premios" style="width: 100%; margin-bottom: 20px; margin-top: 10px; ">
                                <tr>
                                    <th class="th-border fw-b color-danger" style="text-align: left;  ">Pessoas seguras
                                    </th>
                                    <th class="th-border color-danger" style=" ">Data de<br>nascimento</th>
                                    <th class="th-border color-danger" style=" ">Prémio<br>total</th>
                                    @foreach ($modalidadeColumns as $modalidadeColumn)
                                        <th class="th-border color-danger" style=" ">{!! str_replace(' ', '<br>', $modalidadeColumn['label']) !!}</th>
                                    @endforeach
                                </tr>

                                @foreach ($objects as $obj)
                                    @php
                                        $qliWithTemplate = null;
                                        $selectedRate = null;

                                        // Buscar QLI correspondente ao objeto
                                        if (!empty($QLI) && !empty($obj['id'])) {
                                            $qliWithTemplate = collect($QLI)->where('object_id', $obj['id'])->first();
                                        }

                                        // Buscar a taxa selecionada para este template
                                        if (
                                            $qliWithTemplate &&
                                            !empty($qliWithTemplate['ratesObject']) &&
                                            !empty($templatePackageKey)
                                        ) {
                                            $selectedRate = collect($qliWithTemplate['ratesObject'])
                                                ->first(function ($rate) use ($templatePackageKey) {
                                                    $ratePackage = strtoupper(trim((string) ($rate['package'] ?? '')));
                                                    return $ratePackage === $templatePackageKey;
                                                });

                                            // Fallback: alguns payloads trazem package com nome completo do plano
                                            if (empty($selectedRate) && !empty($template['name'])) {
                                                $templateNameKey = strtoupper(trim((string) $template['name']));
                                                $selectedRate = collect($qliWithTemplate['ratesObject'])
                                                    ->first(function ($rate) use ($templateNameKey) {
                                                        $ratePackage = strtoupper(trim((string) ($rate['package'] ?? '')));
                                                        return $ratePackage === $templateNameKey;
                                                    });
                                            }
                                        }

                                        // Fallback adicional: payload já normalizado com pdf_rates no objeto
                                        if (empty($selectedRate) && !empty($obj['pdf_rates']) && !empty($templatePackageKey)) {
                                            $selectedRate = collect($obj['pdf_rates'])
                                                ->first(function ($rate) use ($templatePackageKey) {
                                                    $ratePackage = strtoupper(trim((string) ($rate['package'] ?? '')));
                                                    return $ratePackage === $templatePackageKey;
                                                });
                                        }
                                    @endphp

                                    @if (!empty($selectedRate) && !empty($selectedRate['premium_value']))
                                        @php
                                            // Aplicar desconto
                                            $premiumValue = floatval($selectedRate['premium_value']);
                                            $discountValue = ($premiumValue * floatval($discountPerc)) / 100;
                                            $premiumAfterDiscount = $premiumValue - $discountValue;

                                            // Somar ao total
                                            $total += $premiumAfterDiscount;

                                            $premiumsByModalidade = [];
                                            foreach ($modalidadeColumns as $modalidadeKey => $modalidadeColumn) {
                                                $premiumByModalidade = $premiumAfterDiscount / $modalidadeColumn['divisor'];
                                                $premiumsByModalidade[$modalidadeKey] = $premiumByModalidade;
                                                $totalsByModalidade[$modalidadeKey] += $premiumByModalidade;
                                            }

                                            // Formatar sem casas decimais
                                            $premiumFormatted = number_format($premiumAfterDiscount, 0, ',', '.');

                                            // Formatar data de nascimento
                                            $birthdateFormatted = '';
                                            if (!empty($obj['birthdate'])) {
                                                try {
                                                    $birthdateFormatted = \Carbon\Carbon::createFromFormat(
                                                        'd/m/Y',
                                                        $obj['birthdate'],
                                                    )->format('d/m/Y');
                                                } catch (\Exception $e) {
                                                    $birthdateFormatted = $obj['birthdate'];
                                                }
                                            }
                                        @endphp

                                        <tr>
                                            <td class="td-border"  style="padding: 1px 1px; text-transform:capitalize;">{{ $obj['name'] ?? '' }}</td>
                                            <td class="td-border text-center" style="padding: 1px 1px;">
                                                {{ $birthdateFormatted }}
                                            </td>
                                            <td class="td-border text-center" style="padding: 1px 1px;">
                                                {{ $premiumFormatted }}
                                            </td>
                                            @foreach ($modalidadeColumns as $modalidadeKey => $modalidadeColumn)
                                                <td class="td-border text-center" style="padding: 1px 1px;">
                                                    {{ number_format($premiumsByModalidade[$modalidadeKey] ?? 0, 0, ',', '.') }}
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endif
                                @endforeach

                                <tr>
                                    <td class="td-border" colspan="2" style="padding: 2px 3px;"><strong>Total</strong></td>
                                    <td class="td-border text-center fw-b" style="padding: 2px 3px;">
                                        {{ number_format($total, 0, ',', '.') }}
                                    </td>
                                    @foreach ($modalidadeColumns as $modalidadeKey => $modalidadeColumn)
                                        <td class="td-border text-center fw-b" style="padding: 2px 3px;">
                                            {{ number_format($totalsByModalidade[$modalidadeKey] ?? 0, 0, ',', '.') }}
                                        </td>
                                    @endforeach
                                </tr>
                            </table>
                        @endif
                    @endforeach
                </td>
            </tr>
        </table>

        <table class="text-block">
            <tr>
                <td class="left-text bold">Modalidade de Pagamento</td>
                <td class="right-text">
                    <p>Anual renovável. No caso de optarem por fraccionar o pagamento do prémio, não será imputado
                        qualquer encargo.</p>
                </td>
            </tr>
        </table>

        {{-- <table class="text-block">
            <tr>
                <td class="left-text bold">Encargos de Fraccionamento</td>
                <td class="right-text">
                    <p>Anual renovável. No caso de optarem por fraccionar o pagamento do prémio, não será imputado
                        qualquer encargo.</p>
                </td>
            </tr>
        </table> --}}

        <table class="text-block" >
            <tr>
                <td class="left-text bold">Encargos de <br> Fraccionamento</td>
                <td class="right-text" style="font-weight: 100!important; font-size: 200px">
                     <table style="width: 100%;">
                        <tr>
                            <td style="text-align:left">Mensal</td>
                            <td style="text-align:right">0%
                            </td>
                        </tr>
                    </table>
                    <hr style="margin-top:0px; margin-bottom:0px;">
                   
                     <table style="width: 100%;">
                        <tr>
                            <td style="text-align:left">Trimestral</td>
                            <td style="text-align:right">0%
                            </td>
                        </tr>
                    </table>
                    <hr style="margin-top:0px; margin-bottom:0px;">
                  
                    <table style="width: 100%;">
                        <tr>
                            <td style="text-align:left">Semestral</td>
                            <td style="text-align:right">0%
                            </td>
                        </tr>
                    </table>
                </td>
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

        <div class="another-application">
            <h5>
                <strong> Outras
                    Condições
                    Aplicáveis:</strong>

            </h5>
            <div class="another-application-detail">

                <h5>1. Validade da presente Cotação:</h5>
                <p class="valid-note">Estas condições são válidas por <strong>30 dias</strong>.</p>

                <h5>2. Política de inclusões:</h5>
                <p class="another-application-detail-opt"><span>a. Inclusão do cônjuge do Tomador do Seguro:</span></p>
                <p class="another-application-detail-opt-roman"> I. À inclusão de cônjuge por casamento ocorrido no
                    máximo há 30 dias serão aplicadas as
                    regras de carência em vigor para o Tomador do Seguro;</p>
                <p class="another-application-detail-opt-roman"> II. À inclusão de cônjuge por casamento ocorrido há
                    mais de 30 dias serão aplicados períodos
                    de carência a todas as coberturas do plano de seguro.
                </p>

                <p class="another-application-detail-opt"><span>b. Inclusão de dependentes do Tomador do Seguro
                        incluídos na apólice:</span></p>
                <p class="another-application-detail-opt-roman"> i. À inclusão de dependentes nascidos no máximo há 30
                    dias serão aplicadas as regras de
                    carência em vigor para o Tomador do Seguro;</p>
                <p class="another-application-detail-opt-roman"> ii. À inclusão de dependentes nascidos há mais de 30
                    dias serão aplicados períodos de
                    carência a todas as coberturas do plano de seguro.</p>

                <p class="another-application-detail-opt"><span>c. A facturação das inclusões</span> será efectuada no
                    método de pró-rata temporis pelo
                    período não decorrido da anuidade em curso.</p>



                <h5> 3. Política de exclusões:</h5>
                <p class="another-application-detail-opt"><span>a</span>. As exclusões de pessoas seguras que
                    tiveram consumos durante a anuidade não dará lugar a
                    qualquer devolução de prémio pelo período não decorrido;
                </p>
                <p class="another-application-detail-opt"><span>b</span>. As exclusões de pessoas seguras que não
                    tiveram consumos durante a anuidade dará lugar à
                    devolução de 50% do prémio correspondente ao período não decorrido;</p>
                <p class="another-application-detail-opt"><span>c</span>. A exclusão de cônjuges e/ou dependentes no
                    decurso de uma anuidade só será aceite desde
                    que devidamente justificada.
                </p>
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
                <p class="another-application-detail-opt"><span>c</span>. Caso o rácio de sinistralidade (total de
                    consumos/prémio simples) da apólice, referente ao mês
                    anterior ao de referência da ocorrência de flutuação cambial, seja inferior a 85%, o BIC Seguros
                    não aplicará, de imediato, a cláusula de flutuação cambial, reservando-se, no entanto, o direito
                    de aplicação da mesma retroactivamente à data da ocorrência da flutuação cambial, caso o
                    rácio de sinistralidade se altere para níveis superiores a 85%.</p>
                @php
                    $exchangeRateUsdAoa = $exchange_rate_usd_aoa ?? 938.311;
                    $exchangeRateUsdAoaFormatted = number_format((float) $exchangeRateUsdAoa, 3, ',', '.');
                @endphp
                <p class="another-application-detail-opt"><span>d</span>. À data da elaboração da presente proposta, o
                    câmbio de venda da banca comercial é de 1 USD
                    = {{ $exchangeRateUsdAoaFormatted }} AOA.</p>

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

            <h5 class="" style="color:rgb(22, 22, 22)">   <h5> BIC Seguros, S. A.</h5></h5>
            </p>
        </div>
        {{-- @for ($countBr = 0; $countBr < 50; $countBr++) <br>
            @endfor
            <div class="box-pag-anexo"></div> --}}
        @for ($countBr = 11; $countBr <= 18; $countBr++) <img
                src="img/cotacao/anexo-saude/pag-{{ $countBr }}.jpg" style="height: 1500px" alt="">
                @endfor
    </div>

</body>

</html>
