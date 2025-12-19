# EventosPro Design System

**Versão:** 1.0.0
**Última Atualização:** 2025-12-19
**Escopo:** Blade/Tailwind (excluindo Filament Admin)

---

## 1. Paleta de Cores Oficial

### 1.1 Cores de Superfície (Surface Colors)

| Token              | Light Mode        | Dark Mode          | Uso                    |
|--------------------|-------------------|--------------------|------------------------|
| `surface-primary`  | `bg-white`        | `bg-gray-800`      | Cards, modais, containers |
| `surface-muted`    | `bg-gray-50`      | `bg-gray-900`      | Backgrounds secundários |
| `border-default`   | `border-gray-200` | `border-gray-700`  | Bordas de cards/seções |
| `border-input`     | `border-gray-300` | `border-gray-600`  | Inputs, selects        |

### 1.2 Cores de Texto

| Token          | Light Mode       | Dark Mode         | Uso              |
|----------------|------------------|-------------------|------------------|
| `text-primary` | `text-gray-900`  | `text-white`      | Títulos, destaque |
| `text-body`    | `text-gray-700`  | `text-gray-300`   | Corpo de texto    |
| `text-muted`   | `text-gray-500`  | `text-gray-400`   | Labels, legendas  |
| `text-subtle`  | `text-gray-400`  | `text-gray-500`   | Placeholders      |

### 1.3 Cores de Ação (Action Colors)

| Token        | Classe Tailwind           | Uso                     |
|--------------|---------------------------|-------------------------|
| `primary`    | `bg-gray-800`/`bg-gray-200` ⚠️ | Botão primary (Breeze default) |
| `accent`     | `bg-primary-600`/`text-primary-500` | Brand accent (violet) |
| `info`       | `bg-blue-600`             | Ações informativas      |

> ⚠️ **Decisão:** O projeto usa o padrão Breeze (gray) para botões primários. Manter por consistência.

### 1.4 Cores de Feedback (Semânticas)

| Estado    | Background (Light/Dark)        | Texto (Light/Dark)           |
|-----------|--------------------------------|------------------------------|
| `success` | `bg-green-100`/`bg-green-900`  | `text-green-800`/`text-green-200` |
| `warning` | `bg-yellow-100`/`bg-yellow-900`| `text-yellow-800`/`text-yellow-200` |
| `danger`  | `bg-red-100`/`bg-red-900`      | `text-red-800`/`text-red-200` |
| `neutral` | `bg-gray-100`/`bg-gray-600`    | `text-gray-700`/`text-gray-400` |

### 1.5 Cores Depreciadas ❌

```diff
# NÃO USAR - Cores fora do padrão encontradas no código
- bg-gray-300 (usar bg-gray-100 ou bg-gray-200)
- text-cyan-* (usar text-blue-* para info)
- focus:ring-red-500 (usar focus:ring-indigo-500, cor padrão de foco)
```

---

## 2. Tipografia

### 2.1 Família de Fonte

```css
font-family: 'Figtree', sans-serif; /* Definido em tailwind.config.js */
```

### 2.2 Escala de Tamanhos

| Token       | Classe Tailwind | Tamanho | Peso         | Uso                          |
|-------------|-----------------|---------|--------------|------------------------------|
| `heading-1` | `text-2xl`      | 1.5rem  | `font-bold`  | Títulos de página            |
| `heading-2` | `text-xl`       | 1.25rem | `font-semibold`| Títulos de seção          |
| `heading-3` | `text-lg`       | 1.125rem| `font-semibold`| Títulos de card           |
| `body`      | `text-base`     | 1rem    | `font-normal`| Parágrafo padrão             |
| `body-sm`   | `text-sm`       | 0.875rem| `font-normal`| Texto secundário, labels     |
| `caption`   | `text-xs`       | 0.75rem | `font-medium`| Badges, tags, metadata       |

### 2.3 Exemplos de Uso

```blade
{{-- Título de Página --}}
<h1 class="text-2xl font-bold text-gray-900 dark:text-white">Dashboard</h1>

{{-- Título de Card --}}
<h3 class="text-lg font-semibold text-gray-900 dark:text-white">Resumo</h3>

{{-- Label de Formulário --}}
<label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nome</label>

{{-- Texto muted --}}
<p class="text-sm text-gray-500 dark:text-gray-400">Descrição auxiliar</p>
```

---

## 3. Espaçamento (Spacing)

### 3.1 Padrão de Padding

| Contexto          | Padding         | Exemplo          |
|-------------------|-----------------|------------------|
| Card container    | `p-4`           | `<div class="p-4">` |
| Card header       | `px-4 py-3`     | Header com border-b |
| Modal body        | `p-6`           | Conteúdo de modal |
| Inputs inline     | `px-3 py-2`     | Campos de formulário |
| Buttons           | `px-4 py-2`     | Botões padrão    |
| Badges/Tags       | `px-2 py-0.5`   | Status badges    |

### 3.2 Gap Padrão

| Contexto                | Gap       |
|-------------------------|-----------|
| Entre cards             | `gap-4`   |
| Entre items de lista    | `gap-2`   |
| Entre seções de página  | `gap-6`   |
| Entre form fields       | `space-y-4` |

---

## 4. Componentes Core

### 4.1 Lista de Componentes Obrigatórios

| Componente           | Arquivo                         | Status     |
|----------------------|---------------------------------|------------|
| `x-card`             | `card.blade.php`                | ✅ Conforme |
| `x-modal`            | `modal.blade.php`               | ✅ Conforme |
| `x-primary-button`   | `primary-button.blade.php`      | ✅ Conforme |
| `x-secondary-button` | `secondary-button.blade.php`    | ✅ Conforme |
| `x-danger-button`    | `danger-button.blade.php`       | ✅ Conforme |
| `x-text-input`       | `text-input.blade.php`          | ⚠️ Duplicado |
| `x-stat-card`        | `stat-card.blade.php`           | ✅ Golden Template |
| `x-status-badge`     | `status-badge.blade.php`        | ✅ Conforme |
| `x-progress-bar`     | `progress-bar.blade.php`        | ✅ Conforme |

