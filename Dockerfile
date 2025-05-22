FROM php:8.4-apache

# Установка зависимостей и расширений PHP
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql mbstring zip

# Установка Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Установка mPDF
RUN composer require mpdf/mpdf

# Настройка Apache
RUN a2enmod rewrite
COPY ./public /var/www/html
WORKDIR /var/www/html

# Права для Apache
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html