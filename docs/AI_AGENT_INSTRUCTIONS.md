# Instruções para Agentes de IA - EventosPro

## 🤖 Configuração Obrigatória para Agentes de IA

**REGRA FUNDAMENTAL**: Este projeto utiliza Laravel Sail como ambiente de desenvolvimento. Agentes de IA DEVEM sempre fornecer comandos que utilizem o Laravel Sail.

## ⚠️ Comandos Obrigatórios

### ❌ NUNCA Use Estes Comandos:

```bash
# COMANDOS PROIBIDOS - NÃO USE:
php artisan [qualquer-comando]
composer [qualquer-comando]
npm [qualquer-comando]
phpunit
vendor/bin/phpunit
vendor/bin/pint
vendor/bin/phpstan
./run-tests-coverage.sh
```

### ✅ SEMPRE Use Estes Comandos:

```bash
# COMANDOS CORRETOS - USE SEMPRE:
sail artisan [qualquer-comando]
sail composer [qualquer-comando]
sail npm [qualquer-comando]
sail artisan test
sail bin pint
sail bin phpstan
sail bash -c "./run-tests-coverage.sh"
```

## 📋 Mapeamento de Comandos

| ❌ Comando Incorreto | ✅ Comando Correto |
|---------------------|--------------------|
| `php artisan migrate` | `sail artisan migrate` |
| `php artisan test` | `sail artisan test` |
| `composer install` | `sail composer install` |
| `npm install` | `sail npm install` |
| `npm run dev` | `sail npm run dev` |
| `phpunit` | `sail artisan test` |
| `./run-tests-coverage.sh 80` | `sail bash -c "./run-tests-coverage.sh 80"` |
| `php artisan tinker` | `sail artisan tinker` |
| `php artisan make:controller` | `sail artisan make:controller` |
| `php artisan route:list` | `sail artisan route:list` |
| `composer require package` | `sail composer require package` |
| `npm run build` | `sail npm run build` |

## 🔧 Comandos Específicos por Categoria

### Desenvolvimento
```bash
# Artisan commands
sail artisan make:controller ExampleController
sail artisan make:model Example
sail artisan make:migration create_examples_table
sail artisan route:list
sail artisan config:cache

# Composer
sail composer install
sail composer require vendor/package
sail composer update
sail composer dump-autoload

# NPM/Frontend
sail npm install
sail npm run dev
sail npm run build
sail npm run watch
```

### Testes
```bash
# Testes básicos
sail artisan test
sail artisan test --coverage
sail artisan test tests/Unit/
sail artisan test tests/Feature/

# Testes específicos
sail artisan test tests/Unit/Services/ExchangeRateServiceTest.php
sail artisan test --filter=testMethodName
sail artisan test --group=services

# Cobertura
sail bash -c "./run-tests-coverage.sh 80"
sail artisan test --coverage --min=80
```

### Database
```bash
# Migrações
sail artisan migrate
sail artisan migrate:fresh
sail artisan migrate:fresh --seed
sail artisan migrate:rollback
sail artisan migrate:status

# Seeders
sail artisan db:seed
sail artisan db:seed --class=UserSeeder
```

### Cache e Otimização
```bash
# Limpeza de cache
sail artisan cache:clear
sail artisan config:clear
sail artisan route:clear
sail artisan view:clear
sail artisan optimize:clear

# Otimização
sail artisan optimize
sail artisan config:cache
sail artisan route:cache
sail artisan view:cache
```

### Debug e Análise
```bash
# Debug
sail artisan tinker
sail logs
sail shell
sail root-shell

# Qualidade de código
sail bin pint
sail bin phpstan analyse
```

## 🎯 Cenários Específicos

### Criando um Novo Service
```bash
# 1. Criar o service
sail artisan make:class Services/ExampleService

# 2. Criar teste unitário
sail artisan make:test Unit/Services/ExampleServiceTest --unit

# 3. Executar testes
sail artisan test tests/Unit/Services/ExampleServiceTest.php
```

