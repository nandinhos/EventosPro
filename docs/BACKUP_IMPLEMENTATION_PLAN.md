# Plano de Implementação: Sistema de Backup EventosPro

> **Status**: 📋 Planejamento aprovado - Aguardando implementação no ambiente local
> **Data de criação**: 2025-10-30
> **Prioridade**: 🚨 CRÍTICA (Produção com dados reais)

---

## 📊 Contexto e Necessidade

### Problema Atual
- ❌ **Nenhum sistema de backup automatizado**
- ⚠️ **Deploy atual pode usar `migrate:fresh --seed`** (destrutivo para produção)
- 🎲 **Sincronização manual via phpMyAdmin** (processo trabalhoso e arriscado)
- 💾 **Banco de produção sendo substituído manualmente** após deploys

### Riscos Identificados
- **Perda de dados críticos** durante deploys
- **Incompatibilidade de schemas** entre versões
- **Tempo de downtime** excessivo em caso de problemas
- **Sem estratégia de rollback** estruturada

---

## 🎯 Objetivos da Solução

1. **Proteção de dados**: Backup automático antes de cada deploy
2. **Rollback rápido**: Restauração em < 2 minutos
3. **Sincronização segura**: Produção → Desenvolvimento com dados anonimizados
4. **Automação**: Backups diários sem intervenção manual
5. **Armazenamento seguro**: Google Drive como storage off-site

---

## 📋 Implementação Faseada

### 🚨 FASE 1: SOLUÇÃO URGENTE (1-2 horas)

**Objetivo**: Proteção imediata contra perda de dados

#### Arquivos a criar:

**1. `scripts/backup-database.sh`**
```bash
#!/bin/bash
# Script de backup manual do banco de dados

set -e

BACKUP_DIR="backups"
TIMESTAMP=$(date +%Y%m%d-%H%M%S)
BACKUP_FILE="${BACKUP_DIR}/eventospro-backup-${TIMESTAMP}.sql"

# Criar diretório de backups se não existir
mkdir -p ${BACKUP_DIR}

echo "🔄 Criando backup do banco de dados..."

# Executar mysqldump dentro do container Sail
./vendor/bin/sail exec mysql mysqldump \
    -u sail \
    -ppassword \
    --single-transaction \
    --quick \
    --lock-tables=false \
    eventospro > ${BACKUP_FILE}

# Comprimir backup
echo "📦 Comprimindo backup..."
gzip ${BACKUP_FILE}

echo "✅ Backup criado: ${BACKUP_FILE}.gz"
echo "📊 Tamanho: $(du -h ${BACKUP_FILE}.gz | cut -f1)"

# Manter apenas os últimos 10 backups
echo "🧹 Removendo backups antigos (mantendo últimos 10)..."
ls -t ${BACKUP_DIR}/*.sql.gz | tail -n +11 | xargs -r rm -v

echo "✅ Backup concluído com sucesso!"
ls -lh ${BACKUP_DIR}/ | tail -n 10
```

**2. `scripts/restore-database.sh`**
```bash
#!/bin/bash
# Script de restauração de backup

set -e

BACKUP_DIR="backups"

echo "📋 Backups disponíveis:"
echo "======================"
ls -lht ${BACKUP_DIR}/*.sql.gz | nl

echo ""
read -p "Digite o número do backup para restaurar (ou 0 para cancelar): " CHOICE

if [ "$CHOICE" -eq "0" ]; then
    echo "❌ Restauração cancelada"
    exit 0
fi

BACKUP_FILE=$(ls -t ${BACKUP_DIR}/*.sql.gz | sed -n "${CHOICE}p")

if [ -z "$BACKUP_FILE" ]; then
    echo "❌ Backup inválido"
    exit 1
fi

echo "⚠️  ATENÇÃO: Esta operação vai SUBSTITUIR o banco de dados atual!"
echo "Arquivo selecionado: ${BACKUP_FILE}"
read -p "Tem certeza? (digite 'SIM' para confirmar): " CONFIRM

if [ "$CONFIRM" != "SIM" ]; then
    echo "❌ Restauração cancelada"
    exit 0
fi

echo "🔄 Descomprimindo backup..."
gunzip -c ${BACKUP_FILE} > /tmp/restore.sql

echo "🔄 Restaurando banco de dados..."
./vendor/bin/sail mysql eventospro < /tmp/restore.sql

echo "🧹 Limpando arquivos temporários..."
rm /tmp/restore.sql

echo "✅ Banco de dados restaurado com sucesso!"
echo "🔄 Limpando caches do Laravel..."
./vendor/bin/sail artisan cache:clear
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan view:clear

echo "🎉 Restauração concluída!"
```

