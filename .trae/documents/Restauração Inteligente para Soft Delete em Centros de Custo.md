## Objetivo
- Evitar erro de unicidade quando existir um registro soft-deletado com o mesmo nome.
- Oferecer UX com confirmação para restaurar o registro excluído em vez de criar um novo.

## Alterações Principais
- Validação de unicidade considera apenas registros ativos (deleted_at NULL).
- Detecção proativa de "fantasma" (soft-deleted) e fluxo de restauração por confirmação.
- Endpoint dedicado para restaurar com os dados informados no formulário.
- Modal na UI para confirmar restauração.

## Backend
### 1) Validação (CostCenterRequest)
- Atualizar regra de `name` para ativos apenas:
```php
Rule::unique('cost_centers', 'name')
    ->ignore($costCenterId)
    ->whereNull('deleted_at');
```

### 2) Controller (CostCenterController)
- `store`:
  - Após validar, buscar fantasma:
  ```php
  $ghost = CostCenter::onlyTrashed()->where('name', $data['name'])->first();
  if ($ghost) {
      // Resposta para frontend
      return response()->json([
          'status' => 'restore_required',
          'ghost' => [
              'id' => $ghost->id,
              'name' => $ghost->name,
              'deleted_at' => $ghost->deleted_at?->toDateTimeString(),
          ],
          'message' => "Já existiu e foi excluído em {$ghost->deleted_at}. Deseja restaurar?",
      ], 409);
      // Fallback sem JS: redirect()->back()->with('restore_candidate', [...])->withInput();
  }
  // Caso não haja fantasma, prosseguir com create
  ```
- `update`:
  - Se o novo nome conflitar com um fantasma diferente do registro atual, aplicar lógica análoga e ofertar restauração.
- Novo método `restoreGhost(Request $request)`:
  - Receber `ghost_id` e os dados do formulário.
  - Executar `$ghost->restore();` e `update($data)`;
  - Normalizar `is_active`/`color` com as flags `is_active`/`use_custom_color` do formulário.
  - Redirecionar com sucesso.

### 3) Rotas
- Adicionar rota protegida:
```php
Route::post('/cost-centers/restore', [CostCenterController::class, 'restoreGhost'])
     ->name('cost-centers.restore')
     ->middleware('auth');
```

### 4) (Opcional) Índice Único no Banco
- Trocar `UNIQUE(name)` por `UNIQUE(name, deleted_at)` para que o BD reflita a regra "único entre ativos".
- Migration: `Schema::table('cost_centers', function (Blueprint $table) { $table->dropUnique('cost_centers_name_unique'); $table->unique(['name','deleted_at']); });`
- Benefício: mesmo que algum fluxo tente inserir antes da checagem, o BD permite duplicidade quando `deleted_at` não é NULL e bloqueia apenas o par `(name, NULL)`.

## Frontend (Blade/Alpine)
- No `_form.blade.php`, interceptar a submissão via JS/Alpine:
  - Submit normal → se resposta 409 com `status=restore_required`, abrir modal.
  - Modal:
    - Mensagem: "O Centro de Custo '[Nome]' já existiu e foi excluído em [Data]. Deseja restaurá-lo com os dados atuais?"
    - Botões: [Restaurar] chama `POST cost-centers.restore` com `ghost_id` + dados; [Cancelar] fecha modal e mantém formulário.
- Fallback sem JS: quando `session('restore_candidate')` estiver presente, renderizar bloco acima do formulário com a mesma mensagem e um botão que posta para `cost-centers.restore`.

## Fluxo de UX
1. Usuário tenta criar "Financeiro" → fantasma detectado.
2. Modal exibe data de exclusão e pergunta se deseja restaurar.
3. [Restaurar] → backend restaura e aplica os dados do formulário; sucesso.
4. [Cancelar] → volta ao formulário; usuário pode alterar o nome.

## Segurança/Autorização
- Reutilizar `manage cost-centers` para `restoreGhost`.
- Sanitizar e validar os dados no `CostCenterRequest` também na restauração.

## Testes (futuro)
- Cenário create: fantasma existente → fluxo 409/modal → restore → sucesso.
- Cenário update: mudança de nome conflitando com fantasma → modal → restore/merge → sucesso.
- Índice único composto garante unicidade entre ativos.

## Entregáveis
- Atualização do `CostCenterRequest`.
- Ajustes no `CostCenterController` (store/update + método restoreGhost).
- Rota `cost-centers.restore`.
- Modal/UX no `_form.blade.php` com fallback sem JS.
- (Opcional) Migration para índice único composto.

## Validação
- Criar/editar centros com nome existente soft-deletado → ver modal; restaurar com dados atuais; sem erro 500 nem nomes sujos. 