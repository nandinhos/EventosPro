#!/bin/bash
# EventosPro - Database Backup Script
# Creates a compressed backup of the MySQL database

set -e

BACKUP_DIR="backups"
TIMESTAMP=$(date +%Y%m%d-%H%M%S)
BACKUP_FILE="${BACKUP_DIR}/eventospro-backup-${TIMESTAMP}.sql"

# Criar diretório de backups se não existir
mkdir -p ${BACKUP_DIR}

echo "🔄 Criando backup do banco de dados..."

# Executar mysqldump via Sail (direto no container)
# Using --single-transaction for consistent backup without locks
docker exec eventospro-mysql-1 mysqldump \
    -u user \
    -ppassword \
    --single-transaction \
    --routines \
    --triggers \
    laravel > ${BACKUP_FILE}

# Comprimir backup
echo "📦 Comprimindo backup..."
gzip ${BACKUP_FILE}

echo "✅ Backup criado: ${BACKUP_FILE}.gz"
echo "📊 Tamanho: $(du -h ${BACKUP_FILE}.gz | cut -f1)"

# Manter apenas os últimos 10 backups
echo "🧹 Removendo backups antigos (mantendo últimos 10)..."
ls -t ${BACKUP_DIR}/*.sql.gz 2>/dev/null | tail -n +11 | xargs -r rm -v

echo "✅ Backup concluído com sucesso!"
echo ""
echo "📋 Backups disponíveis:"
ls -lh ${BACKUP_DIR}/ | tail -n 10
