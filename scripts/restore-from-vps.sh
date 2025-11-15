#!/bin/bash
# EventosPro - Download e Restauração Completa de Backup da VPS
# ==============================================================
# Script all-in-one: baixa backup da VPS e restaura no ambiente local
# VPS: nandodev@177.93.106.24

set -e

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

VPS_USER="nandodev"
VPS_HOST="177.93.106.24"
VPS_PORT="6985"
VPS_PATH="/home/nandodev/projects/EventosPro"
VPS_BACKUP_DIR="/home/nandodev/backups/eventospro"
BACKUP_DIR="backups"

echo -e "${CYAN}"
cat << "EOF"
 _____                 _             ____
| ____|_   _____ _ __ | |_ ___  ___ |  _ \ _ __ ___
|  _| \ \ / / _ \ '_ \| __/ _ \/ __|| |_) | '__/ _ \
| |___ \ V /  __/ | | | || (_) \__ \|  __/| | | (_) |
|_____| \_/ \___|_| |_|\__\___/|___/|_|   |_|  \___/

   Restauração de Backup da VPS → Local
EOF
echo -e "${NC}"
echo -e "${BLUE}VPS: ${VPS_USER}@${VPS_HOST}:${VPS_PORT}${NC}"
echo ""

# Criar diretório de backups
mkdir -p ${BACKUP_DIR}

