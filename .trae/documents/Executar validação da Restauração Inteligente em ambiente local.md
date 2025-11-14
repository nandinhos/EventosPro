## Preparação
- Garantir ambiente ativo: `./vendor/bin/sail up -d`.
- Abrir app em `http://localhost/` com usuário autorizado para `manage cost-centers`.
- Confirmar rotas: acessar `Centros de Custo` (menu Configurações).

## Execução (Passo a passo)
1) Create sem fantasma
- Acessar `Centros de Custo → Novo`.
- Preencher “Nome” inédito (ex.: `Operações`), descrição, flags.
- Salvar.
- Verificar: alerta único de sucesso; item listado; `Ativo/Inativo` correto.

2) Create com fantasma
- Soft-delete um nome (ex.: `Financeiro`) pela lista.
- Acessar `Novo` e preencher “Financeiro”.
- Verificar: banner de restauração com data de exclusão.
- Clicar “Restaurar”.
- Confirmar: redireciona com sucesso, item aparece na lista com dados atuais; sem duplicidade.

3) Create com ativo duplicado
- Tentar criar “Financeiro” novamente com item ativo.
- Verificar: erro de validação “Já existe um centro de custo com este nome”; sem criação.

4) Update sem fantasma
- Editar um centro para um nome inédito; salvar.
- Verificar: sucesso; sem banner.

5) Update com fantasma
- Soft-delete “Marketing”.
- Editar outro centro e trocar o nome para “Marketing”.
- Verificar: banner; “Restaurar” aplica dados atuais; sucesso.

6) Flags e cor
- Criar/editar/restaurar testando:
  - `is_active` marcado/desmarcado;
  - `use_custom_color` on/off com cor.
- Verificar badge, cor no chip, persistência.

7) Segurança
- Acessar com usuário sem `manage cost-centers` e tentar criar/restaurar.
- Verificar: 403.

8) Integridade
- Soft delete de um centro; verificar que sai da lista.
- Restaurar; verificar que volta; contagens consistentes.

9) Logs
- Monitorar `storage/logs/laravel.log` durante “Restaurar”.
- Se não aparecer, inspecionar comportamento via navegador; confirmar que request envia `restore_confirm=1`.

## Aceite
- Sem 500; sem duplicatas; restore aplica dados atuais; alerta único; autorização respeitada.

## Commit (dev)
- Após validação, preparar commits:
  - `feat(cost-centers): intelligent restore flow for soft-deleted records`
  - `fix(view): single success alert on cost-centers index`
  - `chore(validation): unique only for active records`
  - (opcional) `test(cost-centers): cover restore flow and validation`

## Próximo
- Executar os passos acima agora e reportar evidências; se tudo ok, realizo os commits seguindo Conventional Commits e avanço para próximas features.