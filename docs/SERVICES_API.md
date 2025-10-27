# Documentação das APIs dos Services - EventosPro

## 📋 Visão Geral

Este documento descreve as APIs dos services principais do sistema EventosPro, incluindo métodos, parâmetros, retornos e exemplos de uso.

## 🏗️ Arquitetura dos Services

Os services seguem o padrão de injeção de dependência e são registrados no container do Laravel. Cada service tem responsabilidades específicas:

- **AuditService**: Auditoria e análise financeira de gigs
- **ArtistFinancialsService**: Métricas financeiras de artistas
- **ExchangeRateService**: Conversão de moedas e taxas de câmbio
- **GigFinancialCalculatorService**: Cálculos financeiros de gigs
- **UserManagementService**: Gerenciamento de usuários
- **DashboardService**: Dados para dashboard
- **FinancialReportService**: Relatórios financeiros
- **FinancialProjectionService**: Projeções financeiras
- **BookerFinancialsService**: Métricas financeiras de bookers

---

## 🔍 AuditService

### Descrição
Service responsável por auditoria e análise financeira de gigs, incluindo cálculos de divergências, validações de integridade e geração de relatórios consolidados.

### Dependências
- `GigFinancialCalculatorService`

### Métodos Principais

#### `calculateGigAuditData(Gig $gig): array`

**Descrição**: Calcula dados de auditoria para um gig específico.

**Parâmetros**:
- `$gig` (Gig): Instância do modelo Gig

**Retorno**:
```php
[
    'gig_id' => int,
    'contract_value_brl' => float,
    'total_paid_brl' => float,
    'total_pending_brl' => float,
    'divergence_amount_brl' => float,
    'divergence_percentage' => float,
    'has_divergence' => bool,
    'divergence_classification' => string, // 'low', 'medium', 'high'
    'payment_status' => string,
    'overdue_payments' => int,
    'upcoming_payments' => int,
    'currency_inconsistencies' => array,
    'observations' => array
]
```

**Exemplo de Uso**:
```php
$auditService = app(AuditService::class);
$gig = Gig::find(1);
$auditData = $auditService->calculateGigAuditData($gig);

if ($auditData['has_divergence']) {
    Log::warning('Divergência encontrada', $auditData);
}
```

#### `calculateBulkAuditData(Collection $gigs): array`

**Descrição**: Calcula dados de auditoria para múltiplos gigs.

**Parâmetros**:
- `$gigs` (Collection): Coleção de gigs

**Retorno**:
```php
[
    'total_gigs' => int,
    'gigs_with_divergence' => int,
    'total_divergence_amount_brl' => float,
    'average_divergence_percentage' => float,
    'gigs_data' => array // Array de dados individuais
]
```

#### `validateGigIntegrity(Gig $gig): array`

**Descrição**: Valida a integridade dos dados financeiros de um gig.

**Retorno**:
```php
[
    'is_valid' => bool,
    'errors' => array,
    'warnings' => array
]
```

#### `generateConsolidatedReport(Collection $gigs): string`

**Descrição**: Gera relatório consolidado de auditoria.

**Retorno**: String com relatório formatado

---

## 💰 ArtistFinancialsService

### Descrição
Service para cálculo de métricas financeiras de artistas, incluindo cachês recebidos e pendentes.

### Dependências
- `GigFinancialCalculatorService`

### Métodos Principais

#### `getFinancialMetrics(Artist $artist, ?Collection $gigs = null): array`

**Descrição**: Calcula métricas financeiras de um artista.

**Parâmetros**:
- `$artist` (Artist): Instância do modelo Artist
- `$gigs` (Collection, opcional): Coleção de gigs pré-filtrada

**Retorno**:
```php
[
    'total_gigs' => int,           // Quantidade total de gigs do artista
    'cache_received_brl' => float, // Valor líquido já recebido pelo artista em BRL
    'cache_pending_brl' => float,  // Valor líquido pendente de pagamento em BRL
    'totalGrossFee' => float       // Valor bruto total de todas as gigs em BRL
]
```

**Exemplo de Uso**:
```php
$financialsService = app(ArtistFinancialsService::class);
$artist = Artist::find(1);
$metrics = $financialsService->getFinancialMetrics($artist);

echo "Total recebido: R$ " . number_format($metrics['cache_received_brl'], 2);
```

---

## ✅ CommissionPaymentValidationService

### Descrição
Service responsável por validar se é possível realizar pagamentos de comissões para bookers e artistas, considerando regras de negócio como datas de eventos e exceções autorizadas.

### Métodos Principais

#### `validateBookerCommissionPayment(Gig $gig, bool $allowExceptions = false): array`

**Descrição**: Valida se é possível pagar comissão para um booker em um evento específico.

**Parâmetros**:
- `$gig` (Gig): Instância do modelo Gig
- `$allowExceptions` (bool): Se deve permitir exceções para eventos futuros

**Retorno**:
```php
[
    'valid' => bool,     // Se o pagamento é válido
    'message' => string  // Mensagem explicativa
]
```

