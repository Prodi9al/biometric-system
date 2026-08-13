#!/bin/bash
set -e

echo "Starting Biometric ATM PHP Service..."

# Wait for database to be ready
if [ -n "$DB_HOST" ] && [ -n "$DB_USER" ] && [ -n "$DB_PASS" ]; then
    echo "Waiting for database at $DB_HOST..."
    until mysqladmin ping -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" --silent 2>/dev/null; do
        sleep 2
        echo "Database not ready yet..."
    done
    echo "Database is ready!"
fi

# Generate secrets if not provided (for development only)
if [ -z "$SHARED_SECRET" ]; then
    echo "WARNING: SHARED_SECRET not set, generating random secret for development"
    export SHARED_SECRET=$(openssl rand -hex 32)
fi

if [ -z "$ENCRYPTION_KEY" ]; then
    echo "WARNING: ENCRYPTION_KEY not set, generating random key for development"
    export ENCRYPTION_KEY=$(openssl rand -hex 32)
fi

# Create .env file for PHP to read
cat > /var/www/html/.env <<EOF
DB_HOST=${DB_HOST:-localhost}
DB_USER=${DB_USER:-root}
DB_PASS=${DB_PASS:-}
DB_NAME=${DB_NAME:-biometric_atm_db}
SHARED_SECRET=${SHARED_SECRET}
ENCRYPTION_KEY=${ENCRYPTION_KEY}
BIOMETRIC_THRESHOLD=${BIOMETRIC_THRESHOLD:-85.0}
MAX_LOGIN_ATTEMPTS=${MAX_LOGIN_ATTEMPTS:-3}
SESSION_TIMEOUT=${SESSION_TIMEOUT:-1800}
APP_ENV=${APP_ENV:-production}
DEBUG=${DEBUG:-false}
EOF

# Initialize database if needed
if [ "$INIT_DB" = "true" ]; then
    echo "Initializing database..."
    cd /var/www/html && php create_tables.php
fi

# Set proper permissions
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html

# Configure PHP
sed -i "s/memory_limit = .*/memory_limit = ${PHP_MEMORY_LIMIT:-256M}/" /usr/local/etc/php/php.ini-production
sed -i "s/upload_max_filesize = .*/upload_max_filesize = ${PHP_UPLOAD_MAX_FILESIZE:-10M}/" /usr/local/etc/php/php.ini-production
sed -i "s/post_max_size = .*/post_max_size = ${PHP_POST_MAX_SIZE:-10M}/" /usr/local/etc/php/php.ini-production
sed -i "s/max_execution_time = .*/max_execution_time = ${PHP_MAX_EXECUTION_TIME:-60}/" /usr/local/etc/php/php.ini-production

# Enable OPcache for production
if [ "$APP_ENV" = "production" ]; then
    echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini
    echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/opcache.ini
    echo "opcache.interned_strings_buffer=8" >> /usr/local/etc/php/conf.d/opcache.ini
    echo "opcache.max_accelerated_files=4000" >> /usr/local/etc/php/conf.d/opcache.ini
    echo "opcache.revalidate_freq=60" >> /usr/local/etc/php/conf.d/opcache.ini
    echo "opcache.fast_shutdown=1" >> /usr/local/etc/php/conf.d/opcache.ini
fi

echo "PHP Service ready!"
exec "$@"