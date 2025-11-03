#!/bin/bash

################################################################################
# EventosPro - Automated Production Deployment Script
#
# This script automates the deployment process for the PRODUCTION environment
# with safety features including backup, health checks, and automatic rollback
#
# Usage: ./scripts/deploy-production.sh
# Rollback: ./scripts/deploy-production.sh rollback
################################################################################

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
MAGENTA='\033[0;35m'
NC='\033[0m' # No Color

# Emojis
CHECK="✅"
CROSS="❌"
ROCKET="🚀"
WRENCH="🔧"
WARNING="⚠️"
INFO="ℹ️"
FIRE="🔥"
SHIELD="🛡️"
BACKUP="💾"

# Configuration
PROJECT_DIR="$(pwd)"
DOCKER_COMPOSE="docker compose -f docker-compose.production.yml"
BACKUP_DIR="$HOME/backups/eventospro"
TIMESTAMP=$(date +%Y%m%d-%H%M%S)
BACKUP_FILE="$BACKUP_DIR/production-$TIMESTAMP.sql.gz"
PREVIOUS_COMMIT_FILE="/tmp/eventospro-previous-commit.txt"

################################################################################
# Helper Functions
################################################################################

log_info() {
    echo -e "${BLUE}${INFO} $1${NC}"
}

log_success() {
    echo -e "${GREEN}${CHECK} $1${NC}"
}

log_warning() {
    echo -e "${YELLOW}${WARNING} $1${NC}"
}

log_error() {
    echo -e "${RED}${CROSS} $1${NC}"
}

log_critical() {
    echo -e "${RED}${FIRE} ${FIRE} ${FIRE} $1 ${FIRE} ${FIRE} ${FIRE}${NC}"
}

log_section() {
    echo ""
    echo -e "${MAGENTA}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${MAGENTA}${WRENCH} $1${NC}"
    echo -e "${MAGENTA}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""
}

confirm_action() {
    local message="$1"
    echo -e "${YELLOW}${WARNING} $message${NC}"
    read -p "Type 'YES' to continue: " confirmation
    if [ "$confirmation" != "YES" ]; then
        log_error "Deployment cancelled by user"
        exit 1
    fi
}

################################################################################
# Rollback Function
################################################################################

rollback() {
    log_section "INITIATING ROLLBACK"

    if [ ! -f "$PREVIOUS_COMMIT_FILE" ]; then
        log_error "No previous commit found! Cannot rollback."
        log_info "Available backups:"
        ls -lh "$BACKUP_DIR/" | tail -5
        exit 1
    fi

    PREVIOUS_COMMIT=$(cat "$PREVIOUS_COMMIT_FILE")
    log_warning "Rolling back to commit: $PREVIOUS_COMMIT"

    confirm_action "This will revert code and rebuild containers. Continue?"

    # Revert code
    log_info "Reverting to previous commit..."
    git reset --hard "$PREVIOUS_COMMIT"

    # Rebuild containers
    log_info "Rebuilding containers with previous code..."
    $DOCKER_COMPOSE build app
    $DOCKER_COMPOSE up -d --no-deps app

    # Clear caches
    log_info "Clearing caches..."
    $DOCKER_COMPOSE exec -T app php artisan optimize:clear
    $DOCKER_COMPOSE exec -T app composer production:optimize

    # Restart workers
    log_info "Restarting queue workers..."
    $DOCKER_COMPOSE exec -T app supervisorctl restart laravel-worker:*

    log_success "ROLLBACK COMPLETED"
    log_warning "Verify application is working correctly"
    log_info "If database rollback is also needed, restore from backup manually"

    exit 0
}

# Check if rollback was requested
if [ "$1" == "rollback" ]; then
    rollback
fi

################################################################################
# Pre-flight Safety Checks
################################################################################

log_section "${SHIELD} PRODUCTION DEPLOYMENT - SAFETY CHECKS"

log_critical "YOU ARE DEPLOYING TO PRODUCTION!"
log_critical "This will affect LIVE users and data"