# ====================================================
# PASSO 1: BAIXAR BACKUP DA VPS
# ====================================================
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}PASSO 1: Baixar Backup da VPS${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

# Verificar conexão
echo -e "${YELLOW}🔐 Testando conexão SSH...${NC}"
if ! ssh -p ${VPS_PORT} -o ConnectTimeout=5 ${VPS_USER}@${VPS_HOST} "echo 'OK'" &>/dev/null; then
    echo -e "${RED}❌ Não foi possível conectar na VPS${NC}"
    echo -e "${YELLOW}Verifique a conexão e credenciais (porta ${VPS_PORT})${NC}"
    exit 1
fi
echo -e "${GREEN}✅ Conexão SSH OK${NC}"

# Perguntar se quer criar novo backup
echo ""
read -p "Criar NOVO backup na VPS antes de baixar? (recomendado) (S/n): " CREATE_NEW
CREATE_NEW=${CREATE_NEW:-s}

if [ "$CREATE_NEW" == "s" ] || [ "$CREATE_NEW" == "S" ]; then
    echo ""
    echo -e "${BLUE}💾 Criando backup na VPS...${NC}"
    ssh -p ${VPS_PORT} ${VPS_USER}@${VPS_HOST} "cd ${VPS_PATH} && ./scripts/backup-database-vps.sh" || {
        echo -e "${RED}❌ Falha ao criar backup${NC}"
        exit 1
    }
fi

# Obter backup mais recente
echo ""
echo -e "${BLUE}📥 Obtendo backup mais recente da VPS...${NC}"
LATEST_BACKUP=$(ssh -p ${VPS_PORT} ${VPS_USER}@${VPS_HOST} "ls -t ${VPS_BACKUP_DIR}/*.sql.gz 2>/dev/null | head -n 1")

if [ -z "$LATEST_BACKUP" ]; then
    echo -e "${RED}❌ Nenhum backup encontrado na VPS${NC}"
    exit 1
fi

BACKUP_NAME=$(basename ${LATEST_BACKUP})
BACKUP_SIZE=$(ssh -p ${VPS_PORT} ${VPS_USER}@${VPS_HOST} "du -h ${LATEST_BACKUP}" | cut -f1)

echo -e "${GREEN}Backup encontrado:${NC}"
echo "  📦 ${BACKUP_NAME}"
echo "  📊 ${BACKUP_SIZE}"

# Baixar backup
echo ""
echo -e "${BLUE}📥 Baixando backup...${NC}"
scp -P ${VPS_PORT} ${VPS_USER}@${VPS_HOST}:${LATEST_BACKUP} ${BACKUP_DIR}/ || {
    echo -e "${RED}❌ Falha ao baixar backup${NC}"
    exit 1
}
echo -e "${GREEN}✅ Download concluído${NC}"

LOCAL_BACKUP="${BACKUP_DIR}/${BACKUP_NAME}"

# ====================================================
# PASSO 2: BACKUP DE SEGURANÇA DO BANCO LOCAL
# ====================================================
echo ""
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}PASSO 2: Backup de Segurança (Local)${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo -e "${YELLOW}💾 Criando backup do banco local atual...${NC}"

SAFETY_BACKUP="${BACKUP_DIR}/pre-restore-local-$(date +%Y%m%d-%H%M%S).sql.gz"
../vendor/bin/sail exec mysql mysqldump \
    -u sail \
    -ppassword \
    --single-transaction \
    --quick \
    --lock-tables=false \
    eventospro | gzip > ${SAFETY_BACKUP}

echo -e "${GREEN}✅ Backup de segurança criado: ${SAFETY_BACKUP}${NC}"

# ====================================================
# PASSO 3: CONFIRMAÇÃO FINAL
# ====================================================
echo ""
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}PASSO 3: Confirmação${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo -e "${RED}⚠️  ATENÇÃO: Esta operação vai SUBSTITUIR o banco de dados local!${NC}"
echo ""
echo -e "${YELLOW}Resumo:${NC}"
echo "  📥 Backup da VPS: ${BACKUP_NAME} (${BACKUP_SIZE})"
echo "  💾 Backup local: ${SAFETY_BACKUP}"
echo "  🎯 Destino: Banco de dados local (eventospro)"
echo ""
read -p "Confirmar restauração? (digite 'SIM' em maiúsculas): " CONFIRM

if [ "$CONFIRM" != "SIM" ]; then
    echo -e "${YELLOW}❌ Restauração cancelada${NC}"
    echo -e "${GREEN}✅ Backup da VPS baixado em: ${LOCAL_BACKUP}${NC}"
    exit 0
fi

# ====================================================
# PASSO 4: RESTAURAÇÃO
# ====================================================
echo ""
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}PASSO 4: Restauração${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

# Descomprimir
echo -e "${BLUE}📦 Descomprimindo backup...${NC}"
gunzip -c ${LOCAL_BACKUP} > /tmp/restore.sql

BACKUP_SIZE_UNCOMPRESSED=$(du -h /tmp/restore.sql | cut -f1)
echo -e "${GREEN}✅ Descomprimido: ${BACKUP_SIZE_UNCOMPRESSED}${NC}"

# Restaurar
echo ""
echo -e "${BLUE}🔄 Restaurando banco de dados...${NC}"
echo -e "${YELLOW}⏳ Isso pode levar alguns minutos...${NC}"

# Criar database se não existir
../vendor/bin/sail exec mysql mysql -uroot -e "CREATE DATABASE IF NOT EXISTS eventospro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Restaurar backup
../vendor/bin/sail mysql eventospro < /tmp/restore.sql
echo -e "${GREEN}✅ Banco restaurado${NC}"

# Limpar temporários
rm /tmp/restore.sql

# ====================================================
# PASSO 5: PÓS-RESTAURAÇÃO
# ====================================================
echo ""
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}PASSO 5: Pós-Restauração${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

# Limpar caches
echo -e "${BLUE}🧹 Limpando caches do Laravel...${NC}"
../vendor/bin/sail artisan cache:clear
../vendor/bin/sail artisan config:clear
../vendor/bin/sail artisan view:clear
echo -e "${GREEN}✅ Caches limpos${NC}"

# Verificar migrations pendentes
echo ""
echo -e "${BLUE}🔍 Verificando migrations pendentes...${NC}"
PENDING=$(../vendor/bin/sail artisan migrate:status 2>/dev/null | grep -c "Pending" || echo "0")

if [ "$PENDING" != "0" ]; then
    echo -e "${YELLOW}⚠️  Há ${PENDING} migration(s) pendente(s)${NC}"
    read -p "Executar migrations agora? (s/N): " RUN_MIGRATIONS

    if [ "$RUN_MIGRATIONS" == "s" ] || [ "$RUN_MIGRATIONS" == "S" ]; then
        ../vendor/bin/sail artisan migrate
        echo -e "${GREEN}✅ Migrations executadas${NC}"
    else
        echo -e "${YELLOW}⚠️  Execute depois: sail artisan migrate${NC}"
    fi
else
    echo -e "${GREEN}✅ Nenhuma migration pendente${NC}"
fi

# ====================================================
# SUMÁRIO FINAL
# ====================================================
echo ""
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}✅ RESTAURAÇÃO CONCLUÍDA COM SUCESSO!${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo -e "${CYAN}📊 Resumo:${NC}"
echo "  ✓ Backup baixado da VPS"
echo "  ✓ Backup de segurança local criado"
echo "  ✓ Banco de dados restaurado"
echo "  ✓ Caches limpos"
echo ""
echo -e "${CYAN}📝 Arquivos importantes:${NC}"
echo "  📥 Backup VPS: ${LOCAL_BACKUP}"
echo "  💾 Backup local: ${SAFETY_BACKUP}"
echo ""
echo -e "${CYAN}🔧 Comandos úteis:${NC}"
echo "  Verificar dados: sail artisan tinker"
echo "  Ver logs: sail artisan pail"
echo "  Testar app: http://localhost"
echo ""
echo -e "${YELLOW}💡 Dica: Você está com dados REAIS de produção!${NC}"
echo -e "${YELLOW}   Senhas e dados sensíveis estão preservados.${NC}"
echo ""
