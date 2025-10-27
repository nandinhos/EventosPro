#!/bin/bash

###################################################################################
# Script: remove-claude-coauthor.sh
# Descrição: Remove menções e co-autoria do Claude Code do histórico de commits
# Autor: Nando Dev
# Data: 2025-10-26
###################################################################################

set -e

echo "🔧 EventosPro - Limpeza de Histórico Git"
echo "=========================================="
echo ""
echo "Este script irá:"
echo "1. Remover todas as linhas contendo 'Claude Code' das mensagens de commit"
echo "2. Remover todas as linhas 'Co-Authored-By: Claude'"
echo "3. Reescrever o histórico completo do repositório"
echo ""
echo "⚠️  ATENÇÃO: Esta operação reescreve o histórico do Git!"
echo "   - Todos os hashes de commit serão alterados"
echo "   - Se o repositório for compartilhado, pode causar problemas"
echo ""

# Verificar se está no diretório correto
if [ ! -f "composer.json" ] || [ ! -d ".git" ]; then
    echo "❌ Erro: Execute este script no diretório raiz do EventosPro"
    exit 1
fi

# Criar backup antes de iniciar
echo "📦 Criando backup..."
BACKUP_BRANCH="backup-before-history-rewrite-$(date +%Y%m%d-%H%M%S)"
git branch "$BACKUP_BRANCH"
echo "✅ Backup criado: $BACKUP_BRANCH"
echo ""

# Contar commits com menção ao Claude Code
COMMITS_WITH_CLAUDE=$(git log --all --grep="Claude Code" --oneline | wc -l)
echo "📊 Encontrados $COMMITS_WITH_CLAUDE commits com menção ao Claude Code"
echo ""

if [ "$COMMITS_WITH_CLAUDE" -eq 0 ]; then
    echo "✅ Nenhum commit encontrado com menção ao Claude Code!"
    echo "   Nada a fazer."
    exit 0
fi

echo "🚀 Iniciando reescrita do histórico..."
echo ""

# Usar filter-branch para reescrever mensagens de commit
git filter-branch --msg-filter '
    sed -e "/🤖 Generated with \\[Claude Code\\]/d" \
        -e "/Co-Authored-By: Claude/d" \
        -e "/^$/N;/^\n$/D"
' --tag-name-filter cat -- --all

echo ""
echo "✅ Histórico reescrito com sucesso!"
echo ""

# Limpar referências antigas
echo "🧹 Limpando referências antigas..."
rm -rf .git/refs/original/
git reflog expire --expire=now --all
git gc --prune=now --aggressive

echo ""
echo "✅ Limpeza concluída!"
echo ""

# Verificar resultado
REMAINING_COMMITS=$(git log --all --grep="Claude Code" --oneline | wc -l)
echo "📊 Verificação final:"
echo "   - Commits antes: $COMMITS_WITH_CLAUDE"
echo "   - Commits depois: $REMAINING_COMMITS"
echo ""

if [ "$REMAINING_COMMITS" -eq 0 ]; then
    echo "✅ Sucesso! Todas as menções ao Claude Code foram removidas!"
    echo ""
    echo "📋 Próximos passos:"
    echo "   1. Verifique o histórico: git log --all --oneline | head -20"
    echo "   2. Se tudo estiver OK, delete o backup: git branch -D $BACKUP_BRANCH"
    echo "   3. Se precisar reverter: git reset --hard $BACKUP_BRANCH"
    echo ""
    echo "⚠️  Se este repositório for compartilhado:"
    echo "   - Force push será necessário: git push --force --all"
    echo "   - Notifique colaboradores sobre a reescrita do histórico"
else
    echo "⚠️  Aviso: Ainda há $REMAINING_COMMITS commits com menção ao Claude Code"
    echo "   Isso pode indicar que o filtro precisa ser ajustado."
fi

echo ""
echo "🎉 Processo concluído!"
