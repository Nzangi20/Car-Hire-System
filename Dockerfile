FROM php:8.2-apache

# Install PDO MySQL extension
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache rewrite module
RUN a2enmod rewrite

# Copy project files
COPY . /var/www/html/

# Create uploads directory and set permissions for Apache
RUN mkdir -p /var/www/html/frontend/uploads && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 775 /var/www/html/frontend/uploads

# Expose standard web port
EXPOSE 80
