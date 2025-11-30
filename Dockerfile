FROM php:8.2-apache

# Install extensions for connecting to MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache Rewrite Module (Optional but recommended)
RUN a2enmod rewrite

# Copy source code
COPY ./src /var/www/html