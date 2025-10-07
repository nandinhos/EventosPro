# Developer Logins Plugin - Guia de Uso

## Visão Geral

O plugin Filament Developer Logins permite login rápido com um clique durante o desenvolvimento, facilitando o teste de diferentes perfis de usuário sem precisar digitar email/senha manualmente.

## Configuração

O plugin já está instalado e configurado em `app/Providers/Filament/AdminPanelProvider.php`:

```php
->plugin(
    FilamentDeveloperLoginsPlugin::make()
        ->enabled(app()->environment('local'))
        ->switchable(true)
        ->users([
            'Admin' => 'admin@eventospro.com',
            'Admin DEV' => 'nandinhos@gmail.com',
            'Diretor' => 'diretor@eventospro.com',
            'Booker' => 'booker@eventospro.com',
        ])
)
```

### Segurança

- ✅ **Habilitado APENAS em ambiente local** (`->enabled(app()->environment('local'))`)
- ✅ **Desabilitado automaticamente em produção**
- ✅ **Botão "Switch to" aparece no canto superior direito quando logado**

## Usuários de Teste Disponíveis

Os seguintes usuários foram criados para facilitar os testes:

| Nome | Email | Senha | Role | Descrição |
|------|-------|-------|------|-----------|
| Admin EventosPro | admin@eventospro.com | password | ADMIN | Administrador principal |
| Admin DEV | nandinhos@gmail.com | 123456789 | ADMIN | Administrador de desenvolvimento |
| Diretor Teste | diretor@eventospro.com | password | DIRETOR | Perfil de diretor |
| Booker Teste | booker@eventospro.com | password | BOOKER | Perfil de booker (vinculado ao Booker ID 1) |

## Como Usar

### 1. Acesso Inicial

1. Acesse a página de login do Filament: `http://localhost/admin/login`
2. Na página de login, você verá botões de "Login rápido" para cada usuário configurado
3. Clique no botão do usuário desejado para fazer login instantaneamente

### 2. Trocar de Usuário (Switch)

Quando já estiver logado:

1. Localize o botão "Switch to" no canto superior direito da interface
2. Clique no botão e selecione o usuário para o qual deseja trocar
3. Você será automaticamente autenticado como o novo usuário

### 3. Testar Diferentes Perfis

**Teste como ADMIN:**
- Login: `admin@eventospro.com`
- Acesso completo a todos os recursos
- Pode gerenciar usuários, artistas, bookers, gigs, etc.

**Teste como DIRETOR:**
- Login: `diretor@eventospro.com`
- Acesso a relatórios de performance
- Visualização de todos os gigs

**Teste como BOOKER:**
- Login: `booker@eventospro.com`
- Acesso apenas aos próprios gigs
- Visualização do dashboard de booker
- Menu "Meu Desempenho" disponível

## Adicionar Novos Usuários de Teste

Para adicionar novos usuários ao plugin:

1. Crie o usuário via Tinker:
```bash
./vendor/bin/sail artisan tinker

$user = App\Models\User::create([
    'name' => 'Novo Usuário',
    'email' => 'novo@eventospro.com',
    'password' => bcrypt('password')
]);
$user->assignRole('BOOKER'); // ou ADMIN, DIRETOR
```

2. Adicione o email no `AdminPanelProvider.php`:
```php
->users([
    'Admin' => 'admin@eventospro.com',
    'Admin DEV' => 'nandinhos@gmail.com',
    'Diretor' => 'diretor@eventospro.com',
    'Booker' => 'booker@eventospro.com',
    'Novo Usuário' => 'novo@eventospro.com', // Adicione aqui
])
```

3. Limpe o cache:
```bash
./vendor/bin/sail artisan optimize:clear
```

## Troubleshooting

### Plugin não aparece na página de login

**Solução:** Verifique se o ambiente está configurado como `local` no `.env`:
```env
APP_ENV=local
```

### Botão "Switch to" não aparece

**Solução:** Certifique-se de que `switchable(true)` está configurado no provider e que você está logado.

### Erro 403 ao tentar fazer login

**Solução:** O plugin pode estar desabilitado. Verifique:
1. Ambiente está como `local`
2. Cache foi limpo: `./vendor/bin/sail artisan optimize:clear`
3. Configuração no `AdminPanelProvider.php` está correta

## Referências

- [Documentação oficial do plugin](https://github.com/DutchCodingCompany/filament-developer-logins)
- [Filament v4 Documentation](https://filamentphp.com/docs/4.x/panels/installation)

## Importante

⚠️ **NUNCA habilite este plugin em produção!** A configuração `->enabled(app()->environment('local'))` garante que o plugin só funcione localmente, mas sempre verifique antes de fazer deploy.
