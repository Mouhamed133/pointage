FROM dunglas/frankenphp:1-php8.4

# Installer les dépendances système
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip

# Installer les extensions PHP
RUN install-php-extensions \
    gd \
    intl \
    zip \
    mysqli \
    pdo_mysql \
    exif

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . .

# Installer les dépendances PHP
RUN composer install --no-dev --optimize-autoloader

EXPOSE 80

CMD ["frankenphp", "run"]