**3. Atualização do `deploy.sh`**

Adicionar no início do arquivo, após as verificações iniciais:

```bash
# Função de backup pré-deploy
create_backup() {
    if [ -f "scripts/backup-database.sh" ]; then
        echo "💾 Criando backup antes do deploy..."
        bash scripts/backup-database.sh
        if [ $? -eq 0 ]; then
            echo "✅ Backup criado com sucesso"
        else
            echo "❌ Falha ao criar backup!"
            read -p "Deseja continuar sem backup? (s/N): " CONTINUE
            if [ "$CONTINUE" != "s" ]; then
                echo "Deploy cancelado"
                exit 1
            fi
        fi
    else
        echo "⚠️  Script de backup não encontrado em scripts/backup-database.sh"
        echo "⚠️  Deploy continuará SEM backup!"
        sleep 3
    fi
}

# Executar backup antes de migrations (exceto se --skip-backup for passado)
if [[ ! "$*" =~ "--skip-backup" ]]; then
    create_backup
fi
```

**4. Atualizar `.gitignore`**

Adicionar:
```
# Database backups (local only)
backups/
*.sql
*.sql.gz
```

#### Comandos disponíveis após Fase 1:

```bash
# Backup manual
./scripts/backup-database.sh

# Restaurar backup
./scripts/restore-database.sh

# Deploy com backup automático (modo produção)
./deploy.sh --production

# Deploy sem backup (usar com cuidado!)
./deploy.sh --production --skip-backup
```

#### Benefícios imediatos:
- ✅ Backup automático antes de cada deploy
- ✅ Histórico dos últimos 10 backups locais
- ✅ Rollback rápido (< 2 minutos)
- ✅ Zero custo adicional
- ✅ Funciona com infraestrutura atual

---

### ⚡ FASE 2: AUTOMAÇÃO COM CLOUD STORAGE (1 dia)

**Objetivo**: Backups diários automáticos + Google Drive

#### Pacotes necessários:

```bash
sail composer require spatie/laravel-backup
sail composer require masbug/flysystem-google-drive-ext
```

#### Configuração do Google Drive:

1. **Google Cloud Console**:
   - Acessar https://console.cloud.google.com
   - Criar novo projeto "EventosPro Backups"
   - Ativar "Google Drive API"
   - Criar credenciais OAuth 2.0
   - Baixar JSON de credenciais

2. **Configurar no projeto**:
   - Salvar credenciais em `storage/app/google-drive-credentials.json`
   - Adicionar ao `.gitignore`:
     ```
     storage/app/google-drive-credentials.json
     ```

#### Arquivos a criar/modificar:

**1. `config/filesystems.php`** (adicionar ao array 'disks'):

```php
'google_drive' => [
    'driver' => 'google',
    'clientId' => env('GOOGLE_DRIVE_CLIENT_ID'),
    'clientSecret' => env('GOOGLE_DRIVE_CLIENT_SECRET'),
    'refreshToken' => env('GOOGLE_DRIVE_REFRESH_TOKEN'),
    'folder' => env('GOOGLE_DRIVE_FOLDER'), // ID da pasta no Drive
],

'backups' => [
    'driver' => 'local',
    'root' => storage_path('app/backups'),
    'throw' => false,
],
```

**2. `config/backup.php`** (será criado via publish):

```bash
sail artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider"
```

Principais configurações:

```php
'backup' => [
    'name' => 'eventospro',

    'source' => [
        'databases' => ['mysql'],
    ],

    'destination' => [
        'filename_prefix' => 'eventospro-',
        'disks' => [
            'backups',        // Local
            'google_drive',   // Google Drive
        ],
    ],
],

'cleanup' => [
    'default_strategy' => [
        'keep_all_backups_for_days' => 7,
        'keep_daily_backups_for_days' => 16,
        'keep_weekly_backups_for_weeks' => 8,
        'keep_monthly_backups_for_months' => 12,
    ],
],

'notifications' => [
    'mail' => [
        'to' => env('BACKUP_NOTIFICATION_EMAIL', 'admin@eventospro.com'),
    ],
],
```

