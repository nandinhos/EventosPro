# Guia de Comandos Laravel Sail - EventosPro

## 📋 Visão Geral

**IMPORTANTE**: Este projeto utiliza Laravel Sail como ambiente de desenvolvimento Docker. Todos os comandos devem ser executados através do Sail para garantir consistência e compatibilidade.

## 🐳 Configuração Inicial

### Iniciando o Ambiente

```bash
# Iniciar containers
./vendor/bin/sail up -d

# Parar containers
./vendor/bin/sail down

# Verificar status dos containers
./vendor/bin/sail ps
```

### Alias Recomendado

Para facilitar o uso, configure um alias:

```bash
# Adicionar ao ~/.bashrc ou ~/.zshrc
alias sail='./vendor/bin/sail'

# Recarregar o shell
source ~/.bashrc
```

## 🛠️ Comandos de Desenvolvimento

### Artisan Commands

```bash
# ❌ NUNCA use diretamente:
php artisan migrate

# ✅ SEMPRE use com Sail:
sail artisan migrate
sail artisan make:controller ExampleController
sail artisan route:list
sail artisan config:cache
sail artisan queue:work
```

### Composer Commands

```bash
# ❌ NUNCA use diretamente:
composer install
composer require package/name

# ✅ SEMPRE use com Sail:
sail composer install
sail composer require package/name
sail composer update
sail composer dump-autoload
```

### NPM/Node Commands

```bash
# ❌ NUNCA use diretamente:
npm install
npm run dev

# ✅ SEMPRE use com Sail:
sail npm install
sail npm run dev
sail npm run build
sail npm run watch
```

## 🧪 Comandos de Teste

### PHPUnit Tests

```bash
# ❌ NUNCA use diretamente:
php artisan test
phpunit

# ✅ SEMPRE use com Sail:
sail artisan test
sail artisan test --coverage
sail artisan test tests/Unit/Services/
sail artisan test tests/Feature/

# Executar com cobertura específica
sail artisan test --coverage --min=80
```

### Scripts de Teste Personalizados

```bash
# Script de cobertura (deve ser executado com Sail)
sail bash -c "./run-tests-coverage.sh 80"

# Executar testes específicos
sail artisan test tests/Unit/Services/ExchangeRateServiceTest.php
```

## 🔧 Comandos de Manutenção

### Database Commands

```bash
# ❌ NUNCA use diretamente:
php artisan migrate:fresh
php artisan db:seed

# ✅ SEMPRE use com Sail:
sail artisan migrate:fresh
sail artisan migrate:fresh --seed
sail artisan db:seed
sail artisan migrate:rollback
sail artisan migrate:status
```

### Cache Commands

```bash
# ❌ NUNCA use diretamente:
php artisan cache:clear
php artisan config:clear

# ✅ SEMPRE use com Sail:
sail artisan cache:clear
sail artisan config:clear
sail artisan route:clear
sail artisan view:clear
sail artisan optimize:clear
```

## 📊 Comandos de Análise e Qualidade

### Code Quality

```bash
# ❌ NUNCA use diretamente:
vendor/bin/pint
vendor/bin/phpstan

# ✅ SEMPRE use com Sail:
sail bin pint
sail bin phpstan analyse
sail composer run-script pint
```

### Debugging

```bash
# Logs do container
sail logs
sail logs -f  # Follow logs

# Acessar shell do container
sail shell
sail root-shell  # Como root

# Executar comandos específicos
sail bash -c "php -v"
sail bash -c "composer --version"
```

## 🚀 Comandos de Deploy e Build

### Assets Build

```bash
# ❌ NUNCA use diretamente:
npm run production
vite build

# ✅ SEMPRE use com Sail:
sail npm run build
sail npm run production
```

### Optimization Commands

```bash
# ❌ NUNCA use diretamente:
php artisan optimize
php artisan config:cache

# ✅ SEMPRE use com Sail:
sail artisan optimize
sail artisan config:cache
sail artisan route:cache
sail artisan view:cache
```

## 🔍 Comandos de Monitoramento

### Performance e Debug

```bash
# Verificar performance
sail artisan route:list
sail artisan about

# Debug de configuração
sail artisan config:show
sail artisan env

# Verificar conexão com banco
sail artisan migrate:status
sail artisan tinker
```

## 📝 Comandos para Services Específicos

### Exchange Rate Service

```bash
# Testar service específico
sail artisan test tests/Unit/Services/ExchangeRateServiceTest.php

# Debug do service
sail artisan tinker
# > app(App\Services\ExchangeRateService::class)->getExchangeRate('USD')
```

### Audit Service

```bash
# Testar audit service
sail artisan test tests/Unit/Services/AuditServiceTest.php

# Executar auditoria manual
sail artisan tinker
# > $gig = App\Models\Gig::first()
# > app(App\Services\AuditService::class)->calculateGigAuditData($gig)
```

## ⚠️ Regras Importantes

### DO's ✅

1. **SEMPRE** use `sail` antes de qualquer comando PHP/Composer/NPM
2. **SEMPRE** verifique se os containers estão rodando (`sail ps`)
3. **SEMPRE** use `sail artisan` ao invés de `php artisan`
4. **SEMPRE** use `sail composer` ao invés de `composer`
5. **SEMPRE** use `sail npm` ao invés de `npm`

