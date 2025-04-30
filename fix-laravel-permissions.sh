#!/bin/bash

# Caminho do projeto Laravel
LARAVEL_DIR="/var/www/html/eventos"

# Dono dos arquivos (ajuste conforme necessário)
USER_NAME="www-data"
GROUP_NAME="www-data"

echo "🛠️ Corrigindo permissões do Laravel em: $LARAVEL_DIR"
echo "📁 Dono será: $USER_NAME:$GROUP_NAME"

# Ajusta dono dos arquivos e pastas
chown -R $USER_NAME:$GROUP_NAME "$LARAVEL_DIR"

# Permissões adequadas para diretórios e arquivos
find "$LARAVEL_DIR" -type f -exec chmod 664 {} \;
find "$LARAVEL_DIR" -type d -exec chmod 775 {} \;

# Diretórios específicos que precisam de escrita
chmod -R 775 "$LARAVEL_DIR/storage"
chmod -R 775 "$LARAVEL_DIR/bootstrap/cache"

echo "✅ Permissões ajustadas com sucesso!"
