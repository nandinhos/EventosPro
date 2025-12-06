#!/bin/bash
# ==============================================================
# EventosPro - Restaurar Backup do VPS Principal no VPS Secundário
# ==============================================================
# Script para baixar backup do VPS Principal (produção)
# e restaurar no VPS Secundário (homologação)
# ==============================================================

set -e

# Cores para output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

# ====================================================
# CONFIGURAÇÕES DOS SERVIDORES
# ====================================================
# VPS Principal (Produção)
VPS_PROD_USER="nandodev"
VPS_PROD_HOST="177.93.106.24"
VPS_PROD_PORT="6985"
VPS_PROD_BACKUP_DIR="/home/nandodev/backups/eventospro"
VPS_PROD_PROJECT="/var/www/EventosPro"

# Diretório local do projeto
PROJECT_DIR="/var/www/EventosPro"
BACKUP_DIR="${PROJECT_DIR}/backups"

# ====================================================
# FUNÇÕES AUXILIARES
# ====================================================
print_header() {
    echo ""
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${BLUE}  $1${NC}"
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
}

print_success() { echo -e "${GREEN}  ✓ $1${NC}"; }
print_warning() { echo -e "${YELLOW}  ⚠ $1${NC}"; }
print_error() { echo -e "${RED}  ✗ $1${NC}"; }
print_info() { echo -e "${CYAN}  ℹ $1${NC}"; }
print_step() { echo -e "${BLUE}  → $1${NC}"; }

