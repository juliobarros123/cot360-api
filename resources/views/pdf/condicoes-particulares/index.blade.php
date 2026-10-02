
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
</head>
<body>
@php
    $ramo       = $cp->ramo        ?? 'AP GRP.PROF/EXTRA PROFISSIONAL';
    $apolice    = $cp->apolice     ?? '14-00000048';
    $acta       = $cp->acta        ?? '';
    $tomadorNum = $cp->tomador_num ?? '002825';
    $tipoDoc    = $cp->tipo_doc    ?? 'SEGURO NOVO';

    $destNome   = $cp->dest_nome   ?? 'BIC SEGUROS, S.A';
    $destMorada = $cp->dest_morada ?? 'RUA N\'GOLA M\'BANDI, R/C';
    $destLocal  = $cp->dest_local  ?? 'MAIANGA';
    $destNif    = $cp->dest_nif    ?? '5402163774';

    $inicio     = $cp->inicio           ?? '28/02/2026';
    $modalidade = $cp->modalidade       ?? 'TEMPORÁRIO';
    $termo      = $cp->termo            ?? '28/02/2026';
    $premio     = $cp->premio_comercial ?? '31.502,00 Kz';
    $fracc      = $cp->fraccionamento   ?? 'ÚNICO';
    $capital    = $cp->capital_seguro   ?? '27.000.000,00 Kz';

    $riscosGar  = $cp->riscos_garantidos ?? 'PROFISSIONAL E EXTRAPROFISSIONAL';

    $coberturas = $cp->coberturas ?? [
        ['nome' => 'MORTE OU INVALIDEZ PERMANENTE',         'valor' => '25.000.000,00 Kz', 'franquia' => 'SEM FRANQUIA'],
        ['nome' => 'DESPESAS FUNERAL',                      'valor' => '500.000,00 Kz',    'franquia' => 'SEM FRANQUIA'],
        ['nome' => 'DESPESAS DE TRATAMENTO E REPATRIAMENTO', 'valor' => '1.500.000,00 Kz', 'franquia' => 'SEM FRANQUIA'],
    ];

    $pessoasSeguras = $cp->pessoas_seguras ?? 'TODOS OS PARTICIPANTES NO EVENTO 2ª ORDEM DE MÉRITO GOLF MANGAIS.';
    $localData      = $cp->local_data ?? 'LUANDA, 27 de Fevereiro de 2026';
    $pagina         = $cp->pagina ?? '01/última';

    $logo = public_path('img/big-assig.png');
    $stampPath = $cp->stamp ?? public_path('img/cotacao/bic_seguro_assinatura.png');
    if (!is_file($stampPath)) { $stampPath = $logo; }
@endphp
<br>
<br>
<br>
<br>

{{-- O logótipo + título "CONDIÇÕES PARTICULARES" são renderizados no header do mPDF --}}

{{-- Meta + EXMO --}}
   
<table class="meta" >
    <tr>
        <td width="45%" class="meta-col mono">
            <table class="fields">
                <tr><td class="fld-lbl">RAMO:</td><td>{{ $ramo }}</td></tr>
                <tr><td class="fld-lbl">APÓLICE Nº</td><td>{{ $apolice }}</td></tr>
                <tr><td class="fld-lbl">ACTA ADICIONAL Nº:</td><td>{{ $acta }}</td></tr>
                <tr><td class="fld-lbl">TOMADOR Nº:</td><td>{{ $tomadorNum }}</td></tr>
                <tr><td class="fld-lbl">TIPO DE DOCUMENTO:</td><td>{{ $tipoDoc }}</td></tr>
            </table>
        </td>
        <td width="55%" class="meta-col"  style=" border: 1px solid red" >
            <div class="exmo-box mono" style="border:none">
                <div class="exmo-title">EXMO(S) SENHOR(ES)</div>
                <div>{{ $destNome }}</div>
                <div>{{ $destMorada }}</div>
                <div>{{ $destLocal }}</div>
                <br>
                <div>NIF: {{ $destNif }}</div>
            </div>
        </td>
    </tr>
</table>


{{-- Tabela financeira --}}
<table class="fin mono" style=" border-bottom: 2px solid red">
    <tr>
        <th width="16%">Início / Alteração</th>
        <th width="15%">Modalidade</th>
        <th width="13%">Termo</th>
        <th width="20%" class="fin-num">Prémio Comercial (*)</th>
        <th width="15%">Fraccionamento</th>
        <th width="21%" class="fin-num">Capital Seguro</th>
    </tr>
    <tr>
        <td>{{ $inicio }}</td>
        <td>{{ $modalidade }}</td>
        <td>{{ $termo }}</td>
        <td class="fin-num">{{ $premio }}</td>
        <td>{{ $fracc }}</td>
        <td class="fin-num">{{ $capital }}</td>
    </tr>
</table>

{{-- Riscos garantidos --}}
<div class="riscos-gar mono">
    <span class="riscos-gar-lbl">RISCOS GARANTIDOS:</span> {{ $riscosGar }}
</div>
 <br>
 <br>
 <br>
 <br>
 <br><br>
{{-- Riscos cobertos --}}
<table class="cov mono">
    <tr>
        <th width="55%">RISCOS COBERTOS (POR PESSOA SEGURA)</th>
        <th width="27%" style="text-align:center">VALORES SEGUROS</th>
        <th width="18%">FRANQUIAS</th>
    </tr>
    @foreach($coberturas as $c)
        <tr>
            <td class="cov-name">  &nbsp;&nbsp;&nbsp; {{ $c['nome'] }}</td>
            <td class="cov-val">{{ $c['valor'] }}</td>
            <td>{{ $c['franquia'] }}</td>
        </tr>
    @endforeach
</table>

{{-- Condições particulares --}}
<div class="cond-block mono">
    <div class="cond-ttl">CONDIÇÕES PARTICULARES</div>
    <div>PESSOAS SEGURAS:</div>
    <div>{{ $pessoasSeguras }}</div>
</div>
 <br>
 <br>
  <br>
 <br> <br>
 
{{-- Assinatura --}}
<table class="sign mono">
    <tr>
        <td width="50%">{{ $localData }}</td>
        <td width="50%" class="sig-line">
            <img src="{{ $stampPath }}" class="stamp" alt=""><br>
            
        </td>
    </tr>
</table>

{{-- Notas finais --}}
<div class="notes mono">
    <div>* O Prémio Comercial é acrescido dos encargos e selos legais</div>
    <br>
    <div>
        Este contrato de seguro é constituído pela proposta que<br>
        lhe serviu de base, pelas condições gerais e especiais e pelas<br>
        presentes condições particulares
    </div>
    <div class="pagina">Pág. {{ $pagina }}</div>
</div>

</body>
</html>