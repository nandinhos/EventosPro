## Objetivo
- Comitar a feature de restauração inteligente de Centros de Custo e a correção de alerta duplicado, seguindo Conventional Commits, na branch `dev`.

## Commits Planejados
1) `chore(validation): enforce unique only for active records`
- Arquivo: `app/Http/Requests/CostCenterRequest.php`
- Regra de `unique` com `whereNull('deleted_at')` e `ignore($id)`.

2) `feat(cost-centers): intelligent restore flow for soft-deleted records`
- Arquivos: 
  - `app/Http/Controllers/CostCenterController.php` (detecção de fantasma e fluxo de restore inline com `restore_confirm`)
  - `resources/views/cost-centers/_form.blade.php` (banner com [Restaurar]/[Cancelar], sem form aninhado)
  - `routes/web.php` (rota opcional para restore via POST)

3) `fix(view): show single success alert in cost-centers index`
- Arquivo: `resources/views/cost-centers/index.blade.php` (remoção do alerta duplicado local; manter apenas o global).

## Passos
- Garantir branch `dev` ativa.
- Verificar `git status` e estagiar apenas os arquivos acima.
- Comitar em três commits separados conforme descrito.
- Push para `origin dev`.

## Saída
- Branch `dev` atualizada com commits organizados; pronta para PR/merge.