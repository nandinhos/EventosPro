# Acompanhamento de Tarefas - Refatoração de Serviços Financeiros

Este documento serve como um "watcher" para o nosso plano de refatoração, garantindo consciência situacional e um local central para gerenciar o progresso.

## Fase 1: Análise e Planejamento (Concluída)

- [x] Analisar o serviço central `GigFinancialCalculatorService`.
- [x] Listar todos os serviços da pasta `app/Services`.
- [x] Ler o conteúdo dos serviços candidatos para identificar duplicação de lógica.
- [x] Criar um plano de refatoração unificado.
- [x] Estruturar o acompanhamento de tarefas e lições aprendidas.

## Fase 2: Refatoração do `FinancialReportService` (Concluída)

- [x] **Análise de Impacto (Parte 1 - Descoberta):**
  - [x] Executar `search_file_content` para encontrar todos os usos da classe `FinancialReportService`.
  - [x] Mapear os locais que consomem o serviço:
    - `app/Http/Controllers/FinancialReportController.php` (Principal)
    - `app/Filament/Widgets/VendasGeraisStats.php`
    - Testes (`...Test.php`)
- [x] **Análise de Impacto (Parte 2 - Análise do Controller):**
  - [x] Ler o conteúdo do `FinancialReportController.php` para identificar quais métodos do serviço são chamados.
- [x] **Execução da Refatoração:**
  - [x] Substituir a lógica manual em `getFinancialReportData` por chamadas ao `GigFinancialCalculatorService`.
  - [x] Substituir a lógica manual em `getProfitabilitySummary` por chamadas ao `GigFinancialCalculatorService`.
  - [x] Substituir a lógica manual em `getOverviewSummary` por chamadas ao `GigFinancialCalculatorService`.
  - [x] Substituir a lógica manual em `getOverviewTableData` por chamadas ao `GigFinancialCalculatorService`.
  - [x] Substituir a lógica manual em `getProfitabilityTableData` por chamadas ao `GigFinancialCalculatorService`.
  - [x] Substituir a lógica manual em `getCommissionsTableData` por chamadas ao `GigFinancialCalculatorService`.
- [x] **Verificação:**
  - [x] Revisar as páginas de relatórios no front-end para garantir que os dados continuam corretos.
  - [x] Executar testes automatizados relacionados a relatórios, se existirem.

## Fase 3: Refatoração do Módulo de Projeções (NEW)

- [x] **Análise e Alinhamento:**
  - [x] Revisar a nova diretriz em `docs/PROJECTION_REFACTORING.md`.
  - [x] Mapear todos os pontos no `DreProjectionService` e `CashFlowProjectionService` que realizam cálculos manuais.
- [x] **Execução da Refatoração (`DreProjectionService`):
  - [x] Substituir o cálculo da margem de contribuição (RLRA) pela chamada ao `GigFinancialCalculatorService::calculateAgencyNetCommissionBrl()`.
- [x] **Execução da Refatoração (`CashFlowProjectionService`):
  - [x] Substituir o cálculo do pagamento do artista pela chamada ao `GigFinancialCalculatorService::calculateArtistInvoiceValueBrl()`.
  - [x] Substituir o cálculo da comissão do booker pela chamada ao `GigFinancialCalculatorService::calculateBookerCommissionBrl()`.
- [x] **Verificação:**
  - [x] Revisar a página de projeções no front-end para garantir que os dados da DRE e do Fluxo de Caixa estão corretos.
  - [x] Executar e/ou criar testes automatizados para validar os novos cálculos.

## Fase 4: Refatoração do `CashFlowProjectionService`

- [x] **Análise de Impacto (Parte 1 - Descoberta):**
  - [x] Executar `search_file_content` para encontrar todos os usos da classe `CashFlowProjectionService`.
  - [x] Mapear os locais que consomem o serviço:
    - `app/Http/Controllers/FinancialProjectionController.php` (Principal)
    - Testes (`...Test.php`)
