# Arquitetura do Sistema EventosPro

## Arquitetura Geral

**Padrão Arquitetural**: Sistema monolítico baseado no padrão **MVC (Model-View-Controller)** do Laravel, com arquitetura em **3 camadas** bem definidas:

1. **Camada de Apresentação** (Views/Frontend)
2. **Camada de Lógica de Negócio** (Controllers/Services)
3. **Camada de Dados** (Models/Database)

**Características Arquiteturais**:
- **Monolito modular** com separação clara de responsabilidades
- **Service Layer** para lógica de negócio complexa
- **Observer Pattern** para eventos do sistema
- **Repository Pattern** implícito via Eloquent ORM
- **Policy-based Authorization** para controle de acesso

## Componentes Principais

### 1. Camada de Dados (Models)

#### Entidades Centrais
- **`Gig`**: Entidade principal representando eventos/apresentações
  - Relacionamentos: `BelongsTo` (Artist, Booker), `HasMany` (Payments, GigCosts), `HasOne` (Settlement)
  - Soft Deletes habilitado
  - Observer para eventos de ciclo de vida

- **`Payment`**: Parcelas de recebimento do cliente
  - Relacionamento com Gig e User (confirmador)
  - Controle de status e conversão de moedas

- **`GigCost`**: Despesas associadas aos eventos
  - Categorização por Centro de Custo
  - Observer para recálculos automáticos

- **`Settlement`**: Acertos financeiros (pagamentos efetuados)
  - Registro de pagamentos para artistas e bookers

#### Entidades de Apoio
- **`Artist`**: Cadastro de artistas
- **`Booker`**: Cadastro de bookers com configuração de comissões
- **`CostCenter`**: Categorização de despesas
- **`Tag`**: Sistema de tags polimórfico
- **`User`**: Usuários do sistema com roles/permissões

### 2. Camada de Lógica de Negócio

#### Controllers Principais
- **`GigController`**: CRUD completo de Gigs com lógica financeira
- **`FinancialReportController`**: Geração de relatórios especializados
- **`AuditController`**: Auditoria e verificação de integridade
- **`DashboardController`**: KPIs e métricas em tempo real

#### Services (Camada de Serviço)
- **`GigFinancialCalculatorService`**: 
  - Cálculo de Cachê Bruto em BRL
  - Cálculo de Comissão da Agência
  - Conversão de moedas com avisos
  - Carregamento otimizado de custos

- **`FinancialReportService`**:
  - Configuração de filtros de data
  - Aplicação de filtros em consultas
  - Integração com calculadora financeira

#### Observers
- **`GigObserver`**: Eventos de ciclo de vida das Gigs
- **`GigCostObserver`**: Recálculos automáticos ao alterar custos

### 3. Camada de Apresentação

#### Estrutura de Views
- **Layout Base**: `layouts/app.blade.php` com Tailwind CSS
- **Componentes Reutilizáveis**:
  - `components/status-dot.blade.php`: Indicadores visuais
  - `reports/components/*`: Componentes especializados para relatórios
- **Views Modulares**: Organizadas por funcionalidade (gigs/, reports/, etc.)

#### Frontend Stack
- **Tailwind CSS**: Framework CSS utilitário
- **Alpine.js**: Reatividade e interatividade
- **Chart.js**: Gráficos e visualizações
- **SweetAlert2**: Alertas e confirmações
- **Font Awesome**: Iconografia

## Fluxo de Dados

### Fluxo Típico de uma Requisição

1. **Rota** (`routes/web.php`) → **Middleware** (auth, permissions)
2. **Controller** recebe requisição e valida dados
3. **Service Layer** executa lógica de negócio complexa
4. **Model/Eloquent** interage com banco de dados
5. **Observer** executa ações automáticas (se aplicável)
6. **View** renderiza resposta com dados processados

### Exemplo: Criação de uma Gig

```
POST /gigs → GigController@store
├── Validação via StoreGigRequest
├── Criação do modelo Gig
├── GigObserver dispara eventos
├── Cálculos via GigFinancialCalculatorService
└── Redirect com mensagem de sucesso
```

### Exemplo: Relatório de Vencimentos

```
GET /reports/due-dates → FinancialReportController@dueDatesReport
├── FinancialReportService configura filtros
├── Query otimizada com relacionamentos
├── GigFinancialCalculatorService calcula valores
├── Agrupamento e ordenação de dados
└── Renderização da view com dados processados
```

## Padrões de Projeto Identificados

### 1. **Service Layer Pattern**
- **Localização**: `app/Services/`
- **Propósito**: Encapsular lógica de negócio complexa
- **Exemplos**: `GigFinancialCalculatorService`, `FinancialReportService`

### 2. **Observer Pattern**
- **Localização**: `app/Observers/`
- **Propósito**: Reações automáticas a eventos do modelo
- **Exemplos**: `GigObserver`, `GigCostObserver`

### 3. **Policy Pattern**
- **Localização**: `app/Policies/`
- **Propósito**: Controle de autorização granular
- **Exemplo**: `GigPolicy`

### 4. **Request Validation Pattern**
- **Localização**: `app/Http/Requests/`
- **Propósito**: Validação centralizada e reutilizável
- **Exemplos**: `StoreGigRequest`, `UpdateGigRequest`

### 5. **Soft Delete Pattern**
- **Implementação**: Trait `SoftDeletes` nos modelos
- **Propósito**: Preservação de histórico e integridade referencial

### 6. **Polymorphic Relationships**
- **Implementação**: Sistema de Tags
- **Propósito**: Flexibilidade na associação de entidades

## Arquitetura de Dados

### Relacionamentos Principais

```
Gig (1) ←→ (N) Payment
Gig (1) ←→ (N) GigCost
Gig (1) ←→ (1) Settlement
Gig (N) ←→ (1) Artist
Gig (N) ←→ (1) Booker
GigCost (N) ←→ (1) CostCenter
Tag (N) ←→ (N) Taggable (Polymorphic)
```

### Estratégias de Performance
- **Eager Loading**: Carregamento otimizado de relacionamentos
- **Query Scopes**: Filtros reutilizáveis nos modelos
- **Indexação**: Índices em campos de busca frequente
- **Soft Deletes**: Preservação sem impacto na performance

## Segurança e Autenticação

- **Laravel Breeze**: Sistema de autenticação base
- **Spatie Laravel Permission**: Controle granular de permissões
- **CSRF Protection**: Proteção automática do Laravel
- **Mass Assignment Protection**: Fillable/Guarded nos modelos
- **SQL Injection Prevention**: Eloquent ORM com prepared statements

## Extensibilidade

O sistema foi projetado para extensibilidade através de:
- **Service Providers**: Registro de serviços customizados
- **Event/Listener System**: Extensão via eventos
- **Blade Components**: Componentes reutilizáveis
- **Modular Structure**: Separação clara de responsabilidades