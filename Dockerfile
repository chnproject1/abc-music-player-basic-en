FROM php:8.2-apache

RUN a2enmod rewrite

COPY . /var/www/html/

RUN { \
    echo '<VirtualHost *:80>'; \
    echo '    DocumentRoot /var/www/html'; \
    echo '    <Directory /var/www/html>'; \
    echo '        Options -Indexes'; \
    echo '        AllowOverride None'; \
    echo '        RewriteEngine On'; \
    echo '        RewriteCond %{REQUEST_FILENAME} !-f'; \
    echo '        RewriteRule ^ index.php [L]'; \
    echo '    </Directory>'; \
    echo '</VirtualHost>'; \
} > /etc/apache2/sites-available/000-default.conf

EXPOSE 80
