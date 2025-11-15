#!/bin/bash
# EventosPro - Database Backup Script (LOCAL/DESENVOLVIMENTO)
# Este script usa Laravel Sail para backup em ambiente local
# Para produção/VPS, use: backup-database.sh

set -e

# Cores para output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Verifica se o Sail está instalado
if [ ! -f "../vendor/bin/sail" ]; then
    echo -e "${RED}❌ Laravel Sail não encontrado!${NC}"
    echo "Execute: docker-compose run --rm laravel.test composer install"
    exit 1
fi

# Verifica se os containers estão rodando
if ! ../vendor/bin/sail ps | grep -q "Up"; then
    echo -e "${RED}❌ Containers do Sail não estão rodando!${NC}"
    echo "Execute: ../vendor/bin/sail up -d"
    exit 1
fi

# Load environment variables from .env
if [ -f .env ]; then
    export $(grep -v '^#' .env | grep 'DB_' | xargs)
else
    echo -e "${RED}❌ Arquivo .env não encontrado!${NC}"
    exit 1
fi

BACKUP_DIR="backups"
TIMESTAMP=$(date +%Y%m%d-%H%M%S)
BACKUP_FILE="${BACKUP_DIR}/eventospro-local-${TIMESTAMP}.sql"

# Criar diretório de backups se não existir
mkdir -p ${BACKUP_DIR}

echo -e "${YELLOW}🔄 Criando backup LOCAL do banco de dados...${NC}"
echo -e "${GREEN}📊 Database: ${DB_DATABASE}${NC}"
echo -e "${GREEN}👤 User: ${DB_USERNAME}${NC}"
echo -e "${GREEN}🏠 Ambiente: DESENVOLVIMENTO (Sail)${NC}"

# Executar mysqldump via Sail
../vendor/bin/sail exec mysql mysqldump \
    -u ${DB_USERNAME} \
    -p${DB_PASSWORD} \
    --single-transaction \
    --routines \
    --triggers \
    ${DB_DATABASE} > ${BACKUP_FILE}

# Comprimir backup
echo -e "${YELLOW}📦 Comprimindo backup...${NC}"
gzip ${BACKUP_FILE}

echo -e "${GREEN}✅ Backup LOCAL criado: ${BACKUP_FILE}.gz${NC}"
echo -e "${GREEN}📊 Tamanho: $(du -h ${BACKUP_FILE}.gz | cut -f1)${NC}"

# Manter apenas os últimos 10 backups locais
echo -e "${YELLOW}🧹 Removendo backups antigos (mantendo últimos 10)...${NC}"
ls -t ${BACKUP_DIR}/*-local-*.sql.gz 2>/dev/null | tail -n +11 | xargs -r rm -v

echo -e "${GREEN}✅ Backup concluído com sucesso!${NC}"
echo ""
echo -e "${GREEN}📋 Backups locais disponíveis:${NC}"
ls -lh ${BACKUP_DIR}/*-local-*.sql.gz 2>/dev/null | tail -n 10

echo ""
echo -e "${YELLOW}ℹ️  Para restaurar um backup, use: scripts/restore-database-local.sh${NC}"
