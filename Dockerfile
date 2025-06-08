FROM php:8.4-apache

# Установка зависимостей и расширений PHP
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    libxml2-dev \
    zlib1g-dev \
    default-libmysqlclient-dev \
    libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql xml mbstring bcmath \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Установка Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Сначала копируем файлы Composer и устанавливаем зависимости
COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Теперь копируем остальные файлы проекта
COPY . .

# Делаем директорию безопасной для git
RUN git config --global --add safe.directory /var/www/html

# Настройка Apache
RUN a2enmod rewrite

# Создаем директории для загрузок и устанавливаем права
RUN mkdir -p /var/www/html/public/uploads /var/www/html/public/images/services && \
    chown -R www-data:www-data /var/www/html/public/uploads /var/www/html/public/images/services /var/www/html/tmp && \
    chmod -R 755 /var/www/html/public/uploads /var/www/html/public/images/services /var/www/html/tmp

# Права для Apache
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|g' /etc/apache2/sites-available/000-default.conf