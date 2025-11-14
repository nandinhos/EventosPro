## Problemas observados
- App acessível em `http://localhost` (porta 80), não em `http://localhost:8000` (o servidor web do container expõe 80; ver `docker-compose.yml:13`).
- Banco criado como `laravel` em algum momento anterior; hoje `.env` aponta para `eventospro` (`.env:23–29`).
- Login com "Page expired" indica provável mismatch de host/porta (CSRF), sessão ou cache.
- `/test-report` não abre quando usado com porta incorreta ou cache de rotas desatualizado.

## Plano de correção
1) Alinhamento de host/porta
- Acessar o app por `http://localhost/` (porta 80 mapeada). Evitar `http://localhost:8000/` durante uso de Sail.
- Confirmar `APP_URL=http://localhost` (`.env:5`) e manter `SESSION_DOMAIN=null` (`.env:34`).

2) Banco de dados
- Verificar bancos existentes no MySQL e garantir que `eventospro` está presente e populado (backup restaurado). Se houver `laravel`, ignorar ou remover se estiver não usado.
- Garantir `.env` com `DB_DATABASE=eventospro` (`.env:26`) e recarregar configs (limpar caches).

3) Sessão/CSRF e caches
- Limpar `config`, `route`, `view` e `cache` para remover artefatos de host/porta.
- Validar que login é feito pelo mesmo host e porta do `APP_URL` (`http://localhost/login`). Evitar misturar com `:8000`.

4) Filas e performance (dev)
- Manter `QUEUE_CONNECTION=sync` no `.env` em dev para evitar timeouts e travas.
- Deixar orquestração sem watchers adicionais e usar apenas `server + vite + logs + queue:listen` (queue inofensiva em sync).

5) Test Report
- Abrir `http://localhost/test-report` e executar testes pelo botão; a rota JavaScript está corrigida para `route('test-report.run')` em `resources/views/test-report/index.blade.php`.
- Se ainda falhar, limpar caches novamente e confirmar lista de rotas (`php artisan route:list`).

## Validação
- Health: `GET http://localhost/up` → 200.
- Login: `POST http://localhost/login` funciona sem "Page expired".
- Navegação: CRUDs e relatórios carregam com dados reais; `/test-report` abre e executa.
- DB: `eventospro` com contagens esperadas conforme `docs/RESTORE_BACKUP_SUCCESS.md`.

## Branch e commits
- Branch `dev` já criada e publicada para desenvolvimento seguro.
- Após validar, faremos commit organizado:
  - `chore(env): set QUEUE_CONNECTION=sync for local dev`
  - `fix(routes): align test-report run route in view`
  - `docs(restore): add validation steps for host/port and DB`

## Próximo passo
- Executar as verificações e limpeza de caches, testar login e `/test-report` em `http://localhost`. Se qualquer checagem falhar, registrar no `docs/TASKS.md` e corrigir imediatamente.
