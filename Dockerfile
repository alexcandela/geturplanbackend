# 1. Base
FROM php:8.2-fpm

# 2. Dependencias del sistema
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libzip-dev \
    zip \
    && docker-php-ext-install pdo_pgsql

# 3. Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 4. Directorio de trabajo
WORKDIR /var/www/html

# 5. Copiar proyecto
COPY . .

# 6. Instalar dependencias de Laravel
RUN composer install --no-dev --optimize-autoloader

# 7. Crear carpetas necesarias y dar permisos
RUN mkdir -p storage/framework/views storage/framework/cache storage/logs bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# 8. Generar APP_KEY y JWT_SECRET si no existen
RUN php artisan key:generate --ansi
RUN php artisan jwt:secret --ansi

# 9. Enlazar storage
RUN php artisan storage:link

# 10. Ejecutar migraciones y seed
RUN php artisan migrate --force
RUN php artisan db:seed --force

# 11. Exponer puerto
EXPOSE 8000

# 12. Comando para ejecutar Laravel
CMD php artisan serve --host=0.0.0.0 --port=8000
