# GEMINI.md - EventosPro

## Project Overview

EventosPro is a web application built with Laravel 12 for managing events, artists, and bookers, with a strong emphasis on financial tracking and reporting. It appears to be an internal tool for an agency or event management company.

The application is built on the following stack:

*   **Backend:** PHP 8.2, Laravel 12
*   **Frontend:** Vite, Tailwind CSS, Alpine.js
*   **Database:** (Not specified, but likely MySQL or PostgreSQL, common with Laravel)
*   **Development Environment:** Laravel Sail (Docker-based)
*   **Admin Panel:** Filament

Key features include:

*   **Gig Management:** Creating and managing events ("gigs") with detailed financial information, including contract value, expenses, and commissions.
*   **Artist and Booker Management:** Maintaining a database of artists and bookers, including contact information and commission rates.
*   **Financial Tracking:** Tracking payments for gigs, including currency conversions and settlement of payments to artists and bookers.
*   **Reporting:** Generating various financial reports, including overview reports, delinquency reports, and performance reports.
*   **Auditing:** A system for auditing data and applying fixes.
*   **User Management:** A role-based user management system using `spatie/laravel-permission`.

## Building and Running

The project uses Laravel Sail for its development environment. All commands should be run through the `sail` script.

**Initial Setup:**

```bash
# Clone the repository
git clone <repository-url>
cd EventosPro

# Install Composer dependencies
composer install

# Copy environment file
cp .env.example .env

# Start the Docker containers
./vendor/bin/sail up -d

# Generate application key
./vendor/bin/sail artisan key:generate

# Run database migrations
./vendor/bin/sail artisan migrate

# Install NPM dependencies and build assets
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
```

**Common Commands:**

It is recommended to create a shell alias for `./vendor/bin/sail`:

```bash
alias sail='./vendor/bin/sail'
```

*   **Start containers:** `sail up -d`
*   **Stop containers:** `sail down`
*   **Run tests:** `sail artisan test`
*   **Run database migrations:** `sail artisan migrate`
*   **Access the shell of the application container:** `sail shell`

## Development Conventions

*   **Development Environment:** All development should be done using the Laravel Sail environment to ensure consistency.
*   **Testing:** The project uses PHPUnit for testing. Tests can be run with `sail artisan test`.
*   **Frontend:** The frontend is built with Vite, Tailwind CSS, and Alpine.js. Frontend assets are located in the `resources` directory and built to the `public/build` directory.
*   **Admin Panel:** The application uses Filament for its admin panel. The Filament configuration can be found in `app/Providers/Filament/AdminPanelProvider.php`.
*   **Database Migrations:** Database schema changes are managed through Laravel's migration system. Migration files are located in the `database/migrations` directory.
*   **Routing:** Web routes are defined in `routes/web.php`.
*   **Permissions:** The application uses the `spatie/laravel-permission` package for handling roles and permissions.