# Banner
clear
echo -e "${CYAN}"
cat << "EOF"
  ____            _                   ____            _                  
 |  _ \ ___  ___| |_ ___  _ __ ___  | __ )  __ _  ___| | ___   _ _ __  
 | |_) / _ \/ __| __/ _ \| '__/ _ \ |  _ \ / _` |/ __| |/ / | | | '_ \ 
 |  _ <  __/\__ \ || (_) | | |  __/ | |_) | (_| | (__|   <| |_| | |_) |
 |_| \_\___||___/\__\___/|_|  \___| |____/ \__,_|\___|_|\_\\__,_| .__/ 
                                                                |_|    
   Restaurar Backup: VPS Produção → VPS Homologação
EOF
echo -e "${NC}"
echo ""

# ====================================================
# VERIFICAR AMBIENTE
# ====================================================
print_header "1/5 Verificando Ambiente"

print_info "Servidor atual: $(hostname)"
print_info "Projeto: ${PROJECT_DIR}"

# Criar diretório de backups
mkdir -p ${BACKUP_DIR}

# Verificar se Docker está rodando
if docker compose ps 2>/dev/null | grep -q "Up"; then
    print_success "Docker Compose está ativo"
else
    print_error "Containers não estão rodando!"
    print_info "Inicie com: docker compose up -d"
    exit 1
fi

# ====================================================
# CONECTAR NO VPS PRINCIPAL
# ====================================================
print_header "2/5 Conectando ao VPS Principal"

print_step "Testando conexão SSH..."
if ! ssh -p ${VPS_PROD_PORT} -o ConnectTimeout=10 ${VPS_PROD_USER}@${VPS_PROD_HOST} "echo 'OK'" &>/dev/null; then
    print_error "Não foi possível conectar no VPS Principal"
    print_info "Verifique: ssh -p ${VPS_PROD_PORT} ${VPS_PROD_USER}@${VPS_PROD_HOST}"
    exit 1
fi
print_success "Conexão SSH OK"

# Perguntar se quer criar novo backup
echo ""
read -p "  Criar NOVO backup na VPS Principal antes de baixar? (S/n): " CREATE_NEW
CREATE_NEW=${CREATE_NEW:-s}

if [ "$CREATE_NEW" == "s" ] || [ "$CREATE_NEW" == "S" ]; then
    print_step "Criando backup na VPS Principal..."
    ssh -p ${VPS_PROD_PORT} ${VPS_PROD_USER}@${VPS_PROD_HOST} \
        "cd ${VPS_PROD_PROJECT}/current 2>/dev/null && ./scripts/backup-database.sh || \
         cd ${VPS_PROD_PROJECT} && ./scripts/backup-database.sh" || {
        print_warning "Não foi possível criar backup, usando existente..."
    }
fi

# Obter backup mais recente
print_step "Obtendo backup mais recente..."
LATEST_BACKUP=$(ssh -p ${VPS_PROD_PORT} ${VPS_PROD_USER}@${VPS_PROD_HOST} \
    "ls -t ${VPS_PROD_BACKUP_DIR}/*.sql.gz 2>/dev/null | head -n 1")

if [ -z "$LATEST_BACKUP" ]; then
    print_error "Nenhum backup encontrado na VPS Principal"
    exit 1
fi

BACKUP_NAME=$(basename ${LATEST_BACKUP})
BACKUP_SIZE=$(ssh -p ${VPS_PROD_PORT} ${VPS_PROD_USER}@${VPS_PROD_HOST} "du -h ${LATEST_BACKUP}" | cut -f1)

print_success "Backup encontrado: ${BACKUP_NAME} (${BACKUP_SIZE})"

# ====================================================
# BAIXAR BACKUP
# ====================================================
print_header "3/5 Baixando Backup"

print_step "Baixando via SCP..."
scp -P ${VPS_PROD_PORT} ${VPS_PROD_USER}@${VPS_PROD_HOST}:${LATEST_BACKUP} ${BACKUP_DIR}/ || {
    print_error "Falha ao baixar backup"
    exit 1
}
print_success "Download concluído"

LOCAL_BACKUP="${BACKUP_DIR}/${BACKUP_NAME}"

# ====================================================
# BACKUP DE SEGURANÇA LOCAL
# ====================================================
print_header "4/5 Backup de Segurança Local"

print_step "Criando backup do banco atual..."

# Carregar variáveis do .env
if [ -f ${PROJECT_DIR}/.env ]; then
    export $(grep -v '^#' ${PROJECT_DIR}/.env | grep 'DB_' | xargs)
fi

SAFETY_BACKUP="${BACKUP_DIR}/pre-restore-$(date +%Y%m%d-%H%M%S).sql.gz"

cd ${PROJECT_DIR}
docker compose exec -T mysql mysqldump \
    -u ${DB_USERNAME:-sail} \
    -p${DB_PASSWORD:-password} \
    --single-transaction \
    --quick \
    --lock-tables=false \
    ${DB_DATABASE:-eventospro} 2>/dev/null | gzip > ${SAFETY_BACKUP}

print_success "Backup de segurança: ${SAFETY_BACKUP}"

# ====================================================
# RESTAURAR BACKUP
# ====================================================
print_header "5/5 Restaurando Banco de Dados"

echo ""
echo -e "${RED}  ⚠️  ATENÇÃO: Esta operação vai SUBSTITUIR o banco de dados!${NC}"
echo ""
echo -e "${YELLOW}  Resumo:${NC}"
echo "    📥 Backup da VPS Prod: ${BACKUP_NAME}"
echo "    💾 Backup de segurança: ${SAFETY_BACKUP}"
echo "    🎯 Destino: Banco local (${DB_DATABASE:-eventospro})"
echo ""
read -p "  Confirmar restauração? (digite 'SIM'): " CONFIRM

if [ "$CONFIRM" != "SIM" ]; then
    print_warning "Restauração cancelada"
    print_success "Backup baixado em: ${LOCAL_BACKUP}"
    exit 0
fi

print_step "Descomprimindo backup..."
gunzip -c ${LOCAL_BACKUP} > /tmp/restore.sql

print_step "Restaurando banco de dados..."
docker compose exec -T mysql mysql \
    -u ${DB_USERNAME:-sail} \
    -p${DB_PASSWORD:-password} \
    ${DB_DATABASE:-eventospro} < /tmp/restore.sql

print_success "Banco restaurado"

# Limpar temporários
rm /tmp/restore.sql

# Pós-restauração
print_step "Limpando caches do Laravel..."
docker compose exec -T laravel.test php artisan cache:clear --quiet
docker compose exec -T laravel.test php artisan config:clear --quiet
docker compose exec -T laravel.test php artisan view:clear --quiet
print_success "Caches limpos"

# Verificar migrations
print_step "Verificando migrations..."
PENDING=$(docker compose exec -T laravel.test php artisan migrate:status 2>/dev/null | grep -c "Pending" || echo "0")

if [ "$PENDING" != "0" ]; then
    print_warning "${PENDING} migration(s) pendente(s)"
    read -p "  Executar migrations? (S/n): " RUN_MIG
    RUN_MIG=${RUN_MIG:-s}
    
    if [ "$RUN_MIG" == "s" ] || [ "$RUN_MIG" == "S" ]; then
        docker compose exec -T laravel.test php artisan migrate --force
        print_success "Migrations executadas"
    fi
else
    print_success "Nenhuma migration pendente"
fi

# ====================================================
# SUMÁRIO FINAL
# ====================================================
echo ""
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}  ✓ RESTAURAÇÃO CONCLUÍDA COM SUCESSO!${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo -e "${CYAN}  Arquivos:${NC}"
echo "    📥 Backup VPS Prod: ${LOCAL_BACKUP}"
echo "    💾 Backup de segurança: ${SAFETY_BACKUP}"
echo ""
echo -e "${YELLOW}  💡 Agora você tem dados de PRODUÇÃO neste VPS!${NC}"
echo ""
