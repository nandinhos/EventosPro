# Contratos de Arquitetura - EventosPro

> **Padrões e Contratos** de arquitetura para desenvolvimento
> **Fonte**: `doc-project/ai_context/2_architecture.md`, `doc-project/CODE_STANDARDS.md`
> **Versão**: 1.0 | **Atualizado**: 2026-02-10

---

## 📐 Arquitetura em 3 Camadas

```
┌─────────────────────────────────────────┐
│  CAMADA 1: Apresentação (Presentation)   │
│  - Filament v4 Resources                │
│  - Blade Templates                      │
│  - Tailwind CSS + Alpine.js             │
└─────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────┐
│  CAMADA 2: Lógica de Negócio (Business) │
│  - Controllers → Validação             │
│  - Services → Cálculos de Negócio      │
│  - Observers → Eventos Automáticos      │
│  - Policies → Autorização               │
└─────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────┐
│  CAMADA 3: Dados (Data)                 │
│  - Models (Eloquent ORM)                │
│  - Migrations (Controle de Schema)      │
│  - Seeders (Dados Base)                 │
│  - MySQL 8.0+ (27 Tabelas)              │
└─────────────────────────────────────────┘
```

---

## 🎯 Contrato #1: Service Layer Pattern

### Definição
Toda lógica de negócio complexa deve ser encapsulada em services. **NUNCA** coloque lógica de negócio em controllers.

### Localização
`app/Services/`

### Estrutura de um Service

```php
<?php

namespace App\Services;

use App\Models\Gig;

class GigFinancialCalculatorService
{
    // Injeção de dependência no construtor (PHP 8.4+)
    public function __construct(
        private ?SomeDependency $dependency = null
    ) {}

    // Métodos públicos com tipos explícitos
    public function calculateContractValueBrl(Gig $gig): float
    {
        // Lógica de negócio aqui
    }

    // Métodos privados para encapsular detalhes
    private function convertToBrl(float $value, string $currency): float
    {
        // Conversão
    }
}
```

### Contrato de Uso

1. **Cálculos Financeiros**: SEMPRE use `GigFinancialCalculatorService`
2. **Projeções**: Use `FinancialProjectionService` e derivados
3. **Relatórios**: Use `FinancialReportService`
4. **Auditoria**: Use `AuditService`

### Exemplo NO Controller (Correto)

```php
use App\Services\GigFinancialCalculatorService;

class GigController extends Controller
{
    public function __construct(
        private GigFinancialCalculatorService $calculator
    ) {}

    public function show(Gig $gig)
    {
        $contractValue = $this->calculator->calculateContractValueBrl($gig);
        $agencyCommission = $this->calculator->calculateTotalAgencyCommissionBrl($gig);
        $netValue = $this->calculator->calculateNetValueForArtistBrl($gig);

        return view('gigs.show', [
            'gig' => $gig,
            'contractValue' => $contractValue,
            'agencyCommission' => $agencyCommission,
            'netValue' => $netValue,
        ]);
    }
}
```

### Exemplo NÃO FAZER (Errado)

```php
// NÃO coloque lógica de negócio no controller
public function show(Gig $gig)
{
    $contractValue = $gig->contract_value * $exchangeRate; // ERRADO
    // ...
}
```

---

## 📡 Contrato #2: Observer Pattern

### Definição
Reações automáticas a eventos do lifecyle de modelos (created, updated, deleted, etc).

### Localização
`app/Observers/`

### Observadores Ativos
| Observer | Modelo | Eventos Monitorados |
|----------|--------|---------------------|
| `GigObserver` | `Gig` | created, updated, deleted |
| `GigCostObserver` | `GigCost` | created, updated, deleted |

### Estrutura de um Observer

```php
<?php

namespace App\Observers;

use App\Models\Gig;
use Illuminate\Support\Facades\Log;

class GigObserver
{
    public function creating(Gig $gig): void
    {
        // Antes de criar
        Log::info('Criando gig', ['gig_id' => $gig->id]);
    }

    public function created(Gig $gig): void
    {
        // Depois de criar
        Log::info('Gig criada com sucesso', ['gig_id' => $gig->id]);
    }

    public function updating(Gig $gig): void
    {
        // Antes de atualizar
    }

    public function updated(Gig $gig): void
    {
        // Depois de atualizar - verificar mudanças
        if ($gig->isDirty('contract_value')) {
            // Recalcular valores dependentes
        }
    }

    public function deleting(Gig $gig): void
    {
        // Antes de deletar (soft delete)
    }

    public function deleted(Gig $gig): void
    {
        // Depois de deletar
    }
}
```

### Quando Usar Observers
- ✅ Recálculo automático de valores
- ✅ Notificações de eventos
- ✅ Manutenção de integridade referencial
- ✅ Log de auditoria

### Quando NÃO Usar Observers
- ❌ Envio de emails comuns (usar Events/Listeners)
- ❌ Lógica complexa que precisa de tratamento de erros específico
- ❌ Ações que precisam ser facilmente desativáveis

