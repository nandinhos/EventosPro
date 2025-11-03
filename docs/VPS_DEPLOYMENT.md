# EventosPro - Guia de Deploy em VPS Otimizado

## 📋 Especificações do VPS

- **CPU**: 2 vCPU
- **RAM**: 4GB
- **Disco**: 50GB
- **OS**: Ubuntu 22.04+ LTS

## 🎯 Arquitetura de Produção

```
┌─────────────────────────────────────────┐
│           VPS (4GB RAM)                 │
├─────────────────────────────────────────┤
│  nginx (80MB)         ← Web Server      │
│  app (768MB-1.5GB)    ← PHP-FPM + Worker│
│  mysql (1-1.5GB)      ← Database        │
│  redis (128-256MB)    ← Cache/Queue     │
├─────────────────────────────────────────┤
│  Total: ~2-3GB RAM utilizada            │
└─────────────────────────────────────────┘
```

## 🚀 Setup Inicial no VPS

### 1. Preparar o Servidor

```bash
# Atualizar sistema
sudo apt update && sudo apt upgrade -y

# Instalar Docker e Docker Compose
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo usermod -aG docker $USER

# Instalar Docker Compose
sudo apt install docker-compose-plugin -y

# Instalar Node.js (para builds locais)
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt install -y nodejs

# Instalar curl (se não estiver instalado)
sudo apt install curl -y

# Relogar para aplicar grupo docker
exit
# ssh novamente
```

### 2. Clonar o Repositório

```bash
# Criar diretório de projetos
mkdir -p ~/projects
cd ~/projects

# Clonar repositório
git clone https://github.com/seu-usuario/EventosPro.git
cd EventosPro
```

### 3. Configurar Ambiente

```bash
# Copiar arquivo de ambiente de produção
cp .env.production.example .env

# Editar configurações (IMPORTANTE!)
nano .env
```

**Configurações obrigatórias no `.env`:**

```env
APP_NAME="EventosPro"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://seu-dominio.com

# Gerar uma key segura (execute depois com Docker)
APP_KEY=

# Database (use senhas fortes!)
DB_DATABASE=eventospro_production
DB_USERNAME=eventospro_user
DB_PASSWORD=SUA_SENHA_SEGURA_AQUI

# Mail (configure seu provedor SMTP)
MAIL_MAILER=smtp
MAIL_HOST=smtp.seuservidor.com
MAIL_PORT=587
MAIL_USERNAME=seu-email@dominio.com
MAIL_PASSWORD=sua-senha-smtp
MAIL_FROM_ADDRESS="noreply@seu-dominio.com"
```

### 4. Build e Start dos Containers

```bash
# Build da aplicação (primeira vez)
docker compose -f docker-compose.production.yml build --no-cache

# Subir containers
docker compose -f docker-compose.production.yml up -d

# Verificar se estão rodando
docker compose -f docker-compose.production.yml ps
```

### 5. Configurar Aplicação Laravel

```bash
# Gerar APP_KEY
docker compose -f docker-compose.production.yml exec app php artisan key:generate

# Rodar migrations
docker compose -f docker-compose.production.yml exec app php artisan migrate --force

# Criar usuário admin (se tiver seeder)
docker compose -f docker-compose.production.yml exec app php artisan db:seed --class=AdminUserSeeder --force

# Otimizar Laravel para produção
docker compose -f docker-compose.production.yml exec app php artisan config:cache
docker compose -f docker-compose.production.yml exec app php artisan route:cache
docker compose -f docker-compose.production.yml exec app php artisan view:cache

# Criar link simbólico para storage
docker compose -f docker-compose.production.yml exec app php artisan storage:link
```

### 6. Configurar Permissões

```bash
# Dar permissões corretas para storage e cache
sudo chown -R 1000:1000 storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### 7. Verificar Saúde dos Serviços

```bash
# Health check de todos os containers
docker compose -f docker-compose.production.yml ps

# Logs da aplicação
docker compose -f docker-compose.production.yml logs app

# Logs do Nginx
docker compose -f docker-compose.production.yml logs nginx

# Verificar queue worker
docker compose -f docker-compose.production.yml exec app supervisorctl status

# Testar aplicação
curl http://localhost/health
```

## 🔄 Processo de Atualização (Git Pull)

```bash
cd ~/projects/EventosPro

# 1. Pull das mudanças
git pull origin main

# 2. Rebuild dos containers (se Dockerfile mudou)
docker compose -f docker-compose.production.yml build

# 3. Restart dos containers
docker compose -f docker-compose.production.yml up -d

# 4. Rodar migrations (se houver)
docker compose -f docker-compose.production.yml exec app php artisan migrate --force

# 5. Limpar e recachear
docker compose -f docker-compose.production.yml exec app php artisan config:clear
docker compose -f docker-compose.production.yml exec app php artisan cache:clear
docker compose -f docker-compose.production.yml exec app php artisan config:cache
docker compose -f docker-compose.production.yml exec app php artisan route:cache
docker compose -f docker-compose.production.yml exec app php artisan view:cache

# 6. Restart do queue worker
docker compose -f docker-compose.production.yml exec app supervisorctl restart laravel-worker:*

# 7. Verificar logs
docker compose -f docker-compose.production.yml logs --tail=50 app
```

## 📊 Monitoramento e Manutenção

### Verificar Uso de Recursos

```bash
# Uso de recursos dos containers
docker stats

# Espaço em disco
df -h
docker system df

# Logs do supervisor (queue worker)
docker compose -f docker-compose.production.yml exec app tail -f /var/log/supervisor/laravel-worker.log
```

### Limpeza de Recursos

```bash
# Limpar imagens antigas
docker image prune -a -f

