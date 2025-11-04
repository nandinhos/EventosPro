# Lições Aprendidas - EventosPro

## Objetivo
Este documento registra as melhores práticas e acertos efetivados durante o desenvolvimento do EventosPro para **evitar consumo excessivo de tokens** e garantir eficiência no trabalho com assistentes de IA.

---

## 1. Otimização de Consultas ao Contexto

### ✅ Acertos Implementados

#### 1.1. Uso Estratégico de `Read` com Offset e Limit
```bash
# ❌ EVITAR: Ler arquivo inteiro desnecessariamente
Read(file_path="/path/to/large/file.php")

# ✅ CORRETO: Ler apenas seções relevantes
Read(file_path="/path/to/large/file.php", offset=100, limit=50)
```

**Economia**: Até 80% na leitura de arquivos grandes (>500 linhas)

#### 1.2. Uso de Glob para Buscar Arquivos Primeiro
```bash
# ✅ Sequência otimizada
1. Glob(pattern="**/*Projection*.php") → identifica arquivos relevantes
2. Read(file_path="<arquivo_específico>") → lê apenas o necessário
```

**Benefício**: Evita leituras desnecessárias de múltiplos arquivos

---

## 2. Gerenciamento Eficiente de Edições

### ✅ Acertos Implementados

#### 2.1. Edições Cirúrgicas com `Edit`
```php
// ✅ CORRETO: Substituição precisa
Edit(
    file_path="...",
    old_string="código exato a substituir com contexto suficiente",
    new_string="novo código"
)
```

**vs**

```php
// ❌ EVITAR: Reescrever arquivo inteiro
Write(file_path="...", content="arquivo completo de 500 linhas")
```

**Economia**: 90% de tokens em edições de arquivos grandes

#### 2.2. Edições Múltiplas em Batch
- Agrupar múltiplas edições relacionadas no mesmo arquivo
- Usar `old_string` maior para fazer múltiplas substituições de uma vez
- Evitar chamadas Edit() sequenciais quando possível

---

## 3. Paralelização de Operações

### ✅ Acertos Implementados

#### 3.1. Tool Calls Paralelos
```xml
<!-- ✅ CORRETO: Executar tools independentes em paralelo -->
<function_calls>
  <invoke name="Edit">
    <parameter name="file_path">file1.php</parameter>
    ...
  </invoke>
  <invoke name="Edit">
    <parameter name="file_path">file2.php</parameter>
    ...
  </invoke>
</function_calls>
```

**Benefício**: Reduz latência total da operação

#### 3.2. Quando NÃO Paralelizar
```bash
# ❌ EVITAR: Paralelizar operações dependentes
Edit(file1.php) + Read(file1.php)  # Read precisa do Edit completo

# ✅ CORRETO: Sequenciar operações dependentes
1. Edit(file1.php)
2. Aguardar completar
3. Read(file1.php)
```

---

## 4. Otimização de Queries no Laravel

### ✅ Acertos Implementados

#### 4.1. Eager Loading Obrigatório
```php
// ❌ EVITAR: N+1 queries
$gigs = Gig::where('status', 'pendente')->get();
foreach ($gigs as $gig) {
    echo $gig->artist->name;  // N+1 problem!
}

// ✅ CORRETO: Eager load relacionamentos
$gigs = Gig::where('status', 'pendente')
    ->with(['artist', 'booker', 'gigCosts'])
    ->get();
```

**Resultado**: Redução de 100+ queries para 2-3 queries

#### 4.2. Select Específico quando Necessário
```php
// ✅ Quando só precisa de campos específicos
$gigs = Gig::select(['id', 'gig_date', 'artist_id'])
    ->with('artist:id,name')
    ->get();
```

**Economia**: 40-60% de dados trafegados

---

## 5. Reutilização de Código Existente

### ✅ Acertos Implementados

#### 5.1. Seguir Padrões Visuais Existentes
```bash
# ✅ CORRETO: Analisar página similar primeiro
1. Read(existing_page.blade.php) → entender padrão
2. Aplicar mesmo padrão na nova página
```

**Exemplo Prático**:
- Analisamos `/finance/monthly-closing/index.blade.php`
- Replicamos estrutura de cards, tabelas e filtros
- Resultado: Consistência visual perfeita sem tentativa e erro

