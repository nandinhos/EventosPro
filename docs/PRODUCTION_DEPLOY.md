# 🚀 EventosPro - Guia de Deploy em PRODUCTION

> **ATENÇÃO**: Este é o ambiente LIVE. Siga todos os passos com cuidado!

---

## ⚠️ Pré-Requisitos Obrigatórios

Antes de fazer deploy em produção:

- [x] ✅ Deploy em STAGING realizado com sucesso
- [x] ✅ Todos os testes passaram em staging
- [x] ✅ Sem erros críticos nos logs de staging
- [x] ✅ Performance validada em staging
- [x] ✅ Feature aprovada pelo responsável
- [x] ✅ Backup recente disponível
- [x] ✅ Horário apropriado (baixo tráfego, se possível)

**Se algum item acima NÃO está marcado, NÃO prossiga!**

---

## 🚀 OPÇÃO 1: Deploy Automático com Backup (Recomendado)

### Passo 1: SSH no Servidor de Produção

```bash
ssh user@production-server.com
# OU
ssh user@IP.DO.PRODUCTION
```

### Passo 2: Executar Script de Deploy

```bash
cd ~/projects/EventosPro
./scripts/deploy-production.sh
```

**O script fará automaticamente:**
1. ✅ Verificação de pré-requisitos
2. ✅ **Backup automático do banco de dados**
3. ✅ Git pull da branch main
4. ✅ Rebuild dos containers (zero-downtime)
5. ✅ Run migrations (com confirmação)
6. ✅ Clear + cache configs
7. ✅ Restart graceful do queue worker
8. ✅ Health checks pós-deploy
9. ✅ Monitoramento de logs (2 minutos)
10. ✅ **Rollback automático se algo falhar**

### Passo 3: Monitorar Deploy

```bash
# O script já mostra logs, mas você pode acompanhar em outra janela:
watch -n 2 'docker stats --no-stream'

# Ver logs em tempo real
docker compose -f docker-compose.production.yml logs -f app
```

### Passo 4: Validação Pós-Deploy

```bash
# Health check
curl https://seu-dominio.com/health

# Verificar versão deployada
docker compose -f docker-compose.production.yml exec app php artisan --version

# Ver commit atual
git log -1 --oneline
```

