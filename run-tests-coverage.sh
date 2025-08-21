#!/bin/bash

# Script para executar testes com cobertura mínima
# 
# IMPORTANTE: Este script deve ser executado através do Laravel Sail:
# sail bash -c "./run-tests-coverage.sh [cobertura_minima]"
# 
# Uso direto (apenas dentro do container):
# ./run-tests-coverage.sh [cobertura_minima]

set -e

# Verificar se está executando dentro do container Laravel Sail
if [ -z "$LARAVEL_SAIL" ] && [ "$USER" != "sail" ]; then
    echo "⚠️  AVISO: Este script deve ser executado através do Laravel Sail!"
    echo "📋 Uso correto: sail bash -c \"./run-tests-coverage.sh [cobertura_minima]\""
    echo "🐳 Ou execute: sail shell e depois ./run-tests-coverage.sh"
    echo ""
    echo "🔄 Tentando executar automaticamente com Sail..."
    if command -v ./vendor/bin/sail &> /dev/null; then
        exec ./vendor/bin/sail bash -c "./run-tests-coverage.sh $*"
    else
        echo "❌ Laravel Sail não encontrado. Execute manualmente com Sail."
        exit 1
    fi
fi

# Cobertura mínima padrão
MIN_COVERAGE=${1:-80}

echo "🧪 Executando testes com cobertura mínima de ${MIN_COVERAGE}%..."
echo "================================================"

# Limpar cache de testes
echo "🧹 Limpando cache..."
sail artisan config:clear
sail artisan cache:clear
sail artisan view:clear

# Executar testes com cobertura
echo "🚀 Executando testes..."
echo "📊 Cobertura mínima: ${MIN_COVERAGE}%"
echo "🐳 Executando dentro do container Laravel Sail"
sail artisan test --coverage --min=${MIN_COVERAGE} --coverage-html=coverage-report

if [ $? -eq 0 ]; then
    echo "✅ Todos os testes passaram e cobertura mínima atingida!"
    echo "📊 Relatório de cobertura gerado em: coverage-report/index.html"
    echo "💡 Para visualizar: open coverage-report/index.html"
else
    echo "❌ Testes falharam ou cobertura insuficiente!"
    exit 1
fi

echo "================================================"
echo "🎉 Pipeline de testes concluído com sucesso!"