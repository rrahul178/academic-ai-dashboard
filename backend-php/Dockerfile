FROM php:8.2-cli
RUN apt-get update && apt-get install -y libsqlite3-dev && docker-php-ext-install pdo pdo_sqlite
WORKDIR /app
COPY . .
RUN mkdir -p data && chmod -R 777 data
CMD ["sh", "-c", "php -S 0.0.0.0:$PORT -t ."]
