Excelente iniciativa! Consolidar todo o escopo e os alinhamentos em um documento de referência é um passo fundamental para a maturidade do projeto. Isso garante que tenhamos uma "fonte da verdade" para o estado atual do sistema e uma base sólida para planejar os próximos passos, como a "Central do Artista".

Com base em todo o trabalho que fizemos juntos, elaborei o escopo global detalhado do seu sistema **EventosPro**.

---

## **Escopo Global do Sistema: EventosPro**

### **Introdução e Contexto Global**

**EventosPro** é um sistema de gestão especializado para agências de artistas, desenvolvido em Laravel 12. O seu core business é a administração completa do ciclo de vida de um evento (Gig), desde o agendamento e contratação até o fechamento financeiro e análise de desempenho.

A aplicação foi projetada para ser a ferramenta central da agência, otimizando fluxos de trabalho, fornecendo insights de negócio através de relatórios e projeções, e evoluindo para uma plataforma de autoatendimento para colaboradores (Bookers).

---

### **Fase 1: Fundação e Lógica de Negócio Principal**

Esta fase estabeleceu a base do sistema, com a modelagem de dados e as funcionalidades essenciais para a operação diária.

#### **Módulo: Gestão de Entidades Principais (CRUDs)**

*   **Artistas:** CRUD completo para o cadastro de artistas, servindo como a entidade principal para a qual as Gigs são associadas.
*   **Bookers:** CRUD completo para o cadastro de bookers, incluindo a definição de uma taxa de comissão padrão.
*   **Centros de Custo:** Cadastro de categorias de despesas (Logística, Hospedagem, etc.) para organização financeira.

#### **Módulo: Gestão de Gigs (Core do Sistema)**

*   **Cadastro Detalhado:** CRUD completo de Gigs, registrando informações contratuais (`contract_number`, `contract_date`), dados do evento (`gig_date`, `location_event_details`) e detalhes financeiros.
*   **Lógica Multi-Moeda:** O sistema suporta o registro de cachês em diferentes moedas (BRL, USD, EUR, etc.).
*   **Cálculos Financeiros Centralizados:** Toda a lógica de cálculo financeiro (conversão para BRL, comissões, repasses, etc.) está encapsulada no `GigFinancialCalculatorService`, que atua como a fonte da verdade para todos os valores.
*   **Reatividade de Dados:**
    *   **Observers (`GigObserver`, `GigCostObserver`):** Automatizam o recálculo de comissões e outros valores quando uma Gig ou um custo associado é modificado.
    *   **Eventos e Listeners (`PaymentSaved` -> `UpdateGigPaymentStatus`):** Atualizam automaticamente o status de pagamento geral de uma Gig quando uma de suas parcelas é confirmada ou alterada.

#### **Módulo: Gestão Financeira Operacional**

*   **Gestão de Pagamentos (Recebíveis):**
    *   Lançamento de múltiplas parcelas de pagamento de clientes para cada Gig.
    *   Funcionalidade para confirmar o recebimento de cada parcela, registrando o valor real, a data e a taxa de câmbio do dia do pagamento.
*   **Gestão de Custos (Despesas):**
    *   Lançamento de despesas associadas a cada Gig, categorizadas por Centro de Custo.
    *   Funcionalidade para "Confirmar" uma despesa.
    *   Flag "Reembolsável via NF do Artista" (`is_invoice`), que impacta diretamente o cálculo do valor final da nota fiscal do artista.
*   **Acertos Financeiros (Settlements):**
    *   Registro dos pagamentos efetuados ao Artista e ao Booker.
    *   Upload de comprovantes de pagamento.
    *   Atualização automática dos status de pagamento internos da Gig (`artist_payment_status`, `booker_payment_status`).

---

### **Fase 2: Análise e Inteligência de Negócios**

Esta fase transformou os dados operacionais em insights acionáveis, com a criação de dashboards e relatórios complexos.

#### **Módulo: Relatórios Financeiros**

*   **Dashboard Interativo com Abas (Alpine.js):**
    *   **Visão Geral:** Tabela detalhada de todas as Gigs no período, **agrupada por Artista**, com subtotais por artista e um total geral. A interface apresenta cards de resumo com os KPIs principais (Cachê Líquido, Comissão Líquida, etc.) e botões de exportação (PDF/Excel) alinhados.
    *   **Rentabilidade:** Visão de "extrato de vendas", listando Gigs ordenadas pela "data da venda" (`COALESCE(contract_date, gig_date)`), com colunas de Receita, Custos, Rentabilidade e Margem.
    *   **Comissões:** Tabela detalhada de comissões, **agrupada por Booker**, com subtotais e totais por grupo, ideal para fechamento.
    *   **Despesas:** Relatório de despesas, **agrupado por Centro de Custo** traduzido.
    *   **Fluxo de Caixa:** Visão de entradas e saídas consolidadas no período.