# Limpar volumes não usados
docker volume prune -f

# Limpar containers parados
docker container prune -f

# Limpeza completa (CUIDADO! Remove tudo não usado)
docker system prune -a --volumes -f
```

### Backup do Banco de Dados

```bash
# Backup manual
docker compose -f docker-compose.production.yml exec mysql mysqldump \
  -u eventospro_user -p eventospro_production > backup-$(date +%Y%m%d).sql

# Restaurar backup
docker compose -f docker-compose.production.yml exec -T mysql mysql \
  -u eventospro_user -p eventospro_production < backup-20250103.sql
```

### Logs da Aplicação

```bash
# Logs do Laravel (via Pail)
docker compose -f docker-compose.production.yml exec app php artisan pail

# Logs do Nginx
docker compose -f docker-compose.production.yml logs -f nginx

# Logs do MySQL
docker compose -f docker-compose.production.yml logs -f mysql

# Logs do Queue Worker
docker compose -f docker-compose.production.yml exec app tail -f /var/log/supervisor/laravel-worker.log
```

## 🔒 Segurança

### Configurar Firewall (UFW)

```bash
# Instalar UFW
sudo apt install ufw -y

# Permitir SSH (IMPORTANTE!)
sudo ufw allow 22/tcp

# Permitir HTTP e HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Ativar firewall
sudo ufw enable

# Verificar status
sudo ufw status
```

### SSL/HTTPS com Let's Encrypt (Certbot)

```bash
# Instalar Certbot
sudo apt install certbot -y

# Gerar certificado (modo standalone - para antes o nginx)
docker compose -f docker-compose.production.yml stop nginx

sudo certbot certonly --standalone -d seu-dominio.com -d www.seu-dominio.com

# Certificados ficam em: /etc/letsencrypt/live/seu-dominio.com/

# Atualizar nginx.conf para usar SSL (descomentar seção HTTPS)
# Montar certificados no docker-compose:
# volumes:
#   - /etc/letsencrypt:/etc/nginx/ssl:ro

# Restart do nginx
docker compose -f docker-compose.production.yml start nginx

# Renovação automática (cron)
sudo crontab -e
# Adicionar:
# 0 3 * * * certbot renew --quiet && docker compose -f ~/projects/EventosPro/docker-compose.production.yml restart nginx
```

## 🐛 Troubleshooting

### Container não inicia

```bash
# Ver logs detalhados
docker compose -f docker-compose.production.yml logs app

# Rebuild forçado
docker compose -f docker-compose.production.yml down
docker compose -f docker-compose.production.yml build --no-cache
docker compose -f docker-compose.production.yml up -d
```

### Erro de permissões

```bash
# Corrigir permissões do storage
docker compose -f docker-compose.production.yml exec app chown -R appuser:appuser /var/www/html/storage
docker compose -f docker-compose.production.yml exec app chmod -R 775 /var/www/html/storage
```

### Queue não está processando jobs

```bash
# Verificar status do worker
docker compose -f docker-compose.production.yml exec app supervisorctl status

# Restart do worker
docker compose -f docker-compose.production.yml exec app supervisorctl restart laravel-worker:*

# Ver logs do worker
docker compose -f docker-compose.production.yml exec app tail -f /var/log/supervisor/laravel-worker.log
```

### Banco de dados não conecta

```bash
# Verificar se MySQL está rodando
docker compose -f docker-compose.production.yml ps mysql

# Testar conexão
docker compose -f docker-compose.production.yml exec app php artisan tinker
# > DB::connection()->getPdo();

# Ver logs do MySQL
docker compose -f docker-compose.production.yml logs mysql
```

### Memória insuficiente

```bash
# Verificar uso de RAM
free -h
docker stats

# Ajustar limites no docker-compose.production.yml:
# - Reduzir pm.max_children no php-fpm.conf
# - Reduzir innodb_buffer_pool_size no mysql.cnf
# - Reduzir worker processes
```

## 📈 Otimizações Pós-Deploy

### 1. Ativar OPcache JIT (já configurado)
- Verifica se está ativo: `docker compose -f docker-compose.production.yml exec app php -i | grep jit`

### 2. Usar Redis para tudo
- Cache: `CACHE_STORE=redis`
- Sessions: `SESSION_DRIVER=redis`
- Queue: `QUEUE_CONNECTION=redis`

### 3. Configurar Laravel Horizon (opcional)
```bash
composer require laravel/horizon
php artisan horizon:install
# Descomente seção horizon no supervisord.conf
```

### 4. Compressão Gzip (já configurado no nginx.conf)
- Reduz bandwidth em ~70%

## 📋 Checklist de Deploy

- [ ] VPS com Ubuntu instalado
- [ ] Docker e Docker Compose instalados
- [ ] Repositório clonado
- [ ] `.env` configurado com senhas fortes
- [ ] Containers buildados e rodando
- [ ] `APP_KEY` gerada
- [ ] Migrations executadas
- [ ] Laravel otimizado (config:cache, route:cache, view:cache)
- [ ] Permissões corretas em storage/
- [ ] Firewall configurado
- [ ] SSL/HTTPS configurado (produção)
- [ ] Backup automático configurado
- [ ] Monitoramento configurado

## 🔗 Recursos Adicionais

- Laravel Documentation: https://laravel.com/docs
- Docker Optimization: https://docs.docker.com/config/containers/resource_constraints/
- Filament Admin: https://filamentphp.com/docs

---

## 🆘 Suporte

Para problemas, consulte:
1. Logs da aplicação: `docker compose logs app`
2. Laravel logs: `storage/logs/laravel.log`
3. Supervisor logs: `docker compose exec app tail -f /var/log/supervisor/supervisord.log`
