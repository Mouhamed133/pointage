FROM dunglas/frankenphp:1-php8.4

RUN install-php-extensions \
    gd \
    intl \
    zip \
    mysqli \
    pdo_mysql \
    exif

WORKDIR /app

COPY . .

RUN composer install --no-dev --optimize-autoloader

EXPOSE 80

CMD ["frankenphp", "run"]