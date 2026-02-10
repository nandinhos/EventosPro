# Catálogo de Rotas - EventosPro

> **174 Rotas** do sistema organizadas por módulo
> **Fonte**: `routes/web.php`
> **Versão**: 1.0 | **Atualizado**: 2026-02-10

---

## 📋 Índice Rápido

| Módulo | Rotas | Linha no routes/web.php |
|--------|-------|------------------------|
| **Autenticação** | 3 | 54-56 |
| **Usuários** | 7 | 48 |
| **Bookers** | 4 | 51, 93, 96-97 |
| **Artistas** | 5 | 78, 80-81 |
| **Custos Agência** | 7 | 87 |
| **Centros de Custo** | 5 | 90 |
| **Relatórios** | 14 | 59-75, 106-112, 120-121 |
| **Projeções** | 4 | 100, 103 |
| **Auditoria** | 12 | 115, 170-180 |
| **Fechamento Mensal** | 3 | 125-127 |
| **Gigs** | 22+ | 130-162 |
| **Test Report** | 3 | 165-167 |

---

## 🔐 Autenticação / Perfil (Breeze)

| Método | Rota | Descrição | Linha |
|--------|------|-----------|-------|
| GET | `/profile` | Editar perfil do usuário | 54 |
| PATCH | `/profile` | Atualizar perfil do usuário | 55 |
| DELETE | `/profile` | Excluir perfil do usuário | 56 |

---

## 👥 Usuários

**Resource**: `users` | **Linhas**: 48

| Método | Rota | Descrição |
|--------|------|-----------|
| GET | `/users` | Listar usuários |
| GET | `/users/create` | Formulário criar usuário |
| POST | `/users` | Criar usuário |
| GET | `/users/{user}` | Detalhes do usuário |
| GET | `/users/{user}/edit` | Formulário editar usuário |
| PUT/PATCH | `/users/{user}` | Atualizar usuário |
| DELETE | `/users/{user}` | Excluir usuário |

---

## 💼 Bookers

| Método | Rota | Descrição | Linha |
|--------|------|-----------|-------|
| GET | `/meu-desempenho` | Portal do booker (performance) | 51 |
| POST | `/bookers/events/{eventId}/commission` | Atualizar comissão do booker | 93 |
| GET | `/bookers/{booker}/export/pdf` | Exportar booker em PDF | 96 |
| GET | `/bookers/{booker}/export/excel` | Exportar booker em Excel | 97 |

---

## 🎭 Artistas

**Resource**: `artists` | **Linhas**: 78

| Método | Rota | Descrição | Linha |
|--------|------|-----------|-------|
| GET | `/artists` | Listar artistas | Resource |
| GET | `/artists/create` | Formulário criar artista | Resource |
| POST | `/artists` | Criar artista | Resource |
| GET | `/artists/{artist}` | Detalhes do artista | Resource |
| GET | `/artists/{artist}/edit` | Formulário editar artista | Resource |
| PUT/PATCH | `/artists/{artist}` | Atualizar artista | Resource |
| DELETE | `/artists/{artist}` | Excluir artista | Resource |
| POST | `/artists/payments/settle-batch` | Liquidar pagamentos de artistas em lote | 80 |
| PATCH | `/artists/payments/unsettle-batch` | Desfazer liquidações de artistas em lote | 81 |

---

## 💰 Custos da Agência

**Resource**: `agency-costs` | **Linhas**: 87

| Método | Rota | Descrição |
|--------|------|-----------|
| GET | `/agency-costs` | Listar custos da agência |
| GET | `/agency-costs/create` | Formulário criar custo |
| POST | `/agency-costs` | Criar custo |
| GET | `/agency-costs/{cost}` | Detalhes do custo |
| GET | `/agency-costs/{cost}/edit` | Formulário editar custo |
| PUT/PATCH | `/agency-costs/{cost}` | Atualizar custo |
| DELETE | `/agency-costs/{cost}` | Excluir custo |

---

## 🏷️ Centros de Custo

**Resource**: `cost-centers` (sem show) | **Linhas**: 90

| Método | Rota | Descrição |
|--------|------|-----------|
| GET | `/cost-centers` | Listar centros de custo |
| POST | `/cost-centers` | Criar centro de custo |
| GET | `/cost-centers/{center}/edit` | Formulário editar centro |
| PUT/PATCH | `/cost-centers/{center}` | Atualizar centro de custo |
| DELETE | `/cost-centers/{center}` | Excluir centro de custo |

---

## 📊 Relatórios

### Relatórios Gerais

| Método | Rota | Descrição | Linha |
|--------|------|-----------|-------|
| GET | `/reports` | Índice de relatórios | 59 |
| GET | `/reports/overview/export/{format}` | Exportar visão geral (pdf/excel) | 61 |
| GET | `/reports/delinquency` | Relatório de inadimplência | 63 |
| GET | `/reports/delinquency/export/pdf` | Exportar inadimplência em PDF | 65 |

### Relatórios de Comissões

