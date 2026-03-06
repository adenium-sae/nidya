FROM php:8.2-fpm-alpine

# Instalar dependencias del sistema y extensiones de PHP
RUN apk add --no-cache \
    nginx \
    wget \
    git \
    nodejs \
    npm \
    postgresql-dev \
    libpq \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql gd

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Directorio de trabajo
WORKDIR /app
COPY . .

# Instalar dependencias (Sin dev para que pese menos)
RUN composer install --no-dev --optimize-autoloader
RUN npm install && npm run build

# PERMISOS: Aseguramos que www-data sea dueño de todo el storage
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache && \
    chmod -R 775 /app/storage

# Configuración de Nginx con límite de subida aumentado
RUN printf 'server {\n\
    listen 8001;\n\
    client_max_body_size 20M;\n\
    root /app/public;\n\
    index index.php index.html;\n\
    location / {\n\
        try_files $uri $uri/ /index.php?$query_string;\n\
    }\n\
    location /storage/ {\n\
        add_header "Access-Control-Allow-Origin" "*";\n\
    }\n\
    location ~ \.php$ {\n\
        fastcgi_pass 127.0.0.1:9000;\n\
        fastcgi_index index.php;\n\
        include fastcgi_params;\n\
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;\n\
    }\n\
}' > /etc/nginx/http.d/default.conf

EXPOSE 8001

# Comando de inicio: Forzamos el link del storage en cada arranque
CMD php artisan storage:link --force && php-fpm -D && nginx -g "daemon off;