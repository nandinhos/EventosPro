# Git Workflow - EventosPro

## 🔑 Configuração SSH Inicial (One-Time Setup)

### 1. Verificar se já tem SSH key

```bash
ls -la ~/.ssh/
```

Procure por arquivos `id_ed25519` ou `id_rsa`.

### 2. Se NÃO tiver chave, criar nova

```bash
ssh-keygen -t ed25519 -C "nandinhos@gmail.com"
# Pressione Enter para aceitar o local padrão
# Digite uma passphrase segura (recomendado) ou deixe em branco
```

### 3. Adicionar chave ao SSH agent

```bash
# Iniciar ssh-agent
eval "$(ssh-agent -s)"

# Adicionar chave
ssh-add ~/.ssh/id_ed25519
```

> **Nota**: Se a chave tiver passphrase, você precisará digitá-la.

### 4. Copiar chave pública para o GitHub

```bash
cat ~/.ssh/id_ed25519.pub
```

Copie o output e adicione em: https://github.com/settings/keys

- Clique em "New SSH key"
- Título: `WSL EventosPro`
- Cole a chave
- Clique em "Add SSH key"

### 5. Testar conexão

```bash
ssh -T git@github.com
```

Resultado esperado:
```
Hi nandinhos! You've successfully authenticated, but GitHub does not provide shell access.
```

---

## 📝 Workflow Diário de Commits

### Padrão: Conventional Commits

Formato:
```
tipo(escopo): descrição curta
```

**Tipos válidos**:
- `feat` - Nova funcionalidade
- `fix` - Correção de bug
- `docs` - Documentação
- `chore` - Tarefas de manutenção
- `refactor` - Refatoração
- `perf` - Performance
- `test` - Testes

**Exemplos reais**:
```bash
git commit -m "feat(reports): add subtotals to due dates report headers"
git commit -m "fix(cost-centers): add AgencyFixedCosts to deletion validation"
git commit -m "docs: update deployment guide"
git commit -m "chore: update dependencies"
```

### Checklist Antes de Commit

```bash
# 1. Verificar mudanças
git status

# 2. Formatar código com Laravel Pint
./vendor/bin/sail bash -c "vendor/bin/pint --dirty"

# 3. Rodar testes (se mudou código)
./vendor/bin/sail artisan test

# 4. Compilar assets (se mudou frontend)
./vendor/bin/sail npm run build

# 5. Adicionar arquivos
git add .

# 6. Commit seguindo Conventional Commits
git commit -m "tipo(escopo): descrição"

# 7. Push
git push origin dev
```

### Regras Importantes

❌ **NUNCA adicionar nos commits**:
```
Co-Authored-By: Claude <noreply@anthropic.com>
🤖 Generated with [Claude Code](https://claude.com/claude-code)
```

✅ **SEMPRE**:
- Usar mensagens limpas e profissionais
- Seguir o padrão Conventional Commits
- Rodar Pint antes de commit
- Testar mudanças importantes

---

## 🔧 Troubleshooting

### SSH não conecta

**Problema**: `Permission denied (publickey)`

**Solução**:
```bash
# 1. Verificar se chave está no agent
ssh-add -l

# 2. Se não estiver, adicionar
eval "$(ssh-agent -s)"
ssh-add ~/.ssh/id_ed25519

# 3. Testar novamente
ssh -T git@github.com
```

### SSH pede passphrase toda hora

**Solução**: Adicionar ao `~/.bashrc` ou `~/.zshrc`:

```bash
# Auto-start ssh-agent
if [ -z "$SSH_AUTH_SOCK" ]; then
   eval "$(ssh-agent -s)" > /dev/null 2>&1
   ssh-add ~/.ssh/id_ed25519 2>/dev/null
fi
```

### Push lento ou travando

**Causa**: Pode estar usando porta 443 ao invés de 22.

**Verificar config**: `cat ~/.ssh/config`

Se tiver configuração para porta 443 e estiver lento, pode comentar e usar porta padrão 22.

### Erro "fatal: refusing to merge unrelated histories"

**Solução**:
```bash
git pull origin dev --rebase
git push origin dev
```

### Mudanças não aparecem após commit

**Verificar**:
```bash
# 1. Limpar caches do Laravel
./vendor/bin/sail artisan optimize:clear

# 2. Verificar se está na branch correta
git branch

# 3. Verificar status do git
git status
```

---

## 🚀 Comandos Rápidos

```bash
# Setup SSH (primeira vez)
ssh-keygen -t ed25519 -C "nandinhos@gmail.com"
eval "$(ssh-agent -s)"
ssh-add ~/.ssh/id_ed25519
cat ~/.ssh/id_ed25519.pub  # Adicionar ao GitHub

# Workflow diário
git status
./vendor/bin/sail bash -c "vendor/bin/pint --dirty"
git add .
git commit -m "tipo(escopo): descrição"
git push origin dev

# Resolver conflitos
git fetch origin dev
git pull origin dev --rebase
# Resolver conflitos manualmente
git add .
git rebase --continue
git push origin dev
```

---

## 📚 Referências

- **Conventional Commits**: https://www.conventionalcommits.org/
- **GitHub SSH**: https://docs.github.com/en/authentication/connecting-to-github-with-ssh
- **Projeto Docs**: `docs/LESSONS_LEARNED.md` (seção 11.1)
- **CLAUDE.md**: Regra #5 sobre commits

---

**Última Atualização**: 2025-11-26
**Autor**: Nando Dev
**Projeto**: EventosPro
