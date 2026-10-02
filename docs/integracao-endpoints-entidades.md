# Integração de Endpoints (com Entidades)

Esta documentação cobre os endpoints principais para consumo externo:
- Cotações
- Versões de cotações
- Propostas
- Apólices

Base URL (exemplo):
`https://SEU-DOMINIO/api`

Autenticação:
- Header `Authorization: Bearer {token}`
- Header `Accept: application/json`

---

## 1) Cotações

### 1.1 Listar cotações
`GET /quo/quotations`

Query params úteis:
- `status`
- `customer_id`
- `insurance_branch_id`
- `search`

Response exemplo:
```json
{
  "success": true,
  "message": "OK",
  "data": {
    "items": [
      {
        "id": 101,
        "quotation_number": "QUO-91-2026-00045",
        "status": "Pending",
        "created_at": "2026-04-07T10:15:00.000000Z",
        "customer": {
          "id": 12,
          "name": "Empresa Alfa, Lda",
          "nif": "5412345678",
          "email": "contacto@alfa.ao"
        },
        "branch": {
          "id": 13,
          "name": "Automóvel",
          "code": "91"
        },
        "agent": {
          "id": 8,
          "name": "Agente Comercial A"
        },
        "version": {
          "id": 890,
          "number": "v3",
          "total_premium": 65882.88,
          "currency": "AOA",
          "valid_until": "2026-05-07",
          "status": "Pending",
          "objects_count": 3
        }
      }
    ],
    "total": 1
  },
  "code": 200
}
```

### 1.2 Buscar cotação por ID
`GET /quo/quotations/{id}`

Inclui:
- `customer` (entidade)
- `branch`
- `agent` (entidade)
- `versions[]` (dados básicos de cada versão)

---

## 2) Versões de Cotação

### 2.1 Listar versões por cotação
`GET /quo/quotation-versions?quotation_id={quotation_id}`

Response exemplo:
```json
{
  "success": true,
  "message": "OK",
  "data": [
    {
      "id": 890,
      "version": "v3",
      "table_code": "A",
      "total_premium": 65882.88,
      "discount_percent": 0,
      "currency": "AOA",
      "status": "Pending",
      "created_date": "2026-04-01",
      "valid_until": "2026-05-07",
      "template_code": "AUTO_STD",
      "created_at": "2026-04-01T09:00:00.000000Z",
      "sales_channel": {
        "id": 1,
        "channel_type": "Broker",
        "name": "Canal Parceiros"
      },
      "cae_rate": {
        "id": 77,
        "cae": "5020",
        "description": "Transportes"
      },
      "objects_count": 3
    }
  ],
  "code": 200
}
```

### 2.2 Buscar versão detalhada
`GET /quo/quotation-versions/{id}`

Inclui:
- `quotation` com `customer` e `branch`
- `premiums[]`
- `special_conditions[]`
- `objects[]` com riscos e coberturas

---

## 3) Propostas

### 3.1 Listar propostas
`GET /quo/quotation-proposals`

Query params úteis:
- `quotation_version_id`
- `quotation_id`
- `status`
- `client_email`
- `client_name`
- `search`

Response exemplo:
```json
{
  "success": true,
  "data": {
    "proposals": [
      {
        "id": 55,
        "client_email": "cliente@alfa.ao",
        "client_name": "Empresa Alfa, Lda",
        "status": "completed",
        "status_formatted": "Completada",
        "sent_at": "07/04/2026 09:10",
        "viewed_at": "07/04/2026 09:30",
        "completed_at": "07/04/2026 10:02",
        "tracking_token": "6f5a8f1e2c...",
        "proposal_pdf_url": "https://SEU-DOMINIO/proposal-pdf/55",
        "proposal_form_url": "https://SEU-DOMINIO/proposal-form?token=6f5a8f1e2c",
        "quotation_version": {
          "id": 890,
          "version": 3,
          "table": "A"
        },
        "quotation": {
          "id": 101,
          "quotation_number": "QUO-91-2026-00045"
        },
        "is_expired": false,
        "days_remaining": 28
      }
    ]
  }
}
```

### 3.2 Lista simples de propostas
`GET /quo/quotation-proposals/list-simple`

Retorna campos leves para combos/listas rápidas.

---

## 4) Apólices

### 4.1 Listar apólices
`GET /pol/policies`

Query params úteis:
- `status`
- `customer_id`
- `insurance_branch_id`
- `search`
- `per_page`

Response exemplo (paginado):
```json
{
  "success": true,
  "message": "OK",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 200,
        "policy_number": "POL-91-2026-00012",
        "quotation_id": 101,
        "quotation_version_id": 890,
        "insurance_branch_id": 13,
        "customer_id": 12,
        "agent_id": 8,
        "inception_date": "2026-04-07",
        "current_expiry_date": "2027-04-06",
        "status": "Active",
        "customer": {
          "id": 12,
          "name": "Empresa Alfa, Lda"
        },
        "branch": {
          "id": 13,
          "name": "Automóvel",
          "code": "91"
        },
        "active_version": {
          "id": 301,
          "policy_id": 200,
          "version": 1,
          "effective_date": "2026-04-07",
          "expiry_date": "2027-04-06",
          "gross_premium": "65882.88",
          "currency": "AOA",
          "status": "Active"
        }
      }
    ],
    "total": 1,
    "per_page": 15,
    "last_page": 1
  },
  "code": 200
}
```

### 4.2 Buscar apólice por ID
`GET /pol/policies/{id}`

Inclui:
- `customer`, `branch`, `agent` (entidades)
- `quotation`
- `versions[]` com riscos e condições
- `movements[]`

### 4.3 Criar apólice a partir de cotação
`POST /pol/policies`

Payload mínimo exemplo:
```json
{
  "quotation_id": 101,
  "quotation_version_id": 890,
  "inception_date": "2026-04-07",
  "expiry_date": "2027-04-06",
  "net_premium": 57792.00,
  "total_taxes": 8090.88,
  "gross_premium": 65882.88
}
```

Response exemplo:
```json
{
  "success": true,
  "message": "Apólice criada com sucesso.",
  "data": {
    "id": 200,
    "policy_number": "POL-91-2026-00012",
    "quotation_id": 101,
    "quotation_version_id": 890,
    "status": "Active"
  },
  "code": 201
}
```

### 4.4 Cancelar apólice
`POST /pol/policies/{id}/cancel`

### 4.5 Criar endosso
`POST /pol/policies/{id}/amendment`

Payload exemplo:
```json
{
  "effective_date": "2026-05-01",
  "subtype": "Inclusão de cobertura",
  "premium_difference": 1200.00,
  "reason": "Alteração solicitada pelo cliente"
}
```

### 4.6 PDF da apólice
`GET /pol/policies/{id}/pdf?version_id={policy_version_id}`

Retorna `application/pdf`.

---

## 5) Notas de Integração

- `customer` e `agent` são entidades (`ent_entities`) e já vêm embutidos nas respostas principais.
- Para fluxo completo externo, a sequência comum é:
  1. `GET /quo/quotations`
  2. `GET /quo/quotation-versions?quotation_id={id}`
  3. `GET /quo/quotation-proposals` (filtrando por versão/cotação)
  4. `POST /pol/policies` para emissão
  5. `GET /pol/policies` / `GET /pol/policies/{id}` para acompanhamento
