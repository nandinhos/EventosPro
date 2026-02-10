# Resumo do Projeto EventosPro

> **Arquivo de Referência Rápida** para agentes AI Dev Superpowers
> **Versão**: 1.0 | **Atualizado**: 2026-02-10

---

## 🎯 Visão Executiva

O **EventosPro** é um sistema de gestão especializado para agências de artistas e bookers que gerenciam eventos artísticos (Gigs/Datas). Substitui controles manuais (planilhas) por plataforma centralizada com controle financeiro completo desde o agendamento até o acerto.

### Propósito
- Centralizar gestão de eventos artísticos
- Automatizar cálculos financeiros (cachês, despesas, comissões)
- Acompanhar status de pagamentos (cliente → agência → artista/booker)
- Gerar relatórios financeiros e auditorias
- Preservar histórico completo com soft deletes

### Público-Alvo
- Agências de artistas (múltiplos talentos)
- Bookers independentes
- Gestores financeiros do setor de entretenimento

---

## 📊 Métricas do Sistema

| Métrica | Valor |
|---------|-------|
| **Modelos Eloquent** | 15 |
| **Tabelas do Banco** | 27 |
| **Services de Negócio** | 17 |
| **Rotas HTTP** | 174 |
| **Filament Resources** | 4 principais |
| **Linha de Código Doc** | 7349+ linhas |

---

## 🗂️ Entidade Principal: Gig

Gig é a entidade central do sistema. Tudo gira em torno de um evento/apresentação.

### Relacionamentos Principais
```
Gig (1) ←→ (N) Payment          → Parcelas de recebimento do cliente
Gig (1) ←→ (N) GigCost          → Despesas por centro de custo
Gig (1) ←→ (1) Settlement       → Acertos financeiros (pagamentos efetuados)
Gig (N) ←→ (1) Artist           → Artista responsável
Gig (N) ←→ (1) Booker           → Booker responsável (opcional)
Gig (N) ←→ (N) Tag (polimórfico)→ Categorização flexível
```

### Ciclo de Vida Financeiro de uma Gig
1. **Criação**: Definir valores, datas, artista/booker
2. **Pagamentos**: Registrar parcelas do cliente
3. **Custos**: Registrar despesas por categoria
4. **Cálculo**: Calcular comissões automaticamente
5. **Acerto**: Pagar artista/booker
6. **Auditoria**: Verificar integridade financeira

---

## 🧩 Arquitetura em 3 Camadas

```
┌─────────────────────────────────────────┐
│  CAMADA 1: APRESENTAÇÃO                 │
│  - Filament v4 (admin panel)           │
│  - Blade + Tailwind CSS + Alpine.js    │
│  - Chart.js + SweetAlert2              │
└─────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────┐
│  CAMADA 2: LÓGICA DE NEGÓCIO            │
│  - Controllers (validação → response)   │
│  - Services (cálculos + regras)         │
│  - Observers (eventos automáticos)      │
│  - Policies (autorização)               │
└─────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────┐
│  CAMADA 3: DADOS                        │
│  - Models (Eloquent)                    │
│  - Migrations (schema control)          │
│  - Seeders (dados base)                 │
│  - MySQL (27 tabelas)                   │
└─────────────────────────────────────────┘
```

---

## 🔧 Stack Tecnológica (Resumido)

### Backend
- **PHP**: 8.4.14
- **Laravel**: 12.43.1
- **MySQL**: 8.0+ (via Docker Sailor)
- **Composer**: Gerenciamento de pacotes

### Frontend
- **Filament**: 4.1.3 (admin panel)
- **Tailwind CSS**: 3.4.1
- **Alpine.js**: 3.13.5 (interatividade)
- **Vite**: 5.0.12 (build system)

### Bibliotecas Principais
- **Spatie Permission**: 6.9.0 (permissões)
- **Laravel Breeze**: 2.2.5 (autenticação)
- **Maatwebsite Excel**: 3.1.55 (exportação Excel)
- **DOMPDF**: 2.2.0 (geração de PDF)

### DevOps
- **Laravel Sail**: Docker obrigatório para desenvolvimento
- **Laravel Pint**: Formatação de código
- **PHPUnit**: Framework de testes

