#!/bin/bash

# Script para corrigir permissões do Laravel EventosPro
# Versão atualizada para o novo ambiente

echo "🔧 Iniciando correção de permissões do Laravel EventosPro..."

# Verificar se estamos no diretório correto
if [ ! -f "artisan" ]; then
    echo "❌ Erro: Este script deve ser executado no diretório raiz do projeto Laravel"
    exit 1
fi

# Verificar se o Sail está rodando
if ./vendor/bin/sail ps | grep -q "laravel.test"; then
    echo "🐳 Containers Docker detectados - aplicando correções dentro do container..."
    
    # Corrigir ownership para o usuário sail dentro do container
    echo "📁 Corrigindo ownership dos diretórios storage e bootstrap/cache..."
    ./vendor/bin/sail exec laravel.test chown -R sail:sail /var/www/html/storage
    ./vendor/bin/sail exec laravel.test chown -R sail:sail /var/www/html/bootstrap/cache
    
    # Aplicar permissões corretas
    echo "🔐 Aplicando permissões 775 aos diretórios..."
    ./vendor/bin/sail exec laravel.test chmod -R 775 /var/www/html/storage
    ./vendor/bin/sail exec laravel.test chmod -R 775 /var/www/html/bootstrap/cache
    
    # Corrigir especificamente o arquivo laravel.log se existir
    if ./vendor/bin/sail exec laravel.test test -f /var/www/html/storage/logs/laravel.log; then
        echo "📝 Corrigindo permissões do arquivo laravel.log..."
        ./vendor/bin/sail exec laravel.test chown sail:sail /var/www/html/storage/logs/laravel.log
        ./vendor/bin/sail exec laravel.test chmod 664 /var/www/html/storage/logs/laravel.log
    fi
    
    # Verificar resultado
    echo "✅ Verificando permissões aplicadas..."
    ./vendor/bin/sail exec laravel.test ls -la /var/www/html/storage/logs/
    
else
    echo "🖥️  Aplicando correções no ambiente local..."
    
    # Aplicar permissões no ambiente local
    echo "🔐 Aplicando permissões 775 aos diretórios..."
    chmod -R 775 storage bootstrap/cache
    
    # Verificar resultado
    echo "✅ Verificando permissões aplicadas..."
    ls -la storage/logs/
fi

echo "🎉 Correção de permissões concluída!"
echo ""
echo "💡 Dicas:"
echo "   - Execute este script sempre que houver problemas de permissão"
echo "   - Para containers Docker, execute: ./fix-permissions.sh"
echo "   - Para ambiente local, as permissões já foram aplicadas"