
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
</head>
<body>
@php
    $segurado    = $cert->segurado        ?? 'NOVA SOTECMA, S.A.';
    $morada      = $cert->morada          ?? 'AV. DEOLINDA RODRIGUES, 399 RANGEL, LUANDA - ANGOLA';
    $apolice     = $cert->apolice         ?? '80.00000031';
    $certificado = $cert->certificado     ?? '2025.0000996';
    $descricao   = $cert->descricao       ?? 'De acordo com as facturas a seguir referidas e anexas ao presente Certificado.';
    $empresaFact = $cert->empresa_factura ?? 'Empresa: Kumpel – Factura nº 1';
    $moeda       = $cert->moeda           ?? 'EUR';
    $valorSeguro = $cert->valor_seguro    ?? '96.534,00';
    $soma        = $cert->soma            ?? '96.534,00';
    $despesas    = $cert->despesas        ?? '';
    $total       = $cert->total           ?? '96.534,00';
    $claimsDest  = $cert->claims_dest     ?? 'Sinistros pagos em Angola';
    $voyage      = $cert->viagem          ?? 'Antuérpia, Bélgica para Luanda, Angola';
    $transbordo  = $cert->transbordo      ?? '';
    $vessel      = $cert->vessel          ?? 'SPIRIT OF SYDNEY';
    $cobertura   = $cert->cobertura       ?? 'Cláusula de seguros de cargas A (ICC A); Cláusula de Exclusão de Contaminação Radioactiva; Cláusula de riscos de guerra; Cláusula de riscos de greves; Institute Classification Clause';
    $emitidoEm   = $cert->emitido_local   ?? 'Luanda';
    $emitidoData = $cert->emitido_data    ?? '15/01/2025';

    $logo = public_path('img/cotacao/bic_seguro.png');
    $stampPath = $cert->stamp ?? public_path('img/bic-assign-2.png');
    if (!is_file($stampPath)) { $stampPath = $logo; }
@endphp

{{-- ====================== PÁGINA 1 ====================== --}}
{{-- O logótipo é renderizado no header do mPDF (header_logo_path) --}}
<div class="cert-title" style="border-bottom: 3px solid red">
    Certificado de Seguro de Mercadorias<br>
    <span class="en">(Cargo Insurance Certificate)</span>
</div>

<div class="intro">
    Certifica-se que efectuámos o seguro dos objectos e nas condições aqui mencionadas, de acordo com a apólice e a proposta de seguro que lhe serviu de base.<br>
    <span class="en">(We hereby certify having issued the insurance of objects and in the terms and conditions hereby mentioned, according to the policy and initial proposal form.)</span>
</div>

{{-- Identificação --}}
<table class="block">
    <tr>
        <td width="55%">Segurado (<span class="en">Assured</span>): {{ $segurado }}</td>
        <td width="22%">Apólice nº<br>(<span class="en">Policy nr.</span>)</td>
        <td width="23%" class="center bold">{{ $apolice }}</td>
    </tr>
    <tr>
        <td>Morada (<span class="en">Address</span>):<br>{{ $morada }}</td>
        <td>Certificado nº<br>(<span class="en">Certificate nr.</span>)</td>
        <td class="center bold">{{ $certificado }}</td>
    </tr>
</table>

{{-- Objectos seguros --}}
<table class="block">
    <tr class="bar"><td colspan="3">OBJECTOS OU INTERESSES SEGUROS (<span class="en">INSURED OBJECTS OR INTERESTS</span>)</td></tr>
    <tr class="hd">
        <td width="64%">Descrição (<span class="en">Description</span>)</td>
        <td width="18%">Moeda<br>(<span class="en">Currency</span>)</td>
        <td width="18%">Valor seguro<br>(<span class="en">Insured value</span>)</td>
    </tr>
    <tr>
        <td class="desc-cell">
            {{ $descricao }}<br><br>
            {{ $empresaFact }}
            <div class="spacer-42"></div>
        </td>
        <td class="center vmid">{{ $moeda }}</td>
        <td class="val vmid">{{ $valorSeguro }}</td>
    </tr>
    <tr>
        <td colspan="2" class="right">Soma (<span class="en">Sum</span>)</td>
        <td class="val">{{ $soma }}</td>
    </tr>
    <tr>
        <td colspan="2">Despesas comprováveis e lucros cessantes (<span class="en">Justifiable expenses and expected profits</span>)</td>
        <td class="val">{{ $despesas }}</td>
    </tr>
    <tr>
        <td colspan="2" class="right">Total (<span class="en">Total</span>)</td>
        <td class="val">{{ $total }}</td>
    </tr>
</table>

{{-- Claims payable destination --}}
<table class="block">
    <tr>
        <td width="22%">Claims payable destination</td>
        <td>{{ $claimsDest }}</td>
    </tr>
