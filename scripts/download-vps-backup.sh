#!/bin/bash
# EventosPro - Download de Backup da VPS
# =======================================
# Baixa o backup mais recente da VPS para o ambiente local
# VPS: nandodev@177.93.106.24

set -e

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m'

VPS_USER="nandodev"
VPS_HOST="177.93.106.24"
VPS_PORT="6985"
VPS_PATH="/home/nandodev/projects/EventosPro"
VPS_BACKUP_DIR="/home/nandodev/backups/eventospro"
LOCAL_BACKUP_DIR="backups"

echo -e "${BLUE}📥 EventosPro - Download de Backup da VPS${NC}"
echo -e "${BLUE}==========================================${NC}"
echo -e "${BLUE}VPS: ${VPS_USER}@${VPS_HOST}:${VPS_PORT}${NC}"
echo ""

# Criar diretório de backups local se não existir
mkdir -p ${LOCAL_BACKUP_DIR}

# Verificar conexão SSH
echo -e "${YELLOW}🔐 Testando conexão SSH com a VPS...${NC}"
if ! ssh -p ${VPS_PORT} -o ConnectTimeout=5 ${VPS_USER}@${VPS_HOST} "echo 'Conexão OK'" &>/dev/null; then
    echo -e "${RED}❌ Não foi possível conectar na VPS${NC}"
    echo -e "${YELLOW}Verifique:${NC}"
    echo "  - Conexão com a internet"
    echo "  - Chave SSH configurada"
    echo "  - IP, porta e usuário corretos (porta ${VPS_PORT})"
    exit 1
fi
echo -e "${GREEN}✅ Conexão SSH OK${NC}"

# Perguntar se quer criar um novo backup ou baixar existente
echo ""
echo -e "${YELLOW}Escolha uma opção:${NC}"
echo "  1) Criar NOVO backup na VPS e baixar"
echo "  2) Baixar backup mais recente existente"
echo "  3) Listar backups disponíveis e escolher"
echo ""
read -p "Opção (1-3): " OPTION

case $OPTION in
    1)
        echo ""
        echo -e "${BLUE}💾 Criando novo backup na VPS...${NC}"
        ssh -p ${VPS_PORT} ${VPS_USER}@${VPS_HOST} "cd ${VPS_PATH} && ./scripts/backup-database-vps.sh"

        echo ""
        echo -e "${BLUE}📥 Baixando backup recém-criado...${NC}"
        LATEST_BACKUP=$(ssh -p ${VPS_PORT} ${VPS_USER}@${VPS_HOST} "ls -t ${VPS_BACKUP_DIR}/*.sql.gz 2>/dev/null | head -n 1")

        if [ -z "$LATEST_BACKUP" ]; then
            echo -e "${RED}❌ Nenhum backup encontrado na VPS${NC}"
            exit 1
        fi

        BACKUP_NAME=$(basename ${LATEST_BACKUP})
        echo -e "${GREEN}Baixando: ${BACKUP_NAME}${NC}"
        scp -P ${VPS_PORT} ${VPS_USER}@${VPS_HOST}:${LATEST_BACKUP} ${LOCAL_BACKUP_DIR}/
        echo -e "${GREEN}✅ Backup baixado: ${LOCAL_BACKUP_DIR}/${BACKUP_NAME}${NC}"
        ;;

    2)
        echo ""
        echo -e "${BLUE}📥 Obtendo backup mais recente...${NC}"
        LATEST_BACKUP=$(ssh -p ${VPS_PORT} ${VPS_USER}@${VPS_HOST} "ls -t ${VPS_BACKUP_DIR}/*.sql.gz 2>/dev/null | head -n 1")

        if [ -z "$LATEST_BACKUP" ]; then
            echo -e "${RED}❌ Nenhum backup encontrado na VPS${NC}"
            echo -e "${YELLOW}Tente a opção 1 para criar um novo backup${NC}"
            exit 1
        fi

        BACKUP_NAME=$(basename ${LATEST_BACKUP})
        BACKUP_DATE=$(ssh -p ${VPS_PORT} ${VPS_USER}@${VPS_HOST} "ls -lh ${LATEST_BACKUP}" | awk '{print $6, $7, $8}')
        BACKUP_SIZE=$(ssh -p ${VPS_PORT} ${VPS_USER}@${VPS_HOST} "ls -lh ${LATEST_BACKUP}" | awk '{print $5}')

        echo -e "${GREEN}Backup encontrado:${NC}"
        echo "  - Nome: ${BACKUP_NAME}"
        echo "  - Data: ${BACKUP_DATE}"
        echo "  - Tamanho: ${BACKUP_SIZE}"
        echo ""

        read -p "Baixar este backup? (s/N): " CONFIRM
        if [ "$CONFIRM" != "s" ] && [ "$CONFIRM" != "S" ]; then
            echo -e "${YELLOW}Download cancelado${NC}"
            exit 0
        fi

        echo -e "${BLUE}📥 Baixando...${NC}"
        scp -P ${VPS_PORT} ${VPS_USER}@${VPS_HOST}:${LATEST_BACKUP} ${LOCAL_BACKUP_DIR}/
        echo -e "${GREEN}✅ Backup baixado: ${LOCAL_BACKUP_DIR}/${BACKUP_NAME}${NC}"
        ;;

    3)
        echo ""
        echo -e "${BLUE}📋 Listando backups disponíveis na VPS...${NC}"
        echo ""
        ssh -p ${VPS_PORT} ${VPS_USER}@${VPS_HOST} "ls -lht ${VPS_BACKUP_DIR}/*.sql.gz 2>/dev/null | head -10 | nl" || {
            echo -e "${RED}❌ Nenhum backup encontrado na VPS${NC}"
            exit 1
        }

        echo ""
        read -p "Digite o número do backup para baixar (ou 0 para cancelar): " CHOICE

        if [ "$CHOICE" -eq "0" ]; then
            echo -e "${YELLOW}Download cancelado${NC}"
            exit 0
        fi

        SELECTED_BACKUP=$(ssh -p ${VPS_PORT} ${VPS_USER}@${VPS_HOST} "ls -t ${VPS_BACKUP_DIR}/*.sql.gz 2>/dev/null | sed -n '${CHOICE}p'")

        if [ -z "$SELECTED_BACKUP" ]; then
            echo -e "${RED}❌ Backup inválido${NC}"
            exit 1
        fi

        BACKUP_NAME=$(basename ${SELECTED_BACKUP})
        echo ""
        echo -e "${BLUE}📥 Baixando: ${BACKUP_NAME}${NC}"
        scp -P ${VPS_PORT} ${VPS_USER}@${VPS_HOST}:${SELECTED_BACKUP} ${LOCAL_BACKUP_DIR}/
        echo -e "${GREEN}✅ Backup baixado: ${LOCAL_BACKUP_DIR}/${BACKUP_NAME}${NC}"
        ;;

    *)
        echo -e "${RED}❌ Opção inválida${NC}"
        exit 1
        ;;
esac

# Sumário final
echo ""
echo -e "${GREEN}============================================${NC}"
echo -e "${GREEN}✅ DOWNLOAD CONCLUÍDO!${NC}"
echo -e "${GREEN}============================================${NC}"
echo ""
echo -e "${BLUE}📝 Próximo passo:${NC}"
echo -e "${BLUE}  ./scripts/restore-database.sh${NC}"
echo ""
echo -e "${YELLOW}💡 Ou rode tudo de uma vez:${NC}"
echo -e "${YELLOW}  ./scripts/restore-from-vps.sh${NC}"
echo ""