**Regras de Validação**:
- Eventos já realizados: sempre válidos
- Eventos futuros: inválidos por padrão
- Eventos futuros com exceção autorizada: válidos se `$allowExceptions = true`

**Exemplo de Uso**:
```php
$validationService = app(CommissionPaymentValidationService::class);
$gig = Gig::find(1);
$result = $validationService->validateBookerCommissionPayment($gig, true);

if ($result['valid']) {
    // Proceder com o pagamento
    echo "Pagamento autorizado: " . $result['message'];
} else {
    // Bloquear pagamento
    echo "Pagamento bloqueado: " . $result['message'];
}
```

#### `validateArtistPayment(Gig $gig, bool $allowExceptions = false): array`

**Descrição**: Valida se é possível pagar cachê para um artista (mesma lógica do booker).

**Parâmetros**: Idênticos ao método anterior
**Retorno**: Idêntico ao método anterior

#### `validateBatchPayment($gigs, bool $allowExceptions = false): array`

**Descrição**: Valida múltiplos eventos para pagamento em lote.

**Parâmetros**:
- `$gigs` (Collection|array): Coleção ou array de gigs
- `$allowExceptions` (bool): Se deve permitir exceções

**Retorno**:
```php
[
    'valid_gigs' => Collection,    // Gigs válidos para pagamento
    'invalid_gigs' => Collection,  // Gigs inválidos
    'errors' => array             // Lista de erros por gig
]
```

**Exemplo de Uso**:
```php
$gigs = Gig::whereIn('id', [1, 2, 3])->get();
$batchResult = $validationService->validateBatchPayment($gigs, true);

echo "Válidos: " . $batchResult['valid_gigs']->count();
echo "Inválidos: " . $batchResult['invalid_gigs']->count();

foreach ($batchResult['errors'] as $error) {
    Log::warning($error);
}
```

#### `createPaymentException(Gig $gig, string $reason, string $authorizedBy): bool`

**Descrição**: Cria uma exceção autorizada para pagamento antecipado de evento futuro.

**Parâmetros**:
- `$gig` (Gig): Evento para criar exceção
- `$reason` (string): Motivo da exceção
- `$authorizedBy` (string): Quem autorizou a exceção

**Retorno**: `true` se a exceção foi criada com sucesso, `false` caso contrário

**Exemplo de Uso**:
```php
$success = $validationService->createPaymentException(
    $gig, 
    'Pagamento antecipado solicitado pelo cliente', 
    'João Silva - Gerente'
);

if ($success) {
    echo "Exceção criada com sucesso";
}
```

### Detecção de Exceções

O service detecta exceções autorizadas através das seguintes palavras-chave nas notas do settlement:
- "exceção" ou "excecao"
- "antecipado"
- "autorizado"

---

## 👤 UserManagementService

### Descrição
Service responsável pelo gerenciamento completo de usuários, incluindo criação, atualização, remoção e associação com perfis de Booker. Garante atomicidade das operações através de transações de banco de dados.

### Dependências
- `User` (Model)
- `Booker` (Model)
- `DB` (Facades)
- `Hash` (Facades)
- `Log` (Facades)

### Métodos Principais

#### `createUser(array $userData): User`

**Descrição**: Cria um novo usuário e, opcionalmente, associa ou cria um perfil de Booker.

**Parâmetros**:
- `$userData` (array): Dados validados do usuário
  - `name` (string): Nome do usuário
  - `email` (string): Email do usuário
  - `password` (string): Senha do usuário
  - `is_booker` (bool, opcional): Se o usuário deve ser um booker
  - `booker_creation_type` (string, opcional): 'new' ou 'existing'
  - `booker_name` (string, opcional): Nome do novo booker
  - `existing_booker_id` (int, opcional): ID do booker existente
  - `default_commission_rate` (float, opcional): Taxa de comissão padrão
  - `contact_info` (string, opcional): Informações de contato

**Retorno**: Instância do usuário criado

**Exceções**: `\Exception` em caso de erro na criação

**Exemplo de Uso**:
```php
$userService = app(UserManagementService::class);

// Criar usuário simples
$userData = [
    'name' => 'João Silva',
    'email' => 'joao@example.com',
    'password' => 'senha123'
];
$user = $userService->createUser($userData);

// Criar usuário com novo booker
$userDataWithBooker = [
    'name' => 'Maria Santos',
    'email' => 'maria@example.com',
    'password' => 'senha123',
    'is_booker' => true,
    'booker_creation_type' => 'new',
    'booker_name' => 'MARIA SANTOS PRODUÇÕES',
    'default_commission_rate' => 15.0,
    'contact_info' => 'maria@producoes.com'
];
$userWithBooker = $userService->createUser($userDataWithBooker);
```

#### `updateUser(User $user, array $userData): User`

**Descrição**: Atualiza um usuário existente e gerencia seu perfil de Booker.