### 4.2 Componente de Formulário (Depreciação)

```diff
# Unificar componentes de input:
+ USAR: x-form.input (resources/views/components/form/input.blade.php)
- DEPRECIAR: x-text-input (resources/views/components/text-input.blade.php)
```

---

## 5. Guia de Sintaxe Blade

### 5.1 Estrutura Obrigatória de Componente (Golden Template)

```blade
{{-- resources/views/components/example-card.blade.php --}}

{{-- 1. SEMPRE começar com @props --}}
@props([
    'title',              {{-- Props obrigatórias SEM default --}}
    'subtitle' => '',     {{-- Props opcionais COM default --}}
    'icon' => null,       {{-- Use null para props condicionais --}}
    'color' => 'primary', {{-- Use strings para enums --}}
])

{{-- 2. Lógica PHP para configuração de classes --}}
@php
    // PADRÃO: Use arrays para mapear variantes
    $colorClasses = [
        'primary' => 'text-blue-600 dark:text-blue-400',
        'success' => 'text-green-600 dark:text-green-400',
        'warning' => 'text-yellow-600 dark:text-yellow-400',
        'danger'  => 'text-red-600 dark:text-red-400',
    ];
    $colorClass = $colorClasses[$color] ?? $colorClasses['primary'];
@endphp

{{-- 3. Root element SEMPRE com $attributes->merge() --}}
<div {{ $attributes->merge(['class' => 'bg-white dark:bg-gray-800 rounded-lg shadow p-4']) }}>
    {{-- 4. Conteúdo interno --}}
    <div class="flex items-center gap-3">
        @if($icon)
            <i class="{{ $icon }} {{ $colorClass }} text-xl"></i>
        @endif
        <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $title }}</h3>
            @if($subtitle)
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $subtitle }}</p>
            @endif
        </div>
    </div>
    
    {{-- 5. Use {{ $slot }} para conteúdo flexível --}}
    @if($slot->isNotEmpty())
        <div class="mt-4">
            {{ $slot }}
        </div>
    @endif
</div>
```

### 5.2 Regras de Ouro

| Regra | Descrição |
|-------|-----------|
| ✅ Sempre usar `@props` | Documentação automática e validação |
| ✅ Usar `$attributes->merge()` | Permite passar classes extras |
| ✅ Mapear variantes com arrays | Evita switch/if aninhados |
| ✅ Dark mode em TODAS as cores | `text-*` + `dark:text-*` |
| ✅ Extrair JS para arquivos separados | Max 50 linhas de JS inline |
| ❌ NÃO misturar Bootstrap/Tailwind | `me-2` → `mr-2`, `mb-0` → `mb-0` (ok) |
| ❌ NÃO usar cores hardcoded | `#7c3aed` → `text-primary-600` |

### 5.3 Como Passar Classes Extras

```blade
{{-- Uso correto: classes extras via $attributes --}}
<x-card class="mt-6 border-2">
    Conteúdo
</x-card>

{{-- O merge adiciona as classes: --}}
{{-- Resultado: class="bg-white dark:bg-gray-800 rounded-lg shadow mt-6 border-2" --}}
```

---

## 6. Plano de Refatoração

### 6.1 Top 3 Componentes Violadores

#### 🔴 1. `cost-reimbursement-inline.blade.php` (23KB, 580+ linhas)

**Violações:**
- JavaScript inline excessivo (400+ linhas)
- Classes Bootstrap (`mb-0`, `me-2`) misturadas
- Repetição de estilos de botão

**Ação Requerida:**
```diff
+ Extrair JavaScript para resources/js/components/cost-reimbursement.js
+ Substituir classes Bootstrap por Tailwind
+ Usar x-primary-button/x-secondary-button em vez de <button> inline
```

---

#### 🟡 2. `settlement-workflow-actions.blade.php` (22KB, 500+ linhas)

**Violações:**
- Botões com classes hardcoded (`bg-blue-500`, `bg-green-500`)
- Modais inline em vez de usar `x-modal`
- Classes `mb-0` (Bootstrap style)

**Ação Requerida:**
```diff
+ Extrair modais para componentes separados
+ Criar x-action-button com prop de color
+ Padronizar cores de botões via configuração
```

---

#### 🟡 3. `export-filter.blade.php` (9.8KB)

**Violações:**
- Modal inline em vez de `x-modal`
- `focus:ring-red-500` fora do padrão (deveria ser indigo)
- Cores inconsistentes (`bg-red-100`, `bg-red-600` para ação exportar)

**Ação Requerida:**
```diff
+ Usar x-modal para o dialog de filtro
+ Padronizar focus:ring para indigo
+ Documentar se red é intencional para "exportar/destruir"
```

---

## 7. Checklist de Conformidade

Use esta checklist ao criar novos componentes:

- [ ] `@props` declarado no topo
- [ ] Todos os props têm valores default ou são claramente obrigatórios
- [ ] Root element usa `$attributes->merge(['class' => 'base classes'])`
- [ ] Dark mode classes em todos os elementos visuais
- [ ] Zero classes Bootstrap (usar apenas Tailwind)
- [ ] JavaScript < 50 linhas (ou extraído para arquivo .js)
- [ ] Cores usando tokens do Design System
- [ ] Tipografia usando classes padronizadas

---

**Mantido por:** Equipe de Frontend EventosPro
**Próxima Revisão:** 2025-03
