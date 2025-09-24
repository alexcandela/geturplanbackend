# Imagen base PHP 8.2 + Apache
FROM php:8.2-apache

# Instalar dependencias necesarias
RUN apt-get update && apt-get install -y \
    git curl zip unzip libpq-dev libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-install pdo pdo_pgsql gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Establecer directorio de trabajo
WORKDIR /var/www/html

# Copiar proyecto
COPY . .

# Crear .env si no existe
RUN if [ ! -f .env ]; then cp .env.example .env; fi

# Instalar dependencias de Laravel
RUN composer install --no-dev --optimize-autoloader

# Comando de arranque: APP_KEY, caches, migraciones, servidor
CMD php artisan key:generate --force && \
    php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache && \
    php artisan migrate --force && \
    php artisan serve --host=0.0.0.0 --port=8080