---

## 🛡️ Contrato #3: Policy Pattern

### Definição
Controle de autorização granular por operação e modelo.

### Localização
`app/Policies/`

### Estrutura de uma Policy

```php
<?php

namespace App\Policies;

use App\Models\Gig;
use App\Models\User;

class GigPolicy
{
    /**
     * Determina se o usuário pode ver o gig.
     */
    public function view(User $user, Gig $gig): bool
    {
        return $user->hasPermissionTo('view any gig')
            || ($user->hasPermissionTo('view own gig') && $gig->isOwner($user));
    }

    /**
     * Determina se o usuário pode criar gigs.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create gig');
    }

    /**
     * Determina se o usuário pode atualizar o gig.
     */
    public function update(User $user, Gig $gig): bool
    {
        return $user->hasPermissionTo('update any gig')
            || ($user->hasPermissionTo('update own gig') && $gig->isOwner($user));
    }

    /**
     * Determina se o usuário pode deletar o gig.
     */
    public function delete(User $user, Gig $gig): bool
    {
        return $user->hasPermissionTo('delete any gig')
            || ($user->hasPermissionTo('delete own gig') && $gig->isOwner($user));
    }
}
```

### Como Usar Policies no Controller

```php
// Via helper (automático)
public function update(Request $request, Gig $gig)
{
    $this->authorize('update', $gig); // Verifica GigPolicy@update

    // ...
}

// Via Policy explicita
public function destroy(Gig $gig)
{
    if (Gate::forUser(auth()->user())->denies('delete', $gig)) {
        abort(403);
    }

    // ...
}
```

### Policies Existentes
| Policy | Modelo |
|--------|--------|
| `GigPolicy` | `Gig` |
| `ArtistPolicy` | `Artist` |
| `BookerPolicy` | `Booker` |
| `UserPolicy` | `User` |

---

## 🧾 Contrato #4: Request Validation Pattern

### Definição
Validação centralizada e reutilizável para cada operação.

### Localização
`app/Http/Requests/`

### Estrutura de uma Form Request

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

class StoreGigRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Verifica se usuário tem permissão
        return $this->user()->can('create gig');
    }

    public function rules(): array
    {
        return [
            'artist_id' => 'required|exists:artists,id',
            'booker_id' => 'nullable|exists:bookers,id',
            'event_date' => 'required|date',
            'contract_value' => 'required|numeric|min:0',
            'currency' => 'required|in:BRL,USD,EUR',
            'description' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'artist_id.required' => 'O artista é obrigatório',
            'event_date.required' => 'A data do evento é obrigatória',
            'contract_value.numeric' => 'O valor deve ser numérico',
            'currency.in' => 'Moeda inválida (BRL, USD, EUR)',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        // Tratamento personalizado de falha
    }
}
```

### Como Usar no Controller

```php
public function store(StoreGigRequest $request)
{
    // $request já está validado e autorizado
    $validated = $request->validated();

    $gig = Gig::create($validated);

    return redirect()->route('gigs.show', $gig)
        ->with('success', 'Gig criada com sucesso');
}
```

### Request Existentes (Exemplos)
| Request | Descrição |
|---------|-----------|
| `StoreGigRequest` | Criação de gig |
| `UpdateGigRequest` | Atualização de gig |
| `StorePaymentRequest` | Criação de pagamento |
| `SettleArtistRequest` | Liquidação de artista |
| `SettleBookerRequest` | Liquidação de booker |

---

## 🔄 Contrato #5: Soft Delete Pattern

### Definição
Preservar histórico financeiro e permitir restauração.

### Implementação

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Gig extends Model
{
    use SoftDeletes; // Habilita soft delete

    protected $dates = ['deleted_at'];
}
```

### Quando Soft Delete é Obrigatório
✅ **Modelos com impacto financeiro**:
- `Gig` (histórico de eventos completos)
- `Payment` (histórico de pagamentos)
- `GigCost` (histórico de custos)
- `Settlement` (histórico de acertos)
- `Artist`, `Booker` (histórico de relacionamentos)

### Métodos Disponíveis

```php
// Soft delete (não remove do banco, apenas marca deleted_at)
$gig->delete();

// Restore (restaura do soft delete)
$gig->restore();

// Force delete (remove permanentemente)
$gig->forceDelete();

// Query com trashed
Gig::withTrashed()->get(); // Inclui deletados
Gig::onlyTrashed()->get(); // Apenas deletados
Gig::withoutTrashed()->get(); // Apenas ativos (padrão)
```

### Atenção
- NUNCA use `forceDelete()` sem aprovação explícita
- Sempre considere impacto em relatórios históricos
- Use `withTrashed()` para consultas que precisam do histórico complet

---

## 🌐 Contrato #6: Model Relationships

### Padrão de Nomenclatura

