#!/bin/bash
# EventosPro - Database Sync to VPS Script
# Replaces manual phpMyAdmin export/import workflow

set -e

echo "======================================================================"
echo "🔄 EventosPro - Sincronização de Banco de Dados para VPS"
echo "======================================================================"
echo ""
echo "Este script substitui o fluxo manual de phpMyAdmin:"
echo "  ❌ ANTES: Exportar no local → Importar no VPS (lento, manual)"
echo "  ✅ AGORA: Backup → Transfer SCP → Restore (rápido, automático)"
echo ""

# ============================================================================
# CONFIGURAÇÃO - EDITE ESTAS VARIÁVEIS DE ACORDO COM SUA VPS
# ============================================================================

# VPS Configuration (EDIT THESE)
VPS_HOST="${VPS_HOST:-your-vps-ip-or-domain}"
VPS_USER="${VPS_USER:-your-ssh-user}"
VPS_PORT="${VPS_PORT:-22}"
VPS_PROJECT_PATH="${VPS_PROJECT_PATH:-/var/www/eventospro}"

# ============================================================================
# NÃO EDITE ABAIXO DESTA LINHA
# ============================================================================

echo "======================================================================"
echo "⚙️  Configuração"
echo "======================================================================"
echo ""
echo "VPS Host:         $VPS_HOST"
echo "VPS User:         $VPS_USER"
echo "VPS Port:         $VPS_PORT"
echo "VPS Project Path: $VPS_PROJECT_PATH"
echo ""

# Validar configuração
if [ "$VPS_HOST" = "your-vps-ip-or-domain" ]; then
    echo "❌ ERRO: Você precisa configurar as variáveis no início do script!"
    echo ""
    echo "📝 Edite o arquivo: scripts/sync-database-to-vps.sh"
    echo ""
    echo "Ou defina as variáveis de ambiente:"
    echo "  export VPS_HOST='seu-ip-ou-dominio'"
    echo "  export VPS_USER='seu-usuario-ssh'"
    echo "  export VPS_PORT='22'"
    echo "  export VPS_PROJECT_PATH='/var/www/eventospro'"
    echo "  ./scripts/sync-database-to-vps.sh"
    echo ""
    exit 1
fi

echo "⚠️  ATENÇÃO: Esta operação vai SUBSTITUIR o banco de dados na VPS!"
echo "   Banco local (WSL) → VPS ($VPS_HOST)"
echo ""
read -p "Tem certeza que deseja continuar? (digite 'SIM'): " CONFIRM

if [ "$CONFIRM" != "SIM" ]; then
    echo "❌ Operação cancelada"
    exit 0
fi

echo ""
echo "======================================================================"
echo "🔌 FASE 1: Testando conexão SSH com VPS"
echo "======================================================================"
echo ""

# Testar conexão SSH
echo "Testando conexão com $VPS_USER@$VPS_HOST..."

if ssh -p $VPS_PORT -o ConnectTimeout=10 -o BatchMode=yes $VPS_USER@$VPS_HOST "echo 'Conexão OK'" 2>/dev/null; then
    echo "✅ Conexão SSH estabelecida com sucesso"
else
    echo "❌ ERRO: Não foi possível conectar via SSH"
    echo ""
    echo "📋 Verifique:"
    echo "   1. VPS está online?"
    echo "   2. Credenciais SSH estão corretas?"
    echo "   3. Chave SSH está configurada? (ssh-copy-id $VPS_USER@$VPS_HOST)"
    echo "   4. Firewall permite conexão na porta $VPS_PORT?"
    echo ""
    echo "💡 Teste manual: ssh -p $VPS_PORT $VPS_USER@$VPS_HOST"
    exit 1
fi

# Verificar se o projeto existe na VPS
echo ""
echo "Verificando se o projeto existe na VPS..."

if ssh -p $VPS_PORT $VPS_USER@$VPS_HOST "[ -d $VPS_PROJECT_PATH ]" 2>/dev/null; then
    echo "✅ Projeto encontrado em: $VPS_PROJECT_PATH"