**Parâmetros**:
- `$user` (User): Instância do usuário a ser atualizado
- `$userData` (array): Dados validados para atualização (mesma estrutura do `createUser`)

**Retorno**: Instância do usuário atualizado

**Comportamentos Especiais**:
- Se `password` não for fornecido, mantém a senha atual
- Se `is_booker = false` e usuário tinha booker, desvincula o booker
- Se `is_booker = true` e usuário já tem booker, atualiza dados do booker existente
- Se `is_booker = true` e usuário não tem booker, cria ou associa conforme `booker_creation_type`

**Exemplo de Uso**:
```php
$user = User::find(1);
$updateData = [
    'name' => 'João Silva Santos',
    'email' => 'joao.santos@example.com',
    'is_booker' => true,
    'default_commission_rate' => 20.0
];
$updatedUser = $userService->updateUser($user, $updateData);
```

#### `deleteUser(User $user): bool`

**Descrição**: Remove (soft delete) o usuário e, opcionalmente, seu perfil de Booker associado.

**Parâmetros**:
- `$user` (User): Instância do usuário a ser removido

**Retorno**: `true` se a remoção foi bem-sucedida, `false` caso contrário

**Comportamento**:
- Realiza soft delete do usuário
- Se o usuário tem booker associado, também realiza soft delete do booker
- Operação é atômica (transação de banco)

**Exemplo de Uso**:
```php
$user = User::find(1);
$success = $userService->deleteUser($user);

if ($success) {
    echo "Usuário removido com sucesso";
} else {
    echo "Erro ao remover usuário";
}
```

### Validações e Regras de Negócio

#### Associação de Bookers
- Um booker só pode estar associado a um usuário por vez
- Ao associar booker existente, verifica se já não está vinculado a outro usuário
- Nomes de bookers são sempre convertidos para maiúsculas

#### Transações
- Todas as operações são atômicas (usam transações de banco)
- Em caso de erro, todas as alterações são revertidas
- Logs detalhados são gerados para auditoria

#### Tratamento de Erros
- Exceções são logadas com contexto completo
- Transações são revertidas automaticamente em caso de erro
- Exceções são re-lançadas para tratamento no controller

---

## 🏠 DashboardService

### Descrição
Service responsável por fornecer dados consolidados para o dashboard principal da aplicação. Calcula métricas de desempenho, estatísticas de gigs, faturamento mensal e prepara dados para visualizações gráficas.

### Dependências
- `GigFinancialCalculatorService`: Para cálculos financeiros consistentes
- `Gig` (Model): Para consultas de dados de gigs
- `Carbon`: Para manipulação de datas
- `DB` (Facades): Para consultas avançadas

### Métodos Principais

#### `setFilters(array $filters = []): self`

**Descrição**: Define filtros de período para os dados do dashboard.

**Parâmetros**:
- `$filters` (array): Filtros opcionais
  - `start_date` (string, opcional): Data de início no formato Y-m-d
  - `end_date` (string, opcional): Data de fim no formato Y-m-d

**Retorno**: Instância do próprio service (fluent interface)

**Comportamento Padrão**: Se não fornecido, usa o mês atual

**Exemplo de Uso**:
```php
$dashboardService = app(DashboardService::class);

// Usar período padrão (mês atual)
$data = $dashboardService->getDashboardData();

// Definir período específico
$data = $dashboardService
    ->setFilters([
        'start_date' => '2024-01-01',
        'end_date' => '2024-01-31'
    ])
    ->getDashboardData();
```

#### `getFirstAndLastMonth(): array`

**Descrição**: Obtém o primeiro e último mês com dados de gigs cadastradas no sistema.

**Retorno**: Array com as chaves:
- `first_month` (string): Data do primeiro mês no formato Y-m-d
- `last_month` (string): Data do último mês no formato Y-m-d

**Comportamento**: Se não houver gigs, retorna o mês atual

**Exemplo de Uso**:
```php
$dateRange = $dashboardService->getFirstAndLastMonth();
// Resultado: ['first_month' => '2023-06-01', 'last_month' => '2024-12-31']
```

#### `getDashboardData(): array`

**Descrição**: Método principal que retorna todos os dados consolidados do dashboard.

**Retorno**: Array com métricas e dados estruturados:

**Dados Gerais**:
- `today` (Carbon): Data atual
- `startOfMonth` (Carbon): Início do período filtrado
- `endOfMonth` (Carbon): Fim do período filtrado

**Contadores Gerais**:
- `totalGigsCount` (int): Total de gigs no sistema
- `activeFutureGigsCount` (int): Gigs futuras ativas
- `overdueClientPaymentsCount` (int): Pagamentos de clientes vencidos
- `pendingArtistPaymentsCount` (int): Pagamentos de artistas pendentes
- `pendingBookerPaymentsCount` (int): Pagamentos de bookers pendentes

