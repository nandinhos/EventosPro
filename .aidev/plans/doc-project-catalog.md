# Catálogo Detalhado de Documentação - EventosPro

**Data**: 2026-02-10
**Estrutura**: Classificação completa dos arquivos em `doc-project/`

---

## 📚 Diretorio Raiz (`doc-project/`)

### 1. AI_CONTEXT.md
- **Path**: `doc-project/AI_CONTEXT.md`
- **Linhas**: 44
- **Tipo**: Indice / Metadados
- **Tags**: `[META]` `[AI]` `[CONTEXT]` `[REFERENCE]`
- **Propósito**: Indice rápido de documentos relevantes para agentes LLM
- **Uso Principal**: Orchestrator - ponto de partida para contexto
- **Status**: ✅ Ativo

**Seções Principais:**
- Índice rápido com tabela de documentos
- Pontos críticos do sistema
- Tags de contexto padronizadas

---

### 2. CODE_STANDARDS.md
- **Path**: `doc-project/CODE_STANDARDS.md`
- **Linhas**: ~50
- **Tipo**: Padrões e Convenções
- **Tags**: `[STANDARDS]` `[CONVENTIONS]` `[PHP]` `[CODE]`
- **Propósito**: Definir padrões de código para o projeto
- **Uso Principal**: Code reviewer, Backend, Frontend
- **Status**: ✅ Ativo

**Pontos Chave:**
- Padrões PHP 8.4
- Laravel conventions
- Filament patterns
- Naming conventions

---

### 3. DATABASE.md
- **Path**: `doc-project/DATABASE.md`
- **Linhas**: ~700
- **Tipo**: Documentação Técnica
- **Tags**: `[DATABASE]` `[SCHEMA]` `[MODELS]` `[RELATIONSHIPS]`
- **Propósito**: Documentação completa do schema do banco de dados
- **Uso Principal**: Architect, Backend, Legacy Analyzer
- **Status**: ✅ Ativo

**Conteúdo:**
- Tabelas existentes (27 tabelas)
- Colunas e tipos
- Relacionamentos
- Índices
- Constraints

---

### 4. DATA_MODEL.md
- **Path**: `doc-project/DATA_MODEL.md`
- **Linhas**: ~40
- **Tipo**: Arquitetura de Dados
- **Tags**: `[DATA]` `[MODEL]` `[LEGACY?]`
- **Propósito**: Descrição do modelo de dados
- **Uso Principal**: Architect (possivelmente superseded por DATABASE.md)
- **Status**: ⚠️ Verificar se ainda é relevante

---

### 5. DEPLOY_PROCEDURE.md
- **Path**: `doc-project/DEPLOY_PROCEDURE.md`
- **Linhas**: ~300
- **Tipo**: Operações
- **Tags**: `[DEPLOY]` `[OPS]` `[PROCEDURE]` `[INFRA]`
- **Propósito**: Procedimentos de deployment do sistema
- **Uso Principal**: DevOps
- **Status**: ✅ Ativo

**Seções:**
- Pre-deploy checklist
- Processo de deploy
- Rollback procedures
- Troubleshooting de deploy

---

### 6. DOCKER_TROUBLESHOOTING.md
- **Path**: `doc-project/DOCKER_TROUBLESHOOTING.md`
- **Linhas**: ~60
- **Tipo**: Troubleshooting
- **Tags**: `[DOCKER]` `[TROUBLESHOOTING]` `[SAIL]`
- **Propósito**: Resolução de problemas com Docker/Laravel Sail
- **Uso Principal**: DevOps, Backend
- **Status**: ✅ Ativo

**Tópicos:**
- Problemas comuns
- Soluções documentadas
- Logs e debugging

---

### 7. ENDPOINTS.md
- **Path**: `doc-project/ENDPOINTS.md`
- **Linhas**: ~120
- **Tipo**: Documentação de API
- **Tags**: `[API]` `[ROUTES]` `[ENDPOINTS]` `[HTTP]`
- **Propósito**: Catálogo das 174 rotas do sistema
- **Uso Principal**: Backend, Frontend, QA
- **Status**: ✅ Ativo

**Estrutura:**
- Rotas de Gigs (CRUD, financeiro, auditoria)
- Rotas de Reports (vencimentos, DRE, cashflow)
- Rotas de Artists/Bookers
- Rotas Admin (users, permissions)

