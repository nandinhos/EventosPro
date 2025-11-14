# Visão Técnica Completa — EventosPro

## Stack e Infra
- Backend: `Laravel 12` (PHP `8.2`) com `Eloquent`, `Filament 4`, `Laravel Breeze`, `Spatie Laravel Permission`.
- Frontend: `Blade`, `TailwindCSS 3`, `Alpine.js 3`, `Axios 1`, `Chart.js 4`, `SweetAlert2`.
- Build: `Vite 6` e `laravel-vite-plugin`.
- Dev/Infra: `Laravel Sail` com `Docker Compose` (serviços: `mysql`, `redis`, `phpmyadmin`).
- Qualidade: `PHPUnit 11`, `PHPStan 2`, `Laravel Pint`, `Debugbar` (dev), `Pail` para streaming de logs.

## Arquitetura e Estrutura
- Tipo: monolito MVC com camadas claras e Service Layer.
- Estrutura:
  - `app/Models/**`: entidades do domínio (Gig, Payment, GigCost, Settlement, Artist, Booker, CostCenter, Tag, User, ActivityLog, AgencyFixedCost, Contract, Event).
  - `app/Services/**`: regras de negócio e cálculos (ex.: `GigFinancialCalculatorService.php`, `FinancialProjectionService.php`, `FinancialReportService.php`).
  - `app/Http/Controllers/**`: orquestração HTTP (CRUDs, relatórios, projeções, auditoria).
  - `app/Observers/**`: invariantes e reações a eventos de modelos.
  - `app/Policies/**`: autorização baseada em políticas.
  - `routes/web.php`, `routes/auth.php`, `routes/console.php`: superfície de rotas.
  - `resources/views/**`: Blade modular com componentes.
  - `config/**`: configuração (logging, queue, mail, services, permission).
  - `database/migrations/**`, `database/seeders/**`, `database/factories/**`: schema, dados e geradores.
  - `bootstrap/app.php`: configuração de rotas, middleware, exceções e health `/up`.
  - `public/index.php`, `artisan`: entrypoints HTTP e CLI.

## Funcionalidades Principais
- Autenticação/Perfil: fluxos padrão Breeze (login, registro, reset/verify email), edição de perfil.
- Usuários: CRUD completo. Rotas: `routes/web.php:48`.
- Portal Booker: painel de desempenho. Rota: `routes/web.php:51`.
- Gigs: CRUD, formulário de NF e agrupamento de recursos aninhados:
  - Pagamentos: CRUD + confirmar/desconfirmar; rotas `routes/web.php:137–143`.
  - Custos: CRUD parcial, confirmar/desconfirmar, alternar emissão de nota; rotas `routes/web.php:145–149`.
  - Acertos: liquidar/desfazer artista e booker; rotas `routes/web.php:152–155`.
  - Debug financeiro por gig: `routes/web.php:162`.
- Relatórios Financeiros:
  - Visão geral e exportação (PDF/Excel): `routes/web.php:59–76`.
  - Inadimplência e exportação: `routes/web.php:63–65`.
  - Vencimentos e exportação: `routes/web.php:119–122`.
  - Performance geral e de artistas: `routes/web.php:105–113`.
- Projeções Financeiras: índice e debug: `routes/web.php:100–104`.
- Pagamentos em Massa: artistas/bookers com desfazer: `routes/web.php:70–73`, `80–81`.
- Auditoria de Dados: painel, execução de auditorias, correções unitárias e em massa: `routes/web.php:115–118`, `169–181`.
- Fechamento Mensal: índices e exportações: `routes/web.php:125–127`.
- Test Report: executar e exportar resultados: `routes/web.php:165–168`.

## Banco de Dados
- SGBD: `MySQL 8`.
- ORM: `Eloquent` com relacionamentos fortes e `SoftDeletes` em entidades críticas.
- Migrations: criam tabelas e índices para `gigs`, `payments`, `gig_costs`, `settlements`, `artists`, `bookers`, `cost_centers`, `tags`, `activity_logs`.
- Seeders: `DatabaseSeeder` e específicos (ex.: `RolesAndPermissionsSeeder.php`).
- Factories: suporte amplo para testes e geração de dados.

## Execução e Entrypoints
- HTTP: `public/index.php`.
- CLI: `artisan`.
- Bootstrap: `bootstrap/app.php` define rotas, middleware, exceções e health `/up`.
- Scripts:
  - Composer `dev`: orquestra `php artisan serve`, filas, logs (Pail) e `vite` via `concurrently`.
  - NPM: `dev` e `build` via `vite`.
- Dev com Sail:
  - `./vendor/bin/sail up -d`
  - `./vendor/bin/sail artisan migrate --seed`
  - `./vendor/bin/sail npm install && ./vendor/bin/sail npm run dev`
- Dev sem Sail (opcional): `composer run dev` após `composer install`, `.env`, `key:generate`, `migrate` e `npm run dev`.

## Qualidade, Log e Monitoramento
- Testes: `PHPUnit` com `phpunit.xml`; testes em `tests/Feature/**` e `tests/Unit/**`.
- Estática/formatador: `PHPStan`, `Laravel Pint`, `EditorConfig`.
- Logs: `Monolog` via `config/logging.php`, streaming com `Pail`, `Debugbar` em dev.
- Saúde: endpoint `/up` (Laravel 12 via `bootstrap/app.php`).

## Segurança
- AuthN: `Laravel Breeze`.
- AuthZ: `spatie/laravel-permission` com policies por recurso.
- Proteções: CSRF, Mass Assignment, Prepared Statements (Eloquent).
- Sessões: driver `database` (ajustes reforçados em `.env.vps.example`).
- Headers/Infra: `public/.htaccess` e recomendações no `.env.vps.example`.

## Fonte da Verdade
- Modelos Eloquent e migrations são a base de dados.
- Lógica financeira e relatórios residem em `app/Services/**`.
- Invariantes/eventos assegurados em `app/Observers/**` e `app/Policies/**`.
- Configurações centralizadas em `.env` e `config/**`.

## Arquivos Importantes
- `/composer.json`, `/package.json`, `/docker-compose.yml`.
- `/routes/web.php`, `/bootstrap/app.php`, `/public/index.php`.
- `/app/Models/*.php`, `/app/Services/*.php`, `/app/Http/Controllers/*.php`.
- `/config/*.php`, `/database/migrations/**`, `/database/seeders/**`.

## Pontos de Atenção
- Referência a `deploy.sh` no `README.md` sem o arquivo no repositório: alinhar documentação.
- Presença de arquivos `*.backup` e `*copy*` em views/config: revisar, mover para `docs/LEGACY.md` ou remover.

