FROM php:8.2-apache 
RUN docker-php-ext-install mysqli pdo pdo_mysql 
COPY pages/ /var/www/html/ 
