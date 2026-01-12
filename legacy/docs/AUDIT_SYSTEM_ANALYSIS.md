# Sistema de Auditoria de Dados - EventosPro

## Visão Geral

O sistema de auditoria de dados do EventosPro é uma funcionalidade robusta que permite verificar a integridade dos dados de gigs, identificar inconsistências financeiras e aplicar correções automáticas ou manuais. O sistema é composto por três componentes principais:

1. **Comando de Console** (`GigDataAuditCommand.php`)
2. **Controller Web** (`AuditController.php`)
3. **Interface Web** (`data-audit.blade.php`)

---

## 1. Arquitetura do Sistema

### 1.1 Componentes Principais

#### A. Comando de Console (`app/Console/Commands/GigDataAuditCommand.php`)
- **Função**: Executa auditoria em lote via linha de comando
- **Assinatura**: `gig:audit-data {--scan-only} {--auto-fix} {--batch-size=100} {--date-from=} {--date-to=}`
- **Saída**: Relatório JSON em `storage/logs/gig_audit_YYYYMMDD_HHMMSS.json`

#### B. Controller Web (`app/Http/Controllers/AuditController.php`)
- **Função**: Interface web para auditoria e correções
- **Métodos principais**:
  - `dataAudit()`: Exibe página de auditoria
  - `runDataAudit()`: Executa auditoria via web
  - `getAuditIssues()`: Recupera issues do relatório
  - `applyFix()`: Aplica correção individual
  - `applyBulkFix()`: Aplica correções em lote

#### C. Interface Web (`resources/views/audit/data-audit.blade.php`)
- **Função**: Interface visual para configuração e execução de auditorias
- **Recursos**: Formulários, tabelas de resultados, ações de correção

### 1.2 Fluxo de Dados

```
[Interface Web] → [AuditController] → [GigDataAuditCommand] → [Relatório JSON] → [Interface Web]
```

---

## 2. Validações e Regras de Negócio

### 2.1 Validações Implementadas

#### A. Integridade Referencial
- **Verificação**: Existência de artista e booker associados
- **Severidade**: Critical
- **Ação**: Correção manual necessária

#### B. Regras de Status de Pagamento
- **Verificação**: Consistência entre `payment_status` e parcelas confirmadas
- **Casos detectados**:
  - Gigs marcadas como "pago" com parcelas pendentes
  - Gigs com status inconsistente após data do evento
- **Severidade**: Critical/Warning

#### C. Consistência de Comissões
- **Verificação**: Valores de comissão dentro dos limites esperados
- **Regras**: Comissões entre 0% e 100%
- **Severidade**: Warning

#### D. Campos Obrigatórios
- **Verificação**: Presença de dados essenciais
- **Campos verificados**:
  - `cache_value` (deve ser > 0)
  - `currency` (não pode estar vazio)
  - `gig_date` (obrigatório)
- **Severidade**: Critical

#### E. Lógica de Datas
- **Verificação**: Consistência temporal
- **Regras**:
  - `contract_date` não pode ser posterior a `gig_date`
  - Verificação de eventos vencidos vs. status de pagamento
- **Severidade**: Warning/Critical

### 2.2 Categorização de Issues

#### Categorias Identificadas:
1. **`falta_lancamento`**: Gigs sem lançamentos de pagamento
2. **`discrepancia_valores`**: Divergência entre valor contrato e parcelas
3. **`gigs_pago_com_parcelas_abertas`**: Status "pago" com parcelas pendentes
4. **`gigs_vencidas`**: Eventos passados com status inconsistente
5. **`gigs_a_vencer`**: Eventos futuros (informativo)

---

## 3. Sistema de Correções

### 3.1 Correções Automáticas

#### A. Comando de Console com `--auto-fix`
- **Ativação**: Flag `--auto-fix` no comando
- **Escopo**: Issues críticas com valor sugerido
- **Processo**:
  1. Identifica issue crítica
  2. Verifica se há valor sugerido
  3. Aplica correção automaticamente
  4. Registra log da ação