</table>

{{-- Viagem --}}
<table class="block">
    <tr class="bar"><td>VIAGEM (<span class="en">VOYAGE</span>)</td></tr>
    <tr><td>De/Para (<span class="en">From/To</span>) {{ $voyage }}</td></tr>
    <tr><td>Com transbordo em (<span class="en">With transhipment at</span>) {{ $transbordo }}</td></tr>
</table>

{{-- Meio de transporte --}}
<table class="block">
    <tr class="bar"><td>MEIO DE TRANSPORTE (<span class="en">MEANS OF TRANSPORTATION</span>)</td></tr>
    <tr><td><span class="bold">Vessel</span> {{ $vessel }}</td></tr>
</table>

{{-- Cobertura --}}
<table class="block">
    <tr class="bar"><td>COBERTURA E OUTRAS CONDIÇÕES (<span class="en">COVERAGE AND OTHER CONDITIONS</span>)</td></tr>
    <tr><td class="clauses">{{ $cobertura }}</td></tr>
</table>

{{-- Box de vistoria / sinistro --}}
<table class="block survey">
    <tr>
        <td width="66%">
            <p class="survey-p">
                <span class="survey-strong">Em caso de sinistro que afecte a mercadoria, a vistoria deve ser prontamente requisitada a:</span><br>
                <span class="en">(In case of claim affecting the insured objects, survey must be promptly requested to):</span>
            </p>
            <p class="survey-p">
                <span class="survey-strong">A reclamação de sinistro, suportada com: original da apólice ou certificado de seguro, conhecimento de embarque ou outro título de transporte, factura comercial, certificado de vistoria e cópia da carta de reclamação ao transportador e original da sua resposta, deve ser apresentada ao Segurador no prazo máximo de 2 meses contados após a descarga no local de destino, sob pena de o isentar de qualquer responsabilidade pelo pagamento das indemnizações.</span>
            </p>
            <p class="survey-p">
                <span class="en">(Claims supported by: Original Policy or Insurance Certificate, Bill of Lading or other title of carriage, commercial invoice, survey report and copy of the letter claiming the damages to the carrier, within the time stipulated in the carriage contract as well as the original of their answer; claims can only be accepted if presented to Insurers within 2 months from the date of unloading at place of destination)</span>
            </p>
        </td>
        <td width="34%" class="survey-contact">
            BIC SEGUROS, SA<br>
            Rua N´Gola M´Bandi, R/C<br>
            Município de Maianga,<br>
            Província de Luanda<br><br>
            Tel: +244 22 644 36 70<br>
            Email:<br>geral.sinistros@bicseguros.ao
        </td>
    </tr>
</table>

{{-- Emissão + assinatura --}}
<table class="issued">
    <tr>
        <td width="60%">
            Emitido em (<span class="en">Issued in</span>) {{ $emitidoEm }} &nbsp;&nbsp; Em (<span class="en">On the</span>) {{ $emitidoData }}
        </td>
         </tr>
         <tr>

               <td width="60%" style="color: white">
           .
        </td>
        <td width="40%" class="issued-sign">
            BIC Seguros<br>
            <img src="{{ $stampPath }}" class="stamp" alt="">
        </td>
    </tr>
</table>

