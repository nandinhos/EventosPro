## Objetivos
- Confirmar que a restauração do banco (VPS → local) está íntegra e o sistema funciona com dados reais.
- Detectar e corrigir quebras (rotas, filas, timeouts, relatórios, auditoria) antes de commitar.
- Criar a branch `dev` para desenvolver com segurança e ter fallback para `main` estável.
- Organizar o commit conforme o padrão do projeto (Conventional Commits) e registrar tarefas no `docs/TASKS.md`.

## Checklist de Validação
- Ambiente
  - Conferir `.env`: `DB_DATABASE=eventospro`, `DB_*` credenciais, `APP_URL`, `QUEUE_CONNECTION`.
  - Subir serviços: `./vendor/bin/sail up -d`.
  - Health: `GET /up` responde 200.
- Dados
  - Contagens básicas após restore: usuários, gigs, artistas, bookers, pagamentos (conforme documento de sucesso).
  - Verificar se nenhuma migration é executada (restore já traz tabelas).
- Funcional
  - Autenticação via usuários restaurados; acessar `GET /dashboard`.
  - CRUDs: `gigs`, `artists`, `bookers`, `cost-centers` listam e detalham.
  - Relatórios: `GET /reports`, visão geral e export (`/reports/overview/export/{format}`).
  - Projeções: `GET /projections` e `GET /projections/debug`.
  - Auditoria: `GET /auditoria`, painel de auditorias (`/audit/dashboard`) e execução (`/audit/run-all-audits`).
  - Fechamento mensal: `GET /financeiro/fechamento-mensal` e export.
  - Test report: `GET /test-report` e `POST /test-report/run` (com cobertura opcional).
- Filas/Jobs
  - Se usar `database`: garantir tabela `jobs`; caso não exista, gerar migration (`php artisan queue:table`) e migrar.
  - Em dev, preferir `QUEUE_CONNECTION=sync` para evitar timeouts e reduzir overhead.
- Qualidade
  - Rodar testes: `vendor/bin/phpunit --testdox`.
  - Logs: usar `php artisan pail --timeout=0`; inspecionar erros recorrentes (rotas não definidas, timeouts de fila, 500s).
- Performance
  - Observar latência de `vite` e servidor; sem watcher adicional ativo.
  - Checar consultas pesadas em relatórios com dados reais; ajustar eager loading se necessário.
- Segurança
  - Verificar políticas (Spatie Permission) aplicadas nas rotas de relatório/auditoria.
  - Sessões e cookies no `.env.vps.example` não impactam dev.

## Ações Planejadas
1. Criar branch `dev`: `git checkout -b dev && git push -u origin dev`.
2. Executar checklist acima e anotar achados em `docs/TASKS.md` (marcar itens concluídos e novos issues encontrados).
3. Corrigir eventuais quebras identificadas (rotas, filas, timeouts, seeds idempotentes, views com links incorretos).
4. Commit organizado após validação, seguindo Conventional Commits (exemplos):
   - `chore(env): set QUEUE_CONNECTION=sync for local dev`
   - `fix(tests): correct test-report run route and coverage flag`
   - `docs(ops): add restore guide steps and validation checklist`
5. Seguir para a próxima feature conforme o backlog em `docs/TASKS.md`.

## Critérios de Aprovação
- `/up` responde OK, login funcional.
- CRUDs e relatórios abrem sem 500/403 indevidos; exportações funcionam.
- Auditorias executam sem exceções bloqueantes.
- Testes rodam com saída `--testdox` sem falhas críticas.
- Filas não geram timeouts em dev (sync ou jobs table presente).

## Rollback
- Em caso de pane na `dev`, voltar para `main`: `git checkout main` e continuar no estado estável.

## Entregáveis
- Branch `dev` criada.
- Validação concluída e registrada em `docs/TASKS.md`.
- Commit(s) organizados com escopos claros e mensagens padronizadas.