*   **Filtragem Dinâmica:** Todos os relatórios podem ser filtrados por período, artista e booker.
*   **Exportação (PDF e Excel):** Funcionalidade robusta para exportar os dados da "Visão Geral", mantendo os filtros aplicados e utilizando layouts profissionais e otimizados para cada formato.

#### **Módulo: Projeções Financeiras**

*   **Dashboard de Projeções:** Apresenta uma visão futura das finanças, com cards de resumo para:
    *   Contas a Receber (todas as parcelas de clientes pendentes).
    *   Contas a Pagar (Artistas, Bookers e Despesas previstas).
*   **Lógica Abrangente:** Os cálculos incluem valores pendentes do passado, além das previsões para o período futuro selecionado.
*   **Detalhamento:** Listas detalhadas para cada projeção, como "Despesas Previstas por Centro de Custo".

---

### **Fase 3: Automação e Experiência do Usuário (UX)**

Esta fase focou em refinar a interface, otimizar fluxos de trabalho e construir a base para a expansão do sistema com novos perfis de acesso.

#### **Módulo: Ações em Lote e Interatividade**

*   **Pagamento/Reversão em Massa:** Na aba de "Comissões", foi implementada uma interface com checkboxes (gerenciada por Alpine.js) que permite ao operador selecionar múltiplas comissões pendentes e pagá-las (ou reverter pagamentos) em uma única ação. A funcionalidade mantém o contexto dos filtros aplicados.
*   **Interfaces Dinâmicas (Acordeão):** Implementação de listas expansíveis (acordeão) em relatórios como "Pendências por Booker" e na "Central do Booker", permitindo uma visualização sintética que pode ser expandida para análise detalhada.

#### **Módulo: Gerenciamento de Usuários e Perfis**

*   **Estrutura de Acesso:** A tabela `users` foi estendida com uma relação `booker_id`, estabelecendo a base para diferentes tipos de usuários (Operadores da Agência vs. Bookers).
*   **CRUD de Usuários:** Implementação de um `UserController` com `FormRequests` robustos e um `UserManagementService` para encapsular a lógica de negócio.
*   **Formulário Inteligente:** O formulário de criação/edição de usuários possui uma lógica condicional avançada (com Alpine.js) que permite:
    *   Marcar um usuário como "Booker".
    *   Escolher entre associar o usuário a um Booker já existente (com validação para evitar duplicação) ou criar um novo perfil de Booker simultaneamente.

---

### **Fase 4: Expansão e Visão de Futuro (Portal do Booker)**

Esta fase representa a evolução do sistema de uma ferramenta interna para uma plataforma colaborativa, começando com o portal de autoatendimento para bookers.

#### **Módulo: Central do Booker (Operador)**

*   A antiga página `bookers.show` foi completamente refatorada para se tornar uma **"Central de Desempenho"** analítica.
*   **Análise Dinâmica:** A página agora possui filtros de período que afetam dinamicamente os KPIs e a tabela de vendas, permitindo uma análise "lifetime" ou por períodos específicos.
*   **Estrutura em Abas:**
    *   **Aba 1 (Análise Detalhada):** Contém os KPIs de vendas e comissões, e uma tabela detalhada das Gigs vendidas que aparece sob demanda após a aplicação de um filtro.
    *   **Aba 2 (Destaques & Métricas Fixas):** Contém componentes de análise de longo prazo que não mudam com o filtro, como o gráfico de evolução de comissões, o ranking de "Top Artistas" e a lista de "Gigs Mais Recentes".

#### **Módulo: Portal do Booker (Acesso Restrito - Em Desenvolvimento)**

*   **Base Implementada:** Já foram criados os fundamentos para o portal:
    *   **Rota Dedicada:** `GET /meu-desempenho` (`booker.portal`).
    *   **Lógica no Controller:** O método `BookerController@portal` verifica se o usuário logado é um booker e, em caso afirmativo, busca seus dados.
    *   **View do Portal:** `bookers.portal.blade.php`, que reutiliza os componentes da "Central do Booker", mas sem as funcionalidades administrativas.
*   **Próximos Passos (Visão Estratégica):**
    *   Implementar Policies/Gates para garantir a segregação de dados.
    *   Refinar a Sidebar e o layout para que o booker veja apenas os menus relevantes.
    *   Evoluir para um sistema de notificações de pagamento.

#### **Visão Futura: Central do Artista**

*   A arquitetura atual, com a separação clara de responsabilidades entre Controllers e Services, e a estrutura de dados relacional, prepara o terreno para a criação de um portal similar para os Artistas, onde eles poderiam consultar suas agendas, status de pagamento de cachês e detalhes de eventos.

Este documento representa o estado atual do EventosPro: um sistema robusto, com uma base de negócio sólida e que evoluiu para uma poderosa ferramenta de análise e gestão, com uma clara visão estratégica para o futuro.