# Seeder de Testes de Auditoria

Esta documentação explica como usar a `AuditTestSeeder` para criar dados de teste específicos para validar o sistema de auditoria do EventosPro.

## 📋 Visão Geral

A `AuditTestSeeder` cria 15 casos de teste diferentes que cobrem todos os tipos de discrepâncias e problemas que o sistema de auditoria deve detectar, além de casos corretos para validação.

## 🚀 Como Executar

### Executar apenas a seeder de testes:
```bash
./vendor/bin/sail artisan db:seed --class=AuditTestSeeder
```

### Executar auditoria após criar os dados:
```bash
./vendor/bin/sail artisan gig:audit-data --scan-only
```

### Acessar interface web:
```
http://localhost/audit/data-audit
```

## 📊 Casos de Teste Criados

### 🔴 Discrepâncias Financeiras

1. **TEST-DISC-001** - Falta lançamento
   - Gig sem pagamentos cadastrados
   - Valor: R$ 50.000,00
   - Deve detectar: `falta_lancamento`

2. **TEST-DISC-002** - Divergência - Falta R$ 10.000
   - Contrato: R$ 80.000,00
   - Pagamentos: R$ 70.000,00 (duas parcelas)
   - Deve detectar: `discrepancia_valores`

3. **TEST-DISC-003** - Divergência - Excesso de R$ 5.000
   - Contrato: R$ 60.000,00
   - Pagamentos: R$ 65.000,00
   - Deve detectar: `discrepancia_valores`

4. **TEST-DISC-004** - Parcelas em USD com taxa incorreta
   - Contrato: R$ 100.000,00 (BRL)
   - Pagamento: $15.000,00 (USD) com taxa errada
   - Deve detectar: `discrepancia_valores`

### 🟡 Status Inconsistentes

5. **TEST-STATUS-001** - Status "pago" com parcelas não confirmadas
   - Status: `pago`
   - Problema: Uma parcela sem `confirmed_at`
   - Deve detectar: `gigs_pago_com_parcelas_abertas`

6. **TEST-STATUS-002** - Evento passado não marcado como pago
   - Evento: 30 dias atrás
   - Status: `a_vencer`
   - Pagamento confirmado
   - Deve detectar: `gigs_vencidas`

7. **TEST-STATUS-003** - Status artista/booker inconsistente
   - Status geral: `pago`
   - Status artista: `pago`
   - Status booker: `pendente`
   - Deve detectar: inconsistência de status

### 🟠 Campos Obrigatórios

8. **TEST-FIELDS-001** - Cache value zerado
   - Valor: R$ 0,00
   - Deve detectar: `required_field`

9. **TEST-FIELDS-002** - Currency vazia
   - Currency: `''` (string vazia)
   - Deve detectar: `required_field`

10. **TEST-FIELDS-003** - Data suspeita
    - Gig date: 1900-01-01
    - Deve detectar: `date_logic`

### 📅 Datas Inválidas

11. **TEST-DATE-001** - Contract date posterior à gig date
    - Contract: hoje + 50 dias
    - Gig: hoje + 30 dias
    - Deve detectar: `date_logic`

12. **TEST-DATE-002** - Evento vencido com status incorreto
    - Evento: 45 dias atrás
    - Status: `vencido`
    - Deve detectar: `gigs_vencidas`

### ✅ Casos Corretos (Controle)

13. **TEST-CORRECT-001** - Caso totalmente correto (futuro)
    - Evento futuro com pagamentos corretos
    - Não deve gerar problemas

14. **TEST-CORRECT-002** - Caso correto (totalmente pago)
    - Evento passado, pago corretamente
    - Não deve gerar problemas

15. **TEST-CORRECT-003** - Caso correto (pagamento parcial)
    - Evento futuro com adiantamento confirmado
    - Não deve gerar problemas

## 🧪 Testando Correções

### Correções Individuais
1. Acesse a interface web: `/audit/data-audit`
2. Execute a auditoria
3. Clique em "Corrigir" em um item específico
4. Verifique se a correção foi aplicada

### Correções em Massa
1. Na interface web, selecione múltiplos itens
2. Clique em "Corrigir Selecionados"
3. Verifique se todas as correções foram aplicadas

### Select All
1. Use o checkbox "Selecionar Todos"
2. Clique em "Corrigir Selecionados"
3. Confirme que todos os itens corrigíveis foram processados

## 🔧 Comandos Úteis

### Limpar dados de teste:
```bash
./vendor/bin/sail artisan tinker
>>> App\Models\Gig::where('contract_number', 'LIKE', 'TEST-%')->delete();
```

### Recriar dados de teste:
```bash
./vendor/bin/sail artisan db:seed --class=AuditTestSeeder
```

### Executar auditoria completa:
```bash
./vendor/bin/sail artisan gig:audit-data
```

### Ver relatório JSON:
```bash
cat storage/logs/gig_audit_*.json | jq .
```

## 📈 Resultados Esperados

Após executar a seeder e a auditoria, você deve ver:

- **9 problemas detectados** (aproximadamente)
- **6 warnings** de payment_status_rule
- **1-2 errors** de required_field
- **1 warning** de date_logic
- **3 casos corretos** sem problemas

## 🎯 Cenários de Teste Recomendados

1. **Teste Individual**: Corrija um item por vez
2. **Teste Seleção Múltipla**: Selecione 3-5 itens e corrija
3. **Teste Select All**: Selecione todos e corrija
4. **Teste Filtros**: Use filtros por categoria/severidade
5. **Teste Paginação**: Se houver muitos itens
6. **Teste Refresh**: Atualize a página após correções

## ⚠️ Observações Importantes

- Os dados de teste são identificados pelo prefixo `TEST-` no contract_number
- A seeder remove automaticamente dados de teste anteriores
- Alguns casos podem não gerar problemas se as regras de negócio mudarem
- Execute sempre em ambiente de desenvolvimento/teste
- Faça backup do banco antes de executar correções em massa

## 🐛 Troubleshooting

### Erro de conexão com banco:
```bash
./vendor/bin/sail up -d
```

### Seeder não encontra artistas/bookers:
```bash
./vendor/bin/sail artisan db:seed --class=ArtistSeeder
./vendor/bin/sail artisan db:seed --class=BookerSeeder
```

### Problemas não detectados:
- Verifique se as regras de auditoria estão ativas
- Confirme se os dados foram criados corretamente
- Execute a auditoria com `--verbose` para mais detalhes