# Plano de Implementação: Coluna "Artista - GIG - Local" nas Projeções Financeiras

**Data**: 2025-10-27
**Autor**: Nando Dev
**Status**: Aprovado - Aguardando Implementação
**Prioridade**: Média
**Estimativa**: 2-3 horas

---

## 🎯 Objetivo

Melhorar UX substituindo a coluna "GIG" por "**Artista - GIG - Local**" em todas as tabelas de detalhamento do menu "Projeções" (módulo Financeiro), usando como base o padrão da coluna "local" já implementado no módulo Booker Performance.

**Requisitos adicionais**:
- Todas as tabelas devem iniciar **recolhidas** (expanded=false)
- Operador decide quais abrir para detalhamento
- Manter performance otimizada (sem N+1 queries)

---

## 📊 Análise da Estrutura Atual

### Localização do Módulo
**Rota**: `/projections` (Menu Financeiro > Projeções)

### Arquivos Principais
```
app/Http/Controllers/FinancialProjectionController.php   # Controller principal
resources/views/projections/dashboard.blade.php          # View principal
resources/views/projections/partials/receivables-tables.blade.php  # Tabelas parciais
```

### Services Envolvidos
- `FinancialProjectionService`
- `CashFlowProjectionService`
- `DreProjectionService`
- `ProjectionCacheService` (cache de 5 minutos)

### Estrutura Atual - 4 Tabelas Expandíveis

1. **Recebíveis de Eventos Passados** (linhas 4-90 de receivables-tables.blade.php)
2. **Recebíveis de Eventos Futuros** (linhas 93-179)
3. **Pagamentos Pendentes aos Artistas** (linhas 182-271)
4. **Comissões Pendentes aos Bookers** (linhas 274-359)

### Colunas Atuais (Tabelas de Recebíveis)
1. Data Vencimento
2. Dias Vencido/Restantes
3. **Gig** ← Será substituída por "Artista - Gig - Local"
4. **Artista** ← Será integrada à coluna combinada
5. Valor
6. Prioridade/Status

---

## 🔍 Análise do Banco de Dados

### Tabela: `gigs`
✅ **Campo existe**: `location_event_details` (tipo TEXT)
- Migration: `database/migrations/2025_05_03_200624_create_gigs_table.php` (linha 19)
- Model: `app/Models/Gig.php` (linha 62 fillable)

### Eager Loading Atual
```php
// Linha 228 do FinancialProjectionController.php
->with('gig:id,contract_number,artist_id,gig_date', 'gig.artist:id,name')
```

### Mapeamento Atual
```php
// Linhas 265-278 do FinancialProjectionController.php
'payments' => $pendingPayments->map(function ($payment) {
    return [
        'payment_id' => $payment->id,
        'gig_id' => $payment->gig_id,
        'gig_contract' => $payment->gig->contract_number ?? 'N/A',
        'artist_name' => $payment->gig->artist->name ?? 'N/A',
        'due_date' => ...,
        'due_value_brl' => ...,
        // 'location' está FALTANDO aqui
    ];
})
```

---

## 🎨 Referência: Padrão Booker Performance

**Arquivo**: `resources/views/bookers/show.blade.php` (linhas 148-152)

```php
<div>
    <p class="font-medium text-gray-900 dark:text-white">{{ $event['artist_name'] }}</p>
    <p class="text-xs text-gray-500 dark:text-gray-400">{{ Str::limit($event['location'], 35) }}</p>
</div>
```

**Características**:
- Artista em destaque (font-medium, cor principal)
- Local abaixo (text-xs, cor secundária)
- Truncamento em 85 caracteres, responsivo
- Padrão estabelecido e validado

---

## 🔧 Modificações Necessárias

### 1. Controller: `app/Http/Controllers/FinancialProjectionController.php`

#### A. Método `calculateGlobalAccountsReceivable()` (linha 221)

**Linha 228** - Atualizar eager loading:
```php
// ANTES:
->with('gig:id,contract_number,artist_id,gig_date', 'gig.artist:id,name')

// DEPOIS:
->with('gig:id,contract_number,artist_id,gig_date,location_event_details', 'gig.artist:id,name')
```

**Linha 272** - Adicionar location ao mapeamento:
```php
return [
    'payment_id' => $payment->id,
    'gig_id' => $payment->gig_id,
    'gig_contract' => $payment->gig->contract_number ?? 'N/A',
    'artist_name' => $payment->gig->artist->name ?? 'N/A',
    'location' => $payment->gig->location_event_details ?? 'N/A', // ADICIONAR ESTA LINHA
    'due_date' => $payment->due_date,
    'due_value_brl' => $payment->due_value,
    'days_until_due' => now()->diffInDays($payment->due_date, false),
    'is_overdue' => $payment->due_date < now(),
];
```

