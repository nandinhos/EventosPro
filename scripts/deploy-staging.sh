#!/bin/bash

################################################################################
# EventosPro - Automated Staging Deployment Script
#
# This script automates the deployment process for the staging environment
#
# Usage: ./scripts/deploy-staging.sh
################################################################################

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Emojis
CHECK="✅"
CROSS="❌"
ROCKET="🚀"
WRENCH="🔧"
WARNING="⚠️"
INFO="ℹ️"

# Configuration
PROJECT_DIR="/home/gacpac/projects/EventosPro"
DOCKER_COMPOSE="docker compose -f docker-compose.production.yml"

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

log_section() {
    echo ""
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${BLUE}${WRENCH} $1${NC}"
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""
}

################################################################################
# Pre-flight Checks
################################################################################

log_section "Starting Staging Deployment"

# Check if running in correct directory
if [ ! -f "docker-compose.production.yml" ]; then
    log_error "docker-compose.production.yml not found!"
    log_error "Please run this script from the project root: $PROJECT_DIR"
    exit 1
fi

# Check if .env exists
if [ ! -f ".env" ]; then
    log_error ".env file not found!"
    log_warning "Copy .env.staging.example to .env and configure it"
    exit 1
fi

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    log_error "Docker is not running!"
    exit 1
fi

log_success "Pre-flight checks passed"

################################################################################
# Display Current State
################################################################################

log_section "Current Environment State"

echo "📍 Current Git Branch:"
git branch --show-current

echo ""
echo "📝 Current Commit:"
git log -1 --oneline

echo ""
echo "🐳 Current Containers:"
$DOCKER_COMPOSE ps --format "table {{.Name}}\t{{.Status}}" 2>/dev/null || echo "No containers running"

################################################################################
# Update Code
################################################################################

log_section "Updating Code from Git"

# Stash any local changes
if ! git diff-index --quiet HEAD --; then
    log_warning "Local changes detected, stashing..."
    git stash
fi

# Pull latest changes
log_info "Pulling latest changes from origin/main..."
git pull origin main

log_success "Code updated to: $(git log -1 --oneline)"

################################################################################
# Rebuild Containers
################################################################################

log_section "Rebuilding Docker Containers"

log_info "Building containers (this may take a few minutes)..."
$DOCKER_COMPOSE build

log_info "Starting/restarting containers..."
$DOCKER_COMPOSE up -d

log_info "Waiting for containers to be healthy (30 seconds)..."
sleep 30

log_success "Containers rebuilt and started"

################################################################################
# Run Migrations
################################################################################

log_section "Database Migrations"

# Check if there are pending migrations
if $DOCKER_COMPOSE exec -T app php artisan migrate:status | grep -q "Pending"; then
    log_warning "Pending migrations detected"

    log_info "Running migrations..."
    $DOCKER_COMPOSE exec -T app php artisan migrate --force

    log_success "Migrations completed"
else
    log_info "No pending migrations"
fi

################################################################################
# Optimize Laravel
################################################################################

log_section "Optimizing Laravel"

log_info "Clearing old caches..."
$DOCKER_COMPOSE exec -T app php artisan optimize:clear

log_info "Caching config, routes, and views..."
$DOCKER_COMPOSE exec -T app php artisan config:cache
$DOCKER_COMPOSE exec -T app php artisan route:cache
$DOCKER_COMPOSE exec -T app php artisan view:cache

log_success "Laravel optimized for performance"

################################################################################
# Restart Queue Workers
################################################################################

log_section "Restarting Queue Workers"

log_info "Restarting all queue workers..."
$DOCKER_COMPOSE exec -T app supervisorctl restart laravel-worker:*

sleep 3

log_info "Queue worker status:"
$DOCKER_COMPOSE exec -T app supervisorctl status

log_success "Queue workers restarted"

################################################################################
# Health Checks
################################################################################

log_section "Running Health Checks"

# Check container health
log_info "Checking container health..."
if $DOCKER_COMPOSE ps | grep -q "unhealthy"; then
    log_warning "Some containers are unhealthy"
    $DOCKER_COMPOSE ps
else
    log_success "All containers are healthy"
fi

# Check application health (if endpoint exists)
if command -v curl &> /dev/null; then
    log_info "Testing application endpoint..."
    if curl -f -s http://localhost/health > /dev/null 2>&1; then
        log_success "Application is responding"
    else
        log_warning "Health endpoint not responding (this may be normal if not configured)"
    fi
fi

################################################################################
# Display Recent Logs
################################################################################

log_section "Recent Application Logs"

echo "Last 20 log lines:"
$DOCKER_COMPOSE logs --tail=20 app

################################################################################
# Resource Usage
################################################################################

log_section "Resource Usage"

docker stats --no-stream --format "table {{.Name}}\t{{.CPUPerc}}\t{{.MemUsage}}"

################################################################################
# Deployment Summary
################################################################################

log_section "Deployment Summary"

echo "${ROCKET} STAGING DEPLOYMENT COMPLETED SUCCESSFULLY!"
echo ""
echo "Environment: STAGING"
echo "Commit: $(git log -1 --oneline)"
echo "Time: $(date '+%Y-%m-%d %H:%M:%S')"
echo ""
echo "${INFO} Next Steps:"
echo "  1. Test the application in your browser"
echo "  2. Verify new features are working"
echo "  3. Check for errors in logs: $DOCKER_COMPOSE logs -f app"
echo "  4. Monitor resources: docker stats"
echo ""
echo "📚 Full validation checklist: docs/STAGING_DEPLOY.md"
echo ""

log_success "Ready for testing!"

exit 0
