# Procedimento de Deploy - EventosPro

## Portas padrão usadas (alteradas para evitar conflitos locais)

Se você tem outros containers locais, estas são as portas recomendadas por este projeto para evitar conflitos. Elas estão configuradas no arquivo `.env` do projeto (variáveis: APP_PORT, VITE_PORT, FORWARD_DB_PORT, FORWARD_PHPMYADMIN_PORT).

- HTTP da aplicação: 8081 (host) -> 80 (container)
- Vite (dev server): 5174 (host) -> 5174 (container)
- MySQL (host): 3307 -> 3306 (container)
- phpMyAdmin (host): 8089 -> 80 (container)

Se alguma dessas portas conflitar com outro serviço, edite o arquivo `.env` e ajuste as variáveis acima antes de subir os containers.

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

**Data de criação:** $(date)  
**Versão:** 1.0  
**Status:** Testado e validado  
**Ambiente:** Laravel Sail + Docker