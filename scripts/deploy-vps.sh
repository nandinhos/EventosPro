#!/bin/bash
# EventosPro - Script de Deploy VPS com Zero-Downtime
# ====================================================
# Deploy automatizado para ambiente VPS/Produção
# Usa estratégia de releases/ para zero-downtime
# ====================================================

set -e  # Para execução em caso de erro

# Cores para output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# ====================================================
# CONFIGURAÇÕES
# ====================================================
PROJECT_NAME="EventosPro"
DEPLOY_PATH="/var/www/${PROJECT_NAME}"
REPO_URL="https://github.com/nandinhos/EventosPro.git"
BRANCH="main"
KEEP_RELEASES=5  # Número de releases antigas para manter

# Detectar Docker Compose v1 ou v2
if docker compose version &> /dev/null; then
    DOCKER_COMPOSE="docker compose"
else
    DOCKER_COMPOSE="docker-compose"
fi

if [ -n "$DEPLOY_BRANCH" ]; then
    BRANCH="$DEPLOY_BRANCH"
fi

while [[ $# -gt 0 ]]; do
    case "$1" in
        --branch|-b)
            BRANCH="$2"
            shift 2
            ;;
        *)
            break
            ;;
    esac
done

# ====================================================
# FUNÇÕES AUXILIARES
# ====================================================

