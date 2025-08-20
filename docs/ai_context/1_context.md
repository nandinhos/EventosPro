# Contexto do Projeto EventosPro

## Visão Geral

O **EventosPro** é um sistema de gestão especializado para agências de artistas e bookers que gerenciam eventos artísticos (Gigs/Datas). O sistema foi desenvolvido para substituir controles manuais complexos (como planilhas) por uma plataforma centralizada que permite acompanhamento eficiente desde o agendamento até o acerto financeiro completo.

## Propósito Principal

Centralizar e automatizar a gestão de eventos artísticos, oferecendo:
- Controle detalhado de cachês, despesas e comissões
- Acompanhamento de status de pagamentos (cliente → agência → artista/booker)
- Relatórios financeiros para tomada de decisão
- Substituição de planilhas manuais por sistema integrado

## Requisitos Funcionais Identificados

### 1. Gestão de Gigs (Módulo Central)
- **CRUD completo** de eventos/apresentações artísticas
- **Gestão financeira detalhada** por evento:
  - Controle de parcelas de recebimento do cliente
  - Registro e categorização de despesas por centro de custo
  - Cálculo automático de comissões (agência e booker)
  - Acertos financeiros com artistas e bookers
- **Sistema de status** para acompanhamento de pagamentos
- **Filtragem e ordenação** avançada na listagem
- **Soft delete** para preservação de histórico

### 2. Gestão de Entidades
- **Artistas**: Cadastro com métricas de performance
- **Bookers**: Cadastro com configuração de comissões
- **Centros de Custo**: Categorização de despesas
- **Tags**: Sistema flexível de categorização

### 3. Dashboard e Relatórios
- **KPIs em tempo real**: Gigs ativas, pagamentos vencidos, pendências
- **Resumo financeiro mensal**: Cachês, comissões estimadas
- **Relatórios especializados**:
  - Relatório de vencimentos com agrupamento inteligente
  - Relatórios de inadimplência
  - Exportação em PDF com layout profissional
  - Auditoria financeira de Gigs

### 4. Sistema de Autenticação e Permissões
- Autenticação via Laravel Breeze
- Sistema de permissões com Spatie Laravel Permission
- Controle de acesso baseado em roles

### 5. Funcionalidades Financeiras Avançadas
- **Conversão de moedas** com taxas de câmbio
- **Cálculos automáticos**:
  - Cachê Bruto em BRL
  - Comissão da Agência
  - Valor líquido para artista (pós-despesas)
  - Comissão do booker
- **Rastreamento de pagamentos** com confirmação manual
- **Gestão de despesas** por categoria

## Público-Alvo

### Usuários Primários
- **Agências de artistas** que gerenciam múltiplos talentos
- **Bookers independentes** que intermediam eventos
- **Gestores financeiros** de empresas do entretenimento

### Casos de Uso Principais
1. **Agendamento e controle de eventos artísticos**
2. **Gestão financeira completa** de cada apresentação
3. **Acompanhamento de inadimplência** de clientes
4. **Cálculo e pagamento de comissões**
5. **Geração de relatórios** para análise de performance
6. **Auditoria financeira** de eventos realizados

## Benefícios Identificados

- **Centralização**: Todas as informações em um local
- **Automatização**: Cálculos financeiros automáticos
- **Transparência**: Status claro de todos os pagamentos
- **Relatórios**: Dados estruturados para decisões
- **Histórico**: Preservação completa de informações
- **Escalabilidade**: Suporte a múltiplos artistas e eventos

## Contexto Técnico

Sistema web desenvolvido em Laravel 12 com interface moderna usando Tailwind CSS e Alpine.js, focado em usabilidade e performance para gestão de dados complexos do setor de entretenimento.