# EventosPro - Guia Completo de Instalação

> **Processo Padronizado de Setup para Desenvolvimento Local**
>
> Este guia documenta o processo completo e replicável para configurar o EventosPro em qualquer ambiente de desenvolvimento.

---

## Índice

1. [Pré-requisitos](#pré-requisitos)
2. [Instalação Rápida (Automatizada)](#instalação-rápida-automatizada)
3. [Instalação Manual (Passo a Passo)](#instalação-manual-passo-a-passo)
4. [Configuração do Laravel Boost MCP](#configuração-do-laravel-boost-mcp)
5. [Diferenças: Local vs Produção](#diferenças-local-vs-produção)
6. [Comandos Úteis](#comandos-úteis)
7. [Troubleshooting](#troubleshooting)

---

## Pré-requisitos

### Software Necessário

- **Git** (para clonar o repositório)
- **Docker** e **Docker Compose** (para Laravel Sail)
- **Opcional**: Claude Desktop (para integração MCP)

### Verificar Instalações

```bash
# Verificar Git
git --version

# Verificar Docker
docker --version
docker-compose --version

# Verificar permissões Docker (deve rodar sem sudo)
docker ps
```

---

## Instalação Rápida (Automatizada)

### Opção 1: Script Automatizado

```bash
# 1. Clonar repositório
git clone https://github.com/nandinhos/EventosPro.git
cd EventosPro

# 2. Executar script de setup
chmod +x scripts/setup-fresh-install.sh
./scripts/setup-fresh-install.sh
```

O script automatiza todo o processo e executa:
- Cópia e configuração do `.env`
- Instalação de dependências (Composer + NPM)
- Geração de `APP_KEY`
- Inicialização dos containers Docker
- Migrations e seeders
- Build dos assets Vite

---

## Instalação Manual (Passo a Passo)

### 1. Clonar o Repositório

```bash
git clone https://github.com/nandinhos/EventosPro.git
cd EventosPro
```

### 2. Configurar Arquivo `.env`

```bash
# Copiar .env.example
cp .env.example .env

# Editar variáveis (se necessário)
nano .env
```

**Variáveis importantes:**

```env
# Database (Laravel Sail - Docker)
DB_HOST=mysql          # Para Sail, use 'mysql' (não 127.0.0.1)
DB_DATABASE=laravel
DB_USERNAME=root
DB_PASSWORD=           # Vazio para Sail

# Docker User (importante!)
WWWUSER=1000          # Seu UID (execute: id -u)
WWWGROUP=1000         # Seu GID (execute: id -g)
```

### 3. Instalar Dependências do Composer

```bash
# IMPORTANTE: Use docker-compose para a primeira instalação
docker-compose run --rm laravel.test composer install
```

**Por quê?** O vendor ainda não existe, então `./vendor/bin/sail` não está disponível.

### 4. Gerar Chave da Aplicação

```bash
docker-compose run --rm laravel.test php artisan key:generate
```

### 5. Criar Alias para Laravel Sail (Opcional)

```bash
# Adicionar ao ~/.bashrc ou ~/.zshrc
alias sail='./vendor/bin/sail'
source ~/.bashrc  # ou source ~/.zshrc
```

Agora você pode usar `sail` ao invés de `./vendor/bin/sail`.

### 6. Subir Containers Docker

```bash
./vendor/bin/sail up -d
# ou, se criou o alias:
sail up -d
```

**Containers criados:**
- `eventospro-mysql-1` (MySQL 8.0)
- `eventospro-redis-1` (Redis Alpine)
- `eventospro-laravel.test-1` (Aplicação Laravel)
- `eventospro-phpmyadmin-1` (PHPMyAdmin - http://localhost:8080)

### 7. Executar Migrations e Seeders

```bash
./vendor/bin/sail artisan migrate:fresh --seed
```

**Dados criados:**
- 30 artistas de música eletrônica
- 30 bookers/agências
- 50 gigs (eventos)
- 162 pagamentos
- 177 custos de gig
- 93 tags
- 36 centros de custo
- Roles e permissions (Spatie)

### 8. Instalar Dependências NPM

```bash
./vendor/bin/sail npm install
```

### 9. Build dos Assets Vite

```bash
# Para desenvolvimento (watch mode)
./vendor/bin/sail npm run dev

# Para produção (minificado)
./vendor/bin/sail npm run build
```

### 10. Acessar a Aplicação

Abra no navegador: **http://localhost**

---

## Configuração do Laravel Boost MCP

### O que é Laravel Boost MCP?

Laravel Boost MCP é uma ferramenta que permite ao Claude Desktop interagir diretamente com sua aplicação Laravel, oferecendo:

- `search-docs` - Buscar documentação Laravel/Filament/Livewire
- `tinker` - Executar código PHP/Laravel em tempo real
- `database-query` - Consultar banco de dados
- `list-artisan-commands` - Listar comandos Artisan disponíveis

### Instalação

**Já está instalado!** O Laravel Boost foi incluído durante o `composer install`.

### Configurar no Claude Desktop

1. **Localizar arquivo de configuração do Claude Desktop:**

   - **macOS**: `~/Library/Application Support/Claude/claude_desktop_config.json`
   - **Linux**: `~/.config/Claude/claude_desktop_config.json`
   - **Windows**: `%APPDATA%\Claude\claude_desktop_config.json`

2. **Adicionar configuração:**

```json
{
  "mcpServers": {
    "eventospro-boost": {
      "command": "php",
      "args": ["artisan", "boost:mcp"],
      "cwd": "/caminho/absoluto/para/EventosPro"
    }
  }
}
```

**Substitua `/caminho/absoluto/para/EventosPro`** pelo caminho completo do projeto.

3. **Reiniciar Claude Desktop**

4. **Testar:**

No Claude Desktop, pergunte:
> "Liste os comandos Artisan disponíveis no EventosPro"

Se funcionar, verá a lista de comandos!

---

## Diferenças: Local vs Produção

| Item | **Local (Sail)** | **Produção (VPS)** |
|------|-----------------|-------------------|
| **Ambiente** | Docker (Sail) | LEMP Stack |
| **DB_HOST** | `mysql` | `127.0.0.1` |
| **Servidor Web** | Sail (nginx interno) | Nginx |
| **PHP** | Container Docker | PHP-FPM 8.4 |
| **Backup Script** | `scripts/backup-database-local.sh` | `scripts/backup-database.sh` |
| **Container MySQL** | `eventospro-mysql-1` | Instalação direta |
| **Comando Artisan** | `sail artisan ...` | `php artisan ...` |

### Scripts de Backup

**Local:**
```bash
./scripts/backup-database-local.sh
```
- Usa `./vendor/bin/sail exec mysql`
- Salva em `backups/eventospro-local-YYYYMMDD-HHMMSS.sql.gz`

**Produção:**
```bash
./scripts/backup-database.sh
```
- Usa `docker exec eventospro-mysql-1` (VPS)
- Salva em `backups/eventospro-backup-YYYYMMDD-HHMMSS.sql.gz`

---

## Comandos Úteis

### Laravel Sail

```bash
# Subir containers
sail up -d

# Parar containers
sail down

# Parar e remover volumes (⚠️ apaga banco!)
sail down -v

# Ver logs em tempo real
sail artisan pail

# Acessar bash do container Laravel
sail bash

# Acessar MySQL CLI
sail mysql

# Executar testes
sail artisan test

# Limpar caches
sail artisan cache:clear
sail artisan config:clear
sail artisan view:clear
```

### Git

```bash
# Ver status
git status

# Criar branch de feature
git checkout -b feature/nome-da-feature

# Commitar mudanças
git add .
git commit -m "feat: descrição da feature"

# Push para GitHub
git push origin main
```

### NPM

```bash
# Desenvolvimento (watch mode)
sail npm run dev

# Build produção
sail npm run build

# Verificar vulnerabilidades
sail npm audit
```

---

## Troubleshooting

### Problema: `docker: permission denied`

**Solução:**
```bash
# Adicionar usuário ao grupo docker
sudo usermod -aG docker $USER

# Reiniciar sessão
newgrp docker
```

### Problema: `SQLSTATE[HY000] [2002] Connection refused`

**Causa:** `.env` com `DB_HOST=127.0.0.1` ao invés de `DB_HOST=mysql`

**Solução:**
```bash
# Editar .env
nano .env

# Alterar:
DB_HOST=mysql

# Reiniciar containers
sail down && sail up -d
```

### Problema: `SQLSTATE[HY000] [1045] Access denied for user`

**Causa:** Senha do banco incorreta ou usuário não existe

**Solução:**
```bash
# Resetar banco (⚠️ apaga dados!)
sail down -v
sail up -d
sail artisan migrate:fresh --seed
```

### Problema: Porta 3306 já em uso

**Causa:** MySQL local rodando e conflitando com container

**Solução:**
```bash
# Parar MySQL local
sudo systemctl stop mysql

# Ou alterar porta no docker-compose.yml:
ports:
  - '3307:3306'  # Muda porta local para 3307
```

### Problema: `vendor/bin/sail: No such file or directory`

**Causa:** Composer install não foi executado

**Solução:**
```bash
docker-compose run --rm laravel.test composer install
```

### Problema: Build Vite falha com erro de memória

**Solução:**
```bash
# Aumentar limite de memória Node.js
sail npm run build -- --max_old_space_size=4096
```

---

## Próximos Passos

Após instalação completa:

1. ✅ Acessar aplicação: http://localhost
2. ✅ Acessar PHPMyAdmin: http://localhost:8080
3. ✅ Configurar Laravel Boost MCP no Claude Desktop
4. ✅ Executar testes: `sail artisan test`
5. ✅ Criar primeiro backup: `./scripts/backup-database-local.sh`

---

## Suporte

- **Documentação Completa**: `/docs`
- **Issues GitHub**: https://github.com/nandinhos/EventosPro/issues
- **Laravel Docs**: https://laravel.com/docs
- **Filament Docs**: https://filamentphp.com/docs

---

**Última atualização:** 2025-11-08
**Versão:** 1.0.0
