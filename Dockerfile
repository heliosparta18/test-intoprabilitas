# Gunakan PHP 8.4 Alpine sebagai base image yang ringan
FROM php:8.4-cli-alpine

# Set working directory di dalam container
WORKDIR /app

# Install dependensi sistem yang dibutuhkan untuk ekstensi PHP dan SQLite
RUN apk add --no-cache \
    curl \
    git \
    unzip \
    libzip-dev \
    sqlite-dev \
    oniguruma-dev \
    linux-headers \
    && docker-php-ext-install pdo pdo_sqlite pcntl bcmath

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy semua file proyek ke dalam container
COPY . .

# Konfigurasi environment dan hak akses
RUN cp .env.example .env 2>/dev/null || true \
    && touch database/database.sqlite \
    && composer install --no-dev --optimize-autoloader --no-interaction \
    && php artisan key:generate --force \
    && php artisan migrate --force \
    && php artisan db:seed --force 2>/dev/null || true

# Port yang dibuka untuk HTTP API
EXPOSE 8000

# Jalankan server Laravel saat container start
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