#### 5.2. Reutilizar Classes Tailwind
```bash
# ✅ Manter classes consistentes entre páginas
- Cards: "bg-gradient-to-br from-X-500 to-X-600 rounded-lg shadow-lg"
- Tabelas: "px-6 py-4 whitespace-nowrap text-sm"
- Headers: "px-6 py-4 border-b bg-gray-50 dark:bg-gray-700/50"
```

---

## 6. Estratégias de Depuração

### ✅ Acertos Implementados

#### 6.1. Usar Laravel Debugbar
```php
// Verificar queries executadas
// Identificar N+1 problems visualmente na debugbar
```

#### 6.2. Logs Estratégicos
```php
// ✅ Adicionar logs em Service layers críticos
//Log::info("[Service] Total calculado: {$total}");
//Log::debug("[Service] Processing item {$item->id}");
```

---

## 7. Compilação de Assets

### ✅ Workflow Otimizado

```bash
# 1. Desenvolvimento
./vendor/bin/sail npm run dev

# 2. Antes de commit
./vendor/bin/sail npm run build

# 3. Verificar build
# Assets devem estar em public/build/assets/
```

**Lição**: Sempre compilar assets antes de testar mudanças visuais

---

## 8. Tratamento de Null Safety

### ✅ Acertos Implementados

#### 8.1. Null Coalescing Operator
```php
// ✅ CORRETO: Garantir valor default
$period = $request->input('period') ?? '30_days';

// ❌ EVITAR: Deixar nullable sem default
$period = $request->input('period', '30_days'); // Pode retornar null!
```

#### 8.2. Type Hints Estritos
```php
// ✅ CORRETO: Declarar tipos explícitos
public function setPeriod(string $period, ?string $startDate = null): void

// ❌ EVITAR: Tipos vagos
public function setPeriod($period, $startDate = null)
```

---

## 9. Gestão de TODOs

### ✅ Acertos Implementados

#### 9.1. TodoWrite para Tracking
```bash
# ✅ Usar TodoWrite para tarefas complexas (3+ passos)
TodoWrite([
    {content: "Optimize queries", status: "in_progress"},
    {content: "Compile assets", status: "pending"},
    {content: "Document lessons", status: "pending"}
])
```

#### 9.2. Atualizar Status Frequentemente
- Marcar `in_progress` ao iniciar
- Marcar `completed` IMEDIATAMENTE ao terminar
- Remove completed tasks quando finalizar tudo

---

## 10. Resumo de Economia de Tokens

### Métricas Aproximadas

| Prática | Economia Estimada |
|---------|-------------------|
| Read com offset/limit | 70-80% |
| Edit vs Write | 85-90% |
| Eager Loading | 60-70% |
| Glob antes de Read | 50-60% |
| Paralelização | 30-40% (latência) |
| Reusar padrões existentes | 40-50% |

### Total Estimado
**Economia geral de 60-70% no consumo de tokens** aplicando todas as práticas

---

## 11. Checklist Antes de Commit

- [ ] Código formatado com Laravel Pint
- [ ] Eager loading em todas as queries
- [ ] Assets compilados (`npm run build`)
- [ ] Null safety verificado
- [ ] Logs estratégicos adicionados
- [ ] Testes atualizados (se necessário)
- [ ] Documentação atualizada

---

## 12. Infraestrutura e Deploy - Alta Disponibilidade em VPS

### ✅ O QUE FAZER

#### 12.1. Restart Policy em Produção
```yaml
# ✅ CORRETO: Todos os containers com restart policy
services:
  laravel.test:
    restart: unless-stopped  # Reinicia automaticamente após boot do VPS
  mysql:
    restart: unless-stopped
  redis:
    restart: unless-stopped
```

**Benefício**: Aplicação fica online automaticamente após reboot do servidor

#### 12.2. Redis para Sessions e Cache
```env
# ✅ CORRETO: Redis para dados temporários
CACHE_STORE=redis
SESSION_DRIVER=redis
REDIS_HOST=redis  # Hostname do container no Docker

# ❌ EVITAR: Database para sessions/cache (causa I/O excessivo)
# CACHE_STORE=database
# SESSION_DRIVER=database
```

