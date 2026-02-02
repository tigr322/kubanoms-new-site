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

# Устанавливаем переменные окружения
ENV APP_ENV=production
ENV APP_DEBUG=false
ENV APP_URL=http://localhost

# Копируем composer.json и устанавливаем зависимости
COPY composer.json ./
ENV COMPOSER_PROCESS_TIMEOUT=600
RUN composer install --no-dev --optimize-autoloader
# Копируем package.json и устанавливаем зависимости
COPY package.json ./
RUN npm install --no-audit --no-fund --unsafe-perm=true --allow-root

# Копируем приложение
COPY . .

# Собираем ассеты
RUN npm run build --unsafe-perm=true --allow-root

# Убедимся что директория public/build существует
RUN mkdir -p public/build

# Создаем символическую ссылку для storage
RUN php artisan storage:link

# Создаем базу данных
RUN touch database/database.sqlite && chown nginx:nginx database/database.sqlite

# Устанавливаем права
RUN chown -R nginx:nginx /var/www && chmod -R 755 /var/www/storage && chmod -R 755 /var/www/public

# Очищаем кэш для уменьшения размера образа
RUN composer clear-cache && npm cache clean --force --unsafe-perm=true --allow-root

# Настраиваем Supervisor
RUN mkdir -p /etc/supervisor/conf.d

# Создаем конфигурацию Supervisor
RUN echo '[unix_http_server]' > /etc/supervisor/conf.d/supervisord.conf && \
    echo 'file=/var/run/supervisor.sock' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo '' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo '[supervisord]' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'logfile=/var/log/supervisor.log' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'logfile_maxbytes=50MB' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'logfile_backups=10' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'loglevel=info' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'pidfile=/var/run/supervisord.pid' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'nodaemon=true' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo '' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo '[rpcinterface:supervisor]' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'supervisor.rpcinterface_factory = supervisor.rpcinterface:make_main_rpcinterface' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo '' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo '[supervisorctl]' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'serverurl=unix:///var/run/supervisor.sock' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo '' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo '[program:php-fpm]' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'command=php-fpm' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'autostart=true' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'autorestart=true' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'priority=5' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo '' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo '[program:nginx]' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'command=nginx -g "daemon off;"' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'autostart=true' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'autorestart=true' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'priority=10' >> /etc/supervisor/conf.d/supervisord.conf

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
