## Objetivo
- Validar todos os cenários da feature de restauração inteligente com soft delete para garantir UX, integridade de dados e segurança.
- Preparar a base para commit em `dev` com Conventional Commits.

## Escopo de Testes
### Funcionais (CRUD)
1. Create sem fantasma
- Dado: nome inédito
- Ação: Criar
- Esperado: 201/redirect com sucesso; registro aparece na lista; `deleted_at=null`.

2. Create com fantasma (soft-deleted)
- Dado: existe `name=Financeiro` com `deleted_at!=null`
- Ação: Criar `Financeiro`
- Esperado: banner de restauração; ao clicar [Restaurar], faz `restore()` e aplica inputs atuais; sem criação duplicada; redireciona com sucesso.

3. Create com ativo duplicado
- Dado: existe `name=Financeiro` ativo (`deleted_at=null`)
- Ação: Criar `Financeiro`
- Esperado: validação bloqueia com mensagem "Já existe um centro de custo com este nome"; sem criação.

4. Update sem fantasma
- Dado: centro existente; novo nome inédito
- Ação: Editar e salvar
- Esperado: atualiza normalmente; sem banner.

5. Update com fantasma
- Dado: existe `name=X` soft-deleted; editando outro registro para `name=X`
- Ação: salvar → [Restaurar]
- Esperado: restaura fantasma `X` e aplica dados; redireciona com sucesso; sem duplicidade.

6. Update com ativo duplicado
- Dado: existe `name=X` ativo
- Ação: editar para `name=X`
- Esperado: validação bloqueia; mensagem exibida; sem alteração.

### Flags e Campos
7. `is_active`
- Ação: marcar/desmarcar no create/update e no restore
- Esperado: persistir corretamente; badge "Ativo/Inativo" na lista.

8. `use_custom_color` + `color`
- Ação: alternar toggle; definir cor; restaurar
- Esperado: `color=null` quando toggle off; cor salva quando on; chip aparece na lista.

### UI/UX
9. Banner de restauração
- Exibe nome e data `deleted_at` formatada
- Botões: [Restaurar] submete formulário atual com `restore_confirm=1`; [Cancelar] limpa banner e mantém inputs.

10. Alertas
- Apenas um alerta de sucesso no topo após ações; erros exibidos uma vez.

### Segurança/Autorização
11. Sem permissão `manage cost-centers`
- Ação: tentar criar/restaurar/excluir
- Esperado: 403.

### Integridade & Banco
12. Soft delete
- Excluir um centro
- Esperado: `deleted_at` setado; não listado por padrão.

13. Restauração
- Restaurar fantasma
- Esperado: `deleted_at=null`; registro aparece na lista; índices/contagens consistentes.

### Concurrency (Manual)
14. Dois usuários tentando criar/restaurar o mesmo nome simultaneamente
- Esperado: apenas um restore efetivo; outro fluxo segue com banner até confirmação; sem duplicatas.

## Testes Automatizados (Plano)
- Ambiente: `APP_ENV=testing`, DB `sqlite` in-memory; `QUEUE_CONNECTION=sync`; `EXTERNAL_APIS_ENABLED=false`.
- Suites:
  - Feature: `CostCenterRestoreTest`
    - test_create_without_ghost_creates_record
    - test_create_with_ghost_shows_banner_and_on_confirm_restores
    - test_create_with_active_duplicate_fails_validation
    - test_update_with_ghost_shows_banner_and_on_confirm_restores
    - test_update_with_active_duplicate_fails_validation
    - test_restore_applies_is_active_and_color_flags
  - Policy: `CostCenterPolicyTest` (autorização básica `manage cost-centers`).

## Dados & Preparação
- Seedar apenas o necessário para cada teste (factories de `CostCenter`).
- Limpar entre testes; usar transactions.

## Critérios de Aceite
- Nenhum 500 ou exceção não tratada.
- Nenhuma duplicidade criada; restore aplica dados atuais.
- Mensagens corretas exibidas; apenas um alerta de sucesso por ação.
- Autorização respeitada.

## Conv Commits (após validação)
- `feat(cost-centers): add intelligent restore flow for soft-deleted records`
- `fix(view): show single success alert in cost-centers index`
- `chore(validation): enforce unique only for active records`
- `test(cost-centers): cover restore flow and validation rules`
- `docs(ops): add UX notes and testing steps for restore`

## Próximos Passos
- Executar os 14 cenários manualmente.
- Implementar os testes automatizados conforme plano.
- Registrar resultados e evidências; se tudo ok, proceder ao commit e merge na branch `dev`. 