**Métricas do Período (Gigs por Data do Evento)**:
- `gigsThisMonthCount` (int): Quantidade de gigs no período
- `totalCacheThisMonth` (float): Cache bruto total em BRL
- `totalAgencyCommissionThisMonth` (float): Comissão da agência em BRL
- `totalBookerCommissionThisMonth` (float): Comissão dos bookers em BRL

**Métricas de Vendas (Gigs por Data de Contrato)**:
- `salesThisMonthCount` (int): Quantidade de vendas no período
- `totalSalesThisMonth` (float): Valor total de vendas em BRL

**URLs de Relatórios**:
- `performanceReportUrl` (string): URL para relatório do período atual
- `fullPerformanceReportUrl` (string): URL para relatório completo

**Próximas Gigs**:
- `nextGigs` (Collection): Próximas 5 gigs ordenadas por data

**Dados do Gráfico de Faturamento**:
- `chartLabels` (array): Labels dos meses para o gráfico
- `chartData` (array): Valores de faturamento por mês
- `chartGigsCount` (array): Quantidade de gigs por mês

**Exemplo de Uso**:
```php
$dashboardService = app(DashboardService::class);
$data = $dashboardService->getDashboardData();

// Acessar dados específicos
echo "Total de gigs: " . $data['totalGigsCount'];
echo "Faturamento do mês: R$ " . number_format($data['totalCacheThisMonth'], 2);
echo "Próximas gigs: " . $data['nextGigs']->count();

// Usar dados do gráfico
$chartConfig = [
    'labels' => $data['chartLabels'],
    'datasets' => [
        [
            'label' => 'Faturamento Mensal',
            'data' => $data['chartData']
        ]
    ]
];
```

### Regras de Negócio

#### Cálculo de Métricas do Período
- **Gigs do Mês**: Baseado na `gig_date` (data do evento)
- **Vendas do Mês**: Baseado na `contract_date` ou `gig_date` se contrato for nulo
- Apenas gigs com status de contrato válidos: `['assinado', 'concluido', 'para_assinatura', 'n/a']`

#### Gráfico de Faturamento Mensal
- Exibe dados dos últimos 12 meses
- Agrupa por mês/ano da data de contrato (ou data da gig se contrato for nulo)
- Inclui contagem de gigs por mês para contexto adicional

#### Consistência de Cálculos
- Todos os cálculos financeiros usam o `GigFinancialCalculatorService`
- Garante consistência com outros relatórios do sistema
- Valores sempre convertidos para BRL

#### Filtros e Períodos
- Período padrão: mês atual
- Suporte a períodos customizados via `setFilters()`
- Datas são tratadas com Carbon para precisão

---

## 📈 FinancialProjectionService

### Descrição
Service responsável por calcular e agregar projeções financeiras da agência, incluindo contas a receber, contas a pagar, fluxo de caixa projetado e análises de despesas por centro de custo. Fornece uma visão completa da situação financeira futura da empresa.

### Dependências
- `GigFinancialCalculatorService`: Para cálculos financeiros consistentes
- `Gig` (Model): Para consultas de gigs
- `Payment` (Model): Para consultas de pagamentos
- `GigCost` (Model): Para consultas de custos
- `Carbon`: Para manipulação de datas
- `Log` (Facades): Para logging detalhado

### Métodos Principais

#### `setPeriod(string $period): void`

**Descrição**: Define o período da projeção financeira.

**Parâmetros**:
- `$period` (string): Identificador do período
  - `'30_days'`: 30 dias a partir de hoje (padrão)
  - `'60_days'`: 60 dias a partir de hoje
  - `'90_days'`: 90 dias a partir de hoje
  - `'next_semester'`: Até o final do próximo semestre
  - `'next_year'`: Até o final do próximo ano civil

**Comportamento**: A projeção sempre começa a partir de hoje

**Exemplo de Uso**:
```php
$projectionService = app(FinancialProjectionService::class);

// Período padrão (30 dias)
$projectionService->setPeriod('30_days');

// Projeção para próximo semestre
$projectionService->setPeriod('next_semester');
```

#### `getAccountsReceivable(): float`

**Descrição**: Calcula o total de contas a receber de clientes (todas as parcelas não confirmadas).

**Retorno**: Valor total a receber em BRL

**Comportamento**: Inclui parcelas vencidas e a vencer, independente da data de vencimento

**Exemplo de Uso**:
```php
$totalReceivable = $projectionService->getAccountsReceivable();
echo "Total a receber: R$ " . number_format($totalReceivable, 2);
```

#### `getUpcomingClientPayments(): Collection`

**Descrição**: Retorna lista detalhada de pagamentos pendentes de clientes no período.

**Retorno**: Collection de pagamentos com relacionamentos carregados (gig, artist)

**Comportamento**: Inclui pagamentos vencidos até ontem + pagamentos a vencer no período de projeção

**Exemplo de Uso**:
```php
$upcomingPayments = $projectionService->getUpcomingClientPayments();

foreach ($upcomingPayments as $payment) {
    echo "Vencimento: {$payment->due_date->format('d/m/Y')} - ";
    echo "Valor: R$ " . number_format($payment->due_value_brl, 2) . " - ";
    echo "Artista: {$payment->gig->artist->name}\n";
}
```

