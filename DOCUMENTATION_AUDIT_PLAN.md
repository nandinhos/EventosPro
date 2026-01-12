# Auditoria e Reorganização Documental - EventosPro

> **Instruções para Claude Code CLI**: Execute este procedimento fase por fase, aguardando confirmação entre cada etapa.

## Sumário Executivo

Análise completa da documentação existente no projeto EventosPro. Foram encontrados **70+ arquivos de documentação** distribuídos em múltiplas pastas, com significativa redundância e conteúdo obsoleto.

---

## FASE 0: Auditoria da Documentação Existente

### 0.1 ✅ Inventário Completo (Já realizado)

#### Documentos na RAIZ do Projeto (11 arquivos)

| Arquivo | Linhas | Status | Classificação |
|---------|--------|--------|---------------|
| `README.md` | 150 | Deploy/Setup atualizado | **ATIVO** |
| `AGENTS.md` | 390 | Guidelines para IA, versões atualizadas | **ATIVO** |
| `GEMINI.md` | 400+ | Contexto do projeto para Gemini | **ATIVO** |
| `CLAUDE.md` | 475+ | Contexto do projeto para Claude | **ATIVO** |
| `TODO.md` | 957 | Tasks 100% concluídas (Nov 2025) | **LEGACY** |
| `DESIGN_SYSTEM.md` | 200+ | Sistema de design | **ATIVO** |
| `ANALISE_FECHAMENTO_MENSAL.md` | 200+ | Análise pontual concluída | **LEGACY** |
| `AUDITORIA_TECNICA.md` | 162 | Auditoria pontual | **LEGACY** |
| `DEVELOPER_LOGINS.md` | 85+ | Credenciais de desenvolvimento | **ATIVO** |
| `INSTRUCOES_MCP_LARAVEL_BOOST.md` | 51 | Setup MCP | **ATIVO** |
| `README_AGENTS.md` | 150+ | Duplica conteúdo de AGENTS.md | **LEGACY** - consolidar |

#### Pasta `docs/` - Documentos ATIVOS (Manter)

| Arquivo | Razão para manter |
|---------|-------------------|
| `TECHNICAL_OVERVIEW.md` | Visão técnica completa |
| `DATABASE.md` | Documentação DB atualizada |
| `SERVICES_API.md` | API dos services (completo) |
| `ENDPOINTS.md` | Referência de rotas |
| `SETUP_GUIDE.md` | Guia de configuração |
| `DEPLOY_PROCEDURE.md` | Procedimento de deploy |
| `GIT_WORKFLOW.md` | Fluxo Git |
| `CODE_STANDARDS.md` | Padrões de código |
| `TESTING.md` | Guia de testes |
| `TESTING_BEST_PRACTICES.md` | Boas práticas |
| `LARAVEL_SAIL_COMMANDS.md` | Comandos Sail |
| `MCP_SERENA_SETUP.md` | Setup Serena |
| `OPCACHE_SETUP.md` | Setup OPcache |
| `GIG_FINANCIAL_CALCULATOR_API.md` | API do calculator |

#### Pasta `docs/` - Documentos LEGACY (Mover)

| Arquivo | Razão para mover |
|---------|------------------|
| `LESSONS_LEARNED.md` | Histórico de lições (1510 linhas) |
| `LESSONS_LEARNED_BACKUP_SYSTEM.md` | Específico de backup |
| `AUDIT_SYSTEM_ANALYSIS.md` | Análise pontual concluída |
| `AUDIT_SYSTEM_EXPANSION.md` | Plano já implementado |
| `AUDIT_OPTIMIZATION_REPORT.md` | Relatório concluído |
| `AUDIT_TEST_SEEDER.md` | Implementação já feita |
| `PERFORMANCE_OPTIMIZATION.md` | Otimização já aplicada |
| `PERFORMANCE_OPTIMIZATION_REPORT.md` | Relatório concluído |
| `PROJECTION_IMPROVEMENTS.md` | Melhorias já implementadas |
| `PROJECTION_REFACTORING.md` | Refatoração já feita |
| `FINANCIAL_DASHBOARD_ANALYSIS.md` | Análise pontual |
| `BACKUP_IMPLEMENTATION_PLAN.md` | Plano já executado |
| `OPTIMIZATION_TASKS.md` | Tasks já concluídas |
| `MEU_FLUXO_DEPLOY.md` | Fluxo pessoal/obsoleto |
| `OPERATIONS.md` | Redundante com SETUP_GUIDE |
| `TASKS.md` | Tasks antigas |
| `AGENT_PROJECTION.md` | Específico de agente |
| `AGENT_SYSTEM.md` | Específico de agente |
| `AI_AGENT_INSTRUCTIONS.md` | Duplicado |

#### Subpasta `docs/ai_context/` 