**Resultado Prático**:
- Reduz carga do MySQL em 60-70%
- Sessions instantâneas (RAM vs Disco)
- Tabela `sessions` vazia (vs 229+ registros acumulados)

#### 12.3. Healthcheck Adequado para Boot
```yaml
# ✅ CORRETO: Healthcheck com start_period
laravel.test:
  healthcheck:
    test: ["CMD", "curl", "-f", "http://localhost"]
    interval: 10s
    timeout: 10s
    retries: 5
    start_period: 60s  # Dá tempo para MySQL/Redis subirem

# ❌ EVITAR: Timeout curto sem start_period
# timeout: 5s
# retries: 3
# (sem start_period)
```

**Problema evitado**: Container marcado como "unhealthy" antes de estar pronto

#### 12.4. Ordem de Inicialização com depends_on
```yaml
# ✅ CORRETO: Aguardar dependências ficarem saudáveis
laravel.test:
  depends_on:
    mysql:
      condition: service_healthy
    redis:
      condition: service_healthy

# ❌ EVITAR: depends_on simples (não aguarda health)
# depends_on:
#   - mysql
#   - redis
```

#### 12.5. Limites de Recursos para Prevenir Sobrecarga
```yaml
# ✅ CORRETO: Definir limites e reservas
laravel.test:
  deploy:
    resources:
      limits:
        memory: 512M
        cpus: '1.0'
      reservations:
        memory: 256M

mysql:
  deploy:
    resources:
      limits:
        memory: 768M
        cpus: '1.5'
      reservations:
        memory: 512M
```

**Benefício**: Um container não consome todos os recursos em picos

#### 12.6. OPcache Otimizado (sem JIT quando incompatível)
```ini
# ✅ CORRETO: Desabilitar JIT se incompatível
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
# JIT comentado se logs mostram incompatibilidade

# ❌ EVITAR: JIT habilitado causando warnings
# opcache.jit_buffer_size=100M
# opcache.jit=1255
# (quando logs mostram: "JIT is incompatible with third party extensions")
```

---

### ❌ O QUE NÃO FAZER

#### 12.1. Parar Outros Containers para Liberar Recursos
```bash
# ❌ NUNCA: Parar aplicações de outros projetos
# docker stop other-project-mysql  # Impacta outras aplicações!

# ✅ CORRETO: Otimizar o próprio projeto
# - Usar Redis para sessions/cache
# - Adicionar limites de recursos
# - Otimizar queries e eager loading
```

**Razão**: Em VPS compartilhado, cada aplicação deve ser otimizada individualmente

#### 12.2. Sessions/Cache em Database em Produção
```env
# ❌ EVITAR: I/O excessivo e tabelas crescentes
SESSION_DRIVER=database  # Causa locking e slow queries
CACHE_STORE=database     # I/O desnecessário no MySQL
```

**Problema**:
- Cada requisição HTTP faz SELECT/UPDATE na tabela `sessions`
- Cache em database gera contenção (locks)
- Tabelas crescem indefinidamente (229+ sessions acumuladas)

#### 12.3. Healthcheck com Timeout Muito Curto
```yaml
# ❌ EVITAR: Container marcado unhealthy prematuramente
healthcheck:
  timeout: 5s
  retries: 3
  # (sem start_period)
```

**Problema**: MySQL lento no boot → Laravel marcado unhealthy → restart loop

#### 12.4. Containers sem Restart Policy em Produção
```yaml
# ❌ EVITAR: Container não reinicia automaticamente
services:
  laravel.test:
    # (sem restart policy)
```

**Problema**: Após reboot do VPS, aplicação fica offline até intervenção manual

---

### 📊 Métricas de Sucesso - VPS 4GB RAM

| Métrica | Antes | Depois | Melhoria |
|---------|-------|--------|----------|
| Uso de RAM | 86% (3.3GB) | ~60% (2.3GB) | -1GB |
| Queries MySQL | 100+ por request | 30-40 por request | -60% |
| Tempo de boot | Unhealthy/Timeout | < 2 minutos healthy | ✅ |
| Sessions em DB | 229 registros | 0 registros | -100% |
| Auto-recovery | Manual | Automático | ✅ |

---

### 🔧 Script de Verificação Pós-Boot

Criado `scripts/check-health.sh` para monitorar inicialização:

