# Acompanhamento de Tarefas - Melhoria das Métricas de Projeção (Concluído)

Este documento serve como um "watcher" para a implementação de novas métricas e aprimoramento do dashboard de projeções financeiras.

## Fase 1: Análise e Planejamento (Concluída)

- [x] Analisar a `view` `projections.dashboard.blade.php`.
- [x] Analisar o `controller` `FinancialProjectionController.php`.
- [x] Definir um plano de ação para redefinir a lógica de "vencido" e introduzir novas métricas.
- [x] Alinhar o plano para incluir a criação de um módulo de Custos Operacionais e refatorar o banco de dados para usar a tabela `cost_centers` existente.
- [x] Criar e atualizar este arquivo de acompanhamento.

## Fase 2: Backend - Lógica de "Vencidos" (Concluída)

- [x] **Modificar o `FinancialProjectionController.php`:**
  - [x] No método `calculateGlobalAccountsReceivable`, alterar a consulta para incluir `gig_date` da `Gig`.
  - [x] Alterar o filtro de `overduePayments` para usar a data do evento (`gig_date`).
  - [x] Corrigir o nome da coluna de `event_date` para `gig_date` para resolver a `QueryException`.

## Fase 3: Refatoração do Banco de Dados (Concluída)

- [x] **Analisar Tabela `cost_centers`:**
  - [x] Ler a migração da tabela `cost_centers` para entender sua estrutura e garantir que é adequada para os custos da agência.
- [x] **Criar Migração para `agency_fixed_costs`:**
  - [x] Gerar um novo arquivo de migração.
  - [x] Na migração, adicionar a coluna `cost_center_id` como uma chave estrangeira para a tabela `cost_centers`.
  - [x] Escrever um script dentro da migração para migrar os dados da antiga coluna `category` (texto) para a nova coluna `cost_center_id` (ID).
  - [x] Após a migração dos dados, remover a coluna `category`.
- [x] **Atualizar o Modelo `AgencyFixedCost`:**
  - [x] Adicionar o relacionamento `belongsTo` para o modelo `CostCenter`.

## Fase 4: Módulo de Custos Operacionais (CRUD) (Concluída)

- [x] **Criar o Controller:**
  - [x] Criar o `AgencyCostController` com os métodos `index`, `create`, `store`, `edit`, `update`, `destroy`.
- [x] **Definir Rotas:**
  - [x] Adicionar as rotas para o CRUD de custos operacionais no arquivo `routes/web.php`, protegidas por autenticação.
- [x] **Desenvolver as Views (Blade):**
  - [x] Criar a view `index.blade.php` para listar os custos, com botões de ação.
  - [x] Criar a view `create.blade.php` com o formulário para novos custos (usando um dropdown para os centros de custo).
  - [x] Criar a view `edit.blade.php` com o formulário para edição.
- [x] **Integrar na Sidebar:**
  - [x] Adicionar o link "Custos Operacionais" no menu lateral da aplicação, abaixo de "Bookers".

## Fase 5: Backend - Lógica das Métricas (Concluída)

- [x] **Atualizar `CashFlowProjectionService`:**
  - [x] Modificar o método `calculateProjectedExpenses` para agrupar os custos pelo novo `cost_center_id` em vez da antiga `category`.
- [x] **Implementar Lógica em `FinancialProjectionController`:**
  - [x] No método `calculateStrategicBalance`, substituir a lógica placeholder pela lógica de cálculo real, utilizando os dados do `CashFlowProjectionService` e outros serviços para calcular:
    - [x] "Caixa Gerado (Eventos Passados)"
    - [x] "Caixa Comprometido (Eventos Futuros)"
    - [x] "Balanço Financeiro"

## Fase 6: Frontend - Dashboard de Projeções (Concluída)

- [x] **Modificar `projections.dashboard.blade.php`:**
  - [x] Adicionar a seção para os 3 novos cards estratégicos (Caixa Gerado, Comprometido, Balanço).
  - [x] Renomear os cards e tabelas de "Contas Vencidas" para "Recebíveis de Eventos Passados".
  - [x] Adicionar um novo card "Total Custo Operacional", que exibirá o total de custos fixos mensais e um resumo por centro de custo.

## Fase 7: Verificação e Testes (Concluída)

- [x] **Verificação Manual:**
  - [x] Acessar o novo módulo e testar o CRUD de custos operacionais.
  - [x] Acessar a página de projeções e verificar se todos os novos cards (estratégicos e de custo operacional) aparecem corretamente.
  - [x] Validar se os números nos cards fazem sentido com base nos dados inseridos.
- [x] **Testes Automatizados:**
  - [x] Criar testes para o novo `AgencyCostController`.
  - [x] Atualizar testes existentes que possam ser afetados pela mudança na estrutura de custos.

## Fase 8: Conclusão (Concluída)

- [x] Revisar e atualizar o documento de `LICOES_APRENDIDAS.md`.
- [x] Fazer o commit final da feature.
- [x] Marcar todas as tarefas neste arquivo como concluídas.