#### `getAccountsPayableArtists(): float`

**Descrição**: Calcula contas a pagar para artistas (valor final da nota fiscal).

**Retorno**: Valor total a pagar em BRL

**Comportamento**: 
- Inclui gigs passadas com pagamento pendente
- Inclui gigs futuras no período de projeção
- Usa o valor da nota fiscal do artista (cachê líquido + reembolsos)

**Exemplo de Uso**:
```php
$payableArtists = $projectionService->getAccountsPayableArtists();
echo "Total a pagar artistas: R$ " . number_format($payableArtists, 2);
```

#### `getAccountsPayableBookers(): float`

**Descrição**: Calcula contas a pagar para bookers (comissões).

**Retorno**: Valor total de comissões a pagar em BRL

**Comportamento**: Similar ao método de artistas, mas para comissões de bookers

#### `getAccountsPayableExpenses(): float`

**Descrição**: Calcula despesas previstas (custos não confirmados).

**Retorno**: Valor total de despesas em BRL

**Comportamento**:
- Considera apenas gigs ativas (não deletadas)
- Inclui despesas com data no período OU sem data mas de gigs no período
- Apenas custos não confirmados

#### `getProjectedExpensesByCostCenter(): Collection`

**Descrição**: Obtém despesas previstas agrupadas por centro de custo.

**Retorno**: Collection agrupada por centro de custo com detalhes das despesas

**Estrutura do Retorno**:
```php
[
    [
        'cost_center_name' => 'Nome do Centro de Custo',
        'total_brl' => 1500.00,
        'expenses' => [
            [
                'gig_id' => 123,
                'gig_contract_number' => 'CONT-2024-001',
                'gig_artist_name' => 'Nome do Artista',
                'description' => 'Descrição da despesa',
                'expense_date_formatted' => '15/03/2024',
                'value_brl' => 500.00,
                'currency' => 'BRL'
            ]
        ]
    ]
]
```

**Exemplo de Uso**:
```php
$expensesByCostCenter = $projectionService->getProjectedExpensesByCostCenter();

foreach ($expensesByCostCenter as $costCenter) {
    echo "Centro de Custo: {$costCenter['cost_center_name']}\n";
    echo "Total: R$ " . number_format($costCenter['total_brl'], 2) . "\n";
    
    foreach ($costCenter['expenses'] as $expense) {
        echo "  - {$expense['description']}: R$ " . number_format($expense['value_brl'], 2) . "\n";
    }
}
```

#### `getProjectedCashFlow(): float`

**Descrição**: Calcula o fluxo de caixa projetado.

**Retorno**: Valor do fluxo de caixa em BRL (pode ser negativo)

**Fórmula**: Contas a Receber - (Contas a Pagar Artistas + Contas a Pagar Bookers + Despesas Previstas)

**Exemplo de Uso**:
```php
$cashFlow = $projectionService->getProjectedCashFlow();

if ($cashFlow > 0) {
    echo "Fluxo positivo: R$ " . number_format($cashFlow, 2);
} else {
    echo "Fluxo negativo: R$ " . number_format(abs($cashFlow), 2);
}
```

#### `getUpcomingInternalPayments(string $type): Collection`

**Descrição**: Retorna lista de gigs com pagamentos pendentes internos.

**Parâmetros**:
- `$type` (string): 'artists' ou 'bookers'

**Retorno**: Collection de gigs com relacionamentos carregados

**Comportamento**: Inclui gigs passadas pendentes + gigs futuras no período

**Exemplo de Uso**:
```php
// Pagamentos pendentes para artistas
$artistPayments = $projectionService->getUpcomingInternalPayments('artists');

foreach ($artistPayments as $gig) {
    $artistPayment = $this->calculatorService->calculateArtistInvoiceValueBrl($gig);
    echo "Gig: {$gig->contract_number} - ";
    echo "Artista: {$gig->artist->name} - ";
    echo "Valor: R$ " . number_format($artistPayment, 2) . "\n";
}

// Pagamentos pendentes para bookers
$bookerPayments = $projectionService->getUpcomingInternalPayments('bookers');
```

### Regras de Negócio

#### Períodos de Projeção
- **30 dias**: Hoje + 29 dias (total 30 dias)
- **60 dias**: Hoje + 59 dias (total 60 dias)
- **90 dias**: Hoje + 89 dias (total 90 dias)
- **Próximo semestre**: 6 meses após o final do semestre atual
- **Próximo ano**: Até 31 de dezembro do próximo ano

#### Contas a Receber
- Inclui TODAS as parcelas não confirmadas
- Independe da data de vencimento (inclui vencidas)
- Valores sempre em BRL (conversão automática)

#### Contas a Pagar
- **Artistas**: Valor da nota fiscal (cachê líquido + reembolsos)
- **Bookers**: Valor da comissão calculada
- **Despesas**: Apenas custos não confirmados de gigs ativas

