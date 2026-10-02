\# Manual de Fórmulas - Cotação Auto (Anexos A/B/C)

## 1) Objetivo
Este documento explica, de forma operacional, como calcular uma cotação de automóvel **à mão**, seguindo a lógica implementada no backend/PDF deste projeto.

Foco:
- Fórmulas de prémio por cobertura, por objeto e total.
- Regras de anexos A/B/C.
- Estrutura mínima de banco para reproduzir o cenário.
- Exemplo completo com números.

## 2) Fontes no código (implementação real)
- Fórmulas principais: `app/Helpers/Helper.php`
- Montagem dos anexos para PDF: `app/Http/Controllers/Api/PDFController.php`
- Template PDF Auto: `resources/views/pdf/quotation/index.blade.php`
- Persistência da cotação/versão/riscos/ajustes:
  - `app/Services/QUO/QUOQuotationsService.php`
  - `app/Services/QUO/QUOQuotationVersionsService.php`

## 3) Modelo de dados mínimo (tabelas que participam do cálculo)

## 3.1 Tabelas principais
1. `quo_quotations`
- Cabeçalho da cotação (cliente, ramo, status, número, etc).

2. `quo_quotation_versions`
- Uma linha por versão/anexo (ex.: `v1/A`, `v1/B`, `v1/C`).
- Campos críticos: `quotation_id`, `version`, `table_code`, `discount_percent`, `total_premium`.

3. `quo_object_risk`
- Linha de risco por `objeto + cobertura` dentro de uma versão.
- Campos críticos:
  - `quotation_version_id`
  - `object_id`
  - `coverage_id`
  - `coverage_premium`
  - `applied_rate`
  - `discount_percent`
  - `surcharge_percent`
  - `rate_id`

4. `quo_insurance_adjustments`
- Ajustes por risco (`Bonus`, `Surcharge`, `Discount`, `Deductible`).

5. `quo_premiums`
- Totais agregados (`Net`, `Tax_FGA`, `Tax_Selo`, `Gross`, etc).

6. `quo_quotation_discount_controls`
- Controle/auditoria do desconto por `cotação + versão + anexo`.

## 3.2 Tabelas de apoio de taxa
1. `cfg_coverages`
- Cadastro de coberturas (`short_name`, `rate_type`, `group_id`, etc).

2. `cfg_rates_rc_options`
- Regras para RC facultativa:
  - `perc_rate` (percentual aplicado sobre R.CIVIL OBR.)
  - `rc_fac` (limite RC facultativo)

## 4) Regras de cálculo (como o sistema calcula)

## 4.1 Prémio de uma cobertura para um objeto (`checkPremium`)
Base:
- Encontrar o item QLI com mesmo `object_id` e `coverage_id`.
- Se não encontrar, prémio = `0`.

Fórmula base:
- `premio_base = arredondarBase5(premiunComercial || coverage_premium)`

Caso especial `RC FACULTAT.`:
- Retorna `ratesObject[0].rc_fac` (não usa a fórmula padrão).

Caso especial de acréscimo em `R.CIVIL OBR.` (quando não está no modo anexo detalhado):
- Busca o QLI de `RC FACULTAT.` do mesmo objeto e usa `rate_id`.
- Busca `perc_rate` em `cfg_rates_rc_options`.
- Aplica:
- `premio_rc_obr = premio_base + (premio_base * perc_rate/100)`

## 4.2 Arredondamento (`arredondarBase5`)
Regra usada:
- Se já for inteiro, mantém.
- Se tiver decimal:
  - decimal >= 0.5: arredonda para cima (`ceil`)
  - decimal < 0.5: corta para baixo (parte inteira)

## 4.3 Total por objeto (`checkPremiumTotal` / `calculateObjectPremiumTotal`)
Somar coberturas elegíveis do objeto:
- Exclui `R.CIVIL` em alguns fluxos de renderização.
- Inclui `R.CIVIL OBR.` explicitamente.
- Considera apenas coberturas marcadas/ativas pela seleção.

Fórmula:
- `total_objeto = soma(premio_cobertura_i)`
- Em `calculateObjectPremiumTotal`, ao final aplica `arredondarBase5(total_objeto)`.

## 4.4 Total por cobertura na frota (`checkPremiumTotalByCoverage` / `calculateTotalCoverage`)
- Soma o prémio da mesma cobertura em todos os objetos.

Fórmula:
- `total_cobertura = soma_objetos(premio_objeto_cobertura)`
- Em `calculateTotalCoverage`, o total final também passa por `arredondarBase5`.

## 4.5 Total geral anual (`checkPremiumTotalGeral`)
- Soma os totais por objeto.

Fórmula:
- `total_geral = soma_objetos(total_objeto)`

## 4.6 Limite RC mostrado no PDF (`checkLimitRC` / `rcFacBC`)
Há dois caminhos usados no template:
1. Bloco principal: `checkLimitRC(...)`
2. Blocos de anexos: `rcFacBC(...)`