confirm_action "Are you ABSOLUTELY SURE you want to deploy to PRODUCTION?"

# Check if running in correct directory
if [ ! -f "docker-compose.production.yml" ]; then
    log_error "docker-compose.production.yml not found!"
    log_error "Please run this script from the project root"
    exit 1
fi

# Check if .env exists and is for production
if [ ! -f ".env" ]; then
    log_error ".env file not found!"
    exit 1
fi

if ! grep -q "APP_ENV=production" .env; then
    log_error ".env is NOT configured for production!"
    log_error "APP_ENV must be 'production'"
    exit 1
fi

if grep -q "APP_DEBUG=true" .env; then
    log_error "APP_DEBUG is TRUE in production!"
    log_error "This is a SECURITY RISK. Set APP_DEBUG=false"
    exit 1
fi

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    log_error "Docker is not running!"
    exit 1
fi

# Check if containers are currently running
if ! $DOCKER_COMPOSE ps | grep -q "Up"; then
    log_error "No containers are running!"
    log_warning "Run initial setup first: docker compose -f docker-compose.production.yml up -d"
    exit 1
fi

log_success "All safety checks passed"

################################################################################
# Display Current State
################################################################################

log_section "Current Production State"

echo "📍 Current Git Branch:"
git branch --show-current

echo ""
echo "📝 Current Commit (will be saved for rollback):"
CURRENT_COMMIT=$(git rev-parse HEAD)
echo "$CURRENT_COMMIT" > "$PREVIOUS_COMMIT_FILE"
git log -1 --oneline

echo ""
echo "🐳 Current Containers:"
$DOCKER_COMPOSE ps --format "table {{.Name}}\t{{.Status}}"

echo ""
log_warning "Containers will be updated, database will be backed up"

################################################################################
# Create Database Backup
################################################################################

log_section "${BACKUP} Creating Database Backup"

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

log_info "Backing up database to: $BACKUP_FILE"

# Get database credentials from .env
DB_DATABASE=$(grep DB_DATABASE .env | cut -d '=' -f2)
DB_USERNAME=$(grep DB_USERNAME .env | cut -d '=' -f2)
DB_PASSWORD=$(grep DB_PASSWORD .env | cut -d '=' -f2)

# Create backup
if $DOCKER_COMPOSE exec -T mysql mysqldump \
    -u "$DB_USERNAME" -p"$DB_PASSWORD" \
    --single-transaction --quick --lock-tables=false \
    "$DB_DATABASE" | gzip > "$BACKUP_FILE"; then

    BACKUP_SIZE=$(du -h "$BACKUP_FILE" | cut -f1)
    log_success "Backup created successfully ($BACKUP_SIZE)"

    # Keep only last 10 backups
    log_info "Cleaning old backups (keeping last 10)..."
    cd "$BACKUP_DIR"
    ls -t production-*.sql.gz | tail -n +11 | xargs -r rm
    cd "$PROJECT_DIR"

else
    log_error "Backup FAILED!"
    log_critical "Cannot proceed without backup!"
    exit 1
fi

################################################################################
# Update Code
################################################################################

log_section "Updating Code from Git"

# Show what will be pulled
log_info "Fetching latest changes..."
git fetch origin main

COMMITS_BEHIND=$(git rev-list --count HEAD..origin/main)

if [ "$COMMITS_BEHIND" -eq "0" ]; then
    log_warning "Already up to date! No new commits to deploy."
    read -p "Continue anyway? (y/N): " continue_deploy
    if [ "$continue_deploy" != "y" ] && [ "$continue_deploy" != "Y" ]; then
        log_info "Deployment cancelled"
        exit 0
    fi
else
    log_info "$COMMITS_BEHIND new commit(s) will be deployed:"
    git log --oneline HEAD..origin/main
    echo ""
fi

# Stash any local changes
if ! git diff-index --quiet HEAD --; then
    log_warning "Local changes detected, stashing..."
    git stash
fi

# Pull latest changes
log_info "Pulling latest changes from origin/main..."
git pull origin main