{{-- ====================== PÁGINA 2 ====================== --}}
{{-- O logótipo repete-se automaticamente pelo header do mPDF --}}
<div class="pagebreak"></div>

 
<div class="proc">
    {{-- Coluna PT --}}
    <div class="proc-col proc-left">
        <div class="col-main">IMPORTANTE</div>
 
        <div class="col-h">EM CASO DE SINISTRO - PROCEDIMENTOS</div>
        <div class="proc-p">O Segurado e/ou os seus Agentes devem:</div>
        <div class="proc-item">Em caso de falta de qualquer volume; à descarga, reclamar por escrito ao transportador e/ou autoridades alfandegárias;</div>
        <div class="proc-item">No caso de existirem vestígios evidentes de perda e/ou dano, solicitar aos transportadores e/ou seus representantes imediata vistoria, no cais ou no armazém portuário ou da alfândega, e reclamar, por escrito, caso aqueles se confirmem;</div>
        <div class="proc-item">Não fornecer, em nenhuma circunstância, recibos de entrega das mercadorias, quando as mesmas se encontrem em condições duvidosas, a não ser sobre protesto escrito;</div>
        <div class="proc-item">Notificar o(s) transportador(es) e/ou seu(s) representante(s) no prazo máximo de três dias após a entrega de mercadorias, quando as perdas e/ou danos não sejam aparentes à data da sua entrega;</div>
        <div class="proc-item">Anexar ao «Certificado de Vistoria», toda a correspondência trocada com as entidades acima referidas, quando submeter a reclamação à Companhia ou ao «Comissário de Avarias» designado, se for o caso.</div>
 
        <div class="col-h">DOCUMENTAÇÃO A APRESENTAR À COMPANHIA E/OU SEUS AGENTES LIQUIDATÁRIOS EM CASO DE RECLAMAÇÃO:</div>
        <div class="proc-item">Original da apólice ou Certificado de Seguro;</div>
        <div class="proc-item">Original ou cópia autenticada do conhecimento de embarque e/ou outro contrato de transporte;</div>
        <div class="proc-item">Fatura comercial, nota de pesos e/ou lista de embalagem;</div>
        <div class="proc-item">Certificado de Vistoria elaborado pela entidade indicada na apólice ou Certificado de Seguro;</div>
        <div class="proc-item">Notas de descarga e/ou notas de pesos no destino final;</div>
        <div class="proc-item">Cópia da carta dirigida, no prazo legal, ao transportador ou a outras entidades eventualmente responsáveis pelos prejuízos ocorridos e a respetiva resposta.</div>
 
        <div class="col-h">É DEVER DOS SEGURADOS E SEUS AGENTES:</div>
        <div class="proc-item">Tomar todas as medidas que esteja ao seu alcance para evitar ou diminuir os prejuízos;</div>
        <div class="proc-item">Promover a guarda, segurança e conservação dos salvados;</div>
        <div class="proc-item">Adotar todas as providências para que não se perca o direito de regresso contra terceiros eventualmente responsáveis pelos prejuízos, nomeadamente no que respeita a entidades transportadores, com vista a apresentar, no prazo estabelecido no título de transporte, na lei ou nas convenções internacionais aplicáveis, a competente reclamação por escrito.</div>
 
        <div class="col-h">EXTENSÃO DA COBERTURA</div>
        <div class="proc-p">Se o percurso ordinário do trânsito, do navio para o destino final, levar mais do que 60 dias, ou se o segurado ou os seus agentes interferirem neste mesmo trânsito, a BIC SEGUROS, S.A. ou os seus Agentes devem ser imediatamente avisados e um prémio adicional deverá ser pago.</div>
    </div>
 
    {{-- Coluna EN --}}
    <div class="proc-col proc-right">
        <div class="col-main">IMPORTANT</div>
 
        <div class="col-h">CLAIMS PROCEDURE</div>
        <div class="proc-p">Assured or their Agents must:</div>
        <div class="proc-item">Claim on carrier, Port Authority or any negligent party for any missing packages;</div>
        <div class="proc-item">Apply immediately for survey in the docks by carriers’ representative if any loss or damage be apparent and claim on the carriers for any actual loss or damage found at such survey;</div>
        <div class="proc-item">In no circumstances give clean receipts where goods are in doubt-full condition except under written protest;</div>
        <div class="proc-item">Give notice to the carriers representative within three days of delivery of the loss or damage was not apparent at time of taking delivery;</div>
        <div class="proc-item">Send all correspondence with the carriers or other negligent party to the Company when submitting the claim.</div>
 
        <div class="col-h">DOCUMENTATION REQUIRED WHEN SUBMITTING A CLAIM TO THE COMPANY AND/OR THEIR SETTLING AGENTS:</div>
        <div class="proc-item">Original Policy or certificate of insurance;</div>
        <div class="proc-item">Original bill of lading and/or other contract of carriage;</div>
        <div class="proc-item">Original or copy of the shipping invoices, together with shipping specification and/or weight notes and/or packing lists;</div>
        <div class="proc-item">Survey report or other documental evidence to show the extent of the loss or damage;</div>
        <div class="proc-item">Landing accounts and weight notes at final destination;</div>
        <div class="proc-item">Correspondence exchanged with the carriers and or other parties regarding their Liability for the loss or damage.</div>
 
        <div class="col-h">IT IS THE DUTY OF THE ASSURED AND THEIR AGENTS:</div>
        <div class="proc-item">To take all measures as may be reasonable to avoid or minimize a loss;</div>
        <div class="proc-item">To promote the guarding, safety and conservation of the salvage;</div>
        <div class="proc-item">To ensure that rights against the carriers or other negligent party are properly preserved and exercised, in order to present the competent claim in writing, within the period prescribed in the transport contract, by law or by applicable international conventions.</div>
 
        <div class="col-h">EXTENSION OF COVER:</div>
        <div class="proc-p">If the ordinary course of transit from the ship to destination takes more than 60 days or if the Assured or their Agents interfere with the ordinary course of transit the BIC SEGUROS, S.A., or their agents must be advised and additional premium paid.</div>
    </div>
 
    <div class="proc-clear"></div>
</div>
 
</body>
</html>