**3. `routes/console.php`** (adicionar):

```php
use Illuminate\Support\Facades\Schedule;

// Backup diário às 2h da manhã
Schedule::command('backup:run --only-db')
    ->daily()
    ->at('02:00');

// Limpeza de backups antigos (diariamente às 3h)
Schedule::command('backup:clean')
    ->daily()
    ->at('03:00');

// Monitoramento de backups (diariamente às 4h)
Schedule::command('backup:monitor')
    ->daily()
    ->at('04:00');
```

**4. Configuração do Cron no VPS**:

```bash
# Editar crontab do usuário
crontab -e

# Adicionar (ajustar caminho do projeto):
* * * * * cd /var/www/eventospro && ./vendor/bin/sail artisan schedule:run >> /dev/null 2>&1
```

**Ou usar Laravel Scheduler Worker (mais confiável)**:

```bash
# Rodar em background com supervisor ou screen
sail artisan schedule:work
```

#### Variáveis de ambiente (.env):

```bash
# Google Drive Backup
GOOGLE_DRIVE_CLIENT_ID=your-client-id
GOOGLE_DRIVE_CLIENT_SECRET=your-client-secret
GOOGLE_DRIVE_REFRESH_TOKEN=your-refresh-token
GOOGLE_DRIVE_FOLDER=folder-id-from-drive

# Backup Notifications
BACKUP_NOTIFICATION_EMAIL=admin@eventospro.com
```

#### Comandos disponíveis:

```bash
# Backup manual (database + Google Drive)
sail artisan backup:run --only-db

# Listar backups
sail artisan backup:list

# Verificar saúde dos backups
sail artisan backup:monitor

# Limpar backups antigos
sail artisan backup:clean
```

#### Benefícios:
- ✅ Backups diários automáticos às 2h
- ✅ Armazenamento seguro no Google Drive
- ✅ Retenção inteligente (7 dias diários, 8 semanas semanais, 12 meses mensais)
- ✅ Notificações por email em caso de falha
- ✅ Limpeza automática de backups antigos

---

### 🔄 FASE 3: SINCRONIZAÇÃO MANUAL PROD → DEV (2 dias)

**Objetivo**: Trazer dados reais (anonimizados) de produção para desenvolvimento

#### Arquivos a criar:

**1. `app/Console/Commands/SyncDatabaseFromProduction.php`**

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Booker;
use App\Models\Artist;

class SyncDatabaseFromProduction extends Command
{
    protected $signature = 'db:sync-from-production
                            {--download-only : Apenas baixar backup sem importar}
                            {--skip-anonymize : Não anonimizar dados (usar com cuidado!)}';

    protected $description = 'Sincroniza banco de dados de produção para local (com anonimização)';

    public function handle()
    {
        $this->info('🔄 Iniciando sincronização de banco de dados...');

        if (!$this->confirm('Esta operação vai SUBSTITUIR seu banco local. Continuar?')) {
            $this->error('Operação cancelada');
            return 1;
        }

        // 1. Criar backup do banco local atual
        $this->info('💾 Criando backup do banco local atual...');
        $this->call('backup:run', ['--only-db' => true]);

        // 2. Baixar último backup do Google Drive
        $this->info('📥 Baixando último backup de produção...');
        $latestBackup = $this->getLatestProductionBackup();

        if (!$latestBackup) {
            $this->error('❌ Nenhum backup encontrado no Google Drive');
            return 1;
        }

        $tempFile = storage_path('app/temp_restore.sql.gz');
        Storage::disk('google_drive')->download($latestBackup, $tempFile);

        if ($this->option('download-only')) {
            $this->info("✅ Backup baixado: {$tempFile}");
            return 0;
        }

        // 3. Descomprimir
        $this->info('📦 Descomprimindo backup...');
        $sqlFile = storage_path('app/temp_restore.sql');
        shell_exec("gunzip -c {$tempFile} > {$sqlFile}");

        // 4. Importar para banco local
        $this->info('🔄 Importando banco de dados...');
        $exitCode = shell_exec("./vendor/bin/sail mysql eventospro < {$sqlFile}");

        if ($exitCode !== null && $exitCode !== 0) {
            $this->error('❌ Falha ao importar banco de dados');
            return 1;
        }

        // 5. Anonimizar dados sensíveis
        if (!$this->option('skip-anonymize')) {
            $this->info('🔒 Anonimizando dados sensíveis...');
            $this->anonymizeData();
        }

        // 6. Limpar arquivos temporários
        $this->info('🧹 Limpando arquivos temporários...');
        unlink($tempFile);
        unlink($sqlFile);

        // 7. Limpar caches
        $this->info('⚡ Limpando caches...');
        $this->call('cache:clear');
        $this->call('config:clear');
        $this->call('view:clear');

        $this->info('✅ Sincronização concluída com sucesso!');
        $this->info('📊 Execute migrations se houver pendentes: sail artisan migrate');

        return 0;
    }

