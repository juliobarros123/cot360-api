@php
    $yesNo = strtolower(trim((string) ($proposal->be_entity_beneficiario_efectivo ?? '')));
    $hasBeneficiario = in_array($yesNo, ['sim', 'yes', 'true', '1'], true) || ($yesNo !== '' && $yesNo !== 'nao' && $yesNo !== 'não');
    $beneficiarioNome = $hasBeneficiario && !in_array($yesNo, ['sim', 'yes', 'true', '1'], true)
        ? (string) $proposal->be_entity_beneficiario_efectivo
        : '';
    $rowsFromText = static function (?string $value, int $limit): array {
        $lines = preg_split('/\r\n|\r|\n/', trim((string) $value)) ?: [];
        $rows = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $parts = preg_split('/\s*[|;\-]\s*/', $line, 2) ?: [];
            $rows[] = [
                $parts[0] ?? '',
                $parts[1] ?? '',
            ];
        }
        while (count($rows) < $limit) {
            $rows[] = ['', ''];
        }
        return array_slice($rows, 0, $limit);
    };
    $socios = $rowsFromText($proposal->be_entity_socios_accionistas ?? null, 9);
    $membros = $rowsFromText($proposal->be_entity_membros_orgaos_sociais ?? null, 8);
@endphp

<div class="doc-page">
    <table class="doc-header">
        <tr>
            <td class="doc-title">DECLARAÇÃO DE BENEFICIÁRIO EFECTIVO</td>
        </tr>
    </table>

    <div class="section-bar">IDENTIFICAÇÃO DA ENTIDADE CONTRATANTE</div>
    <div class="mandatory">Obrigatório o preenchimento dos dados abaixo:</div>

    <table class="field-grid">
        <tr>
            <td width="30%">Denominação Social:</td>
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
            <td>Representada por:</td>
            <td><div class="field-box">{{ $proposal->be_entity_representada_por ?? '' }}</div></td>
        </tr>
        <tr>
            <td>Tipo de Sociedade:</td>
            <td><div class="field-box">{{ $proposal->be_entity_tipo_sociedade ?? '' }}</div></td>
        </tr>
    </table>

    <table style=" margin: 0 24px 6px; font-size: 12pt;">
        <tr>
            <td width="22%" style="vertical-align: top; padding-top: 24px;   font-size: 9pt;" >Sócios/Accionistas:</td>
            <td width="78%" style="background: #E9ECF7">
                <div class="table-shell"  width="100%"  >
                    <table class="mini-table" width="100%">
                        <thead>
                            <tr>
                                <th width="65%">Sócio/Accionista</th>
                                <th width="35%" class="text-center">%</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($socios as [$nome, $percentual])
                                <tr>
                                    <td class="text-center">{{ $nome }}</td>
                                    <td class="text-center">{{ $percentual }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <table style=" margin: 0 24px; font-size: 12pt;">
        <tr>
            <td width="22%" style="vertical-align: top; padding-top: 24px;  font-size: 9pt;">Membros dos Órgãos Sociais:</td>
            <td style="background: #E9ECF7">
                <div class="table-shell">
                    <table class="mini-table">
                        <thead>
                            <tr>
                                <th width="50%">Nome</th>
                                <th width="50%">Cargo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($membros as [$nome, $cargo])
                                <tr>
                                    <td class="text-center">{{ $nome }}</td>
                                    <td class="text-center">{{ $cargo }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <table style=" margin: 30px 24px 0; font-size: 12pt;height:1000px!important;" class="recipient">
        <tr>
            <td width="22%" style="vertical-align: top; padding-top: 14px;">Beneficiário Efectivo:</td>
            <td height="10%" style="background: #E9ECF7">
                <div class="benef-shell">
                    <div class="benef-title">Existe Beneficiário Efectivo (1)?</div>
                  
                    <div style="margin-bottom: 8px;"><span class="check-box">{{ $hasBeneficiario ? 'X' : '' }}</span>Sim</div>
                    <div><span class="check-box">{{ $hasBeneficiario ? '' : 'X' }}</span>Não</div>
                    <div style="margin-top: 20px;">Se Sim, indique: <span class="line">{{ $beneficiarioNome }}</span></div>
                    <div style="margin-top: 16px;"><span class="line"></span></div>
                </div>
            </td>
        </tr>
    </table>

</div>
@for ($i = 0; $i < 10; $i++)
    <br>
@endfor
<div class="doc-page">
    <table class="doc-header">
        <tr>
            <td class="doc-title">DECLARAÇÃO DE BENEFICIÁRIO EFECTIVO</td>
        </tr>
    </table>

    <div class="representante-text">
        {{ $proposal->declaracao_texto ?? 'As respostas à presente Declaração devem ser preenchidas com veracidade, sob pena de incorrer em responsabilidades que derivem do incumprimento.' }}
    </div>

    <div class="representante-label">O Representante</div>
    <div class="signature-line-short">{{ $proposal->declaracao_representante_assinatura ?? ($proposal->be_entity_o_representante ?? '') }}</div>

    <div class="footnote">
        (1) Nos termos do disposto no n.º 9 do artigo 3.º da Lei n.º 5/20 de 27 de Janeiro – Lei de Prevenção e Combate aos Crimes de Branqueamento de Capitais, Financiamento ao Terrorismo e da Proliferação de Armas de Destruição em Massa.
    </div>

</div>
