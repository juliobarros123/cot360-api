# Fluxo de Desconto em Cotação com Anexos (A/B/C)

## Objetivo

Garantir que, numa mesma cotação e mesma versão (ex.: `v2`), o desconto aplicado em um anexo (ex.: A) seja propagado para os outros anexos (B/C), com rastreabilidade e validação.

---

## Contexto Funcional

No front, cada anexo pode ser salvo separadamente, mas o desconto é uma regra comercial da versão da cotação.  
Por isso, no backend a regra foi centralizada para manter consistência entre anexos irmãos.

---

## Tabelas Envolvidas

## 1) `quo_quotation_versions` (já existia)
- Guarda a versão da cotação (`v1`, `v2`, etc.) e o anexo (`table_code`: A/B/C).
- Campo de desconto efetivo: `discount_percent`.

Exemplo de linhas:
- `id=101`, `quotation_id=55`, `version='v2'`, `table_code='A'`, `discount_percent=10`
- `id=102`, `quotation_id=55`, `version='v2'`, `table_code='B'`, `discount_percent=10`
- `id=103`, `quotation_id=55`, `version='v2'`, `table_code='C'`, `discount_percent=10`

## 2) `quo_quotation_discount_controls` (nova)
- Migration: `2026_05_12_100500_create_quo_quotation_discount_controls_table.php`
- Finalidade: auditoria/controle por cotação + versão + anexo.

Campos principais:
- `quotation_id`
- `quotation_version_id`
- `version_code` (ex.: `v2`)
- `table_code` (A/B/C)
- `discount_percent`
- `status` (ex.: `applied`)
- `applied_at`

Constraint única:
- (`quotation_id`, `version_code`, `table_code`)

---

## Sequência do Fluxo (Write Path)

## Cenário A: Criar nova versão/anexo
Endpoint:
- `POST /api/quo/quotations/{id}/versions`

Sequência:
1. Backend cria linha em `quo_quotation_versions`.
2. Backend sincroniza `discount_percent` para anexos irmãos da mesma cotação e mesmo `version`.
3. Backend grava/atualiza controle em `quo_quotation_discount_controls` para cada anexo afetado.

Efeito esperado:
- Se criou `v3/A` com 12%, então `v3/B` e `v3/C` passam para 12% (se existirem), e o controle fica alinhado.

## Cenário B: Atualizar versão existente (edição)
Endpoint:
- `PATCH /api/quo/quotation-versions/{id}`

Sequência:
1. Backend atualiza a versão editada (`discount_percent`).
2. Backend sincroniza o mesmo desconto para anexos irmãos (mesma `quotation_id` + `version`).
3. Backend grava/atualiza controle por anexo em `quo_quotation_discount_controls`.

---

## Sequência do Fluxo (Read/Validation Path)

Endpoint:
- `GET /api/quo/quotations/{id}/discount-status`

Objetivo:
- Validar consistência entre:
  - `quo_quotation_versions.discount_percent`
  - `quo_quotation_discount_controls.discount_percent`

Regra de consistência:
- `is_consistent = true` quando ambos valores são iguais no mesmo `version + table_code`.
- `all_consistent = true` quando todos os anexos da cotação estão consistentes.

---

## Exemplo Prático Completo

## Situação inicial
Cotação `55`, versão `v2`:
- Anexo A = 5%
- Anexo B = 5%
- Anexo C = 5%

## Ação
Usuário edita anexo A e salva desconto 15% via:
- `PATCH /api/quo/quotation-versions/101`

## Resultado esperado no banco
`quo_quotation_versions`:
- A = 15%
- B = 15%
- C = 15%

`quo_quotation_discount_controls`:
- (`55`, `v2`, `A`) = 15%, `status='applied'`
- (`55`, `v2`, `B`) = 15%, `status='applied'`
- (`55`, `v2`, `C`) = 15%, `status='applied'`

---

## Exemplo de Resposta do Endpoint de Status

`GET /api/quo/quotations/55/discount-status`

```json
{
  "success": true,
  "message": "OK",
  "data": {
    "quotation_id": 55,
    "all_consistent": true,
    "items": [
      {
        "quotation_version_id": 101,
        "version": "v2",
        "table_code": "A",
        "discount_percent": 15,
        "control_discount_percent": 15,
        "status": "applied",
        "is_consistent": true,
        "applied_at": "2026-05-12T10:20:00Z"
      },
      {
        "quotation_version_id": 102,
        "version": "v2",
        "table_code": "B",
        "discount_percent": 15,
        "control_discount_percent": 15,
        "status": "applied",
        "is_consistent": true,
        "applied_at": "2026-05-12T10:20:00Z"
      }
    ]
  },
  "code": 200
}
```

---

## Arquivos Alterados no Código

- `app/Services/QUO/QUOQuotationsService.php`
  - Sincronização cross-anexo
  - Upsert na tabela de controle
  - Método `getDiscountStatus`

- `app/Services/QUO/QUOQuotationVersionsService.php`
  - Sincronização cross-anexo
  - Upsert na tabela de controle

- `app/Http/Controllers/Api/QUO/QUOQuotationsController.php`
  - Endpoint `discountStatus`

- `routes/api.php`
  - Rota `GET /quo/quotations/{id}/discount-status`

- `app/Models/QUO/QUO_quotation_discount_controls.php`
  - Model da nova tabela

- `database/migrations/2026_05_12_100500_create_quo_quotation_discount_controls_table.php`
  - Estrutura da nova tabela

---

## Operação e Deploy

1. Rodar migration:
```bash
php artisan migrate
```

2. Validar em ambiente de teste:
- Criar/editar desconto em anexo A
- Confirmar propagação em B/C
- Consultar `GET /api/quo/quotations/{id}/discount-status`

---

## Limitações Conhecidas / Próximo Passo

- O fluxo atual garante consistência no momento de criação/edição.
- Pode ser adicionado um endpoint de reconciliação em lote para histórico legado (corrigir cotações antigas com inconsistência).
