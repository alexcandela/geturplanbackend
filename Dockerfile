# Imagen base con PHP 8.2 y Apache
FROM php:8.2-apache

# Instalar dependencias necesarias
RUN apt-get update && apt-get install -y \
    git curl zip unzip libpq-dev libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql gd

# Instalar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Establecer directorio de trabajo
WORKDIR /var/www/html

# Copiar el proyecto al contenedor
COPY . .

# Instalar dependencias de PHP (Laravel)
RUN composer install --no-dev --optimize-autoloader

# Generar cachés de Laravel
# RUN php artisan config:cache && php artisan route:cache && php artisan view:cache

# Exponer puerto
EXPOSE 8080

# Comando de arranque
CMD php artisan serve --host=0.0.0.0 --port=8080
