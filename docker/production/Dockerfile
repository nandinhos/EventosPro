# EventosPro - Optimized Production Dockerfile for VPS
# Target: 2 vCPU, 4GB RAM, 50GB Disk
# Base image size: ~5MB (Alpine) vs ~200MB (Ubuntu)
# Final image size: ~200-250MB vs ~800MB

# ============================================
# STAGE 1: Build Frontend Assets with Node.js
# ============================================
FROM node:22-alpine AS node-builder

WORKDIR /app

# Copy package files
COPY package*.json ./

# Install dependencies (will be discarded after build)
RUN npm ci --only=production --no-audit --no-fund

# Copy source files needed for build
COPY resources ./resources
COPY public ./public
COPY vite.config.js ./
COPY tailwind.config.js ./
COPY postcss.config.js ./

# Build assets
RUN npm run build

# ============================================
# STAGE 2: PHP Production Image (Alpine-based)
# ============================================
FROM php:8.4-fpm-alpine

LABEL maintainer="EventosPro Team"
LABEL description="Optimized Laravel EventosPro for VPS deployment"

# Set working directory
WORKDIR /var/www/html

# Install system dependencies (Alpine packages)
# Only essential packages - removed: postgresql, mongodb, ffmpeg, etc.
RUN apk add --no-cache \
    # Core utilities
    bash \
    curl \
    git \
    unzip \
    supervisor \
    # MySQL client
    mysql-client \
    # Image processing (GD dependencies)
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    # Compression libraries
    libzip-dev \
    zlib-dev \
    # Internationalization
    icu-dev \
    # XML processing
    libxml2-dev \
    # TLS/SSL
    openssl \
    # Process management
    shadow \
    # Cleanup
    && rm -rf /var/cache/apk/*

# Install PHP extensions (ONLY what EventosPro actually uses)
# REMOVED: pgsql, mongodb, imap, ldap, swoole, memcached, xdebug, pcov
RUN docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    # Database
    pdo_mysql \
    mysqli \
    # String operations
    mbstring \
    # Financial calculations
    bcmath \
    # Compression (Excel generation)
    zip \
    # Internationalization
    intl \
    # Image processing (PDF generation)
    gd \
    # XML (Excel/PDF)
    xml \
    soap \
    # OPcache (performance)
    opcache \
    # Process control
    pcntl \
    # Sockets (Redis)
    sockets

# Install Redis extension via PECL (for cache/sessions/queue)
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis-6.1.0 \
    && docker-php-ext-enable redis \
    && apk del .build-deps \
    && rm -rf /tmp/pear

# Install Composer (production version)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Configure PHP for production (OPcache optimized)
COPY docker/production/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/production/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Configure PHP-FPM (tuned for 4GB RAM VPS)
COPY docker/production/php-fpm.conf /usr/local/etc/php-fpm.d/zzz-custom.conf

# Copy Supervisor configuration (manages queue worker)
COPY docker/production/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Create application user (non-root for security)
RUN addgroup -g 1000 appuser \
    && adduser -D -u 1000 -G appuser appuser

# Copy application code
COPY --chown=appuser:appuser . /var/www/html

# Copy built assets from Node.js stage (no node_modules!)
COPY --from=node-builder --chown=appuser:appuser /app/public/build /var/www/html/public/build

# Install Composer dependencies (production only, no dev packages)
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader \
    && composer clear-cache

# Set correct permissions
RUN chown -R appuser:appuser /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Create directories for supervisor logs
RUN mkdir -p /var/log/supervisor \
    && chown -R appuser:appuser /var/log/supervisor

# Switch to non-root user
USER appuser

# Expose PHP-FPM port
EXPOSE 9000

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s --retries=3 \
    CMD php-fpm -t || exit 1

# Start Supervisor (manages PHP-FPM + queue worker)
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
