#!/bin/sh

# Este script deve ser rodado DENTRO do container da aplicação.
# Ele configura a aplicação para produção preservando os dados existentes.
#
# AVISO: Para resetar o banco do zero (APAGA TUDO), use:
#   php artisan migrate:fresh --force && php artisan db:seed --force

echo "==> Rodando Migrações pendentes..."
php artisan migrate --force

echo "==> Garantindo que usuários Admin existem..."
php artisan db:seed --class=RolesAndPermissionsSeeder --force

echo "==> Limpando Cache do Sistema..."
php artisan optimize:clear

echo "==> OK! Tudo finalizado com sucesso."
