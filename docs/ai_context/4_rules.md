# Regras e Convenções de Desenvolvimento - EventosPro

## Guia de Estilo e Convenções

### Código PHP

#### Padrões de Codificação
- **PSR-12**: Seguir rigorosamente o padrão PSR-12 para formatação
- **Laravel Pint**: Usar para formatação automática do código
- **Nomenclatura**: 
  - Classes: `PascalCase` (ex: `GigController`, `FinancialReportService`)
  - Métodos: `camelCase` (ex: `calculateGrossCache`, `dueDatesReport`)
  - Variáveis: `camelCase` (ex: `$gigFinancialData`, `$duePayments`)
  - Constantes: `UPPER_SNAKE_CASE`

#### Estrutura de Classes
```php
<?php

namespace App\Controllers;

use App\Models\Gig;
use App\Services\GigFinancialCalculatorService;

class GigController extends Controller
{
    // Propriedades primeiro
    protected $gigFinancialCalculator;
    
    // Construtor
    public function __construct(GigFinancialCalculatorService $calculator)
    {
        $this->gigFinancialCalculator = $calculator;
    }
    
    // Métodos públicos
    // Métodos protegidos
    // Métodos privados
}
```

### Código JavaScript

#### Padrões Alpine.js
- **Nomenclatura**: `camelCase` para propriedades e métodos
- **Estrutura**: Manter lógica simples e declarativa
- **Exemplo**:
```javascript
<div x-data="{
    isOpen: false,
    toggleModal() {
        this.isOpen = !this.isOpen;
    }
}">
```

#### Integração com Backend
- **CSRF**: Sempre incluir token CSRF em requisições POST
- **Fetch API**: Usar para requisições AJAX
- **Error Handling**: Tratar erros adequadamente

### CSS/Tailwind

#### Convenções de Classes
- **Componentes**: Criar classes de componente quando necessário
- **Responsividade**: Mobile-first approach
- **Cores**: Usar palette definida no `tailwind.config.js`
- **Espaçamento**: Seguir escala de espaçamento do Tailwind

#### Exemplo de Estrutura
```html
<div class="bg-white rounded-lg shadow-md p-6 mb-4">
    <h3 class="text-lg font-semibold text-gray-900 mb-2">
        Título do Card
    </h3>
    <p class="text-gray-600 text-sm">
        Conteúdo do card
    </p>
</div>
```

## Padrões de Desenvolvimento

### Estrutura de Controllers

#### Métodos Padrão
- `index()`: Listagem com filtros e paginação
- `create()`: Formulário de criação
- `store()`: Processamento de criação
- `show()`: Visualização detalhada
- `edit()`: Formulário de edição
- `update()`: Processamento de edição
- `destroy()`: Exclusão (soft delete preferível)

#### Validação
- **Form Requests**: Sempre usar para validação complexa
- **Nomenclatura**: `Store{Model}Request`, `Update{Model}Request`
- **Regras**: Definir regras específicas por contexto

### Estrutura de Models

#### Convenções Obrigatórias
```php
class Gig extends Model
{
    use HasFactory, SoftDeletes;
    
    // Fillable/Guarded sempre definido
    protected $fillable = ['field1', 'field2'];
    
    // Casts para tipos específicos
    protected $casts = [
        'event_date' => 'date',
        'cache_value' => 'decimal:2',
    ];
    
    // Relacionamentos
    public function artist(): BelongsTo
    {
        return $this->belongsTo(Artist::class);
    }
    
    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
    
    // Accessors/Mutators se necessário
}
```

### Services (Camada de Serviço)

#### Quando Criar Services
- Lógica de negócio complexa
- Cálculos financeiros
- Operações que envolvem múltiplos modelos
- Lógica reutilizável entre controllers

#### Estrutura de Service
```php
class GigFinancialCalculatorService
{
    public function calculateGrossCache(Gig $gig): array
    {
        // Lógica de cálculo
        return [
            'gross_cache_brl' => $value,
            'warnings' => $warnings
        ];
    }
}
```

## Testes

### Estratégia de Testes
- **Unit Tests**: Para Services e métodos de Models
- **Feature Tests**: Para fluxos completos de Controllers
- **Database**: Usar transações para rollback automático

