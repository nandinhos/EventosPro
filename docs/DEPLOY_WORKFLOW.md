# 🔄 EventosPro - Workflow de Deploy Completo

> **Guia Mestre**: Fluxo completo de desenvolvimento até produção com 2 servidores (Staging + Production)

---

## 🎯 Visão Geral do Workflow

```
┌─────────────────────────────────────────────────────────────┐
│                    AMBIENTE LOCAL (WSL)                     │
│  • Docker + Sail (desenvolvimento)                          │
│  • Hot reload, debug tools, logs verbose                    │
│  • Desenvolver → Testar → Commit → Push                     │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       │ git push origin main/staging
                       ▼
┌─────────────────────────────────────────────────────────────┐
│              SERVIDOR STAGING (VPS 1)                       │
│  • Ambiente "quase produção"                                │
│  • Docker otimizado (Alpine + Nginx + Redis)                │
│  • Testar features em condições reais                       │
│  • Validar performance e bugs                               │
│  • Aprovar ou rejeitar deploy                               │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       │ Após validação ✅
                       ▼
┌─────────────────────────────────────────────────────────────┐
│            SERVIDOR PRODUCTION (VPS 2)                      │
│  • Aplicação 100% otimizada                                 │
│  • Backup automático antes de deploy                        │
│  • Zero-downtime deployment                                 │
│  • Monitoramento e logs                                     │
│  • Rollback automático se falhar                            │
└─────────────────────────────────────────────────────────────┘
```

---

## 📋 Ambientes e Suas Características

| Aspecto | Local (WSL) | Staging (VPS 1) | Production (VPS 2) |
|---------|-------------|-----------------|-------------------|
| **Propósito** | Desenvolvimento | Testes pré-produção | Aplicação live |
| **Docker Compose** | `docker-compose.yml` | `docker-compose.production.yml` | `docker-compose.production.yml` |
| **ENV** | `.env` (local) | `.env` (staging) | `.env` (production) |
| **APP_ENV** | `local` | `staging` | `production` |
| **APP_DEBUG** | `true` | `false` | `false` |
| **Base Docker** | Ubuntu (Sail) | Alpine (otimizado) | Alpine (otimizado) |
| **Web Server** | artisan serve | Nginx + PHP-FPM | Nginx + PHP-FPM |
| **Cache** | Database/File | Redis | Redis |
| **Queue** | Sync | Redis + Worker | Redis + Worker |
| **Logs** | Debug | Info/Warning | Warning/Error |
| **SSL** | Não | Opcional | **Obrigatório** |
| **Backup** | Não | Diário | A cada 6h |
| **Monitoramento** | Não | Básico | Completo |

---

## 🔄 Fluxo Passo a Passo

### **Fase 1: Desenvolvimento Local** 📝

#### 1.1 Setup Inicial (uma vez)

```bash
cd ~/projects/EventosPro

# Instalar dependências
sail composer install
sail npm install

# Configurar ambiente
cp .env.example .env
sail artisan key:generate

# Subir containers
sail up -d

# Rodar migrations
sail artisan migrate --seed
```

#### 1.2 Desenvolvimento de Feature

```bash
# Criar branch (opcional, mas recomendado)
git checkout -b feature/nome-da-feature

# Desenvolver...
# Editar arquivos em app/, resources/, etc.

# Testar localmente
sail artisan test
sail npm run dev  # Hot reload

# Ver aplicação: http://localhost
```

#### 1.3 Commit e Push

```bash
# Adicionar mudanças
git add .

# Commit descritivo
git commit -m "feat: Descrição da feature"

# Push para repositório
git push origin feature/nome-da-feature

# OU merge direto na main (se não usar PRs)
git checkout main
git merge feature/nome-da-feature
git push origin main
```

**📖 Guia detalhado:** `COMMIT_GUIDE.md`

---

### **Fase 2: Deploy em Staging** 🧪

#### 2.1 Quando fazer deploy em Staging?

✅ **SEMPRE que:**
- Adicionar nova feature
- Modificar lógica de negócio
- Atualizar dependências importantes
- Mudar configurações de produção

❌ **Pode pular se:**
- Correções de typo em textos
- Ajustes de CSS/design menores
- Atualização de documentação apenas

#### 2.2 Executar Deploy

**Opção A: Script Automatizado (Recomendado)**

```bash
# SSH no staging
ssh user@staging-server.com

# Executar script
cd ~/projects/EventosPro
./scripts/deploy-staging.sh
```

**Opção B: Manual (passo a passo)**

```bash
# SSH no staging
ssh user@staging-server.com

# Seguir guia completo
cat ~/projects/EventosPro/docs/STAGING_DEPLOY.md
```

#### 2.3 Validação em Staging

**Checklist de testes:**

