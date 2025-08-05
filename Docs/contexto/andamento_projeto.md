# **Relatório de Andamento do Projeto EventosPro**

**Data da Última Atualização:** 05/08/2025  
**Versão do Documento:** 2.0

## **Visão Geral**

Este documento apresenta o estado atual do **EventosPro**, um sistema de gestão para agências de artistas desenvolvido em Laravel 12. O sistema evoluiu significativamente desde sua concepção inicial, incorporando novas funcionalidades e melhorias que vão além do escopo original.

## **Status Atual**

O sistema está em **fase avançada de desenvolvimento**, com a maior parte das funcionalidades principais já implementadas e em operação. A arquitetura atual permite uma fácil expansão para novas funcionalidades e integrações futuras.

---

## **Escopo Global do Sistema: EventosPro**

### **Introdução e Contexto Global**

**EventosPro** é um sistema de gestão especializado para agências de artistas, desenvolvido em Laravel 12. O seu core business é a administração completa do ciclo de vida de um evento (Gig), desde o agendamento e contratação até o fechamento financeiro e análise de desempenho.

A aplicação foi projetada para ser a ferramenta central da agência, otimizando fluxos de trabalho, fornecendo insights de negócio através de relatórios e projeções, e evoluindo para uma plataforma de autoatendimento para colaboradores (Bookers).

---

## **Fase 1: Fundação e Lógica de Negócio Principal** ✅ **Concluída**

Esta fase estabeleceu a base do sistema, com a modelagem de dados e as funcionalidades essenciais para a operação diária. Todas as funcionalidades planejadas foram implementadas e estão estáveis em produção.

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

## **Fase 2: Análise e Inteligência de Negócios** 🚧 **Em Andamento**

Esta fase transformou os dados operacionais em insights acionáveis, com a criação de dashboards e relatórios complexos. A maior parte das funcionalidades está implementada, com alguns ajustes pendentes.

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

## **Fase 3: Automação e Experiência do Usuário (UX)** 🚧 **Em Andamento**

Esta fase focou em refinar a interface, otimizar fluxos de trabalho e construir a base para a expansão do sistema com novos perfis de acesso. Várias melhorias foram implementadas, mas ainda existem oportunidades de otimização.

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

## **Fase 4: Expansão e Visão de Futuro** 🚀 **Em Desenvolvimento**

Esta fase representa a evolução do sistema de uma ferramenta interna para uma plataforma colaborativa, começando com o portal de autoatendimento para bookers. A arquitetura atual suporta esta expansão, mas várias funcionalidades ainda estão em desenvolvimento ativo.

#### **Módulo: Central do Booker (Operador)**

*   A antiga página `bookers.show` foi completamente refatorada para se tornar uma **"Central de Desempenho"** analítica.
*   **Análise Dinâmica:** A página agora possui filtros de período que afetam dinamicamente os KPIs e a tabela de vendas, permitindo uma análise "lifetime" ou por períodos específicos.
*   **Estrutura em Abas:**
    *   **Aba 1 (Análise Detalhada):** Contém os KPIs de vendas e comissões, e uma tabela detalhada das Gigs vendidas que aparece sob demanda após a aplicação de um filtro.
    *   **Aba 2 (Destaques & Métricas Fixas):** Contém componentes de análise de longo prazo que não mudam com o filtro, como o gráfico de evolução de comissões, o ranking de "Top Artistas" e a lista de "Gigs Mais Recentes".

#### **Módulo: Portal do Booker (Acesso Restrito - Em Desenvolvimento)**

*   **Base Implementada:**
    *   **Rota Dedicada:** `GET /meu-desempenho` (`booker.portal`)
    *   **Lógica no Controller:** `BookerController@portal` com verificação de permissões
    *   **View do Portal:** `bookers.portal.blade.php` com componentes reutilizáveis
    *   **Segurança:** Implementação de Policies para controle de acesso

*   **Status Atual:**
    - [x] Estrutura básica do portal
    - [x] Visualização de desempenho individual
    - [x] Filtros de período e métricas
    - [ ] Painel de notificações integrado
    - [ ] Upload de documentos
    - [ ] Sistema de mensagens

*   **Próximos Passos:**
    1. Implementar sistema de notificações em tempo real
    2. Desenvolver área de documentos compartilhados
    3. Criar painel de métricas avançadas
    4. Implementar sistema de mensagens internas

## **Próximas Fases Planejadas**

### **Fase 5: Central do Artista** 📅 **Planejada**

*   **Objetivo:** Criar um portal de autoatendimento para artistas, permitindo que acompanhem suas agendas, status de pagamentos e métricas de desempenho.
*   **Funcionalidades Planejadas:**
    - Dashboard personalizado para cada artista
    - Acompanhamento de pagamentos e extratos
    - Upload de materiais (rider técnico, fotos, vídeos)
    - Calendário de compromissos

### **Fase 6: Automação e Integrações** ⚙️ **Planejada**

*   **Objetivo:** Automatizar processos manuais e integrar com sistemas externos.
*   **Funcionalidades Planejadas:**
    - Integração com sistemas de pagamento
    - Automação de cobranças recorrentes
    - API para integração com outros sistemas
    - Webhooks para notificações em tempo real

## **Desafios e Melhorias Identificadas**

1. **Desempenho:**
   - Otimização de consultas complexas
   - Implementação de cache para relatórios pesados
   - Melhorias na indexação do banco de dados

2. **Usabilidade:**
   - Refinamento da experiência mobile
   - Melhorias na acessibilidade
   - Tutoriais e ajuda contextual

3. **Segurança:**
   - Revisão de permissões e políticas de acesso
   - Auditoria de segurança completa
   - Implementação de autenticação em dois fatores

## **Conclusão**

O EventosPro evoluiu significativamente desde seu lançamento, transformando-se em uma ferramenta robusta e completa para gestão de eventos artísticos. Com uma base sólida e arquitetura escalável, o sistema está bem posicionado para incorporar novas funcionalidades e atender às necessidades em constante evolução dos usuários.

**Próximos Passos Imediatos:**
1. Finalizar o módulo de notificações
2. Implementar a exportação de relatórios em PDF/Excel
3. Concluir a migração para o novo sistema de autenticação
4. Realizar testes de carga e otimização de desempenho

Este documento será atualizado continuamente para refletir o progresso do projeto e as mudanças de prioridades.