| Arquivo | Classificação |
|---------|---------------|
| `1_context.md` | **ATIVO** |
| `2_architecture.md` | **ATIVO** |
| `3_stack.md` | **ATIVO** |
| `4_rules.md` | **ATIVO** |
| `model-relationships.md` | **ATIVO** |
| `gig-data-audit-command-usage.md` | **ATIVO** |
| `plano-implementacao-tasks.md` | **LEGACY** |
| `relatorio-mapeamento-sistema.md` | **LEGACY** |

#### Subpasta `docs/devlog/`

| Arquivo | Classificação |
|---------|---------------|
| `index.md` | **ATIVO** - manter atualizado |
| `2025-*.md` (7 arquivos) | **LEGACY** |

#### Pasta `Docs/` (com D maiúsculo)

| Arquivo | Classificação |
|---------|---------------|
| `DOCKER_TROUBLESHOOTING.md` | **ATIVO** - mover para docs/ |
| `tutorial_front_inicial.md` | **LEGACY** |
| `modelo_show_payments.blade.php` | **LEGACY** |
| `contexto/` (toda a pasta) | **LEGACY** |

---

## FASE 0.5: Executar Reorganização

### Passo 1: Criar estrutura legacy
```bash
mkdir -p legacy/docs/ai_context
mkdir -p legacy/docs/devlog
mkdir -p legacy/docs/archive
mkdir -p legacy/Docs/contexto
```

### Passo 2: Mover arquivos da raiz para legacy
```bash
mv TODO.md legacy/
mv ANALISE_FECHAMENTO_MENSAL.md legacy/
mv AUDITORIA_TECNICA.md legacy/
mv README_AGENTS.md legacy/
```

### Passo 3: Mover arquivos de docs/ para legacy/docs/
```bash
# LESSONS LEARNED
mv docs/LESSONS_LEARNED.md legacy/docs/
mv docs/LESSONS_LEARNED_BACKUP_SYSTEM.md legacy/docs/

# AUDIT
mv docs/AUDIT_SYSTEM_ANALYSIS.md legacy/docs/
mv docs/AUDIT_SYSTEM_EXPANSION.md legacy/docs/
mv docs/AUDIT_OPTIMIZATION_REPORT.md legacy/docs/
mv docs/AUDIT_TEST_SEEDER.md legacy/docs/

# PERFORMANCE
mv docs/PERFORMANCE_OPTIMIZATION.md legacy/docs/
mv docs/PERFORMANCE_OPTIMIZATION_REPORT.md legacy/docs/

# PROJECTION
mv docs/PROJECTION_IMPROVEMENTS.md legacy/docs/
mv docs/PROJECTION_REFACTORING.md legacy/docs/

# OUTROS
mv docs/FINANCIAL_DASHBOARD_ANALYSIS.md legacy/docs/
mv docs/BACKUP_IMPLEMENTATION_PLAN.md legacy/docs/
mv docs/OPTIMIZATION_TASKS.md legacy/docs/
mv docs/MEU_FLUXO_DEPLOY.md legacy/docs/
mv docs/OPERATIONS.md legacy/docs/
mv docs/TASKS.md legacy/docs/
mv docs/AGENT_PROJECTION.md legacy/docs/
mv docs/AGENT_SYSTEM.md legacy/docs/
mv docs/AI_AGENT_INSTRUCTIONS.md legacy/docs/
```

### Passo 4: Mover arquivos de docs/ai_context/ para legacy
```bash
mv docs/ai_context/plano-implementacao-tasks.md legacy/docs/ai_context/
mv docs/ai_context/relatorio-mapeamento-sistema.md legacy/docs/ai_context/
```

### Passo 5: Mover logs de docs/devlog/ para legacy
```bash
mv docs/devlog/2025-01-21-test-fixes.md legacy/docs/devlog/
mv docs/devlog/2025-08-20-initial-analysis.md legacy/docs/devlog/
mv docs/devlog/2025-08-25-inconsistency-analysis.md legacy/docs/devlog/
mv docs/devlog/2025-08-25-task-tests.md legacy/docs/devlog/
mv docs/devlog/2025-08-30-architectural-analysis.md legacy/docs/devlog/
mv docs/devlog/2025-08-30-task-service-providers.md legacy/docs/devlog/
mv docs/devlog/2025-09-27-gig-audit-command-fix.md legacy/docs/devlog/
```

### Passo 6: Mover docs/archive/ (já é legacy)
```bash
mv docs/archive/* legacy/docs/archive/
rmdir docs/archive
```

### Passo 7: Mover pasta Docs/ para legacy
```bash
mv Docs/tutorial_front_inicial.md legacy/Docs/
mv Docs/modelo_show_payments.blade.php legacy/Docs/
mv Docs/contexto legacy/Docs/

# Mover arquivo útil para docs/
mv Docs/DOCKER_TROUBLESHOOTING.md docs/

# Remover pasta vazia
rmdir Docs
```