Em termos práticos de leitura manual:
- O limite RC vem do universo RC facultativo/rate (`cfg_rates_rc_options.rc_fac`) ou de dados equivalentes em `ratesObject`.

## 4.7 Semestral/Trimestral/Mensal (`calcularPremios`)
Dado `premiumTotal` (normalmente anual já exibido no quadro), a função reconstrói componentes:
- `premio_simples = premiumTotal / 1.418`
- `premio_encargos = premio_simples * 0.20`
- `premio_fundo_garantia = premio_simples * 0.05`
- `premio_comercial = premio_simples + premio_encargos`
- `iva = premio_comercial * 0.14`
- `comercial_mais_iva = premio_comercial + iva`
- `total_anual = premio_fundo_garantia + comercial_mais_iva`
- `total_semestre = ((comercial_mais_iva * 1.03) + premio_fundo_garantia)/2`
- `total_trimestre = ((comercial_mais_iva * 1.05) + premio_fundo_garantia)/4`
- `total_mensal = ((comercial_mais_iva * 1.07) + premio_fundo_garantia)/12`

Observação: existem outras rotinas auxiliares no projeto para `premiosGlobais`; para simulação manual do PDF Auto, use esta fórmula acima, que é a usada diretamente no template.

## 5) Regra de anexos A/B/C (desconto e ajustes)

## 5.1 Persistência por anexo
- Cada anexo é uma linha separada em `quo_quotation_versions` com o mesmo `version` e `table_code` diferente.

Exemplo:
- `v1/A`
- `v1/B`
- `v1/C`

## 5.2 Sincronização de desconto entre anexos
Ao criar/editar versão, backend sincroniza:
- `discount_percent` entre anexos irmãos da mesma `quotation_id + version`.
- Ajustes de desconto por risco/cobertura entre anexos.
- Prêmios agregados (`quo_premiums`) entre anexos.
- Controle em `quo_quotation_discount_controls`.

## 5.3 Como o PDF monta anexos
No `PDFController`:
1. Monta `annexsAdjustments` a partir de `QLI.annex`, BD e/ou surcharges.
2. Se necessário, gera QLI de `RC FACULTAT.` por anexo com base na sobretaxa.
3. Renderiza o template Auto:
- Bloco principal (Anexo A/base)
- Blocos adicionais para B/C iterando `annexsAdjustments`.

## 6) Exemplo completo manual (cotação auto com A/B/C)

## 6.1 Cenário
- Cotação: `Q-001`
- Versão lógica: `v1`
- Anexos: `A`, `B`, `C`
- Objetos: 2 viaturas (`V1`, `V2`)
- Coberturas ativas para cálculo:
  - `DANOS PRÓPRIOS`
  - `R.CIVIL OBR.`
  - `ROUBO`

Taxa RC facultativa por anexo (`perc_rate`):
- Anexo A: 10%
- Anexo B: 20%
- Anexo C: 30%

## 6.2 Prémios base (sem acréscimo RC facultativo)
Para simplificar, assumindo `premiunComercial` já carregado no QLI:

Viatura V1:
- DP = 100.000
- RC OBR = 50.000
- ROUBO = 20.000

Viatura V2:
- DP = 80.000
- RC OBR = 40.000
- ROUBO = 15.000

## 6.3 Cálculo do RC OBR com acréscimo por anexo
Fórmula:
- `RC_OBR_ajustado = RC_OBR_base * (1 + perc_rate)`

Anexo A (`10%`):
- V1: `50.000 * 1,10 = 55.000`
- V2: `40.000 * 1,10 = 44.000`

Anexo B (`20%`):
- V1: `50.000 * 1,20 = 60.000`
- V2: `40.000 * 1,20 = 48.000`

Anexo C (`30%`):
- V1: `50.000 * 1,30 = 65.000`
- V2: `40.000 * 1,30 = 52.000`

## 6.4 Total por objeto em cada anexo
Fórmula:
- `total_objeto = DP + RC_OBR_ajustado + ROUBO`

Anexo A:
- V1: `100.000 + 55.000 + 20.000 = 175.000`
- V2: `80.000 + 44.000 + 15.000 = 139.000`
- Total Anual A: `314.000`

Anexo B:
- V1: `100.000 + 60.000 + 20.000 = 180.000`
- V2: `80.000 + 48.000 + 15.000 = 143.000`
- Total Anual B: `323.000`

Anexo C:
- V1: `100.000 + 65.000 + 20.000 = 185.000`
- V2: `80.000 + 52.000 + 15.000 = 147.000`
- Total Anual C: `332.000`

## 6.5 Semestral e trimestral (exemplo com Anexo A)
Usando `premiumTotal = 314.000`:
- `premio_simples = 314.000 / 1,418 = 221.438,65`
- `premio_encargos = 44.287,73`
- `premio_fundo_garantia = 11.071,93`
- `premio_comercial = 265.726,38`
- `iva = 37.201,69`
- `comercial_mais_iva = 302.928,07`
- `total_semestre = ((302.928,07*1,03)+11.071,93)/2 = 161.043,12`
- `total_trimestre = ((302.928,07*1,05)+11.071,93)/4 = 82.536,10`

