# 🧪 EventosPro - Guia de Deploy em STAGING

> **Servidor de Testes**: Ambiente "quase produção" para validar features antes de ir ao ar

---

## 📋 Informações do Servidor Staging

**Características:**
- 🖥️ VPS com recursos similares à produção
- 🔧 Docker otimizado (Alpine + Nginx + Redis)
- 🔒 APP_ENV=staging, APP_DEBUG=false
- 🌐 URL: `http://staging.seu-dominio.com` (ou IP)
- 📊 Dados: Cópia ou dados de teste

---

## 🚀 OPÇÃO 1: Deploy Automático (Recomendado)

### Passo 1: SSH no Servidor

```bash
ssh user@staging-server.com
# OU
ssh user@IP.DO.STAGING
```

### Passo 2: Executar Script

```bash
cd ~/projects/EventosPro
./scripts/deploy-staging.sh
```

**O script fará automaticamente:**
1. ✅ Git pull da branch main
2. ✅ Rebuild dos containers Docker
3. ✅ Run migrations
4. ✅ Clear + cache configs
5. ✅ Restart queue worker
6. ✅ Health checks
7. ✅ Mostra logs finais

### Passo 3: Validar Deploy

```bash
# Ver status dos containers
docker compose -f docker-compose.production.yml ps

# Testar aplicação
curl http://staging-server.com/health

# Ver logs em tempo real
docker compose -f docker-compose.production.yml logs -f app
```