---

### 8. GIG_FINANCIAL_CALCULATOR_API.md
- **Path**: `doc-project/GIG_FINANCIAL_CALCULATOR_API.md`
- **Linhas**: ~200
- **Tipo**: Documentação de API
- **Tags**: `[API]` `[SERVICE]` `[FINANCIAL]` `[CALCULATOR]`
- **Propósito**: Documentação da GigFinancialCalculatorService
- **Uso Principal**: Backend, QA, Auditoria
- **Status**: ✅ Ativo e Crítico

**Métodos Documentados:**
- 11 métodos de cálculo financeiro
- Parâmetros e retornos
- Exemplos de uso
- Regras de negócio

---

### 9. GIT_WORKFLOW.md
- **Path**: `doc-project/GIT_WORKFLOW.md`
- **Linhas**: ~100
- **Tipo**: Processo
- **Tags**: `[GIT]` `[WORKFLOW]` `[VERSION]` `[COMMIT]`
- **Propósito**: Fluxo de trabalho Git do projeto
- **Uso Principal**: Todos os agentes
- **Status**: ✅ Ativo

**Seções:**
- Branch strategy
- Commit message format
- Pull request process
- Merge procedures

---

### 10. LARAVEL_SAIL_COMMANDS.md
- **Path**: `doc-project/LARAVEL_SAIL_COMMANDS.md`
- **Linhas**: ~250
- **Tipo**: Documentação Técnica
- **Tags**: `[LARAVEL]` `[SAIL]` `[COMMANDS]` `[DOCKER]`
- **Propósito**: Comandos Laravel Sail do projeto
- **Uso Principal**: DevOps, Backend (todos os comandos obrigatórios)
- **Status**: ✅ Ativo e Crítico

**Categorias:**
- Build/Install commands
- Test commands
- Docker commands
- Development commands

---

### 11. LEGACY.md
- **Path**: `doc-project/LEGACY.md`
- **Linhas**: ~50
- **Tipo**: Documentação Arquivada
- **Tags**: `[LEGACY]` `[HISTORY]` `[ARCHIVED]`
- **Propósito**: Informações arquivadas/obsoletas
- **Uso Principal**: Legacy Analyzer
- **Status**: 📁 Arquivado

---

### 12. MCP_SERENA_SETUP.md
- **Path**: `doc-project/MCP_SERENA_SETUP.md`
- **Linhas**: ~50
- **Tipo**: Configuração
- **Tags**: `[MCP]` `[SERENA]` `[SETUP]` `[CONFIG]`
- **Propósito**: Configuração do servidor MCP Serena
- **Uso Principal**: DevOps, Architect
- **Status**: ✅ Ativo

---

### 13. OPCACHE_SETUP.md
- **Path**: `doc-project/OPCACHE_SETUP.md`
- **Linhas**: ~80
- **Tipo**: Configuração
- **Tags**: `[OPCACHE]` `[PHP]` `[CONFIG]` `[PERFORMANCE]`
- **Propósito**: Configuração de OPcache
- **Uso Principal**: DevOps
- **Status**: ✅ Ativo

---

### 14. SERVICES_API.md
- **Path**: `doc-project/SERVICES_API.md`
- **Linhas**: ~1400+
- **Tipo**: Documentação de API
- **Tags**: `[API]` `[SERVICES]` `[BUSINESS]` `[CRITICAL]`
- **Propósito**: Documentação completa dos 17 services do sistema
- **Uso Principal**: Backend, QA, Architect
- **Status**: ✅ Ativo e Crítico

**Services Documentados:**
1. AuditService
2. ArtistFinancialsService
3. BookerFinancialsService
4. CashFlowProjectionService
5. DreProjectionService
6. ExchangeRateService
7. FinancialProjectionService
8. FinancialReportService
9. GigFinancialCalculatorService
10. SettlementService
11. NotificationService
12. CurrencyConversionService
13. CommissionPaymentValidationService
14. GigAuditCommandService
15. (mais 2 services...)

**Estrutura por service:**
- Descrição
- Dependências
- Métodos públicos
- Parâmetros e retornos
- Exemplos de uso

---

