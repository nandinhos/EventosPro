# Stack Tecnológico - EventosPro

## Linguagens e Runtimes

### Backend
- **PHP**: 8.2+ (requisito mínimo)
- **Laravel Framework**: 12.x (última versão)

### Frontend
- **JavaScript**: ES6+ (módulos nativos)
- **CSS**: CSS3 com Tailwind CSS
- **HTML**: HTML5 semântico

## Frameworks e Bibliotecas Principais

### Backend Framework
- **Laravel 12.x**: Framework PHP principal
  - Eloquent ORM para mapeamento objeto-relacional
  - Blade Template Engine para views
  - Artisan CLI para comandos
  - Migration system para versionamento de banco

### Frontend Frameworks e Bibliotecas

#### CSS Framework
- **Tailwind CSS**: 3.4.1
  - Framework CSS utilitário
  - Configuração customizada via `tailwind.config.js`
  - Integração com PostCSS e Autoprefixer

#### JavaScript Libraries
- **Alpine.js**: 3.13.5
  - Framework JavaScript reativo e leve
  - Interatividade sem complexidade de SPA
  - Integração nativa com Laravel

- **Chart.js**: 4.4.1
  - Biblioteca para gráficos e visualizações
  - Suporte a múltiplos tipos de gráfico
  - Responsivo e customizável

- **SweetAlert2**: 11.10.5
  - Alertas e modais elegantes
  - Substituição para alert() nativo
  - Altamente customizável

#### Iconografia
- **Font Awesome**: 6.5.1 (Free)
  - Biblioteca de ícones vetoriais
  - Integração via CDN ou local

### Build Tools e Desenvolvimento

#### Build System
- **Vite**: 5.0.12
  - Build tool moderno e rápido
  - Hot Module Replacement (HMR)
  - Otimização automática para produção
  - Configuração via `vite.config.js`

#### CSS Processing
- **PostCSS**: 8.4.35
  - Processamento de CSS
  - Autoprefixer para compatibilidade
  - Integração com Tailwind CSS

- **Autoprefixer**: 10.4.17
  - Prefixos automáticos para CSS
  - Compatibilidade cross-browser

## Bancos de Dados e Persistência

### Banco de Dados Principal
- **MySQL**: Configuração padrão para desenvolvimento via docker usando laravel sail
  - Versão recomendada: 8.0+
  - Configuração via variáveis de ambiente
- **MySQL**: Suporte configurado para produção
  - Versão recomendada: 8.0+
  - Configuração via variáveis de ambiente

### ORM e Migrations
- **Eloquent ORM**: Sistema de mapeamento do Laravel
- **Laravel Migrations**: Versionamento de esquema
- **Database Seeding**: População inicial de dados

## Bibliotecas Especializadas

### Geração de PDF
- **barryvdh/laravel-dompdf**: 2.2.0
  - Geração de PDFs a partir de HTML/CSS
  - Integração nativa com Laravel
  - Suporte a layouts complexos

### Exportação de Dados
- **maatwebsite/laravel-excel**: 3.1.55
  - Exportação/importação de Excel
  - Múltiplos formatos suportados
  - Integração com Eloquent

### Sistema de Permissões
- **spatie/laravel-permission**: 6.9.0
  - Controle granular de permissões
  - Roles e permissions
  - Cache automático de permissões

### Interface Administrativa (Painel de Controle para implementação futura)
- **filament/filament**: 3.2.115
  - Painel administrativo moderno
  - Componentes pré-construídos
  - Integração com Eloquent

### Autenticação
- **laravel/breeze**: 2.2.5
  - Sistema de autenticação simples
  - Views pré-construídas
  - Middleware de autenticação

## Ferramentas de Desenvolvimento

### Debug e Profiling
- **barryvdh/laravel-debugbar**: 3.14.7
  - Barra de debug para desenvolvimento
  - Profiling de queries e performance
  - Informações detalhadas de requisições

### Testing
- **PHPUnit**: Incluído no Laravel
  - Framework de testes unitários
  - Testes de feature e integração

### Code Quality
- **Laravel Pint**: Formatação de código PHP
- **Laravel Sail**: Ambiente Docker (se configurado)

## Infraestrutura e DevOps

### Ambiente de Desenvolvimento
- **Artisan Serve**: Servidor de desenvolvimento local
- **Vite Dev Server**: Hot reload para assets
- **MySQL via Docker**: Banco local para desenvolvimento

### Configuração de Produção
- **Apache/Nginx**: Servidor web recomendado
- **MySQL**: Banco de dados para produção
- **Redis**: Cache e sessões (configurável)
- **Queue Workers**: Processamento assíncrono

### Gerenciamento de Dependências
- **Composer**: Gerenciador de pacotes PHP
  - `composer.json` para dependências backend
  - Autoloading PSR-4

- **NPM**: Gerenciador de pacotes JavaScript
  - `package.json` para dependências frontend
  - Scripts de build automatizados

## Configuração de Ambiente

### Variáveis de Ambiente (.env)
```
APP_NAME=EventosPro
APP_ENV=production
APP_DEBUG=false
DB_CONNECTION=mysql
DB_DATABASE=laravel
DB_HOST=mysql
DB_PORT=3306
DB_USERNAME=user
DB_PASSWORD=password
SESSION_DRIVER=database
BROADCAST_DRIVER=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
CACHE_STORE=database
```

### Drivers Configurados
- **Session**: Database driver
- **Cache**: Database driver (configurável para Redis)
- **Queue**: Database driver (configurável para Redis/SQS)
- **Filesystem**: Local storage (configurável para S3)
- **Broadcasting**: Log driver (configurável para Pusher)

## Versões e Compatibilidade

### Requisitos Mínimos
- **PHP**: 8.2+
- **Node.js**: 18+ (para build tools)
- **Composer**: 2.0+
- **NPM**: 8+

### Compatibilidade de Navegadores
- **Chrome**: 90+
- **Firefox**: 88+
- **Safari**: 14+
- **Edge**: 90+

## Estrutura de Assets

### CSS
- **Entrada**: `resources/css/app.css`
- **Build**: `public/build/assets/app-[hash].css`
- **Tailwind**: Configuração em `tailwind.config.js`

### JavaScript
- **Entrada**: `resources/js/app.js`
- **Build**: `public/build/assets/app-[hash].js`
- **Módulos**: Importação ES6 nativa

### Fontes e Ícones
- **Font Awesome**: Via CDN ou local
- **Fontes do sistema**: Tailwind CSS defaults

## Performance e Otimização

### Frontend
- **Vite**: Bundling otimizado
- **Tree Shaking**: Remoção de código não utilizado
- **Code Splitting**: Carregamento sob demanda
- **Asset Hashing**: Cache busting automático

### Backend
- **Eloquent**: Query optimization
- **Eager Loading**: Redução de N+1 queries
- **Database Indexing**: Índices em campos críticos
- **Caching**: Sistema de cache configurável