### Passo 8: Criar MIGRATION_LOG.md
```bash
cat > legacy/MIGRATION_LOG.md << 'EOF'
# Log de Migração de Documentação

**Data**: 2026-01-12
**Executado por**: Claude/Gemini Agent

## Arquivos Movidos para legacy/

### Da raiz do projeto
| Arquivo | Razão |
|---------|-------|
| TODO.md | Tasks 100% concluídas |
| ANALISE_FECHAMENTO_MENSAL.md | Análise pontual concluída |
| AUDITORIA_TECNICA.md | Auditoria pontual |
| README_AGENTS.md | Conteúdo duplicado em AGENTS.md |

### De docs/ para legacy/docs/
- 19 arquivos de análises, relatórios e planos já implementados
- Ver lista completa em DOCUMENTATION_AUDIT_PLAN.md

### De docs/ai_context/ para legacy/docs/ai_context/
- plano-implementacao-tasks.md
- relatorio-mapeamento-sistema.md

### De docs/devlog/ para legacy/docs/devlog/
- 7 arquivos de log datados de 2025

### De Docs/ para legacy/Docs/
- tutorial_front_inicial.md
- modelo_show_payments.blade.php
- contexto/ (toda a pasta)

## Arquivos Mantidos/Movidos para docs/
- DOCKER_TROUBLESHOOTING.md (de Docs/ para docs/)

## Critérios de Classificação
- **ATIVO**: Reflete estado atual, útil para desenvolvimento
- **LEGACY**: Planos já executados, análises pontuais, relatórios concluídos
EOF
```

---

## FASE 1-2: Varredura do Código (Após Reorganização)

### 1.1 Usar Serena MCP para mapeamento
```
mcp_serena_activate_project: /home/nandodev/projects/EventosPro
mcp_serena_list_dir: "." recursive=true
mcp_serena_get_symbols_overview: arquivos chave
```

### 2.1 Usar Laravel Boost para análise
```
mcp_laravel-boost_application-info
mcp_laravel-boost_database-schema
mcp_laravel-boost_list-routes
```

---

## FASE 3: Atualizar Documentos Mestres

### 3.1 Expandir SOURCE_OF_TRUTH.md
- Adicionar versão e data
- Expandir links para documentação
- Adicionar changelog

### 3.2 Atualizar AI_CONTEXT.md
- Consolidar informações de ai_context/*.md
- Manter conciso

### 3.3 Atualizar LEGACY.md
- Listar todos arquivos movidos
- Adicionar referências

---

## Estrutura Final Esperada

```
EventosPro/
├── README.md
├── AGENTS.md
├── CLAUDE.md
├── GEMINI.md
├── DESIGN_SYSTEM.md
├── DEVELOPER_LOGINS.md
├── INSTRUCOES_MCP_LARAVEL_BOOST.md
│
├── docs/                      # DOCUMENTAÇÃO ATIVA
│   ├── SOURCE_OF_TRUTH.md
│   ├── TECHNICAL_OVERVIEW.md
│   ├── DATABASE.md
│   ├── SERVICES_API.md
│   ├── ENDPOINTS.md
│   ├── SETUP_GUIDE.md
│   ├── DEPLOY_PROCEDURE.md
│   ├── GIT_WORKFLOW.md
│   ├── CODE_STANDARDS.md
│   ├── TESTING.md
│   ├── TESTING_BEST_PRACTICES.md
│   ├── LARAVEL_SAIL_COMMANDS.md
│   ├── DOCKER_TROUBLESHOOTING.md
│   ├── MCP_SERENA_SETUP.md
│   ├── OPCACHE_SETUP.md
│   ├── GIG_FINANCIAL_CALCULATOR_API.md
│   ├── LEGACY.md
│   ├── ai_context/
│   │   ├── 1_context.md
│   │   ├── 2_architecture.md
│   │   ├── 3_stack.md
│   │   ├── 4_rules.md
│   │   ├── model-relationships.md
│   │   └── gig-data-audit-command-usage.md
│   ├── devlog/
│   │   └── index.md
│   ├── implementation-plans/
│   └── nota_de_debito/
│
└── legacy/                    # DOCUMENTAÇÃO OBSOLETA
    ├── MIGRATION_LOG.md
    ├── TODO.md
    ├── ANALISE_FECHAMENTO_MENSAL.md
    ├── AUDITORIA_TECNICA.md
    ├── README_AGENTS.md
    ├── docs/
    │   ├── (19 arquivos movidos)
    │   ├── ai_context/
    │   ├── devlog/
    │   └── archive/
    └── Docs/
        └── contexto/
```
