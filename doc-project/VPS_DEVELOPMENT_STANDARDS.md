# Padrões de Desenvolvimento para VPS - EventosPro

Este documento detalha decisões técnicas e padrões de infraestrutura adotados para garantir a estabilidade da aplicação em ambiente de produção (VPS).

## 1. Porta da Aplicação (HTTP)

### Contexto
A aplicação utiliza **Laravel Sail** (Docker). Em ambientes de desenvolvimento, é comum usar portas como 8000 ou 8080. No entanto, para a VPS da EventosPro, o padrão estabelecido é a **porta 80**.

### Padrão Adotado
- **docker-compose.yml**: O mapeamento deve ser `'${APP_PORT:-80}:80'`.
- **Dockerfile**: O comando do Supervisor (`SUPERVISOR_PHP_COMMAND`) deve servir a aplicação explicitamente na porta **80** (`--port=80`).
- **Configuração**: Evitamos servir internamente no container em 8000 para manter a simplicidade do mapeamento 80:80, evitando confusões de redirecionamento.

> [!IMPORTANT]
> Se houver necessidade de alterar para 8000, ambos os arquivos (`docker-compose.yml` e `Dockerfile`) devem ser sincronizados, mas o padrão VPS deve permanecer **80**.

## 2. Visibilidade de Módulos (Sidebar & Permissões)

### O Problema do "Nesting"
Identificamos um erro comum onde links de novos módulos (ex: Backups) eram inseridos dentro de blocos `@can` de outros módulos (ex: Centro de Custo). Isso criava uma dependência oculta: o usuário precisava de ambas as permissões para ver o link.

### Padrão de Implementação na Sidebar
1. **Independência**: Cada módulo independente deve ter seu próprio check de permissão.
2. **Encapsulamento de Seção**: Se uma seção (ex: "Configurações") engloba vários módulos, use um `@if` que verifique qualquer uma das permissões daquela seção.

**Exemplo Correto:**
```blade
@if(auth()->user()->can('perm_a') || auth()->user()->can('perm_b'))
    <li class="header">Seção</li>
    
    @can('perm_a')
        <li>Link A</li>
    @endcan
    
    @can('perm_b')
        <li>Link B</li>
    @endcan
@endif
```

## 3. Sincronização de Permissões (Seeders)

Sempre que novas permissões forem adicionadas via código:
1. Certifique-se de que estão no `RolesAndPermissionsSeeder`.
2. Após o deploy, execute:
   ```bash
   ./vendor/bin/sail artisan db:seed --class=RolesAndPermissionsSeeder
   ```
   Isso garante que o cache de permissões do Spatie seja renovado e os papéis (Roles) recebam as novas capacidades.

## 4. Integridade de Dados e Testes (Status VPS 17/02/2026)

Durante a atualização, foram identificadas inconsistências que requerem investigação no ambiente de desenvolvimento:

### 4.1 Falhas em Testes Automatizados (30 falhas)
- **Sintoma**: 30 testes falharam, concentrados na lógica de `settlements` e cálculos financeiros.
- **Destaque**: O valor `artist_payment_value` está retornando `0.00` em cenários onde deveria haver valores calculados.
- **Hipótese**: A nova `GigFinancialCalculatorService` pode estar falhando devido a estados de dados imprevistos ou dependências não sincronizadas no banco da VPS.

### 4.2 Alertas de Auditoria
Os novos comandos `artisan gig:audit-*` detectaram:
- **Custos Órfãos**: 45 registros de `GigCost` sem relação com uma `Gig` válida.
- **Divergência de Moeda**: 15 casos onde a moeda da Gig e seus custos/pagamentos não coincidem.
- **Duplicatas**: 2 números de contrato (`contract_number`) duplicados e 2 grupos de eventos similares.

> [!CAUTION]
> Antes de aplicar correções automáticas (`--auto-fix`) em larga escala, deve-se reproduzir estas falhas em desenvolvimento para validar se a correção de dados não impactará a lógica de negócio esperada.
