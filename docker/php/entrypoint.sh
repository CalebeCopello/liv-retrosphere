#!/bin/sh

set -e

cd /var/www/html

create_laravel_directories() {
    mkdir -p \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/testing \
        storage/framework/views \
        storage/logs \
        bootstrap/cache
}

configure_permissions() {
    chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true
}

install_composer_dependencies() {
    if [ ! -f composer.json ]; then
        echo "composer.json was not found. Skipping Composer installation."
        return
    fi

    if [ ! -f vendor/autoload.php ]; then
        echo "Installing Composer dependencies..."

        composer install \
            --no-interaction \
            --prefer-dist
    fi
}

prepare_laravel() {
    if [ ! -f artisan ]; then
        echo "Laravel has not been created yet."
        return
    fi

    create_laravel_directories
    configure_permissions
    install_composer_dependencies

    if [ -f .env ] && grep -q '^APP_KEY=$' .env; then
        echo "Generating Laravel application key..."
        php artisan key:generate --no-interaction
    fi
}

if [ "${CONTAINER_ROLE:-app}" = "app" ]; then
    prepare_laravel
fi

exec "$@"