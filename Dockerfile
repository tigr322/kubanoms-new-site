FROM php:8.5.1-fpm-alpine

# Устанавливаем зависимости
RUN apk add --no-cache nginx sqlite sqlite-dev curl zip unzip libzip-dev icu-dev supervisor \
    build-base autoconf make gcc g++ libc-dev file re2c dpkg dpkg-dev git

# Устанавливаем PHP расширения
RUN docker-php-ext-install pdo_sqlite zip intl

# Удаляем build-зависимости
RUN apk del build-base autoconf make gcc g++ libc-dev file re2c dpkg dpkg-dev

# Устанавливаем Composer
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

# Устанавливаем Node.js
RUN apk add --no-cache nodejs npm

# Настраиваем nginx
RUN echo 'events { worker_connections 1024; } \
http { \
    include /etc/nginx/mime.types; \
    default_type application/octet-stream; \
    server { \
        listen 80; \
        root /var/www/public; \
        index index.php; \
        location / { \
            try_files $uri $uri/ /index.php?$query_string; \
        } \
        location ~ \.php$ { \
            fastcgi_pass 127.0.0.1:9000; \
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; \
            include fastcgi_params; \
        } \
        location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ { \
            expires 1y; \
            add_header Cache-Control "public, immutable"; \
            try_files $uri =404; \
        } \
    } \
}' > /etc/nginx/nginx.conf

# Настраиваем PHP-FPM
RUN sed -i 's/user = www-data/user = nginx/' /usr/local/etc/php-fpm.d/www.conf && \
    sed -i 's/group = www-data/group = nginx/' /usr/local/etc/php-fpm.d/www.conf

WORKDIR /var/www


# Копируем приложение
COPY . .

# Создаем .env файл для production из docker конфигурации
RUN cp .env.example .env

# Устанавливаем зависимости Composer с увеличенным таймаутом
ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_NO_INTERACTION=1 \
    COMPOSER_PROCESS_TIMEOUT=1200

RUN composer config -g process-timeout 1200 && \
    composer install --no-dev --optimize-autoloader --prefer-dist --no-progress

# Генерируем ключ приложения
RUN php artisan key:generate --force

# Устанавливаем зависимости Node.js
RUN npm install --no-audit --no-fund --unsafe-perm=true --allow-root

# Собираем ассеты
RUN npm run build --unsafe-perm=true --allow-root

# Убедимся что директория public/build существует
RUN mkdir -p public/build

# Создаем символическую ссылку для storage
RUN php artisan storage:link

# Создаем базу данных
RUN touch database/database.sqlite && chown nginx:nginx database/database.sqlite

# Запускаем миграции и сидирование
RUN php artisan migrate --force && \
    php artisan db:seed --force

# Публикуем Livewire ассеты
RUN php artisan livewire:publish --assets

# Устанавливаем права - поэтапно для оптимизации
RUN chown nginx:nginx /var/www/storage /var/www/public /var/www/bootstrap/cache

RUN chmod -R 755 /var/www/storage /var/www/public

RUN chmod -R 775 /var/www/storage/logs /var/www/storage/framework/cache /var/www/storage/framework/sessions /var/www/storage/framework/views

# Очищаем кэш для уменьшения размера образа
RUN composer clear-cache && npm cache clean --force --unsafe-perm=true --allow-root

# Устанавливаем правильные права доступа для Laravel
RUN chown -R nginx:nginx /var/www && \
    chmod -R 755 /var/www && \
    chmod -R 775 /var/www/storage /var/www/bootstrap/cache && \
    chmod 644 /var/www/bootstrap/app.php /var/www/bootstrap/providers.php


EXPOSE 80

CMD ["php", "/var/www/artisan", "serve", "--host=0.0.0.0", "--port=80"]