### Convenções de Teste
```php
class GigControllerTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_user_can_create_gig(): void
    {
        // Arrange
        $user = User::factory()->create();
        $gigData = Gig::factory()->make()->toArray();
        
        // Act
        $response = $this->actingAs($user)
            ->post(route('gigs.store'), $gigData);
        
        // Assert
        $response->assertRedirect();
        $this->assertDatabaseHas('gigs', ['name' => $gigData['name']]);
    }
}
```

## Commits e Versionamento

### Conventional Commits
Seguir o padrão Conventional Commits:

```
feat: adiciona cálculo de comissão automática
fix: corrige bug no filtro de datas
docs: atualiza documentação da API
style: formata código seguindo PSR-12
refactor: extrai lógica financeira para service
test: adiciona testes para GigController
chore: atualiza dependências do composer
```

### Estrutura de Branches
- `main`: Branch principal (produção)
- `develop`: Branch de desenvolvimento
- `feature/nome-da-feature`: Features específicas
- `hotfix/nome-do-fix`: Correções urgentes

## O Que NÃO Fazer

### Antipatterns Identificados

#### 1. **Lógica de Negócio em Controllers**
❌ **Evitar**:
```php
public function store(Request $request)
{
    // Cálculos complexos diretamente no controller
    $grossCache = ($request->cache_value * $exchangeRate) - $expenses;
    $commission = $grossCache * 0.15;
    // ...
}
```

✅ **Fazer**:
```php
public function store(StoreGigRequest $request)
{
    $gig = Gig::create($request->validated());
    $financialData = $this->gigFinancialCalculator->calculate($gig);
    // ...
}
```

#### 2. **Queries N+1**
❌ **Evitar**:
```php
$gigs = Gig::all();
foreach ($gigs as $gig) {
    echo $gig->artist->name; // N+1 query
}
```

✅ **Fazer**:
```php
$gigs = Gig::with('artist')->get();
foreach ($gigs as $gig) {
    echo $gig->artist->name;
}
```

#### 3. **Mass Assignment Vulnerabilities**
❌ **Evitar**:
```php
Gig::create($request->all()); // Perigoso
```

✅ **Fazer**:
```php
Gig::create($request->validated()); // Seguro
```

#### 4. **Hard Delete sem Justificativa**
❌ **Evitar**: `$gig->delete()` para dados importantes
✅ **Fazer**: Usar `SoftDeletes` para preservar histórico

#### 5. **Inline Styles e Scripts**
❌ **Evitar**: CSS e JS inline nas views
✅ **Fazer**: Usar assets compilados via Vite

### Práticas de Segurança

#### Obrigatórias
- **CSRF Protection**: Sempre ativo em formulários
- **Mass Assignment**: Definir `$fillable` ou `$guarded`
- **SQL Injection**: Usar Eloquent ou Query Builder
- **XSS Protection**: Escapar output com `{{ }}` no Blade
- **Authorization**: Verificar permissões em actions sensíveis

#### Dados Sensíveis
- **Nunca** commitar arquivos `.env`
- **Nunca** expor chaves de API em código
- **Sempre** usar `config()` para acessar configurações

## Padrões de Views

### Estrutura de Blade
```blade
<x-app-layout>
    <x-slot name="title">
        Título da Página
    </x-slot>

    <div class="container mx-auto px-4 py-6">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">
                Título Principal
            </h1>
        </div>

        {{-- Conteúdo principal --}}
    </div>

    <x-slot name="scripts">
        <script>
            // Scripts específicos da página
        </script>
    </x-slot>
</x-app-layout>
```

### Componentes Reutilizáveis
- Criar componentes para elementos repetitivos
- Usar `@component` ou `<x-component>` syntax
- Documentar props e slots

## Performance e Otimização

### Database
- **Índices**: Criar em campos de busca frequente
- **Eager Loading**: Carregar relacionamentos necessários
- **Pagination**: Sempre paginar listagens grandes
- **Query Optimization**: Revisar queries complexas

### Frontend
- **Asset Optimization**: Usar Vite para bundling
- **Image Optimization**: Otimizar imagens antes do upload
- **Lazy Loading**: Implementar quando apropriado
- **Caching**: Usar cache de views em produção

## Documentação

### Código
- **DocBlocks**: Documentar métodos complexos
- **README**: Manter atualizado com setup e deployment
- **CHANGELOG**: Registrar mudanças importantes

### API (se aplicável)
- Documentar endpoints com exemplos
- Especificar formatos de request/response
- Incluir códigos de erro possíveis