| Método | Rota | Descrição | Linha |
|--------|------|-----------|-------|
| POST | `/reports/commissions/settle-batch` | Liquidar comissões de bookers em lote | 67 |
| PATCH | `/reports/commissions/unsettle-batch` | Desfazer liquidações de bookers em lote | 69 |

### Relatórios de Pagamentos de Artistas

| Método | Rota | Descrição | Linha |
|--------|------|-----------|-------|
| POST | `/reports/artist-payments/settle-batch` | Liquidar pagamentos de artistas em lote | 71 |
| PATCH | `/reports/artist-payments/unsettle-batch` | Desfazer liquidações de artistas em lote | 73 |

### Exportações Diversas

| Método | Rota | Descrição | Linha |
|--------|------|-----------|-------|
| GET | `/reports/export/{type}/{format}` | Exportações diversos formatos | 75 |

### Relatórios de Performance

| Método | Rota | Descrição | Linha |
|--------|------|-----------|-------|
| GET | `/reports/performance` | Relatório de performance geral | 106 |
| GET | `/reports/performance/export` | Exportar performance em PDF | 107 |
| GET | `/reports/artist-performance` | Relatório de performance de artistas | 110 |
| GET | `/reports/artist-performance/export/pdf` | Exportar performance artistas PDF | 111 |
| GET | `/reports/artist-performance/export/excel` | Exportar performance artistas Excel | 112 |

### Relatórios de Vencimentos

| Método | Rota | Descrição | Linha |
|--------|------|-----------|-------|
| GET | `/reports/due-dates` | Relatório de vencimentos | 120 |
| GET | `/reports/due-dates/export/pdf` | Exportar vencimentos em PDF | 121 |

---

## 📈 Projeções Financeiras

| Método | Rota | Descrição | Linha |
|--------|------|-----------|-------|
| GET | `/projections` | Índice de projeções financeiras | 100 |
| GET | `/projections/debug` | Debug de projeções | 103 |

---

## 🔍 Auditoria

| Método | Rota | Descrição | Linha |
|--------|------|-----------|-------|
| GET | `/auditoria` | Índice de auditoria | 115 |
| GET | `/auditoria/{gig}` | Detalhes de auditoria de um gig | 116 |
| GET | `/auditoria/export/csv` | Exportar auditoria em CSV | 117 |
| GET | `/audit/data-audit` | Auditoria de dados | 170 |
| POST | `/audit/run-data-audit` | Executar auditoria de dados | 171 |
| POST | `/audit/get-issues` | Listar problemas encontrados | 172 |
| POST | `/audit/apply-fix` | Aplicar correção específica | 173 |
| POST | `/audit/apply-bulk-fix` | Aplicar correção em massa | 174 |
| GET | `/audit/available-audits` | Listar auditorias disponíveis | 177 |
| GET | `/audit/dashboard` | Painel de auditoria | 178 |
| POST | `/audit/run-specific-audit` | Executar auditoria específica | 179 |
| POST | `/audit/run-all-audits` | Executar todas as auditorias | 180 |

---

## 📅 Fechamento Mensal

| Método | Rota | Descrição | Linha |
|--------|------|-----------|-------|
| GET | `/financeiro/fechamento-mensal` | Índice de fechamento mensal | 125 |
| GET | `/financeiro/fechamento-mensal/exportar/pdf` | Exportar fechamento em PDF | 126 |
| GET | `/financeiro/fechamento-mensal/exportar` | Exportar fechamento (formato genérico) | 127 |

---

## 🎤 Gigs (Módulo Principal)

**Resource**: `gigs` | **Linhas**: 130

### CRUD de Gigs

| Método | Rota | Descrição |
|--------|------|-----------|
| GET | `/gigs` | Listar gigs |
| GET | `/gigs/create` | Formulário criar gig |
| POST | `/gigs` | Criar gig |
| GET | `/gigs/{gig}` | Detalhes do gig |
| GET | `/gigs/{gig}/edit` | Formulário editar gig |
| PUT/PATCH | `/gigs/{gig}` | Atualizar gig |
| DELETE | `/gigs/{gig}` | Excluir gig |

### Operações Específicas

| Método | Rota | Descrição | Linha |
|--------|------|-----------|-------|
| GET | `/gigs/{gig}/request-nf` | Formulário de solicitação de NF | 131 |
| GET | `/gigs/{gig}/debug-financials` | Debug financeiro do gig | 162 |

### Rotas Aninhadas: Pagamentos

**Prefix**: `/gigs/{gig}/payments` | **Linhas**: 137-142

| Método | Rota | Descrição |
|--------|------|-----------|
| POST | `/gigs/{gig}/payments` | Criar parcela de pagamento |
| GET | `/gigs/{gig}/payments/{payment}/edit` | Editar parcela de pagamento |
| PUT/PATCH | `/gigs/{gig}/payments/{payment}` | Atualizar parcela de pagamento |
| DELETE | `/gigs/{gig}/payments/{payment}` | Excluir parcela de pagamento |
| PATCH | `/gigs/{gig}/payments/{payment}/confirm` | Confirmar pagamento do cliente |
| PATCH | `/gigs/{gig}/payments/{payment}/unconfirm` | Desconfirmar pagamento do cliente |

