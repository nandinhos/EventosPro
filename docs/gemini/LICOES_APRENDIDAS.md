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
