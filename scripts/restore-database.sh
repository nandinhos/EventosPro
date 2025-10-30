#!/bin/bash
# EventosPro - Database Restore Script
# Restores a database backup from the backups directory

set -e

BACKUP_DIR="backups"

echo "📋 Backups disponíveis:"
echo "======================"
ls -lht ${BACKUP_DIR}/*.sql.gz 2>/dev/null | nl || {
    echo "❌ Nenhum backup encontrado no diretório ${BACKUP_DIR}/"
    exit 1
}

echo ""
read -p "Digite o número do backup para restaurar (ou 0 para cancelar): " CHOICE

if [ "$CHOICE" -eq "0" ]; then
    echo "❌ Restauração cancelada"
    exit 0
fi

BACKUP_FILE=$(ls -t ${BACKUP_DIR}/*.sql.gz 2>/dev/null | sed -n "${CHOICE}p")

if [ -z "$BACKUP_FILE" ]; then
    echo "❌ Backup inválido"
    exit 1
fi

echo ""
echo "⚠️  ATENÇÃO: Esta operação vai SUBSTITUIR o banco de dados atual!"
echo "Arquivo selecionado: ${BACKUP_FILE}"
echo ""
read -p "Tem certeza? (digite 'SIM' para confirmar): " CONFIRM

if [ "$CONFIRM" != "SIM" ]; then
    echo "❌ Restauração cancelada"
    exit 0
fi

echo ""
echo "🔄 Descomprimindo backup..."
gunzip -c ${BACKUP_FILE} > /tmp/restore.sql

echo "🔄 Restaurando banco de dados..."
./vendor/bin/sail mysql laravel < /tmp/restore.sql

echo "🧹 Limpando arquivos temporários..."
rm /tmp/restore.sql

echo ""
echo "✅ Banco de dados restaurado com sucesso!"
echo "🔄 Limpando caches do Laravel..."
./vendor/bin/sail artisan cache:clear
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan view:clear

echo ""
echo "🎉 Restauração concluída!"
