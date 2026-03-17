FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    libpng-dev \
    netcat-openbsd \
    && docker-php-ext-install pdo_mysql gd

WORKDIR /var/www/html

COPY setup.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/setup.sh

ENTRYPOINT ["setup.sh"]