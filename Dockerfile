FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    libpng-dev \
    netcat-openbsd \
    && docker-php-ext-install pdo_mysql gd

WORKDIR /var/www/html

COPY setup.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/setup.sh

ENTRYPOINT ["setup.sh"]

# Installa msmtp (Email detector)
RUN apt-get update && apt-get install -y msmtp && rm -rf /var/lib/apt/lists/*

# Crea il file di configurazione per msmtp
RUN echo "account default" > /etc/msmtprc && \
    echo "host mailhog" >> /etc/msmtprc && \
    echo "port 1025" >> /etc/msmtprc && \
    echo "from no-reply@camagru.it" >> /etc/msmtprc && \
    echo "auth off" >> /etc/msmtprc && \
    echo "tls off" >> /etc/msmtprc

# Diciamo a PHP di usare msmtp per la funzione mail()
RUN echo "sendmail_path = \"/usr/bin/msmtp -t\"" > /usr/local/etc/php/conf.d/php-sendmail.ini