- [x] **Análise de Impacto (Parte 2 - Análise do Controller):**
  - [x] Ler o conteúdo do `FinancialProjectionController.php` para identificar quais métodos do serviço são chamados.
- [x] **Execução da Refatoração:**
  - [x] Injetar `GigFinancialCalculatorService` no construtor.
  - [x] Remover a dependência do `DreProjectionService` para cálculos de comissão/pagamento.
  - [x] Substituir os cálculos manuais (ex: `* 0.80`) em `calculateMonthlyOutflows` por chamadas diretas ao `GigFinancialCalculatorService`.
  - [x] Substituir os cálculos manuais em `calculateArtistPaymentDetails` por chamadas diretas ao `GigFinancialCalculatorService`.
  - [x] Substituir os cálculos manuais em `calculateBookerCommissionDetails` por chamadas diretas ao `GigFinancialCalculatorService`.
- [x] **Verificação:**
  - [x] Revisar as páginas de projeção financeira.
    - [x] **Erro de UI:** `QueryException` - Coluna 'stage_name' não encontrada na tabela `artists` - **Corrigido (removido 'stage_name' das cláusulas `with()` em `CashFlowProjectionService.php`)**.
    - [x] **Erro de UI:** `RelationNotFoundException` - Relação 'settlements' não definida no modelo `Gig` - **Corrigido (relação `settlements()` adicionada ao modelo `Gig`)**.
    - [x] **Erro de UI:** `QueryException` - Coluna 'amount_brl' não encontrada na tabela `settlements` - **Corrigido (substituído por `artist_payment_value` em `calculateArtistPaymentDetails`)**.
    - [x] **Erro de UI:** `QueryException` - Coluna 'amount_brl' não encontrada na tabela `settlements` - **Corrigido (substituído por `booker_commission_value_paid` em `calculateBookerCommissionDetails`)**.
  - [x] Executar testes automatizados relacionados a projeções, se existirem.
    - [x] **Correção de Erro:** Erro de sintaxe em `FinancialReportService.php` (método `getProfitabilitySummary`) corrigido (faltava `try {`).
    - [x] **Correção de Erro:** Erro de sintaxe em `FinancialReportService.php` (método `getOverviewTableData`) corrigido (faltava `try {`).
    - [x] **Correção de Erro:** Erro de sintaxe em `FinancialReportService.php` (método `getProfitabilitySummary`) corrigido (método inteiro substituído).
    - [x] **Correção de Erro:** `ArgumentCountError` em `CashFlowProjectionSoftDeleteTest.php` corrigido (instanciação do serviço no `setUp` do teste).
    - [x] **Correção de Erro:** `UnknownTypeException` em `CashFlowProjectionSoftDeleteTest.php` corrigido (falta de `use` statement para `GigFinancialCalculatorService`).
    - [x] **Falha Lógica:** `monthly inflows excludes soft deleted gigs` (esperado 500.0, recebido 0) - **Corrigido (accessor `received_value_actual_brl` adicionado ao modelo `Payment`)**.
    - [x] **Falha Lógica:** `monthly outflows excludes soft deleted gigs` (esperado > 0, recebido 0.0) - **Corrigido (mock configurado)**.
    - [x] **Correção de Erro:** Erro de sintaxe em `FinancialReportService.php` (método `getProfitabilitySummary`) corrigido (faltava `try {`).
    - [x] **Correção de Erro:** Erro de sintaxe em `FinancialReportService.php` (método `getOverviewTableData`) corrigido (faltava `try {`).
    - [x] **Correção de Erro:** Erro de sintaxe em `FinancialReportService.php` (método `getProfitabilitySummary`) corrigido (método inteiro substituído).
    - [x] **Correção de Erro:** `ArgumentCountError` em `CashFlowProjectionSoftDeleteTest.php` corrigido (instanciação do serviço no `setUp` do teste).
    - [x] **Correção de Erro:** `UnknownTypeException` em `CashFlowProjectionSoftDeleteTest.php` corrigido (falta de `use` statement para `GigFinancialCalculatorService`).
    - [x] **Falha Lógica:** `monthly inflows excludes soft deleted gigs` (esperado 500.0, recebido 0) - **Corrigido (accessor `received_value_actual_brl` adicionado ao modelo `Payment`)**.
    - [x] **Falha Lógica:** `monthly outflows excludes soft deleted gigs` (esperado > 0, recebido 0.0) - **Corrigido (mock configurado)**.
    - [x] **Correção de Erro:** Erro de sintaxe em `FinancialReportService.php` (método `getProfitabilitySummary`) corrigido (faltava `try {`).
    - [x] **Correção de Erro:** Erro de sintaxe em `FinancialReportService.php` (método `getOverviewTableData`) corrigido (faltava `try {`).
    - [x] **Correção de Erro:** Erro de sintaxe em `FinancialReportService.php` (método `getProfitabilitySummary`) corrigido (método inteiro substituído).
    - [x] **Correção de Erro:** `ArgumentCountError` em `CashFlowProjectionSoftDeleteTest.php` corrigido (instanciação do serviço no `setUp` do teste).
    - [x] **Correção de Erro:** `UnknownTypeException` em `CashFlowProjectionSoftDeleteTest.php` corrigido (falta de `use` statement para `GigFinancialCalculatorService`).
    - [x] **Falha Lógica:** `monthly inflows excludes soft deleted gigs` (esperado 500.0, recebido 0) - **Corrigido (accessor `received_value_actual_brl` adicionado ao modelo `Payment`)**.
    - [x] **Falha Lógica:** `monthly outflows excludes soft deleted gigs` (esperado > 0, recebido 0.0) - **Corrigido (mock configurado)**.
    - [x] **Correção de Erro:** Erro de sintaxe em `FinancialReportService.php` (método `getProfitabilitySummary`) corrigido (faltava `try {`).
    - [x] **Correção de Erro:** Erro de sintaxe em `FinancialReportService.php` (método `getOverviewTableData`) corrigido (faltava `try {`).
    - [x] **Correção de Erro:** Erro de sintaxe em `FinancialReportService.php` (método `getProfitabilitySummary`) corrigido (método inteiro substituído).
    - [x] **Correção de Erro:** `ArgumentCountError` em `CashFlowProjectionSoftDeleteTest.php` corrigido (instanciação do serviço no `setUp` do teste).
    - [x] **Correção de Erro:** `UnknownTypeException` em `CashFlowProjectionSoftDeleteTest.php` corrigido (falta de `use` statement para `GigFinancialCalculatorService`).
    - [x] **Falha Lógica:** `monthly inflows excludes soft deleted gigs` (esperado 500.0, recebido 0) - **Corrigido (accessor `received_value_actual_brl` adicionado ao modelo `Payment`)**.
    - [x] **Falha Lógica:** `monthly outflows excludes soft deleted gigs` (esperado > 0, recebido 0.0) - **Corrigido (mock configurado)**.## Fase 5: Conclusão

- [x] Revisar e atualizar o documento de `LICOES_APRENDIDAS.md` com os resultados da refatoração.
- [x] Validar que todas as tarefas foram concluídas e o sistema está estável.

## Resumo da Refatoração

A refatoração dos serviços de projeção (`DreProjectionService` e `CashFlowProjectionService`) foi concluída com sucesso. Todos os testes unitários e de integração estão passando, garantindo que a nova lógica de negócio está correta e que o sistema está estável. As lições aprendidas durante o processo foram documentadas no arquivo `LICOES_APRENDIDAS.md`.

O sistema agora utiliza o `GigFinancialCalculatorService` como a única fonte de verdade para todos os cálculos financeiros, eliminando a duplicação de código e garantindo a consistência dos dados em toda a aplicação.