### 15. SETUP_GUIDE.md
- **Path**: `doc-project/SETUP_GUIDE.md`
- **Linhas**: ~200
- **Tipo**: Documentação de Instalação
- **Tags**: `[SETUP]` `[INSTALL]` `[GUIDE]` `[ONBOARDING]`
- **Propósito**: Guia de configuração do ambiente de desenvolvimento
- **Uso Principal**: DevOps, novos desenvolvedores
- **Status**: ✅ Ativo

---

### 16. SOURCE_OF_TRUTH.md
- **Path**: `doc-project/SOURCE_OF_TRUTH.md`
- **Linhas**: 52
- **Tipo**: Metadados / Fonte da Verdade
- **Tags**: `[TRUTH]` `[SOURCE]` `[META]` `[REFERENCE]`
- **Propósito**: Fonte única de verdade sobre o sistema
- **Uso Principal**: Todos os agentes
- **Status**: ✅ Ativo e Essencial

**Seções:**
- Dados (modelos, tabelas, seeders)
- Regras de Negócio (services)
- Invariantes/Eventos (observers, policies)
- Interface (Filament resources, rotas)
- Configuração
- Changelog

---

### 17. TECHNICAL_OVERVIEW.md
- **Path**: `doc-project/TECHNICAL_OVERVIEW.md`
- **Linhas**: ~160
- **Tipo**: Documentação Técnica
- **Tags**: `[TECHNICAL]` `[ARCHITECTURE]` `[OVERVIEW]` `[STACK]`
- **Propósito**: Visão geral técnica do sistema
- **Uso Principal**: Architect, Legacy Analyzer
- **Status**: ✅ Ativo

**Seções:**
- Stack tecnológica
- Arquitetura
- Componentes principais
- Padrões de projeto

---

### 18. TESTING.md
- **Path**: `doc-project/TESTING.md`
- **Linhas**: ~100
- **Tipo**: Documentação de Teste
- **Tags**: `[TESTING]` `[TESTS]` `[QA]` `[VALIDATION]`
- **Propósito**: Guia de escrita e execução de testes
- **Uso Principal**: QA, Backend, Frontend
- **Status**: ✅ Ativo

**Categorias:**
- Testes de unidade
- Testes de integração
- Testes de feature
- Coverage requirements (80%)

---

### 19. TESTING_BEST_PRACTICES.md
- **Path**: `doc-project/TESTING_BEST_PRACTICES.md`
- **Linhas**: ~250
- **Tipo**: Guidelines
- **Tags**: `[TESTING]` `[BEST_PRACTICES]` `[GUIDELINES]`
- **Propósito**: Melhores práticas de teste
- **Uso Principal**: QA, Code reviewer
- **Status**: ✅ Ativo

---

---

## 📁 Subdiretorio: `doc-project/ai_context/`

### AI. 1_context.md
- **Path**: `doc-project/ai_context/1_context.md`
- **Linhas**: 84
- **Tipo**: Contexto de Projeto
- **Tags**: `[DOMAIN]` `[BUSINESS]` `[CONTEXT]` `[REQUIREMENTS]`
- **Propósito**: Contexto do projeto EventosPro (negócio)
- **Uso Principal**: Architect, Orchestrator
- **Status**: ✅ Ativo

**Seções:**
- Visão Geral
- Propósito Principal
- Requisitos Funcionais (5 módulos)
- Público-Alvo
- Benefícios
- Contexto Técnico

---

### AI. 2_architecture.md
- **Path**: `doc-project/ai_context/2_architecture.md`
- **Linhas**: 183
- **Tipo**: Documentação Técnica
- **Tags**: `[ARCHITECTURE]` `[DESIGN]` `[PATTERNS]` `[FLOW]`
- **Propósito**: Arquitetura detalhada do sistema
- **Uso Principal**: Architect, Backend, Frontend, Legacy Analyzer
- **Status**: ✅ Ativo

**Seções:**
- Arquitetura geral (MVC, 3 camadas)
- Camada de Dados (Models)
- Camada de Lógica de Negócio (Controllers, Services)
- Camada de Apresentação (Views)
- Fluxo de Dados
- Padrões de Projeto (6 patterns)
- Arquitetura de Dados
- Segurança e Autenticação
- Extensibilidade

---

