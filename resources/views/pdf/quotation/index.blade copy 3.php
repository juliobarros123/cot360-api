<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title></title>
</head>

<body>

    <div class="logo-container"><img src="img/cotacao/icone_capa.jpeg" class="icone_capa" alt=""></div>
    <div class="pdf-container" id="pdfContent">
        <!-- Page 1 -->

        <div class="page-border"></div>
        <div class="header">
            <img class="title-img" src="img/cotacao/title.png" alt="">
            <h5 class="area-corrector">ÁREA DE CORRETORES</h5>
        </div>

        <div class="box-cover">

            <img class="cover" src="{{ $cover }}" alt="">
        </div>

        <div class="proposal-title">
            <h1 class="proposal-title-title color-black fw-b">
                <strong>Proposta de Seguro {{ $selectedBranch['name'] ?? '' }}</strong>
            </h1>
            <h5 class=" color-black ">Cotação nº 000014_V2/{{ now()->format('Y') }}</h5>
        </div>

        <div class="intro-text">

            <p>

                Agradecemos a oportunidade concedida à BIC Seguros para apresentar esta proposta de colaboração, a
                qual fica sujeita a eventuais reajustamentos que sejam julgados convenientes. A nossa sugestão é que
                considerem o conjunto dos serviços oferecidos, e dos meios técnicos e humanos que colocamos à vossa
                disposição, como as ferramentas que permitirão obter um serviço de Excelência, dando cumprimento às
                vossas expectativas.
                <br>
                <br>
                Será para nós uma honra a colaboração com uma tão prestigiada entidade na implementação e eventual
                redefinição da vossa política de seguros e, portanto, empenhar-nos-emos a fundo para que todos os
                objectivos definidos possam ser alcançados.
            </p>
        </div>

        <div class="date-text">
            <p class="fw-b color-black " style="text-align: right">{{ now()->format('d \d\e F \d\e Y') }}</p>
        </div>


        <!-- Page 2 -->
        <br><br> <br>
        <div class="page-title color-black">PROPOSTA DE SEGURO ACIDENTES DE TRABALHO</div>
        <div class="page-sub-title red" style="margin-top: 3px;">
            COTAÇÃO Nº 0000370/{{ now()->format('Y') }}
        </div>
        <br><br>


        <table class="text-block">
            <tr>
                <td class="left-text bold">Tomador do Seguro</td>
                <td class="right-text">
                    <p> <strong>{{ $entity['name'] ?? '' }} </strong><br>
                        Luanda, Angola
                        <br>
                        NIF: {{ $entity['nif'] ?? '' }}
                    </p>
                </td>
            </tr>
        </table>
        @if ($caeSelected)
            <table class="text-block">
                <tr>
                    <td class="left-text bold">Segurados</td>
                    <td class="right-text">
                        <p><strong> Todos os trabalhadores</strong> da <strong>{{ $entity['name'] ?? '' }}</strong> </p>
                        <p>compreendendo:</p>
                        <p> &nbsp;&nbsp;
                            &nbsp;&nbsp;-
                            &nbsp;&nbsp;
                            &nbsp;&nbsp; os vinculados por contrato de trabalho ou contrato legalmente

                            &nbsp;&nbsp; &nbsp;&nbsp;
                            &nbsp;&nbsp; &nbsp;&nbsp;
                            &nbsp;&nbsp; &nbsp;&nbsp;
                            &nbsp;&nbsp; &nbsp;&nbsp;
                            &nbsp;&nbsp; &nbsp;&nbsp;

                            equiparado</p>
                        <p> &nbsp;&nbsp;
                            &nbsp;&nbsp;- &nbsp;&nbsp;
                            &nbsp; os aprendizes, praticantes, estagiários e demais situações que devam
                            &nbsp;&nbsp;
                            &nbsp;&nbsp; &nbsp;&nbsp;
                            &nbsp;&nbsp; &nbsp;&nbsp;
                            &nbsp;&nbsp;
                            considerar-se de
                            formação
                            prática.</p>
                        <p> &nbsp;&nbsp;
                            &nbsp;&nbsp;- &nbsp;&nbsp;
                            &nbsp;&nbsp;todo aquele que, considerando-se na dependência económica do tomador
                            &nbsp;&nbsp;
                            &nbsp;&nbsp; &nbsp;&nbsp;
                            &nbsp;&nbsp;
                            de
                            seguro, preste em
                            conjunto ou isoladamente determinado serviço.</p>
                    </td>
                </tr>
            </table>
        @endif
        <table class="text-block">
            <tr>
                <td class="left-text bold">Código de Actividade <br> Económica (CAE)</td>
                <td class="right-text">
                    @if ($caeSelected)
                        <p>{{ $caeSelected['cae'] ?? '' }} - {{ $caeSelected['description'] ?? '' }}</p>
                    @else
                        <p>A indicar</p>
                    @endif

                </td>
            </tr>
        </table>


        <table class="text-block">
            <tr>
                <td class="left-text bold">Local de Risco</td>
                <td class="right-text">
                    <p class="bold">Angola</p>
                </td>
            </tr>
        </table>

        <table class="text-block">
            <tr>
                <td class="left-text bold">Períodos e Datas</td>
                <td class="right-text">
                    <p style="padding: 0;margin: 0;">Início do Seguro: &nbsp;&nbsp;
                        &nbsp;&nbsp; &nbsp;&nbsp;
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; às
                        00h00 de …./…./…… (Em data a indicar)</p>
                    <p style="padding: 0;margin: 0;">
                        <hr style="padding: 0;margin: 0; "> <br> Período
                        do Contrato: &nbsp;&nbsp;
                        &nbsp;&nbsp; &nbsp;&nbsp;
                        &nbsp;&nbsp; Anual
                        Renovável
                    </p>
                </td>
            </tr>
        </table>

        <table class="text-block">
            <tr>
                <td class="left-text bold">Tipo de Contrato</td>
                <td class="right-text">
                        @if ($selectedBranch['code'] ?? '' === 90)
                        <p>Automóvel</p>
                    @elseif($selectedBranch['code'] ?? '' === 91)
                        <p>Automóvel Frota</p>
                    @elseif($selectedBranch['code'] ?? '' === 10)
                        <p>Acidentes Trabalho</p>
                    @endif
                </td>
            </tr>
        </table>

        <table class="text-block">
            <tr>
                <td class="left-text bold">Âmbito da Cobertura</td>
                <td class="right-text">
                    @if ($caeSelected)
                        <p>Todos os acidentes que se verifique no exercício da actividade laboral ao serviço da empresa
                            e/ou
                            doenças profissionais que provoquem lesão ou danos corporais ao trabalhador.</p>
                        <p>Considera-se também acidente de trabalho, o ocorrido no trajecto habitual de ida e de
                            regresso do
                            local de trabalho (in itinere).</p>
                        <p>Os sinistrados têm direito ao tratamento e cura da lesão, adequadas ao estabelecimento do
                            estado
                            de saúde e da capacidade de trabalho ou de ganho e à sua recuperação para a vida activa.</p>
                    @else
                        <p> Proporcionar, a defesa dos legítimos interesses dos lesados em acidentes de
                            viação assim como, a segurança pessoal e patrimonial dos proprietários de
                            veículos.</p>
                    @endif

                </td>
            </tr>
        </table>

        <!-- Page 3 -->
        @if (!$caeSelected)


            <table class="text-block">

                <tr>
                    <td class="left-text bold">Coberturas e Capitais Seguros</td>
                    <td class="right-text">
                        <table class="tb_ccs">
                            <tr>
                                <td class="td-title"
                                    style="width: 70%;border-left-color: transparent !important; border-top-color: red!important">
                                    Coberturas</td>
                                <td class="td-title"
                                    style="width: 30%;border-right-color: transparent !important; border-top-color: red!important">
                                    Capitais Seguros</td>
                            </tr>
                            <tr>
                                <td class="td-coverage" style="border-left-color: transparent !important;">
                                    <div class="coverage-block">
                                        <strong class="sublinhado">Cobertura Obrigatória (Lei 35/09)</strong>
                                        <div class="coverage-content">
                                            @foreach ($coverages as $coverage)
                                                @if ($coverage['type_covt'] === 'O')
                                                    <div class="coverage-item tt-capitalize"><span
                                                            class="span-space">sp</span> <img
                                                            src="img/cotacao/icone-item.png" class="icone_item"
                                                            alt=""> {{ $coverage['name'] }}</div>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                </td>
                                <th style="border-right-color: transparent !important;font-weight: 100!important;">
                                    <div class="coverage-content">
                                        <div class="coverage-item" style=" text-align: center!important;">Conforme anexo
                                            A</div>
                                        <div class="coverage-item">Conforme anexos B e C</div>
                                    </div>
                                </th>
                            </tr>
                            @foreach ($coverages as $coverage)
                                <tr>
                                    <td style="border-left-color: transparent !important;">
                                        <div class="coverage-block">
                                            <div class="coverage-item">
                                                <strong
                                                    class="sublinhado tt-capitalize">{{ $coverage['name'] }}</strong>
                                                <div class="subcoverage">
                                                    @foreach ($coverage['subcoverages'] as $sub)
                                                        @if (selectedCoverage($QLI, $coverage, $sub['id']))
                                                            <div class="coverage-item tt-capitalize"><span
                                                                    class="span-space">sp</span> <img
                                                                    src="img/cotacao/icone-item.png" class="icone_item"
                                                                    alt=""><span
                                                                    class="span-space">s</span>{{ $sub['name'] }}
                                                            </div>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <th style="border-right-color: transparent !important; font-weight: 100!important;">
                                        <div class="coverage-content">
                                            <div class="coverage-item">N/A</div>
                                        </div>
                                    </th>
                                </tr>
                            @endforeach
                        </table>
                    </td>
                </tr>
            </table>
        @endif
        <table class="text-block">
            <tr>
                <td>*O valor seguro deverá ser automaticamente alterado de acordo com a tabela de desvalorização
                    existente para
                    o seguro Danos Próprios sendo o respectivo prémio ajustado à desvalorização do valor seguro. </td>
            </tr>
        </table>
        @if ($caeSelected)
            <br>
            <br><br><br>
            <table class="text-block">
                <tr>
                    <td class="left-text bold">Condições Especiais</td>
                    <td class="right-text">
                        <p>- Cobertura automática de deslocações ao estrangeiro até 30 dias, gratuita
                            e sem aviso prévio, incluindo as despesas efectuadas no estrangeiro
                            relativas a Assistência Médica, Medicamentosa ou Hospitalar e ao
                            transporte de repatriamento;
                        </p>
                        <p>
                            - Em caso de incapacidade Temporária Absoluta Superior ou inferior a 30
                            dias fica salvaguardado que, mediante autorização expressa do tomador do
                            seguro corroborada pelo corpo clínico da seguradora ou, em período
                            inferior mediante autorização idêntica e desde que clinicamente
                            justificável, os sinistros poderão ser assistidos clinicamente em Portugal,
                            estando incluída todas as despesas médicas efetuadas relativas à
                            assistência medica, medicamentosa ou Hospitalar e ao Transporte ou
                            Repatriamento, bem como as indemnizações devidas por incapacidade
                            temporária
                        </p>
                        <p>
                            - Garantia de cobertura dos trabalhos em ambiente offshore</p>
                    </td>
                </tr>
            </table>
        @endif

        @if ($caeSelected)
            <table class="text-block">
                <tr>
                    <td class="left-text bold">Capitais a Segurar</td>
                    <td class="right-text">
                        <p class="bold">{{ number_format($premiosGlobais['premioSimples'], 2, ',', '.') }} KZ (13
                            meses)</p>
                        <p>A determinação da retribuição segura (salário seguro), valor sobre o qual são calculadas as
                            responsabilidades da seguradora, é sempre da responsabilidade do Tomador e deverá
                            corresponder, tanto na data da celebração do contrato como em qualquer momento da sua
                            vigência, a tudo o que a lei considera como elemento integrante do salário, incluindo
                            subsídio de alimentação, bem como outras prestações em espécie ou dinheiro que revistam
                            carácter de regularidade1 e ainda os subsídios de férias e natal.</p>
                    </td>
                </tr>
            </table>
        @endif

        <table class="text-block">
            <tr>
                <td class="left-text bold">Franquias Deduzíveis por Sinistro</td>
                <td class="right-text">
                    @if ($caeSelected)
                        <p>Neste tipo de contrato não são aplicadas franquias</p>
                    @else
                        <table class="tb_ccs">
                            <tr>
                                <td class="td-title"
                                    style="width: 70%;border-left-color: transparent !important; border-top-color: red!important">
                                    Coberturas</td>
                                <td class="td-title"
                                    style="width: 30%;border-right-color: transparent !important; border-top-color: red!important">
                                    Capitais Seguros</td>
                            </tr>
                            <tr>
                                <td class="td-coverage" style="border-left-color: transparent !important;">
                                    <div class="coverage-block">
                                        <strong class="sublinhado">Cobertura Obrigatória (Lei 35/09)</strong>
                                        <div class="coverage-content">
                                            @foreach ($coverages as $coverage)
                                                @if ($coverage['type_covt'] === 'O')
                                                    <div class="coverage-item tt-capitalize"><span
                                                            class="span-space">sp</span> <img
                                                            src="img/cotacao/icone-item.png" class="icone_item"
                                                            alt=""> {{ $coverage['name'] }}</div>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                </td>
                                <th style="border-right-color: transparent !important;font-weight: 100!important;">
                                    <div class="coverage-content">
                                        <div class="coverage-item" style=" text-align: center!important;">Sem franquia

                                        </div>
                                        <div class="coverage-item"> Sem franquia</div>
                                    </div>
                                </th>
                            </tr>
                            @foreach ($coverages as $coverage)
                                <tr>
                                    <td style="border-left-color: transparent !important;">
                                        <div class="coverage-block">
                                            <div class="coverage-item">
                                                <strong
                                                    class="sublinhado tt-capitalize">{{ $coverage['name'] }}</strong>
                                                <div class="subcoverage">
                                                    @foreach ($coverage['subcoverages'] as $sub)
                                                        @if (selectedCoverage($QLI, $coverage, $sub['id']))
                                                            <div class="coverage-item tt-capitalize"><span
                                                                    class="span-space">sp</span> <img
                                                                    src="img/cotacao/icone-item.png"
                                                                    class="icone_item" alt=""><span
                                                                    class="span-space">s</span>{{ $sub['name'] }}
                                                            </div>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <th
                                        style="border-right-color: transparent !important; font-weight: 100!important;">
                                        <div class="coverage-content">
                                            @if ($coverage['rate_type'] == Str::upper('FIXED'))
                                                @php
                                                    // dd(findQLI($QLI, $coverage));
                                                @endphp
                                            @endif
                                            @php
                                                $array = isset(findQLI($QLI, $coverage)['rate']);
                                                // dd($array);
                                            @endphp
                                            <div class="coverage-item">2
                                            </div>


                                            {{-- @if (isset(findQLI($QLI, $coverage)['rate']))
                                                @foreach (findQLI($QLI, $coverage)['rate'] as $index => $number)
                                                    <div class="coverage-item">{{ $number[$index] }}
                                                    </div>
                                                @endforeach
                                            @endif --}}

                                        </div>
                                    </th>
                                </tr>
                            @endforeach
                        </table>

                    @endif
                </td>
            </tr>
        </table>

        <table class="text-block">
            <tr>
                <td class="left-text bold">Taxa e Prémio Total</td>
                <td class="right-text">
                    @if ($caeSelected)
                        <p>Para cobertura dos riscos acima indicados e sujeito aos períodos e capitais acima referidos,
                            será aplicada a seguinte taxa simples:</p>
                        <br>
                        <p>
                            &nbsp;&nbsp;<img src="img/cotacao/icone-item.png" class="icone_item" alt=""> Taxa
                            simples
                            - <strong>2,00%</strong> a incidir sobre o Capital a Segurar (Salários
                            Seguros), o que corresponde a Taxa Total de <strong>2,74%</strong>


                        </p>
                        <br>
                        <p>Ao prémio simples assim calculado será acrescido dos encargos legais em vigor à data da
                            emissão, sendo, actualmente, de 20% e dos impostos legais quando aplicável.</p>
                        <br>
                        <span class="span-space">
                            @for ($b = 0; $b < 54; $b++)
                                {{ '-' }}
                            @endfor
                        </span>

                        <small>Moeda: Kz</small>

                        {{-- <p class="premium-line">Prémio Simples <span class="span-space">
                                @for ($b = 0; $b < 40; $b++)
                                    {{ '-' }}
                                @endfor
                            </span>
                            <span
                                class="premium-value">
                            </span>
                        </p> --}}
                        <table>
                            <tr class="tr_taxa_premio">
                                <td>Prémio Simples</td>
                                {{-- <hr style="margin-top:0px; margin-bottom:0px;"> --}}

                                <td class="text-right">
                                    {{ number_format($premiosGlobais['premioSimples'], 2, ',', ' ') }}</td>


                            </tr>

                            <tr class="tr_taxa_premio">
                                <td>Encargos (20%)</td>
                                <td class="text-right">
                                    {{ number_format($premiosGlobais['premioEncargos'], 2, ',', ' ') }}</td>
                            </tr>
                            <tr class="tr_taxa_premio">
                                <td>IVA (14%)</td>
                                <td class="text-right">
                                    {{ number_format($premiosGlobais['iva'], 2, ',', ' ') }}</td>
                            </tr>
                            <tr class="tr_taxa_premio">
                                <td> <strong>Prémio Total Anual</strong> </td>
                                <td class="text-right">
                                    <strong> {{ number_format($premiosGlobais['premioTotalAnual'], 2, ',', ' ') }}
                                    </strong>


                                </td>
                            </tr>
                            <tr class="tr_taxa_premio">
                                <td><strong>Prémio Total Mensal</strong></td>
                                <td class="text-right">
                                    <strong> {{ number_format($premiosGlobais['premioTotalMensal'], 2, ',', ' ') }}
                                    </strong>

                                </td>
                            </tr>
                        </table>
                    @else
                        <p>Conforme anexos A, B e C, à presente Cotação</p>
                    @endif
                </td>
            </tr>
        </table>

        <table class="text-block">
            <tr>
                <td class="left-text bold">Modalidade de Pagamento</td>
                <td class="right-text">
                    @if (!$caeSelected)
                        <p>Anual Renovável, sendo que caso se opte por fraccionar o prémio será imputado os respectivos
                            encargos de fraccionamento.</p>
                    @else
                        <p>Anual/Semestral/Trimestral/Mensal</p>
                    @endif
                </td>
            </tr>
        </table>

        @if (!$caeSelected)
            <table class="text-block">
                <tr>
                    <td class="left-text bold">Encargos de <br> Fraccionamento</td>
                    <th class="right-text" style="font-weight: 100!important;">
                        <div>
                            <span style=""> Mensal</span>
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            &nbsp;&nbsp;
                            &nbsp;&nbsp;
                            &nbsp;&nbsp;
                            <span style="">7%</span>
                        </div>
                        <hr style="margin-top:0px; margin-bottom:0px;">
                        <div>
                            <span style=""> Trimestral</span>
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            &nbsp;&nbsp;
                            &nbsp;

                            <span style="">5%</span>
                        </div>

                        <hr style="margin-top:0px; margin-bottom:0px;">
                        <div>
                            <span style=""> Semestral</span>
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

                            &nbsp;&nbsp;
                            &nbsp;&nbsp;
                            <span style="">3%</span>
                        </div>
                    </th>
                </tr>
            </table>
        @endif

        <table class="text-block">
            <tr>
                <td class="left-text bold">Outras Condições Aplicáveis</td>
                <td class="right-text">
                    <p>Estas condições são válidas por 30 dias.</p>
                </td>
            </tr>
        </table>
        <br>
        <div class="important-info">
            <h5 class="" style="margin-bottom:0px">INFORMAÇÃO IMPORTANTE</h5>
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

            <h5 class=""> BIC Seguros, S.A.</h5>
            </p>
        </div>

        @if (!$caeSelected)
            <br>
            <br><br><br>
            <div class="attachment-title bold sublinhado color-black">ANEXO A</div>
            <br>
            <table class="tb_ccs attachment-table " style="margin-top: 0px;">
                <thead>
                    <tr>
                        @php
                            $colspan = 8 + count($coverages);
                            $coverasSingla = '';
                            foreach ($coverages as $index => $cov) {
                                $coverasSingla .= $cov['short_name'] . ($index % 2 != 0 ? '+' : '');
                            }
                        @endphp
                        <th colspan="{{ $colspan }}">
                            <strong> Cotação_{{ Str::upper($entity['name']) }}_AUTO_{{ $coverasSingla }}_Nº_533_V2
                            </strong>
                        </th>
                    </tr>
                    <tr>
                        <th>Nº</th>
                        @foreach ($attributesTablePDF['titlesObjectsHeadPDF'] as $td)
                            <th>
                                <strong>{{ $td['valuetd'] }}</strong>
                            </th>
                        @endforeach

                        @foreach ($coverages as $coverage)
                            <th class="text-center bold  py-1">
                                <div class="d-flex align-items-center justify-content-center">
                                    <span> <strong>{{ $coverage['short_name'] }} (KZ)</strong> </span>
                                </div>
                            </th>
                        @endforeach

                        <th> <strong>Prémio Total (KZ)</strong> </th>
                    </tr>
                    @foreach ($objects as $index => $object)
                        <tr>
                            <th>{{ $index + 1 }}</th>
                            @foreach ($attributesTablePDF['titlesObjectsHeadPDF'] as $td)
                                <th class="text-center">
                                    @if ($td['name'] === 'capital_value' || $td['name'] === 'engine_min')
                                        {{ number_format($object[$td['name']], 0, ',', '.') }}
                                    @elseif($td['name'] === 'brand_model')
                                        {{ $object['brand'] }} {{ $object[$td['name']] }}
                                    @else
                                        {{ $object[$td['name']] }}
                                    @endif
                                </th>
                            @endforeach
                            @foreach ($coverages as $coverage)
                                <th class="text-center  py-1">
                                    <div class="d-flex align-items-center justify-content-center">
                                        <span>{{ number_format(checkPremium($object, $coverage, $QLI), 0, ',', ' ') }}
                                        </span>
                                    </div>
                                </th>
                            @endforeach
                            <th class="text-right  py-1">
                                <div class="d-flex align-items-center justify-content-center">
                                    <span>{{ number_format(checkPremiumTotal($object, $coverage, $QLI), 0, ',', ' ') }}</span>
                                </div>
                            </th>
                        </tr>
                    @endforeach
                <tfoot>
                    <tr>
                        <td colspan="{{ count($attributesTablePDF['titlesObjectsHeadPDF']) + 1 }}"
                            style="text-align: right;"><strong>Total Anual</strong></td>

                        @foreach ($coverages as $coverage)
                            <td class="text-center">
                                <strong>{{ number_format(checkPremiumTotalByCoverage($coverage, $QLI), 0, ',', ' ') }}</strong>
                            </td>
                        @endforeach
                        <td class="text-right">
                            <strong>{{ number_format(checkPremiumTotalGeral($QLI), 0, ',', ' ') }}</strong>
                        </td>

                    </tr>
                </tfoot>

                </thead>
                <tbody>

                </tbody>

            </table>
        @endif
    </div>
</body>

</html>