    private function getLatestProductionBackup(): ?string
    {
        $files = Storage::disk('google_drive')->files();
        $backups = collect($files)
            ->filter(fn($file) => str_contains($file, 'eventospro-backup'))
            ->sortDesc();

        return $backups->first();
    }

    private function anonymizeData(): void
    {
        $this->info('  → Anonimizando emails de usuários...');
        User::where('email', '!=', 'admin@eventospro.com')
            ->chunkById(100, function ($users) {
                foreach ($users as $user) {
                    $user->update([
                        'email' => 'user' . $user->id . '@example.com',
                        'name' => 'Usuário ' . $user->id,
                    ]);
                }
            });

        $this->info('  → Anonimizando dados de bookers...');
        Booker::chunkById(100, function ($bookers) {
            foreach ($bookers as $booker) {
                $booker->update([
                    'email' => 'booker' . $booker->id . '@example.com',
                    'phone' => '(11) 9****-' . str_pad($booker->id, 4, '0', STR_PAD_LEFT),
                ]);
            }
        });

        $this->info('  → Anonimizando dados de artistas...');
        Artist::chunkById(100, function ($artists) {
            foreach ($artists as $artist) {
                $artist->update([
                    'email' => 'artist' . $artist->id . '@example.com',
                    'phone' => '(11) 9****-' . str_pad($artist->id, 4, '0', STR_PAD_LEFT),
                ]);
            }
        });

        $this->info('  ✅ Anonimização concluída');
    }
}
```

**2. `scripts/download-prod-backup.sh`** (alternativa via SSH):

```bash
#!/bin/bash
# Download de backup direto do servidor de produção via SSH

set -e

echo "🔐 Conectando ao servidor de produção..."

PROD_USER="your-user"
PROD_HOST="your-production-server.com"
PROD_PATH="/var/www/eventospro"

# 1. Criar backup no servidor de produção
echo "💾 Criando backup no servidor de produção..."
ssh ${PROD_USER}@${PROD_HOST} "cd ${PROD_PATH} && ./scripts/backup-database.sh"

# 2. Obter nome do último backup
LATEST_BACKUP=$(ssh ${PROD_USER}@${PROD_HOST} "ls -t ${PROD_PATH}/backups/*.sql.gz | head -n 1")

echo "📥 Baixando backup: ${LATEST_BACKUP}"

# 3. Baixar backup via SCP
scp ${PROD_USER}@${PROD_HOST}:${LATEST_BACKUP} ./backups/prod-latest.sql.gz

echo "✅ Backup baixado para: backups/prod-latest.sql.gz"
echo ""
echo "Para importar, execute:"
echo "  gunzip -c backups/prod-latest.sql.gz | ./vendor/bin/sail mysql eventospro"
```

#### Comandos disponíveis:

```bash
# Sync completo (download + import + anonimização)
sail artisan db:sync-from-production

# Apenas download (sem importar)
sail artisan db:sync-from-production --download-only

# Sync sem anonimizar (cuidado!)
sail artisan db:sync-from-production --skip-anonymize

