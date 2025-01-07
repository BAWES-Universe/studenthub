# Use an official PHP image with Apache
FROM php:8.2-apache

# Install required PHP extensions
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    unzip \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql zip opcache

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory in the container
WORKDIR /var/www/html

# Copy application files to the container
COPY . /var/www/html

# Set permissions for the web server
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html

RUN cd /var/www/html

#RUN ./init --env=Krushn --overwrite=All

#RUN ./yii migrate

# Expose port 80
EXPOSE 80

# Set the entry point
CMD ["apache2-foreground"]
