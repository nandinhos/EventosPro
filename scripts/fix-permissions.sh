#!/bin/bash

##
# EventosPro - Fix Permissions Script
#
# Este script corrige as permissões dos diretórios storage e bootstrap/cache
# de dentro do container Laravel, evitando necessidade de sudo no host.
#
# Uso:
#   ./scripts/fix-permissions.sh
##

set -e

# Cores para output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}[EventosPro]${NC} Corrigindo permissões..."

# Corrigir ownership dentro do container
echo "→ Ajustando ownership (sail:sail)..."
./vendor/bin/sail exec laravel.test chown -R sail:sail \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache

# Corrigir permissões dentro do container
echo "→ Ajustando permissões (775)..."
./vendor/bin/sail exec laravel.test chmod -R 775 \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache

# Limpar caches Laravel
echo "→ Limpando caches do Laravel..."
./vendor/bin/sail artisan config:clear > /dev/null 2>&1
./vendor/bin/sail artisan cache:clear > /dev/null 2>&1
./vendor/bin/sail artisan view:clear > /dev/null 2>&1

echo ""
echo -e "${GREEN}✓ Permissões corrigidas com sucesso!${NC}"
echo ""
echo "Validação:"
echo "  - Storage writable: ✓"
echo "  - Bootstrap cache writable: ✓"
echo "  - Caches limpos: ✓"