#### Fluxo de Caixa
- Cálculo: Recebível - Total a Pagar
- Pode resultar em valor negativo (déficit projetado)
- Logging detalhado para auditoria

#### Tratamento de Datas
- Despesas sem data usam a data da gig associada
- Gigs deletadas (soft delete) são excluídas
- Todas as datas são tratadas com precisão (início/fim do dia)

---

## 💱 ExchangeRateService

### Descrição
Service para conversão de moedas e obtenção de taxas de câmbio, com integração à API do Banco Central do Brasil.

### Métodos Principais

#### `getExchangeRate(string $currency): float`

**Descrição**: Obtém taxa de câmbio para uma moeda específica.

**Parâmetros**:
- `$currency` (string): Código da moeda (USD, EUR, etc.)

**Retorno**: Taxa de câmbio em relação ao BRL

**Exemplo de Uso**:
```php
$exchangeService = app(ExchangeRateService::class);
$usdRate = $exchangeService->getExchangeRate('USD');
echo "1 USD = R$ " . number_format($usdRate, 4);
```

#### `convertToBRL(float $amount, string $currency): float`

**Descrição**: Converte valor para BRL.

**Parâmetros**:
- `$amount` (float): Valor a ser convertido
- `$currency` (string): Moeda de origem

**Retorno**: Valor convertido em BRL

#### `getMultipleRates(array $currencies): array`

**Descrição**: Obtém taxas para múltiplas moedas.

**Retorno**:
```php
[
    'USD' => 5.25,
    'EUR' => 6.10,
    // ...
]
```

#### `isSupportedCurrency(string $currency): bool`

**Descrição**: Verifica se uma moeda é suportada.

---

## 📊 FinancialReportService

Service responsável pela geração de relatórios financeiros detalhados, incluindo análises de rentabilidade, fluxo de caixa, comissões e despesas operacionais.

### Dependências
- `GigFinancialCalculatorService`: Para cálculos financeiros

### Métodos Principais

#### `setFilters(array $filters): void`

**Descrição**: Define filtros para os relatórios.

**Parâmetros**:
- `$filters` (array): Filtros disponíveis
  - `start_date` (string): Data inicial (Y-m-d)
  - `end_date` (string): Data final (Y-m-d)
  - `booker_id` (int): ID do booker
  - `artist_id` (int): ID do artista

#### `getOverviewSummary(): array`

**Descrição**: Retorna resumo geral do fluxo de caixa.

**Retorno**:
```php
[
    'total_inflow' => float,    // Total de entradas
    'total_outflow' => float,   // Total de saídas
    'net_cashflow' => float     // Fluxo líquido
]
```

#### `getOverviewTableData(): Collection`

**Descrição**: Dados tabulares para visão geral de gigs.

**Retorno**: Collection com dados de cada gig:
```php
[
    'contract_number' => string,
    'gig_date' => string,
    'artist' => string,
    'booker' => string,
    'revenue' => float,
    'costs' => float,
    'commission' => float,
    'net_profit' => float
]
```

#### `getProfitabilitySummary(): array`

**Descrição**: Resumo de rentabilidade dos eventos.

**Retorno**:
```php
[
    'total_profit' => float,        // Lucro total
    'average_margin' => float,      // Margem média (%)
    'profitable_events' => int      // Número de eventos lucrativos
]
```

#### `getProfitabilityTableData(): Collection`

**Descrição**: Dados detalhados de rentabilidade por gig.

#### `getCashflowSummary(): array`

**Descrição**: Resumo detalhado do fluxo de caixa.

**Retorno**:
```php
[
    'total_inflow' => float,
    'total_outflow' => float,
    'total_outflow_expenses' => float,
    'total_outflow_artists' => float,
    'total_outflow_bookers' => float,
    'net_cashflow' => float
]
```

#### `getFinancialReportData(): array`

**Descrição**: Relatório financeiro completo com todas as métricas.

**Retorno**:
```php
[
    'total_revenue' => float,
    'total_agency_commissions' => float,
    'total_booker_commissions' => float,
    'total_events' => int,
    'events_by_artist' => array,
    'revenue_by_booker' => array,
    'operational_expenses' => Collection,
    'total_operational_expenses' => float,
    'net_revenue' => float,
    'operational_result' => float
]
```

#### `getDetailedPerformanceData(): array`

**Descrição**: Análise detalhada de performance financeira.

#### `getProfitabilityAnalysisData(): array`

**Descrição**: Análise de rentabilidade por período.

#### `getSalesProfitabilityData(): Collection`

**Descrição**: Dados de rentabilidade de vendas por gig.

#### `getGroupedExpensesData(): array`

**Descrição**: Despesas agrupadas por categoria.

#### `getGroupedCommissionsData(): array`

**Descrição**: Comissões agrupadas por tipo.