### AI. 3_stack.md
- **Path**: `doc-project/ai_context/3_stack.md`
- **Linhas**: (a verificar)
- **Tipo**: Documentação Técnica
- **Tags**: `[STACK]` `[TECHNOLOGY]` `[DEPENDENCIES]`
- **Propósito**: Stack tecnológica do projeto
- **Uso Principal**: Todos os agentes
- **Status**: ✅ Ativo

---

### AI. 4_rules.md
- **Path**: `doc-project/ai_context/4_rules.md`
- **Linhas**: (a verificar)
- **Tipo**: Regras de Negócio
- **Tags**: `[RULES]` `[BUSINESS]` `[VALIDATION]`
- **Propósito**: Regras de negócio do sistema
- **Uso Principal**: Backend, QA, Code reviewer
- **Status**: ✅ Ativo

---

## 📁 Subdiretorio: `doc-project/devlog/`

### DL. index.md
- **Path**: `doc-project/devlog/index.md`
- **Linhas**: (a verificar)
- **Tipo**: Log de Desenvolvimento
- **Tags**: `[DEVLOG]` `[HISTORY]` `[CHANGELOG]` `[MEMORY]`
- **Propósito**: Histórico de desenvolvimento e mudanças
- **Uso Principal**: Orchestrator (memória persistente)
- **Status**: ✅ Ativo

---

## 📁 Subdiretorio: `doc-project/implementation-plans/`

### IP. 1. projections-artist-gig-local-column.md
- **Path**: `doc-project/implementation-plans/projections-artist-gig-local-column.md`
- **Linhas**: (a verificar)
- **Tipo**: Plano de Implementação
- **Tags**: `[PLAN]` `[IMPLEMENTATION]` `[FEATURE]`
- **Propósito**: Plano para feature de projeções
- **Uso Principal**: Architect, Backend
- **Status**: 🟡 Em implementação?

---

## 📁 Subdiretorio: `doc-project/nota_de_debito/`

### ND. 1. nota_de_debito.html
- **Path**: `doc-project/nota_de_debito/nota_de_debito.html`
- **Tipo**: Template HTML
- **Tags**: `[TEMPLATE]` `[HTML]` `[NOTA_DEBITO]`
- **Propósito**: Template para nota de débito
- **Uso Principal**: Frontend
- **Status**: ✅ Ativo

### ND. 2. csv/tomadores.csv
- **Path**: `doc-project/nota_de_debito/csv/tomadores.csv`
- **Tipo**: Dados CSV
- **Tags**: `[DATA]` `[CSV]` `[NOTA_DEBITO]`
- **Propósito**: Lista de tomadores para nota de débito
- **Uso Principal**: Backend (seed/data)
- **Status**: ✅ Ativo

### ND. 3. img/logo.png
- **Path**: `doc-project/nota_de_debito/img/logo.png`
- **Tipo**: Imagem
- **Propósito**: Logo para nota de débito
- **Uso Principal**: Frontend
- **Status**: ✅ Ativo

---

## 📊 Estatísticas Gerais

| Diretorio | Arquivos MD | Linhas Aprox |
|-----------|-------------|--------------|
| `doc-project/` | 20 | ~4500 |
| `doc-project/ai_context/` | 4 | ~300 |
| `doc-project/devlog/` | 1 | ? |
| `doc-project/implementation-plans/` | 1 | ? |
| **Total** | **26** | **~7349+** |

## 🏷️ Matriz de Tags

| Tag | Descrição | Agentes que Utilizam |
|-----|-----------|---------------------|
| `[META]` | Metadados, indices | Orchestrator, All |
| `[CONTEXT]` | Contexto de projeto | Orchestrator, Architect |
| `[TRUTH]` | Fonte da verdade | All |
| `[ARCHITECTURE]` | Arquitetura | Architect, Legacy Analyzer |
| `[DATABASE]` | Schema do banco | Architect, Backend |
| `[API]` | APIs e endpoints | Backend, Frontend, QA |
| `[SERVICES]` | Services da aplicação | Backend, QA |
| `[STANDARDS]` | Padrões de código | Code reviewer, All |
| `[TESTING]` | Testes e QA | QA, Code reviewer |
| `[DEPLOY]` | Deploy e operações | DevOps |
| `[LEGACY]` | Código/documento legado | Legacy Analyzer |
| `[BUSINESS]` | Regras de negócio | Backend, QA |
| `[COMMANDS]` | Comandos (Sail, etc) | DevOps, Backend |
| `[ROUTE]` | Rotas HTTP | Backend, Frontend |
| `[FINANCIAL]` | Lógica financeira | Backend, Auditoria |
| `[RULES]` | Regras de validacao | Backend, QA |
| `[OPS]` | Operações/Monitoring | DevOps |
| `[PATTERNS]` | Padrões de projeto | Architect, All |
| `[SECURITY]` | Segurança | Security Guardian |

