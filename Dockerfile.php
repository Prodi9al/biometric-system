FROM php:8.2-apache

ARG BUILD_DATE
ARG VERSION
LABEL maintainer="Biometric ATM System" \
      org.opencontainers.image.created=$BUILD_DATE \
      org.opencontainers.image.version=$VERSION \
      org.opencontainers.image.title="Biometric ATM - PHP Service" \
      org.opencontainers.image.description="Secure biometric authentication system"

# Install system dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    libssl-dev \
    libcurl4-openssl-dev \
    unzip \
    git \
    ca-certificates \
    && rm -rf /var/lib/apt/lists/*

# Configure and install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    pdo_pgsql \
    mbstring \
    zip \
    gd \
    bcmath \
    opcache \
    xml \
    curl

# Enable Apache modules
RUN a2enmod rewrite headers ssl expires

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application code
COPY --chown=www-data:www-data . .

# Configure Apache
COPY docker/apache/vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/apache/security.conf /etc/apache2/conf-available/security.conf
RUN a2enconf security

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \; \
    && chmod -R 775 /var/www/html/admin/debug_trace.txt 2>/dev/null || true

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=30s --retries=3 \
    CMD curl -f http://localhost/health.php || exit 1

# Expose port
EXPOSE 80

# Use entrypoint script
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]