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

## Conclusão

Estas práticas garantem:
1. **Eficiência**: Menos tokens = mais trabalho por sessão
2. **Qualidade**: Código consistente e otimizado
3. **Velocidade**: Menos refatorações e correções posteriores
4. **Manutenibilidade**: Código que segue padrões estabelecidos
5. **Alta Disponibilidade**: Aplicação sempre online, mesmo em VPS com recursos limitados

**Data**: 2025-10-22
**Projeto**: EventosPro
**Versão**: 1.1
