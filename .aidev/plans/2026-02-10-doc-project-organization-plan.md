# Plano de Organização da Documentação EventosPro para AI Dev Superpowers

**Data**: 2026-02-10
**Sprint Atual**: 4
**Objetivo**: Levantamento e organização da documentação do sistema `doc-project` para integrar com o ecossistema AI Dev Superpowers

---

## 📋 Declaracão do Problema

O projeto EventosPro possui um acervo documental extenso na pasta `doc-project/` (7349+ linhas em arquivos MD) que precisa ser organizado e mapeado para funcionar perfeitamente com o sistema de agentes **AI Dev Superpowers**. Atualmente, a documentação existe em uma estrutura que precisa ser categorizada e integrada com os arquivos de configuração do aidev (`.aidev/`).

### Objetivos Específicos
1. **Identificar** todos os arquivos de documentação em `doc-project/`
2. **Categorizar** cada documento por tipo e proposito
3. **Mapear** quais documentos alimentam quais agentes/skills do aidev
4. **Definir** estrutura de memória (MCP) para contexto persistente
5. **Criar** plano de migracao para integrar com o ecossistema

---

## 📊 Inventario Preliminar da Documentação

### Estrutura Atual de `doc-project/`

```
doc-project/
├── Arquivos Raiz (20 arquivos MD)
│   ├── AI_CONTEXT.md              - Indice de contexto para IA
│   ├── CODE_STANDARDS.md          - Padrões de código
│   ├── DATABASE.md                - Schema completo do banco
│   ├── DATA_MODEL.md              - Modelo de dados
│   ├── DEPLOY_PROCEDURE.md        - Procedimentos de deploy
│   ├── DOCKER_TROUBLESHOOTING.md  - Resolução de problemas Docker
│   ├── ENDPOINTS.md               - Catalogo de rotas (174 rotas)
│   ├── GIG_FINANCIAL_CALCULATOR_API.md - API do calculadora financeira
│   ├── GIT_WORKFLOW.md            - Fluxo de trabalho Git
│   ├── LARAVEL_SAIL_COMMANDS.md   - Comandos Sail
│   ├── LEGACY.md                  - Documentacao arquivada
│   ├── MCP_SERENA_SETUP.md        - Configuracao MCP Serena
│   ├── OPCACHE_SETUP.md           - Configuracao OPcache
│   ├── SERVICES_API.md            - API dos 17 services
│   ├── SETUP_GUIDE.md             - Guia de setup
│   ├── SOURCE_OF_TRUTH.md         - Fonte da verdade do sistema
│   ├── TECHNICAL_OVERVIEW.md      - Visao técnica
│   ├── TESTING.md                 - Guia de testes
│   └── TESTING_BEST_PRACTICES.md  - Melhores práticas de teste
│
├── ai_context/ (4 arquivos)
│   ├── 1_context.md         - Contexto do projeto (negócio)
│   ├── 2_architecture.md    - Arquitetura do sistema
│   ├── 3_stack.md           - Stack tecnológica
│   └── 4_rules.md           - Regras de negocio
│
├── devlog/ (1 arquivo)
│   └── index.md             - Log de desenvolvimento
│
├── implementation-plans/ (1 plano)
│   └── projections-artist-gig-local-column.md
│
└── nota_de_debito/ (feature específica)
    ├── nota_de_debito.html
    ├── csv/tomadores.csv
    └── img/logo.png
```

---

## 🗂️ Categorização Proposta

### Categoria A: Contexto de Projeto (para `.aidev/context/`)
A alimenta o knowledge base dos agentes

| Arquivo Fonte | Categoria Prioritaria | Agentes/Skills que Utilizam |
|---------------|----------------------|-----------------------------|
| `SOURCE_OF_TRUTH.md` | Fonte da verdade | ALL |
| `ai_context/1_context.md` | Domain knowledge | Architect, orchestrator |
| `ai_context/2_architecture.md` | Arquitetura | Architect, backend, frontend |
| `ai_context/3_stack.md` | Stack tecnológica | Todos |
| `ai_context/4_rules.md` | Regras de negocio | Backend, QA |
| `TECHNICAL_OVERVIEW.md` | Visao técnica | Architect, legacy-analyzer |

### Categoria B: Padrões e Convenções (para `.aidev/rules/`)
A define como o código deve ser escrito

