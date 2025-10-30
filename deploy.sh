#!/bin/bash

# EventosPro - Automated Deploy Script
# This script handles the complete deployment process for the Laravel application

set -e  # Exit on any error

echo "🚀 Starting EventosPro Deployment..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if Docker is running
check_docker() {
    if ! docker info >/dev/null 2>&1; then
        print_error "Docker is not running. Please start Docker and try again."
        exit 1
    fi
    print_success "Docker is running"
}

# Stop existing containers
stop_containers() {
    print_status "Stopping existing containers..."
    ./vendor/bin/sail down 2>/dev/null || true
    print_success "Containers stopped"
}

# Start containers
start_containers() {
    print_status "Starting containers..."
    ./vendor/bin/sail up -d
    print_success "Containers started"
}

# Install/update Composer dependencies
install_composer() {
    print_status "Installing Composer dependencies..."
    ./vendor/bin/sail composer install --no-interaction --optimize-autoloader
    print_success "Composer dependencies installed"
}

# Install/update NPM dependencies
install_npm() {
    print_status "Installing NPM dependencies..."
    ./vendor/bin/sail npm install
    print_success "NPM dependencies installed"
}

# Build assets
build_assets() {
    print_status "Building assets..."
    ./vendor/bin/sail npm run build
    print_success "Assets built"
}

# Generate application key if not exists
generate_key() {
    if ! grep -q "^APP_KEY=" .env || grep -q "^APP_KEY=$" .env; then
        print_status "Generating application key..."
        ./vendor/bin/sail artisan key:generate
        print_success "Application key generated"
    else
        print_success "Application key already exists"
    fi
}

# Run database migrations and seeders (DEVELOPMENT)
migrate_database() {
    print_status "Running database migrations and seeders..."
    ./vendor/bin/sail artisan migrate:fresh --seed
    print_success "Database migrated and seeded"
}

# Run database migrations only (PRODUCTION - SAFE)
migrate_production() {
    print_status "Running database migrations (production mode - preserving data)..."
    ./vendor/bin/sail artisan migrate --force
    print_success "Database migrated successfully (data preserved)"
}

# Create backup before deployment
create_backup() {
    if [ -f "scripts/backup-database.sh" ]; then
        print_status "💾 Creating backup before deploy..."
        bash scripts/backup-database.sh
        if [ $? -eq 0 ]; then
            print_success "Backup created successfully"
        else
            print_error "Failed to create backup!"
            read -p "Do you want to continue without backup? (y/N): " CONTINUE
            if [ "$CONTINUE" != "y" ] && [ "$CONTINUE" != "Y" ]; then
                print_error "Deploy cancelled"
                exit 1
            fi
        fi
    else
        print_warning "Backup script not found at scripts/backup-database.sh"
        print_warning "Deploy will continue WITHOUT backup!"
        sleep 3
    fi
}

# Cache configurations
cache_configs() {
    print_status "Caching configurations..."
    ./vendor/bin/sail artisan config:cache
    ./vendor/bin/sail artisan route:cache
    ./vendor/bin/sail artisan view:cache
    print_success "Configurations cached"
}

# Check application health
check_health() {
    print_status "Checking application health..."
    sleep 5  # Wait for services to be ready

    if curl -s -o /dev/null -w "%{http_code}" http://localhost:8081 | grep -q "200"; then
        print_success "Application is healthy!"
        echo ""
        echo "🎉 Deployment completed successfully!"
        echo ""
        echo "Application URLs:"
        echo "  🌐 Web Application: http://localhost:8081"
        echo "  🗄️  phpMyAdmin:      http://localhost:8089"
        echo ""
        echo "Useful commands:"
        echo "  🛑  Stop containers:  ./vendor/bin/sail down"
        echo "  📊  View logs:        ./vendor/bin/sail logs"
        echo "  🔧  Run tests:        ./vendor/bin/sail test"
        echo "  🎯  Run single test:  ./vendor/bin/sail test --filter=TestName"
        echo "  📝  Format code:      ./vendor/bin/sail bash -c \"vendor/bin/pint --dirty\""
    else
        print_error "Application health check failed!"
        print_status "Checking container logs..."
        ./vendor/bin/sail logs --tail=20
        exit 1
    fi
}

# Main deployment function
deploy() {
    print_status "Starting automated deployment process..."
    echo ""

    check_docker
    stop_containers
    start_containers
    install_composer
    install_npm
    build_assets
    generate_key
    migrate_database
    cache_configs
    check_health
}

# Show usage if help is requested
if [[ "$1" == "--help" || "$1" == "-h" ]]; then
    echo "EventosPro Automated Deploy Script"
    echo ""
    echo "Usage: $0 [options]"
    echo ""
    echo "Options:"
    echo "  --help, -h        Show this help message"
    echo "  --production      PRODUCTION deploy (migrations only, preserves data)"
    echo "  --fresh           Fresh deploy (DEVELOPMENT - resets database)"
    echo "  --quick           Quick deploy (skip composer/npm install)"
    echo "  --assets          Only rebuild assets"
    echo "  --migrate         Only run migrations (DEVELOPMENT - with seed)"
    echo ""
    echo "Examples:"
    echo "  $0                  # Full deployment (DEVELOPMENT)"
    echo "  $0 --production     # Production deployment (SAFE - preserves data)"
    echo "  $0 --fresh          # Fresh deployment (DEVELOPMENT - resets DB)"
    echo "  $0 --quick          # Quick deployment"
    echo "  $0 --assets         # Only rebuild assets"
    echo ""
    echo "⚠️  WARNING: Use --production on VPS to preserve existing data!"
    exit 0
fi

# Handle different deployment modes
case "$1" in
    --production)
        print_status "🚀 Performing PRODUCTION deployment (safe mode)..."
        print_warning "This will preserve all existing data in the database"
        check_docker
        stop_containers
        start_containers
        install_composer
        install_npm
        build_assets
        generate_key
        create_backup  # Create backup before migrations
        migrate_production  # Use production migration (no seed, no fresh)
        cache_configs
        check_health
        print_success "✅ Production deployment completed successfully!"
        ;;
    --fresh)
        print_status "Performing fresh deployment..."
        check_docker
        stop_containers
        print_status "Removing old containers and volumes..."
        docker system prune -f --volumes 2>/dev/null || true
        deploy
        ;;
    --quick)
        print_status "Performing quick deployment..."
        check_docker
        start_containers
        generate_key
        migrate_database
        cache_configs
        check_health
        ;;
    --assets)
        print_status "Rebuilding assets only..."
        check_docker
        start_containers
        install_npm
        build_assets
        print_success "Assets rebuilt successfully!"
        ;;
    --migrate)
        print_status "Running database migration only..."
        check_docker
        start_containers
        generate_key
        migrate_database
        print_success "Database migrated successfully!"
        ;;
    *)
        deploy
        ;;
esac
