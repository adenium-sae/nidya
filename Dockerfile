FROM php:8.2-fpm-alpine

# Instalar dependencias del sistema y extensiones de PHP para Postgres
RUN apk add --no-cache \
    nginx \
    wget \
    git \
    nodejs \
    npm \
    postgresql-dev \
    libpq \
    # Dependencias necesarias para GD
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql gd

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar directorio de trabajo
WORKDIR /app
COPY . .

# Instalar dependencias de PHP y JS
RUN composer install --no-dev --optimize-autoloader
RUN npm install && npm run build

# Configurar permisos para Laravel
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache

RUN printf 'server {\n\
    listen 8001;\n\
    root /app/public;\n\
    index index.php index.html;\n\
    location / {\n\
        try_files $uri $uri/ /index.php?$query_string;\n\
    }\n\
    location ~ \.php$ {\n\
        fastcgi_pass 127.0.0.1:9000;\n\
        fastcgi_index index.php;\n\
        include fastcgi_params;\n\
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;\n\
    }\n\
}' > /etc/nginx/http.d/default.conf

# Exponer el puerto
EXPOSE 8001

# Comando para iniciar Nginx y PHP-FPM
CMD php-fpm -D && nginx -g "daemon off;"