#### B. Correções Interativas (Console)
- **Ativação**: Execução em console sem `--auto-fix`
- **Processo**:
  1. Apresenta issue ao usuário
  2. Solicita confirmação
  3. Aplica correção se confirmada

### 3.2 Correções Manuais (Interface Web)

#### A. Correção Individual
- **Endpoint**: `POST /audit/apply-fix`
- **Campos editáveis**:
  - `artist_payment_status`
  - `booker_payment_status`
  - Campos financeiros básicos
- **Validação**: Whitelist de campos permitidos
- **Transação**: Operação atômica com rollback

#### B. Correção em Lote
- **Endpoint**: `POST /audit/apply-bulk-fix`
- **Campos editáveis**:
  - `artist_fee`, `production_cost`, `travel_cost`
  - `accommodation_cost`, `other_costs`, `total_cost`
  - `artist_name`, `location_event_details`
  - `gig_date`, `contract_number`
- **Processo**:
  1. Validação de cada correção
  2. Transação única para todo o lote
  3. Rollback em caso de erro
  4. Relatório detalhado de sucessos/falhas

---

## 4. Interface Web - Funcionalidades

### 4.1 Configuração de Auditoria

#### Formulário de Configuração:
- **Modo Scan-Only**: Apenas identificação, sem correções
- **Batch Size**: Tamanho do lote (padrão: 100)
- **Filtros de Data**: `date_from` e `date_to`
- **Validação**: CSRF token obrigatório

### 4.2 Visualização de Resultados

#### Estatísticas:
- **Total de Issues**: Contador geral
- **Issues Críticas**: Requerem ação imediata
- **Warnings**: Alertas informativos
- **Issues Corrigidas**: Contador de correções aplicadas

#### Tabela de Issues:
- **Filtros**: Por severidade e tipo
- **Colunas**: Gig ID, Artista, Tipo, Descrição, Severidade, Ações
- **Ações**: Correção individual, seleção para lote

### 4.3 Funcionalidades JavaScript

#### Principais Funções:
- **`runAudit()`**: Executa auditoria via AJAX
- **`loadAuditIssues()`**: Carrega resultados do relatório
- **`applyFix()`**: Aplica correção individual
- **`applyBulkFix()`**: Aplica correções em lote
- **Filtros dinâmicos**: Por severidade e tipo de issue

---

## 5. Rotas e Endpoints

### 5.1 Rotas Principais

```php
// Visualização
Route::get('/audit/data-audit', [AuditController::class, 'dataAudit'])->name('audit.data-audit');

// Execução
Route::post('/audit/run-data-audit', [AuditController::class, 'runDataAudit'])->name('audit.run-data-audit');

// Resultados
Route::get('/audit/get-issues', [AuditController::class, 'getAuditIssues'])->name('audit.get-issues');

// Correções
Route::post('/audit/apply-fix', [AuditController::class, 'applyFix'])->name('audit.apply-fix');
Route::post('/audit/apply-bulk-fix', [AuditController::class, 'applyBulkFix'])->name('audit.apply-bulk-fix');
```

### 5.2 Parâmetros de Requisição

#### `runDataAudit`:
- `scan_only`: boolean
- `batch_size`: integer (padrão: 100)
- `date_from`: date (opcional)
- `date_to`: date (opcional)

#### `applyFix`:
- `gig_id`: integer
- `field`: string (whitelist)
- `new_value`: mixed
- `issue_type`: string

#### `applyBulkFix`:
- `fixes`: array de objetos com gig_id, field, new_value, issue_type

---

## 6. Logs e Relatórios

### 6.1 Estrutura do Relatório JSON

```json
{
  "execution_info": {
    "environment": "console|web",
    "start_time": "2024-01-01 10:00:00",
    "end_time": "2024-01-01 10:05:00",
    "total_gigs_processed": 150,
    "total_issues_found": 25
  },
  "summary": {
    "critical_issues": 5,
    "warning_issues": 15,
    "info_issues": 5,
    "issues_by_type": {
      "discrepancia_valores": 10,
      "gigs_pago_com_parcelas_abertas": 8,
      "falta_lancamento": 7
    }
  },
  "issues": [
    {
      "gig_id": 123,
      "type": "discrepancia_valores",
      "severity": "critical",
      "description": "Divergência de valores detectada",
      "field": "cache_value",
      "current_value": 5000,
      "suggested_value": 5500,
      "gig_info": {
        "artist_name": "Artista Exemplo",
        "booker_name": "Booker Exemplo",
        "gig_date": "2024-01-15"
      }
    }
  ]
}
```

