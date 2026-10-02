@php
    $isPep = in_array($proposal->pep_status ?? null, ['PEP', 'Related_PEP'], true);
    $isNotPep = !$isPep;
    $isActive = ($proposal->pep_activity_status ?? null) === 'active';
    $isInactive = ($proposal->pep_activity_status ?? null) === 'inactive';
    $check = static fn (bool $value): string => $value ? 'X' : '';
    $formatDate = static fn (?string $value): string => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '';
@endphp

<div class="doc-page">
    <table class="doc-header">
        <tr>
            <td class="doc-title">DECLARAÇÃO DE PESSOA POLITICAMENTE EXPOSTA</td>
        </tr>
    </table>

    <div class="intro">
        Nos termos da Lei n.º 05/20, de 27 de Janeiro – Lei de Prevenção e Combate ao Branqueamento de Capitais,
        do Financiamento do Terrorismo e da Proliferação de Armas de Destruição em Massa, consideram-se Pessoas
        Politicamente Expostas os indivíduos nacionais ou estrangeiros que desempenham ou desempenharam funções
        públicas proeminentes em Angola, ou em qualquer outro País ou jurisdição ou em qualquer organização
        internacional, assim como seus representantes, membros da família, parentes até o terceiro (3.º) grau
        colateral e outras pessoas do seu relacionamento próximo.
    </div>

    <div class="list-block">
        Para efeitos da referida lei, consideram-se altos cargos de natureza política ou pública, de entre outros, os
        seguintes:
        <ol class="pep-list" type="i">
            <li>Presidente da República ou Chefe de Estado;</li>
            <li>Vice-Presidente da República;</li>
            <li>Órgãos Auxiliares do Presidente República, ou membros do Governo, designadamente Ministros de Estado, Ministros, Secretários de Estado e outros cargos ou funções equiparadas;</li>
            <li>Deputados, membros de Câmaras Parlamentares e equiparadas;</li>
            <li>Magistrados judiciais dos tribunais superiores e da Relação, cujas decisões não possam ser objecto de recurso, salvo em circunstâncias excepcionais;</li>
            <li>Magistrados do Ministério Público de escalão equiparado aos magistrados judiciais referidos no número anterior;</li>
            <li>Provedor de Justiça e Provedor de Justiça Adjunto;</li>
            <li>Membros do Conselho da República, do Conselho de Segurança e demais Conselheiros de Estado;</li>
            <li>Membros da Comissão Nacional Eleitoral;</li>
            <li>Membros dos Conselhos Superiores da Magistratura Judicial e do Ministério Público;</li>
            <li>Membros de órgãos de administração e fiscalização dos bancos centrais e outras autoridades de regulação e supervisão do sector financeiro;</li>
            <li>Chefes de missões diplomáticas e de postos consulares;</li>
            <li>Oficiais Generais das Forças Armadas e Oficiais Comissários das Forças de Segurança e Ordem Interna;</li>
            <li>Membros de órgãos de administração e fiscalização de empresas públicas e de sociedades de capitais exclusiva ou maioritariamente públicos, institutos públicos, associações e fundações públicas, estabelecimentos públicos, incluindo os órgãos de gestão de empresas dos sectores empresariais locais;</li>
            <li>Membros do Conselho de Administração, directores, directores-adjuntos e ou pessoas que exercem funções equivalentes numa organização mundial;</li>
            <li>Membros dos órgãos executivos de direcção de partidos políticos;</li>
            <li>Membros dos órgãos das administrações locais e do poder autárquico;</li>
            <li>Líderes de confissões religiosas.</li>
        </ol>
    </div>

    <div class="choice-line">
        <span class="check-box">{{ $check($isPep) }}</span>Sou Pessoa Politicamente Exposta ou tenho relação com Pessoa Politicamente Exposta, como abaixo assinalado:
    </div>

    <div class="subtext">
        Caso tenha assinalado afirmativamente a opção acima, preencha os dados da Pessoa Politicamente Exposta:
    </div>

    <table class="field-table">
        <tr>
            <td colspan="4">Nome Completo: <span class="line w-82">{{ $proposal->pep_full_name ?? '' }}</span></td>
        </tr>
        <tr>
            <td width="40%">Especificar o cargo/função: <span class="line w-60">{{ $proposal->pep_role ?? '' }}</span></td>
            <td width="22%">Data de Início: <span class="line w-45">{{ $formatDate($proposal->pep_from ?? null) }}</span></td>
            <td width="3%"></td>
            <td width="22%">Data de fim: <span class="line w-45">{{ $formatDate($proposal->pep_to ?? null) }}</span></td>
        </tr>
        <tr>
            <td colspan="3" width="60%">Documento de Identificação: Emitido por: <span class="line w-45">{{ $proposal->pep_id_issued_by ?? '' }}</span></td>
            <td width="40%">Data de Emissão: <span class="line w-45">{{ $formatDate($proposal->pep_id_issue_date ?? null) }}</span></td>
        </tr>
        <tr>
            <td colspan="2" width="30%">NIF: <span class="line w-35">{{ $proposal->pep_nif ?? '' }}</span></td>
            <td colspan="2"></td>
        </tr>
    </table>

    <div class="checkbox-row">
        <span class="option"><span class="check-box">{{ $check($isActive) }}</span>Activo (Desempenha)</span>
        <span class="option"><span class="check-box">{{ $check($isInactive) }}</span>Inactivo (Desempenhou nos últimos 5 anos)</span>
    </div>

    <div class="strong-line">
        <span class="check-box">{{ $check($isNotPep) }}</span>Não sou Pessoa Politicamente Exposta.
    </div>
</div>
@for ($i = 0; $i < 10; $i++)
    <br>
@endfor
<div class="doc-page">
    <table class="doc-header">
        <tr>
            <td class="doc-title">DECLARAÇÃO DE PESSOA POLITICAMENTE EXPOSTA</td>
        </tr>
    </table>

    <div class="section-bar section-bar--pep">DECLARAÇÃO</div>

    <div class="declaration-text">
        {{ $proposal->declaracao_texto ?? 'Declaro que as informações prestadas são verdadeiras. Tendo conhecimento das disposições legais aplicáveis, autorizo o BIC Seguros, SA. a partilhar com as autoridades competentes, as informações aqui declaradas.' }}
    </div>

    <!-- Assinatura do Representante Legal -->
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

    <!-- A preencher pelo BIC Seguros -->
    <div class="office-box">
        <div class="office-title">A preencher pelo BIC Seguros</div>
        <div class="office-content">

            <!-- Assinatura Responsável pela Operação -->
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

            <!-- Assinatura Compliance Officer -->
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