| Tipo de Relação | Convenção |
|-----------------|-----------|
| 1:N (um para muitos) | Plural (`gig` → `payments`) |
| 1:1 (um para um) | Singular (`gig` → `settlement`) |
| N:1 (muitos para um) | Prefixo com id (`payment` → `gig_id`) |
| N:M (muitos para muitos) | Pivot table |

### Definição no Model

```php
class Gig extends Model
{
    // HasMany:Um gig tem muitos pagamentos
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // HasMany:Um gig tem muitos custos
    public function costs(): HasMany
    {
        return $this->hasMany(GigCost::class, 'gig_id');
    }

    // HasOne:Um gig tem um acerto (settlement)
    public function settlement(): HasOne
    {
        return $this->hasOne(Settlement::class, 'gig_id');
    }

    // BelongsTo:Um gig pertence a um artista
    public function artist(): BelongsTo
    {
        return $this->belongsTo(Artist::class, 'artist_id');
    }

    // BelongsTo:Um gig pertence a um booker (opcional)
    public function booker(): BelongsTo
    {
        return $this->belongsTo(Booker::class, 'booker_id');
    }
}
```

### Relacionamento Polimórfico (Tags)

```php
// Model com tags polimórficas
class Gig extends Model
{
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }
}

// Migration de tabela polimórfica
Schema::create('taggables', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tag_id')->constrained();
    $table->morphs('taggable'); // Cria taggable_id e taggable_type
});
```

### Eager Loading (Prevenir N+1)

```php
// ❌ SEMPRE EVITE (causa N+1 queries)
$gigs = Gig::all();
foreach ($gigs as $gig) {
    echo $gig->artist->name; // Query extra para cada gig!
}

// ✅ SEMPRE USE (carrega relacionamentos em 1 query)
$gigs = Gig::with(['artist', 'booker', 'payments', 'costs'])->get();
foreach ($gigs as $gig) {
    echo $gig->artist->name; // Nenhuma query extra!
}
```

---

## 🚦 Fluxo de Requisição

### Arquétipo Padrão

```
1. Rota (routes/web.php)
   ↓
2. Middleware (auth, permissions)
   ↓
3. Controller
   ├─ Form Request (validação)
   ├─ Policy (autorização)
   └─ Service Layer (lógica de negócio)
   ↓
4. Model (Eloquent)
   ├─ Query com eager loading
   └─ Observer (eventos automáticos)
   ↓
5. View/Response
   ├─ Filament Resource
   ├─ Blade Template
   └─ JSON Response
```

### Exemplo: Criação de Gig

```php
// 1. Rota: POST /gigs

// 2. Controller
public function store(StoreGigRequest $request, GigService $service)
{
    // 3. Validacão automática via StoreGigRequest
    $validated = $request->validated();

    // 4. Criação service layer (se necessário)
    $gig = $service->createGig($validated);

    // 5. GigObserver::created() disparado
    // - Log criado
    // - Recalculos automáticos (se necessário)

    return redirect()->route('gigs.show', $gig);
}
```

---

## 📐 Padrões de Código PHP 8.4

### Constructor Property Promotion

```php
// ✅ PHP 8.4+ (recomendado)
public function __construct(
    private GigFinancialCalculatorService $calculator
) {}

// ❌ PHP 8.0- (evitar)
private GigFinancialCalculatorService $calculator;

public function __construct(GigFinancialCalculatorService $calculator)
{
    $this->calculator = $calculator;
}
```

### Tipos Explícitos

```php
// ✅ SEMPRE use tipos
public function calculateNetValueBrl(Gig $gig): float
{
    return $gig->contract_value;
}

// ❌ NÃO omita tipos
public function calculateNetValueBrl($gig)
{
    return $gig->contract_value;
}
```

### PHPDoc Opcional

```php
/**
 * Calcula o valor líquido para o artista em BRL.
 *
 * @param Gig $gig
 * @return float
 */
public function calculateNetValueBrl(Gig $gig): float
```

---

## 🎯 Checklist de Arquitetura

### Antes de Criar Novo Código:
- [ ] Identificar se lógica pertence a Service existente
- [ ] Criar Form Request para validação
- [ ] Verificar se Policy existe para autorização
- [ ] Determinar se Observer é necessário
- [ ] Garantir Soft Delete em modelos principais
- [ ] Verificar se Model relacionamentos estão definidos
- [ ] Usar Eager Loading para evitar N+1
- [ ] Seguir convenção de nomenclatura de routes

### Antes de Comitar:
- [ ] Executar `vendor/bin/sail bin pint --dirty`
- [ ] Executar `vendor/bin/sail artisan test --filter=nomeDoTeste`
- [ ] Verificar se types PHP 8.4 estão corretos
- [ ] Validar se Service Layer foi usado
- [ ] Confirmar não há lógica de negócio em controllers

---

**Versão**: 1.0
**Fonte Detalhada**: `doc-project/ai_context/2_architecture.md`, `doc-project/CODE_STANDARDS.md`
**Próxima Referência**: `.aidev/context/testing-patterns.md`