---

## 🔄 Mapeamento para Atualização de Agents

### Orchestrator (.aidev/agents/orchestrator.md)
**Documentos a referenciar:**
- `SOURCE_OF_TRUTH.md` → .aidev/context/project-truth.md
- `AI_CONTEXT.md` → .aidev/context/index.md
- `ai_context/1_context.md` → .aidev/context/domain.md
- `devlog/index.md` → MCP memory points

**Atualização necessária:**
- Adicionar seção "Document Sources" com paths reais
- Mapear MCP memory queries

### Architect (.aidev/agents/architect.md)
**Documentos a referenciar:**
- `ai_context/2_architecture.md` → Arquitetura completa
- `TECHNICAL_OVERVIEW.md` → Stack overview
- `DATABASE.md` → Schema reference

**Atualização necessária:**
- Mapear serviços específicos do EventosPro
- Documentar EventosPro architecture patterns

### Backend (.aidev/agents/backend.md)
**Documentos a referenciar:**
- `SERVICES_API.md` → 17 services catalog
- `GIG_FINANCIAL_CALCULATOR_API.md` → Calculator patterns
- `ENDPOINTS.md` → Route catalog
- `LARAVEL_SAIL_COMMANDS.md` → Required commands
- `CODE_STANDARDS.md` → PHP 8.4 standards

**Atualização necessária:**
- Listar 17 services com responsabilidades
- Documentar Laravel 12 + Filament 4 patterns
- Service layer + Observer patterns

### Frontend (.aidev/agents/frontend.md)
**Documentos a referenciar:**
- `TECHNICAL_OVERVIEW.md` → Frontend stack
- `CODE_STANDARDS.md` → Frontend conventions
- `ENDPOINTS.md` → Filament routes

**Atualização necessária:**
- Filament v4 resource patterns
- Tailwind conventions
- Alpine.js usage

### Code Reviewer (.aidev/agents/code-reviewer.md)
**Documentos a referenciar:**
- `CODE_STANDARDS.md` → Standards checklist
- `TESTING_BEST_PRACTICES.md` → Test requirements
- `GIT_WORKFLOW.md` → Commit rules

**Atualização necessária:**
- Laravel 12 + Pint checklist
- 80% coverage requirement

### QA (.aidev/agents/qa.md)
**Documentos a referenciar:**
- `TESTING.md` → Test guide
- `TESTING_BEST_PRACTICES.md` → Guidelines
- `SERVICES_API.md` → API to test
- `ENDPOINTS.md` → Routes to test

**Atualização necessária:**
- PHPUnit patterns
- Factories/seeders available
- Coverage requirements

### Security Guardian (.aidev/agents/security-guardian.md)
**Documentos a referenciar:**
- `ai_context/2_architecture.md` → Security in architecture
- `SOURCE_OF_TRUTH.md` → Security configs

**Atualização necessária:**
- Breeze + Spatie Permission rules
- OWASP compliance

### DevOps (.aidev/agents/devops.md)
**Documentos a referenciar:**
- `LARAVEL_SAIL_COMMANDS.md` → All commands
- `DEPLOY_PROCEDURE.md` → Deploy process
- `DOCKER_TROUBLESHOOTING.md` → Troubleshooting
- `SETUP_GUIDE.md` → Environment setup

**Atualização necessária:**
- Laravel Sail mandatory commands
- Deploy checklist Laravel 12

### Legacy Analyzer (.aidev/agents/legacy-analyzer.md)
**Documentos a referenciar:**
- `LEGACY.md` → Archived docs
- `DATA_MODEL.md` → Potentially legacy
- All historical documentation

**Atualização necessária:**
- EventosPro legacy patterns
- Refactoring guide

---

**Versão**: 1.0
**Status**: Catalogo Completo
**Próximo Passo**: Criar arquivos de referencia em `.aidev/context/`