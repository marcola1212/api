FROM php:8.2-apache

# Copie os arquivos do projeto para o container
COPY . /var/www/html/

# Exponha a porta 80 para o servidor web
EXPOSE 80
