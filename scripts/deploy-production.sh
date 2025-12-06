#!/bin/bash
# ==============================================================
# EventosPro - Deploy Otimizado para VPS de Homologação
# ==============================================================
# Script de deploy sem dependências de desenvolvimento
# Otimizado para performance em ambiente de homologação/produção
# ==============================================================

set -e  # Para execução em caso de erro

# Cores para output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

# ====================================================
# CONFIGURAÇÕES
# ====================================================
PROJECT_NAME="EventosPro"
DEPLOY_PATH="/var/www/${PROJECT_NAME}"
REPO_URL="git@github.com:nandinhos/EventosPro.git"
BRANCH="main"

# Verificar variável de ambiente ou argumento
if [ -n "$DEPLOY_BRANCH" ]; then
    BRANCH="$DEPLOY_BRANCH"
fi

while [[ $# -gt 0 ]]; do
    case "$1" in
        --branch|-b)
            BRANCH="$2"
            shift 2
            ;;
        --quick|-q)
            QUICK_MODE=true
            shift
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
  ____             _               ____                 _ 
 |  _ \  ___ _ __ | | ___  _   _  |  _ \ _ __ ___   __| |
 | | | |/ _ \ '_ \| |/ _ \| | | | | |_) | '__/ _ \ / _` |
 | |_| |  __/ |_) | | (_) | |_| | |  __/| | | (_) | (_| |
 |____/ \___| .__/|_|\___/ \__, | |_|   |_|  \___/ \__,_|
            |_|            |___/                         
   EventosPro - Deploy Otimizado para Produção
EOF
echo -e "${NC}"
echo -e "${YELLOW}  Branch: ${BRANCH} | Modo: ${QUICK_MODE:-normal}${NC}"
echo ""

# ====================================================
# 1. VERIFICAR AMBIENTE
# ====================================================
print_header "1/8 Verificando Ambiente"

print_info "Servidor: $(hostname)"
print_info "Usuário: $(whoami)"
print_info "Data: $(date '+%Y-%m-%d %H:%M:%S')"

# Verificar Docker
if command -v docker &> /dev/null; then
    print_success "Docker instalado: $(docker --version | awk '{print $3}')"
else
    print_error "Docker não encontrado!"
    exit 1
fi

# Verificar Docker Compose
if command -v docker compose &> /dev/null || command -v docker-compose &> /dev/null; then
    print_success "Docker Compose disponível"
else
    print_error "Docker Compose não encontrado!"
    exit 1
fi

# ====================================================
# 2. ATUALIZAR CÓDIGO
# ====================================================
print_header "2/8 Atualizando Código"

cd ${DEPLOY_PATH}

# Fazer backup do .env antes do pull
if [ -f .env ]; then
    cp .env .env.backup.$(date +%Y%m%d%H%M%S)
    print_success "Backup do .env criado"
fi

print_step "Atualizando repositório (branch: ${BRANCH})..."
git fetch origin
git checkout ${BRANCH}
git pull origin ${BRANCH}
print_success "Código atualizado para: $(git rev-parse --short HEAD)"

# Restaurar .env se necessário
if [ ! -f .env ] && [ -f .env.backup.* ]; then
    cp $(ls -t .env.backup.* | head -1) .env
    print_warning ".env restaurado do backup"
fi

# ====================================================
# 3. INSTALAR DEPENDÊNCIAS (PRODUÇÃO)
# ====================================================
print_header "3/8 Instalando Dependências (Produção)"

print_step "Composer install (sem dev, otimizado)..."
docker compose exec -T laravel.test composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --prefer-dist \
    --no-progress

print_success "Dependências PHP instaladas (sem dev)"

# ====================================================
# 4. BUILD DE ASSETS
# ====================================================
print_header "4/8 Build de Assets"

print_step "NPM install (produção)..."
docker compose exec -T laravel.test npm ci --omit=dev --silent

print_step "Build de assets..."
docker compose exec -T laravel.test npm run build

print_success "Assets compilados"

# ====================================================
# 5. OTIMIZAÇÕES LARAVEL
# ====================================================
print_header "5/8 Otimizando Laravel para Produção"

print_step "Limpando caches antigos..."
docker compose exec -T laravel.test php artisan cache:clear --quiet
docker compose exec -T laravel.test php artisan config:clear --quiet
docker compose exec -T laravel.test php artisan route:clear --quiet
docker compose exec -T laravel.test php artisan view:clear --quiet

print_step "Gerando caches otimizados..."
docker compose exec -T laravel.test php artisan config:cache
docker compose exec -T laravel.test php artisan route:cache
docker compose exec -T laravel.test php artisan view:cache
docker compose exec -T laravel.test php artisan event:cache

print_success "Caches de produção gerados"

# Otimização do Composer autoloader
print_step "Otimizando autoloader..."
docker compose exec -T laravel.test composer dump-autoload --optimize --classmap-authoritative

print_success "Autoloader otimizado"

# ====================================================
# 6. MIGRATIONS
# ====================================================
print_header "6/8 Migrations"

PENDING=$(docker compose exec -T laravel.test php artisan migrate:status 2>/dev/null | grep -c "Pending" || echo "0")

if [ "$PENDING" != "0" ]; then
    print_warning "Há ${PENDING} migration(s) pendente(s)"
    
    if [ "$QUICK_MODE" == "true" ]; then
        print_step "Executando migrations automaticamente (modo quick)..."
        docker compose exec -T laravel.test php artisan migrate --force
        print_success "Migrations executadas"
    else
        read -p "  Executar migrations? (S/n): " RUN_MIGRATIONS
        RUN_MIGRATIONS=${RUN_MIGRATIONS:-s}
        
        if [ "$RUN_MIGRATIONS" == "s" ] || [ "$RUN_MIGRATIONS" == "S" ]; then
            docker compose exec -T laravel.test php artisan migrate --force
            print_success "Migrations executadas"
        else
            print_warning "Migrations puladas - execute manualmente depois"
        fi
    fi
else
    print_success "Nenhuma migration pendente"
fi

# ====================================================
# 7. REINICIAR SERVIÇOS
# ====================================================
print_header "7/8 Reiniciando Serviços"

print_step "Reiniciando containers..."
docker compose restart

print_step "Aguardando serviços ficarem prontos (10s)..."
sleep 10

# Health check
print_step "Verificando saúde da aplicação..."
HEALTH=$(docker compose exec -T laravel.test php artisan --version 2>/dev/null || echo "FAIL")

if [[ "$HEALTH" == *"Laravel"* ]]; then
    print_success "Aplicação saudável: ${HEALTH}"
else
    print_error "Health check falhou!"
    exit 1
fi

# ====================================================
# 8. LIMPEZA
# ====================================================
print_header "8/8 Limpeza Final"

# Limpar backups de .env antigos (manter últimos 5)
if ls .env.backup.* 1> /dev/null 2>&1; then
    ls -t .env.backup.* | tail -n +6 | xargs rm -f 2>/dev/null || true
    print_success "Backups antigos do .env limpos"
fi

# Limpar logs muito antigos
docker compose exec -T laravel.test find storage/logs -name "*.log" -mtime +30 -delete 2>/dev/null || true
print_success "Logs antigos limpos"

# ====================================================
# SUMÁRIO FINAL
# ====================================================
echo ""
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}  ✓ DEPLOY CONCLUÍDO COM SUCESSO!${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo -e "${CYAN}  Informações:${NC}"
echo -e "    📦 Branch: ${GREEN}${BRANCH}${NC}"
echo -e "    📝 Commit: ${GREEN}$(git rev-parse --short HEAD)${NC}"
echo -e "    📁 Path: ${GREEN}${DEPLOY_PATH}${NC}"
echo -e "    ⏰ Data: ${GREEN}$(date '+%Y-%m-%d %H:%M:%S')${NC}"
echo ""
echo -e "${CYAN}  Próximos passos:${NC}"
echo "    1. Testar aplicação no navegador"
echo "    2. Verificar logs: docker compose logs -f laravel.test"
echo ""
echo -e "${GREEN}  Deploy finalizado! 🚀${NC}"
echo ""