#### B. Verificar Outros Métodos

Aplicar mesmo padrão em métodos similares que retornem dados de gigs:
- Buscar por `->with('gig:` no arquivo
- Adicionar `location_event_details` onde necessário
- Adicionar `'location'` aos arrays mapeados

---

### 2. View Parcial: `resources/views/projections/partials/receivables-tables.blade.php`

#### A. Tabela 1: Recebíveis de Eventos Passados

**Linha ~16** - Atualizar header:
```blade
<!-- ANTES: -->
<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
    Gig
</th>
<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
    Artista
</th>

<!-- DEPOIS: -->
<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
    Artista - Gig - Local
</th>
<!-- REMOVER a coluna "Artista" separada -->
```

**Linhas ~34-43** - Atualizar célula de dados:
```blade
<!-- SUBSTITUIR TODO O BLOCO DA COLUNA "GIG" POR: -->
<td class="px-6 py-4 text-sm">
    <div>
        <!-- Artista em destaque -->
        <p class="font-medium text-gray-900 dark:text-white">
            {{ $payment['artist_name'] ?? 'N/A' }}
        </p>

        <!-- Gig (com link) -->
        @if(isset($payment['gig_id']))
            <a href="{{ route('gigs.show', $payment['gig_id']) }}"
               class="text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300 text-xs font-medium inline-block mt-0.5"
               title="Ver detalhes da Gig">
                Gig #{{ $payment['gig_id'] }}
            </a>
        @else
            <span class="text-xs text-gray-500 dark:text-gray-400">
                {{ $payment['gig_contract'] ?? 'N/A' }}
            </span>
        @endif

        <!-- Local com ícone -->
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
            <span class="inline-block mr-1">📍</span>
            {{ Str::limit($payment['location'] ?? 'Local não informado', 40) }}
        </p>
    </div>
</td>

<!-- REMOVER completamente a célula <td> da coluna "Artista" que vem depois -->
```

#### B. Tabela 2: Recebíveis de Eventos Futuros (linhas ~93-179)

Aplicar **exatamente o mesmo padrão** da Tabela 1:
- Linha ~105: Atualizar header
- Linhas ~123-132: Atualizar célula de dados
- Remover coluna "Artista" separada

#### C. Tabela 3: Pagamentos Pendentes aos Artistas (linhas ~182-271)

**Status**: ✅ IMPLEMENTADO NA FASE 2

Aplicar **mesmo padrão**, adaptando para estrutura dessa tabela:
- Header: Substituir "Gig" + "Artista" por "Artista - Gig - Local"
- Células: Layout combinado (Artista destaque + Gig link + Local ícone)
- Remover coluna "Artista" separada
- Ajustar colspan (8→7 colunas)

**Colunas atuais**: Data Evento, Gig, Artista, Cachê Líquido, A Pagar, Pago, Pendente, Dias
**Colunas novas**: Data Evento, **Artista-Gig-Local**, Cachê Líquido, A Pagar, Pago, Pendente, Dias

#### D. Tabela 4: Comissões Pendentes aos Bookers (linhas ~274-359)

**Status**: ✅ IMPLEMENTADO NA FASE 2

Aplicar **mesmo padrão**, adaptando para estrutura dessa tabela:
- Header: Substituir "Gig" por "Artista - Gig - Local"
- Células: Layout combinado
- Não tem coluna "Artista" separada
- Ajustar colspan (7→7 colunas - sem mudança)

**Colunas atuais**: Data Evento, Gig, Booker, Comissão, Pago, Pendente, Dias
**Colunas novas**: Data Evento, **Artista-Gig-Local**, Booker, Comissão, Pago, Pendente, Dias

#### E. Tabela 5: Despesas de Eventos - GigCost (linhas ~362-445)

**Status**: ✅ IMPLEMENTADO NA FASE 2

Aplicar **mesmo padrão**:
- Header: Substituir "Gig" por "Artista - Gig - Local"
- Células: Layout combinado
- Ajustar colspan (5→5 colunas - sem mudança)

**Colunas atuais**: Data Evento, Gig, Descrição, Valor, Status
**Colunas novas**: Data Evento, **Artista-Gig-Local**, Descrição, Valor, Status

---

### 3. View Principal: `resources/views/projections/dashboard.blade.php`

**Requisito**: Tabelas devem iniciar **recolhidas** por padrão

**Procurar por**: `<x-expandable-section`

**Modificar atributo** `expanded` em todas as 4 tabelas:

