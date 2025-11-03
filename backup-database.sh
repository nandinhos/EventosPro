#!/bin/bash

# EventosPro - Script de Backup do Banco de Dados
# ATENÇÃO: Execute este script ANTES de qualquer operação crítica

set -e  # Parar em caso de erro

# Configurações
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/home/nandodev/backups/eventospro"
RETENTION_DAYS=30

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${YELLOW}  EventosPro - Backup do Banco de Dados${NC}"
echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

# Criar diretório de backup se não existir
mkdir -p "$BACKUP_DIR"

# Verificar se container MySQL está rodando
echo -e "${YELLOW}🔍 Verificando container MySQL...${NC}"
if ! docker ps | grep -q "eventospro-mysql-1"; then
    echo -e "${RED}❌ ERRO: Container MySQL não está rodando!${NC}"
    echo -e "${RED}   Execute: ./vendor/bin/sail up -d${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Container MySQL está rodando${NC}"
echo ""

# Nome do arquivo de backup
BACKUP_FILE="$BACKUP_DIR/eventospro_backup_$DATE.sql"
BACKUP_COMPRESSED="$BACKUP_FILE.gz"

# Criar backup
echo -e "${YELLOW}🔄 Criando backup do banco de dados...${NC}"
echo -e "   Arquivo: $BACKUP_FILE"
echo ""

if docker exec eventospro-mysql-1 mysqldump \
    -u root \
    -ppassword \
    --single-transaction \
    --routines \
    --triggers \
    --events \
    --databases eventospro \
    > "$BACKUP_FILE"; then

    echo -e "${GREEN}✅ Backup criado com sucesso!${NC}"

    # Comprimir backup
    echo -e "${YELLOW}🗜️  Comprimindo backup...${NC}"
    gzip -f "$BACKUP_FILE"

    # Tamanho do arquivo
    SIZE=$(du -h "$BACKUP_COMPRESSED" | cut -f1)
    echo -e "${GREEN}✅ Backup comprimido: $SIZE${NC}"
    echo ""

    # Listar backups existentes
    echo -e "${YELLOW}📂 Backups existentes:${NC}"
    ls -lh "$BACKUP_DIR" | grep "\.sql\.gz$" | tail -10
    echo ""

    # Limpar backups antigos
    echo -e "${YELLOW}🧹 Removendo backups com mais de $RETENTION_DAYS dias...${NC}"
    DELETED=$(find "$BACKUP_DIR" -name "*.sql.gz" -mtime +$RETENTION_DAYS -delete -print | wc -l)
    echo -e "${GREEN}✅ $DELETED backup(s) antigo(s) removido(s)${NC}"
    echo ""

    # Resumo
    echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${GREEN}✅ BACKUP CONCLUÍDO COM SUCESSO!${NC}"
    echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "   📁 Arquivo: $BACKUP_COMPRESSED"
    echo -e "   📊 Tamanho: $SIZE"
    echo -e "   📅 Data: $(date '+%d/%m/%Y %H:%M:%S')"
    echo ""

    # Instruções de restore
    echo -e "${YELLOW}ℹ️  Para restaurar este backup, use:${NC}"
    echo -e "   gunzip < $BACKUP_COMPRESSED | docker exec -i eventospro-mysql-1 mysql -u root -ppassword eventospro"
    echo ""

else
    echo -e "${RED}❌ ERRO ao criar backup!${NC}"
    exit 1
fi
