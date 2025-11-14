## Status Atual
- Documentação técnica consolidada em `docs/*` (visão, padrões, operações, endpoints, dados, AI context, MCP). 
- Rotas para relatório de testes já existentes: `routes/web.php:165–167` (index/execução/export). 
- Sem branch de refatoração criada ainda; sem watcher dedicado para backend/tests/QA.

## Objetivos
- Criar uma branch dedicada para a refatoração e instrumentação. 
- Adicionar watchers para backend, testes, estática e formatação. 
- Integrar um Painel de Status de Desenvolvimento (Taskmaster-like) com visão em tempo real de branch/processos/testes.

## Branch
- Nome: `feature/refactor-watchers-dev-status`. 
- Comandos: `git checkout -b feature/refactor-watchers-dev-status`; `git push -u origin feature/refactor-watchers-dev-status`.

## Watchers (Design)
- Backend code watcher: observar `app/`, `routes/`, `config/`, `resources/` e disparar: 
  - `vendor/bin/phpunit --testdox` (rápido) e opcionalmente `--coverage-text` sob demanda.
  - `vendor/bin/phpstan` (nível atual) para análise estática. 
  - `vendor/bin/pint` para autoformatação on-change (modo agressivo opcional).
- Frontend watcher: já coberto por `vite` (`npm run dev`). 
- Logs/filas: streaming com `php artisan pail --timeout=0` e `php artisan queue:listen` (já orquestrado no `composer.json:62–65`).
- Implementação técnica do watcher: 
  - Preferência: script Node com `fs.watch`/`chokidar` (adição como `devDependency`) + `concurrently` para orquestrar.
  - Alternativa sem nova lib: uso de `watchexec`/`entr` (se disponível) com comandos shell; ou loop com `inotifywait` (Linux).

## Painel de Status (Taskmaster-like)
- Nova rota dev-only `/dev/status` (protegida por role admin): 
  - Exibe: branch atual (`git rev-parse --abbrev-ref HEAD`), último commit (`git rev-parse --short HEAD`), processos ativos (server/queue/pail/vite/watchers), resumo de testes (usa `TestReportController@index`), saúde (`/up`).
  - Backend: controller lê um arquivo `storage/app/dev-status.json` atualizado pelos watchers (heartbeat, timestamps, comandos em execução, última falha). 
  - Frontend: Blade com indicadores (verde/amarelo/vermelho) e ações (forçar reexecução de testes via `POST /test-report/run`).

## Scripts
- Composer: adicionar `scripts` "watch" rodando watchers + `pail` + `queue:listen` + `vite` via `concurrently`. 
- NPM: adicionar `watch:dev` (frontend) e `watch:backend` (backend/test/phpstan/pint) para orquestração.

## Acompanhamento
- Web: 
  - `/test-report` para ver testes e cobertura. 
  - `/dev/status` para status consolidado (branch/processos/health/tests). 
- CLI:
  - Sail: `sail up -d`; `sail artisan pail --timeout=0`; `sail artisan queue:listen`; `sail npm run dev`; `sail composer run dev`.
- Logs e falhas: 
  - Pail streaming + registros estruturados; watchers registram última execução e erro em `storage/app/dev-status.json`.

## Entregáveis
- Branch criada e publicada.
- Scripts de watcher e orquestração adicionados a `composer.json`/`package.json`.
- Controller/rota/view para `/dev/status` com heartbeat de watchers.
- Documentação rápida em `docs/OPERATIONS.md` sobre uso dos watchers e painel.

## Validação
- Rodar ambiente de dev (Sail) com watchers ativos; verificar `/up`, `/test-report` e `/dev/status`. 
- Introduzir mudança simulada em `app/Services/*` e confirmar reexecução automática de testes e atualização de painel.

## Plano de Rollback
- Remover rota/painel dev-only e watchers; voltar scripts para estado anterior; manter branch para histórico.

## Solicitação de Aprovação
- Ao aprovar, executo: criação da branch, adição dos scripts de watcher, painel `/dev/status`, e instruções operacionais atualizadas.