**Exemplo de Uso**:
```php
$reportService = app(FinancialReportService::class);

// Definir período
$reportService->setFilters([
    'start_date' => '2024-01-01',
    'end_date' => '2024-12-31',
    'booker_id' => 1
]);

// Obter resumo
$overview = $reportService->getOverviewSummary();
$profitability = $reportService->getProfitabilitySummary();

// Relatório completo
$fullReport = $reportService->getFinancialReportData();
```

---

## 🧮 GigFinancialCalculatorService

### Descrição
Service para cálculos financeiros específicos de gigs, incluindo valores líquidos e conversões.

### Métodos Principais

#### `calculateArtistNetPayout(Gig $gig): float`

**Descrição**: Calcula valor líquido a ser pago ao artista.

**Retorno**: Valor em BRL

#### `calculateTotalPaid(Gig $gig): float`

**Descrição**: Calcula total já pago para o gig.

#### `calculateTotalPending(Gig $gig): float`

**Descrição**: Calcula total pendente de pagamento.

---

## 🤝 BookerFinancialsService

### Descrição
Service para cálculo e análise de métricas financeiras de bookers, incluindo vendas, comissões, rankings de artistas e análise de eventos realizados e futuros.

### Dependências
- `GigFinancialCalculatorService`

### Métodos Principais

#### `getSalesKpis(Booker $booker, ?Carbon $startDate = null, ?Carbon $endDate = null): array`

**Descrição**: Calcula KPIs de vendas do booker para um período específico.

**Parâmetros**:
- `$booker` (Booker): Instância do modelo Booker
- `$startDate` (Carbon, opcional): Data de início do período
- `$endDate` (Carbon, opcional): Data de fim do período

**Retorno**:
```php
[
    'total_sold_value' => float,  // Valor total vendido em BRL
    'total_gigs_sold' => int      // Quantidade total de gigs vendidas
]
```

#### `getCommissionKpis(Booker $booker): array`

**Descrição**: Calcula KPIs de comissões do booker.

**Retorno**:
```php
[
    'commission_received' => float,   // Comissão já recebida
    'commission_to_receive' => float  // Comissão pendente de recebimento
]
```

#### `getCommissionChartData(Booker $booker): array`

**Descrição**: Gera dados para gráfico de comissões dos últimos 12 meses.

**Retorno**:
```php
[
    'labels' => array,  // Labels dos meses (ex: ['Jan/24', 'Fev/24', ...])
    'data' => array     // Valores de comissão por mês
]
```

#### `getTopArtists(Booker $booker, ?Carbon $startDate = null, ?Carbon $endDate = null, int $limit = 10): Collection`

**Descrição**: Retorna ranking dos artistas mais vendidos pelo booker.

**Parâmetros**:
- `$limit` (int): Quantidade máxima de artistas a retornar (padrão: 10)

**Retorno**: Collection com objetos contendo:
```php
[
    'artist_name' => string,
    'gigs_count' => int,
    'total_value' => float,
    'gigs' => Collection  // Detalhes das gigs do artista
]
```

#### `getRecentGigs(Booker $booker, int $limit = 10): Collection`

**Descrição**: Retorna as gigs mais recentes do booker.

**Parâmetros**:
- `$limit` (int): Quantidade máxima de gigs a retornar (padrão: 10)

#### `getGigsForPeriod(Booker $booker, Carbon $startDate, Carbon $endDate): Collection`

**Descrição**: Retorna gigs do booker para um período específico.

#### `getRealizedEvents(Booker $booker, ?Carbon $startDate = null, ?Carbon $endDate = null): Collection`

**Descrição**: Retorna eventos já realizados (gig_date no passado) com métricas financeiras.

**Retorno**: Collection com dados detalhados incluindo:
- Informações básicas do evento
- Status de pagamentos
- Comissões calculadas
- Custos totais
- Indicadores de permissão de pagamento

#### `getFutureEvents(Booker $booker, ?Carbon $startDate = null, ?Carbon $endDate = null): Collection`

**Descrição**: Retorna eventos futuros (gig_date no futuro) com métricas financeiras.

### Métodos Privados

#### `canPayCommission($gig): bool`

**Descrição**: Verifica se é possível pagar comissão para um evento.

**Regra**: Só permite pagamento se o evento foi realizado OU se há exceção justificada.

#### `isPaymentException($gig): bool`

**Descrição**: Verifica se há exceção justificada para pagamento antecipado através do campo 'notes'.

**Palavras-chave de exceção**: 'exceção', 'excecao', 'antecipado', 'justificado', 'autorizado', 'aprovado'

### Exemplo de Uso