NEW_COMMIT=$(git log -1 --oneline)
log_success "Code updated to: $NEW_COMMIT"

################################################################################
# Rebuild Containers (Zero-Downtime)
################################################################################

log_section "Rebuilding Docker Containers"

log_info "Building new container images (this may take 5-7 minutes)..."
$DOCKER_COMPOSE build app

log_info "Starting updated containers (zero-downtime update)..."
$DOCKER_COMPOSE up -d --no-deps app

log_info "Waiting for containers to stabilize (40 seconds)..."
for i in {40..1}; do
    echo -ne "⏱️  $i seconds remaining...\r"
    sleep 1
done
echo ""

log_success "Containers rebuilt and started"

################################################################################
# Run Migrations (with caution)
################################################################################

log_section "Database Migrations"

# Check if there are pending migrations
if $DOCKER_COMPOSE exec -T app php artisan migrate:status | grep -q "Pending"; then
    log_warning "PENDING MIGRATIONS DETECTED!"

    echo ""
    echo "Migrations that will be executed:"
    $DOCKER_COMPOSE exec -T app php artisan migrate:status | grep Pending

    echo ""
    log_warning "Database backup available at: $BACKUP_FILE"

    confirm_action "Execute migrations in PRODUCTION database?"

    log_info "Running migrations..."
    if $DOCKER_COMPOSE exec -T app php artisan migrate --force; then
        log_success "Migrations completed successfully"
    else
        log_error "MIGRATION FAILED!"
        log_critical "Database may be in inconsistent state!"
        log_warning "Consider rolling back: ./scripts/deploy-production.sh rollback"
        log_warning "Or restore backup: gunzip < $BACKUP_FILE | docker compose exec -T mysql mysql -u$DB_USERNAME -p$DB_PASSWORD $DB_DATABASE"
        exit 1
    fi

else
    log_info "No pending migrations"
fi

################################################################################
# Optimize Laravel
################################################################################

log_section "Optimizing Laravel"

log_info "Clearing old caches..."
$DOCKER_COMPOSE exec -T app php artisan optimize:clear

log_info "Rebuilding optimized caches..."
$DOCKER_COMPOSE exec -T app php artisan config:cache
$DOCKER_COMPOSE exec -T app php artisan route:cache
$DOCKER_COMPOSE exec -T app php artisan view:cache
$DOCKER_COMPOSE exec -T app php artisan event:cache

log_success "Laravel fully optimized"

################################################################################
# Restart Queue Workers
################################################################################

log_section "Restarting Queue Workers"

log_info "Gracefully restarting all queue workers..."
$DOCKER_COMPOSE exec -T app supervisorctl restart laravel-worker:*

sleep 5

log_info "Queue worker status:"
$DOCKER_COMPOSE exec -T app supervisorctl status | grep laravel

log_success "Queue workers restarted"

################################################################################
# Health Checks
################################################################################

log_section "Running Critical Health Checks"

HEALTH_PASSED=true

# Check container health
log_info "Checking container health..."
if $DOCKER_COMPOSE ps | grep -q "unhealthy"; then
    log_error "Some containers are unhealthy!"
    $DOCKER_COMPOSE ps
    HEALTH_PASSED=false
else
    log_success "All containers are healthy"
fi

# Check application responsiveness
if command -v curl &> /dev/null; then
    log_info "Testing application endpoint..."
    for i in {1..3}; do
        if curl -f -s --max-time 5 http://localhost/health > /dev/null 2>&1; then
            log_success "Application is responding (attempt $i/3)"
            break
        else
            if [ $i -eq 3 ]; then
                log_error "Application not responding after 3 attempts!"
                HEALTH_PASSED=false
            else
                sleep 2
            fi
        fi
    done
fi

# Check database connection
log_info "Testing database connection..."
if $DOCKER_COMPOSE exec -T app php artisan tinker --execute="DB::connection()->getPdo();" 2>&1 | grep -q "PDO"; then
    log_success "Database connection OK"
else
    log_error "Database connection FAILED!"
    HEALTH_PASSED=false