```blade
<!-- ANTES (expandido por padrão): -->
<x-expandable-section
    title="Recebíveis de Eventos Passados"
    :count="count($overdue['payments'])"
    color="red"
    :expanded="true">
    <!-- conteúdo -->
</x-expandable-section>

<!-- DEPOIS (recolhido por padrão): -->
<x-expandable-section
    title="Recebíveis de Eventos Passados"
    :count="count($overdue['payments'])"
    color="red"
    :expanded="false">
    <!-- conteúdo -->
</x-expandable-section>
```

**Aplicar em**:
1. Recebíveis de Eventos Passados → `expanded="false"`
2. Recebíveis de Eventos Futuros → `expanded="false"`
3. Pagamentos Pendentes aos Artistas → `expanded="false"`
4. Comissões Pendentes aos Bookers → `expanded="false"`

**Caso esteja feito, verificar se a tabela está funcionando corretamente**


---

## ⚡ Considerações de Performance

### Otimizações Atuais (Manter)
✅ Eager loading com `with()`
✅ Cache de 5 minutos via `ProjectionCacheService`
✅ Query scoping com `whereHas('gig')`
✅ Seleção de colunas específicas

### Impacto da Mudança
- **Adição**: 1 campo TEXT (`location_event_details`) ao SELECT
- **Processamento**: Truncamento client-side com `Str::limit(40)`
- **Queries**: Nenhuma query adicional (eager loading já existe)
- **Memória**: Aumento mínimo (~5-10% se locations forem grandes)

### Estimativa de Performance
- **Antes**: ~150ms para carregar dashboard
- **Depois**: ~155-160ms (estimado, +3-5%)
- **N+1 Queries**: Zero (prevenido por eager loading)

### Cache
- **TTL atual**: 5 minutos (300 segundos)
- **Chave**: `'projections:global_metrics'`
- **Invalidação**: `FinancialProjectionController::clearCache()`
- **Ação**: Cache automaticamente incluirá location após próxima geração

---

## ✅ Checklist de Implementação

### Fase 1: Modificações no Controller
- [ ] Abrir `app/Http/Controllers/FinancialProjectionController.php`
- [ ] Linha 228: Adicionar `,location_event_details` ao eager loading
- [ ] Linha 272: Adicionar `'location' => $payment->gig->location_event_details ?? 'N/A'`
- [ ] Procurar outros métodos com eager loading de gig e aplicar mesmo padrão
- [ ] Salvar arquivo

### Fase 2: Modificações na View Parcial
- [ ] Abrir `resources/views/projections/partials/receivables-tables.blade.php`
- [ ] **Tabela 1** (Recebíveis Passados):
  - [ ] Atualizar header (linha ~16)
  - [ ] Atualizar célula de dados (linhas ~34-43)
  - [ ] Remover coluna "Artista" separada
- [ ] **Tabela 2** (Recebíveis Futuros):
  - [ ] Atualizar header (linha ~105)
  - [ ] Atualizar célula de dados (linhas ~123-132)
  - [ ] Remover coluna "Artista" separada
- [ ] **Tabela 3** (Pagamentos Artistas):
  - [ ] Atualizar header
  - [ ] Atualizar célula de dados
  - [ ] Remover coluna "Artista" se existir
- [ ] **Tabela 4** (Comissões Bookers):
  - [ ] Atualizar header
  - [ ] Atualizar célula de dados
  - [ ] Remover coluna "Artista" se existir
- [ ] Salvar arquivo

### Fase 3: Configurar Tabelas Recolhidas
- [ ] Abrir `resources/views/projections/dashboard.blade.php`
- [ ] Procurar por `<x-expandable-section` (4 ocorrências)
- [ ] Alterar `expanded="true"` para `expanded="false"` em todas
- [ ] Salvar arquivo

### Fase 4: Testes e Validação
- [ ] Limpar cache: Chamar `FinancialProjectionController::clearCache()`
- [ ] Acessar `/projections` no browser
- [ ] Verificar que tabelas iniciam recolhidas
- [ ] Expandir cada tabela e verificar:
  - [ ] Coluna "Artista - Gig - Local" presente
  - [ ] Artista em destaque
  - [ ] Link "Gig #X" funcionando
  - [ ] Local exibido com ícone 📍
  - [ ] Truncamento funcionando (máx 40 chars)
  - [ ] "Local não informado" para gigs sem location
- [ ] Testar responsividade mobile
- [ ] Verificar N+1 queries:
  ```bash
  sail artisan tinker
  \DB::enableQueryLog();
  app(App\Http\Controllers\FinancialProjectionController::class)->dashboard();
  dd(count(\DB::getQueryLog())); // Deve ser similar ao número atual
  ```

