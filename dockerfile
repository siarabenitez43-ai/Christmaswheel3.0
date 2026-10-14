# Use an official PHP runtime as a parent image
FROM php:8.2-fpm

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    nginx \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo_mysql mysqli mbstring exif pcntl bcmath gd

# Set the working directory
WORKDIR /var/www/html

# Copy the current directory contents into the container
COPY . /var/www/html

# Copy Nginx configuration
COPY ./nginx.conf /etc/nginx/nginx.conf

# Create a directory for the socket
RUN mkdir -p /run/php

# Give proper permissions to the web directory
RUN chown -R www-data:www-data /var/www/html

# Expose port 10000 (Render's default expected port)
EXPOSE 10000

# Start PHP-FPM and Nginx
CMD service nginx start && php-fpm