<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    {{-- <title>Seguro de Multirriscos de Habitação</title> --}}
</head>
<body>
@php
    $obj = $objects[0] ?? [];
    $details = $obj['details'] ?? [];

    $planName = $selectedTemplate['name'] ?? 'PLANO BASE';
    $riskClass = $obj['risk_class'] ?? $details['risk_class'] ?? '';
    $constructionYear = $obj['construction_year'] ?? $details['construction_year'] ?? '';
    $insuredRole = $obj['insured_role'] ?? $details['insured_role'] ?? '';
    $tipologia = $obj['property_type'] ?? $details['property_type'] ?? '';
    $residenceType = $obj['residence_type'] ?? $details['residence_type'] ?? '';
    $theftProtection = $obj['theft_protection'] ?? $details['theft_protection'] ?? false;
    $buildingCapital = (float) ($obj['building_capital_value'] ?? $details['building_capital_value'] ?? $details['building_capital'] ?? 0);
    $contentCapital = (float) ($obj['content_capital_value'] ?? $details['content_capital_value'] ?? $details['content_capital'] ?? 0);

    $formatKz = function ($v) {
        return number_format((float) $v, 2, ',', '.') . ' Kz';
    };

    $sortedCoverages = collect($coverages ?? [])->sortBy(function ($cov) {
        return (int) ($cov['seq'] ?? 0);
    })->values()->all();

    $coverageLines = [];
    foreach ($sortedCoverages as $cov) {
        if (!empty($cov['group_id']) && in_array(($cov['active'] ?? true), [true, 1, '1'], true)) {
            $objType = strtoupper((string) ($cov['obj_type'] ?? ''));
            $base = str_contains($objType, 'CONTENT') ? $contentCapital : $buildingCapital;
            $perc = (float) ($cov['cov_perc'] ?? 0);
            $capital = $perc > 0 ? ($base * $perc) : $base;
            $coverageLines[] = [
                'name' => $cov['name'] ?? $cov['short_name'] ?? '',
                'capital' => $capital,
            ];
        }
    }

    $totalPremium = collect($QLI ?? [])->sum(function ($qli) {
        return (float) ($qli['premium_value'] ?? $qli['premiunComercial'] ?? $qli['coverage_premium'] ?? 0);
    });

    $simDate = \Carbon\Carbon::now()->format('Y/m/d');
    if (!empty($quotation['created_date'])) {
        try {
            $simDate = \Carbon\Carbon::parse($quotation['created_date'])->format('Y/m/d');
        } catch (\Throwable $e) {}
    }
@endphp

<div class="mh-page">
    <div class="mh-header">
        <h2 class="mh-sim">SIMULAÇÃO</h2>
    </div>

    <h3 class="mh-title">SEGURO DE MULTIRISCOS DE HABITAÇÃO</h3>

    <div class="mh-row">
        <span class="label">SIMULAÇÃO Nº</span>
        <span class="value">{{ $quotation_number ?? ($quotation['quotation_number'] ?? '') }}</span>
    </div>

    <h1 class="mh-section-title">Dados Gerais</h1>
    <table class="mh-info">
        <tr><td class="k">Data Início</td><td>{{ \Carbon\Carbon::now()->format('Y/m/d') }}</td></tr>
        <tr><td class="k">Plano</td><td>{{ strtoupper((string) $planName) }}</td></tr>
        <tr><td class="k">Classe de Risco</td><td>{{ $riskClass }}</td></tr>
        <tr><td class="k">Ano Edifício</td><td>{{ $constructionYear }}</td></tr>
        <tr><td class="k">Qualificação em que segura</td><td>{{ $insuredRole }}</td></tr>
        <tr><td class="k">Tipologia</td><td>{{ $tipologia }}</td></tr>
        <tr><td class="k">Tipo Residência</td><td>{{ $residenceType }}</td></tr>
        <tr><td class="k">Meios Segurança Roubo</td><td>{{ $theftProtection ? 'Sim' : 'Não' }}</td></tr>
    </table>

    <h2 class="mh-subtitle">Edifício</h2>
    <table class="mh-cap">
        <tr>
            <td class="d-label">Descrição</td>
            <td class="d-box">CONFORME LISTAGEM APRESENTADA</td>
            <td class="v-label">Valor</td>
            <td class="v">{{ $formatKz($buildingCapital) }}</td>
        </tr>
    </table>

    <h2 class="mh-subtitle">Conteúdo</h2>
    <table class="mh-cap">
        <tr>
            <td class="d-label">Descrição</td>
            <td class="d-box">CONFORME LISTAGEM APRESENTADA</td>
            <td class="v-label">Valor</td>
            <td class="v">{{ $formatKz($contentCapital) }}</td>
        </tr>
    </table>

    <h2 class="mh-subtitle">Coberturas</h2>
    <table class="mh-cov">
        <thead>
            <tr>
                <th>Cobertura Base</th>
                <th class="text-right" style="text-align: right;"><strong>Capital</strong> </th>
            </tr>
        </thead>
        <tbody>
            @foreach ($coverageLines as $line)
                <tr>
                    <td>{{ $line['name'] }}</td>
                    <td class="text-right">{{ $formatKz($line['capital']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2 class="mh-subtitle mh-prem-title">Prémio Total a Pagar</h2>
    <table class="mh-prem">
        <tr><td class="color-danger"> <strong>Fraccionamento</strong></td><td style="text-align: right;" class="color-danger"> <strong>Anual</strong></td></tr>
        <tr><td class="color-danger"> <strong>1º recibo</strong></td><td style="text-align: right;">{{ $formatKz($totalPremium) }}</td></tr>
        <tr><td class="color-danger"><strong>Recibos seguintes</strong></td><td style="text-align: right;">{{ $formatKz($totalPremium) }}</td></tr>
        <tr><td class="color-danger"  width="30%"><strong>Data Simulação</strong></td><td  >{{ $simDate }}</td></tr>
    </table>
    <div class="mh-note">Nota: Valores meramente indicativos</div>

</div>

</body>
</html>
