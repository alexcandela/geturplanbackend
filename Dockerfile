# Imagen base PHP 8.2 + Apache
FROM php:8.2-apache

# Instalar dependencias necesarias
RUN apt-get update && apt-get install -y \
    git curl zip unzip libpq-dev libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-install pdo pdo_pgsql gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Directorio de trabajo
WORKDIR /var/www/html

# Copiar proyecto
COPY . .

# Copiar .env.example como .env
RUN cp .env.example .env

# Crear carpetas necesarias con permisos
RUN mkdir -p storage/framework/{sessions,views,cache} storage/logs bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Instalar dependencias de Laravel
RUN composer install --no-dev --optimize-autoloader

# Comando de arranque
CMD bash -c "\
    php artisan key:generate --force && \
    php artisan jwt:secret --force && \
    php artisan storage:link && \
    php artisan migrate --force && \
    php artisan serve --host=0.0.0.0 --port=8080"