### DON'Ts ❌

1. **NUNCA** execute comandos PHP diretamente no host
2. **NUNCA** use `php artisan` sem o `sail`
3. **NUNCA** use `composer` diretamente no host
4. **NUNCA** use `npm` diretamente no host
5. **NUNCA** modifique arquivos de configuração Docker sem documentar

## 🔧 Troubleshooting

### Problemas Comuns

```bash
# Container não inicia
sail down
sail up -d --force-recreate

# Problemas de permissão
sail root-shell
chown -R sail:sail /var/www/html

# Cache corrompido
sail artisan optimize:clear
sail composer dump-autoload

# Banco de dados não conecta
sail down
sail up -d
sail artisan migrate:status
```

### Verificação de Ambiente

```bash
# Verificar se Sail está funcionando
sail --version
sail ps
sail artisan about

# Verificar configuração do projeto
sail artisan config:show database
sail artisan route:list
```

## 🚨 Troubleshooting de Deploy

### Conflitos de Porta

**Problema**: Erro "address already in use" ao subir containers

**Solução**: Verificar e alterar portas no `.env`:

```bash
# 1. Identificar porta em conflito
ss -tlnp | grep :3308
netstat -tlnp | grep :9090

# 2. Editar .env com portas livres
nano .env
# Alterar:
# FORWARD_DB_PORT=3308 (ou outra porta livre)
# FORWARD_REDIS_PORT=6380
# FORWARD_PHPMYADMIN_PORT=9090 (use porta > 9000)

# 3. Reiniciar containers
sail down
sail up -d
```

**Dica**: Docker Desktop/WSL costuma usar portas 3306, 6379, 8080-8090. Use portas > 9000 para evitar conflitos.

---

### Aplicar Mudanças do .env SEM Reiniciar Containers

**NÃO precisa** reiniciar containers! Use cache:

```bash
# Limpar e recriar cache de configuração
sail artisan config:clear
sail artisan config:cache

# Verificar que foi aplicado
sail artisan tinker --execute="echo config('app.locale');"
```

---

### ViteManifestNotFoundException

**Problema**: Erro sobre manifest.json não encontrado

**Solução**:

```bash
# Instalar dependências e compilar assets
sail npm install
sail npm run build

# Verificar que foi criado
ls -lh public/build/manifest.json
```

---

### Container com Status "Unhealthy"

**Problema**: Container sobe mas fica unhealthy

**Diagnóstico**:

```bash
# Ver logs do container problemático
sail logs laravel.test --tail=50
sail logs mysql --tail=50
sail logs redis --tail=20

# Verificar status de todos
docker ps --format "table {{.Names}}\t{{.Status}}"
```

**Solução**: Aguardar mais tempo (MySQL pode demorar 60s+) ou verificar healthcheck no docker-compose.yml

---

### Funcionalidade não Aparece (com código presente)

**Checklist de verificação**:

```bash
# 1. Limpar cache Laravel
sail artisan optimize:clear

# 2. Verificar permissões
sail artisan tinker --execute="echo Spatie\Permission\Models\Permission::pluck('name');"

# 3. Executar seeders de permissões
sail artisan db:seed --class=RolesAndPermissionsSeeder

# 4. Dar permissão ao usuário
sail artisan tinker --execute="
\$user = App\Models\User::find(USER_ID);
\$user->givePermissionTo('PERMISSION_NAME');
echo 'Permissão atribuída';
"

# 5. Verificar rotas registradas
sail artisan route:list --path=nome-rota

# 6. Limpar cache do navegador (Ctrl+Shift+R)
```

---

### Erro "permission denied" ao Parar Container

**Problema**: `cannot stop container: permission denied`

**Solução**: Use docker-compose diretamente:

```bash
# Em vez de:
# sail down  # ❌ Pode dar erro

# Use:
docker-compose down  # ✅ Funciona
```

Ou simplesmente não pare! Para aplicar mudanças do .env, use `config:cache`.

---

### Sincronização Container vs Host

**Verificar se código do host está no container**:

```bash
# 1. Verificar volume montado
docker inspect eventospro-laravel.test-1 --format='{{range .Mounts}}{{.Source}} -> {{.Destination}}{{println}}{{end}}'
# Deve mostrar: /caminho/do/projeto -> /var/www/html

# 2. Comparar arquivo
ls -la app/Http/Controllers/SeuController.php
sail bash -c "ls -la /var/www/html/app/Http/Controllers/SeuController.php"
# Timestamps devem ser idênticos
```

---

## 📚 Recursos Adicionais

- [Documentação Oficial do Laravel Sail](https://laravel.com/docs/sail)
- [Docker Compose Reference](https://docs.docker.com/compose/)
- [Procedimento de Deploy Completo](./DEPLOY_PROCEDURE.md)
- [Lições Aprendidas](./LESSONS_LEARNED.md)
- [Configuração de Testes](./TESTING.md)
- [APIs dos Services](./SERVICES_API.md)

---

**Última Atualização**: 2025-11-04

**Lembre-se**: A consistência no uso do Laravel Sail garante que todos os desenvolvedores trabalhem no mesmo ambiente, evitando problemas de "funciona na minha máquina".