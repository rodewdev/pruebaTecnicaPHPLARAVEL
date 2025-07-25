#!/bin/bash

# Wait for database to be ready
echo "Waiting for database connection..."

# Set default values if not provided
export APP_ENV=${APP_ENV:-production}
export APP_DEBUG=${APP_DEBUG:-false}
export APP_KEY=${APP_KEY:-base64:$(openssl rand -base64 32)}
export DB_CONNECTION=${DB_CONNECTION:-mysql}

# Run migrations if database is available
if [ "$DB_CONNECTION" = "mysql" ]; then
    echo "Running database migrations..."
    php artisan migrate --force || echo "Migration failed, continuing..."
fi

# Clear cache and regenerate docs
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan scribe:generate

# Start Apache
echo "Starting Apache..."
apache2-foreground