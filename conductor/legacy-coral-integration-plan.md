# Integração Sistema Legado Coral

Este plano descreve a implementação da API de importação para o sistema legado **Coral**, permitindo a migração de contratos, recebíveis e pagamentos para o novo sistema **EventosPro**.

## Objetivo
Disponibilizar endpoints de API para que o sistema Coral exporte dados em um formato compatível, garantindo a normalização e vinculação correta às entidades atuais (Artistas, Bookers, Gigs).

## 1. Atualizações de Banco de Dados (Database Updates)

### 1.1 Nova Tabela: `legal_entities`
- **Campos**: `id`, `name`, `description`, `created_at`, `updated_at`.
- **Seeder**: Criar a `LegalEntity` com `id = 4` e `name = "Coral"`.

### 1.2 Alterações na Tabela `gigs`
- **Campos a adicionar**:
  - `legal_entity_id`: `bigint`, nullable (ou default 1 para os atuais).
  - `contract_data_status`: `enum(['New', 'Legacy'])`, default 'New'.

## 2. Implementação da API (API Implementation)

### 2.1 Configuração de Rotas
- Criar `routes/api.php`.
- Registrar no `bootstrap/app.php`.
- Prefixar rotas com `api/v1/import/legacy/`.

### 2.2 Endpoints (Controllers & Requests)
- **`POST /contracts`**: Criação de `Gigs` com status `Legacy`.
- **`POST /receivables`**: Criação de `Payments` vinculados à `Gig` pelo `contract_number`.
- **`POST /payables`**: Criação de `GigCosts` vinculados à `Gig` pelo `contract_number`.

### 2.3 Validação (Form Requests)
- Garantir tipos de dados corretos (ISO Date, Decimal).
- Validar obrigatoriedade de campos conforme especificação.

## 3. Lógica de Negócio (Business Logic)

### 3.1 Normalização de Nomes
- Reutilizar a lógica de `GigImportService::normalizeName` para buscar Artistas e Bookers pelo nome.

### 3.2 Processamento de Recebíveis
- Se `status_pagamento == 'pago'`, preencher `received_value_actual`, `received_date_actual` e `confirmed_at`.

### 3.3 Associação de Entidade Legal
- Vincular automaticamente à `LegalEntity` "Coral" (ID 4).

## 4. Verificação e Testes (Verification & Testing)

### 4.1 Testes de Integração (Feature Tests)
- `tests/Feature/Api/LegacyImportTest.php`:
  - Testar importação de contrato único.
  - Testar importação de lote de contratos.
  - Testar associação correta com Artista (por nome).
  - Testar importação de recebível com vínculo ao contrato.
  - Testar importação de pagamento com vínculo ao contrato.
  - Validar erro ao enviar `contrato_ref` inexistente.

### 4.2 Verificação Manual
- Usar `tinker` para verificar se os registros foram criados com `contract_data_status = 'Legacy'`.
