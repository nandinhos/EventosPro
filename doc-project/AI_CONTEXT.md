# Contexto de IA — EventosPro

> **Atualizado**: 2026-01-12 | **Stack**: Laravel 12 + Filament 4 + PHP 8.4

## Objetivo

Consolidar informações essenciais para agentes LLM com mínimo custo de tokens.

## Índice Rápido

| Tag | Documento | Propósito |
|-----|-----------|-----------|
| [FONTE] | `docs/SOURCE_OF_TRUTH.md` | Verdade única do sistema |
| [ARQUITETURA] | `docs/TECHNICAL_OVERVIEW.md` | Visão técnica |
| [PADRÕES] | `docs/CODE_STANDARDS.md` | Padrões de código |
| [ENDPOINTS] | `docs/ENDPOINTS.md` | Catálogo de rotas |
| [DADOS] | `docs/DATABASE.md` | Schema e relacionamentos |
| [SERVICES] | `docs/SERVICES_API.md` | API dos services |
| [TESTES] | `docs/TESTING.md` | Guia de testes |

## Contexto Detalhado

- `docs/ai_context/1_context.md` - Negócio
- `docs/ai_context/2_architecture.md` - Arquitetura
- `docs/ai_context/3_stack.md` - Stack
- `docs/ai_context/4_rules.md` - Regras

## Pontos Críticos

- **Entrypoints**: `public/index.php`, `bootstrap/app.php`, `artisan`
- **Rotas**: 174 rotas em `routes/web.php`
- **Services**: 17 services em `app/Services/**`
- **Models**: 15 modelos em `app/Models/**`
- **Segurança**: Breeze + Spatie Permission + Policies
- **Logs/Health**: `config/logging.php` e `/up`

## Tags de Contexto

- `[DOMAIN]` Serviços e modelos centrais
- `[ROUTES]` Catálogo de endpoints e nomes
- `[SECURITY]` Autenticação, autorização e sessões
- `[OPS]` Execução, deploy e portas
- `[LEGACY]` Documentação arquivada em `legacy/`
