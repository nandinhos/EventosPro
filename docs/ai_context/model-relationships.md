# Relacionamentos dos Modelos - EventosPro

**Data de Atualização: 27/09/2025**  
**Status: Atualizado com correções PHPDoc implementadas**

## 📋 Visão Geral

Este documento detalha os relacionamentos entre os modelos do sistema EventosPro, incluindo as correções de PHPDoc e type hints implementadas para resolver erros PHPStan.

## 🔗 Relacionamentos Principais

### User Model
**Arquivo**: `app/Models/User.php`

#### Propriedades Documentadas
```php
/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property int|null $booker_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Booker|null $booker
 */
```

#### Relacionamentos
- **`booker()`**: `belongsTo(Booker::class)` - Relacionamento com o booker associado ao usuário

### Payment Model
**Arquivo**: `app/Models/Payment.php`

#### Propriedades Documentadas
```php
/**
 * @property-read string $inferred_status - Status inferido baseado em lógica de negócio
 * @property-read string $status_color - Cor do status para interface
 * @property-read float $due_value_brl - Valor devido em BRL
 */
```

#### Accessors Implementados
- **`getInferredStatusAttribute()`**: Calcula status baseado em regras de negócio
- **`getStatusColorAttribute()`**: Retorna cor baseada no status inferido
- **`getDueValueBrlAttribute()`**: Converte valor devido para BRL

## 🛠️ Correções Implementadas

### 1. Correções PHPDoc
- ✅ Adicionadas propriedades `@property` em User model
- ✅ Adicionadas propriedades `@property-read` em Payment model
- ✅ Documentados relacionamentos com tipos corretos

### 2. Correções Auth::user() em Filament Resources
- ✅ **GigResource.php**: Adicionado type hint `/** @var \App\Models\User $user */`
- ✅ **UserResource.php**: Adicionado type hint `/** @var \App\Models\User $user */`
- ✅ Substituído `auth()->user()` por `Auth::user()` com imports corretos

### 3. Resolução de Erros PHPStan
- ✅ Propriedade `booker_id` reconhecida no User model
- ✅ Accessors `inferred_status`, `status_color`, `due_value_brl` reconhecidos no Payment model
- ✅ Método `Auth::user()` corretamente tipado

## 🔍 Estrutura de Relacionamentos Completa

### Gig (Evento)
- `belongsTo(Artist::class)` - Artista do evento
- `belongsTo(Booker::class)` - Booker responsável
- `hasMany(Payment::class)` - Pagamentos do evento
- `hasMany(GigCost::class)` - Custos do evento
- `hasOne(Settlement::class)` - Acerto financeiro

### User (Usuário)
- `belongsTo(Booker::class)` - Booker associado (se aplicável)

### Payment (Pagamento)
- `belongsTo(Gig::class)` - Evento relacionado
- `belongsTo(User::class, 'confirmed_by')` - Usuário que confirmou

### Booker
- `hasMany(Gig::class)` - Eventos gerenciados
- `hasMany(User::class)` - Usuários associados

### Artist (Artista)
- `hasMany(Gig::class)` - Eventos do artista

## 📚 Documentação Relacionada

- **Plano de Implementação**: `docs/ai_context/plano-implementacao-tasks.md`
- **Relatório de Mapeamento**: `docs/ai_context/relatorio-mapeamento-sistema.md`
- **Arquitetura**: `docs/ai_context/2_architecture.md`
- **DevLog**: `docs/devlog/index.md`

---

**Status**: ✅ Atualizado com todas as correções implementadas  
**Próxima Revisão**: Quando novos relacionamentos forem adicionados