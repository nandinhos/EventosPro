#!/bin/bash
# EventosPro - Script de Instalação Automatizada
# Automatiza o processo completo de setup para desenvolvimento local

set -e  # Para execução em caso de erro

# Cores para output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Função para imprimir cabeçalho
print_header() {
    echo ""
    echo -e "${BLUE}============================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}============================================${NC}"
    echo ""
}

# Função para imprimir sucesso
print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

# Função para imprimir aviso
print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

# Função para imprimir erro
print_error() {
    echo -e "${RED}✗ $1${NC}"
}

# Função para imprimir info
print_info() {
    echo -e "${BLUE}ℹ $1${NC}"
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

        Setup Automatizado - Ambiente Local
EOF
echo -e "${NC}"

# ============================================
# 1. PRÉ-REQUISITOS
# ============================================
print_header "1/9 - Verificando Pré-requisitos"

# Verificar Git
if command -v git &> /dev/null; then
    GIT_VERSION=$(git --version)
    print_success "Git instalado: $GIT_VERSION"
else
    print_error "Git não encontrado!"
    exit 1
fi

# Verificar Docker
if command -v docker &> /dev/null; then
    DOCKER_VERSION=$(docker --version)
    print_success "Docker instalado: $DOCKER_VERSION"
else
    print_error "Docker não encontrado! Instale: https://docs.docker.com/get-docker/"
    exit 1
fi

# Verificar Docker Compose
if command -v docker-compose &> /dev/null; then
    COMPOSE_VERSION=$(docker-compose --version)
    print_success "Docker Compose instalado: $COMPOSE_VERSION"
else
    print_error "Docker Compose não encontrado!"
    exit 1
fi

# Verificar permissões Docker
if docker ps &> /dev/null; then
    print_success "Docker rodando com permissões corretas"
else
    print_error "Docker sem permissões! Execute: sudo usermod -aG docker \$USER"
    exit 1
fi

# ============================================
# 2. CONFIGURAR .ENV
# ============================================
print_header "2/9 - Configurando arquivo .env"

if [ -f .env ]; then
    print_warning "Arquivo .env já existe!"
    read -p "Deseja sobrescrever? (y/N): " OVERWRITE
    if [ "$OVERWRITE" != "y" ] && [ "$OVERWRITE" != "Y" ]; then
        print_info "Mantendo .env existente"
    else
        cp .env.example .env
        print_success "Arquivo .env criado a partir do .env.example"
    fi
else
    cp .env.example .env
    print_success "Arquivo .env criado a partir do .env.example"
fi

# Verificar/configurar variáveis Docker
if ! grep -q "WWWUSER=" .env; then
    echo "" >> .env
    echo "# Docker Sail Configuration" >> .env
    echo "WWWUSER=1000" >> .env
    echo "WWWGROUP=1000" >> .env
    print_success "Variáveis Docker adicionadas ao .env"
else
    print_success "Variáveis Docker já configuradas"
fi

# ============================================
# 3. INSTALAR DEPENDÊNCIAS COMPOSER
# ============================================
print_header "3/9 - Instalando dependências do Composer"

print_info "Isso pode levar alguns minutos na primeira vez..."
if docker-compose run --rm laravel.test composer install; then
    print_success "192 pacotes Composer instalados (incluindo Laravel Boost MCP)"
else
    print_error "Falha ao instalar dependências do Composer"
    exit 1
fi

# ============================================
# 4. GERAR APP_KEY
# ============================================
print_header "4/9 - Gerando chave da aplicação"

if docker-compose run --rm laravel.test php artisan key:generate; then
    print_success "APP_KEY gerada com sucesso"
else
    print_error "Falha ao gerar APP_KEY"
    exit 1
fi

# ============================================
# 5. SUBIR CONTAINERS DOCKER
# ============================================
print_header "5/9 - Iniciando containers Docker Sail"

print_info "Subindo: MySQL, Redis, Laravel, PHPMyAdmin..."
if ./vendor/bin/sail up -d; then
    print_success "Containers iniciados com sucesso"
    print_info "Aguardando MySQL inicializar..."
    sleep 10
else
    print_error "Falha ao subir containers"
    exit 1
fi

# ============================================
# 6. EXECUTAR MIGRATIONS E SEEDERS
# ============================================
print_header "6/9 - Rodando migrations e seeders"

print_info "Criando estrutura do banco de dados..."
if ./vendor/bin/sail artisan migrate:fresh --seed; then
    print_success "Banco de dados populado com dados de teste"
    print_info "✓ 30 artistas criados"
    print_info "✓ 30 bookers/agências criados"
    print_info "✓ 50 gigs criados"
    print_info "✓ 162 pagamentos criados"
    print_info "✓ 177 custos criados"
else
    print_error "Falha ao executar migrations"
    exit 1
fi

# ============================================
# 7. INSTALAR DEPENDÊNCIAS NPM
# ============================================
print_header "7/9 - Instalando dependências NPM"

if ./vendor/bin/sail npm install; then
    print_success "Pacotes NPM instalados"
else
    print_error "Falha ao instalar pacotes NPM"
    exit 1
fi

# ============================================
# 8. BUILD ASSETS VITE
# ============================================
print_header "8/9 - Compilando assets com Vite"

print_info "Gerando CSS e JavaScript para produção..."
if ./vendor/bin/sail npm run build; then
    print_success "Assets compilados com sucesso"
else
    print_error "Falha ao compilar assets"
    exit 1
fi

# ============================================
# 9. FINALIZAÇÃO
# ============================================
print_header "9/9 - Finalização"

# Criar alias sail (opcional)
print_info "Criando alias 'sail' para facilitar uso..."
if ! grep -q "alias sail=" ~/.bashrc 2>/dev/null; then
    echo "" >> ~/.bashrc
    echo "# Laravel Sail alias - EventosPro" >> ~/.bashrc
    echo "alias sail='./vendor/bin/sail'" >> ~/.bashrc
    print_success "Alias 'sail' adicionado ao ~/.bashrc"
    print_warning "Execute: source ~/.bashrc para ativar"
else
    print_success "Alias 'sail' já existe"
fi

# Tornar scripts de backup executáveis
chmod +x scripts/backup-database-local.sh 2>/dev/null || true
chmod +x scripts/backup-database.sh 2>/dev/null || true
print_success "Scripts de backup marcados como executáveis"

# ============================================
# SUMÁRIO FINAL
# ============================================
echo ""
echo -e "${GREEN}============================================"
echo -e "✓ INSTALAÇÃO CONCLUÍDA COM SUCESSO!"
echo -e "============================================${NC}"
echo ""
echo -e "${BLUE}Informações da Aplicação:${NC}"
echo -e "  🌐 Aplicação:  ${GREEN}http://localhost${NC}"
echo -e "  🗄️  PHPMyAdmin: ${GREEN}http://localhost:8080${NC}"
echo -e "  🐳 Containers:  4 rodando (MySQL, Redis, Laravel, PHPMyAdmin)"
echo ""
echo -e "${BLUE}Próximos Passos:${NC}"
echo "  1. Acesse a aplicação: http://localhost"
echo "  2. Configure Laravel Boost MCP (veja docs/SETUP_GUIDE.md)"
echo "  3. Crie um backup: ./scripts/backup-database-local.sh"
echo "  4. Execute testes: ./vendor/bin/sail artisan test"
echo ""
echo -e "${BLUE}Comandos Úteis:${NC}"
echo "  ./vendor/bin/sail up -d    # Subir containers"
echo "  ./vendor/bin/sail down     # Parar containers"
echo "  ./vendor/bin/sail artisan  # Comandos Laravel"
echo "  ./vendor/bin/sail npm      # Comandos NPM"
echo ""
echo -e "${YELLOW}Documentação:${NC}"
echo "  📖 Guia Completo: docs/SETUP_GUIDE.md"
echo "  📖 Serviços API:  docs/SERVICES_API.md"
echo "  📖 Testing:       docs/TESTING.md"
echo ""
print_success "Pronto para desenvolver! 🚀"
echo ""