print_header() {
    echo ""
    echo -e "${BLUE}============================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}============================================${NC}"
    echo ""
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_info() {
    echo -e "${CYAN}ℹ $1${NC}"
}

# Banner
clear
echo -e "${BLUE}"
cat << "EOF"
 _____                 _             ____
| ____|_   _____ _ __ | |_ ___  ___ |  _ \ _ __ ___
|  _| \ \ / / _ \ '_ \| __/ _ \/ __|| |_) | '__/ _ \
| |___ \ V /  __/ | | | || (_) \__ \|  __/| | | (_) |
|_____| \_/ \___|_| |_|\__\___/|___/|_|   |_|  \___/

       Deploy VPS com Zero-Downtime
EOF
echo -e "${NC}"

# ====================================================
# 1. VERIFICAR AMBIENTE
# ====================================================
print_header "1/10 - Verificando Ambiente VPS"

# Verificar se está no servidor correto
print_info "Servidor: $(hostname)"
print_info "Usuário: $(whoami)"
print_info "Diretório deploy: ${DEPLOY_PATH}"

# Verificar Docker
if command -v docker &> /dev/null && command -v $DOCKER_COMPOSE &> /dev/null; then
    print_success "Docker e Docker Compose instalados"
else
    print_error "Docker ou Docker Compose não encontrados!"
    exit 1
fi

# ====================================================
# 2. CRIAR ESTRUTURA DE DIRETÓRIOS
# ====================================================
print_header "2/10 - Preparando Estrutura de Deploy"

# Criar estrutura se não existir
mkdir -p ${DEPLOY_PATH}/{releases,shared,backups}
mkdir -p ${DEPLOY_PATH}/shared/{storage,logs,.env}

print_success "Estrutura de diretórios preparada"
print_info "  ${DEPLOY_PATH}/releases/  (releases anteriores)"
print_info "  ${DEPLOY_PATH}/shared/    (dados compartilhados)"
print_info "  ${DEPLOY_PATH}/current -> (symlink para release ativa)"

# ====================================================
# 3. VERIFICAR/CRIAR .ENV
# ====================================================
print_header "3/10 - Verificando Configuração .env"

if [ ! -f "${DEPLOY_PATH}/shared/.env" ]; then
    print_warning "Arquivo .env não encontrado em shared/"

    if [ -f ".env.vps.example" ]; then
        print_info "Copiando .env.vps.example para shared/.env"
        cp .env.vps.example ${DEPLOY_PATH}/shared/.env
        print_warning "IMPORTANTE: Edite ${DEPLOY_PATH}/shared/.env com suas configurações!"
        read -p "Pressione ENTER após configurar o .env ou Ctrl+C para cancelar"
    else
        print_error "Arquivo .env.vps.example não encontrado!"
        exit 1
    fi
else
    print_success "Arquivo .env encontrado"
fi

# Carregar variáveis do .env
export $(grep -v '^#' ${DEPLOY_PATH}/shared/.env | xargs)

# ====================================================
# 4. BACKUP AUTOMÁTICO (SE PRODUÇÃO)
# ====================================================
print_header "4/10 - Verificando necessidade de Backup"

if [ "${APP_ENV}" == "production" ]; then
    print_warning "APP_ENV=production detectado - Backup obrigatório!"

    BACKUP_FILE="${DEPLOY_PATH}/backups/pre-deploy-$(date +%Y%m%d-%H%M%S).sql"

    print_info "Criando backup do banco de dados..."
    if [ -f "${DEPLOY_PATH}/current/scripts/backup-database.sh" ]; then
        cd ${DEPLOY_PATH}/current
        ./scripts/backup-database.sh || true
        print_success "Backup criado"
    else
        print_warning "Script de backup não encontrado, pulando..."
    fi
else
    print_info "APP_ENV=${APP_ENV} - Backup não obrigatório"
    read -p "Deseja criar backup mesmo assim? (y/N): " CREATE_BACKUP
    if [ "$CREATE_BACKUP" == "y" ] || [ "$CREATE_BACKUP" == "Y" ]; then
        cd ${DEPLOY_PATH}/current 2>/dev/null || true
        ./scripts/backup-database.sh 2>/dev/null || print_warning "Backup não disponível"
    fi
fi

# ====================================================
# 5. NOVA RELEASE
# ====================================================
print_header "5/10 - Criando Nova Release"

RELEASE_NAME=$(date +%Y%m%d%H%M%S)
RELEASE_PATH="${DEPLOY_PATH}/releases/${RELEASE_NAME}"

print_info "Clonando branch ${BRANCH}..."
git clone --depth 1 --branch ${BRANCH} ${REPO_URL} ${RELEASE_PATH}
print_success "Release ${RELEASE_NAME} criada"

# ====================================================
# 6. SYMLINKS COMPARTILHADOS
# ====================================================
print_header "6/10 - Configurando Symlinks Compartilhados"

cd ${RELEASE_PATH}

# Remover diretórios que serão substituídos por symlinks
rm -rf storage
rm -f .env

# Criar symlinks
ln -s ${DEPLOY_PATH}/shared/.env .env
ln -s ${DEPLOY_PATH}/shared/storage storage

print_success "Symlinks configurados"
print_info "  .env -> ${DEPLOY_PATH}/shared/.env"
print_info "  storage -> ${DEPLOY_PATH}/shared/storage"

# ====================================================
# 7. INSTALAR DEPENDÊNCIAS
# ====================================================
print_header "7/10 - Instalando Dependências"

print_info "Composer install (sem dev)..."
$DOCKER_COMPOSE run --rm laravel.test composer install --optimize-autoloader --no-dev
print_success "Dependências Composer instaladas"

print_info "NPM install e build..."
$DOCKER_COMPOSE run --rm laravel.test npm install
$DOCKER_COMPOSE run --rm laravel.test npm run build
print_success "Assets compilados"

# ====================================================
# 8. OTIMIZAÇÕES LARAVEL
# ====================================================
print_header "8/10 - Otimizando Laravel"

$DOCKER_COMPOSE run --rm laravel.test php artisan config:cache
$DOCKER_COMPOSE run --rm laravel.test php artisan route:cache
$DOCKER_COMPOSE run --rm laravel.test php artisan view:cache

print_success "Caches gerados"

# ====================================================
# 9. MIGRATIONS (SE NECESSÁRIO)
# ====================================================
print_header "9/10 - Verificando Migrations"

read -p "Executar migrations? (y/N): " RUN_MIGRATIONS
if [ "$RUN_MIGRATIONS" == "y" ] || [ "$RUN_MIGRATIONS" == "Y" ]; then
    print_warning "Executando migrations..."
    $DOCKER_COMPOSE run --rm laravel.test php artisan migrate --force
    print_success "Migrations executadas"
else
    print_info "Migrations puladas"
fi

# ====================================================
# 10. SWITCH PARA NOVA RELEASE (ZERO-DOWNTIME)
# ====================================================
print_header "10/10 - Ativando Nova Release"

# Health check da nova release
print_info "Subindo containers da nova release..."
cd ${RELEASE_PATH}
$DOCKER_COMPOSE up -d

# Aguardar containers ficarem healthy
print_info "Aguardando containers ficarem saudáveis (30s)..."
sleep 30

# Verificar se aplicação está respondendo
HEALTH_CHECK=$($DOCKER_COMPOSE exec -T laravel.test php artisan --version 2>/dev/null || echo "FAIL")
if [[ "$HEALTH_CHECK" == *"Laravel"* ]]; then
    print_success "Nova release está saudável"

    # Parar containers da release antiga (se existir)
    if [ -L "${DEPLOY_PATH}/current" ]; then
        OLD_RELEASE=$(readlink ${DEPLOY_PATH}/current)
        print_info "Parando containers da release antiga..."
        cd ${OLD_RELEASE}
        $DOCKER_COMPOSE down || true
    fi

    # Atualizar symlink current para nova release (ATOMIC)
    ln -sfn ${RELEASE_PATH} ${DEPLOY_PATH}/current

    print_success "Deploy concluído! Nova release ativa: ${RELEASE_NAME}"
else
    print_error "Health check falhou! Fazendo rollback..."
    cd ${RELEASE_PATH}
    $DOCKER_COMPOSE down
    rm -rf ${RELEASE_PATH}
    exit 1
fi

# ====================================================
# 11. LIMPEZA DE RELEASES ANTIGAS
# ====================================================
print_header "Limpeza de Releases Antigas"

print_info "Mantendo últimas ${KEEP_RELEASES} releases..."
cd ${DEPLOY_PATH}/releases
RELEASES_TO_DELETE=$(ls -t | tail -n +$((KEEP_RELEASES + 1)))

if [ -n "$RELEASES_TO_DELETE" ]; then
    echo "$RELEASES_TO_DELETE" | while read OLD_RELEASE; do
        print_info "Removendo release antiga: $OLD_RELEASE"
        cd ${DEPLOY_PATH}/releases/${OLD_RELEASE}
        $DOCKER_COMPOSE down 2>/dev/null || true
        rm -rf ${DEPLOY_PATH}/releases/${OLD_RELEASE}
    done
    print_success "Releases antigas removidas"
else
    print_info "Nenhuma release antiga para remover"
fi

# ====================================================
# SUMÁRIO FINAL
# ====================================================
echo ""
echo -e "${GREEN}============================================"
echo -e "✓ DEPLOY CONCLUÍDO COM SUCESSO!"
echo -e "============================================${NC}"
echo ""
echo -e "${BLUE}Informações do Deploy:${NC}"
echo -e "  📦 Release Ativa: ${GREEN}${RELEASE_NAME}${NC}"
echo -e "  📁 Caminho: ${GREEN}${DEPLOY_PATH}/current${NC}"
echo -e "  🌐 Aplicação: ${GREEN}http://$(hostname):${APP_PORT:-8001}${NC}"
echo -e "  🗄️  Banco: ${GREEN}porta ${FORWARD_DB_PORT:-33061}${NC}"
echo ""
echo -e "${BLUE}Próximos Passos:${NC}"
echo "  1. Testar aplicação no navegador"
echo "  2. Verificar logs: cd ${DEPLOY_PATH}/current && $DOCKER_COMPOSE logs -f"
echo "  3. Monitorar métricas e performance"
echo ""
echo -e "${BLUE}Rollback (se necessário):${NC}"
echo "  cd ${DEPLOY_PATH}/releases"
echo "  ls -lt  # Listar releases"
echo "  cd RELEASE_ANTERIOR && $DOCKER_COMPOSE up -d"
echo "  ln -sfn ${DEPLOY_PATH}/releases/RELEASE_ANTERIOR ${DEPLOY_PATH}/current"
echo ""
print_success "Deploy finalizado! 🚀"
echo ""
