# Plano de Integração: Sistema Legado Coral

Este documento define a estrutura de dados (API) necessária para a integração e migração de registros do sistema legado **Coral** para o novo sistema.

## Objetivo
Permitir que os desenvolvedores do sistema Coral exportem dados em um formato compatível que será processado e marcado com o status `Legacy` (Legado) no novo banco de dados.

## 1. Estrutura de Contratos (Contracts)

### Endpoint Sugerido: `POST /api/v1/import/legacy/contracts`

**Campos Principais:**

| Campo | Tipo | Obrigatório | Descrição |
| :--- | :--- | :--- | :--- |
| `numero_contrato` | String(30) | Sim | Identificador único do contrato no Coral. |
| `artista` | String | Sim | Nome do artista (será normalizado no sistema). |
| `data_evento` | Date (ISO) | Sim | Data do show/evento (YYYY-MM-DD). |
| `valor_bruto` | Decimal | Sim | Valor total do contrato. |
| `moeda` | String(3) | Não | Padrão: `BRL`. Opções: `USD`, `EUR`, `GBP`. |
| `status` | String | Não | Ex: `Confirmado`, `Cancelado`, `Pendente`. |
| `legal_entity` | String | Não | Padrão: `CORAL`. |

**Exemplo JSON:**
```json
{
  "contracts": [
    {
      "numero_contrato": "COR-2024-001",
      "artista": "Artista Exemplo",
      "data_evento": "2024-12-31",
      "valor_bruto": 50000.00,
      "moeda": "BRL",
      "status": "Confirmado",
      "venue": "Clube X",
      "city": "São Paulo",
      "state": "SP"
    }
  ]
}
```

---

## 2. Estrutura de Recebíveis (Receivables)

### Endpoint Sugerido: `POST /api/v1/import/legacy/receivables`

**Campos Principais:**

| Campo | Tipo | Obrigatório | Descrição |
| :--- | :--- | :--- | :--- |
| `contrato_ref` | String(30) | Sim | Deve bater com o `numero_contrato` enviado no lote anterior. |
| `parcela` | String | Sim | Formato `X/Y` (ex: `1/2`). |
| `valor` | Decimal | Sim | Valor da parcela. |
| `data_vencimento` | Date (ISO) | Sim | Data para recebimento. |
| `status_pagamento` | String | Não | `pago`, `pendente`, `atrasado`. |

**Exemplo JSON:**
```json
{
  "receivables": [
    {
      "contrato_ref": "COR-2024-001",
      "parcela": "1/1",
      "valor": 50000.00,
      "data_vencimento": "2024-12-15",
      "status_pagamento": "pago",
      "data_pagamento": "2024-12-14"
    }
  ]
}
```

---

## 3. Estrutura de Pagamentos (Payables)

### Endpoint Sugerido: `POST /api/v1/import/legacy/payables`

**Campos Principais:**

| Campo | Tipo | Obrigatório | Descrição |
| :--- | :--- | :--- | :--- |
| `ctr_ref` | String(30) | Não | Referência ao contrato (se houver). |
| `descricao` | String | Sim | Descrição do custo (ex: "Logística", "Equipamento"). |
| `contraparte` | String | Sim | Nome do fornecedor/prestador. |
| `valor_devido` | Decimal | Sim | Valor total a pagar. |
| `data_devida` | Date (ISO) | Sim | Data de vencimento do pagamento. |

**Exemplo JSON:**
```json
{
  "payables": [
    {
      "ctr_ref": "COR-2024-001",
      "descricao": "Cachê Artista",
      "contraparte": "Empresa Artista Ltda",
      "valor_devido": 30000.00,
      "data_devida": "2025-01-05",
      "status": "paid"
    }
  ]
}
```

## Regras de Processamento (Lógica Interna)
1.  **Legal Entity**: Todos os registros importados via estes endpoints serão associados à `LegalEntity` "Coral" (ID 4).
2.  **Status Legado**: O campo `contract_data_status` de todos os contratos criados será definido como `Legacy` (Legado).
3.  **Normalização**: Nomes de artistas e bookers passarão pelo `ValueParser` para garantir integridade com o banco atual.
