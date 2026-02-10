# Guia de Testes e Cobertura - EventosPro

## 📋 Visão Geral

Este documento descreve a configuração de testes, cobertura de código e pipeline de CI/CD do projeto EventosPro.

## 🧪 Executando Testes

### Testes Locais

**⚠️ IMPORTANTE**: Este projeto usa Laravel Sail. SEMPRE execute comandos através do Sail:

## 📐 Padrões de Comandos Estabelecidos

### ✅ **Usar sempre Sail (dentro do container):**
- `./vendor/bin/sail test` ao invés de `php artisan test`
- `./vendor/bin/sail artisan` ao invés de `php artisan`
- `./vendor/bin/sail composer` ao invés de `composer`
- `./vendor/bin/sail npm` ao invés de `npm`

### ✅ **Flags e Parâmetros:**
- **NÃO usar** `--verbose` para saída mais limpa
- Usar apenas flags essenciais para o funcionamento
- Manter comandos simples e diretos

### Exemplos Padronizados:

```bash
# ✅ CORRETO - Executar todos os testes
./vendor/bin/sail test

# ✅ CORRETO - Testes com cobertura
./vendor/bin/sail test --coverage

# ✅ CORRETO - Testes específicos
./vendor/bin/sail test tests/Unit/AuditServiceTest.php

# ✅ CORRETO - Comandos Artisan
./vendor/bin/sail artisan migrate

# ✅ CORRETO - Composer
./vendor/bin/sail composer install

# ❌ EVITAR - Comandos fora do container
php artisan test --verbose
composer install --verbose
```

```bash
# Executar todos os testes
./vendor/bin/sail test

# Executar testes com cobertura
./vendor/bin/sail test --coverage

# Executar testes com cobertura mínima (usando script)
./vendor/bin/sail bash -c "./run-tests-coverage.sh 80"

# Executar apenas testes unitários
./vendor/bin/sail test tests/Unit

# Executar apenas testes de feature
./vendor/bin/sail test tests/Feature
```

### Testes Específicos

```bash
# Testar services específicos
./vendor/bin/sail test tests/Unit/Services/

# Testar um service específico
./vendor/bin/sail test tests/Unit/Services/AuditServiceTest.php

# Testar com filtros específicos
./vendor/bin/sail test --filter=testCalculateGigAuditData

# Testar com grupos específicos
./vendor/bin/sail test --group=services
```

## 📊 Cobertura de Código

### Metas de Cobertura

- **Cobertura Geral**: Mínimo 80%, Meta 90%
- **Services Críticos**: Mínimo 95%
  - AuditService
  - ExchangeRateService
  - ArtistFinancialsService

### Relatórios de Cobertura

```bash
# Gerar relatório HTML
./vendor/bin/sail test --coverage --coverage-html=coverage-report

# Visualizar relatório (o arquivo será criado no host)
open coverage-report/index.html

# Gerar relatório com limite mínimo
./vendor/bin/sail test --coverage --min=80
```

### Arquivos Excluídos da Cobertura

- `app/Console/` - Comandos Artisan
- `app/Exceptions/` - Handlers de exceção
- `app/Http/Middleware/` - Middlewares
- `app/Providers/RouteServiceProvider.php` - Provider de rotas

## 🚀 Pipeline CI/CD

### GitHub Actions

O pipeline é executado automaticamente em:
- Push para branches `main` e `develop`
- Pull Requests para `main` e `develop`

### Jobs do Pipeline

#### 1. Test Job
- Configura ambiente PHP 8.2
- Instala dependências
- Executa migrações
- Roda testes com cobertura mínima de 80%
- Gera relatório de cobertura

#### 2. Code Quality Job
- Verifica estilo de código (PHP CS Fixer)
- Análise estática (PHPStan)

### Configuração do Ambiente

```yaml
# .github/workflows/ci.yml
services:
  mysql:
    image: mysql:8.0
    env:
      MYSQL_ROOT_PASSWORD: password
      MYSQL_DATABASE: testing
```

## 📁 Estrutura de Testes

```
tests/
├── Feature/           # Testes de integração
│   ├── Auth/
│   ├── ExampleTest.php
│   └── ProfileTest.php
├── Unit/              # Testes unitários
│   └── Services/      # Testes dos services
│       ├── AuditServiceTest.php
│       ├── ArtistFinancialsServiceTest.php
│       └── ExchangeRateServiceTest.php
└── TestCase.php       # Classe base para testes
```

## 🛠️ Configuração

### PHPUnit (phpunit.xml)

```xml
<coverage>
    <report>
        <html outputDirectory="coverage-report" lowUpperBound="50" highLowerBound="80"/>
        <text outputFile="php://stdout" showUncoveredFiles="false"/>
    </report>
</coverage>
```

### Configuração de Cobertura (.coverage-config.json)

```json
{
  "coverage": {
    "minimum_percentage": 80,
    "target_percentage": 90,
    "critical_services": {
      "minimum_percentage": 95
    }
  }
}
```

## 🔧 Scripts Úteis

### run-tests-coverage.sh

Script para executar testes com verificação de cobertura:

```bash
# Usar cobertura padrão (80%)
./run-tests-coverage.sh

# Definir cobertura específica
./run-tests-coverage.sh 85
```

## 📈 Monitoramento

### Métricas Importantes

1. **Cobertura de Linha**: Percentual de linhas executadas
2. **Cobertura de Função**: Percentual de funções testadas
3. **Cobertura de Branch**: Percentual de caminhos testados

### Alertas

- ❌ Pipeline falha se cobertura < 80%
- ⚠️ Aviso se cobertura de services críticos < 95%
- 📊 Relatórios são gerados a cada execução

## 🎯 Boas Práticas

### Escrevendo Testes

1. **Nomeação Clara**: Use nomes descritivos para testes
2. **Arrange-Act-Assert**: Organize testes em seções claras
3. **Isolamento**: Cada teste deve ser independente
4. **Cobertura Significativa**: Teste cenários reais, não apenas cobertura

### Mantendo Qualidade

1. **Testes Primeiro**: Escreva testes antes de implementar features
2. **Refatoração Segura**: Use testes para validar mudanças
3. **Revisão de Código**: Inclua testes em code reviews
4. **Monitoramento Contínuo**: Acompanhe métricas de cobertura

## 🚨 Troubleshooting

### Problemas Comuns

#### Testes Falhando no CI
```bash
# Verificar localmente
./vendor/bin/sail test --env=testing

# Limpar cache
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan cache:clear
```

#### Cobertura Baixa
```bash
# Identificar arquivos sem cobertura
./vendor/bin/sail test --coverage --coverage-text

# Gerar relatório detalhado
./vendor/bin/sail test --coverage --coverage-html=coverage-report
```

#### Problemas de Banco
```bash
# Recriar banco de testes
./vendor/bin/sail artisan migrate:fresh --env=testing
./vendor/bin/sail artisan db:seed --env=testing
```

## 📞 Suporte

Para dúvidas sobre testes e cobertura:
1. Consulte este documento
2. Verifique logs do CI/CD
3. Execute testes localmente para debug
4. Consulte a equipe de desenvolvimento