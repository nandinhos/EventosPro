## **Visão Geral do Sistema EventosPro (Estado Atual)**

**Propósito Principal:**

O EventosPro, em seu estado atual, é um sistema de gestão projetado para auxiliar no controle e organização de eventos artísticos (Gigs/Datas), com um foco crescente na gestão financeira detalhada associada a cada evento. Ele visa substituir controles manuais (como planilhas complexas) por uma plataforma centralizada, permitindo um acompanhamento mais eficiente desde o agendamento da data até o acerto financeiro com artistas e bookers, além de fornecer dados para relatórios e tomadas de decisão.

**Contexto de Uso:**

Ideal para agências de artistas, bookers independentes ou qualquer profissional que gerencie a agenda e os aspectos financeiros de apresentações artísticas. O sistema busca trazer clareza sobre cachês, despesas, comissões e o status de pagamento de cada transação.

---

### **Módulos Implementados e Suas Funções:**

1.  **Dashboard (Visão Geral e Acesso Rápido):**
    *   **Função:** Servir como a página inicial após o login, oferecendo um panorama rápido do estado atual dos negócios.
    *   **Implementado:**
        *   **KPIs (Indicadores Chave):**
            *   Contagem de Gigs Ativas/Futuras.
            *   Contagem de Gigs com Pagamento de Cliente Vencido.
            *   Contagem de Gigs com Pagamento de Artista Pendente.
            *   Contagem de Gigs com Pagamento de Booker Pendente.
        *   **Resumo Financeiro (Mês Atual):**
            *   Total de Cachê Bruto (BRL) das Gigs com data do evento no mês corrente.
            *   Total Estimado de Comissão da Agência no mês.
            *   Total Estimado de Comissão de Bookers no mês.
        *   **Acesso Rápido:**
            *   Lista das próximas 5 Gigs, com link para seus detalhes.
        *   **Gráfico (Em Desenvolvimento):**
            *   Placeholder para um gráfico de Faturamento Mensal (Gigs Pagas/Realizadas), com a lógica de busca de dados já iniciada no backend.
    *   **Propósito no Fluxo:** Dar ao usuário uma noção imediata das prioridades, pendências e um resumo da saúde financeira do período atual, com links diretos para as listagens filtradas de Gigs.

