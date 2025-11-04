# Procedimento de Deploy - EventosPro

## ⚡ Deploy Rápido (TL;DR)

Para deploy em uma nova VPS, execute este comando único:

```bash
# Clone, configure e suba a aplicação em uma linha
git clone https://github.com/nandinhos/EventosPro.git && \
cd EventosPro && \
cp .env.example .env && \
# IMPORTANTE: Edite o .env para ajustar portas se necessário (veja seção abaixo)
./vendor/bin/sail up -d && \
./vendor/bin/sail artisan key:generate && \
./vendor/bin/sail artisan migrate && \
./vendor/bin/sail artisan db:seed --class=RolesAndPermissionsSeeder && \
./vendor/bin/sail npm install && \
./vendor/bin/sail npm run build && \
./vendor/bin/sail artisan optimize
```

**Após execução**: Acesse http://SEU_IP_VPS

---

## Portas padrão usadas (IMPORTANTE: Evitar conflitos)

**Problema Comum**: Docker Desktop/WSL ou outros serviços podem ocupar portas baixas (3306, 6379, 8080-8090).

**Solução**: Use portas altas no `.env` antes de subir os containers:

```env
# Configuração recomendada para VPS (editar .env)
APP_PORT=80                      # HTTP da aplicação
VITE_PORT=5173                   # Vite dev server
FORWARD_DB_PORT=3308             # MySQL (NÃO use 3306/3307 - conflitam com WSL)
FORWARD_REDIS_PORT=6380          # Redis (NÃO use 6379 - pode conflitar)
FORWARD_PHPMYADMIN_PORT=9090     # phpMyAdmin (use porta > 9000)
```

**Dica**: Se trabalha com Docker Desktop no WSL, sempre use portas **> 9000** para serviços auxiliares (phpMyAdmin, Mailhog, etc.)

### Como verificar conflitos de porta ANTES de subir:

```bash
# Verificar se porta está livre
ss -tlnp | grep :3308
netstat -tlnp | grep :9090

# Se retornar algo, a porta está em uso - escolha outra!
```

## Guia Completo de Deploy da Aplicação EventosPro

Este documento contém o procedimento testado e validado para fazer o deploy correto da aplicação EventosPro, baseado na resolução bem-sucedida de problemas de permissões e configuração.

---

## 1. Pré-requisitos

Antes de iniciar, certifique-se de que o sistema possui:

- **PHP 8.3** com extensões necessárias
- **Composer** instalado
- **Node.js** e **npm** instalados
- **Docker** e **Docker Compose** instalados
- **Git** configurado

---

## 2. Clonagem do Repositório

```bash
# Navegar para o diretório de projetos
cd /home/nandodev/projects

# Clonar o repositório
git clone https://github.com/nandinhos/EventosPro.git

# Entrar no diretório do projeto
cd EventosPro
```

---

## 3. Configuração do Ambiente

### 3.1 Criar arquivo de configuração
```bash
# Copiar arquivo de exemplo para .env
cp .env.example .env
```

### 3.2 Configurar permissões iniciais
```bash
# Aplicar permissões corretas aos diretórios críticos
chmod -R 775 storage bootstrap/cache
```

---

## 4. Instalação de Dependências PHP

### 4.1 Verificar e instalar extensões PHP necessárias
```bash
# Atualizar repositórios
sudo apt update

# Instalar extensões PHP obrigatórias
sudo apt install -y php8.3-gd php8.3-bcmath
```

### 4.2 Instalar dependências do Composer
```bash
# Instalar dependências PHP
composer install
```

---

## 5. Instalação de Dependências Frontend

```bash
# Instalar dependências Node.js
npm install
```

---

## 6. Configuração da Aplicação Laravel

### 6.1 Gerar chave da aplicação
```bash
# Gerar APP_KEY
php artisan key:generate
```

### 6.2 Configurar banco de dados no .env
Editar o arquivo `.env` com as configurações corretas para Sail:

```env
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=eventospro
DB_USERNAME=sail
DB_PASSWORD=password
```

---

## 7. Configuração de Permissões (Script Automatizado)

### 7.1 Criar script de correção de permissões
Criar o arquivo `fix-permissions.sh`:

```bash
#!/bin/bash

# Script para corrigir permissões do Laravel no EventosPro
# Funciona tanto em ambiente Docker (Sail) quanto local

echo "🔧 Corrigindo permissões do Laravel..."

# Verificar se estamos em ambiente Sail
if docker ps | grep -q "eventospro-laravel.test"; then
    echo "📦 Ambiente Docker detectado - usando Sail"
    
    # Corrigir ownership para sail:sail dentro do container
    ./vendor/bin/sail exec laravel.test chown -R sail:sail /var/www/html/storage
    ./vendor/bin/sail exec laravel.test chown -R sail:sail /var/www/html/bootstrap/cache
    
    # Aplicar permissões 775
    ./vendor/bin/sail exec laravel.test chmod -R 775 /var/www/html/storage
    ./vendor/bin/sail exec laravel.test chmod -R 775 /var/www/html/bootstrap/cache
    
    # Corrigir permissões específicas do log
    ./vendor/bin/sail exec laravel.test touch /var/www/html/storage/logs/laravel.log
    ./vendor/bin/sail exec laravel.test chown sail:sail /var/www/html/storage/logs/laravel.log
    ./vendor/bin/sail exec laravel.test chmod 775 /var/www/html/storage/logs/laravel.log
    
    echo "✅ Permissões corrigidas no ambiente Docker"
else
    echo "🖥️  Ambiente local detectado"
    
    # Aplicar permissões no ambiente local
    sudo chown -R $USER:$USER storage bootstrap/cache
    chmod -R 775 storage bootstrap/cache
    
    # Corrigir permissões do log se existir
    if [ -f "storage/logs/laravel.log" ]; then
        sudo chown $USER:$USER storage/logs/laravel.log
        chmod 775 storage/logs/laravel.log
    fi
    
    echo "✅ Permissões corrigidas no ambiente local"
fi

echo "🎉 Script de permissões executado com sucesso!"
```

### 7.2 Tornar o script executável
```bash
chmod +x fix-permissions.sh
```

---

## 8. Inicialização do Ambiente Docker

```bash
# Iniciar containers em background
./vendor/bin/sail up -d

# Aguardar inicialização completa (cerca de 30 segundos)
sleep 30
```

---

## 9. Aplicação de Permissões no Docker

```bash
# Executar script de correção de permissões
./fix-permissions.sh
```

---

## 10. Configuração do Banco de Dados

### 10.1 Verificar e configurar banco
```bash
# Conectar ao MySQL e configurar banco
./vendor/bin/sail mysql -e "
SHOW DATABASES;
CREATE DATABASE IF NOT EXISTS eventospro;
GRANT ALL PRIVILEGES ON eventospro.* TO 'sail'@'%';
FLUSH PRIVILEGES;
"
```

### 10.2 Executar migrações
```bash
# Executar migrações do banco de dados
./vendor/bin/sail artisan migrate
```

---

## 11. Compilação dos Assets Frontend

```bash
# Compilar assets para produção
./vendor/bin/sail npm run build
```

---

## 12. Verificação Final

### 12.1 Testar a aplicação
```bash
# Verificar se a aplicação responde corretamente
curl -I http://localhost
```

**Resultado esperado:** `HTTP/1.1 200 OK`

### 12.2 Verificar containers
```bash
# Verificar status dos containers
./vendor/bin/sail ps
```

---

## 13. Comandos Úteis para Manutenção

### Reiniciar ambiente completo
```bash
# Parar containers
./vendor/bin/sail down

# Iniciar novamente
./vendor/bin/sail up -d

# Aguardar e aplicar permissões
sleep 30
./fix-permissions.sh
```

### Limpar cache da aplicação
```bash
./vendor/bin/sail artisan cache:clear
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan view:clear
```

### Recompilar assets
```bash
./vendor/bin/sail npm run build
```

---

## 14. Solução de Problemas Comuns

### Erro 500 - Internal Server Error
1. Verificar se os assets foram compilados: `./vendor/bin/sail npm run build`
2. Verificar permissões: `./fix-permissions.sh`
3. Limpar cache: `./vendor/bin/sail artisan cache:clear`

### Erro de conexão com banco de dados
1. Verificar se containers estão rodando: `./vendor/bin/sail ps`
2. Verificar configurações do `.env` (DB_HOST=mysql, DB_USERNAME=sail, etc.)
3. Recriar banco: executar comandos da seção 10.1

### Problemas de permissão
1. Executar: `./fix-permissions.sh`
2. Se persistir, reiniciar containers e executar novamente

---

## 15. Estrutura de Arquivos Importantes

```
EventosPro/
├── .env                    # Configurações do ambiente
├── fix-permissions.sh      # Script de correção de permissões
├── docker-compose.yml      # Configuração Docker
├── storage/               # Diretório de armazenamento (permissões críticas)
├── bootstrap/cache/       # Cache do Laravel (permissões críticas)
└── public/build/          # Assets compilados
```

---

## 16. Notas Importantes

- **Sempre executar** `./fix-permissions.sh` após inicializar containers
- **Compilar assets** é obrigatório para evitar erro 500
- **Aguardar inicialização** completa do MySQL antes de executar migrações
- **Usar configurações específicas** do Sail no arquivo `.env`
- **Manter script de permissões** atualizado para futuras necessidades

