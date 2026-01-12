# Documentação Legacy — EventosPro

> **Atualizado**: 2026-01-12 | Ver `legacy/MIGRATION_LOG.md` para detalhes completos

## Estrutura Legacy

Toda documentação obsoleta foi movida para a pasta `legacy/` na raiz do projeto:

```
legacy/
├── MIGRATION_LOG.md          # Log detalhado da migração
├── TODO.md                   # Tasks 100% concluídas
├── ANALISE_FECHAMENTO_MENSAL.md
├── AUDITORIA_TECNICA.md
├── README_AGENTS.md
├── docs/
│   ├── LESSONS_LEARNED.md
│   ├── AUDIT_*.md            # Relatórios de auditoria
│   ├── PERFORMANCE_*.md      # Relatórios de performance
│   ├── PROJECTION_*.md       # Planos de projeção
│   ├── ai_context/           # Planos já implementados
│   ├── devlog/               # Logs de 2025
│   └── archive/
│       ├── 2025-10-optimization/
│       └── gemini/
└── Docs/
    └── contexto/             # Contexto antigo do projeto
```

## Por que manter?

- **Referência histórica**: Decisões arquitetônicas passadas
- **Lições aprendidas**: Erros e soluções anteriores
- **Auditoria**: Trail de mudanças no sistema

## Arquivos de Backup na Raiz

- `tailwind.config.js.v3.backup` — Config Tailwind v3 anterior

## Documentação Ativa

A documentação ativa está em `docs/`:

| Arquivo | Propósito |
|---------|-----------|
| `SOURCE_OF_TRUTH.md` | Fonte única da verdade |
| `TECHNICAL_OVERVIEW.md` | Visão técnica completa |
| `DATABASE.md` | Schema e relacionamentos |
| `SERVICES_API.md` | API dos services |
| `TESTING.md` | Guia de testes |
| `DEPLOY_PROCEDURE.md` | Procedimento de deploy |

### Contexto de IA

- `docs/ai_context/1_context.md` - Contexto do negócio
- `docs/ai_context/2_architecture.md` - Arquitetura do sistema
- `docs/ai_context/3_stack.md` - Stack tecnológico
- `docs/ai_context/4_rules.md` - Regras de desenvolvimento
- `docs/ai_context/model-relationships.md` - Relacionamentos
- `docs/ai_context/gig-data-audit-command-usage.md` - Comando de auditoria