2.  **Gerenciamento de Gigs/Datas (Módulo Central):**
    *   **Função:** O coração do sistema. Permite cadastrar, visualizar, editar e excluir cada "data" ou apresentação artística agendada, que é a unidade principal de negociação e acompanhamento.
    *   **Tabela Principal:** `gigs`.
    *   **Implementado:**
        *   **Listagem (`gigs.index`):**
            *   Tabela exibindo Gigs com colunas chave: Data do Evento, Artista/Booker (combinado), Local/Evento, Moeda Original, Cachê Original, Status de Pagamento do Cliente, Status de Pagamento ao Artista, Status de Pagamento ao Booker.
            *   Funcionalidade de **ordenação** clicável para a maioria das colunas.
            *   Sistema de **filtragem** por: Busca Livre (Nº Contrato, Artista, Local), Artista específico, Booker específico, Status de Pagamento do Cliente, Moeda, Período da Data do Evento.
            *   Destaque visual para linhas de Gigs com pagamento de cliente vencido ou moeda estrangeira.
            *   Paginação dos resultados.
            *   Botões de ação: "Ver Detalhes", "Editar", "Excluir (Soft Delete)".
        *   **Criação (`gigs.create` e `gigs.store`):**
            *   Formulário para registrar uma nova Gig com todos os seus dados principais: Artista, Booker (opcional), Data do Evento, Local/Detalhes, Nº Contrato (opcional), Data Contrato (opcional), Status do Contrato (formal), Valor do Cachê Bruto, Moeda.
            *   Campos para definir o **tipo de comissão do booker** (percentual ou fixa) e a **taxa/valor** correspondente.
            *   (Lógica para comissão da agência ainda é um default ou a ser refinada no form).
            *   Associação de Tags.
            *   Validação robusta dos dados via `StoreGigRequest`.
            *   Ao salvar, as taxas/valores de comissão são armazenados corretamente.
        *   **Edição (`gigs.edit` e `gigs.update`):**
            *   Formulário pré-preenchido com os dados da Gig.
            *   Permite alterar todos os campos editáveis.
            *   Validação via `UpdateGigRequest` (incluindo verificação de `contract_number` único, ignorando o registro atual).
            *   Recálculo e salvamento correto das comissões.
        *   **Visualização de Detalhes (`gigs.show`):**
            *   Página rica em informações sobre uma Gig específica.
            *   **Card de Informações Gerais:** Exibe todos os dados cadastrais da Gig, incluindo Artista, Booker, datas, local, contrato, status do contrato e tags.
            *   **Card de Resumo Financeiro:** Mostra o Valor do Contrato (Cachê Bruto), Total Recebido daquela Gig (na moeda original da Gig, baseado nos pagamentos confirmados), Saldo Pendente, e os status gerais de pagamento (Cliente, Artista, Booker).
            *   **Card de Parcelas/Recebimentos (Tabela `payments`):**
                *   Lista todas as parcelas de pagamento *previstas* para o cliente pagar à agência.
                *   Exibe: Descrição, Valor Devido, Vencimento, Status Inferido (Confirmado, Vencido, A Vencer).
                *   Detalhes do recebimento se confirmado (valor, data, quem confirmou).
                *   **CRUD de Parcelas Previstas:** Botão para adicionar novas parcelas. Botões para editar (se não confirmado), excluir (se não confirmado) parcelas existentes.
                *   **Confirmação de Recebimento:** Botão para abrir um modal e registrar o recebimento efetivo de uma parcela (data, valor, moeda recebida, câmbio, notas). Isso atualiza o status da parcela e, via listener (a ser implementado), o status geral de pagamento da Gig.
                *   **Reversão de Confirmação:** Botão para desconfirmar um pagamento já registrado.
            *   **Card de Despesas/Custos (Tabela `gig_costs`):**
                *   Lista todas as despesas associadas à Gig, agrupadas por Centro de Custo.
                *   Exibe totais de despesas confirmadas e pendentes.
                *   Para cada despesa: Descrição, Valor, Pagador, Status (Confirmada/Pendente).
                *   **CRUD de Despesas:** Botão para adicionar nova despesa (com Centro de Custo, valor, descrição, pagador). Botões para editar (se não confirmada), excluir (se não confirmada).
                *   **Confirmação de Despesa:** Botão para marcar uma despesa como confirmada (double check).
                *   **Reversão de Confirmação:** Botão para reverter a confirmação de uma despesa.
            *   **Card de Acertos Financeiros (Pagamentos Efetuados - Tabela `settlements`):**
                *   **Acerto com Artista:** Mostra o cálculo do valor líquido estimado a pagar ao artista (Cachê Bruto BRL - Despesas Confirmadas BRL - Comissão Agência BRL). Exibe status do pagamento ao artista. Botão para registrar o pagamento efetivo ao artista (abrindo modal para data, valor, comprovante, notas) que atualiza o status da Gig e cria/atualiza registro em `settlements`. Botão para reverter o pagamento ao artista. Botão para "Solicitar/Ver NF Artista" que leva a uma página de detalhamento para a NF.
                *   **Acerto com Booker:** Mostra o cálculo do valor da comissão do booker (baseado no Cachê Líquido Pós-Despesas). Exibe status do pagamento da comissão. Botão para registrar o pagamento da comissão (abrindo modal) que atualiza o status da Gig e cria/atualiza `settlements`. Botão para reverter o pagamento da comissão.
            *   **Card de Histórico (Activity Log):** Lista as atividades recentes relacionadas à Gig (ainda sem a lógica de registro automático implementada, mas a estrutura está lá).
        *   **Exclusão (`gigs.destroy`):**
            *   Remove a Gig (Soft Delete).
            *   Registros relacionados em `payments`, `gig_costs`, `settlements` são tratados via `cascadeOnDelete` ou precisam de lógica de exclusão manual se o cascade não for desejado para todos.
    *   **Propósito no Fluxo:** É onde a maior parte do trabalho de gestão de uma "data" acontece, desde o cadastro inicial, acompanhamento financeiro (o que entrou da cliente, o que saiu de despesa) até o fechamento dos pagamentos internos.

