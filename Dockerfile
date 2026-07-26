FROM dunglas/frankenphp:1-php8.4

# Installer les dépendances système
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    mariadb-client-compat \
    --no-install-recommends && \
    rm -rf /var/lib/apt/lists/*

# Installer les extensions PHP
RUN install-php-extensions \
    gd \
    intl \
    zip \
    mysqli \
    pdo_mysql \
    exif \
    opcache

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock* ./

RUN composer install --no-dev --optimize-autoloader --no-scripts

COPY . .

RUN composer dump-autoload --optimize

# Créer les répertoires nécessaires
RUN mkdir -p storage/logs storage/exports storage/qrcodes public/uploads/photos public/uploads/justificatifs && \
    chmod -R 755 storage public

EXPOSE 8000

# Pour Railway, le port est défini via la variable d'environnement $PORT
CMD sh -c "frankenphp php-server --root /app/public --addr 0.0.0.0:${PORT:-8000}"