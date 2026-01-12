# Fonte da Verdade — EventosPro

> **Versão**: 2.0 | **Atualizado**: 2026-01-12 | **Stack**: Laravel 12.43.1 + PHP 8.4.14 + Filament 4.1.3

## Dados

- Modelos Eloquent em `app/Models/**` refletem o schema definido por `database/migrations/**`.
- **15 modelos**: Gig, Artist, Booker, Payment, Settlement, GigCost, CostCenter, DebitNote, ServiceTaker, User, etc.
- **27 tabelas** no banco MySQL com índices otimizados e foreign keys.
- Seeders (`database/seeders/**`) estabelecem papéis/permissões e dados base.

## Regras de Negócio

- **17 Services** em `app/Services/**` centralizam cálculos financeiros, projeções, relatórios e validações.
- Core: `GigFinancialCalculatorService.php` (11 métodos de cálculo)
- Projeções: `FinancialProjectionService.php`, `CashFlowProjectionService.php`, `DreProjectionService.php`
- Relatórios: `FinancialReportService.php`, `AuditReportService.php`
- Validação: `CommissionPaymentValidationService.php`, `AuditService.php`

## Invariantes/Eventos

- Observers em `app/Observers/**` reagem a mudanças e mantêm consistência (Gig, GigCost, Payment).
- Policies em `app/Policies/**` garantem autorização correta por operação.

## Interface (Filament v4)

- **4 Resources principais**: Artist, Booker, Gig, User
- **174 rotas** incluindo CRUD, operações financeiras, relatórios e auditoria.
- Widgets customizados em `app/Filament/Widgets/**`
- Pages customizadas em `app/Filament/Pages/**`

## Configuração

- `.env` + `config/**` controlam drivers (db, cache, queue), canais de log e permissões.
- `bootstrap/app.php` define rotas, middleware, exceções e health `/up`.

## Documentação

| Tipo | Localização |
|------|-------------|
| Ativa | `docs/` |
| AI Context | `docs/ai_context/` |
| Devlog | `docs/devlog/` |
| Claude Guides | `.claude/` |
| Legacy/Arquivada | `legacy/` |

## Changelog

- **2026-01-12**: Reorganização documental - 35+ arquivos movidos para `legacy/`
- **2025-11**: Implementação de Services API completa
- **2025-10**: Otimizações de performance e cache
