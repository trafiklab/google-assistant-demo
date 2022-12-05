FROM php:8.1-fpm

# Set working directory
WORKDIR /var/www

# Install dependencies
RUN apt-get update
RUN apt-get install -y build-essential locales libcurl4-gnutls-dev gnupg2 libonig-dev libmcrypt-dev nano zip unzip git curl

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install extensions
RUN docker-php-ext-install curl

# Add user for laravel application
RUN groupadd -g 1000 www
RUN useradd -u 1000 -ms /bin/bash -g www www

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy existing application directory contents
COPY --chown=www:www . /var/www
RUN chown www:www /var/www

# Change current user to www
USER www

RUN composer install

# Expose port 9000 and start php-fpm server
EXPOSE 9000
EXPOSE 8081
CMD php -S 0.0.0.0:8081 -t /var/www/public/