**✅ Deploy concluído!** Prossiga para [Testes de Validação](#-testes-de-valida%C3%A7%C3%A3o)

---

## 🛠️ OPÇÃO 2: Deploy Manual (Passo a Passo)

### Passo 1: Conectar ao Servidor

```bash
ssh user@staging-server.com
cd ~/projects/EventosPro
```

### Passo 2: Backup Preventivo (Opcional mas Recomendado)

```bash
# Backup do banco de dados
docker compose -f docker-compose.production.yml exec mysql mysqldump \
  -u ${DB_USERNAME} -p${DB_PASSWORD} ${DB_DATABASE} \
  > ~/backups/staging-backup-$(date +%Y%m%d-%H%M%S).sql

echo "✅ Backup criado"
```

### Passo 3: Atualizar Código

```bash
# Ver branch e commit atual
git branch
git log -1 --oneline

# Pull das mudanças
git pull origin main

# Verificar novo commit
git log -1 --oneline

echo "✅ Código atualizado"
```

### Passo 4: Rebuild dos Containers

```bash
# Rebuild e restart
docker compose -f docker-compose.production.yml up -d --build

# Aguardar containers iniciarem (30-60s)
sleep 30

# Verificar status
docker compose -f docker-compose.production.yml ps

echo "✅ Containers atualizados"
```

### Passo 5: Executar Migrations

```bash
# Rodar migrations (se houver)
docker compose -f docker-compose.production.yml exec app php artisan migrate --force

# Verificar status das migrations
docker compose -f docker-compose.production.yml exec app php artisan migrate:status

echo "✅ Migrations executadas"
```

### Passo 6: Limpar e Recachear

```bash
# Limpar caches antigos
docker compose -f docker-compose.production.yml exec app php artisan config:clear
docker compose -f docker-compose.production.yml exec app php artisan cache:clear
docker compose -f docker-compose.production.yml exec app php artisan route:clear
docker compose -f docker-compose.production.yml exec app php artisan view:clear

# Recachear para performance
docker compose -f docker-compose.production.yml exec app php artisan config:cache
docker compose -f docker-compose.production.yml exec app php artisan route:cache
docker compose -f docker-compose.production.yml exec app php artisan view:cache

echo "✅ Caches otimizados"
```

### Passo 7: Restart Queue Worker

```bash
# Restart do worker (para pegar novo código)
docker compose -f docker-compose.production.yml exec app supervisorctl restart laravel-worker:*

# Verificar status
docker compose -f docker-compose.production.yml exec app supervisorctl status

echo "✅ Queue worker reiniciado"
```

### Passo 8: Verificar Logs

```bash
# Ver logs recentes
docker compose -f docker-compose.production.yml logs --tail=50 app

# Logs em tempo real (Ctrl+C para sair)
docker compose -f docker-compose.production.yml logs -f app
```

**✅ Deploy manual concluído!**

---

## ✔️ Testes de Validação

### 1. Health Check Básico

```bash
# Testar endpoint de health
curl http://staging-server.com/health

# Esperado: "healthy" ou resposta 200 OK
```

### 2. Verificar Serviços Docker

```bash
docker compose -f docker-compose.production.yml ps

# Esperado: Todos os serviços "Up"
# - nginx (Up)
# - app (Up, healthy)
# - mysql (Up, healthy)
# - redis (Up, healthy)
```

### 3. Testar Aplicação no Browser

```
http://staging-server.com

OU

http://IP_DO_SERVIDOR
```

**Checklist no browser:**
- [ ] Página inicial carrega
- [ ] Login funciona
- [ ] Dashboard acessível
- [ ] Nova feature aparece
- [ ] Sem erros visíveis

### 4. Verificar Queue Worker

```bash
# Status do supervisor
docker compose -f docker-compose.production.yml exec app supervisorctl status

# Esperado:
# laravel-worker:laravel-worker_00   RUNNING
# laravel-worker:laravel-worker_01   RUNNING
# laravel-scheduler                  RUNNING

# Testar processamento de job manual
docker compose -f docker-compose.production.yml exec app php artisan queue:work redis --once

# Esperado: "Processed: App\Jobs\SuaJob"
```

### 5. Verificar Logs de Erro

```bash
# Logs do Laravel (últimos 5 minutos)
docker compose -f docker-compose.production.yml exec app tail -f storage/logs/laravel.log

# Esperado: Sem errors críticos
# Warnings são aceitáveis, mas investigate
```

### 6. Testar Funcionalidade Específica

**Exemplo: Se você adicionou relatórios**

```bash
# Acessar no browser
http://staging-server.com/admin/relatorios

# Testar geração de PDF
# Clicar em "Gerar Relatório"
# Verificar que PDF baixa corretamente
```

### 7. Verificar Performance

```bash
# Uso de recursos
docker stats --no-stream

# Esperado:
# nginx:  < 100MB RAM
# app:    < 1GB RAM
# mysql:  < 1.5GB RAM
# redis:  < 256MB RAM
# Total:  < 3GB

# Tempo de resposta
time curl http://staging-server.com

# Esperado: < 1 segundo
```

### 8. Testar Redis Cache

```bash
# Conectar ao Redis
docker compose -f docker-compose.production.yml exec redis redis-cli

# Dentro do redis-cli:
> PING
# Esperado: PONG

> KEYS *
# Esperado: Lista de chaves em cache

> exit
```

---

## 🐛 Troubleshooting

### Erro: Container não sobe

```bash
# Ver logs detalhados
docker compose -f docker-compose.production.yml logs app

# Rebuild forçado sem cache
docker compose -f docker-compose.production.yml down
docker compose -f docker-compose.production.yml build --no-cache
docker compose -f docker-compose.production.yml up -d
```

### Erro: Migrations falharam

```bash
# Ver erro específico
docker compose -f docker-compose.production.yml exec app php artisan migrate --force

# Se erro de conexão MySQL:
docker compose -f docker-compose.production.yml exec app php artisan tinker
>>> DB::connection()->getPdo();

# Verificar credenciais no .env
docker compose -f docker-compose.production.yml exec app cat .env | grep DB_
```

### Erro: 502 Bad Gateway (Nginx)

```bash
# Verificar se PHP-FPM está rodando
docker compose -f docker-compose.production.yml exec app ps aux | grep php-fpm

# Verificar logs do Nginx
docker compose -f docker-compose.production.yml logs nginx

# Restart do app
docker compose -f docker-compose.production.yml restart app
```

### Erro: Cache não limpa

```bash
# Forçar clear de todos os caches
docker compose -f docker-compose.production.yml exec app php artisan optimize:clear

# Remover arquivos de cache manualmente
docker compose -f docker-compose.production.yml exec app rm -rf bootstrap/cache/*.php
docker compose -f docker-compose.production.yml exec app rm -rf storage/framework/cache/*
docker compose -f docker-compose.production.yml exec app rm -rf storage/framework/views/*

# Recriar caches
docker compose -f docker-compose.production.yml exec app php artisan config:cache
docker compose -f docker-compose.production.yml exec app php artisan route:cache
docker compose -f docker-compose.production.yml exec app php artisan view:cache
```

### Erro: Queue não processa jobs

```bash
# Verificar supervisor
docker compose -f docker-compose.production.yml exec app supervisorctl status

# Restart do worker
docker compose -f docker-compose.production.yml exec app supervisorctl restart laravel-worker:*

# Ver logs do worker
docker compose -f docker-compose.production.yml exec app tail -f /var/log/supervisor/laravel-worker.log

# Testar job manual
docker compose -f docker-compose.production.yml exec app php artisan queue:work redis --once
```

### Erro: Permissões (storage, cache)

```bash
# Corrigir permissões
docker compose -f docker-compose.production.yml exec app chown -R appuser:appuser /var/www/html/storage
docker compose -f docker-compose.production.yml exec app chown -R appuser:appuser /var/www/html/bootstrap/cache
docker compose -f docker-compose.production.yml exec app chmod -R 775 /var/www/html/storage
docker compose -f docker-compose.production.yml exec app chmod -R 775 /var/www/html/bootstrap/cache
```

---

## 🔄 Rollback (Reverter Deploy)

### Se algo deu muito errado:

```bash
# 1. Ver commits recentes
git log --oneline -5

# 2. Voltar para commit anterior
git checkout <hash-do-commit-anterior>

# 3. Rebuild containers
docker compose -f docker-compose.production.yml up -d --build

# 4. Executar migrations para baixo (se necessário)
docker compose -f docker-compose.production.yml exec app php artisan migrate:rollback

# 5. Limpar caches
docker compose -f docker-compose.production.yml exec app php artisan optimize:clear
docker compose -f docker-compose.production.yml exec app composer production:optimize

# 6. Restaurar banco (se fez backup)
docker compose -f docker-compose.production.yml exec -T mysql mysql \
  -u ${DB_USERNAME} -p${DB_PASSWORD} ${DB_DATABASE} \
  < ~/backups/staging-backup-YYYYMMDD-HHMMSS.sql
```

---

## 📊 Checklist de Validação Final

Antes de aprovar para produção:

### Funcionalidades

- [ ] ✅ Todas as páginas carregam sem erro
- [ ] ✅ Login/Logout funciona
- [ ] ✅ CRUD principal funciona (criar, ler, atualizar, deletar)
- [ ] ✅ Nova feature implementada funciona 100%
- [ ] ✅ Forms validam corretamente
- [ ] ✅ Upload de arquivos funciona (se aplicável)
- [ ] ✅ Geração de PDFs funciona (se aplicável)
- [ ] ✅ Export Excel funciona (se aplicável)

### Performance

- [ ] ✅ Páginas carregam em < 2 segundos
- [ ] ✅ Uso de RAM < 3GB total
- [ ] ✅ Uso de CPU < 70%
- [ ] ✅ Banco de dados responde rápido

### Jobs/Queue

- [ ] ✅ Queue worker está rodando
- [ ] ✅ Jobs são processados
- [ ] ✅ Scheduler está executando (cron)
- [ ] ✅ Emails são enviados (se aplicável)

### Logs e Monitoramento

- [ ] ✅ Sem errors críticos nos logs
- [ ] ✅ Warnings são compreensíveis/esperados
- [ ] ✅ Health check retorna OK
- [ ] ✅ Redis está respondendo

### Segurança

- [ ] ✅ APP_DEBUG=false
- [ ] ✅ APP_ENV=staging
- [ ] ✅ Não há dados sensíveis expostos
- [ ] ✅ Credenciais não commitadas

---

## ✅ Aprovação para Produção

**Se todos os itens acima estão OK:**

```
✅ STAGING VALIDADO

Próximo passo:
- Seguir guia: docs/PRODUCTION_DEPLOY.md
- Executar: ./scripts/deploy-production.sh
```

**Se encontrou bugs:**

```
❌ STAGING REPROVADO

Ações:
1. Fazer rollback em staging
2. Corrigir bugs localmente
3. Commit + push
4. Repetir deploy staging
5. Validar novamente
```

---

## 🤖 Instruções para Agente de IA

Quando executar deploy em staging:

1. **Confirmar antes:**
   - "Vou fazer deploy em STAGING. Confirma?"

2. **Executar:**
   ```bash
   ssh user@staging-server "cd ~/projects/EventosPro && ./scripts/deploy-staging.sh"
   ```

3. **Validar automaticamente:**
   - Health check
   - Logs (últimas 20 linhas)
   - Status dos containers

4. **Reportar:**
   ```
   ✅ Deploy em STAGING concluído!

   Status: Todos os containers UP
   Health: OK
   Logs: Sem erros críticos

   Pronto para testes manuais.
   ```

5. **Se houver erro:**
   - Mostrar logs completos
   - Sugerir troubleshooting
   - NÃO prosseguir para production

---

**Deploy em Staging completo! 🧪**

Valide todas as funcionalidades antes de prosseguir para produção.
