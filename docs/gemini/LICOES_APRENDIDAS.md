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

## 6. Ambiente de Desenvolvimento e Ferramentas

- **Hooks de Pré-Commit e Laravel Sail:** O projeto utiliza hooks de pré-commit para garantir a qualidade e a consistência do código. Uma regra importante é que todos os commits **DEVEM** ser executados de dentro do ambiente Laravel Sail.
    - **Comando Correto:** `./vendor/bin/sail bash -c "git commit -m 'Sua mensagem'"`
    - **Justificativa:** Isso garante que as ferramentas de análise de código (linters, formatadores) rodem no mesmo ambiente configurado para o projeto, evitando inconsistências entre as máquinas dos desenvolvedores. Tentar commitar fora do Sail resultará em um erro e o commit será bloqueado.

## 7. Lições da Auditoria e Correções Recentes

- **Padronização de Testes com Atributos PHP 8**: A diretriz de usar `#[Test]` em vez de `/** @test */` é crucial para a manutenibilidade e compatibilidade futura com o PHPUnit. Todos os novos testes devem seguir este padrão.

- **Tradução de Datas com `isoFormat`**: A localização de datas deve ser feita preferencialmente com o método `isoFormat()` do Carbon, após garantir que o locale da aplicação esteja configurado globalmente no `AppServiceProvider`. O método `format()` não respeita a localidade e deve ser evitado em saídas para o usuário.

- **Commits via Laravel Sail**: Todos os commits devem ser executados de dentro do container do Sail para garantir que os hooks de pré-commit (como o Pint para formatação de código) funcionem corretamente. O comando `git commit` direto no host irá falhar.

- **Tratamento de Erros de Pré-Commit**: Erros de linting, como `trailing whitespace`, podem ser introduzidos por ferramentas de substituição em massa. É importante corrigir esses erros antes de tentar o commit novamente. O fluxo é: 1) Tentar o commit, 2) Ler a saída do erro do hook, 3) Corrigir o problema, 4) Adicionar o arquivo corrigido (`git add .`), 5) Tentar o commit novamente.

## 8. Lições da Padronização de Datas e Locale

- **Reinicialização Após Mudanças no AppServiceProvider:** Alterações no `AppServiceProvider` (como configuração global do locale do Carbon) requerem reinicialização completa da aplicação Laravel. Mesmo após limpar caches de configuração, view e aplicação, pode ser necessário parar e reiniciar os containers do Laravel Sail para que as mudanças entrem em vigor.
    - **Sintomas:** Funcionalidades que dependem das mudanças não funcionam, mesmo com caches limpos.
    - **Solução:** Executar `./vendor/bin/sail down` seguido de `./vendor/bin/sail up -d`.
    - **Justificativa:** O AppServiceProvider é carregado durante o bootstrap da aplicação, e mudanças nele podem não ser refletidas até uma reinicialização completa.

- **Verificação de Locale do Carbon:** Após configurar `Carbon::setLocale(config('app.locale'))` no AppServiceProvider, sempre verifique se o locale está sendo aplicado corretamente criando um script de teste que inicialize o Laravel e verifique `Carbon::getLocale()`.

- **Diferenças Visuais Sutils:** Mudanças de `format('d/m/y H:i')` para `isoFormat('l LT')` podem parecer não ter efeito porque ambas produzem saídas similares em pt_BR, mas a segunda inclui o ano completo (4 dígitos) em vez de 2 dígitos, melhorando a consistência.

## 9. Lições sobre Configurações Locais e Controle de Versão

- **Arquivos de Configuração Local Não Devem Ser Commitados:** Arquivos de configuração específicos do ambiente local do desenvolvedor (como `.claude/settings.local.json`, `.vscode/settings.json`, `.idea/workspace.xml`) **NUNCA** devem ser incluídos no controle de versão.
    - **Justificativa:** Esses arquivos contêm preferências pessoais, caminhos absolutos da máquina do desenvolvedor, credenciais locais e outras configurações que não devem ser compartilhadas ou impostas a outros desenvolvedores.
    - **Solução:** Sempre adicionar esses arquivos ao `.gitignore` antes do primeiro commit.
    - **Exemplo no Projeto:** O arquivo `.claude/settings.local.json` já está corretamente listado no `.gitignore` (linha 27).

- **Verificar Status Antes de Commit:** Antes de criar um commit, sempre executar `git status` para revisar quais arquivos estão sendo incluídos. Se arquivos de configuração local aparecerem como modificados, verificar se estão no `.gitignore`.
    - **Comando:** `git status --porcelain` (formato conciso, ideal para scripts)
    - **Ação:** Se um arquivo local está modificado mas no `.gitignore`, ele não será commitado automaticamente (comportamento correto).

- **Padrão de Nomenclatura para Arquivos Locais:** Adotar o sufixo `.local` para arquivos de configuração que não devem ser versionados facilita a manutenção do `.gitignore`.
    - **Exemplos:**
        - ✅ `settings.local.json` → Ignorado
        - ✅ `.env.local` → Ignorado
        - ✅ `config.local.php` → Ignorado
        - ❌ `my-config.json` → Pode ser commitado por engano

- **Documentar no README:** Quando houver arquivos de configuração que precisam ser criados localmente, documentar no README com exemplos:
    ```markdown
    ## Configuração Local

    Copie o arquivo de exemplo e ajuste conforme necessário:
    ```bash
    cp .claude/settings.example.json .claude/settings.local.json
    ```
    ```

- **Revisão de Pull Requests:** Durante code reviews, sempre verificar se arquivos de configuração local foram acidentalmente incluídos no PR. Esse é um erro comum que deve ser detectado antes do merge.