3.  **Gerenciamento de Artistas (`artists`):**
    *   **Função:** Cadastrar e gerenciar a lista de artistas com os quais a agência trabalha.
    *   **Implementado:** CRUD básico completo (Listar, Criar, Editar, Excluir - Soft Delete). Associação de Tags. Exibição de contagem de Gigs na listagem. Página de detalhes (`artists.show`) com cards de métricas (Total Gigs, Cachê Recebido/Pendente aproximado pelo artista) e lista de Gigs associadas.
    *   **Propósito no Fluxo:** Manter um cadastro central de artistas para serem selecionados ao criar Gigs e para futuros relatórios de desempenho por artista.

4.  **Gerenciamento de Bookers (`bookers`):**
    *   **Função:** Cadastrar e gerenciar a lista de bookers da agência.
    *   **Implementado:** CRUD básico completo (Listar, Criar, Editar, Excluir - Soft Delete). Campo para taxa de comissão padrão. Exibição de contagem de Gigs na listagem. Página de detalhes (`bookers.show`) com cards de métricas (Total Gigs, Comissão Recebida/Pendente) e lista de Gigs associadas.
    *   **Propósito no Fluxo:** Manter um cadastro de bookers para serem selecionados ao criar Gigs, para cálculo de comissões e para futuros relatórios de desempenho por booker.

5.  **Gerenciamento de Centros de Custo (`cost_centers`):**
    *   **Função:** Definir as categorias de despesas (Catering, Logistics, Hotel).
    *   **Implementado:** Tabela e Modelo criados. Seeder para popular com valores iniciais. (CRUD ainda não implementado na interface, mas a base está lá).
    *   **Propósito no Fluxo:** Permitir a categorização das despesas das Gigs para melhor controle financeiro e relatórios.

6.  **Gerenciamento de Tags (`tags` e `taggables`):**
    *   **Função:** Permitir a categorização flexível de Gigs e Artistas.
    *   **Implementado:** Tabelas e Modelos criados. Seeder para tags iniciais. Funcionalidade de associar tags no CRUD de Gigs e Artistas. (CRUD de Tags em si ainda não implementado na interface).
    *   **Propósito no Fluxo:** Ajudar na organização, busca e filtragem de Gigs e Artistas.

**Funcionalidades de Suporte Implementadas:**

*   **Autenticação:** Login, Registro, Logout (via Laravel Breeze).
*   **Layout Responsivo Básico:** Com Sidebar retrátil.
*   **Componente de Badge de Status:** Para visualização colorida dos diversos status.
*   **Soft Deletes:** Implementado nos modelos principais.
*   **Form Requests:** Para validação robusta nos formulários de Gig, Artista, Booker.

**O que Falta (Principais Próximos Passos com Foco Financeiro):**

*   **Finalizar CRUD de Pagamentos Recebidos (`payments`):** Garantir que a edição de parcelas previstas funcione bem e que a lógica de atualização do status da Gig após confirmação/desconfirmação de pagamento esteja 100%.
*   **Finalizar CRUD de Despesas da Gig (`gig_costs`):** Garantir que o formulário de adicionar/editar despesa (seja modal ou página) esteja funcionando perfeitamente, incluindo o campo "Pagador".
*   **Implementar Lógica de Cálculo de Comissão nos Accessors:** Garantir que `agency_commission_value`, `booker_commission_value` e `liquid_commission_value` no modelo `Gig` sejam calculados corretamente e dinamicamente pelos accessors, usando a `commission_base_brl` (que já considera as despesas confirmadas).
*   **Refinar e Testar Tela de Solicitação de NF Artista (`gigs.request-nf`).**
*   **Implementar Módulo de Fechamento Mensal e Relatórios.**
*   **Implementar Módulo de Projeções.**
*   **Implementar Auditoria (`activity_logs`).**
*   **Implementar Notificações.**

