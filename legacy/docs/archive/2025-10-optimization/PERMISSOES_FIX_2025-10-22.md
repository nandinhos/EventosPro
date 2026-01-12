# Fix de Permissões - EventosPro
**Data**: 2025-10-22
**Problema**: `Permission denied` ao tentar escrever em `/var/www/html/storage/framework/views/`

---

## 🔍 Causa Raiz

Arquivos no `storage/` e `bootstrap/cache/` estavam com owner `1337:root`, mas o container Laravel roda com usuário `1001:1001` (devuser).

**Verificação**:
```bash
ls -la storage/framework/views/ | head -5
# Output: -rw-r--r-- 1 1337 root ...
```

---

## ✅ Solução Implementada

### 1. Adicionar Variáveis de Ambiente

Adicionado ao `.env`:
```env
# Sail user/group configuration
WWWUSER=1001
WWWGROUP=1001
```

**Motivo**: Define que os containers devem rodar com UID/GID do usuário `devuser` (1001).

---

### 2. Corrigir Permissões Dentro do Container

Como `sudo` não está disponível no ambiente, corrigimos as permissões **de dentro do container Laravel**:

```bash
# Parar containers
./vendor/bin/sail down

# Reiniciar com nova configuração WWWUSER/WWWGROUP
./vendor/bin/sail up -d

# Corrigir ownership dentro do container
./vendor/bin/sail exec laravel.test chown -R sail:sail /var/www/html/storage /var/www/html/bootstrap/cache

# Corrigir permissões dentro do container
./vendor/bin/sail exec laravel.test chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Limpar caches Laravel
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan cache:clear
./vendor/bin/sail artisan view:clear
```

---

## 🎯 Validação

### Verificar que cache está funcionando:
```bash
./vendor/bin/sail artisan tinker --execute="Cache::put('test', 'works'); echo Cache::get('test');"
# Output esperado: works
```

### Verificar containers saudáveis:
```bash
./scripts/check-health.sh
# Output esperado: ✓ Todos os containers do EventosPro estão saudáveis!
```

### Verificar uso de recursos:
```bash
docker stats --no-stream eventospro-laravel.test-1 eventospro-mysql-1 eventospro-redis-1
```

**Valores obtidos** (VPS 4GB, 1 CPU):
```
NAME                        CPU %     MEM USAGE / LIMIT     MEM %
eventospro-laravel.test-1   0.05%     162MiB / 512MiB       31.65%
eventospro-mysql-1          0.53%     383MiB / 768MiB       49.89%
eventospro-redis-1          0.34%     5.6MiB / 128MiB       4.40%
```

**Total EventosPro**: ~550MB RAM (~14% do VPS de 4GB) ✅

---

## 📝 Lições Aprendidas

### ✅ O QUE FAZER

1. **Sempre definir WWWUSER e WWWGROUP no .env**:
   ```env
   WWWUSER=1001
   WWWGROUP=1001
   ```

2. **Corrigir permissões de dentro do container**:
   ```bash
   ./vendor/bin/sail exec laravel.test chown -R sail:sail /var/www/html/storage
   ./vendor/bin/sail exec laravel.test chmod -R 775 /var/www/html/storage
   ```

3. **Validar cache Redis após mudanças**:
   ```bash
   ./vendor/bin/sail artisan tinker --execute="Cache::put('test','ok'); echo Cache::get('test');"
   ```

---

### ❌ O QUE NÃO FAZER

1. **Não usar sudo no host quando não disponível**:
   ```bash
   # ❌ EVITAR (requer senha sudo interativa)
   sudo chown -R 1001:1001 storage/
   ```

2. **Não deixar storage/ com owner errado**:
   - Sempre verificar ownership após mudanças no ambiente

3. **Não ignorar warnings de permissão**:
   - `Permission denied` indica problema sério que trava a aplicação

---

## 🔧 Script de Fix Rápido

Salvo em `scripts/fix-permissions.sh`:

```bash
#!/bin/bash
# Fix permissions inside Laravel container

echo "Fixing storage permissions..."
./vendor/bin/sail exec laravel.test chown -R sail:sail /var/www/html/storage /var/www/html/bootstrap/cache
./vendor/bin/sail exec laravel.test chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

echo "Clearing caches..."
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan cache:clear
./vendor/bin/sail artisan view:clear

echo "✓ Permissions fixed!"
```

**Uso**:
```bash
chmod +x scripts/fix-permissions.sh
./scripts/fix-permissions.sh
```

---

## 🎯 Status Final

- ✅ Permissões corrigidas
- ✅ Cache Redis funcionando
- ✅ Containers saudáveis em < 1 minuto
- ✅ Uso de RAM: 550MB (14% do VPS)
- ✅ Aplicação acessível em http://localhost:8081

---

**Autor**: Claude Code
**Status**: ✅ Resolvido
