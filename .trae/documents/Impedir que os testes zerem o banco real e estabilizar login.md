## Problema
- Ao executar `/test-report`, os testes rodaram com `RefreshDatabase` e zeraram o banco real `eventospro`.
- A causa provável: `.env.testing` aponta para MySQL (testing) e/ou não é aplicado, e o test runner caiu no `.env` local com MySQL real; ou `.env.testing` está sendo usado e os testes droparam tabelas de “default connection”.

## Correções
1) Forçar ambiente de testes com SQLite in-memory:
- Atualizar `.env.testing` para `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`;
- Garantir `QUEUE_CONNECTION=sync`, `SESSION_DRIVER=array`, `EXTERNAL_APIS_ENABLED=false`.
2) Fixar o runner para usar `phpunit.xml` e APP_ENV=testing:
- Em `TestReportController`, executar: `env APP_ENV=testing vendor/bin/phpunit -c phpunit.xml` (com `--coverage-text` quando solicitado).
3) Orientação operacional:
- Restaurar o banco `eventospro` uma última vez após a correção e validar login.

## Validação
- Rodar `/test-report` novamente: banco `eventospro` permanece intacto; testes usam SQLite; login funciona.
- Checar que Vite e servidor seguem responsivos.

## Commit
- `test(db): force sqlite in-memory via .env.testing and phpunit.xml`
- `fix(tests): run phpunit with APP_ENV=testing and config file`

## Próximo passo
- Aplicar as alterações na branch `dev`, restaurar banco e validar login e `/test-report`.