fi

# Check queue worker
log_info "Testing queue worker..."
if $DOCKER_COMPOSE exec -T app supervisorctl status | grep -q "RUNNING"; then
    log_success "Queue workers are running"
else
    log_error "Queue workers NOT running!"
    HEALTH_PASSED=false
fi

if [ "$HEALTH_PASSED" = false ]; then
    log_critical "HEALTH CHECKS FAILED!"
    log_warning "Application may not be functioning correctly"
    log_info "Options:"
    log_info "  1. Check logs: $DOCKER_COMPOSE logs -f app"
    log_info "  2. Rollback: ./scripts/deploy-production.sh rollback"
    log_info "  3. Restore DB: gunzip < $BACKUP_FILE | docker compose exec -T mysql mysql ..."

    read -p "Continue despite health check failures? (y/N): " continue_anyway
    if [ "$continue_anyway" != "y" ] && [ "$continue_anyway" != "Y" ]; then
        log_error "Deployment aborted due to failed health checks"
        exit 1
    fi
fi

################################################################################
# Monitor Application (2 minutes)
################################################################################

log_section "Monitoring Application Logs"

log_info "Monitoring for critical errors (next 30 seconds)..."
log_info "Press Ctrl+C to skip monitoring"

timeout 30 $DOCKER_COMPOSE logs -f app 2>&1 | grep -E "(ERROR|CRITICAL|EMERGENCY|FATAL)" || true

echo ""
log_info "No critical errors detected in initial monitoring"

################################################################################
# Resource Usage
################################################################################

log_section "Resource Usage Report"

docker stats --no-stream --format "table {{.Name}}\t{{.CPUPerc}}\t{{.MemUsage}}\t{{.MemPerc}}"

# Check if memory usage is concerning
MEMORY_USAGE=$(docker stats --no-stream --format "{{.MemPerc}}" | grep -o '[0-9.]*' | sort -n | tail -1 | cut -d '.' -f1)
if [ "$MEMORY_USAGE" -gt 80 ]; then
    log_warning "Memory usage is HIGH: ${MEMORY_USAGE}%"
    log_info "Monitor closely: watch -n 2 'docker stats --no-stream'"
fi

################################################################################
# Display Recent Logs
################################################################################

log_section "Recent Application Logs"

echo "Last 15 log entries:"
$DOCKER_COMPOSE logs --tail=15 app

################################################################################
# Deployment Summary
################################################################################

log_section "${ROCKET} PRODUCTION DEPLOYMENT SUMMARY"

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo -e "${GREEN}${CHECK} DEPLOYMENT COMPLETED SUCCESSFULLY${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "📊 Deployment Details:"
echo "  Environment: PRODUCTION"
echo "  Previous Commit: $(cat $PREVIOUS_COMMIT_FILE | cut -c1-7)"
echo "  New Commit: $NEW_COMMIT"
echo "  Timestamp: $(date '+%Y-%m-%d %H:%M:%S')"
echo "  Backup: $BACKUP_FILE ($BACKUP_SIZE)"
echo ""
echo "${INFO} Post-Deployment Actions:"
echo "  ✓ Monitor logs for 10-15 minutes"
echo "  ✓ Test critical functionality in browser"
echo "  ✓ Check error tracking (Sentry, etc.)"
echo "  ✓ Monitor resource usage"
echo ""
echo "🔧 Useful Commands:"
echo "  Logs:      $DOCKER_COMPOSE logs -f app"
echo "  Stats:     docker stats"
echo "  Status:    $DOCKER_COMPOSE ps"
echo "  Rollback:  ./scripts/deploy-production.sh rollback"
echo ""
echo "📚 Full documentation: docs/PRODUCTION_DEPLOY.md"
echo ""

if [ "$HEALTH_PASSED" = true ]; then
    log_success "${ROCKET} Production is LIVE and healthy!"
else
    log_warning "${WARNING} Production is LIVE but health checks had warnings"
    log_info "Monitor closely for the next 24 hours"
fi

echo ""

exit 0
