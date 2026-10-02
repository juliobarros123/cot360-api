@php
    $check = static fn (bool $value): string => $value ? 'X' : '';
@endphp

<div class="doc-page">
    <table class="doc-header">
        <tr>
            <td class="doc-title">DECLARAÇÃO DE ORIGEM/DESTINO DOS FUNDOS</td>
        </tr>
    </table>

    <div class="intro intro--odf">
        A presente declaração é prestada em conformidade com o disposto nos artigos 11.º, 13.º e 14.º da Lei n.º
        05/20, de 27 de Janeiro – Lei de Prevenção e Combate ao Branqueamento de Capitais, do Financiamento do
        Terrorismo e da Proliferação de Armas de Destruição em Massa, conjugado com os artigos 2.º e 20.º do
        Regulamento n.º 5/21 de 8 de Novembro, incluindo toda a legislação relacionada com a matéria e demais
        regulamentação pertinente da Agência de Regulação e Supervisão de Seguros e Fundos de Pensões (ARSEG).
        <br><br>
        O BIC Seguros, S.A., poderá contactar directamente o cliente, se for necessário, para prestação de informação
        ou documentação adicional.
    </div>

    <div class="section-bar section-bar--odf">IDENTIFICAÇÃO DA SOCIEDADE</div>
    <div class="mandatory">Obrigatório o preenchimento dos dados abaixo:</div>

    <table class="field-grid">
        <tr>
            <td width="28%">Denominação Social:</td>
            <td><div class="field-box">{{ $proposal->be_entity_denominacao_social ?? '' }}</div></td>
        </tr>
        <tr>
            <td>Endereço da sede social:</td>
            <td><div class="field-box">{{ $proposal->be_entity_endereco_sede ?? '' }}</div></td>
        </tr>
        <tr>
            <td></td>
            <td><div class="field-box tall"></div></td>
        </tr>
        <tr>
            <td>Número de Registo Comercial:</td>
            <td><div class="field-box">{{ $proposal->odf_registo_comercial ?? '' }}</div></td>
        </tr>
        <tr>
            <td>Número de Identificação Fiscal:</td>
            <td>
                <table style="width:100%; border-collapse: collapse;">
                    <tr>
                        <td width="55%"><div class="field-box">{{ $proposal->odf_nif ?? '' }}</div></td>
                        <td width="10%" style="padding-left: 18px;">CAE:</td>
                        <td width="35%"><div class="field-box">{{ $proposal->odf_cae ?? '' }}</div></td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td>Actividade Económica Principal:</td>
            <td><div class="field-box">{{ $proposal->odf_atividade_economica_principal ?? '' }}</div></td>
        </tr>
    </table>

    <div class="section-bar">ORIGEM DOS FUNDOS</div>

    <table class="choices">
        <tr>
            <td><span class="check-box">{{ $check(!empty($proposal->odf_origem_atividade_economica)) }}</span>Actividade Económica</td>
            <td><span class="check-box">{{ $check(!empty($proposal->odf_origem_investimento_estrangeiro)) }}</span>Investimento Estrangeiro</td>
        </tr>
        <tr>
            <td><span class="check-box">{{ $check(!empty($proposal->odf_origem_rendimentos_capital)) }}</span>Rendimentos de Capital</td>
            <td><span class="check-box">{{ $check(!empty($proposal->odf_origem_doacoes)) }}</span>Doações</td>
        </tr>
        <tr>
            <td><span class="check-box">{{ $check(!empty($proposal->odf_origem_emprestimo_bancario)) }}</span>Empréstimo Bancário</td>
            <td><span class="check-box">{{ $check(!empty($proposal->odf_origem_solidariedade_social)) }}</span>Solidariedade social</td>
        </tr>
        <tr>
            <td><span class="check-box">{{ $check(!empty($proposal->odf_origem_rendimentos_propriedades)) }}</span>Rendimentos de Propriedades</td>
            <td></td>
        </tr>
        <tr>
            <td style="vertical-align: top;">
                <span class="check-box">{{ $check(!empty($proposal->odf_origem_outras)) }}</span>Outras Origens
            </td>
            <td>
                Descreva:
                <div class="description-box">{{ $proposal->odf_origem_outras_descricao ?? '' }}</div>
            </td>
        </tr>
    </table>

    <div class="section-bar">IDENTIFICAÇÃO DA FINALIDADE DA TRANSACÇÃO</div>

    <div class="purpose-text">
        Na qualidade de procurador, com poderes bastantes para movimentação, conforme ficha de assinaturas, declaro
        que os activos apresentados para efeito de operações no mercado de seguros, no valor de
        <span class="inline-box value">{{ $proposal->odf_finalidade_valor ?? '' }}</span>
        por extenso
        <span class="inline-box extenso">{{ $proposal->odf_finalidade_valor_extenso ?? '' }}</span>
    </div>
    <div class="full-box"></div>

</div>

<div class="doc-page">
    <table class="doc-header">
        <tr>
            <td class="doc-title">DECLARAÇÃO DE ORIGEM/DESTINO DOS FUNDOS</td>
        </tr>
    </table>

    <div class="section-bar section-bar--pep">DECLARAÇÃO</div>

    <div class="declaration-text">
        {{ $proposal->declaracao_texto ?? 'Declaro que as informações prestadas são verdadeiras. Tendo conhecimento das disposições legais aplicáveis, autorizo o BIC Seguros, SA., a partilhar com as autoridades competentes, as informações aqui declaradas.' }}
    </div>

    <div class="signature-block">
        <div class="signature-wrapper">
            <div class="signature-left">
                <div class="signature-spacer"></div>
                <div class="signature-line-center">
                    {{ $proposal->declaracao_representante_assinatura ?? '' }}
                </div>
                <div class="signature-label-center">(Assinatura do Representante Legal)</div>
            </div>
            <div class="signature-right">
                <div class="signature-spacer"></div>
                <div class="signature-date">
                    Data: {{ $proposal->declaracao_representante_data ?? '' }}
                </div>
            </div>
        </div>
    </div>

    <div class="office-box">
        <div class="office-title">A preencher pelo BIC Seguros</div>
        <div class="office-content">
            <div class="signature-office">
                <div class="signature-left">
                    <div class="signature-spacer"></div>
                    <div class="signature-line-center">
                        {{ $proposal->declaracao_operacao_assinatura ?? '' }}
                    </div>
                    <div class="signature-label-center">(Assinatura Responsável pela Operação)</div>
                </div>
                <div class="signature-right">
                    <div class="signature-spacer"></div>
                    <div class="signature-date">
                        Data: {{ $proposal->declaracao_operacao_data ?? '' }}
                    </div>
                </div>
            </div>

            <div class="signature-office">
                <div class="signature-left">
                    <div class="signature-spacer"></div>
                    <div class="signature-line-center">
                        {{ $proposal->declaracao_compliance_assinatura ?? '' }}
                    </div>
                    <div class="signature-label-center">(Assinatura Compliance Officer)</div>
                </div>
                <div class="signature-right">
                    <div class="signature-spacer"></div>
                    <div class="signature-date">
                        Data: {{ $proposal->declaracao_compliance_data ?? '' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