**✅ Deploy automático concluído!** Prossiga para [Validação Final](#-valida%C3%A7%C3%A3o-final)

---

## 🛠️ OPÇÃO 2: Deploy Manual Seguro (Passo a Passo)

> Use esta opção se preferir controle total ou se o script falhar

### Passo 1: Conectar e Preparar

```bash
ssh user@production-server.com
cd ~/projects/EventosPro

# Verificar ambiente
docker compose -f docker-compose.production.yml ps
git status
git branch
```

### Passo 2: Backup OBRIGATÓRIO do Banco de Dados

```bash
# Criar diretório de backups (se não existir)
mkdir -p ~/backups/eventospro

# Backup completo do banco
docker compose -f docker-compose.production.yml exec mysql mysqldump \
  -u ${DB_USERNAME} -p${DB_PASSWORD} --single-transaction --quick --lock-tables=false \
  ${DB_DATABASE} | gzip > ~/backups/eventospro/production-$(date +%Y%m%d-%H%M%S).sql.gz

# Verificar backup criado
ls -lh ~/backups/eventospro/ | tail -1

echo "✅ Backup criado com sucesso"
```

**⚠️ NÃO prossiga sem backup!**

### Passo 3: Notificar Usuários (Opcional)

```bash
# Se tiver manutenção programada, ativar modo maintenance
docker compose -f docker-compose.production.yml exec app php artisan down \
  --message="Sistema em atualização. Voltamos em 5 minutos." \
  --retry=60

echo "⚠️ Modo manutenção ATIVADO"
```

### Passo 4: Atualizar Código

```bash
# Verificar commit atual (antes)
echo "Commit ANTES:"
git log -1 --oneline

# Stash qualquer mudança local (se houver)
git stash

# Pull das mudanças
git pull origin main

# Verificar novo commit (depois)
echo "Commit DEPOIS:"
git log -1 --oneline

# Ver diferença de commits
git log --oneline HEAD@{1}..HEAD

echo "✅ Código atualizado"
```

### Passo 5: Rebuild Containers (Zero-Downtime)

```bash
# Rebuild APENAS o container app (mantém outros rodando)
docker compose -f docker-compose.production.yml build app

# Restart gradual (zero-downtime)
docker compose -f docker-compose.production.yml up -d --no-deps app

# Aguardar container ficar healthy
sleep 30

# Verificar saúde
docker compose -f docker-compose.production.yml ps app

echo "✅ Containers atualizados"
```

### Passo 6: Executar Migrations (COM CUIDADO!)

```bash
# VER migrations pendentes ANTES de executar
docker compose -f docker-compose.production.yml exec app php artisan migrate:status

# Verificar SQL que será executado (dry-run)
docker compose -f docker-compose.production.yml exec app php artisan migrate --pretend

# Se tudo OK, executar migrations
docker compose -f docker-compose.production.yml exec app php artisan migrate --force

echo "✅ Migrations executadas"
```

**⚠️ Se migration falhar:**
```bash
# NÃO entre em pânico!
# Verificar erro específico
docker compose -f docker-compose.production.yml logs app | tail -50

# Se necessário, fazer rollback da migration
docker compose -f docker-compose.production.yml exec app php artisan migrate:rollback --step=1
```

### Passo 7: Otimizar Laravel

```bash
# Limpar todos os caches antigos
docker compose -f docker-compose.production.yml exec app php artisan optimize:clear

# Recachear para máxima performance
docker compose -f docker-compose.production.yml exec app php artisan config:cache
docker compose -f docker-compose.production.yml exec app php artisan route:cache
docker compose -f docker-compose.production.yml exec app php artisan view:cache
docker compose -f docker-compose.production.yml exec app php artisan event:cache

# OU usar script do composer
docker compose -f docker-compose.production.yml exec app composer production:optimize

echo "✅ Laravel otimizado"
```

### Passo 8: Restart Graceful do Queue Worker

```bash
# Restart suave (termina jobs em andamento antes de reiniciar)
docker compose -f docker-compose.production.yml exec app supervisorctl restart laravel-worker:*

# Aguardar workers reiniciarem
sleep 5

# Verificar status
docker compose -f docker-compose.production.yml exec app supervisorctl status

echo "✅ Queue workers reiniciados"
```

### Passo 9: Desativar Modo Manutenção

```bash
# Reativar aplicação
docker compose -f docker-compose.production.yml exec app php artisan up

echo "✅ Aplicação reativada"
```

### Passo 10: Monitorar Logs (5-10 minutos)

```bash
# Logs em tempo real (Ctrl+C para sair)
docker compose -f docker-compose.production.yml logs -f app

# Em outra janela, monitorar recursos
docker stats
```

**✅ Deploy manual concluído!**

---

## ✔️ Validação Final

### 1. Health Checks Automáticos

```bash
# Health da aplicação
curl -f https://seu-dominio.com/health || echo "❌ FALHOU"

# Health dos containers
docker compose -f docker-compose.production.yml ps | grep -E "(Up|healthy)"

# Health do banco
docker compose -f docker-compose.production.yml exec mysql mysqladmin ping -u root -p
```

### 2. Verificar Serviços

```bash
# Status de todos os containers
docker compose -f docker-compose.production.yml ps

# Esperado: TODOS "Up" e "healthy"
# nginx       Up (healthy)
# app         Up (healthy)
# mysql       Up (healthy)
# redis       Up (healthy)
```

### 3. Testar Aplicação no Browser

```
https://seu-dominio.com
```

**Checklist crítico:**
- [ ] ✅ Página inicial carrega
- [ ] ✅ SSL/HTTPS funcionando
- [ ] ✅ Login funciona
- [ ] ✅ Dashboard acessível
- [ ] ✅ Nova feature está visível e funciona
- [ ] ✅ Funcionalidades críticas funcionam (pagamentos, relatórios, etc.)
- [ ] ✅ Sem erros 500 ou 404

### 4. Verificar Queue e Jobs

```bash
# Supervisor status
docker compose -f docker-compose.production.yml exec app supervisorctl status

# Esperado: Todos RUNNING
# laravel-worker:laravel-worker_00   RUNNING   pid 123, uptime 0:05:30
# laravel-worker:laravel-worker_01   RUNNING   pid 124, uptime 0:05:30
# laravel-scheduler                  RUNNING   pid 125, uptime 0:05:30

# Ver fila de jobs
docker compose -f docker-compose.production.yml exec app php artisan queue:monitor redis

# Processar um job para testar
docker compose -f docker-compose.production.yml exec app php artisan queue:work redis --once
```

### 5. Monitorar Recursos

```bash
# Uso de CPU e RAM
docker stats --no-stream

# Esperado (4GB RAM total):
# nginx:  < 100MB
# app:    500MB - 1.2GB
# mysql:  800MB - 1.5GB
# redis:  100MB - 256MB
# TOTAL:  < 3.2GB (deixa 800MB+ livre)

# Espaço em disco
df -h
docker system df

# Esperado: < 40GB usados (de 50GB)
```

### 6. Verificar Logs de Erro

```bash
# Laravel logs (últimos 5 minutos)
docker compose -f docker-compose.production.yml exec app tail -100 storage/logs/laravel.log | grep -E "(ERROR|CRITICAL|EMERGENCY)"

# Esperado: Nenhum erro crítico novo

# Nginx error logs
docker compose -f docker-compose.production.yml logs nginx | grep -i error | tail -20

# Esperado: Nenhum erro 5xx

# PHP-FPM logs
docker compose -f docker-compose.production.yml logs app | grep -i "error" | tail -20
```

### 7. Teste de Performance

```bash
# Tempo de resposta da home
time curl https://seu-dominio.com

# Esperado: < 1.5 segundos

# Teste de carga leve (10 requests)
for i in {1..10}; do
  curl -o /dev/null -s -w "%{time_total}\n" https://seu-dominio.com
done | awk '{sum+=$1} END {print "Média: " sum/NR " segundos"}'

# Esperado: Média < 1 segundo
```

### 8. Verificar Redis

```bash
# Conectar ao Redis
docker compose -f docker-compose.production.yml exec redis redis-cli

# Dentro do redis-cli:
> INFO stats
> DBSIZE
# Esperado: Chaves de cache presentes

> GET_some_cache_key  # Testar uma chave específica
> exit
```

---

## 🔄 Rollback Completo (Se Necessário)

### Quando fazer rollback?

- ❌ Erro crítico impedindo uso do sistema
- ❌ Migration causou corrupção de dados
- ❌ Performance degradou significativamente
- ❌ Funcionalidade crítica quebrou

### Procedimento de Rollback

```bash
echo "⚠️ INICIANDO ROLLBACK"

# 1. Ativar modo manutenção
docker compose -f docker-compose.production.yml exec app php artisan down

# 2. Ver commits recentes
git log --oneline -5

# 3. Voltar para commit anterior (ANTES do deploy problemático)
git reset --hard HEAD~1
# OU para commit específico:
# git reset --hard <hash-do-commit-estavel>

# 4. Rebuild containers com código anterior
docker compose -f docker-compose.production.yml build app
docker compose -f docker-compose.production.yml up -d --no-deps app

# 5. Rollback de migrations (se aplicável)
docker compose -f docker-compose.production.yml exec app php artisan migrate:rollback --step=1

# 6. Limpar e recachear
docker compose -f docker-compose.production.yml exec app composer production:clear
docker compose -f docker-compose.production.yml exec app composer production:optimize

# 7. Restart workers
docker compose -f docker-compose.production.yml exec app supervisorctl restart laravel-worker:*

# 8. Desativar modo manutenção
docker compose -f docker-compose.production.yml exec app php artisan up

# 9. Verificar
curl https://seu-dominio.com/health
docker compose -f docker-compose.production.yml logs --tail=50 app

echo "✅ ROLLBACK CONCLUÍDO"
```

### Rollback do Banco de Dados (EXTREMO)

```bash
# ⚠️ Use APENAS se migration corrompeu dados

# 1. Listar backups
ls -lh ~/backups/eventospro/

# 2. Restaurar backup mais recente
gunzip < ~/backups/eventospro/production-YYYYMMDD-HHMMSS.sql.gz | \
  docker compose -f docker-compose.production.yml exec -T mysql mysql \
  -u ${DB_USERNAME} -p${DB_PASSWORD} ${DB_DATABASE}

# 3. Verificar dados
docker compose -f docker-compose.production.yml exec app php artisan tinker
>>> App\Models\User::count();
>>> exit

echo "✅ Banco restaurado"
```

---

## 📊 Monitoramento Pós-Deploy

### Primeiras 24 Horas

```bash
# A cada 30 minutos, verificar:

# 1. Logs de erro
docker compose -f docker-compose.production.yml logs --since 30m app | grep -i error

# 2. Uso de recursos
docker stats --no-stream

# 3. Queue backlog
docker compose -f docker-compose.production.yml exec app php artisan queue:monitor redis

# 4. Uptime
docker compose -f docker-compose.production.yml ps
```

### Ferramentas de Monitoramento (Recomendado)

```bash
# Configurar alertas (opcional)
# - UptimeRobot: https://uptimerobot.com (free)
# - Pingdom
# - New Relic
# - Sentry (erros de aplicação)
```

---

## 🐛 Troubleshooting de Produção

### Erro 500 Internal Server Error

```bash
# 1. Ver logs do Laravel
docker compose -f docker-compose.production.yml exec app tail -50 storage/logs/laravel.log

# 2. Ver logs do PHP-FPM
docker compose -f docker-compose.production.yml logs app | tail -100

# 3. Verificar permissões
docker compose -f docker-compose.production.yml exec app ls -la storage/

# 4. Verificar .env
docker compose -f docker-compose.production.yml exec app php artisan config:show

# 5. Testar conexão banco
docker compose -f docker-compose.production.yml exec app php artisan tinker
>>> DB::connection()->getPdo();
```

### Site muito lento

```bash
# 1. Verificar uso de recursos
docker stats

# 2. Verificar queries lentas do MySQL
docker compose -f docker-compose.production.yml exec mysql mysql -u root -p -e \
  "SELECT * FROM information_schema.processlist WHERE time > 5;"

# 3. Verificar se cache está funcionando
docker compose -f docker-compose.production.yml exec redis redis-cli INFO stats

# 4. Verificar se OPcache está ativo
docker compose -f docker-compose.production.yml exec app php -i | grep opcache

# 5. Profiling (Laravel Debugbar - CUIDADO EM PRODUÇÃO!)
# Ativar temporariamente apenas para seu IP
```

### Queue worker morreu

```bash
# 1. Ver logs do supervisor
docker compose -f docker-compose.production.yml exec app tail -50 /var/log/supervisor/laravel-worker.log

# 2. Restart manual
docker compose -f docker-compose.production.yml exec app supervisorctl restart laravel-worker:*

# 3. Se continuar morrendo, aumentar memory_limit
# Editar docker/production/php-fpm.conf
# php_admin_value[memory_limit] = 512M  (ao invés de 256M)

# 4. Rebuild
docker compose -f docker-compose.production.yml up -d --build app
```

### Banco de dados cheio

```bash
# 1. Ver tamanho das tabelas
docker compose -f docker-compose.production.yml exec mysql mysql -u root -p -e \
  "SELECT table_name, ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)' \
   FROM information_schema.TABLES \
   WHERE table_schema = '${DB_DATABASE}' \
   ORDER BY (data_length + index_length) DESC \
   LIMIT 10;"

# 2. Limpar tabelas antigas (CUIDADO!)
# - failed_jobs antigos
# - logs antigos
# - sessions antigas

# 3. Otimizar tabelas
docker compose -f docker-compose.production.yml exec mysql mysqlcheck -u root -p --optimize ${DB_DATABASE}
```

---

## 📋 Checklist Final de Produção

### Após Deploy

- [ ] ✅ Health check passou
- [ ] ✅ Todos os containers UP e healthy
- [ ] ✅ Site acessível via HTTPS
- [ ] ✅ Login funciona
- [ ] ✅ Nova feature ativa e funcionando
- [ ] ✅ Queue worker processando
- [ ] ✅ Sem erros críticos nos logs
- [ ] ✅ Performance normal (< 3GB RAM, < 70% CPU)
- [ ] ✅ Backup do banco criado
- [ ] ✅ Monitoramento ativo (próximas 24h)

### Comunicação

- [ ] ✅ Time notificado sobre deploy
- [ ] ✅ Changelog atualizado (se aplicável)
- [ ] ✅ Usuários avisados de novas features (se aplicável)

---

## 🤖 Instruções para Agente de IA

Quando executar deploy em production:

1. **Confirmar TRÊS VEZES:**
   ```
   ⚠️ ATENÇÃO: Você está prestes a fazer deploy em PRODUCTION!

   Confirmações necessárias:
   1. Staging foi validado? [S/N]
   2. Tem backup recente? [S/N]
   3. Confirma deploy em PRODUCTION? [S/N]
   ```

2. **Executar apenas se todas as respostas forem "S":**
   ```bash
   ssh user@production-server "cd ~/projects/EventosPro && ./scripts/deploy-production.sh"
   ```

3. **Monitorar ativamente:**
   - Health checks a cada 30s por 5 minutos
   - Logs em tempo real
   - Uso de recursos

4. **Reportar detalhadamente:**
   ```
   ✅ Deploy em PRODUCTION concluído!

   Commit: abc1234 - "feat: Add feature X"
   Backup: production-20250103-143022.sql.gz (543MB)
   Migrations: 3 executadas com sucesso

   Status:
   - Containers: Todos UP e healthy
   - Health: OK (200)
   - Logs: Sem erros críticos
   - RAM: 2.8GB / 4GB (70%)
   - CPU: 45%

   Monitorando por mais 10 minutos...
   ```

5. **Se houver QUALQUER erro:**
   - 🛑 **PARAR imediatamente**
   - Mostrar logs completos
   - Perguntar: "Fazer rollback automático? [S/N]"
   - Se S: Executar `./scripts/deploy-production.sh rollback`
   - NÃO tentar "consertar" sozinho em produção

---

**Deploy em Production concluído! 🚀**

Monitore a aplicação nas próximas 24 horas.
