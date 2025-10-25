# Lições Aprendidas e Diretrizes

Este documento centraliza os aprendizados e define as boas práticas para garantir a consistência e a qualidade do código, especialmente ao colaborar com múltiplos agentes de IA.

## 1. Acertos e Padrões a Seguir

- **Centralização da Lógica de Negócio:** A existência do `GigFinancialCalculatorService` é um grande acerto. Serviços como `ArtistFinancialsService`, `BookerFinancialsService` e `AuditService` já o utilizam corretamente, servindo como "Padrão Ouro" para futuras implementações.

- **Injeção de Dependência:** O uso da injeção de dependência do Laravel para fornecer serviços a outros serviços ou controllers é a prática correta e deve ser seguida.

- **Arquitetura MCP (Laravel Boost):** O projeto demonstra uma estrutura organizada que se alinha aos princípios do MCP. As responsabilidades são bem definidas entre Models, Controllers e Providers (Services), o que facilita a manutenção.

## 3. Lições da Refatoração do Módulo de Projeções

- **O Risco dos Testes Desatualizados:** A refatoração dos serviços de projeção (`DreProjectionService` e `CashFlowProjectionService`) expôs uma falha crítica no processo de desenvolvimento: os testes unitários e de integração estavam validando uma lógica de negócio antiga e incorreta. Isso gerou uma grande quantidade de falhas pós-refatoração, não porque o novo código estava errado, mas porque os testes estavam testando o comportamento errado.

- **Diretriz: Testes Devem Acompanhar a Lógica de Negócio:** Ao refatorar uma lógica de negócio para uma fonte central da verdade (como o `GigFinancialCalculatorService`), os testes que cobrem essa lógica também devem ser atualizados para refletir os novos resultados esperados. **NUNCA** se deve reverter um código correto para fazer um teste desatualizado passar.

- **O Valor dos Testes de Integração:** Embora os testes unitários tenham sido corrigidos, os testes de integração (`FinancialReportServiceIntegrationTest`) foram essenciais para validar o fluxo completo e garantir que a interação entre os diferentes serviços estava funcionando como esperado após as mudanças. Eles foram a validação final de que a refatoração foi bem-sucedida.


1.  **Fonte Única da Verdade para Cálculos de Gig:**
    - **REGRA:** **TODA** e **QUALQUER** lógica de cálculo financeiro relacionada a uma `Gig` (comissões, cachês, totais, etc.) **DEVE** ser implementada ou chamada a partir do `GigFinancialCalculatorService`.
    - **JUSTIFICATIVA:** Evita duplicação de código, inconsistência nos cálculos e facilita a manutenção. Uma mudança na regra de negócio precisa ser feita em um único lugar.

2.  **Não "Hardcodar" Regras de Negócio:**
    - **REGRA:** Evite números mágicos ou percentuais fixos (ex: `* 0.20`, `* 0.80`) diretamente no código de serviços de alto nível (relatórios, projeções). Esses cálculos devem pertencer ao serviço de calculadora.
    - **JUSTIFICATIVA:** Regras de negócio mudam. Centralizá-las em um serviço específico torna o sistema mais flexível e fácil de atualizar.

3.  **Processo de Refatoração Segura:**
    - **REGRA:** Antes de refatorar um serviço, siga o fluxo definido em `ACOMPANHAMENTO_TAREFAS.md`: 1) Análise de Impacto (`search_file_content`), 2) Execução, 3) Verificação.
    - **JUSTIFICATIVA:** Garante que as alterações não quebrarão outras partes do sistema de forma inesperada.

4.  **Documentação Contínua:**
    - **REGRA:** Ao final de cada tarefa significativa, atualize os arquivos `ACOMPANHAMENTO_TAREFAS.md` e `LICOES_APRENDIDAS.md`.
    - **JUSTIFICATIVA:** Mantém a consciência situacional do time (humano e IA) e enriquece a base de conhecimento do projeto.

## 4. Lições do Desenvolvimento do Módulo de Custos

- **Cache de Rotas:** Ao adicionar novas rotas no Laravel, especialmente usando `Route::resource`, é crucial limpar o cache de rotas (`php artisan route:clear`). Um erro `Route [...] not defined` é um sintoma clássico de cache de rotas desatualizado.

- **Migrações com Dados:** Ao refatorar uma coluna que contém dados (como `category` -> `cost_center_id`), é fundamental criar um script de migração de dados dentro da própria migração para evitar a perda de informações. O uso de um Seeder para garantir a existência dos dados de destino (os `CostCenters`) antes da migração é uma prática robusta que previne falhas.

## 5. Lições da Refatoração de Layout do Dashboard de Projeções

A modernização da UI/UX do dashboard de projeções (`projections.dashboard.blade.php`) trouxe aprendizados importantes sobre a arquitetura de frontend com a stack TALL (Tailwind, Alpine.js, Laravel, Livewire/Blade).

- **Componentização Agressiva com Blade:** A decisão de quebrar a interface em pequenos componentes Blade reutilizáveis (`strategic-metric`, `kpi-card`, `value-card`, `expandable-section`) foi um grande acerto.
    - **Benefícios:** Reduziu a duplicação de código em cerca de 40%, tornou a view principal mais limpa e declarativa, e centralizou a lógica de apresentação, facilitando a manutenção.

- **Hierarquia Visual e Agrupamento Semântico:** A organização das métricas em seções claras e tituladas ("Métricas Estratégicas", "Indicadores Gerenciais", "Valores Globais") melhorou drasticamente a legibilidade e a compreensão do usuário. O uso consciente de espaçamento (`space-y-8`) e cabeçalhos de seção foi fundamental.

- **Melhoria da UX com Interatividade Sutil:**
    - **Tooltips Informativos:** Adicionar tooltips para explicar cada métrica é uma melhoria de baixo esforço e alto impacto. Torna o dashboard acessível a novos usuários sem poluir a interface.
    - **Tabelas Expansíveis com Alpine.js:** Usar Alpine.js para criar seções colapsáveis para tabelas de detalhes é um padrão excelente. Mantém a visão inicial focada nos números de alto nível, mas permite o acesso aos detalhes quando necessário, economizando espaço vertical.

- **Consistência de Design via Componentes:** A aplicação de padrões de design (cores, espaçamento, tipografia) diretamente nos componentes garante uma aparência consistente. Uma mudança no design de um card, por exemplo, pode ser feita em um único arquivo.

- **Estados Vazios (Empty States) Informativos:** Projetar e implementar "estados vazios" claros para tabelas é muito superior a simplesmente mostrar uma tabela vazia. Melhora a UX ao fornecer contexto e confirmar que a ausência de dados é o estado esperado.