# Download via SSH direto do servidor
./scripts/download-prod-backup.sh
```

#### Benefícios:
- ✅ Dados reais para desenvolvimento
- ✅ Anonimização automática de dados sensíveis
- ✅ Preserva usuário admin local
- ✅ Comando simples e rápido
- ✅ Backup do banco local antes de substituir

---

## 📁 Estrutura Final de Arquivos

```
EventosPro/
├── backups/                          # Git-ignored
│   ├── eventospro-backup-20251030-*.sql.gz
│   └── ...                          # Últimos 10 backups
│
├── scripts/
│   ├── backup-database.sh           # FASE 1 - Backup manual
│   ├── restore-database.sh          # FASE 1 - Restore
│   └── download-prod-backup.sh      # FASE 3 - Download via SSH
│
├── storage/app/
│   ├── backups/                     # Backups Spatie (local)
│   └── google-drive-credentials.json # FASE 2 (Git-ignored)
│
├── app/Console/Commands/
│   └── SyncDatabaseFromProduction.php # FASE 3
│
├── config/
│   ├── backup.php                   # FASE 2 (novo)
│   └── filesystems.php              # FASE 2 (atualizado)
│
├── routes/
│   └── console.php                  # FASE 2 (scheduler)
│
├── deploy.sh                        # FASE 1 (atualizado)
├── .gitignore                       # Atualizado
│
└── docs/
    ├── BACKUP_IMPLEMENTATION_PLAN.md # Este arquivo
    └── BACKUP_PROCEDURES.md         # FASE 2 (documentação de uso)
```

---

## ⚙️ Fluxo de Deployment Recomendado

### Ambiente de Produção (VPS):

```bash
# 1. Atualizar código
git pull origin main

# 2. Instalar dependências
./vendor/bin/sail composer install --optimize-autoloader --no-dev

# 3. Build assets
./vendor/bin/sail npm ci
./vendor/bin/sail npm run build

# 4. Deploy com backup automático
./deploy.sh --production

# O deploy.sh agora vai:
# - Criar backup automático
# - Ativar maintenance mode
# - Rodar migrations
# - Otimizar caches
# - Desativar maintenance mode
# - Validar aplicação

# 5. Monitorar logs por 5 minutos
./vendor/bin/sail artisan pail --filter=error

# 6. Em caso de problema:
./scripts/restore-database.sh  # Escolher backup mais recente
```

### Ambiente de Desenvolvimento Local:

```bash
# Opção 1: Seeders (dados fictícios)
sail artisan migrate:fresh --seed