| Arquivo Fonte | Categoria Prioritaria | Agentes/Skills que Utilizam |
|---------------|----------------------|-----------------------------|
| `CODE_STANDARDS.md` | Padrões de código | Code-reviewer, backend, frontend |
| `TESTING.md` | Diretrizes de testes | QA, backend, frontend |
| `TESTING_BEST_PRACTICES.md` | Melhores práticas test | QA |
| `GIT_WORKFLOW.md` | Workflow Git | DevOps, todos |
| `LARAVEL_SAIL_COMMANDS.md` | Comandos padrão | DevOps, backend |

### Categoria C: API e Serviços (para `.aidev/context/api/`)
Reference material para implementação

| Arquivo Fonte | Categoria Prioritaria | Agentes/Skills que Utilizam |
|---------------|----------------------|-----------------------------|
| `SERVICES_API.md` | API dos 17 services | Backend, QA |
| `GIG_FINANCIAL_CALCULATOR_API.md` | Calculadora financeira | Backend, QA, auditoria |
| `ENDPOINTS.md` | Catalogo de 174 rotas | Backend, Frontend, QA |
| `DATABASE.md` | Schema completo | Backend, Architect |

### Categoria D: Operações e Infraestrutura (para `.aidev/context/ops/`)
Informações para deploy e manutenção

| Arquivo Fonte | Categoria Prioritaria | Agentes/Skills que Utilizam |
|---------------|----------------------|-----------------------------|
| `DEPLOY_PROCEDURE.md` | Procedimentos deploy | DevOps |
| `DOCKER_TROUBLESHOOTING.md` | Troubleshooting Docker | DevOps |
| `OPCACHE_SETUP.md` | Configuracao OPcache | DevOps |
| `MCP_SERENA_SETUP.md` | Configuracao MCP | DevOps, architect |
| `SETUP_GUIDE.md` | Guia de setup | DevOps, novos membros |

### Categoria E: Documentação Arquivada (para `.aidev/legacy/`)

| Arquivo Fonte | Categoria Prioritaria | Agentes/Skills que Utilizam |
|---------------|----------------------|-----------------------------|
| `LEGACY.md` | Historico arquivado | Legacy-analyzer |
| `DATA_MODEL.md` | Possivelmente superado | Legacy-analyzer |

### Categoria F: DevLogs e Planos (para `.aidev/plans/`)

| Arquivo Fonte | Categoria Prioritaria | Agentes/Skills que Utilizam |
|---------------|----------------------|-----------------------------|
| `devlog/index.md` | Historico de dev | Orchestrator (memory) |
| `implementation-plans/*` | Planos de implementacao | Architect, backend |


---

## 🤖 Mapeamento para Agentes AI Dev

### Agente: Orchestrator
**Documentação Necessária:**
- `SOURCE_OF_TRUTH.md` → Estado atual do sistema
- `ai_context/1_context.md` → Entendimento do domínio
- `doc-project/AI_CONTEXT.md` → Indice de contexto
- `devlog/index.md` → Histórico de desenvolvimento

**Atualização necessária:** `.aidev/agents/orchestrator.md`
- Adicionar referências específicas aos arquivos de projeto
- Definir pontos de memoria para sessões futuras

### Agente: Architect
**Documentação Necessária:**
- `ai_context/2_architecture.md` → Arquitetura completa
- `ai_context/3_stack.md` → Stack tecnológica
- `DATABASE.md` → Modelo de dados
- `TECHNICAL_OVERVIEW.md` → Visão técnica

**Atualização necessária:** `.aidev/agents/architect.md`
- Mapear serviços e componentes principais
- Documentar padrões arquiteturais específicos do EventosPro

### Agente: Backend (Laravel)
**Documentação Necessária:**
- `SERVICES_API.md` → API dos 17 services
- `GIG_FINANCIAL_CALCULATOR_API.md` → Calculadora financeira
- `CODE_STANDARDS.md` → Padrões PHP/Laravel
- `ENDPOINTS.md` → Rotas disponíveis
- `ai_context/4_rules.md` → Regras de negócio

**Atualização necessária:** `.aidev/agents/backend.md`
- Lista dos 17 services e suas responsabilidades
- Padrões específicos de Laravel 12 + Filament 4
- Service layer pattern
- Observer pattern implementations

### Agente: Frontend (Filament/Tailwind)
**Documentação Necessária:**
- `CODE_STANDARDS.md` → Padrões frontend
- `TECHNICAL_OVERVIEW.md` → Stack frontend
- `ENDPOINTS.md` → Rotas Filament (174 rotas)

