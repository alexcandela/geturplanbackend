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

# 6. Copiar .env.example como .env si no existe
RUN cp .env.example .env || true

# 7. Instalar dependencias de Laravel
RUN composer install --no-dev --optimize-autoloader

# 8. Limpiar caches de Laravel (IMPORTANTE para CORS y variables nuevas)
# RUN php artisan config:clear && php artisan cache:clear && php artisan route:clear && php artisan view:clear

# 9. Crear carpetas necesarias y dar permisos
RUN mkdir -p storage/framework/views storage/framework/cache storage/logs bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# 10. Generar APP_KEY y JWT_SECRET solo si no existen
# (mejor definir APP_KEY y JWT_SECRET en Render directamente)
RUN php artisan key:generate --ansi || true
RUN php artisan jwt:secret --ansi || true

# 11. Enlazar storage
RUN php artisan storage:link || true

# 12. Ejecutar migraciones y seed
RUN php artisan migrate --force
RUN php artisan db:seed --force

# 13. Exponer puerto
EXPOSE 8000

# 14. Comando para ejecutar Laravel
CMD php artisan config:clear && php artisan cache:clear && php artisan route:clear && php artisan view:clear && \
    php artisan serve --host=0.0.0.0 --port=8000

