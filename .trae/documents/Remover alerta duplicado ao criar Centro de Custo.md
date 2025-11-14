## Causa
- Na view `resources/views/cost-centers/index.blade.php` há um bloco que renderiza `session('success')` (linhas 18–22).
- O layout/base (`x-app-layout`) já exibe um alerta de sucesso global, resultando em duas mensagens idênticas na tela após o redirect com `with('success', ...)`.

## Solução
- Remover o bloco local de `session('success')` em `cost-centers/index.blade.php` e manter apenas o alerta global do layout.
- Preservar o bloco de `session('error')` (linhas 24–28), já que o layout pode não renderizar erros da mesma forma.
- Nenhuma mudança necessária no `CostCenterController` (o `return redirect()->route(...)->with('success', '...')` continua idêntico).

## Passos
1. Editar `resources/views/cost-centers/index.blade.php` e excluir o bloco:
   ```blade
   @if (session('success'))
       <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
           {{ session('success') }}
       </div>
   @endif
   ```
2. Testar criando um Centro de Custo: deve aparecer apenas o alerta superior (global) e não mais o segundo.
3. Manter `session('error')` para mensagens de exclusão bloqueada.

## Validação
- Criar novo centro de custo → apenas um alerta de sucesso (o superior) é exibido.
- Executar exclusão com dependências → erro mostrado apenas uma vez.

## Observação
- Se desejar padronização futura, podemos mover todos os flashes (`success`, `error`) para um componente único global e remover duplicações em outras views, mas neste ajuste atuaremos apenas na tela de Centros de Custo como solicitado.