## Objetivos
- Produzir uma visão técnica completa e atual do sistema para manutenção.
- Consolidar documentação dispersa/obsoleta em uma única fonte de verdade, otimizada para uso por LLMs.
- Mapear padrões de codificação, arquitetura e segurança.
- Propor instalação e integração do SERENA MCP para gestão de contexto de IA.

## Sumário Técnico (entregar como docs/TECHNICAL_OVERVIEW.md)
- Stack: Laravel 12 (PHP 8.2), Filament 4, Breeze, Spatie Permission; Frontend Blade/Tailwind/Alpine; Vite 6; Axios/Chart.js.
- Arquitetura: Monolito MVC; camadas em app/ (Models, Services, Controllers, Policies, Observers);
  - Entrypoints: `public/index.php`, `artisan`, `bootstrap/app.php` (rotas health `/up`).
- Funcionalidades (rotas principais com referências):
  - Autenticação e perfil: `routes/auth.php`, `routes/web.php:54–56`.
  - Usuários: `routes/web.php:48`.
  - Portal Booker: `routes/web.php:51`.
  - Gigs CRUD e aninhadas: `routes/web.php:130–151` (costs/payments/settlements) e debug financeiro `routes/web.php:162`.
  - Relatórios: overview/delinquency/due-dates/performance/artist-performance, export PDF/Excel `routes/web.php:59–76, 119–122, 105–113`.
  - Projeções financeiras e debug: `routes/web.php:100–104`.
  - Pagamentos em massa (artists/bookers): `routes/web.php:70–73, 80–81`.
  - Auditoria de dados e painel: `routes/web.php:115–118, 169–181`.
  - Fechamento mensal: `routes/web.php:125–127`.
  - Test report: `routes/web.php:165–168`.
- Banco de Dados: MySQL 8; migrations/seeders/factories; soft deletes em entidades centrais.
- Serviço e domínio: regras de negócio em `app/Services/**` (ex.: `GigFinancialCalculatorService.php`, `FinancialProjectionService.php`), invariantes em `app/Observers/**`, autorização em `app/Policies/**`.
- Execução: Docker Compose + Laravel Sail; scripts `composer.json:scripts.dev` e `package.json:scripts`.
- Qualidade: PHPUnit/PhpStan/Pint; Debugbar (dev); EditorConfig.
- Logging/Monitoramento: Monolog (`config/logging.php`), Pail (tempo real), health `/up`.
- Segurança: Breeze (AuthN), Spatie Permission (AuthZ), CSRF nativo, sessões `database`, `.env.vps.example` com endurecimento.

## Padrões de Codificação (entregar como docs/CODE_STANDARDS.md)
- PSR-12 via Pint; nomes de classes/arquivos consistentes; controllers finos, lógica em services; validação via FormRequests; Eloquent com fillable/guarded; SoftDeletes+Observers; Policies por recurso.
- Rotas: `Route::resource` + prefixos nomeados; evitar lógica em closures.
- Views: Blade modular com componentes reutilizáveis; Tailwind utilitário; JS com Alpine.
- Frontend: Vite 6, sem `vite.config.js` custom; assets sob `resources/` com import por `@vite`.

## Fonte da Verdade (entregar como docs/SOURCE_OF_TRUTH.md)
- Dados: MySQL (migrations/seeders) e modelos Eloquent.
- Regras: `app/Services/**` centraliza cálculos financeiros e relatórios.
- Invariantes/eventos: `app/Observers/**` e Policies.
- Configuração: `.env` + `config/**`; health e pipeline em `bootstrap/app.php`.

## Consolidação de Documentação
- Unificar conteúdo de `docs/ai_context/**`, `README_AGENTS.md` e arquivos como `GEMINI.md` em `docs/AI_CONTEXT.md` com índice enxuto.
- Criar `docs/OPERATIONS.md` com:
  - Setup (Sail/sem Sail), comandos, migrações/seed, build de assets, portas.
  - Deploy (VPS com Docker), ajustes de `.env.vps.example`.
- Criar `docs/ENDPOINTS.md` extraindo rotas de `routes/web.php` com descrição, parâmetros e permissões.
- Criar `docs/DATA_MODEL.md` com entidades, relacionamentos e flags (soft delete, índices).
- Notas de obsolescência:
  - README menciona `deploy.sh` não localizado: ajustar referências.
  - Backups (`tailwind.config.js.v3.backup`, views `*copy*`): revisar/remover ou mover para `docs/LEGACY.md`.

## Otimização de Contexto para LLMs
- Introduzir um único arquivo síntese (`docs/CONTEXT_SUMMARY.md`) com os pontos críticos e links internos.
- Padronizar cabeçalhos e tags para recuperação semântica (ex.: `[DOMAIN]`, `[ROUTES]`, `[SECURITY]`).

## SERENA MCP (entregar como docs/MCP_SERENA_SETUP.md)
- Objetivo: servir contexto consolidado via MCP para agentes de IA, reduzindo uso de tokens e trazendo documentos sob demanda.
- Passos:
  - Instalar SERENA MCP Host.
  - Configurar provider para leitura de `docs/**` e índices.
  - Definir policies de acesso (somente leitura em produção).
  - Integrar com agente (README_AGENTS.md atualizado) e validar com queries típicas.

## Validação
- Verificar que todos os documentos novos cobrem: arquitetura, endpoints, operações, padrões, fonte da verdade.
- Rodar `composer run dev` e acessar `/up` para sanity check (documentado em OPERATIONS). Sem executar agora.
- Garantir consistência de nomes e referências; remover menções a arquivos inexistentes.

## Entregáveis
- `docs/TECHNICAL_OVERVIEW.md`, `docs/CODE_STANDARDS.md`, `docs/SOURCE_OF_TRUTH.md`, `docs/OPERATIONS.md`, `docs/ENDPOINTS.md`, `docs/DATA_MODEL.md`, `docs/AI_CONTEXT.md`, `docs/CONTEXT_SUMMARY.md`, `docs/MCP_SERENA_SETUP.md`, `docs/LEGACY.md`.

## Próximos Passos
- Aguardar aprovação para criar/atualizar os arquivos e alinhar referências no README.
- Após aprovação, executar a consolidação e revisar obsolescências.
