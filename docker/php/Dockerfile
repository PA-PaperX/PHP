FROM php:8.3-apache

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Configure Apache
RUN echo '<Directory /var/www/html>\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/custom.conf \
    && a2enconf custom

# Configure PHP
RUN echo 'upload_max_filesize = 10M\n\
post_max_size = 10M\n\
session.cookie_samesite = None\n\
session.cookie_secure = false' > /usr/local/etc/php/conf.d/custom.ini

EXPOSE 80
