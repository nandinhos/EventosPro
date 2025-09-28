# GigDataAuditCommand - Guia de Uso

**Data de Atualização: 27/09/2025**  
**Status: Comando corrigido e funcional**

## 📋 Visão Geral

O `GigDataAuditCommand` é um comando Laravel para auditoria e correção automática de dados das gigs. O comando foi corrigido para funcionar tanto em ambiente de terminal quanto via interface web.

## 🔧 Correções Implementadas

### Problema Original
- **Erro**: `Undefined constant "STDIN"` quando executado via `Artisan::call()`
- **Causa**: Tentativa de ler entrada do usuário em ambiente web sem STDIN

### Solução Implementada
1. **Detecção de Ambiente**: Método `isRunningInConsole()` detecta se está rodando em terminal ou web
2. **Fallback Automático**: Bypass de confirmações quando não há STDIN disponível
3. **Logging Aprimorado**: Relatórios detalhados para execução web

## 🚀 Como Usar

### 1. Via Terminal (Linha de Comando)

```bash
# Apenas escaneamento (requer confirmação)
./vendor/bin/sail artisan gig:audit-data --scan-only

# Escaneamento com tamanho de lote específico
./vendor/bin/sail artisan gig:audit-data --scan-only --batch-size=10

# Correção automática (requer confirmação)
./vendor/bin/sail artisan gig:audit-data --auto-fix

# Com filtros de data
./vendor/bin/sail artisan gig:audit-data --scan-only --date-from=2025-01-01 --date-to=2025-12-31
```

### 2. Via Interface Web (Artisan::call)

```php
// No AuditController ou qualquer controller
use Illuminate\Support\Facades\Artisan;

// Apenas escaneamento
$exitCode = Artisan::call('gig:audit-data', [
    '--scan-only' => true,
    '--batch-size' => 10
]);

// Correção automática (recomendado para web)
$exitCode = Artisan::call('gig:audit-data', [
    '--auto-fix' => true,
    '--batch-size' => 5
]);

// Capturar output
$output = Artisan::output();
```

## ⚙️ Parâmetros Disponíveis

| Parâmetro | Tipo | Descrição | Padrão |
|-----------|------|-----------|---------|
| `--scan-only` | Flag | Apenas escaneia, não aplica correções | false |
| `--auto-fix` | Flag | Aplica correções automaticamente | false |
| `--batch-size` | Integer | Tamanho do lote para processamento | 100 |
| `--date-from` | Date | Data inicial para filtro (Y-m-d) | null |
| `--date-to` | Date | Data final para filtro (Y-m-d) | null |

## 🔍 Tipos de Auditoria

O comando verifica os seguintes aspectos:

1. **Integridade Referencial**
   - Relacionamentos válidos entre gigs e entidades relacionadas
   - Verificação de foreign keys

2. **Regras de Status de Pagamento**
   - Consistência entre status de pagamento e valores
   - Validação de regras de negócio

3. **Consistência de Comissões**
   - Cálculos de comissão corretos
   - Valores de agência vs. artista

4. **Campos Obrigatórios**
   - Verificação de campos essenciais
   - Validação de dados mínimos

5. **Lógica de Datas**
   - Consistência entre datas de evento
   - Validação de períodos

## 📊 Relatórios Gerados

### Localização
- **Arquivo**: `/storage/logs/gig_audit_YYYY-MM-DD_HH-MM-SS.json`
- **Formato**: JSON estruturado

### Estrutura do Relatório
```json
{
  "execution_info": {
    "start_time": "2025-09-27 20:53:13",
    "end_time": "2025-09-27 20:53:15",
    "environment": "Console (Terminal)",
    "total_gigs": 100,
    "batch_size": 5
  },
  "summary": {
    "total_issues": 12,
    "fixes_applied": 0,
    "errors": 0
  },
  "issues_by_type": {
    "payment_status_rule": {
      "count": 12,
      "severity": "warning",
      "gigs": [1, 5, 8, ...]
    }
  },
  "detailed_issues": [
    {
      "gig_id": 1,
      "issue_type": "payment_status_rule",
      "severity": "warning",
      "description": "Payment status inconsistency",
      "current_value": "pending",
      "expected_value": "paid"
    }
  ]
}
```

## 🎯 Comportamento por Ambiente

### Terminal (Console)
- ✅ Solicita confirmação do usuário
- ✅ Exibe progress bar em tempo real
- ✅ Permite interrupção via Ctrl+C
- ✅ Output colorido e formatado

### Web (Artisan::call)
- ✅ Execução automática sem confirmações
- ✅ Logging detalhado para debug
- ✅ Relatório JSON sempre gerado
- ✅ Exit code 0 para sucesso

## ⚠️ Recomendações de Uso

### Para Desenvolvimento
```bash
# Teste rápido com poucos registros
./vendor/bin/sail artisan gig:audit-data --scan-only --batch-size=5
```

### Para Produção
```php
// Via interface web com auto-fix
Artisan::call('gig:audit-data', [
    '--auto-fix' => true,
    '--batch-size' => 50  // Lote maior para eficiência
]);
```

### Para Análise Específica
```bash
# Período específico
./vendor/bin/sail artisan gig:audit-data --scan-only --date-from=2025-09-01 --date-to=2025-09-30
```

## 🔧 Troubleshooting

### Problemas Comuns

1. **Erro de Conexão com Banco**
   ```
   SQLSTATE[HY000] [2002] php_network_getaddresses: getaddrinfo for mysql failed
   ```
   **Solução**: Use `./vendor/bin/sail artisan` em vez de `php artisan`

2. **Timeout em Lotes Grandes**
   **Solução**: Reduza o `--batch-size` para 10-20 registros

3. **Memória Insuficiente**
   **Solução**: Processe em lotes menores ou aumente `memory_limit`

### Logs de Debug
- **Laravel Log**: `storage/logs/laravel.log`
- **Audit Log**: `storage/logs/gig_audit_*.json`

## 📈 Performance

### Benchmarks Típicos
- **100 gigs**: ~2-3 segundos
- **1000 gigs**: ~15-20 segundos
- **Batch size ótimo**: 50-100 registros

### Otimizações
- Use `--batch-size` apropriado para seu ambiente
- Execute durante horários de baixo tráfego
- Monitore uso de memória para lotes grandes

## ✅ Testes de Validação

### Teste Terminal
```bash
./vendor/bin/sail artisan gig:audit-data --scan-only --batch-size=5
```

### Teste Web
```php
$exitCode = Artisan::call('gig:audit-data', ['--auto-fix' => true, '--batch-size' => 3]);
echo "Exit Code: $exitCode\n";
echo Artisan::output();
```

### Resultado Esperado
- Exit code: 0 (sucesso)
- Relatório JSON gerado
- Sem erros de STDIN
- Output formatado corretamente

---

**Última atualização**: 27/09/2025  
**Status**: ✅ Funcional em todos os ambientes  
**Próxima revisão**: Conforme necessário