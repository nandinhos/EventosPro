# Troubleshooting: Erro na Restauração de Backup VPS → Local

**Data**: 2025-11-11
**Status**: ✅ RESOLVIDO
**Erro Original**: `ERROR 1049 (42000): Unknown database 'laravel'`

> **📄 SOLUÇÃO COMPLETA**: Veja [RESTORE_BACKUP_SUCCESS.md](./RESTORE_BACKUP_SUCCESS.md) para a documentação da solução final implementada.

---

## 📋 Contexto do Problema

### Ambiente
- **Local**: Laravel Sail (Docker MySQL)
- **VPS**: Laravel Sail (Docker MySQL)
- **Database VPS**: `eventospro`
- **Database Local**: `eventospro` (atualizado de `laravel`)

### O Que Funciona ✅
1. ✅ Criação de backup na VPS (`scripts/backup-database-vps.sh`)
2. ✅ Download do backup da VPS para local
3. ✅ Backup de segurança do banco local
4. ✅ Descompressão do arquivo `.sql.gz`
5. ✅ Conexão SSH sem senha (chave configurada)

### O Que NÃO Funciona ❌
❌ **Restauração do backup no database local**

```bash
ERROR 1049 (42000): Unknown database 'laravel'
```

---

## 🔍 Análise do Erro

### 1. Scripts Já Corrigidos

**Arquivos atualizados para usar `eventospro`:**
- ✅ `scripts/restore-from-vps.sh` (linha 116, 133, 169)
- ✅ `scripts/restore-database.sh` (linha 53)
- ✅ `.env` local → `DB_DATABASE=eventospro`

### 2. Verificações Realizadas

**Conteúdo do backup:**
```bash
gunzip -c backups/eventospro_backup_*.sql.gz | head -30
# Resultado: -- Host: localhost    Database: eventospro ✅
```

**Databases MySQL locais:**
```bash
./vendor/bin/sail exec mysql mysql -uroot -e "SHOW DATABASES;"
# Resultado: eventospro (laravel foi removido) ✅
```

### 3. Possíveis Causas

#### Hipótese 1: Referências a 'laravel' Dentro do Backup SQL
O arquivo `.sql` pode conter:
- `USE laravel;` em algum lugar
- Dados serializados com referências a 'laravel'
- Triggers ou procedures que referenciam 'laravel'

**Como verificar:**
```bash
gunzip -c backups/eventospro_backup_20251111_152401.sql.gz | grep -i "laravel" | head -20
```

#### Hipótese 2: Tabela `cache` com Dados Serializados
Na análise anterior, encontramos na tabela `cache`:
```sql
INSERT INTO `cache` VALUES ('laravel_cache_projections:gig_expenses', ...)
```

Esses dados serializados podem estar causando o erro quando MySQL tenta processar.

#### Hipótese 3: Script Ainda Usando Versão Antiga em Cache
Possível que o bash esteja usando versão cacheada do script.

**Como verificar:**
```bash
which restore-from-vps.sh
hash -r  # Limpar cache do bash
```

---

## 🛠️ Soluções Propostas

### Solução 1: Limpar Dados de Cache do Backup (RECOMENDADA)

Remover entradas da tabela `cache` que referenciam 'laravel':

```bash
# Criar script de limpeza
cat > scripts/clean-backup-sql.sh << 'EOF'
#!/bin/bash
# Remove referências a 'laravel' do backup SQL

BACKUP_FILE="$1"
CLEANED_FILE="${BACKUP_FILE%.sql.gz}_cleaned.sql"

echo "📦 Descomprimindo backup..."
gunzip -c "$BACKUP_FILE" > /tmp/original.sql

echo "🧹 Removendo linhas com 'laravel_cache'..."
grep -v "laravel_cache" /tmp/original.sql > "$CLEANED_FILE"

echo "📦 Comprimindo backup limpo..."
gzip "$CLEANED_FILE"

echo "✅ Backup limpo criado: ${CLEANED_FILE}.gz"
rm /tmp/original.sql
EOF

chmod +x scripts/clean-backup-sql.sh

# Usar:
# ./scripts/clean-backup-sql.sh backups/eventospro_backup_20251111_152401.sql.gz
```

### Solução 2: Restaurar Ignorando Erros de Cache

Modificar `restore-from-vps.sh` para ignorar erros relacionados a cache:

```bash
# Em vez de:
./vendor/bin/sail mysql eventospro < /tmp/restore.sql

# Usar:
./vendor/bin/sail mysql eventospro --force < /tmp/restore.sql 2>&1 | grep -v "Unknown database 'laravel'" || true
```

### Solução 3: Restaurar e Limpar Cache Depois

Adicionar limpeza de cache após restauração:

```bash
# Após restauração bem-sucedida:
./vendor/bin/sail artisan cache:clear
./vendor/bin/sail exec mysql mysql -uroot eventospro -e "DELETE FROM cache WHERE \`key\` LIKE '%laravel%';"
```