```bash
#!/bin/bash
# Aguarda até containers ficarem healthy (timeout 2min)
./scripts/check-health.sh
```

**Uso**:
- Validar que aplicação subiu corretamente após reboot
- Debugging de problemas de inicialização
- CI/CD para validar deploy

---

## 13. Deploy Inicial em VPS - Checklist Completo

### ✅ Passo a Passo para Deploy em Nova VPS

#### 13.1. Limpeza de Docker (se necessário)
```bash
# Verificar containers rodando
docker ps

# Parar todos os containers do projeto anterior
docker-compose down

# Limpar sistema Docker (apenas se necessário)
docker system prune -f
```

**Economia de espaço**: Até 3.5GB liberados removendo cache antigo

#### 13.2. Resolução de Conflitos de Porta

**Problema Comum**: Portas já em uso por outros serviços ou Docker Desktop/WSL

```bash
# Verificar portas em uso
ss -tlnp | grep :PORTA
netstat -tlnp | grep :PORTA

# Identificar processo usando porta
sudo lsof -i :PORTA
```

**Solução no .env**:
```env
# Use portas altas (>3000) para evitar conflitos
FORWARD_DB_PORT=3308        # MySQL (padrão 3306 pode conflitar)
FORWARD_REDIS_PORT=6380     # Redis (padrão 6379 pode conflitar)
FORWARD_PHPMYADMIN_PORT=9090 # phpMyAdmin (portas 8080-8090 comuns em WSL)
```

**Dica**: Se trabalha com Docker Desktop no WSL, sempre use portas > 9000 para serviços auxiliares

#### 13.3. Inicialização dos Containers

```bash
# 1. Subir containers
./vendor/bin/sail up -d

# 2. Verificar status
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"

# 3. Verificar logs se houver problema
./vendor/bin/sail logs --tail=50
```

**Esperar por**: Todos os containers mostrarem status "healthy"

#### 13.4. Configuração Inicial da Aplicação

```bash
# 1. Gerar chave da aplicação (se APP_KEY vazia)
./vendor/bin/sail artisan key:generate

# 2. Executar migrações
./vendor/bin/sail artisan migrate

# 3. Executar seeders (primeira vez ou reset)
./vendor/bin/sail artisan db:seed --class=RolesAndPermissionsSeeder

# 4. Limpar e cachear configurações
./vendor/bin/sail artisan config:cache
./vendor/bin/sail artisan route:cache
./vendor/bin/sail artisan view:cache
```

#### 13.5. Build de Assets Frontend

```bash
# 1. Instalar dependências NPM
./vendor/bin/sail npm install

# 2. Compilar assets para produção
./vendor/bin/sail npm run build

# 3. Verificar que manifest foi gerado
ls -lh public/build/manifest.json
```

**Erro Comum**: "ViteManifestNotFoundException" - significa que esqueceu de rodar `npm run build`

#### 13.6. Sincronização Container vs Host

**Como Verificar**:
```bash
# 1. Verificar volumes montados
docker inspect eventospro-laravel.test-1 --format='{{range .Mounts}}{{.Source}} -> {{.Destination}}{{println}}{{end}}'

# 2. Comparar arquivo host vs container
ls -la app/Http/Controllers/SeuController.php
./vendor/bin/sail bash -c "ls -la /var/www/html/app/Http/Controllers/SeuController.php"

# 3. Verificar rotas registradas
./vendor/bin/sail artisan route:list --path=sua-rota

# 4. Limpar cache quando alterar código
./vendor/bin/sail artisan optimize:clear
```

#### 13.7. Atualizar Configurações do .env

```bash
# Após alterar .env, aplicar mudanças SEM reiniciar containers
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan config:cache

# Verificar que configuração foi aplicada
./vendor/bin/sail artisan tinker --execute="echo config('app.locale');"
```

**Dica**: Não precisa reiniciar containers para alterar .env, apenas limpar cache!

#### 13.8. Permissões e Acessos

```bash
# 1. Verificar permissões existentes
./vendor/bin/sail artisan tinker --execute="echo Spatie\Permission\Models\Permission::pluck('name');"

# 2. Criar permissões faltantes
./vendor/bin/sail artisan db:seed --class=RolesAndPermissionsSeeder

# 3. Dar permissão ao usuário admin
./vendor/bin/sail artisan tinker --execute="
\$user = \App\Models\User::find(USER_ID);
\$user->givePermissionTo('PERMISSION_NAME');
echo 'Permissão atribuída';
"
```