### Debugging de Services
```bash
# Usar Tinker para debug
sail artisan tinker

# Dentro do Tinker:
# > $service = app(App\Services\ExchangeRateService::class)
# > $service->getExchangeRate('USD')
```

### Análise de Cobertura
```bash
# Executar com cobertura
sail artisan test --coverage

# Gerar relatório HTML
sail artisan test --coverage --coverage-html=coverage-report

# Script personalizado
sail bash -c "./run-tests-coverage.sh 80"
```

## 🚨 Verificações Automáticas

### Antes de Sugerir Comandos

1. **Verificar se o comando usa `sail`**
2. **Confirmar que não há comandos diretos PHP/Composer/NPM**
3. **Validar se scripts personalizados são executados via `sail bash -c`**

### Padrões de Resposta

Quando um agente de IA sugerir comandos, deve sempre:

```markdown
**Execute os seguintes comandos com Laravel Sail:**

```bash
sail artisan test
sail composer install
```

**Importante**: Certifique-se de que os containers estão rodando com `sail up -d`.
```

## 🏗️ Services Disponíveis

O projeto EventosPro possui os seguintes services documentados em `SERVICES_API.md`:

### Services Principais
- **AuditService**: Auditoria e validação de integridade de gigs
- **ArtistFinancialsService**: Métricas financeiras de artistas
- **ExchangeRateService**: Conversão de moedas e taxas de câmbio
- **GigFinancialCalculatorService**: Cálculos financeiros específicos de gigs
- **CommissionPaymentValidationService**: Validação de pagamentos de comissões
- **UserManagementService**: Gerenciamento de usuários e permissões
- **DashboardService**: Agregação de dados para dashboard
- **FinancialProjectionService**: Projeções financeiras e fluxo de caixa
- **FinancialReportService**: Relatórios financeiros detalhados
- **BookerFinancialsService**: Métricas financeiras de bookers

### Testando Services
```bash
# Testar todos os services
sail artisan test tests/Unit/Services/

# Testar service específico
sail artisan test tests/Unit/Services/ExchangeRateServiceTest.php

# Debug via Tinker
sail artisan tinker
# > app(App\Services\ExchangeRateService::class)->getExchangeRate('USD')
```

### Criando Novos Services
```bash
# 1. Criar service
sail artisan make:class Services/NovoService

# 2. Criar teste
sail artisan make:test Unit/Services/NovoServiceTest --unit

# 3. Documentar em SERVICES_API.md
# 4. Atualizar esta lista
```

## 📚 Recursos de Referência

- **[Comandos Laravel Sail Completos](LARAVEL_SAIL_COMMANDS.md)**
- **[Configuração de Testes](TESTING.md)**
- **[APIs dos Services](SERVICES_API.md)**
- **[Documentação Oficial Laravel Sail](https://laravel.com/docs/sail)**

## 🔍 Validação de Comandos

### Checklist para Agentes de IA:

- [ ] Comando inicia com `sail`?
- [ ] Não há uso direto de `php`, `composer`, `npm`?
- [ ] Scripts personalizados usam `sail bash -c`?
- [ ] Instruções incluem verificação de containers (`sail ps`)?
- [ ] Documentação referencia uso do Sail?

## 🎯 Exemplos de Respostas Corretas

### ✅ Resposta Correta:
```markdown
Para executar os testes do ExchangeRateService, use:

```bash
sail artisan test tests/Unit/Services/ExchangeRateServiceTest.php
```

Certifique-se de que os containers estão rodando com `sail up -d`.
```

### ❌ Resposta Incorreta:
```markdown
Para executar os testes do ExchangeRateService, use:

```bash
php artisan test tests/Unit/Services/ExchangeRateServiceTest.php
```
```

---

**Lembre-se**: A consistência no uso do Laravel Sail é fundamental para manter o ambiente de desenvolvimento estável e reproduzível para todos os desenvolvedores e agentes de IA.