```php
$bookerService = app(BookerFinancialsService::class);
$booker = Booker::find(1);

// KPIs de vendas
$salesKpis = $bookerService->getSalesKpis($booker);
echo "Total vendido: R$ " . number_format($salesKpis['total_sold_value'], 2);

// KPIs de comissões
$commissionKpis = $bookerService->getCommissionKpis($booker);
echo "Comissão recebida: R$ " . number_format($commissionKpis['commission_received'], 2);

// Top artistas
$topArtists = $bookerService->getTopArtists($booker, limit: 5);
foreach ($topArtists as $artist) {
    echo "{$artist->artist_name}: {$artist->gigs_count} gigs, R$ " . 
         number_format($artist->total_value, 2) . "\n";
}

// Eventos realizados
$realizedEvents = $bookerService->getRealizedEvents($booker);
foreach ($realizedEvents as $event) {
    echo "Evento: {$event['contract_number']} - ";
    echo "Pode pagar comissão: " . ($event['can_pay_commission'] ? 'Sim' : 'Não') . "\n";
}
```

### Regras de Negócio

1. **Cálculo de Vendas**: Usa `contract_date` como prioridade, com fallback para `gig_date`
2. **Comissões**: Calcula usando `GigFinancialCalculatorService` para consistência
3. **Pagamento de Comissões**: Só permite para eventos realizados ou com exceção justificada
4. **Ranking de Artistas**: Ordenado por valor total vendido (decrescente)
5. **Eventos Recentes**: Ordenados por data mais recente primeiro
6. **Exceções de Pagamento**: Identificadas através de palavras-chave no campo 'notes'

---

## 👥 UserManagementService

### Descrição
Service para gerenciamento de usuários, incluindo criação, atualização e controle de permissões.

### Métodos Principais

#### `createUser(array $data): User`

**Descrição**: Cria novo usuário.

**Parâmetros**:
```php
[
    'name' => string,
    'email' => string,
    'password' => string,
    'role' => string // opcional
]
```

#### `updateUser(User $user, array $data): User`

**Descrição**: Atualiza dados do usuário.

#### `assignRole(User $user, string $role): bool`

**Descrição**: Atribui papel ao usuário.

---

## 📊 DashboardService

### Descrição
Service para agregação de dados do dashboard principal.

### Métodos Principais

#### `getDashboardData(): array`

**Descrição**: Retorna dados consolidados para o dashboard.

**Retorno**:
```php
[
    'total_gigs' => int,
    'total_revenue_brl' => float,
    'pending_payments_brl' => float,
    'active_artists' => int,
    'recent_activities' => array,
    'monthly_stats' => array
]
```

---

## 📈 FinancialReportService

### Descrição
Service para geração de relatórios financeiros detalhados.

### Métodos Principais

#### `generateMonthlyReport(int $year, int $month): array`

**Descrição**: Gera relatório mensal.

#### `generateArtistReport(Artist $artist, ?Carbon $startDate = null, ?Carbon $endDate = null): array`

**Descrição**: Gera relatório específico de artista.

---

## 🔮 FinancialProjectionService

### Descrição
Service para projeções financeiras e análises preditivas.

### Métodos Principais

#### `projectMonthlyRevenue(int $months = 12): array`

**Descrição**: Projeta receita para os próximos meses.

#### `calculateTrends(): array`

**Descrição**: Calcula tendências financeiras.

---

## 🎯 BookerFinancialsService

### Descrição
Service para métricas financeiras de bookers.

### Métodos Principais

#### `getBookerMetrics(Booker $booker): array`

**Descrição**: Calcula métricas financeiras de um booker.

---

## 🛠️ Padrões de Uso

### Injeção de Dependência

```php
// Em controllers
class GigController extends Controller
{
    public function __construct(
        private AuditService $auditService,
        private ArtistFinancialsService $financialsService
    ) {}
}

// Em outros services
class CustomService
{
    public function __construct(
        private ExchangeRateService $exchangeService
    ) {}
}
```

### Tratamento de Erros

```php
try {
    $rate = $exchangeService->getExchangeRate('USD');
} catch (ExchangeRateException $e) {
    Log::error('Erro ao obter taxa de câmbio', ['error' => $e->getMessage()]);
    $rate = $exchangeService->getDefaultRate('USD');
}
```

### Cache

Muitos services utilizam cache para otimizar performance:

```php
// ExchangeRateService usa cache de 1 hora
// DashboardService usa cache de 15 minutos
// FinancialReportService usa cache de 1 dia
```

## 🧪 Testes

Todos os services possuem testes unitários em `tests/Unit/Services/`:

- `AuditServiceTest.php`
- `ArtistFinancialsServiceTest.php`
- `ExchangeRateServiceTest.php`
- E outros...

### Executando Testes dos Services

**⚠️ IMPORTANTE**: Use Laravel Sail para executar todos os comandos:

```bash
# Testar todos os services
sail artisan test tests/Unit/Services/

# Testar service específico
sail artisan test tests/Unit/Services/ExchangeRateServiceTest.php

# Testar com cobertura
sail artisan test tests/Unit/Services/ --coverage

# Debug de services via Tinker
sail artisan tinker
# > app(App\Services\ExchangeRateService::class)->getExchangeRate('USD')
```

## 📞 Suporte

Para dúvidas sobre os services:
1. Consulte esta documentação
2. Verifique os testes unitários para exemplos
3. Consulte o código fonte dos services
4. Entre em contato com a equipe de desenvolvimento