# Opção 2: Dados reais de produção (após Fase 3)
sail artisan db:sync-from-production
sail artisan migrate  # Se houver migrações pendentes
```

---

## 🔒 Segurança e Boas Práticas

### Dados Sensíveis

**Nunca versionar no Git:**
- ✅ Adicionar ao `.gitignore`:
  ```
  /backups/
  *.sql
  *.sql.gz
  storage/app/google-drive-credentials.json
  ```

**Anonimização obrigatória:**
- Emails → `user{id}@example.com`
- Telefones → `(11) 9****-{id}`
- Senhas → Já hasheadas (seguro)
- CPF/CNPJ → Se houver, anonimizar na Fase 3

### Permissões de Arquivos

```bash
# Restringir acesso aos backups
chmod 700 backups/
chmod 600 backups/*.sql.gz

# Proteger credenciais do Google Drive
chmod 600 storage/app/google-drive-credentials.json
```

### Validação de Backups

**Testar backups regularmente:**

```bash
# A cada 2 semanas, validar um backup aleatório
sail artisan backup:list
./scripts/restore-database.sh  # Restaurar em ambiente de teste
```

---

## 📊 Métricas e Monitoramento

### Indicadores de Saúde

**Após Fase 2, monitorar:**

1. **Taxa de sucesso de backups**: > 98%
2. **Idade do último backup**: < 25 horas
3. **Tamanho dos backups**: Crescimento gradual esperado
4. **Tempo de restore**: < 2 minutos

### Alertas Configurados

- 📧 Email se backup diário falhar
- 📧 Email se último backup > 25h
- 📧 Email se storage > 10GB

---

## 💰 Custo Estimado

### Fase 1 (Backup Local):
- **Custo**: R$ 0,00
- **Storage necessário**: ~1-2GB (VPS atual)

### Fase 2 (Google Drive):
- **Custo**: R$ 0,00 (15GB gratuitos do Google)
- **Storage usado**: ~3-5GB (backups comprimidos)

### Fase 3 (Sync):
- **Custo**: R$ 0,00 (usa mesma infra)
- **Requisito**: SSH habilitado no VPS

**Total mensal: R$ 0,00** ✅

---

## ⏱️ Cronograma de Implementação

### Fase 1 (Urgente - Hoje):
- [ ] Criar `scripts/backup-database.sh`
- [ ] Criar `scripts/restore-database.sh`
- [ ] Atualizar `deploy.sh`
- [ ] Atualizar `.gitignore`
- [ ] Testar backup + restore
- [ ] **Commit e push**
- **Tempo estimado**: 1-2 horas

### Fase 2 (Próxima semana):
- [ ] Instalar Spatie Laravel Backup
- [ ] Configurar Google Drive API
- [ ] Configurar `config/backup.php`
- [ ] Atualizar `config/filesystems.php`
- [ ] Configurar scheduler em `routes/console.php`
- [ ] Configurar cron no VPS
- [ ] Testar backup automático
- [ ] Documentar procedimentos
- **Tempo estimado**: 4-6 horas

### Fase 3 (Quando necessário):
- [ ] Criar comando `SyncDatabaseFromProduction`
- [ ] Criar script `download-prod-backup.sh`
- [ ] Implementar anonimização
- [ ] Testar sync completo
- [ ] Documentar processo
- **Tempo estimado**: 4-6 horas

---

## 🎯 Próximos Passos

### Implementação no Servidor Local (Posterior):

1. **Pull do repositório**:
   ```bash
   git pull origin refactor/financial-services-centralization
   ```

2. **Executar Fase 1**:
   ```bash
   # Dar permissão de execução aos scripts
   chmod +x scripts/*.sh

   # Testar backup
   ./scripts/backup-database.sh

   # Testar restore
   ./scripts/restore-database.sh
   ```

3. **Validar integração com deploy.sh**:
   ```bash
   # Teste em ambiente local primeiro
   ./deploy.sh --production
   ```

### No VPS (Agora):

```bash
# Apenas fazer o pull quando estiver pronto
git pull origin refactor/financial-services-centralization

# A implementação será feita posteriormente no ambiente local
# e então sincronizada de volta para produção
```

---

## 📚 Documentação Adicional

Após implementação das fases, consultar:

- **FASE 1**: Este arquivo (seção de scripts)
- **FASE 2**: `docs/BACKUP_PROCEDURES.md` (será criado)
- **FASE 3**: `docs/SYNC_PROCEDURES.md` (será criado)

---

## ✅ Checklist de Validação

### Antes do Primeiro Deploy em Produção:

- [ ] Fase 1 implementada e testada
- [ ] Backup criado manualmente com sucesso
- [ ] Restore testado e funcionando
- [ ] `.gitignore` atualizado
- [ ] Scripts com permissão de execução
- [ ] `deploy.sh` rodando com backup automático

### Antes de Considerar "Pronto":

- [ ] Fase 2 implementada (backups automáticos)
- [ ] Google Drive configurado e testado
- [ ] Scheduler rodando (cron configurado)
- [ ] Recebeu email de sucesso de backup
- [ ] Testou restore de backup do Google Drive

### Opcional (Quando Necessário):

- [ ] Fase 3 implementada (sync manual)
- [ ] Sync testado com dados anonimizados
- [ ] Documentação completa criada

---

## 🆘 Troubleshooting

### Erro: "Permission denied" nos scripts

```bash
chmod +x scripts/*.sh
```

### Backup demora muito tempo

- Usar `--single-transaction` (já incluído nos scripts)
- Considerar backup apenas de tabelas críticas
- Aumentar `timeout` no Spatie config

### Google Drive não conecta

- Verificar credenciais JSON
- Verificar token de refresh
- Re-autenticar usando `sail artisan backup:run --only-db`

### Restore falha no meio

- Verificar se há espaço em disco
- Verificar se MySQL tem permissões
- Usar `--force` se necessário

---

## 📞 Suporte

Em caso de dúvidas ou problemas:

1. Consultar documentação do [Spatie Laravel Backup](https://spatie.be/docs/laravel-backup)
2. Verificar logs: `./vendor/bin/sail artisan pail`
3. Revisar este documento

---

**Documento criado em**: 2025-10-30
**Última atualização**: 2025-10-30
**Versão**: 1.0.0
**Status**: 📋 Aguardando implementação