- [ ] Aplicação está acessível
- [ ] Login funciona
- [ ] Nova feature funciona conforme esperado
- [ ] Não há erros nos logs
- [ ] Performance aceitável
- [ ] Jobs da fila estão processando
- [ ] Emails estão sendo enviados (se aplicável)

```bash
# Ver logs
docker compose -f docker-compose.production.yml logs -f app

# Testar health
curl http://staging-server.com/health

# Verificar queue
docker compose -f docker-compose.production.yml exec app php artisan queue:work --once
```

**Resultado:**
- ✅ **APROVADO**: Prosseguir para produção
- ❌ **REPROVADO**: Corrigir bugs, fazer novo commit, repetir staging

**📖 Guia detalhado:** `docs/STAGING_DEPLOY.md`

---

### **Fase 3: Deploy em Production** 🚀

#### 3.1 Pré-requisitos

✅ **Antes de fazer deploy em produção:**

- [ ] Feature validada em staging
- [ ] Todos os testes passaram
- [ ] Performance verificada
- [ ] Sem erros nos logs de staging
- [ ] Backup recente disponível
- [ ] Horário de baixo tráfego (opcional, mas recomendado)

#### 3.2 Executar Deploy

**Opção A: Script Automatizado com Backup (Recomendado)**

```bash
# SSH no production
ssh user@production-server.com

# Executar script (faz backup automático)
cd ~/projects/EventosPro
./scripts/deploy-production.sh
```

**Opção B: Manual com Controle Total**

```bash
# SSH no production
ssh user@production-server.com

# Seguir guia passo a passo
cat ~/projects/EventosPro/docs/PRODUCTION_DEPLOY.md
```

#### 3.3 Validação Pós-Deploy

```bash
# Health check
curl https://production-domain.com/health

# Ver logs (primeiros 5 minutos)
docker compose -f docker-compose.production.yml logs -f app

# Verificar queue worker
docker compose -f docker-compose.production.yml exec app supervisorctl status

# Monitorar recursos
docker stats
```

**Checklist:**
- [ ] Site acessível
- [ ] SSL funcionando
- [ ] Login funciona
- [ ] Nova feature ativa
- [ ] Queue processando
- [ ] Sem erros nos logs
- [ ] Uso de RAM/CPU normal

#### 3.4 Rollback (se necessário)

```bash
# Reverter para versão anterior
git revert HEAD
git push origin main

# OU voltar para commit específico
git checkout <commit-hash-anterior>
git push origin main --force  # CUIDADO!

# Rebuild no servidor
./scripts/deploy-production.sh
```

**📖 Guia detalhado:** `docs/PRODUCTION_DEPLOY.md`

---

## 🛠️ Ferramentas e Scripts

### Scripts Automatizados

| Script | Localização | Uso |
|--------|-------------|-----|
| Deploy Staging | `scripts/deploy-staging.sh` | Deploy automatizado para staging |
| Deploy Production | `scripts/deploy-production.sh` | Deploy com backup para production |

### Guias de Referência

| Documento | Quando Usar |
|-----------|-------------|
| `COMMIT_GUIDE.md` | Antes de fazer commit das mudanças |
| `docs/DEPLOY_WORKFLOW.md` | Visão geral do fluxo (este arquivo) |
| `docs/STAGING_DEPLOY.md` | Deploy manual em staging |
| `docs/PRODUCTION_DEPLOY.md` | Deploy manual em production |
| `docs/VPS_DEPLOYMENT.md` | Setup inicial dos servidores |
| `docs/OPTIMIZATION_SUMMARY.md` | Detalhes técnicos das otimizações |

---

## 📊 Decisões de Deploy

### Quando usar Staging vs Production diretamente?

```
┌────────────────────────────────────────────┐
│  SEMPRE USAR STAGING ANTES:                │
│  ✅ Novas features                         │
│  ✅ Mudanças em migrations                 │
│  ✅ Atualização de pacotes major           │
│  ✅ Mudanças em lógica de negócio          │
│  ✅ Alterações em configuração Docker      │
└────────────────────────────────────────────┘

┌────────────────────────────────────────────┐
│  PODE IR DIRETO PARA PRODUCTION:           │
│  ⚡ Hotfix crítico (bug grave)            │
│  ⚡ Correção de segurança urgente          │
│  ⚡ Ajuste de texto/tradução               │
│  ⚡ Mudança de CSS minor                   │
└────────────────────────────────────────────┘
```

---

## 🔄 Exemplo de Workflow Completo

### Cenário: Adicionar nova funcionalidade de relatórios

#### **Dia 1: Desenvolvimento**

```bash
# Local
git checkout -b feature/relatorios-mensais
# ... desenvolver código ...
sail artisan test --filter=RelatorioTest
git commit -m "feat: Add monthly reports feature"
git push origin feature/relatorios-mensais
```

#### **Dia 2: Code Review (opcional)**