### 6.2 Logs de Sistema

#### Tipos de Log:
- **Info**: Correções aplicadas com sucesso
- **Warning**: Issues críticas em ambiente web
- **Error**: Falhas na aplicação de correções

---

## 7. Considerações para Testes

### 7.1 Cenários de Teste Recomendados

#### A. Validação de Dados:
1. **Gigs sem artista/booker**: Testar integridade referencial
2. **Valores inconsistentes**: Divergência entre contrato e parcelas
3. **Status incorretos**: Gigs "pagas" com parcelas pendentes
4. **Datas inválidas**: contract_date > gig_date

#### B. Sistema de Correções:
1. **Correção individual**: Aplicar fix em campo permitido
2. **Correção em lote**: Múltiplas correções simultâneas
3. **Validação de campos**: Tentar editar campo não permitido
4. **Rollback**: Simular erro durante correção em lote

#### C. Interface Web:
1. **Filtros**: Testar filtros por severidade e tipo
2. **Paginação**: Grandes volumes de issues
3. **AJAX**: Execução de auditoria sem reload
4. **Responsividade**: Interface em diferentes dispositivos

### 7.2 Dados de Teste Sugeridos

#### Criar Gigs com:
- Valores de contrato sem parcelas correspondentes
- Status "pago" com parcelas não confirmadas
- Datas de contrato posteriores à data do evento
- Campos obrigatórios vazios
- Comissões fora dos limites (< 0% ou > 100%)

---

## 8. Melhorias e Ajustes Recomendados

### 8.1 Performance
- **Índices de banco**: Otimizar consultas de auditoria
- **Cache**: Implementar cache para relatórios recentes
- **Paginação**: Melhorar handling de grandes volumes

### 8.2 Funcionalidades
- **Agendamento**: Auditorias automáticas periódicas
- **Notificações**: Alertas para issues críticas
- **Histórico**: Rastreamento de correções aplicadas
- **Exportação**: Relatórios em CSV/Excel

### 8.3 Segurança
- **Permissões**: Controle de acesso por perfil
- **Auditoria**: Log de todas as ações de correção
- **Validação**: Sanitização rigorosa de inputs

---

## 9. Comandos Úteis

### 9.1 Execução via Console

```bash
# Auditoria completa com correções automáticas
php artisan gig:audit-data --auto-fix

# Auditoria apenas para identificação (sem correções)
php artisan gig:audit-data --scan-only

# Auditoria com filtro de datas
php artisan gig:audit-data --date-from=2024-01-01 --date-to=2024-01-31

# Auditoria com batch personalizado
php artisan gig:audit-data --batch-size=50
```

### 9.2 Localização de Arquivos

```
app/Console/Commands/GigDataAuditCommand.php    # Comando principal
app/Http/Controllers/AuditController.php        # Controller web
resources/views/audit/data-audit.blade.php      # Interface web
storage/logs/gig_audit_*.json                   # Relatórios gerados
routes/web.php                                  # Definição de rotas
```

---

## Conclusão

O sistema de auditoria de dados do EventosPro oferece uma solução completa para manutenção da integridade dos dados de gigs. Com capacidades tanto de identificação quanto de correção automática/manual, o sistema garante que inconsistências sejam detectadas e resolvidas de forma eficiente.

A arquitetura modular permite flexibilidade na execução (console vs. web) e escalabilidade para grandes volumes de dados. O sistema de logs e relatórios fornece rastreabilidade completa das operações realizadas.

Para desenvolvimento e testes, recomenda-se focar nos cenários de validação descritos, garantindo que todas as regras de negócio sejam adequadamente testadas e que o sistema de correções funcione de forma confiável em diferentes situações.