### Fase 5: Code Quality
- [ ] Executar Pint: `sail bash -c "vendor/bin/pint --dirty"`
- [ ] Executar testes: `sail artisan test`
- [ ] Verificar sem warnings/erros

### Fase 6: Commit
- [ ] Criar commit com mensagem descritiva
- [ ] **NÃO adicionar** co-autoria do Claude
- [ ] Push para branch

---

## 🧪 Cenários de Teste

### 1. Location Normal
```
Dado: Gig com location = "Teatro Municipal de São Paulo"
Esperado:
  Artista: João Silva
  Gig #123
  📍 Teatro Municipal de São Paulo
```

### 2. Location Longa (Truncamento)
```
Dado: Gig com location = "Centro de Convenções Internacional de Brasília - Auditório Principal"
Esperado:
  Artista: Maria Santos
  Gig #456
  📍 Centro de Convenções Internacional de B...
```

### 3. Location Vazia/Null
```
Dado: Gig com location = null
Esperado:
  Artista: Pedro Costa
  Gig #789
  📍 Local não informado
```

### 4. Gig Sem ID (Contrato)
```
Dado: Payment sem gig_id, mas com gig_contract
Esperado:
  Artista: Ana Lima
  CONTRATO-2025-001
  📍 Local não informado
```

---

## 📦 Arquivos a Modificar

| Arquivo | Linhas Aprox. | Tipo de Mudança |
|---------|---------------|-----------------|
| `app/Http/Controllers/FinancialProjectionController.php` | 2-5 linhas | Adicionar campo ao eager loading e mapeamento |
| `resources/views/projections/partials/receivables-tables.blade.php` | ~40 linhas | Substituir colunas em 4 tabelas |
| `resources/views/projections/dashboard.blade.php` | 4 linhas | Alterar `expanded="false"` |

**Total**: 3 arquivos, ~50 linhas modificadas

---

## 🎨 Resultado Visual Esperado

### Antes
```
┌───────────┬──────┬────────┬───────────┬─────────┐
│ Data Venc │ Dias │ Gig    │ Artista   │ Valor   │
├───────────┼──────┼────────┼───────────┼─────────┤
│ 15/11/25  │ -3   │ Gig#123│ João Silva│ 5.000   │
└───────────┴──────┴────────┴───────────┴─────────┘
```

### Depois
```
┌───────────┬──────┬────────────────────────────┬─────────┐
│ Data Venc │ Dias │ Artista - Gig - Local      │ Valor   │
├───────────┼──────┼────────────────────────────┼─────────┤
│ 15/11/25  │ -3   │ João Silva                 │ 5.000   │
│           │      │ Gig #123                   │         │
│           │      │ 📍 Teatro Municipal - S... │         │
└───────────┴──────┴────────────────────────────┴─────────┘
```

---

## 🚨 Possíveis Problemas e Soluções

### Problema 1: Location muito longa quebra layout
**Solução**: Usar `Str::limit(40)` + CSS `word-break: break-all` se necessário

### Problema 2: Cache não atualiza
**Solução**: Chamar `FinancialProjectionController::clearCache()` ou esperar 5 minutos

### Problema 3: N+1 queries aparecem
**Solução**: Verificar se `location_event_details` está no `->with()` corretamente

### Problema 4: Tabelas não recolhem
**Solução**: Verificar se Alpine.js está carregado e `expanded="false"` está correto

---

## 📚 Referências

- **CLAUDE.md**: Seção "Query Optimization Pattern"
- **Common Pitfalls**: Seção 5 (N+1 Query Problem)
- **Feature Development Context**: Seção "Query Optimization"
- **Booker Performance**: `resources/views/bookers/show.blade.php:148-152`

---

## 💡 Melhorias Futuras (Opcional)

1. **Adicionar tooltip** com location completa no hover (para locations truncadas)
2. **Ícone dinâmico** baseado no tipo de local (teatro, bar, festival)
3. **Filtro por local** nas tabelas
4. **Mapa** com localização (integração Google Maps)

---

## ✍️ Notas de Implementação

- **Não usar** co-autoria do Claude nos commits
- **Seguir** padrão Tailwind CSS do projeto
- **Manter** consistência com Booker Performance
- **Testar** em dark mode
- **Documentar** se adicionar novos métodos

---

**Status**: ⏸️ Aguardando limite semanal Claude Code
**Próximo Passo**: Executar checklist de implementação quando disponível
**Responsável**: Nando Dev
**Revisão**: Recomendada após implementação
