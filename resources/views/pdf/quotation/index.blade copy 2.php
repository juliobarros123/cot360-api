<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title></title>
</head>

<body
    style="background-image: url('img/cotacao/border.jpg');background-position: top left;
background-repeat: no-repeat;
background-image-resize: 2;
background-image-resolution: from-image;">

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
            <h1 class="proposal-title-title color-black">Proposta de Seguro {{ $selectedBranch['name'] ?? '' }}</h1>
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
            <p class="fw-b color-black text-right">{{ now()->format('d \d\e F \d\e Y') }}</p>
        </div>


        <!-- Page 2 -->
        <br><br>
        <h5 class="page-title color-black">PROPOSTA DE SEGURO ACIDENTES DE TRABALHO</h5>
        <h5 class="quote-number red quote-pag" style="margin-top: 3px;">
            COTAÇÃO Nº 0000370/{{ now()->format('Y') }}
        </h5>
        <br><br>
        <table class="text-block">
            <tr>
                <td class="left-text bold">Segurados</td>
                <td class="right-text">
                    <p>Todos os trabalhadores da Empresa</p>
                    <p>compreendendo:</p>
                    <p>- os vinculados por contrato de trabalho ou contrato legalmente equiparado</p>
                    <p>- os aprendizes, praticantes, estagiários e demais situações que devam considerar-se de formação
                        prática.</p>
                    <p>- todo aquele que, considerando-se na dependência económica do tomador de seguro, preste em
                        conjunto ou isoladamente determinado serviço.</p>
                </td>
            </tr>
        </table>

        @if ($caeSelected)
            <table class="text-block">
                <tr>
                    <td class="left-text bold">Código de Actividade <br> Económica (CAE)</td>
                    <td class="right-text">
                        <p>{{ $caeSelected['cae'] ?? '' }} - {{ $caeSelected['description'] ?? '' }}</p>
                    </td>
                </tr>
            </table>
        @endif

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
                    <p>Início do Seguro: às 00h00 de …./…./…… (Em data a indicar)</p>
                    <p>_________________________________________________________ <br> Período do Contrato: Anual
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
                    <p>Todos os acidentes que se verifique no exercício da actividade laboral ao serviço da empresa e/ou
                        doenças profissionais que provoquem lesão ou danos corporais ao trabalhador.</p>
                    <p>Considera-se também acidente de trabalho, o ocorrido no trajecto habitual de ida e de regresso do
                        local de trabalho (in itinere).</p>
                    <p>Os sinistrados têm direito ao tratamento e cura da lesão, adequadas ao estabelecimento do estado
                        de saúde e da capacidade de trabalho ou de ganho e à sua recuperação para a vida activa.</p>
                </td>
            </tr>
        </table>

        <!-- Page 3 -->

        <table class="text-block" style="height: 400px">
            
            <tr>
                <td class="left-text bold">Coberturas e Capitais Seguros</td>
                <td class="right-text">
                    <table class="tb_ccs">
                        <tr>
                            <td class="td-title" style="width: 70%">Coberturas</td>
                            <td class="td-title" style="width: 30%">Capitais Seguros</td>
                        </tr>
                        <tr>
                            <td class="td-coverage">
                                <div class="coverage-block">
                                    <strong class="sublinhado">Cobertura Obrigatória (Lei 35/09)</strong>
                                    <div class="coverage-content">
                                        @foreach ($coverages as $coverage)
                                            @if ($coverage['type_covt'] === 'O')
                                                <div class="coverage-item"><span class="span-space">spa</span> <img
                                                        src="img/cotacao/icone-item.png" class="icone_item"
                                                        alt=""> {{ $coverage['name'] }}</div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            </td>
                            <td style="border-right-color: transparent !important;">
                                <div class="coverage-content">
                                    <div class="coverage-item">Conforme anexo A</div>
                                    <div class="coverage-item">Conforme anexos B e C</div>
                                </div>
                            </td>
                        </tr>
                        @foreach ($coverages as $coverage)
                            <tr>
                                <td style="border-left-color: transparent !important;">
                                    <div class="coverage-block">
                                        <div class="coverage-item">
                                            <strong class="sublinhado">{{ $coverage['name'] }}</strong>
                                            <div class="subcoverage">
                                                @foreach ($coverage['subcoverages'] as $sub)
                                                    <div class="coverage-item"><span class="span-space">spa</span> <img
                                                            src="img/cotacao/icone-item.png" class="icone_item"
                                                            alt=""><span
                                                            class="span-space">s</span>{{ $sub['name'] }}</div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td style="border-right-color: transparent !important;">
                                    <div class="coverage-content">
                                        <div class="coverage-item">N/A</div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </table>
                </td>
            </tr>
        </table>

        <table class="text-block">
            <tr>
                <td class="left-text bold">Condições Especiais</td>
                <td class="right-text">
                    <p>Não Aplicável</p>
                </td>
            </tr>
        </table>

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
                                <td class="td-title" style="border-left-color: transparent;">Coberturas</td>
                                <td class="td-title" style="border-right-color: transparent;">Capitais Seguros</td>
                            </tr>
                            <tr>
                                <td style="border-left-color: transparent;">
                                    <div class="coverage-item">
                                        <strong class="sublinhado">Cobertura Obrigatória (Lei 35/09)</strong>
                                        <div class="coverage-content">
                                            @foreach ($coverages as $coverage)
                                                @if ($coverage['type_covt'] === 'O')
                                                    <div class="coverage-list">{{ $coverage['name'] }}</div>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                </td>
                                <td style="border-right-color: transparent;">
                                    <div class="coverage-content">
                                        <div>Sem franquia</div>
                                        <div>Sem franquia</div>
                                    </div>
                                </td>
                            </tr>
                            @foreach ($coverages as $coverage)
                                <tr>
                                    <td style="border-left-color: transparent;">
                                        <div class="coverage-item">
                                            <strong class="sublinhado">{{ $coverage['name'] }}</strong>
                                            <div class="subcoverage">
                                                @foreach ($coverage['subcoverages'] as $sub)
                                                    <div class="coverage-list">{{ $sub['name'] }}</div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </td>
                                    <td style="border-right-color: transparent;">
                                        <div class="coverage-content">
                                            <div>N/A</div>
                                        </div>
                                    </td>
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
                    @if (!$caeSelected)
                        <p>Para cobertura dos riscos acima indicados e sujeito aos períodos e capitais acima referidos,
                            será aplicada a seguinte taxa simples:</p>
                        <p>1,75% a incidir sobre o Capital a Segurar (Salários Seguros).</p>
                        <p>Ao prémio simples assim calculado será acrescido dos encargos legais em vigor à data da
                            emissão, sendo, actualmente, de 20% e dos impostos legais quando aplicável.</p>
                        <p class="text-right">Moeda: Kz</p>
                        <p class="premium-line">Prémio Simples<span
                                class="premium-value">{{ number_format($premiosGlobais['premioSimples'], 2, ',', '.') }}
                                Kz</span></p>
                        <p class="premium-line">Encargos (20%)<span
                                class="premium-value">{{ number_format($premiosGlobais['premioEncargos'], 2, ',', '.') }}</span>
                        </p>
                        <p class="premium-line">IVA (14%)<span
                                class="premium-value">{{ number_format($premiosGlobais['iva'], 2, ',', '.') }}</span>
                        </p>
                        <p class="premium-line">Prémio Total Anual<span
                                class="premium-value">{{ number_format($premiosGlobais['premioTotalAnual'], 2, ',', '.') }}</span>
                        </p>
                        <p class="premium-line">Prémio Total Mensal<span
                                class="premium-value">{{ number_format($premiosGlobais['premioTotalMensal'], 2, ',', '.') }}</span>
                        </p>
                    @else
                        <p>Neste tipo de contrato não são aplicadas franquias</p>
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
                    <td class="right-text">
                        <div class="mod">Mensal <span>7%</span></div>
                        <hr style="margin-top:0px; margin-bottom:0px;">
                        <div class="mod">Trimestral <span>5%</span></div>
                        <hr style="margin-top:0px; margin-bottom:0px;">
                        <div class="mod">Semestral <span>3%</span></div>
                        <hr style="margin-top:0px; margin-bottom:0px;">
                    </td>
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
        <div class="important-info">
            <h5 class="quote-number quote-pag bold">INFORMAÇÃO IMPORTANTE</h5>
            <p class="info-importante">
                Os valores da presente cotação assentam num conjunto de pressupostos, como sejam os dados que nos
                indicou e a inexistência de factores de agravamento de risco, pressupostos estes sujeitos a
                confirmação por parte do Segurador. <br>
                O Segurador pode solicitar informação adicional e/ou confirmar os dados indicados, podendo daqui
                resultar a não verificação, no caso concreto, dos pressupostos sobre os quais assentou a presente
                cotação e, consequentemente, a não-aceitação do seguro nas condições referidas na mesma. Deste modo,
                importa notar que o prémio desta cotação e a verificação dos respectivos pressupostos carecem de
                confirmação e aceitação por parte do Segurador. <br>
                Para os dados indicados, apurou-se o prémio acima que inclui cargas fiscais e parafiscais em vigor à
                data da cotação. Qualquer alteração legal posterior às cargas fiscais ou parafiscais aplicáveis,
                terá que ser considerada pelo Segurador aquando da emissão do contrato. Esta cotação tem validade
                acima indicada, sem prejuízo da possibilidade, aquando da emissão do contrato, de alteração do
                prémio apurado caso ocorra uma alteração legal posterior à data da cotação que determine a
                modificação das cargas fiscais ou parafiscais aplicáveis. <br>
            </p>
            <p class="quote-number quote-pag bold">BIC Seguros, S.A.</p>
        </div>

        <div class="signature"></div>



        <!-- Page 6 (Anexo A) -->

    </div>
</body>

</html>
