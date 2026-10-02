<!doctype html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Apólice {{ $policy->policy_number }}</title>
</head>
<body>
    <div class="header">
        <div class="title">Apólice de Seguro</div>
        <div class="subtitle">{{ $policy->policy_number }} | Versão {{ $version->version }}</div>
    </div>

    <table class="meta">
        <tr>
            <td><strong>Cliente</strong></td>
            <td>{{ $policy->customer->name ?? '-' }}</td>
            <td><strong>Ramo</strong></td>
            <td>{{ $policy->branch->name ?? '-' }}</td>
        </tr>
        <tr>
            <td><strong>Início</strong></td>
            <td>{{ optional($policy->inception_date)->format('d/m/Y') }}</td>
            <td><strong>Fim</strong></td>
            <td>{{ optional($policy->current_expiry_date)->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td><strong>Status</strong></td>
            <td>{{ $policy->status }}</td>
            <td><strong>Moeda</strong></td>
            <td>{{ $version->currency }}</td>
        </tr>
        <tr>
            <td><strong>Prémio Líquido</strong></td>
            <td>{{ number_format((float) $version->net_premium, 2, ',', '.') }}</td>
            <td><strong>Prémio Bruto</strong></td>
            <td>{{ number_format((float) $version->gross_premium, 2, ',', '.') }}</td>
        </tr>
    </table>

    <h3>Riscos Cobertos</h3>
    <table class="grid">
        <thead>
            <tr>
                <th>#</th>
                <th>Objecto</th>
                <th>Cobertura</th>
                <th>Capital</th>
                <th>Prémio</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($version->objectRisks as $idx => $risk)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>{{ $risk->object->description ?? ('Objecto #' . $risk->object_id) }}</td>
                    <td>{{ $risk->coverage->name ?? ('Cobertura #' . $risk->coverage_id) }}</td>
                    <td>{{ number_format((float) ($risk->capital ?? 0), 2, ',', '.') }}</td>
                    <td>{{ number_format((float) ($risk->coverage_premium ?? 0), 2, ',', '.') }}</td>
                    <td>{{ $risk->status }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">Sem riscos registados para esta versão.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <h3>Condições Especiais</h3>
    <table class="grid">
        <thead>
            <tr>
                <th>Tipo</th>
                <th>Texto Curto</th>
                <th>Texto Longo</th>
            </tr>
        </thead>
        <tbody>
            @forelse($version->specialConditions as $condition)
                <tr>
                    <td>{{ $condition->condition_type }}</td>
                    <td>{{ $condition->short_text }}</td>
                    <td>{{ $condition->long_text ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">Sem condições especiais.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Documento gerado em {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>

