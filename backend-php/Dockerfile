FROM php:8.2-cli 
RUN docker-php-ext-install pdo pdo_sqlite 
WORKDIR /app 
COPY . . 
RUN mkdir -p data && chmod -R 777 data 
CMD ["sh", "-c", "php -S 0.0.0.0:$PORT -t ."]