```bash
# GitHub/GitLab
# ... review do código ...
# ... aprovar PR ...
git checkout main
git merge feature/relatorios-mensais
git push origin main
```

#### **Dia 2: Deploy Staging**

```bash
# SSH staging
ssh user@staging.eventospro.com
cd ~/projects/EventosPro
./scripts/deploy-staging.sh

# Testes
# ... testar relatório em http://staging.eventospro.com ...
# ... verificar que PDF é gerado corretamente ...
# ... verificar performance com dados reais ...
```

**Resultado: ✅ APROVADO**

#### **Dia 3: Deploy Production (horário de baixo tráfego)**

```bash
# SSH production
ssh user@eventospro.com
cd ~/projects/EventosPro
./scripts/deploy-production.sh

# Monitoramento
# ... verificar logs ...
# ... confirmar que usuários estão conseguindo gerar relatórios ...
```

**Resultado: ✅ SUCESSO**

---

## 🆘 Troubleshooting Comum

### Deploy falhou no Staging

```bash
# Ver logs detalhados
docker compose -f docker-compose.production.yml logs --tail=100 app

# Rebuild forçado
docker compose -f docker-compose.production.yml down
docker compose -f docker-compose.production.yml up -d --build --force-recreate

# Se persistir, reverter commit
git revert HEAD
git push origin main
```

### Deploy falhou no Production

```bash
# Script de production já faz rollback automático!
# Mas se precisar manual:
./scripts/deploy-production.sh rollback

# Ou restaurar backup do banco
docker compose -f docker-compose.production.yml exec mysql mysql -u root -p < /backups/backup-latest.sql
```

### Diferença entre Staging e Production

```bash
# Ver diff entre ambientes
diff <(ssh staging 'cat ~/projects/EventosPro/.env') \
     <(ssh production 'cat ~/projects/EventosPro/.env')

# Ver versão (commit) em cada ambiente
ssh staging 'cd ~/projects/EventosPro && git log -1 --oneline'
ssh production 'cd ~/projects/EventosPro && git log -1 --oneline'
```

---

## 📈 Boas Práticas

### ✅ DO (Faça)

- ✅ Sempre teste em staging primeiro
- ✅ Use mensagens de commit descritivas
- ✅ Faça backup antes de deploy em production
- ✅ Monitore logs após deploy
- ✅ Documente mudanças breaking
- ✅ Use branches para features grandes
- ✅ Execute testes automatizados
- ✅ Valide migrations em staging

### ❌ DON'T (Não Faça)

- ❌ Deploy direto em production sem testar
- ❌ Commit credenciais (`.env`) no git
- ❌ Ignorar erros nos logs de staging
- ❌ Deploy em horário de pico sem necessidade
- ❌ Pular backups antes de deploy
- ❌ Fazer rollback sem entender o problema
- ❌ Modificar `.env` direto no servidor sem documentar

---

## 🎯 Checklist Rápido

### Antes de Qualquer Deploy

- [ ] Código commitado e pushed
- [ ] Testes locais passaram
- [ ] `.env.example` atualizado (se mudou configs)
- [ ] Documentação atualizada (se necessário)
- [ ] Migrations testadas localmente

### Deploy Staging

- [ ] Executei `./scripts/deploy-staging.sh`
- [ ] Verifiquei logs (`docker compose logs -f app`)
- [ ] Testei funcionalidades principais
- [ ] Validei a nova feature
- [ ] Sem erros críticos

### Deploy Production

- [ ] Staging validado ✅
- [ ] Backup feito (automático pelo script)
- [ ] Executei `./scripts/deploy-production.sh`
- [ ] Monitorei logs por 10-15 minutos
- [ ] Health check passou
- [ ] Performance normal
- [ ] Comuniquei deploy ao time (se aplicável)

---

## 🤖 Instruções para Agente de IA

Quando o usuário pedir para você fazer deploy:

1. **Perguntar qual ambiente:**
   - Staging ou Production?

2. **Se Staging:**
   - Confirmar: "Vou fazer deploy em STAGING usando `scripts/deploy-staging.sh`. Confirma?"
   - Executar: `ssh user@staging-server "cd ~/projects/EventosPro && ./scripts/deploy-staging.sh"`
   - Mostrar: Logs e resultado

3. **Se Production:**
   - Confirmar: "Staging foi validado? Vou fazer deploy em PRODUCTION com backup automático. Confirma?"
   - Executar: `ssh user@production-server "cd ~/projects/EventosPro && ./scripts/deploy-production.sh"`
   - Monitorar: Logs por 5 minutos
   - Reportar: Status final

4. **Se houver erro:**
   - Mostrar logs completos
   - Sugerir: Rollback ou debug
   - Não fazer deploy em production se staging falhou

---

**Workflow definido! 🎉**

Siga este fluxo para garantir deploys seguros e confiáveis.
