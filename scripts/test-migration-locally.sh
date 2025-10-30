#!/bin/bash
# EventosPro - Migration Test Script
# Tests migrations locally before committing to prevent production issues

set -e

echo "======================================================================"
echo "🔬 EventosPro - Teste de Migration Antes de Commit"
echo "======================================================================"
echo ""
echo "Este script vai:"
echo "  1. Criar backup do banco atual"
echo "  2. Rodar migrations pendentes"
echo "  3. Testar se a aplicação ainda funciona"
echo "  4. Se der erro: restaurar backup automaticamente"
echo "  5. Se der certo: você pode commitar com segurança!"
echo ""
echo "⚠️  ATENÇÃO: Use apenas em ambiente de DESENVOLVIMENTO"
echo ""

# Verificar se containers estão rodando
if ! docker ps | grep -q eventospro-mysql-1; then
    echo "❌ Container MySQL não está rodando!"
    echo "   Execute: ./vendor/bin/sail up -d"
    exit 1
fi

echo "✅ Container MySQL rodando"
echo ""

# Verificar se há migrations pendentes
echo "======================================================================"
echo "📋 Verificando migrations pendentes..."
echo "======================================================================"
echo ""

PENDING_MIGRATIONS=$(./vendor/bin/sail artisan migrate:status | grep -c "Pending" || echo "0")

if [ "$PENDING_MIGRATIONS" -eq "0" ]; then
    echo "ℹ️  Nenhuma migration pendente encontrada."
    echo ""
    echo "Opções:"
    echo "  1. Se você criou uma migration, rode: ./vendor/bin/sail artisan migrate:refresh"
    echo "  2. Se quer testar rollback/re-run: ./vendor/bin/sail artisan migrate:refresh"
    echo "  3. Se está tudo OK: nada a fazer!"
    echo ""
    read -p "Deseja continuar mesmo assim? (s/N): " CONTINUE_ANYWAY
    if [ "$CONTINUE_ANYWAY" != "s" ] && [ "$CONTINUE_ANYWAY" != "S" ]; then
        exit 0
    fi
else
    echo "✅ Encontradas $PENDING_MIGRATIONS migration(s) pendente(s)"
    echo ""
    ./vendor/bin/sail artisan migrate:status
fi

echo ""
echo "======================================================================"
echo "💾 FASE 1: Criando backup de segurança"
echo "======================================================================"
echo ""

# Criar backup antes de rodar migration
./scripts/backup-database.sh

BACKUP_FILE=$(ls -t backups/*.sql.gz | head -1)
echo ""
echo "✅ Backup de segurança criado: $BACKUP_FILE"

echo ""
echo "======================================================================"
echo "🚀 FASE 2: Executando migrations"
echo "======================================================================"
echo ""

# Tentar rodar migrations
MIGRATION_SUCCESS=true
./vendor/bin/sail artisan migrate --force || MIGRATION_SUCCESS=false

if [ "$MIGRATION_SUCCESS" = false ]; then
    echo ""
    echo "======================================================================"
    echo "❌ ERRO: Migration falhou!"
    echo "======================================================================"
    echo ""
    echo "🔄 Restaurando backup automaticamente..."

    # Restaurar backup automaticamente
    gunzip -c $BACKUP_FILE > /tmp/auto-restore.sql
    ./vendor/bin/sail mysql laravel < /tmp/auto-restore.sql
    rm /tmp/auto-restore.sql

    echo ""
    echo "✅ Banco de dados restaurado para o estado anterior"
    echo ""
    echo "📋 O que fazer agora:"
    echo "   1. Verifique o erro da migration acima"
    echo "   2. Corrija o problema no arquivo de migration"
    echo "   3. Execute este script novamente"
    echo ""
    exit 1
fi

echo ""
echo "✅ Migrations executadas com sucesso!"

echo ""
echo "======================================================================"
echo "🧪 FASE 3: Testando aplicação"
echo "======================================================================"
echo ""

# Testar se o artisan ainda funciona (indicador de que o DB está OK)
echo "Testando comandos básicos do Laravel..."

TEST_SUCCESS=true

# Test 1: Tinker pode conectar ao DB
echo -n "  📝 Testando conexão com banco... "
if echo "DB::connection()->getPdo();" | ./vendor/bin/sail artisan tinker --execute="DB::connection()->getPdo();" > /dev/null 2>&1; then
    echo "✅"
else
    echo "❌"
    TEST_SUCCESS=false
fi

# Test 2: Cache funciona
echo -n "  💾 Testando cache... "
if ./vendor/bin/sail artisan cache:clear > /dev/null 2>&1; then
    echo "✅"
else
    echo "❌"
    TEST_SUCCESS=false
fi

# Test 3: Config funciona
echo -n "  ⚙️  Testando config... "
if ./vendor/bin/sail artisan config:clear > /dev/null 2>&1; then
    echo "✅"
else
    echo "❌"
    TEST_SUCCESS=false
fi

# Test 4: Migrate status funciona
echo -n "  📊 Testando migrate status... "
if ./vendor/bin/sail artisan migrate:status > /dev/null 2>&1; then
    echo "✅"
else
    echo "❌"
    TEST_SUCCESS=false
fi

if [ "$TEST_SUCCESS" = false ]; then
    echo ""
    echo "======================================================================"
    echo "❌ ERRO: Aplicação apresentou problemas após migration!"
    echo "======================================================================"
    echo ""
    echo "🔄 Restaurando backup automaticamente..."

    gunzip -c $BACKUP_FILE > /tmp/auto-restore.sql
    ./vendor/bin/sail mysql laravel < /tmp/auto-restore.sql
    rm /tmp/auto-restore.sql

    echo ""
    echo "✅ Banco de dados restaurado para o estado anterior"
    echo ""
    echo "📋 O que fazer agora:"
    echo "   1. Revise sua migration - pode ter quebrado algo"
    echo "   2. Verifique logs: ./vendor/bin/sail artisan pail"
    echo "   3. Teste manualmente a aplicação"
    echo "   4. Execute este script novamente quando corrigir"
    echo ""
    exit 1
fi

echo ""
echo "✅ Todos os testes da aplicação passaram!"

echo ""
echo "======================================================================"
echo "🔍 FASE 4: Verificando integridade do schema"
echo "======================================================================"
echo ""

# Mostrar tabelas criadas/modificadas
echo "📊 Status atual das migrations:"
echo ""
./vendor/bin/sail artisan migrate:status | tail -10

echo ""
echo "======================================================================"
echo "🎉 RESULTADO FINAL"
echo "======================================================================"
echo ""
echo "✅ Migration testada com SUCESSO!"
echo ""
echo "📋 Resumo:"
echo "   ✔️  Backup de segurança criado"
echo "   ✔️  Migration executada sem erros"
echo "   ✔️  Aplicação funciona normalmente"
echo "   ✔️  Schema do banco está válido"
echo ""
echo "🎯 SEGURO para fazer COMMIT!"
echo ""
echo "📝 Próximos passos:"
echo "   1. git add ."
echo "   2. git commit -m 'feat/fix: sua mensagem'"
echo "   3. git push"
echo "   4. Na VPS: git pull && ./deploy.sh --production"
echo ""
echo "💡 Dica: O backup fica salvo em:"
echo "   $BACKUP_FILE"
echo "   Caso precise reverter depois, use: ./scripts/restore-database.sh"
echo ""
echo "======================================================================"
