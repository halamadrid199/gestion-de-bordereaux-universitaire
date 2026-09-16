FROM php:8.2-apache 
RUN docker-php-ext-install mysqli pdo pdo_mysql 
COPY pages/ /var/www/html/ 
RUN sed -i 's!/var/www/html!/var/www/html/pages!g' /etc/apache2/sites-available/000-default.conf 
RUN echo "DirectoryIndex connexion.php" 
