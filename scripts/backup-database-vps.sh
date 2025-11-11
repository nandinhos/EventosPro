#!/bin/bash
# EventosPro - VPS Database Backup Script
# Creates a compressed backup of the MySQL database for VPS environment

set -e

# VPS specific paths
PROJECT_DIR="/home/nandodev/projects/EventosPro"
BACKUP_DIR="/home/nandodev/backups/eventospro"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="${BACKUP_DIR}/eventospro_backup_${TIMESTAMP}.sql"

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

# Criar diretório de backups se não existir
mkdir -p ${BACKUP_DIR}

# Load environment variables from .env
if [ -f ${PROJECT_DIR}/.env ]; then
    export $(grep -v '^#' ${PROJECT_DIR}/.env | grep 'DB_' | xargs)
else
    echo -e "${RED}❌ Arquivo .env não encontrado em ${PROJECT_DIR}/${NC}"
    exit 1
fi

echo -e "${GREEN}🔄 Criando backup do banco de dados...${NC}"
echo -e "${GREEN}📊 Database: ${DB_DATABASE}${NC}"
echo -e "${GREEN}👤 User: ${DB_USERNAME}${NC}"

# Detectar container MySQL
MYSQL_CONTAINER=$(docker ps --filter "name=mysql" --format "{{.Names}}" | head -n 1)

if [ -z "$MYSQL_CONTAINER" ]; then
    echo -e "${RED}❌ Container MySQL não encontrado!${NC}"
    echo -e "${YELLOW}Containers rodando:${NC}"
    docker ps --format "table {{.Names}}\t{{.Status}}"
    exit 1
fi

echo -e "${GREEN}🐳 Container MySQL: ${MYSQL_CONTAINER}${NC}"

# Executar mysqldump via Docker
# Simplified version without operations requiring special privileges
docker exec ${MYSQL_CONTAINER} mysqldump \
    -u ${DB_USERNAME} \
    -p${DB_PASSWORD} \
    --skip-add-locks \
    --skip-lock-tables \
    --no-tablespaces \
    --quick \
    ${DB_DATABASE} > ${BACKUP_FILE}

# Verificar se backup foi criado
if [ ! -f "${BACKUP_FILE}" ]; then
    echo -e "${RED}❌ Falha ao criar backup${NC}"
    exit 1
fi

# Comprimir backup
echo -e "${GREEN}📦 Comprimindo backup...${NC}"
gzip ${BACKUP_FILE}

echo -e "${GREEN}✅ Backup criado: ${BACKUP_FILE}.gz${NC}"
echo -e "${GREEN}📊 Tamanho: $(du -h ${BACKUP_FILE}.gz | cut -f1)${NC}"

# Manter apenas os últimos 10 backups
echo -e "${YELLOW}🧹 Removendo backups antigos (mantendo últimos 10)...${NC}"
ls -t ${BACKUP_DIR}/*.sql.gz 2>/dev/null | tail -n +11 | xargs -r rm -v

echo -e "${GREEN}✅ Backup concluído com sucesso!${NC}"
echo ""
echo -e "${GREEN}📋 Backups disponíveis:${NC}"
ls -lh ${BACKUP_DIR}/ | tail -n 10