---

### ❌ Problemas Comuns e Soluções

#### Problema 1: Containers com Conflito de Porta
**Sintoma**: `Error: address already in use`

**Causa**: Outra aplicação ou Docker Desktop/WSL usando a mesma porta

**Solução**:
1. Alterar porta no `.env` (usar porta > 9000)
2. Parar containers: `./vendor/bin/sail down`
3. Subir novamente: `./vendor/bin/sail up -d`

#### Problema 2: Vite Manifest Not Found
**Sintoma**: `ViteManifestNotFoundException`

**Causa**: Assets frontend não foram compilados

**Solução**:
```bash
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
```

#### Problema 3: Container com Status "Unhealthy"
**Sintoma**: Container sobe mas fica marcado como unhealthy

**Causa**: Healthcheck falhando ou dependências não prontas

**Solução**:
1. Verificar logs: `./vendor/bin/sail logs CONTAINER_NAME`
2. Aumentar `start_period` no healthcheck (docker-compose.yml)
3. Verificar que MySQL/Redis estão healthy antes do Laravel

#### Problema 4: Permissão "permission denied" ao Parar Container
**Sintoma**: `cannot stop container: permission denied`

**Causa**: Container criado com usuário/grupo diferente

**Solução**:
- Não precisa parar! Use `config:cache` para aplicar mudanças do .env
- Se realmente precisar reiniciar, use: `docker-compose down` (sem sail)

#### Problema 5: Funcionalidade não Aparece (mesmo com código presente)
**Sintoma**: Código existe, rotas registradas, mas funcionalidade não aparece

**Verificações**:
1. Cache do Laravel: `./vendor/bin/sail artisan optimize:clear`
2. Permissões do usuário: Verificar se tem a permission necessária
3. Seeders: Executar seeders de permissões/roles
4. Browser cache: Limpar cache do navegador (Ctrl+Shift+R)

---

### 📋 Checklist de Deploy Completo

**Antes de Subir Containers**:
- [ ] `.env` configurado (DB, Redis, portas sem conflito)
- [ ] Docker limpo (se necessário)
- [ ] Portas verificadas (não estão em uso)

**Ao Subir Containers**:
- [ ] `./vendor/bin/sail up -d` executado
- [ ] Todos containers com status "healthy"
- [ ] Logs sem erros críticos

**Configuração da Aplicação**:
- [ ] `APP_KEY` gerado
- [ ] Migrações executadas
- [ ] Seeders de permissões executados
- [ ] Cache de config criado

**Assets Frontend**:
- [ ] `npm install` executado
- [ ] `npm run build` executado
- [ ] `public/build/manifest.json` existe

**Verificações Finais**:
- [ ] Aplicação acessível em http://IP_VPS
- [ ] Login funciona
- [ ] Permissões corretas atribuídas
- [ ] phpMyAdmin acessível (se necessário)
- [ ] Todas funcionalidades visíveis

---

### 🎯 Atalhos Úteis para Deploy Rápido

```bash
# Alias útil (adicionar ao ~/.bashrc)
alias sail='./vendor/bin/sail'

# Deploy completo em uma linha
sail up -d && \
sail artisan migrate && \
sail artisan db:seed --class=RolesAndPermissionsSeeder && \
sail npm install && \
sail npm run build && \
sail artisan optimize

# Verificação de saúde
docker ps --format "table {{.Names}}\t{{.Status}}" | grep healthy
```

---

## Conclusão

Estas práticas garantem:
1. **Eficiência**: Menos tokens = mais trabalho por sessão
2. **Qualidade**: Código consistente e otimizado
3. **Velocidade**: Menos refatorações e correções posteriores
4. **Manutenibilidade**: Código que segue padrões estabelecidos
5. **Alta Disponibilidade**: Aplicação sempre online, mesmo em VPS com recursos limitados
6. **Deploy Rápido**: Checklist claro para deploy em novas VPS sem dor de cabeça

**Data**: 2025-11-04
**Projeto**: EventosPro
**Versão**: 1.2