**Atualização necessária:** `.aidev/agents/frontend.md`
- Filament v4 patterns
- Tailwind conventions do projeto
- Alpine.js usage patterns

### Agente: Code Reviewer
**Documentação Necessária:**
- `CODE_STANDARDS.md` → Padrões principais
- `TESTING_BEST_PRACTICES.md` → Práticas de teste
- `GIT_WORKFLOW.md` → Commit rules (já em generic.md)

**Atualização necessária:** `.aidev/agents/code-reviewer.md`
- Checklist específico Laravel 12
- Requisitos de Pint
- Cobertura de testes (80% min)

### Agente: QA
**Documentação Necessária:**
- `TESTING.md` → Guia de testes
- `TESTING_BEST_PRACTICES.md` → Melhores práticas
- `SERVICES_API.md` → API para testar
- `ENDPOINTS.md` → Endpoints para testar

**Atualização necessária:** `.aidev/agents/qa.md`
- Padrões PHPUnit para Laravel
- Factories e seeders disponíveis
- Coverage requirements

### Agente: Security Guardian
**Documentação Necessária:**
- `ai_context/2_architecture.md` → Segurança na arquitetura
- `SOURCE_OF_TRUTH.md` → Configurações de segurança

**Atualização necessária:** `.aidev/agents/security-guardian.md`
- Breeze + Spatie Permission rules
- OWASP compliance checklist
- Validação de formulários

### Agente: DevOps
**Documentação Necessária:**
- `LARAVEL_SAIL_COMMANDS.md` → Comandos Sail
- `DEPLOY_PROCEDURE.md` → Deploy
- `DOCKER_TROUBLESHOOTING.md` → Docker
- `SETUP_GUIDE.md` → Setup

**Atualização necessária:** `.aidev/agents/devops.md`
- Checklist de deploy Laravel 12
- Commands via Sail obrigatórios
- Environment configuration

### Agente: Legacy Analyzer
**Documentação Necessária:**
- `LEGACY.md` → Documentação arquivada
- `DATA_MODEL.md` → Possivelmente legado
- Toda a documentação histórica

**Atualização necessária:** `.aidev/agents/legacy-analyzer.md`
- Identificar padrões legados
- Guia de refatoração

---

## 💾 Estrutura de Memória (MCP) Proposta

### 1. Basic Memory (Contexto do Projeto)
**Arquivos para indexar:**
```json
{
  "project_context": [
    "doc-project/SOURCE_OF_TRUTH.md",
    "doc-project/ai_context/1_context.md",
    "doc-project/TECHNICAL_OVERVIEW.md"
  ],
  "architecture": [
    "doc-project/ai_context/2_architecture.md",
    "doc-project/ai_context/3_stack.md"
  ],
  "business_rules": [
    "doc-project/ai_context/4_rules.md"
  ]
}
```

### 2. Serena (Busca Semântica no Código)
**Configuração existente:** `MCP_SERENA_SETUP.md`
**Índices necessários:**
- `app/Services/*` → Busca de métodos de service
- `app/Models/*` → Busca de modelos
- `routes/*` → Busca de rotas
- `app/Filament/*` → Busca de recursos Filament

### 3. Context7 (Documentação Externa)
**Package references:**
- Laravel 12
- Filament 4
- PHP 8.4

---

## 📝 Plano de Migracao

### Fase 1: Analise e Catalogacao (Atual)
- [x] Listar todos os arquivos em `doc-project/`
- [x] Categorizar por tipo e propósito
- [ ] Catalogar cada arquivo com tags de contexto
- [ ] Mapear para agentes/skills correspondentes

### Fase 2: Criacao de Arquivos de Referencia
- [ ] Criar `.aidev/context/project-summary.md` (resumo condensado)
- [ ] Criar `.aidev/context/services-catalog.md` (17 services)
- [ ] Criar `.aidev/context/routes-catalog.md` (174 rotas agrupadas)
- [ ] Criar `.aidev/context/architecture-contracts.md` (pattern definitions)

### Fase 3: Atualizacao de Agents
- [ ] Atualizar `.aidev/agents/orchestrator.md`
  - Adicionar referencias a `doc-project/`
  - Definir pontos de memoria MCP
- [ ] Atualizar `.aidev/agents/architect.md`
  - Mapear arquitetura EventosPro
