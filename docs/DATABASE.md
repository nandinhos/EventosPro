# Estrutura do Banco de Dados - EventosPro

**Última Atualização:** 2025-10-07
**Versão:** 1.0.0
**DBMS:** MySQL 8.0.32

---

## Índice

1. [Visão Geral](#visão-geral)
2. [Diagrama de Relacionamentos](#diagrama-de-relacionamentos)
3. [Tabelas Principais](#tabelas-principais)
4. [Tabelas de Suporte](#tabelas-de-suporte)
5. [Regras de Negócio](#regras-de-negócio)
6. [Migrations](#migrations)

---

## Visão Geral

O sistema EventosPro gerencia eventos artísticos (Gigs), com controle financeiro completo incluindo:
- Cachês de artistas
- Comissões de agência e bookers
- Pagamentos parcelados
- Despesas de eventos
- Acertos financeiros (Settlements)

### Entidades Principais
- **Gig** - Evento artístico (entidade central)
- **Artist** - Artista contratado
- **Booker** - Agenciador/vendedor
- **Payment** - Parcelas de pagamento do cliente
- **GigCost** - Despesas do evento
- **Settlement** - Acerto financeiro final

---

## Diagrama de Relacionamentos

```
┌─────────────┐
│   Artist    │
│  (artista)  │
└──────┬──────┘
       │ 1:N
       │
┌──────▼──────────────────────────────────────────────┐
│                     Gig                             │
│              (evento artístico)                     │
│                                                     │
│ - Cachê do artista                                  │
│ - Comissões (agência + booker)                     │
│ - Status de pagamentos                              │
└──────┬──────────────────────┬──────────────┬───────┘
       │ 1:N                  │ 1:N          │ 1:1
       │                      │              │
┌──────▼──────┐      ┌────────▼────────┐   ┌▼──────────┐
│  Payment    │      │    GigCost      │   │Settlement │
│ (parcelas)  │      │   (despesas)    │   │ (acerto)  │
└─────────────┘      └─────────────────┘   └───────────┘
                              │
                              │ N:1
                         ┌────▼────────┐
                         │ CostCenter  │
                         │  (centro    │
                         │   custo)    │
                         └─────────────┘

┌─────────────┐
│   Booker    │
│ (vendedor)  │
└──────┬──────┘
       │ 1:N
       └─────────► Gig

┌─────────────┐
│    User     │
│  (usuário)  │
└──────┬──────┘
       │ 1:1 (opcional)
       └─────────► Booker
```

---

## Tabelas Principais

### 1. **gigs** (Eventos Artísticos)

**Descrição**: Tabela central do sistema. Armazena informações sobre eventos artísticos.

**Estrutura** (24 colunas):

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| `id` | bigint unsigned | NOT NULL | AUTO | ID único |
| `artist_id` | bigint unsigned | NOT NULL | - | FK para artists |
| `booker_id` | bigint unsigned | NULL | - | FK para bookers |
| `contract_number` | varchar(100) | NULL | - | Número do contrato |
| `contract_date` | date | NULL | - | Data de assinatura |
| `gig_date` | date | NOT NULL | - | Data do evento |
| `location_event_details` | text | NOT NULL | - | Local e detalhes |
| `cache_value` | decimal(12,2) | NOT NULL | - | Valor do cachê original |
| `currency` | varchar(10) | NOT NULL | 'BRL' | Moeda do cachê |
| `agency_commission_type` | varchar(10) | NULL | 'percent' | Tipo: 'percent' ou 'fixed' |
| `agency_commission_rate` | decimal(5,2) | NULL | 20.00 | Taxa % da agência |
| `agency_commission_value` | decimal(12,2) | NULL | - | Valor em BRL (calculado) |
| `booker_commission_type` | varchar(10) | NULL | 'percent' | Tipo: 'percent' ou 'fixed' |
| `booker_commission_rate` | decimal(5,2) | NULL | 5.00 | Taxa % do booker |
| `booker_commission_value` | decimal(12,2) | NULL | - | Valor em BRL (calculado) |
| `liquid_commission_value` | decimal(12,2) | NULL | - | Comissão líquida agência |
| `contract_status` | varchar(50) | NOT NULL | 'n/a' | Status do contrato |
| `payment_status` | varchar(50) | NOT NULL | 'a_vencer' | Status recebimento cliente |
| `artist_payment_status` | varchar(50) | NOT NULL | 'pendente' | Status pagamento artista |
| `booker_payment_status` | varchar(50) | NOT NULL | 'pendente' | Status pagamento booker |
| `notes` | text | NULL | - | Observações |
| `created_at` | timestamp | NULL | - | Data criação |
| `updated_at` | timestamp | NULL | - | Data atualização |
| `deleted_at` | timestamp | NULL | - | Soft delete |

**Índices**:
- PRIMARY KEY (`id`)
- INDEX (`artist_id`)
- INDEX (`booker_id`)
- INDEX (`gig_date`)
- INDEX (`contract_status`)
- INDEX (`payment_status`)
- INDEX (`artist_payment_status`)
- INDEX (`booker_payment_status`)

**Foreign Keys**:
- `artist_id` → `artists.id` (ON DELETE CASCADE)
- `booker_id` → `bookers.id` (ON DELETE SET NULL)

**Relacionamentos Eloquent**:
```php
public function artist(): BelongsTo           // artists
public function booker(): BelongsTo           // bookers (withDefault)
public function payments(): HasMany           // payments
public function gigCosts(): HasMany           // gig_costs
public function settlement(): HasOne          // settlements
public function tags(): MorphToMany           // tags (polimórfico)
```

**Cálculos Importantes** (via GigFinancialCalculatorService):
- **Cachê Bruto BRL** = Valor Contrato (BRL) - Total Despesas Confirmadas
- **Comissão Agência Bruta** = Cachê Bruto × Taxa Agência (ou valor fixo)
- **Comissão Booker** = Cachê Bruto × Taxa Booker (ou valor fixo)
- **Comissão Agência Líquida** = Comissão Agência Bruta - Comissão Booker
- **Cachê Líquido Artista** = Cachê Bruto - Comissão Agência Bruta
- **NF Artista** = Cachê Líquido Artista + Despesas Reembolsáveis (is_invoice=true)

---

### 2. **payments** (Parcelas de Pagamento)

**Descrição**: Parcelas de pagamento recebidas do cliente para uma Gig.

**Estrutura** (15 colunas):

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| `id` | bigint unsigned | NOT NULL | AUTO | ID único |
| `gig_id` | bigint unsigned | NOT NULL | - | FK para gigs |
| `description` | varchar(255) | NULL | - | Descrição da parcela |
| `due_value` | decimal(12,2) | NOT NULL | - | Valor previsto |
| `due_date` | date | NOT NULL | - | Data de vencimento |
| `currency` | varchar(10) | NOT NULL | 'BRL' | Moeda |
| `exchange_rate` | decimal(10,6) | NULL | - | Taxa de câmbio real |
| `received_value_actual` | decimal(12,2) | NULL | - | Valor efetivamente recebido |
| `received_date_actual` | date | NULL | - | Data real do recebimento |
| `confirmed_at` | timestamp | NULL | - | Timestamp de confirmação |
| `confirmed_by` | bigint unsigned | NULL | - | FK para users |
| `notes` | text | NULL | - | Observações |
| `created_at` | timestamp | NULL | - | Data criação |
| `updated_at` | timestamp | NULL | - | Data atualização |
| `deleted_at` | timestamp | NULL | - | Soft delete |

**Índices**:
- PRIMARY KEY (`id`)
- INDEX (`gig_id`)
- INDEX (`due_date`)
- INDEX (`confirmed_at`)
- INDEX (`confirmed_by`)

**Foreign Keys**:
- `gig_id` → `gigs.id` (ON DELETE CASCADE)
- `confirmed_by` → `users.id` (ON DELETE SET NULL)

**Relacionamentos Eloquent**:
```php
public function gig(): BelongsTo              // gigs
public function confirmer(): BelongsTo        // users
```

**Regras de Negócio**:
- Taxa de câmbio (`exchange_rate`) é registrada no momento da confirmação
- Quando `confirmed_at` IS NOT NULL, o pagamento foi recebido
- Para moeda BRL, `exchange_rate` = 1.0
- Valor em BRL = `received_value_actual` × `exchange_rate`

---

### 3. **gig_costs** (Despesas do Evento)

**Descrição**: Despesas relacionadas a uma Gig.

**Estrutura** (15 colunas):

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| `id` | bigint unsigned | NOT NULL | AUTO | ID único |
| `gig_id` | bigint unsigned | NOT NULL | - | FK para gigs |
| `cost_center_id` | bigint unsigned | NOT NULL | - | FK para cost_centers |
| `description` | varchar(255) | NULL | - | Descrição da despesa |
| `value` | decimal(12,2) | NOT NULL | - | Valor |
| `currency` | varchar(10) | NOT NULL | 'BRL' | Moeda |
| `expense_date` | date | NULL | - | Data da despesa |
| `is_confirmed` | tinyint(1) | NOT NULL | 0 | Confirmada? |
| `is_invoice` | tinyint(1) | NOT NULL | 0 | Reembolsável (NF Artista)? |
| `confirmed_by` | bigint unsigned | NULL | - | FK para users |
| `confirmed_at` | timestamp | NULL | - | Timestamp confirmação |
| `notes` | text | NULL | - | Observações |
| `created_at` | timestamp | NULL | - | Data criação |
| `updated_at` | timestamp | NULL | - | Data atualização |
| `deleted_at` | timestamp | NULL | - | Soft delete |

**Índices**:
- PRIMARY KEY (`id`)
- INDEX (`gig_id`)
- INDEX (`cost_center_id`)
- INDEX (`is_confirmed`)
- INDEX (`is_invoice`)
- INDEX (`confirmed_by`)

**Foreign Keys**:
- `gig_id` → `gigs.id` (ON DELETE CASCADE)
- `cost_center_id` → `cost_centers.id` (ON DELETE RESTRICT)
- `confirmed_by` → `users.id` (ON DELETE SET NULL)

**Relacionamentos Eloquent**:
```php
public function gig(): BelongsTo              // gigs
public function costCenter(): BelongsTo       // cost_centers
public function confirmer(): BelongsTo        // users
```

**Regras de Negócio**:
- **TODAS** despesas confirmadas (`is_confirmed=true`) são deduzidas da base de comissão
- `is_invoice=true`: Despesa paga pelo artista, será reembolsada na NF
- Despesas NÃO confirmadas não afetam cálculos financeiros
- Observer (`GigCostObserver`) recalcula valores da Gig ao salvar/deletar

---

### 4. **settlements** (Acertos Financeiros)

**Descrição**: Registro do acerto final com artista e booker.

**Estrutura** (13 colunas):

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| `id` | bigint unsigned | NOT NULL | AUTO | ID único |
| `gig_id` | bigint unsigned | NOT NULL | - | FK para gigs |
| `settlement_date` | date | NOT NULL | - | Data do acerto |
| `artist_payment_value` | decimal(12,2) | NULL | - | Valor pago ao artista |
| `artist_payment_paid_at` | date | NULL | - | Data pagamento artista |
| `artist_payment_proof` | varchar(255) | NULL | - | Comprovante artista |
| `booker_commission_value_paid` | decimal(12,2) | NULL | - | Valor pago ao booker |
| `booker_commission_paid_at` | date | NULL | - | Data pagamento booker |
| `booker_commission_proof` | varchar(255) | NULL | - | Comprovante booker |
| `notes` | text | NULL | - | Observações |
| `created_at` | timestamp | NULL | - | Data criação |
| `updated_at` | timestamp | NULL | - | Data atualização |
| `deleted_at` | timestamp | NULL | - | Soft delete |

**Índices**:
- PRIMARY KEY (`id`)
- INDEX (`gig_id`)
- INDEX (`settlement_date`)

**Foreign Keys**:
- `gig_id` → `gigs.id` (ON DELETE CASCADE)

**Relacionamentos Eloquent**:
```php
public function gig(): BelongsTo              // gigs
```

**Regras de Negócio**:
- Uma Gig pode ter apenas UM settlement (hasOne)
- Valores pagos são registrados nesta tabela, NÃO na tabela `gigs`
- **IMPORTANTE**: `booker_commission_value_paid` está nesta tabela, não em `gigs`
- Só pode pagar comissão de eventos PASSADOS (ou com exceção autorizada)

---

## Tabelas de Suporte

### 5. **artists** (Artistas)

**Estrutura** (6 colunas):

| Coluna | Tipo | Nullable | Default |
|--------|------|----------|---------|
| `id` | bigint unsigned | NOT NULL | AUTO |
| `name` | varchar(255) | NOT NULL | - |
| `contact_info` | text | NULL | - |
| `created_at` | timestamp | NULL | - |
| `updated_at` | timestamp | NULL | - |
| `deleted_at` | timestamp | NULL | - |

**Índices**: PRIMARY KEY (`id`), UNIQUE (`name`)

**Relacionamentos**:
```php
public function gigs(): HasMany               // gigs
public function tags(): MorphToMany           // tags
```

---

### 6. **bookers** (Agenciadores/Vendedores)

**Estrutura** (6 colunas):

| Coluna | Tipo | Nullable | Default |
|--------|------|----------|---------|
| `id` | bigint unsigned | NOT NULL | AUTO |
| `name` | varchar(255) | NOT NULL | - |
| `default_commission_rate` | decimal(5,2) | NULL | - |
| `created_at` | timestamp | NULL | - |
| `updated_at` | timestamp | NULL | - |
| `deleted_at` | timestamp | NULL | - |

**Índices**: PRIMARY KEY (`id`), UNIQUE (`name`)

**Relacionamentos**:
```php
public function gigs(): HasMany               // gigs
public function user(): HasOne                // users
```

---

### 7. **cost_centers** (Centros de Custo)

**Estrutura** (5 colunas):

| Coluna | Tipo | Nullable | Default |
|--------|------|----------|---------|
| `id` | bigint unsigned | NOT NULL | AUTO |
| `name` | varchar(255) | NOT NULL | - |
| `description` | text | NULL | - |
| `created_at` | timestamp | NULL | - |
| `updated_at` | timestamp | NULL | - |

**Índices**: PRIMARY KEY (`id`), UNIQUE (`name`)

**Relacionamentos**:
```php
public function gigCosts(): HasMany           // gig_costs
```

**Exemplos**: Transporte, Hospedagem, Alimentação, Equipamentos, etc.

---

### 8. **users** (Usuários)

**Estrutura** (10 colunas):

| Coluna | Tipo | Nullable | Default |
|--------|------|----------|---------|
| `id` | bigint unsigned | NOT NULL | AUTO |
| `booker_id` | bigint unsigned | NULL | - |
| `name` | varchar(255) | NOT NULL | - |
| `email` | varchar(255) | NOT NULL | - |
| `email_verified_at` | timestamp | NULL | - |
| `password` | varchar(255) | NOT NULL | - |
| `remember_token` | varchar(100) | NULL | - |
| `created_at` | timestamp | NULL | - |
| `updated_at` | timestamp | NULL | - |
| `deleted_at` | timestamp | NULL | - |

**Índices**: PRIMARY KEY (`id`), UNIQUE (`email`)

**Foreign Keys**: `booker_id` → `bookers.id` (ON DELETE SET NULL)

---

### 9. **tags** (Tags/Etiquetas)

**Estrutura** (6 colunas):

| Coluna | Tipo | Nullable | Default |
|--------|------|----------|---------|
| `id` | bigint unsigned | NOT NULL | AUTO |
| `name` | varchar(255) | NOT NULL | - |
| `slug` | varchar(255) | NOT NULL | - |
| `type` | varchar(50) | NULL | - |
| `created_at` | timestamp | NULL | - |
| `updated_at` | timestamp | NULL | - |

**Índices**: PRIMARY KEY (`id`), UNIQUE (`name`), UNIQUE (`slug`), INDEX (`type`)

---

### 10. **taggables** (Tabela Pivot Polimórfica)

**Estrutura** (3 colunas):

| Coluna | Tipo | Nullable |
|--------|------|----------|
| `tag_id` | bigint unsigned | NOT NULL |
| `taggable_type` | varchar(255) | NOT NULL |
| `taggable_id` | bigint unsigned | NOT NULL |

**Índices**:
- PRIMARY KEY (`tag_id`, `taggable_id`, `taggable_type`)
- INDEX (`taggable_type`, `taggable_id`)

**Foreign Keys**: `tag_id` → `tags.id` (ON DELETE CASCADE)

**Uso**: Permite adicionar tags a qualquer modelo (Gig, Artist, etc.)

---

## Regras de Negócio

### Fluxo Financeiro

```
┌──────────────────────────────────────────────────────┐
│              VALOR DO CONTRATO (BRL)                 │
└────────────────────┬─────────────────────────────────┘
                     │
                     ▼
          ┌──────────────────────┐
          │  DESPESAS CONFIRMADAS │
          │    (deduzidas)        │
          └──────────┬────────────┘
                     ▼
          ┌──────────────────────┐
          │    CACHÊ BRUTO        │
          └──────────┬────────────┘
                     │
         ┌───────────┴───────────┐
         │                       │
         ▼                       ▼
┌────────────────┐    ┌──────────────────┐
│  COMISSÃO      │    │  CACHÊ LÍQUIDO   │
│  AGÊNCIA BRUTA │    │  ARTISTA         │
└────────┬───────┘    └──────────┬───────┘
         │                       │
         │                       ▼
         │            ┌──────────────────┐
         │            │ + REEMBOLSOS     │
         │            │  (is_invoice=1)  │
         │            └──────────┬───────┘
         │                       │
         │                       ▼
         │            ┌──────────────────┐
         │            │   NF ARTISTA     │
         │            └──────────────────┘
         │
         ▼
┌────────────────┐
│  COMISSÃO      │
│  BOOKER        │
└────────┬───────┘
         │
         ▼
┌────────────────┐
│  COMISSÃO      │
│  LÍQUIDA       │
│  AGÊNCIA       │
└────────────────┘
```

### Status do Pagamento

**payment_status** (Recebimento do Cliente):
- `a_vencer` - Ainda não venceu
- `vencido` - Venceu mas não foi pago
- `pago` - Totalmente recebido

**artist_payment_status** (Pagamento ao Artista):
- `pendente` - Não pago
- `pago` - Pago (registrado em `settlements`)

**booker_payment_status** (Pagamento ao Booker):
- `pendente` - Não pago
- `pago` - Pago (registrado em `settlements`)

### Validações de Auditoria

1. **Integridade Referencial**: Artistas e Bookers devem existir
2. **Status de Pagamento**: Consistência entre parcelas e status geral
3. **Comissões**: Valores calculados vs armazenados
4. **Datas**: Data evento ≥ Data contrato
5. **Settlements**: Só pagar eventos passados (exceto autorizados)
6. **Custos**: Não confirmar custos antes do evento acontecer
7. **Moedas**: Consistência entre gig e custos/pagamentos

---

## Migrations

### Ordem de Criação (Dependências)

```
1. artists
2. bookers
3. cost_centers
4. users (depende: bookers)
5. gigs (depende: artists, bookers)
6. payments (depende: gigs, users)
7. gig_costs (depende: gigs, cost_centers, users)
8. settlements (depende: gigs)
9. tags
10. taggables (depende: tags)
```

### Alterações Importantes

**NUNCA EXISTIRAM EM `gigs`**:
- ❌ `booker_commission_value_paid` (está em `settlements`)
- ❌ `artist_payment_paid_at` (está em `settlements`)
- ❌ `artist_payment_value` (está em `settlements`)
- ❌ `artist_payment_proof` (está em `settlements`)

**Campos que EXISTEM em `gigs`**:
- ✅ `agency_commission_value` (calculado e armazenado)
- ✅ `booker_commission_value` (calculado e armazenado)
- ✅ `liquid_commission_value` (calculado e armazenado)

---

## Campos Calculados vs Armazenados

### Armazenados no Banco (em `gigs`):
- `agency_commission_value` - Atualizado pelo GigObserver
- `booker_commission_value` - Atualizado pelo GigObserver
- `liquid_commission_value` - Atualizado pelo GigObserver

### Calculados Dinamicamente (Accessors):
- `cache_value_brl` - Via GigFinancialCalculatorService
- `gross_cash_brl` - Cachê bruto
- `total_confirmed_expenses_brl` - Total despesas confirmadas
- `total_reimbursable_expenses_brl` - Despesas reembolsáveis
- `calculated_artist_net_payout_brl` - Cachê líquido artista
- `calculated_artist_invoice_value_brl` - Valor NF artista

### Armazenados em `settlements`:
- `artist_payment_value` - Valor efetivamente pago ao artista
- `artist_payment_paid_at` - Data do pagamento
- `booker_commission_value_paid` - Valor efetivamente pago ao booker
- `booker_commission_paid_at` - Data do pagamento

---

## Observers

### GigObserver
- **Evento**: `saving`
- **Ação**: Recalcula comissões quando tipo/taxa mudam
- **Campos Atualizados**: `agency_commission_value`, `booker_commission_value`, `liquid_commission_value`

### GigCostObserver
- **Evento**: `saved`, `deleted`
- **Ação**: Dispara recalculo da Gig pai
- **Objetivo**: Manter valores de comissão atualizados quando despesas mudam

---

## Importante para Manutenção

### Ao Criar Nova Migration:

1. **Atualizar este arquivo** (`docs/DATABASE.md`)
2. **Verificar relacionamentos** afetados
3. **Atualizar Models** se necessário
4. **Atualizar Factories** se criar novas colunas obrigatórias
5. **Criar/Atualizar Seeders** se necessário
6. **Rodar testes** após migração

### Ao Modificar Estrutura:

```bash
# Sempre verificar estado atual
./vendor/bin/sail artisan db:table nome_tabela

# Após criar migration
./vendor/bin/sail artisan migrate

# Atualizar este documento
# Atualizar testes se necessário
./vendor/bin/sail artisan test
```

---

## Queries Úteis

### Verificar Integridade

```sql
-- Gigs sem artista válido
SELECT * FROM gigs
WHERE artist_id NOT IN (SELECT id FROM artists WHERE deleted_at IS NULL);

-- Custos sem Gig válido
SELECT * FROM gig_costs
WHERE gig_id NOT IN (SELECT id FROM gigs WHERE deleted_at IS NULL);

-- Pagamentos sem Gig válido
SELECT * FROM payments
WHERE gig_id NOT IN (SELECT id FROM gigs WHERE deleted_at IS NULL);

-- Settlements com valores divergentes
SELECT g.id, g.contract_number,
       g.booker_commission_value as calculado,
       s.booker_commission_value_paid as pago,
       (g.booker_commission_value - COALESCE(s.booker_commission_value_paid, 0)) as diferenca
FROM gigs g
LEFT JOIN settlements s ON g.id = s.gig_id
WHERE ABS(g.booker_commission_value - COALESCE(s.booker_commission_value_paid, 0)) > 0.01;
```

---

**FIM DA DOCUMENTAÇÃO**

Para dúvidas ou sugestões de melhorias, consulte a equipe de desenvolvimento.
