FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    libpng-dev \
    netcat-openbsd \
    msmtp \
    && docker-php-ext-install pdo_mysql gd \
    && rm -rf /var/lib/apt/lists/*

RUN echo "account default" > /etc/msmtprc && \
    echo "host mailhog" >> /etc/msmtprc && \
    echo "port 1025" >> /etc/msmtprc && \
    echo "from no-reply@camagru.it" >> /etc/msmtprc && \
    echo "auth off" >> /etc/msmtprc && \
    echo "tls off" >> /etc/msmtprc

RUN echo "sendmail_path = \"/usr/bin/msmtp -t\"" > /usr/local/etc/php/conf.d/php-sendmail.ini

WORKDIR /var/www/html

COPY setup.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/setup.sh

ENTRYPOINT ["setup.sh"]