---

## 💼 Camada de Serviços (17 Services)

A lógica de negócio complexa está encapsulada em services.

### Services Financeiros (Críticos)
| Service | Responsabilidade |
|---------|------------------|
| `GigFinancialCalculatorService` | Cálculos financeiros (11 métodos) |
| `FinancialProjectionService` | Projeções financeiras |
| `CashFlowProjectionService` | Fluxo de caixa projetado |
| `DreProjectionService` | DRE projetado |
| `FinancialReportService` | Relatórios financeiros |
| `AuditService` | Auditoria e divergências |

### Services de Gestão
| Service | Responsabilidade |
|---------|------------------|
| `ArtistFinancialsService` | Métricas financeiras de artistas |
| `BookerFinancialsService` | Métricas financeiras de bookers |
| `SettlementService` | Gestão de acertos |
| `NotificationService` | Notificações |
| `UserManagementService` | Gestão de usuários |
| `DashboardService` | Dados para dashboard |

### Services de Suporte
| Service | Responsabilidade |
|---------|------------------|
| `ExchangeRateService` | Conversão de moedas |
| `CurrencyConversionService` | Taxas de câmbio |
| `CommissionPaymentValidationService` | Validação pagamentos comissão |
| `GigAuditCommandService` | Comando de auditoria |

**Fonte Detalhada**: `doc-project/SERVICES_API.md` (1400+ linhas)

---

## 🎨 Frontend: Filament v4 Resources

### 4 Resources Principais
1. **Artist**: CRUD de artistas com métricas
2. **Booker**: CRUD de bookers com configuração de comissões
3. **Gig**: CRUD de eventos + gestão financeira
4. **User**: Gestão de usuários + permissões

### Outros Componentes
- **Widgets**: KPIs em tempo real (Gigs ativas, pagamentos vencidos)
- **Pages**: Relatórios specialized (vencimentos, DRE, cashflow)
- **Actions**: Operações customizadas (auditoria, exportação)

---

## 🔒 Segurança e Autenticação

### Autenticação
- **Laravel Breeze**: Sistema de autenticação base
- **Session**: Database driver
- **Middleware**: CSRF, auth, permissions

### Autorização
- **Spatie Laravel Permission**: Controle granular de permissões
- **Policies**: Autorização por operação (GigPolicy, ArtistPolicy, etc)
- **Roles**: Sistema de papéis configuráveis

### Proteção
- **Soft Deletes**: Preserva histórico em todos os modelos principais
- **Policy Gates**: Validação antes de ações
- **Mass Assignment Protection**: Fillable/Guarded nos modelos

---

## 📦 Padrões de Projeto

### 1. Service Layer Pattern
- **Localização**: `app/Services/`
- **Propósito**: Encapsular lógica de negócio complexa
- **Exemplo**: `GigFinancialCalculatorService` centraliza todos os cálculos financeiros

### 2. Observer Pattern
- **Localização**: `app/Observers/`
- **Propósito**: Reações automáticas a eventos do modelo
- **Exemplo**: `GigObserver` e `GigCostObserver` disparam recálculos

### 3. Policy Pattern
- **Localização**: `app/Policies/`
- **Propósito**: Controle de autorização granular
- **Exemplo**: `GigPolicy` define quem pode editar/ver/deletar

### 4. Request Validation Pattern
- **Localização**: `app/Http/Requests/`
- **Propósito**: Validação centralizada e reutilizável
- **Exemplo**: `StoreGigRequest`, `UpdateGigRequest`

### 5. Soft Delete Pattern
- **Propósito**: Preservação de histórico financeiro
- **Implementação**: Trait `SoftDeletes` nos modelos

---

## ⚙️ Comandos Obrigatórios (Sail)

**REGRAS CRÍTICAS:**
- **TODOS** os comandos devem usar `vendor/bin/sail`
- **NUNCA** execute PHP ou Artisan direto (fora de container)

### Comandos de Desenvolvimento
```bash
# Iniciar containers
vendor/bin/sail up -d

# Parar containers
vendor/bin/sail stop

# Abrir no navegador
vendor/bin/sail open

# Acessar shell
vendor/bin/sail bash
```

