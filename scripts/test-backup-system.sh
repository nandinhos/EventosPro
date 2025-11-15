#!/bin/bash
# EventosPro - Backup System Test Script
# Tests backup creation, restoration, and data integrity

set -e

echo "======================================================================"
echo "🧪 EventosPro - Sistema de Teste de Backup"
echo "======================================================================"
echo ""
echo "Este script vai testar:"
echo "  1. Criação de backup"
echo "  2. Integridade do arquivo comprimido"
echo "  3. Restauração do backup"
echo "  4. Validação de integridade dos dados"
echo "  5. Deploy --production com backup automático"
echo ""
echo "⚠️  ATENÇÃO: Este teste vai restaurar o banco de dados!"
echo "   Certifique-se de estar em ambiente de desenvolvimento."
echo ""
read -p "Continuar com os testes? (s/N): " CONTINUE

if [ "$CONTINUE" != "s" ] && [ "$CONTINUE" != "S" ]; then
    echo "❌ Testes cancelados"
    exit 0
fi

echo ""
echo "======================================================================"
echo "📊 FASE 1: Coletando estado atual do banco de dados"
echo "======================================================================"

# Verificar se containers estão rodando
if ! docker ps | grep -q eventospro-mysql-1; then
    echo "❌ Container MySQL não está rodando!"
    echo "   Execute: ../vendor/bin/sail up -d"
    exit 1
fi

echo "✅ Container MySQL rodando"

# Contar registros antes do backup
echo ""
echo "📋 Contando registros nas principais tabelas..."

USERS_COUNT=$(../vendor/bin/sail mysql laravel -sN -e "SELECT COUNT(*) FROM users;" 2>/dev/null || echo "0")
GIGS_COUNT=$(../vendor/bin/sail mysql laravel -sN -e "SELECT COUNT(*) FROM gigs;" 2>/dev/null || echo "0")
PAYMENTS_COUNT=$(../vendor/bin/sail mysql laravel -sN -e "SELECT COUNT(*) FROM payments;" 2>/dev/null || echo "0")

echo "   Users: $USERS_COUNT"
echo "   Gigs: $GIGS_COUNT"
echo "   Payments: $PAYMENTS_COUNT"

if [ "$USERS_COUNT" -eq "0" ]; then
    echo ""
    echo "⚠️  Banco de dados vazio! Recomenda-se rodar seeders primeiro."
    echo "   Execute: ../vendor/bin/sail artisan migrate:fresh --seed"
    read -p "Continuar mesmo assim? (s/N): " CONTINUE_EMPTY
    if [ "$CONTINUE_EMPTY" != "s" ] && [ "$CONTINUE_EMPTY" != "S" ]; then
        exit 0
    fi
fi

echo ""
echo "======================================================================"
echo "💾 FASE 2: Testando criação de backup"
echo "======================================================================"

# Criar backup
echo ""
echo "Executando: ./scripts/backup-database.sh"
./scripts/backup-database.sh