(Valores arredondados a 2 casas, como no helper.)

## 7) Exemplo de inserts SQL (mínimo para simular)

```sql
-- 1) Cotação
INSERT INTO quo_quotations (id, quotation_number, customer_id, insurance_branch_id, status, slug, created_at, updated_at)
VALUES (1001, '(2026/90.01.00001)', 10, 90, 'Pending', 'q-1001', NOW(), NOW());

-- 2) Versões/anexos v1
INSERT INTO quo_quotation_versions
(id, quotation_id, version, table_code, total_premium, discount_percent, currency, exchange_rate, status, created_date, valid_until, created_at, updated_at)
VALUES
(2001, 1001, 'v1', 'A', 314000, 10, 'AOA', 1, 'Pending', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), NOW(), NOW()),
(2002, 1001, 'v1', 'B', 323000, 10, 'AOA', 1, 'Pending', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), NOW(), NOW()),
(2003, 1001, 'v1', 'C', 332000, 10, 'AOA', 1, 'Pending', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), NOW(), NOW());

-- 3) Riscos (exemplo parcial: V1 e V2 no anexo A)
INSERT INTO quo_object_risk
(quotation_version_id, object_id, coverage_id, coverage_perc, applied_rate, coverage_premium, discount_percent, surcharge_percent, slug, created_at, updated_at)
VALUES
(2001, 501, 9001, 0, 0, 100000, 0, 0, 'r-2001-501-9001', NOW(), NOW()), -- DP V1
(2001, 501, 9002, 0, 10, 55000, 0, 0, 'r-2001-501-9002', NOW(), NOW()),  -- RC OBR V1 ajustado
(2001, 501, 9003, 0, 0, 20000, 0, 0, 'r-2001-501-9003', NOW(), NOW()),   -- ROUBO V1
(2001, 502, 9001, 0, 0, 80000, 0, 0, 'r-2001-502-9001', NOW(), NOW()),
(2001, 502, 9002, 0, 10, 44000, 0, 0, 'r-2001-502-9002', NOW(), NOW()),
(2001, 502, 9003, 0, 0, 15000, 0, 0, 'r-2001-502-9003', NOW(), NOW());

-- 4) Controle de desconto por anexo
INSERT INTO quo_quotation_discount_controls
(quotation_id, quotation_version_id, version_code, table_code, discount_percent, status, applied_at, created_at, updated_at)
VALUES
(1001, 2001, 'v1', 'A', 10, 'applied', NOW(), NOW(), NOW()),
(1001, 2002, 'v1', 'B', 10, 'applied', NOW(), NOW(), NOW()),
(1001, 2003, 'v1', 'C', 10, 'applied', NOW(), NOW(), NOW());
```

Observação:
- IDs de `coverage_id` (`9001`, `9002`, `9003`) são ilustrativos.
- Em ambiente real, devem existir em `cfg_coverages` com `short_name` coerente (`DANOS PRÓPRIOS`, `R.CIVIL OBR.`, etc).

## 8) Como validar manualmente contra o PDF
1. Calcular, para cada objeto, cada cobertura elegível.
2. Aplicar regra de RC OBR conforme `perc_rate` do anexo.
3. Somar por objeto.
4. Somar por cobertura (rodapé por coluna).
5. Somar total geral anual.
6. Derivar semestral/trimestral com `calcularPremios`.
7. Conferir no PDF:
- Linhas dos objetos.
- Rodapé `Total Anual` por cobertura e geral.
- Colunas semestral/trimestral (se habilitadas).

## 9) Pontos de atenção para cálculo “na mão”
1. O projeto usa duas estruturas em alguns fluxos (`QLI` no payload e `quo_object_risk` no banco). Garanta consistência dos valores.
2. `arredondarBase5` pode alterar total (sempre revisar arredondamento no fim das somas onde a função é aplicada).
3. Regras de inclusão de cobertura dependem de seleção/`status` e de `group_id`.
4. Para anexos B/C, o PDF pode usar dados do banco e/ou payload (fallbacks).
5. O desconto por versão (`discount_percent`) é sincronizado entre anexos da mesma versão, mas não entre versões diferentes.

## 10) Checklist rápido para replicar sem sistema
1. Definir objetos segurados.
2. Definir coberturas ativas por objeto.
3. Definir prêmio base por cobertura (`premiunComercial`/`coverage_premium`).
4. Definir `perc_rate` de RC facultativa por anexo.
5. Calcular RC OBR ajustado por anexo.
6. Somar totais por objeto, por cobertura e total anual.
7. Aplicar fórmula de semestral/trimestral/mensal.
8. Registrar tudo em planilha com colunas equivalentes ao PDF.

