#!/bin/bash

# ⚠️ AVISO: Este script é DESTRUTIVO e deve ser usado APENAS em DESENVOLVIMENTO!
# ❌ NUNCA execute em produção - causará downtime e possível perda de dados!

# Verificação de segurança - prevenir execução em produção
if [ -f ".env" ]; then
    if grep -q "APP_ENV=production" .env; then
        echo "❌ ERRO CRÍTICO: Detectado ambiente de PRODUÇÃO!"
        echo "❌ Este script NÃO PODE ser executado em produção."
        echo "❌ Operação cancelada por segurança."
        exit 1
    fi
fi

# Confirmação dupla
echo "⚠️  AVISO: Este script vai PARAR e REMOVER todos os containers do EventosPro!"
echo "⚠️  Isso pode causar perda de dados em transações não finalizadas."
echo ""
read -p "Você tem certeza que deseja continuar? (digite 'SIM' em maiúsculas): " confirmacao

if [ "$confirmacao" != "SIM" ]; then
    echo "❌ Operação cancelada pelo usuário."
    exit 0
fi

echo ""
echo "🔄 Resetando TODOS os containers..."
echo ""

# Para todos os containers do projeto
echo "Parando todos os containers..."
sudo docker compose down --remove-orphans 2>/dev/null || true

# Mata processos travados se existirem
echo "Matando processos travados..."
sudo pkill -9 -f "eventospro" 2>/dev/null || true

# Remove containers forçadamente
echo "Removendo containers forçadamente..."
sudo docker rm -f $(sudo docker ps -a | grep eventospro | awk '{print $1}') 2>/dev/null || true

# Limpa rede e volumes órfãos
echo "Limpando recursos órfãos..."
docker network prune -f 2>/dev/null || true
docker container prune -f 2>/dev/null || true

# Aguarda um pouco
sleep 2

# Sobe TUDO de novo SEM SUDO
echo ""
echo "🚀 Subindo containers (SEM sudo)..."
docker compose up -d

echo ""
echo "✅ Pronto! Aguarde 30-40 segundos e verifique: docker ps"
echo ""
echo "A aplicação estará em: http://localhost:9000"
echo "PHPMyAdmin estará em: http://localhost:8089"
echo ""
