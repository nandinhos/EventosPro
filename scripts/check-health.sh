#!/bin/bash

##
# EventosPro - Health Check Script
#
# Este script aguarda até que todos os containers do EventosPro
# estejam saudáveis (healthy) após inicialização do VPS.
#
# Uso:
#   ./scripts/check-health.sh
#
# Exit codes:
#   0 - Todos os containers estão healthy
#   1 - Timeout ou erro
##

set -e

# Configurações
TIMEOUT=120  # 2 minutos
CHECK_INTERVAL=5  # Verificar a cada 5 segundos
PROJECT_NAME="eventospro"

# Cores para output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${YELLOW}[EventosPro]${NC} Aguardando containers ficarem saudáveis..."
echo "Timeout configurado: ${TIMEOUT}s"
echo ""

# Função para verificar se todos os containers estão healthy
check_containers() {
    local containers=$(docker ps --filter "name=${PROJECT_NAME}" --format "{{.Names}}\t{{.Status}}")

    if [ -z "$containers" ]; then
        echo -e "${RED}[ERRO]${NC} Nenhum container do EventosPro está rodando!"
        return 1
    fi

    # Verificar se algum container não está healthy
    local unhealthy=$(echo "$containers" | grep -v "healthy" | grep -v "phpmyadmin" || true)

    if [ -z "$unhealthy" ]; then
        return 0  # Todos healthy
    else
        return 1  # Ainda tem containers não-healthy
    fi
}

# Aguardar até containers ficarem healthy
elapsed=0
while [ $elapsed -lt $TIMEOUT ]; do
    if check_containers; then
        echo ""
        echo -e "${GREEN}✓ Todos os containers do EventosPro estão saudáveis!${NC}"
        echo ""
        echo "Status dos containers:"
        docker ps --filter "name=${PROJECT_NAME}" --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
        exit 0
    fi

    echo -n "."
    sleep $CHECK_INTERVAL
    elapsed=$((elapsed + CHECK_INTERVAL))
done

# Timeout
echo ""
echo -e "${RED}[TIMEOUT]${NC} Containers não ficaram healthy em ${TIMEOUT}s"
echo ""
echo "Status atual dos containers:"
docker ps --filter "name=${PROJECT_NAME}" --format "table {{.Names}}\t{{.Status}}"
echo ""
echo "Verificar logs com:"
echo "  docker logs eventospro-laravel.test-1"
echo "  docker logs eventospro-mysql-1"

exit 1
