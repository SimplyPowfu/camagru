# Usa l'immagine ufficiale con Apache e PHP 8.2
FROM php:8.2-apache

# Abilita il modulo rewrite di Apache (fondamentale per il tuo router.php)
RUN a2enmod rewrite

# Installa le dipendenze di sistema necessarie (incluse zip e unzip per Composer)
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql gd zip

# Installa Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Imposta la DocumentRoot di Apache per puntare alla cartella "public"
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Imposta la directory di lavoro
WORKDIR /var/www/html

# Copia tutto il codice del progetto nel container
COPY . .

# ESEGUE COMPOSER INSTALL (Questa era la parte mancante!)
# Scarica Cloudinary, PHPMailer, ecc. direttamente nel container cloud
RUN composer install --no-dev --optimize-autoloader

# Sistema i permessi per Apache (assicurandoci che copra anche la nuova cartella vendor)
RUN chown -R www-data:www-data /var/www/html

# Esponi la porta per apache
CMD sed -i "s/80/${PORT}/g" /etc/apache2/ports.conf && \
    sed -i "s/:80/:${PORT}/g" /etc/apache2/sites-enabled/000-default.conf && \
    apache2-foreground