- [ ] Atualizar `.aidev/agents/backend.md`
  - Detalhar Laravel 12 + Filament 4
  - Mapear 17 services
- [ ] Atualizar `.aidev/agents/frontend.md`
  - Filament patterns
- [ ] Atualizar `.aidev/agents/code-reviewer.md`
  - Checklist EventosPro específico
- [ ] Atualizar `.aidev/agents/qa.md`
  - Test patterns Laravel
- [ ] Atualizar `.aidev/agents/security-guardian.md`
  - Breeze + Spatie Permission
- [ ] Atualizar `.aidev/agents/devops.md`
  - Sail commands obrigatorios
- [ ] Atualizar `.aidev/agents/legacy-analyzer.md`
  - Padrões legados EventosPro

### Fase 4: Criacao de Skills Especificas
- [ ] Adaptar `brainstorming` skill para EventosPro
- [ ] Adaptar `writing-plans` skill para Laravel features
- [ ] Adaptar `test-driven-development` skill para Laravel/PHPUnit
- [ ] Adaptar `code-review` skill para Laravel Pint
- [ ] Adaptar `systematic-debugging` skill para Laravel
- [ ] Adaptar `learned-lesson` skill para EventosPro patterns

### Fase 5: Integracao com MCP
- [ ] Configurar Basic Memory com documentos-chave
- [ ] Verificar configuracao Serena
- [ ] Configurar Context7 para Laravel 12 + Filament 4

### Fase 6: Validacao
- [ ] Testar agents com documentacao integrada
- [ ] Verificar memoria persistente entre sessoes
- [ ] Validar buscas semânticas Serena

---

## 🎯 Artefatos a Serem Criados

### 1. `.aidev/context/project-overview.md`
Resumo executivo condensado para sessões do Orchestrator

### 2. `.aidev/context/services-manifest.md`
Catalogo dos 17 services:
- Nome do service
- Responsabilidade principal
- Métodos públicos
- Dependências
- Exemplos de uso

### 3. `.aidev/context/routes-manifest.md`
Catalogo de rotas agrupado:
- Gigs (CRUD, financeiro, auditoria)
- Reports (vencimentos, DRE, cashflow)
- Artists/Bookers ( gestão)
- Admin (users, permissions)

### 4. `.aidev/context/testing-patterns.md`
Patterns de testes:
- Factories disponíveis
- Seeders para dados base
- Mocks comuns
- Coverage requirements (80%)

### 5. `.aidev/rules/laravel-filament.md`
Regras específicas Laravel 12 + Filament 4:
- Service layer obrigatorio
- Observer pattern
- Pint antes de commits
- Sail para todos os comandos

---

## 📊 Matriz de Impacto

| Agente | Documento Impactante | Prioridade Alteracao |
|--------|---------------------|---------------------|
| All | `SOURCE_OF_TRUTH.md` | Alta |
| Architect | `ai_context/2_architecture.md` | Alta |
| Backend | `SERVICES_API.md` | Critica |
| Backend | `GIG_FINANCIAL_CALCULATOR_API.md` | Critica |
| Code Reviewer | `CODE_STANDARDS.md` | Alta |
| QA | `TESTING.md` | Alta |
| DevOps | `LARAVEL_SAIL_COMMANDS.md` | Alta |
| DevOps | `DEPLOY_PROCEDURE.md` | Media |

---

## 🔄 Proximos Passos

1. **Criar catalogo detalhado** de cada arquivo `doc-project/` com tags de contexto
2. **Definir estrutura** `.aidev/context/` para arquivos de reference
3. **Criar artefatos condensados** (project-overview, services-manifest, etc)
4. **Atualizar cada agent** com referencias aos documentos apropriados
5. **Configurar MCP memory** para persistencia de contexto
6. **Testar integracao** com os agentes

---

## ⚠️ Riscos e Mitigacoes

| Risco | Probabilidade | Impacto | Mitigacao |
|-------|---------------|---------|-----------|
| Documentacao obsoleta | Media | Media | Criar processo de revisao periodica |
| Tokens excessivos | Alta | Alta | Criar resumos condensados |
| Dependencias circulares | Baixa | Media | Mapeamento claro de responsabilidades |
| Duplicacao de informacao | Alta | Baixa | Criar "source of truth" unico |

---

**Versão**: 1.0
**Status**: Em analise
**Próximo artefato**: Catalogo detalhado dos arquivos `doc-project/`