### Rotas Aninhadas: Custos

**Resource**: `/gigs/{gig}/costs` (sem index/show) | **Linhas**: 145-149

| Método | Rota | Descrição |
|--------|------|-----------|
| POST | `/gigs/{gig}/costs` | Criar custo da gig |
| GET | `/gigs/{gig}/costs/{cost}/edit` | Editar custo da gig |
| PUT/PATCH | `/gigs/{gig}/costs/{cost}` | Atualizar custo da gig |
| DELETE | `/gigs/{gig}/costs/{cost}` | Excluir custo da gig |

### Operações de Custo

| Método | Rota | Descrição | Linha |
|--------|------|-----------|-------|
| PATCH | `/gigs/{gig}/costs/{cost}/confirm` | Confirmar custo | 146 |
| PATCH | `/gigs/{gig}/costs/{cost}/unconfirm` | Desconfirmar custo | 147 |
| PATCH | `/gigs/{gig}/costs/{cost}/toggle-invoice` | Alternar status de nota fiscal | 148 |
| GET | `/gigs/{gig}/costs-json` | Listar custos em JSON | 149 |

### Rotas Aninhadas: Settlements (Acertos)

| Método | Rota | Descrição | Linha |
|--------|------|-----------|-------|
| POST | `/gigs/{gig}/settle-artist` | Liquidar pagamento ao artista | 152 |
| POST | `/gigs/{gig}/settle-booker` | Liquidar comissão ao booker | 153 |
| PATCH | `/gigs/{gig}/unsettle-artist` | Desfazer liquidação do artista | 154 |
| PATCH | `/gigs/{gig}/unsettle-booker` | Desfazer liquidação do booker | 155 |

---

## 🧪 Test Report

| Método | Rota | Descrição | Linha |
|--------|------|-----------|-------|
| GET | `/test-report` | Índice de relatório de teste | 165 |
| POST | `/test-report/run` | Executar relatório de teste | 166 |
| GET | `/test-report/export` | Exportar relatório de teste | 167 |

---

## 🎯 Padrões de Nomenclatura de Rotas

### Convenção para Gigs (Nível 1)
- `{resource}` → CRUD completo
- `/{resource}/{id}/action` → Ação específica
- `/{resource}/{id}/nested-resource` → Recursos aninhados

### Convenção para Pagamentos (Nível 2 em Gig)
- `/gigs/{gig}/payments` → Criar
- `/gigs/{gig}/payments/{payment}/edit` → Editar
- `/gigs/{gig}/payments/{payment}` → Atualizar/Deletar
- `/gigs/{gig}/payments/{payment}/confirm` → Ação específica

### Convenção para Custos (Nível 2 em Gig)
- `/gigs/{gig}/costs` → CRUD (sem index/show)
- `/gigs/{gig}/costs/{cost}/confirm` → Ação específica
- `/gigs/{gig}/costs-json` → Via JSON

### Convenção para Settlements
- `/gigs/{gig}/settle-artist` → Liquidar artista
- `/gigs/{gig}/unsettle-artist` → Desfazer liquidação artista

### Convenção para Relatórios
- `/reports/{report-type}` → Listar relatório
- `/reports/{report-type}/export/{format}` → Exportar

### Convenção para Auditoria
- `/auditoria/{gig}` → Auditoria de gig específico
- `/audit/{action}` -> Ações de auditoria

---

## 📐 Estrutura de Middlewares

### Padrão de Autenticação
- `auth` → Usuário autenticado
- `auth.basic` → Autenticação básica

### Padrão de Permissões
- `can:view,gig` → Verificar permissão específica
- `can:update,gig` → Verificar permissão update
- `can:delete,gig` → Verificar permissão delete
- Baseado em Policies em `app/Policies/`

### Padrão de Throttling
- `throttle:60,1` → 60 requisições por minuto

---

## 🗺️ Fluxo Principal de Rotas

### Fluxo de Criação de Gig
1. `GET /gigs/create` → Formulário
2. `POST /gigs` → Criar gig
3. `POST /gigs/{gig}/payments` → Adicionar parcelas
4. `POST /gigs/{gig}/costs` → Adicionar custos
5. `POST /gigs/{gig}/settle-artist` → Liquidar artista

### Fluxo de Relatório de Vencimentos
1. `GET /reports/due-dates` → Listar vencimentos
2. `GET /reports/due-dates/export/pdf` → Exportar PDF

### Fluxo de Auditoria
1. `GET /auditoria` → Lista gigs
2. `GET /auditoria/{gig}` → Detalhes auditoria
3. `GET /auditoria/export/csv` → Exportar CSV

---

## ⚠️ Importante

1. **Todas as rotas passam por middleware de autenticação**
2. **Policies verificam permissões específicas**
3. **Soft deletes aplicados a todas as rotas de delete**
4. **Relatórios suportam exportação em PDF/Excel/CSV**
5. **Validações via Form Requests em `app/Http/Requests/`**

---

**Versão**: 1.0
**Fonte**: `routes/web.php` (174 rotas)
**Próxima Referência**: `.aidev/context/architecture-contracts.md`