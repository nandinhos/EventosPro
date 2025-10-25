
# Resumo Executivo e Especificação Definitiva para o Agente de IA de Projeção Financeira

## I. Missão Principal do Agente de IA

Garantir a precisão da Projeção Financeira da agência, desdobrando as regras de comissionamento nos dados da tabela `gigs` e `gig_costs`, e gerando relatórios de **DRE Projetada (Competência)** e **Fluxo de Caixa Projetado (Caixa)**.

## II. Mapeamento de Dados e Ajustes Necessários no DB

A tabela `gigs` e `gig_costs` contêm os dados centrais.

**Métrica Financeira**

**Fonte de Dados (Tabela/Coluna)**

**Uso no Cálculo**

**Observação/Ação do Agente de IA**

**Cachê Bruto (CB)**

`gigs.cache_value` 

Base da receita.

**OK.**

**Despesas Variáveis (DV)**

`gig_costs.value` (somado) 

Subtraído do CB para obter o CL.

**Agregação:** Somar `gig_costs.value` onde `is_confirmed = 1`  e `gig_id` é o ID do GIG.

**Comissão Artista (%)**

Fixa em 80%.

Aplicada ao Cachê Líquido.

**HARD CODE:**  $0.80$ (A não ser que `artists` tenha uma coluna para isso, o que não foi encontrado).

**Comissão Agência (%)**

Fixa em 20%.

Aplicada ao Cachê Líquido (Base para o Booker).

**HARD CODE:** $0.20$.

**Comissão Booker (%)**

`gigs.booker_commission_rate` 

Aplicada à Receita Bruta Agência (RBA - 20% CL).

**Validação:** O Agente deve garantir que o valor seja lido como **porcentagem sobre a RBA**, e não sobre o CL.

**Entradas (Caixa)**

`payments.received_value_actual`

Usado para a Projeção de Fluxo de Caixa.

**Projeção:** Usar `payments.received_date_actual` para determinar o mês real de entrada (Regime de Caixa).

**Custo Fixo Médio (CFM)**

Não existe tabela dedicada.

Deduzido da RLRA Total para o Resultado Operacional.

**Ação Essencial:** O Agente **DEVE sugerir a criação da tabela `agency_fixed_costs`** para armazenar e rastrear o CFM $(\text{R\$ } 43.325,42 \text{ - exemplo})$.

## III. Métricas Reais e KPIs de Projeção

O Agente deve priorizar os seguintes cálculos e KPIs, extraindo os dados das colunas mapeadas acima:

### A. Cálculo no Nível do Evento (Margem de Contribuição)

1.  Cachê Líquido (CL):
    
    $$CL = \text{gigs.cache\_value} - \sum (\text{gig\_costs.value onde confirmed}=1)$$
    
2.  Receita Bruta Agência (RBA):
    
    $$\text{RBA} = 0.20 \times CL$$
    
3.  Custo Booker (CBK):
    
    $$\text{CBK} = (\text{gigs.booker\_commission\_rate} / 100) \times \text{RBA}$$
    
4.  Receita Líquida Real da Agência (RLRA):
    
    $$\text{RLRA} = \text{RBA} - \text{CBK} \quad (\text{Esta é a Margem de Contribuição do Evento})$$
    

### B. Consolidado Mensal (DRE e Fluxo de Caixa)

**Relatório**

**Linha**

**Fato Gerador (Mês de)**

**Cálculo**

**DRE Projetada**

Receita RLRA Total

**Execução (Competência)**

Soma de todas as **RLRA** dos GIGs com `gigs.gig_date` no mês.

Resultado Operacional

**Execução (Competência)**

$\text{Receita RLRA Total} - \text{CFM (R\$ 43.325,42 projetado)}$

**Fluxo de Caixa**

Entradas (Recebimento)

**Recebimento (Caixa)**

Soma de todos os `payments.received_value_actual` com `payments.received_date_actual` no mês.

Saídas (Artistas/Booker)

**Execução (Competência)**

Soma dos **Custo Artista** e **Custo Booker** dos GIGs executados no mês.

### C. KPIs Chave

-   **Ticket Médio (TM):** $\frac{\text{Soma de gigs.cache\_value (no período)}}{\text{Total de GIGs realizados (no período)}}$
    
-   **Ponto de Equilíbrio em Valor (RLRA):** $\text{R\$ } 43.325,42$ (CFM)
    

## IV. Diretrizes e Exemplo de Código para o Agente de IA

O Agente de IA deve priorizar a refatoração do `ProjecaoFinanceiraService` para refletir o mapeamento de DB e as regras.