### Comandos de Testes
```bash
# Rodar todos os testes
vendor/bin/sail artisan test

# Rodar arquivo específico
vendor/bin/sail artisan test tests/Unit/Services/AuditServiceTest.php

# Rodar teste específico
vendor/bin/sail artisan test --filter=testCalculateGigAuditData

# Rodar com coverage
vendor/bin/sail artisan test --coverage
```

### Comandos de Qualidade
```bash
# Formatar código (OBRIGATÓRIO antes de commits)
vendor/bin/sail bin pint --dirty
```

**Fonte Detalhada**: `doc-project/LARAVEL_SAIL_COMMANDS.md`

---

## 🎯 Fonte da Verdade

Arquivo mestre: **`doc-project/SOURCE_OF_TRUTH.md`**

Contém:
- Dados (modelos, tabelas, seeders)
- Regras de Negócio (services)
- Invariantes/Eventos (observers, policies)
- Interface (Filament resources, rotas)
- Configuração
- Changelog

---

## 📚 Documentação Principal

### Contexto de Negócio
- `doc-project/ai_context/1_context.md` - Contexto do projeto
- `doc-project/ai_context/2_architecture.md` - Arquitetura
- `doc-project/ai_context/3_stack.md` - Stack completa
- `doc-project/ai_context/4_rules.md` - Regras

### Documentação Técnica
- `doc-project/TECHNICAL_OVERVIEW.md` - Visão técnica
- `doc-project/DATABASE.md` - Schema completo (700+ linhas)
- `doc-project/SERVICES_API.md` - API dos 17 services (1400+ linhas)
- `doc-project/CODE_STANDARDS.md` - Padrões de código

### Operações
- `doc-project/LARAVEL_SAIL_COMMANDS.md` - Comandos Sail
- `doc-project/DEPLOY_PROCEDURE.md` - Procedimentos deploy
- `doc-project/SETUP_GUIDE.md` - Guia de setup

---

## 🔄 Fluxo Típico de Requisição

```
Rota (routes/web.php) → Middleware (auth, permissions)
→ Controller → Validação (Form Request)
→ Service Layer → Model/Eloquent
→ Observer (eventos automáticos)
→ View/Response
```

### Exemplo: Criar Gig
```
POST /gigs → GigController@store
  ├─ Validação via StoreGigRequest
  ├─ Criação do modelo Gig
  ├─ GigObserver dispara eventos
  ├─ Cálculos via GigFinancialCalculatorService
  └─ Redirect com mensagem
```

---

## ⚠️ Pontos Críticos para Desenvolvedores

1. **Service Layer OBRIGATÓRIO**: Nunca coloque lógica de negócio complexa em controllers. Use services.
2. **Laravel Sail OBRIGATÓRIO**: Todos os comandos via containers.
3. **Pint ANTES de Commits**: Formatar código com `sail bin pint --dirty`
4. **Soft Deletes**: Nunca use `delete()` sem considerar histórico financeiro.
5. **Eager Loading**: Sempre carregue relacionamentos para evitar N+1.
6. **Conversão de Moedas**: Sempre converter para BRL em relatórios.
7. **Pagamentos**: Artistas/bookers só podem ser pagos após datas dos eventos.

---

## 📋 Checklist de Referência Rápida

### Começar Desenvolvimento
- [ ] Container Sail rodando: `vendor/bin/sail up -d`
- [ ] Formatar código: `vendor/bin/sail bin pint --dirty`
- [ ] Verificar testes: `vendor/bin/sail artisan test --filter=testName`

### Criar Nova Feature
- [ ] Criar teste antes (TDD)
- [ ] Usar Service para lógica de negócio
- [ ] Validação em Form Request
- [ ] Policy para autorização
- [ ] Observer se necessário
- [ ] Rodar testes específicos

### Comitar Mudanças
- [ ] Pint: `vendor/bin/sail bin pint --dirty`
- [ ] Testes: `vendor/bin/sail artisan test`
- [ ] Mensagem em PORTUGUÊS
- [ ] Sem emojis
- [ ] Formato: `tipo(escopo): descricao`

---

**Versão**: 1.0
**Para Mais Detalhes**: Consulte arquivos em `doc-project/`