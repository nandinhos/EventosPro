## O que /test-report faz
- `GET /test-report` (app/Http/Controllers/TestReportController.php:12–41) roda dois comandos via Process:
  - `vendor/bin/phpunit --testdox` (app/Http/Controllers/TestReportController.php:92–104) para coletar resumo dos testes.
  - `vendor/bin/phpunit --coverage-text` (app/Http/Controllers/TestReportController.php:148–160) para extrair cobertura.
- `POST /test-report/run` (app/Http/Controllers/TestReportController.php:44–88) executa `vendor/bin/phpunit`, opcionalmente com cobertura, e retorna JSON.
- `phpunit.xml` (phpunit.xml:33–48) força `APP_ENV=testing` e define DB `testing` (`DB_DATABASE=testing`, `DB_USERNAME=user`, `DB_PASSWORD=password`).
- A view (resources/views/test-report/index.blade.php) chama a rota `test-report.run` e renderiza gráficos e métricas.

## Por que ocorre lentidão e resets de banco
- Se o MySQL não tiver o banco/usuário de testes (DB `testing` e user `user`/`password`), o `phpunit` pode falhar ou, em algumas configurações, cair no `.env` local e executar traits de teste que limpam/rodem migrations na base real.
- `queue:listen` com timeouts (ProcessTimedOutException) consome recursos em dev; com `database` driver sem tabela `jobs` há erros e reprocessos.
- Vite está recarregando em massa arquivos de `storage/framework/views/*` (compilação Blade), causando tempestade de reload no cliente.
- Chamadas externas (API BCB) geram timeouts e travam quando executadas em dev/test.

## Plano de correção (sem executar ainda)
1) Isolar testes em banco dedicado
- Opção A (recomendada): `sqlite` em memória no `phpunit.xml` (DB isolado, rápido).
- Opção B: Criar DB `eventospro_testing` no MySQL e ajustar `phpunit.xml`/`.env.testing` para credenciais reais do Sail (ex.: `root`/vazio). Garantir que testes NUNCA atinjam `eventospro`.
2) Filas em dev
- Fixar `QUEUE_CONNECTION=sync` (já aplicado) e remover `queue:listen` do script `composer dev` para evitar timeouts e reduzir consumo.
3) Vite
- Adicionar `vite.config.js` mínimo para ignorar `storage/framework/views` em watch (reduz recarregamentos em massa) e manter watch apenas em `resources/**`.
4) BCB / chamadas externas
- Introduzir flag/env (`EXTERNAL_APIS_ENABLED=false`) e usar fakes/mocks em `ExchangeRateService` quando `APP_ENV=local/testing`, evitando timeouts.
5) `/test-report`
- Garantir que rota JS está correta (`test-report.run`) e que os testes rodam no ambiente isolado; se desejar, desabilitar cobertura por padrão (rápido) e deixar cobertura como opcional.
6) Limpeza de caches
- Após ajustes, limpar `config`, `route`, `view` e `cache` e validar.

## Validação
- Acessar `http://localhost/up` → OK.
- Login em `http://localhost/login` sem “Page expired”.
- `GET /test-report` abre e executa testes sem tocar dados reais.
- Sem timeouts em filas; Vite sem tempestade de reloads.

## Branch e commits
- Usar branch `dev` (já criada) para aplicar ajustes.
- Commits propostos (Conventional Commits):
  - `test(db): use sqlite in-memory for phpunit to isolate data`
  - `chore(dev): remove queue:listen from composer dev script`
  - `build(vite): ignore storage/framework/views from watch`
  - `feat(config): add EXTERNAL_APIS_ENABLED and fake ExchangeRate in dev/test`
  - `fix(tests): correct /test-report run route and optional coverage`

## Próximo passo
- Ao aprovar, aplico a configuração de testes isolados, removo o `queue:listen` do script, crio o `vite.config.js` com ignore, adiciono flag de APIs e verifico `/test-report` e login em `http://localhost`. Depois registro no `docs/TASKS.md` e faço commit organizado.