# Verificar se backup foi criado
LATEST_BACKUP=$(ls -t backups/*.sql.gz 2>/dev/null | head -1)

if [ -z "$LATEST_BACKUP" ]; then
    echo "❌ FALHA: Nenhum backup encontrado!"
    exit 1
fi

echo ""
echo "✅ Backup criado: $LATEST_BACKUP"
echo "   Tamanho: $(du -h $LATEST_BACKUP | cut -f1)"

# Testar integridade do arquivo gzip
echo ""
echo "🔍 Testando integridade do arquivo comprimido..."
if gunzip -t $LATEST_BACKUP 2>/dev/null; then
    echo "✅ Arquivo comprimido está íntegro"
else
    echo "❌ FALHA: Arquivo comprimido corrompido!"
    exit 1
fi

# Verificar se o SQL contém dados esperados
echo ""
echo "🔍 Validando conteúdo do backup..."
BACKUP_CONTENT=$(gunzip -c $LATEST_BACKUP | head -20)

if echo "$BACKUP_CONTENT" | grep -q "MySQL dump"; then
    echo "✅ Backup contém dump válido do MySQL"
else
    echo "❌ FALHA: Backup não parece ser um dump válido do MySQL!"
    exit 1
fi

if echo "$BACKUP_CONTENT" | grep -q "Database: laravel"; then
    echo "✅ Backup é do database correto (laravel)"
else
    echo "⚠️  WARNING: Database no backup pode estar incorreto"
fi

echo ""
echo "======================================================================"
echo "♻️  FASE 3: Testando restauração do backup"
echo "======================================================================"

echo ""
echo "Descomprimindo backup..."
gunzip -c $LATEST_BACKUP > /tmp/test-restore.sql

echo "Restaurando banco de dados..."
../vendor/bin/sail mysql laravel < /tmp/test-restore.sql

echo "Limpando arquivo temporário..."
rm /tmp/test-restore.sql

echo "✅ Restauração concluída"

echo ""
echo "======================================================================"
echo "✔️  FASE 4: Validando integridade dos dados após restore"
echo "======================================================================"

echo ""
echo "📋 Contando registros após restauração..."

USERS_COUNT_AFTER=$(../vendor/bin/sail mysql laravel -sN -e "SELECT COUNT(*) FROM users;" 2>/dev/null || echo "0")
GIGS_COUNT_AFTER=$(../vendor/bin/sail mysql laravel -sN -e "SELECT COUNT(*) FROM gigs;" 2>/dev/null || echo "0")
PAYMENTS_COUNT_AFTER=$(../vendor/bin/sail mysql laravel -sN -e "SELECT COUNT(*) FROM payments;" 2>/dev/null || echo "0")

echo "   Users: $USERS_COUNT_AFTER (antes: $USERS_COUNT)"
echo "   Gigs: $GIGS_COUNT_AFTER (antes: $GIGS_COUNT)"
echo "   Payments: $PAYMENTS_COUNT_AFTER (antes: $PAYMENTS_COUNT)"

# Validar se os dados são idênticos
VALIDATION_PASSED=true

if [ "$USERS_COUNT" != "$USERS_COUNT_AFTER" ]; then
    echo "❌ FALHA: Contagem de users não bate!"
    VALIDATION_PASSED=false
fi

if [ "$GIGS_COUNT" != "$GIGS_COUNT_AFTER" ]; then
    echo "❌ FALHA: Contagem de gigs não bate!"
    VALIDATION_PASSED=false
fi

if [ "$PAYMENTS_COUNT" != "$PAYMENTS_COUNT_AFTER" ]; then
    echo "❌ FALHA: Contagem de payments não bate!"
    VALIDATION_PASSED=false
fi

if [ "$VALIDATION_PASSED" = true ]; then
    echo ""
    echo "✅ VALIDAÇÃO PASSOU: Todos os dados foram restaurados corretamente!"
else
    echo ""
    echo "❌ VALIDAÇÃO FALHOU: Dados não correspondem!"
    exit 1
fi

echo ""
echo "======================================================================"
echo "🚀 FASE 5: Testando deploy --production (simulação)"
echo "======================================================================"

echo ""
echo "ℹ️  Esta fase simula o deploy de produção com backup automático"
echo "   Não vai executar o deploy completo, apenas testar o fluxo de backup"
echo ""

# Contar backups antes
BACKUPS_BEFORE=$(ls backups/*.sql.gz 2>/dev/null | wc -l)
echo "📊 Backups existentes antes: $BACKUPS_BEFORE"

# Simular a parte de backup do deploy (sem executar o deploy completo)
echo ""
echo "Criando backup como se fosse um deploy --production..."
./scripts/backup-database.sh

BACKUPS_AFTER=$(ls backups/*.sql.gz 2>/dev/null | wc -l)
echo ""
echo "📊 Backups existentes depois: $BACKUPS_AFTER"

if [ "$BACKUPS_AFTER" -gt "$BACKUPS_BEFORE" ]; then
    echo "✅ Backup automático funcionaria no deploy --production"
else
    echo "❌ FALHA: Backup não foi criado!"
    exit 1
fi

echo ""
echo "======================================================================"
echo "🎉 RESULTADO FINAL"
echo "======================================================================"
echo ""
echo "✅ Todos os testes passaram com sucesso!"
echo ""
echo "📋 Resumo:"
echo "   ✔️  Backup cria arquivos válidos"
echo "   ✔️  Arquivos comprimidos estão íntegros"
echo "   ✔️  Restore funciona corretamente"
echo "   ✔️  Integridade dos dados é mantida"
echo "   ✔️  Deploy --production criaria backup automático"
echo ""
echo "🎯 Sistema de backup está PRONTO PARA USO!"
echo ""
echo "📝 Próximos passos:"
echo "   1. Use './scripts/backup-database.sh' para criar backups manuais"
echo "   2. Use './scripts/restore-database.sh' para restaurar backups"
echo "   3. Use './deploy.sh --production' no VPS para deploy seguro"
echo ""
echo "======================================================================"