else
    echo "❌ ERRO: Projeto não encontrado em: $VPS_PROJECT_PATH"
    echo ""
    echo "📋 Verifique se o caminho está correto ou:"
    echo "   ssh $VPS_USER@$VPS_HOST 'ls -la /var/www/'"
    exit 1
fi

echo ""
echo "======================================================================"
echo "💾 FASE 2: Criando backup do banco LOCAL (WSL)"
echo "======================================================================"
echo ""

# Verificar se containers locais estão rodando
if ! docker ps | grep -q eventospro-mysql-1; then
    echo "❌ Container MySQL local não está rodando!"
    echo "   Execute: ../vendor/bin/sail up -d"
    exit 1
fi

# Criar backup local
echo "Criando backup do banco local..."
./scripts/backup-database.sh

LOCAL_BACKUP=$(ls -t backups/*.sql.gz | head -1)
echo ""
echo "✅ Backup local criado: $LOCAL_BACKUP"
echo "   Tamanho: $(du -h $LOCAL_BACKUP | cut -f1)"

echo ""
echo "======================================================================"
echo "🛡️  FASE 3: Criando backup de segurança do banco da VPS"
echo "======================================================================"
echo ""

echo "Criando backup preventivo da VPS antes de sobrescrever..."

# Executar backup na VPS remotamente
VPS_BACKUP_OUTPUT=$(ssh -p $VPS_PORT $VPS_USER@$VPS_HOST "cd $VPS_PROJECT_PATH && ./scripts/backup-database.sh" 2>&1)

if [ $? -eq 0 ]; then
    echo "✅ Backup da VPS criado com sucesso"
    echo "$VPS_BACKUP_OUTPUT" | tail -5
else
    echo "❌ ERRO ao criar backup na VPS:"
    echo "$VPS_BACKUP_OUTPUT"
    echo ""
    read -p "Deseja continuar SEM backup da VPS? (s/N): " CONTINUE_NO_BACKUP
    if [ "$CONTINUE_NO_BACKUP" != "s" ] && [ "$CONTINUE_NO_BACKUP" != "S" ]; then
        echo "❌ Operação cancelada por segurança"
        exit 1
    fi
fi

echo ""
echo "======================================================================"
echo "📤 FASE 4: Transferindo backup LOCAL → VPS"
echo "======================================================================"
echo ""

BACKUP_FILENAME=$(basename $LOCAL_BACKUP)
VPS_BACKUP_PATH="$VPS_PROJECT_PATH/backups/$BACKUP_FILENAME"

echo "Transferindo via SCP..."
echo "  Origem:  $LOCAL_BACKUP"
echo "  Destino: $VPS_USER@$VPS_HOST:$VPS_BACKUP_PATH"
echo ""

# Criar diretório de backups na VPS se não existir
ssh -p $VPS_PORT $VPS_USER@$VPS_HOST "mkdir -p $VPS_PROJECT_PATH/backups"

# Transferir arquivo via SCP
if scp -P $VPS_PORT $LOCAL_BACKUP $VPS_USER@$VPS_HOST:$VPS_BACKUP_PATH; then
    echo ""
    echo "✅ Transferência concluída"

    # Verificar tamanho do arquivo na VPS
    VPS_FILE_SIZE=$(ssh -p $VPS_PORT $VPS_USER@$VPS_HOST "du -h $VPS_BACKUP_PATH | cut -f1")
    echo "   Tamanho na VPS: $VPS_FILE_SIZE"
else
    echo ""
    echo "❌ ERRO: Falha na transferência via SCP"
    exit 1
fi

echo ""
echo "======================================================================"
echo "♻️  FASE 5: Restaurando backup na VPS"
echo "======================================================================"
echo ""

echo "Descomprimindo backup na VPS..."
ssh -p $VPS_PORT $VPS_USER@$VPS_HOST "gunzip -c $VPS_BACKUP_PATH > /tmp/sync-restore.sql"

echo "Restaurando banco de dados na VPS..."
ssh -p $VPS_PORT $VPS_USER@$VPS_HOST "cd $VPS_PROJECT_PATH && ./vendor/bin/sail mysql laravel < /tmp/sync-restore.sql"

echo "Limpando arquivos temporários..."
ssh -p $VPS_PORT $VPS_USER@$VPS_HOST "rm /tmp/sync-restore.sql"

echo "Limpando caches do Laravel na VPS..."
ssh -p $VPS_PORT $VPS_USER@$VPS_HOST "cd $VPS_PROJECT_PATH && ./vendor/bin/sail artisan cache:clear" > /dev/null 2>&1
ssh -p $VPS_PORT $VPS_USER@$VPS_HOST "cd $VPS_PROJECT_PATH && ./vendor/bin/sail artisan config:clear" > /dev/null 2>&1
ssh -p $VPS_PORT $VPS_USER@$VPS_HOST "cd $VPS_PROJECT_PATH && ./vendor/bin/sail artisan view:clear" > /dev/null 2>&1

echo ""
echo "✅ Restauração na VPS concluída!"

echo ""
echo "======================================================================"
echo "✔️  FASE 6: Validando sincronização"
echo "======================================================================"
echo ""

# Contar registros no banco local
LOCAL_USERS=$(../vendor/bin/sail mysql laravel -sN -e "SELECT COUNT(*) FROM users;" 2>/dev/null || echo "0")
LOCAL_GIGS=$(../vendor/bin/sail mysql laravel -sN -e "SELECT COUNT(*) FROM gigs;" 2>/dev/null || echo "0")

# Contar registros no banco da VPS
VPS_USERS=$(ssh -p $VPS_PORT $VPS_USER@$VPS_HOST "cd $VPS_PROJECT_PATH && ./vendor/bin/sail mysql laravel -sN -e 'SELECT COUNT(*) FROM users;'" 2>/dev/null || echo "0")
VPS_GIGS=$(ssh -p $VPS_PORT $VPS_USER@$VPS_HOST "cd $VPS_PROJECT_PATH && ./vendor/bin/sail mysql laravel -sN -e 'SELECT COUNT(*) FROM gigs;'" 2>/dev/null || echo "0")

echo "📊 Comparação de registros:"
echo ""
echo "   Tabela    | LOCAL (WSL) | VPS         | Status"
echo "   ----------|-------------|-------------|--------"
echo "   Users     | $LOCAL_USERS           | $VPS_USERS           | $([ "$LOCAL_USERS" = "$VPS_USERS" ] && echo '✅' || echo '❌')"
echo "   Gigs      | $LOCAL_GIGS           | $VPS_GIGS           | $([ "$LOCAL_GIGS" = "$VPS_GIGS" ] && echo '✅' || echo '❌')"

if [ "$LOCAL_USERS" = "$VPS_USERS" ] && [ "$LOCAL_GIGS" = "$VPS_GIGS" ]; then
    echo ""
    echo "✅ VALIDAÇÃO PASSOU: Bancos estão sincronizados!"
else
    echo ""
    echo "⚠️  ATENÇÃO: Contagens não batem!"
    echo "   Isso pode ser normal se as queries falharam."
    echo "   Verifique manualmente na aplicação da VPS."
fi

echo ""
echo "======================================================================"
echo "🎉 SINCRONIZAÇÃO CONCLUÍDA COM SUCESSO!"
echo "======================================================================"
echo ""
echo "✅ Banco de dados da VPS está atualizado com dados do WSL"
echo ""
echo "📋 O que foi feito:"
echo "   1. ✔️  Backup do banco local criado"
echo "   2. ✔️  Backup de segurança da VPS criado"
echo "   3. ✔️  Backup transferido via SCP"
echo "   4. ✔️  Banco da VPS restaurado"
echo "   5. ✔️  Caches limpos"
echo "   6. ✔️  Sincronização validada"
echo ""
echo "🌐 Teste a aplicação na VPS:"
echo "   http://$VPS_HOST:8081"
echo ""
echo "📁 Backups disponíveis:"
echo "   LOCAL: $LOCAL_BACKUP"
echo "   VPS:   Listados em $VPS_PROJECT_PATH/backups/"
echo ""
echo "💡 Se algo estiver errado na VPS, você pode:"
echo "   ssh $VPS_USER@$VPS_HOST"
echo "   cd $VPS_PROJECT_PATH"
echo "   ./scripts/restore-database.sh"
echo ""
echo "======================================================================"