---

## 17. Troubleshooting de Deploy Inicial em VPS

### Problema: ViteManifestNotFoundException

**Sintoma**: Erro "Vite manifest not found at: /var/www/html/public/build/manifest.json"

**Causa**: Assets frontend não foram compilados

**Solução**:
```bash
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
# Verificar que manifest foi criado
ls -lh public/build/manifest.json
```

---

### Problema: Erro "address already in use" ao subir containers

**Sintoma**: `Error: failed to bind host port... address already in use`

**Causa**: Porta já está sendo usada por outro serviço (Docker Desktop/WSL comum)

**Solução**:
1. Identificar qual porta está em conflito:
```bash
# Exemplo: verificar porta 3307
ss -tlnp | grep :3307
```

2. Editar `.env` e mudar para porta mais alta:
```env
# Exemplo: mudar MySQL de 3307 para 3308
FORWARD_DB_PORT=3308

# phpMyAdmin: sempre usar porta > 9000
FORWARD_PHPMYADMIN_PORT=9090
```

3. Parar e reiniciar:
```bash
./vendor/bin/sail down
./vendor/bin/sail up -d
```

---

### Problema: Container marcado como "unhealthy"

**Sintoma**: `docker ps` mostra container com status "unhealthy"

**Causa**: Healthcheck falhando ou dependências (MySQL/Redis) não prontas

**Solução**:
1. Verificar logs do container:
```bash
./vendor/bin/sail logs laravel.test --tail=50
./vendor/bin/sail logs mysql --tail=50
```

2. Aguardar mais tempo (MySQL pode demorar 60s+ no primeiro boot)

3. Se persistir, verificar `docker-compose.yml`:
```yaml
healthcheck:
  start_period: 60s  # Aumentar se necessário
  retries: 10        # Aumentar tentativas
```

---

### Problema: Funcionalidade não aparece (código existe mas não funciona)

**Sintomas**:
- Código está no repositório
- Rotas registradas
- Mas funcionalidade não aparece no menu ou não funciona

**Verificações em ordem**:

1. **Cache do Laravel**:
```bash
./vendor/bin/sail artisan optimize:clear
```

2. **Permissões de usuário**:
```bash
# Verificar permissões existentes
./vendor/bin/sail artisan tinker --execute="echo Spatie\Permission\Models\Permission::pluck('name');"

# Executar seeder de permissões
./vendor/bin/sail artisan db:seed --class=RolesAndPermissionsSeeder

# Dar permissão ao usuário (substituir USER_ID e PERMISSION)
./vendor/bin/sail artisan tinker --execute="
\$user = App\Models\User::find(USER_ID);
\$user->givePermissionTo('PERMISSION_NAME');
echo 'OK';
"
```

3. **Sincronização container vs host**:
```bash
# Verificar volume montado
docker inspect eventospro-laravel.test-1 --format='{{range .Mounts}}{{.Source}} -> {{.Destination}}{{println}}{{end}}'

# Deve mostrar: /home/USER/projects/EventosPro -> /var/www/html
```

4. **Cache do navegador**:
- Abrir DevTools (F12)
- Clicar com botão direito no refresh
- Selecionar "Empty Cache and Hard Reload"

---

### Problema: "permission denied" ao parar containers

**Sintoma**: `cannot stop container: permission denied`

**Causa**: Container criado com usuário/grupo diferente

**Solução**:
```bash
# Não use sail down, use docker-compose diretamente
docker-compose down

# Ou simplesmente não pare! Para aplicar mudanças do .env:
./vendor/bin/sail artisan config:cache
```

---

### Problema: Mudanças no .env não são aplicadas

**Sintoma**: Alterou .env mas aplicação usa valores antigos

**Solução**: **NÃO precisa** reiniciar containers!
```bash
# Limpar e recriar cache de configuração
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan config:cache

# Verificar que foi aplicado
./vendor/bin/sail artisan tinker --execute="echo config('app.locale');"
```

---

## 18. Checklist de Verificação Pós-Deploy

Após deploy, verificar:

- [ ] `docker ps` mostra todos containers "healthy"
- [ ] `curl -I http://localhost` retorna HTTP 200
- [ ] `ls public/build/manifest.json` existe
- [ ] Login funciona
- [ ] Menu mostra todas funcionalidades esperadas
- [ ] phpMyAdmin acessível (se necessário): http://IP:9090

---

**Data de criação**: 2025-10-22
**Data de atualização**: 2025-11-04
**Versão**: 2.0
**Status**: Testado e validado em múltiplas VPS
**Ambiente**: Laravel Sail + Docker