### Solução 4: Criar Database 'laravel' Temporário

Se o erro for inevitável devido a dados serializados:

```bash
# Antes da restauração:
./vendor/bin/sail exec mysql mysql -uroot -e "CREATE DATABASE IF NOT EXISTS laravel;"

# Restaurar
./vendor/bin/sail mysql eventospro < /tmp/restore.sql

# Limpar database temporário
./vendor/bin/sail exec mysql mysql -uroot -e "DROP DATABASE laravel;"
```

---

## 🧪 Plano de Testes

### Teste 1: Verificar Conteúdo do Backup
```bash
gunzip -c backups/eventospro_backup_20251111_152401.sql.gz | grep -i "laravel" > /tmp/laravel_refs.txt
wc -l /tmp/laravel_refs.txt
head -50 /tmp/laravel_refs.txt
```

### Teste 2: Tentar Restauração com --force
```bash
gunzip -c backups/eventospro_backup_20251111_152401.sql.gz > /tmp/restore.sql
./vendor/bin/sail mysql eventospro --force < /tmp/restore.sql
```

### Teste 3: Restaurar com Database Laravel Temporário
```bash
./vendor/bin/sail exec mysql mysql -uroot -e "CREATE DATABASE IF NOT EXISTS laravel;"
./vendor/bin/sail mysql eventospro < /tmp/restore.sql
./vendor/bin/sail exec mysql mysql -uroot -e "DROP DATABASE IF EXISTS laravel;"
```

---

## 📊 Informações de Debug

### Backup Atual
- **Arquivo**: `eventospro_backup_20251111_152401.sql.gz`
- **Tamanho**: 116K (comprimido) / 888K (descomprimido)
- **Database origem**: `eventospro` (VPS)
- **Database destino**: `eventospro` (local)

### Linha do Erro
```bash
# Linha 169 de restore-from-vps.sh:
./vendor/bin/sail mysql eventospro < /tmp/restore.sql
# ❌ ERROR 1049 (42000): Unknown database 'laravel'
```

### Configuração Local MySQL
```bash
# Sail docker-compose.yml
mysql:
  image: 'mysql/mysql-server:8.0'
  environment:
    MYSQL_ROOT_PASSWORD: ''
    MYSQL_ROOT_HOST: '%'
    MYSQL_DATABASE: '${DB_DATABASE}'
    MYSQL_USER: '${DB_USERNAME}'
    MYSQL_PASSWORD: '${DB_PASSWORD}'
```

---

## 📝 Próximos Passos

1. [ ] Executar Teste 1: Verificar todas as referências a 'laravel' no backup
2. [ ] Identificar origem exata do erro (qual linha do SQL?)
3. [ ] Implementar Solução 4 (database temporário) como workaround rápido
4. [ ] Implementar Solução 1 (limpeza de cache) como solução permanente
5. [ ] Atualizar script de backup da VPS para não incluir cache serializado
6. [ ] Documentar solução final neste arquivo

---

## 🔗 Referências

- Script principal: `scripts/restore-from-vps.sh`
- Script de backup VPS: `scripts/backup-database-vps.sh`
- Configuração: `.env` → `DB_DATABASE=eventospro`
- Logs: Output do script acima

---

## ✅ Solução Final

**Data da resolução**: 2025-11-11
**Solução implementada**: Criação do database antes da restauração (sem DROP/CREATE)
**Tempo de resolução**: ~2 horas

### O Que Foi Feito

A solução final envolveu **remover a lógica de DROP/CREATE DATABASE** e substituir por:

```bash
# 1. Criar database se não existir
./vendor/bin/sail exec mysql mysql -uroot -e "CREATE DATABASE IF NOT EXISTS eventospro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 2. Restaurar backup diretamente no database
./vendor/bin/sail mysql eventospro < /tmp/restore.sql

# 3. NÃO rodar migrations (dados já vêm completos)
```

**Por que funcionou:**
- O arquivo SQL do `mysqldump` contém `CREATE TABLE` mas NÃO contém `CREATE DATABASE`
- Tentar dropar e recriar o database causava erro porque o SQL tentava usar um database inexistente
- A solução cria um database vazio e restaura os dados dentro dele

### Como Prevenir no Futuro

1. ✅ **Scripts atualizados** com lógica correta em:
   - `scripts/restore-from-vps.sh`
   - `scripts/restore-database.sh`

2. ✅ **Documentação completa** criada:
   - `docs/RESTORE_BACKUP_SUCCESS.md` - Guia de uso
   - `docs/TROUBLESHOOTING_RESTORE_BACKUP.md` - Este arquivo

3. ✅ **Verificação de dados** após restauração mostra contagens reais:
   - 6 Usuários, 671 Gigs, 39 Artistas, 17 Bookers, 1127 Pagamentos

---

**Última atualização**: 2025-11-11 12:35
**Responsável**: EventosPro Dev Team
