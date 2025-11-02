# Meu Fluxo de Deploy - EventosPro

> **Guia Personalizado** adaptado ao workflow: WSL Local → GitHub → VPS via SSH

---

## 📋 Índice

1. [Visão Geral do Fluxo](#visão-geral-do-fluxo)
2. [Scripts Disponíveis](#scripts-disponíveis)
3. [Cenários Comuns](#cenários-comuns)
4. [Configuração Inicial](#configuração-inicial)
5. [Troubleshooting](#troubleshooting)

---

## Visão Geral do Fluxo

### ❌ ANTES (Fluxo Manual e Trabalhoso)

```
┌─────────────────┐
│ WSL Local (Dev) │
│ Docker          │
└────────┬────────┘
         │ git commit & push
         ↓
┌─────────────────┐
│ GitHub Repo     │
└────────┬────────┘
         │ SSH + git pull
         ↓
┌─────────────────┐
│ VPS             │
│ ./deploy.sh     │
└────────┬────────┘
         │
         ↓ ⚠️ PROBLEMA: DB desatualizado
         │
┌─────────────────────────────┐
│ phpMyAdmin (Manual)         │
│ 1. Exportar DB do WSL       │
│ 2. Download arquivo         │
│ 3. Upload para VPS          │
│ 4. Importar no phpMyAdmin   │
│ 5. Testar manualmente       │
└─────────────────────────────┘
    ↓
⏱️  Tempo: 15-20 minutos
😰 Estresse: Alto (migration pode quebrar)
```

**Problemas**:
- ❌ Exportar/importar via phpMyAdmin é lento
- ❌ Sem backup automático antes de mudanças
- ❌ Difícil reverter se algo der errado
- ❌ Migrations podem quebrar dados em produção
- ❌ Testar migrations é manual e sujeito a erros

---

### ✅ AGORA (Fluxo Automatizado e Seguro)

```
┌─────────────────────────────────────┐
│ WSL Local (Dev) com Docker          │
│ 1. Desenvolvimento normal            │
│ 2. ./scripts/test-migration-locally │ ← 🆕 Testar ANTES de commit
│ 3. ./scripts/backup-database        │ ← 🆕 Backup local
│ 4. git commit & push                │
└──────────────┬──────────────────────┘
               │
               ↓
┌─────────────────────────────────────┐
│ GitHub Repository                    │
└──────────────┬──────────────────────┘
               │ SSH + git pull
               ↓
┌─────────────────────────────────────┐
│ VPS                                  │
│ ./deploy.sh --production             │ ← 🆕 Backup automático
│   ├─ Backup automático do DB        │
│   ├─ Migrations seguras (--force)   │
│   ├─ Composer install                │
│   ├─ NPM build                       │
│   ├─ Cache configs                   │
│   └─ Health check                    │
└─────────────┬───────────────────────┘
              │
              ↓
        ✅ Aplicação Atualizada
        (Dados preservados!)

Se algo der errado:
    └─ ./scripts/restore-database.sh (rollback em 30s)
```

**Melhorias**:
- ✅ Backup automático antes de QUALQUER migration
- ✅ Migrations testadas localmente antes de commitar
- ✅ Sem phpMyAdmin (tudo por linha de comando)
- ✅ Rollback rápido se algo quebrar
- ✅ Transferência de DB local→VPS via script SCP

---

## Scripts Disponíveis

### 🔧 Scripts de Produção (Use no dia-a-dia)

| Script | Quando Usar | Tempo | Descrição |
|--------|-------------|-------|-----------|
| `./scripts/backup-database.sh` | Antes de mudanças importantes | ~10s | Cria backup comprimido do banco local |
| `./scripts/restore-database.sh` | Quando precisar reverter | ~30s | Restaura backup (interativo com menu) |
| `./scripts/test-migration-locally.sh` | **SEMPRE** antes de commitar migration | ~1min | Testa migration localmente com rollback automático |
| `./scripts/sync-database-to-vps.sh` | Quando quiser copiar DB local→VPS | ~2-3min | **Substitui phpMyAdmin** para sync de dados |
| `./deploy.sh --production` | Deploy seguro na VPS | ~3-5min | Deploy completo com backup automático |

### 🧪 Scripts de Teste (Use uma vez para validar)

| Script | Quando Usar | Tempo | Descrição |
|--------|-------------|-------|-----------|
| `./scripts/test-backup-system.sh` | Uma vez (validação inicial) | ~5min | Testa todo o sistema de backup/restore |

---

## Cenários Comuns

### 🎯 Cenário 1: Desenvolvimento Normal (SEM mudança de schema)

**Situação**: Alterou código, views, controllers, mas NÃO mexeu no banco

```bash
# 1. No WSL (Local) - Desenvolvimento
vim app/Http/Controllers/GigController.php
# ... faz alterações ...

# 2. Testar localmente
./vendor/bin/sail up -d
./vendor/bin/sail test

# 3. Commit e push
git add .
git commit -m "feat: melhorar lógica do GigController"
git push

# 4. Na VPS (conectar via SSH no VSCode Fork)
cd /var/www/eventospro
git pull
./deploy.sh --production

# Resultado:
# ✅ Backup automático criado
# ✅ Código atualizado
# ✅ Assets rebuilded
# ✅ Sem migrations = dados preservados
```

**Tempo total**: ~2-3 minutos
**Risco**: Zero (backup automático)

---

### 🎯 Cenário 2: Deploy COM Migration (Mudança de Schema)

**Situação**: Criou/alterou migration que muda estrutura do banco

```bash
# 1. No WSL (Local) - Criar migration
./vendor/bin/sail artisan make:migration add_status_to_gigs_table
# ... edita a migration ...

# 2. ⚠️ IMPORTANTE: Testar ANTES de commitar
./scripts/test-migration-locally.sh

# O script vai:
#   ✅ Criar backup automático
#   ✅ Rodar a migration
#   ✅ Testar se a aplicação ainda funciona
#   ❌ Se falhar: restaura backup automaticamente
#   ✅ Se passar: seguro para commitar!

# 3. Se o teste passou, commitar com confiança
git add .
git commit -m "feat: adicionar campo status na tabela gigs"
git push

# 4. Na VPS
cd /var/www/eventospro
git pull
./deploy.sh --production

# O que acontece automaticamente na VPS:
# ├─ 💾 Backup do DB antes da migration
# ├─ 🚀 Migration rodada com --force (segura)
# ├─ 📦 Composer install
# ├─ 🎨 NPM build
# ├─ ⚡ Cache configs
# └─ ✅ Health check

# 5. Verificar aplicação
curl http://seu-vps:8081

# Se algo der errado:
./scripts/restore-database.sh  # Rollback em 30 segundos
```

**Tempo total**: ~5-8 minutos
**Risco**: Baixíssimo (migration testada localmente + backup automático na VPS)

---

### 🎯 Cenário 3: Sincronizar DB Local → VPS

**Situação**: Quer copiar dados de desenvolvimento do WSL para testar na VPS

> **Substitui**: Exportar/Importar manual via phpMyAdmin ✨

```bash
# CONFIGURAÇÃO INICIAL (primeira vez apenas):
# Editar o arquivo: scripts/sync-database-to-vps.sh
# OU exportar variáveis de ambiente:

export VPS_HOST='168.231.96.81'
export VPS_USER='devuser'
export VPS_PORT='22'
export VPS_PROJECT_PATH='/var/www/eventospro'

# EXECUTAR SINCRONIZAÇÃO:
./scripts/sync-database-to-vps.sh

# O que acontece automaticamente:
# ├─ 🔌 Testa conexão SSH com VPS
# ├─ 💾 Cria backup do DB local (WSL)
# ├─ 🛡️  Cria backup de segurança do DB da VPS
# ├─ 📤 Transfere via SCP (rápido!)
# ├─ ♻️  Restaura na VPS
# ├─ 🧹 Limpa caches
# └─ ✔️  Valida sincronização

# Resultado:
# ✅ VPS tem mesmos dados do WSL
# ⏱️  10x mais rápido que phpMyAdmin
```

**Tempo total**: ~2-3 minutos (vs 15-20 min com phpMyAdmin)
**Risco**: Zero (backup da VPS antes de sobrescrever)

---

### 🎯 Cenário 4: Rollback (Algo Deu Errado)

**Situação**: Deploy quebrou a aplicação ou migration causou problemas

```bash
# Na VPS (via SSH)
cd /var/www/eventospro
./scripts/restore-database.sh

# Interface interativa mostra:
# ┌─────────────────────────────────────────────────────┐
# │ 📋 Backups disponíveis:                             │
# │ 1. eventospro-backup-20251030-180000.sql.gz (5 min) │
# │ 2. eventospro-backup-20251030-120000.sql.gz (6h)    │
# │ 3. eventospro-backup-20251029-180000.sql.gz (1 dia) │
# │ ...                                                  │
# └─────────────────────────────────────────────────────┘

# Digite: 1 (backup mais recente antes do deploy)
# Confirme: SIM

# Resultado:
# ✅ Database restaurado
# ✅ Caches limpos automaticamente
# ✅ Aplicação volta a funcionar

# Depois: corrigir o problema no código e fazer novo deploy
git revert HEAD  # ou fix manual
./deploy.sh --production
```

**Tempo total**: ~1 minuto
**Downtime**: < 30 segundos

---

### 🎯 Cenário 5: Testar Migration Complexa

**Situação**: Migration muito complexa, quer garantir que não vai quebrar nada

```bash
# 1. No WSL Local - Criar backup preventivo
./scripts/backup-database.sh

# 2. Criar ambiente de teste paralelo (opcional)
# Duplicar container:
docker cp eventospro-mysql-1:/var/lib/mysql /tmp/mysql-backup

# 3. Testar migration múltiplas vezes
./scripts/test-migration-locally.sh  # Tentativa 1
# ... ajusta migration ...
./scripts/test-migration-locally.sh  # Tentativa 2
# ... até funcionar perfeitamente

# 4. Quando estiver 100% confiante:
git commit -m "feat: migration complexa testada"
git push

# 5. Na VPS com confiança total
./deploy.sh --production
```

**Benefício**: Zero risco de quebrar produção

---

## Configuração Inicial

### 1️⃣ Configurar SSH na VPS (Uma Vez)

Para usar `sync-database-to-vps.sh`, configure SSH sem senha:

```bash
# No WSL Local
# Gerar chave SSH (se não tiver)
ssh-keygen -t ed25519 -C "seu-email@example.com"

# Copiar chave pública para VPS
ssh-copy-id -p 22 usuario@seu-vps-ip

# Testar conexão (deve entrar sem pedir senha)
ssh usuario@seu-vps-ip
```

### 2️⃣ Configurar Variáveis do Script de Sync

**Opção A**: Editar diretamente o script

```bash
vim scripts/sync-database-to-vps.sh

# Alterar estas linhas:
VPS_HOST="${VPS_HOST:-SEU-IP-OU-DOMINIO}"
VPS_USER="${VPS_USER:-SEU-USUARIO-SSH}"
VPS_PORT="${VPS_PORT:-22}"
VPS_PROJECT_PATH="${VPS_PROJECT_PATH:-/var/www/eventospro}"
```

**Opção B**: Usar variáveis de ambiente (mais flexível)

```bash
# Adicionar no ~/.bashrc ou ~/.zshrc:
export VPS_HOST='192.168.1.100'
export VPS_USER='ubuntu'
export VPS_PORT='22'
export VPS_PROJECT_PATH='/var/www/eventospro'

# Recarregar:
source ~/.bashrc

# Agora pode usar:
./scripts/sync-database-to-vps.sh
```

### 3️⃣ Validar Sistema (Primeira Vez)

```bash
# No WSL Local
./scripts/test-backup-system.sh

# Deve mostrar:
# ✅ Todos os testes passaram!
# 🎯 Sistema de backup está PRONTO PARA USO!
```

---

## Checklist de Deploy para VPS

Use este checklist antes de cada deploy na VPS:

```
PRÉ-DEPLOY
──────────
❌ → ✅  Código testado localmente
❌ → ✅  Migrations testadas com test-migration-locally.sh
❌ → ✅  Tests passando: ./vendor/bin/sail test
❌ → ✅  Código commitado e pushed para GitHub
❌ → ✅  Backup local criado (opcional, mas recomendado)

DEPLOY
──────
❌ → ✅  SSH na VPS
❌ → ✅  cd /var/www/eventospro
❌ → ✅  git pull origin main
❌ → ✅  ./deploy.sh --production
❌ → ✅  Aguardar: "✅ Production deployment completed"

PÓS-DEPLOY
──────────
❌ → ✅  Verificar backup foi criado: ls -lh backups/
❌ → ✅  Testar aplicação: curl http://localhost:8081
❌ → ✅  Verificar logs: ./vendor/bin/sail artisan pail --timeout=60
❌ → ✅  Testar funcionalidades críticas manualmente
❌ → ✅  Monitorar por 5-10 minutos
```

---

## Troubleshooting

### ❌ Problema: Migration quebrou a aplicação na VPS

**Sintoma**: Deploy completou mas aplicação não funciona

**Solução**:
```bash
# 1. Rollback imediato
./scripts/restore-database.sh
# Escolher: backup mais recente ANTES do deploy

# 2. Verificar o erro
./vendor/bin/sail artisan pail | grep ERROR

# 3. Reverter código se necessário
git log --oneline -5  # Ver últimos commits
git revert HEAD       # Reverter último commit

# 4. Corrigir localmente
# ... fix no WSL ...
./scripts/test-migration-locally.sh

# 5. Novo deploy quando corrigido
./deploy.sh --production
```

---

### ❌ Problema: Backup da VPS está falhando

**Sintoma**: `./scripts/backup-database.sh` retorna erro de permissão

**Causa**: Usuário MySQL não tem privilégios necessários

**Solução**:
```bash
# Na VPS, conceder privilégios:
./vendor/bin/sail mysql -e "GRANT RELOAD, LOCK TABLES, PROCESS, REPLICATION CLIENT ON *.* TO 'user'@'%'; FLUSH PRIVILEGES;"

# Ou usando root:
docker exec eventospro-mysql-1 mysql -u root -ppassword -e "GRANT RELOAD, LOCK TABLES, PROCESS, REPLICATION CLIENT ON *.* TO 'user'@'%'; FLUSH PRIVILEGES;"

# Testar novamente:
./scripts/backup-database.sh
```

---

### ❌ Problema: sync-database-to-vps.sh não consegue conectar

**Sintoma**: "Não foi possível conectar via SSH"

**Soluções**:

**1. Verificar SSH manual**:
```bash
ssh -p 22 usuario@seu-vps-ip
# Deve entrar SEM pedir senha
```

**2. Se pede senha, configurar chave**:
```bash
ssh-copy-id -p 22 usuario@seu-vps-ip
```

**3. Verificar firewall**:
```bash
# Na VPS:
sudo ufw status
sudo ufw allow 22/tcp  # Se necessário
```

**4. Verificar configurações no script**:
```bash
vim scripts/sync-database-to-vps.sh
# Confirmar: VPS_HOST, VPS_USER, VPS_PORT estão corretos
```

---

### ❌ Problema: Deploy lento (> 10 minutos)

**Causa**: Composer ou NPM baixando muitas dependências

**Soluções**:

**1. Usar deploy rápido para pequenas mudanças**:
```bash
./deploy.sh --quick
# Pula: composer install, npm install
# Útil para mudanças só de código
```

**2. Apenas rebuild de assets**:
```bash
./deploy.sh --assets
# Útil quando só mudou CSS/JS
```

**3. Otimizar Composer**:
```bash
# Usar cache do Composer:
./vendor/bin/sail composer install --no-interaction --prefer-dist --optimize-autoloader
```

---

### ❌ Problema: Banco de dados muito grande (> 1GB)

**Sintoma**: Backup ou restore demora muito

**Soluções**:

**1. Comprimir melhor**:
```bash
# Editar backup-database.sh, trocar gzip por:
pigz -9  # Paralelo, mais rápido
# Ou:
bzip2    # Compressão melhor
```

**2. Backup incremental** (Fase 2 do plano):
- Implementar backup apenas de mudanças
- Usar binlog do MySQL

**3. Transferência paralela**:
```bash
# Usar rsync em vez de SCP:
rsync -avz -e "ssh -p 22" backup.sql.gz usuario@vps:/path/
```

---

### ❌ Problema: phpMyAdmin ainda é necessário?

**Resposta**: NÃO! ✨

**Substituições**:

| Tarefa antiga (phpMyAdmin) | Novo método (Script) |
|----------------------------|----------------------|
| Exportar DB local | `./scripts/backup-database.sh` |
| Importar DB na VPS | `./scripts/sync-database-to-vps.sh` |
| Restaurar backup antigo | `./scripts/restore-database.sh` |
| Ver tabelas/dados | `./vendor/bin/sail artisan tinker` ou `./vendor/bin/sail mysql laravel` |

**Exemplo de consultas via linha de comando**:
```bash
# Ver registros:
./vendor/bin/sail mysql laravel -e "SELECT * FROM users LIMIT 10;"

# Contar registros:
./vendor/bin/sail mysql laravel -e "SELECT COUNT(*) FROM gigs;"

# Tinker (mais poderoso):
./vendor/bin/sail artisan tinker
>>> User::count()
>>> Gig::latest()->take(5)->get()
```

---

## Dicas Avançadas

### 💡 Automatizar Deploy Completo

Criar alias no `~/.bashrc`:

```bash
alias deploy-eventospro='
    echo "🚀 Deploy EventosPro" && \
    ./scripts/test-migration-locally.sh && \
    git push && \
    ssh usuario@vps "cd /var/www/eventospro && git pull && ./deploy.sh --production"
'
```

Uso:
```bash
deploy-eventospro  # Faz tudo automaticamente!
```

---

### 💡 Monitorar Logs em Tempo Real

```bash
# Na VPS:
./vendor/bin/sail artisan pail --filter=error

# Ou com filtro personalizado:
./vendor/bin/sail artisan pail --filter=GigController
```

---

### 💡 Backup Agendado (Cron)

```bash
# Na VPS, adicionar ao crontab:
crontab -e

# Backup diário às 3h da manhã:
0 3 * * * cd /var/www/eventospro && ./scripts/backup-database.sh >> /var/log/eventospro-backup.log 2>&1
```

---

### 💡 Validar Integridade dos Backups

```bash
# Verificar se todos os backups estão válidos:
for file in backups/*.sql.gz; do
    echo "Testando: $file"
    gunzip -t "$file" && echo "✅ OK" || echo "❌ CORROMPIDO"
done
```

---

## Resumo dos Benefícios

### Antes (Fluxo Manual)
- ⏱️ Deploy: ~15-20 minutos
- 😰 Estresse: Alto
- 🐌 phpMyAdmin lento
- ⚠️ Sem rollback rápido
- ❌ Migrations arriscadas

### Agora (Fluxo Automatizado)
- ⏱️ Deploy: ~2-5 minutos
- 😌 Estresse: Zero
- 🚀 Scripts 10x mais rápidos
- ✅ Rollback em 30 segundos
- ✅ Migrations testadas

---

## Próximos Passos (Fase 2 - Futuro)

Melhorias planejadas para o futuro:

1. **Backup para Google Drive** (automático e off-site)
2. **Monitoramento de saúde** do banco de dados
3. **Backup incremental** (apenas mudanças)
4. **Deploy com zero downtime** (blue-green deployment)
5. **Testes automatizados** antes de cada deploy
6. **Notificações** (Slack/Discord) quando deploy completa

---

## Suporte e Contato

**Documentação Relacionada**:
- `docs/BACKUP_IMPLEMENTATION_PLAN.md` - Plano completo de backup
- `docs/SERVICES_API.md` - API dos services
- `docs/TESTING.md` - Guia de testes

**Logs Importantes**:
- Backup logs: `backups/` (veja timestamps)
- Laravel logs: `storage/logs/laravel.log`
- Docker logs: `./vendor/bin/sail logs`

---

**Última Atualização**: 2025-10-30
**Versão**: 1.0
**Autor**: Equipe EventosPro
