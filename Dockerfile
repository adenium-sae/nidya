# syntax=docker/dockerfile:1.4
FROM php:8.2-fpm-alpine

# Instalar dependencias del sistema
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
    oniguruma-dev \
    icu-dev \
    bash

# Instalar extensiones PHP necesarias
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
    pdo \
    pdo_pgsql \
    gd \
    intl \
    mbstring \
    opcache

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copiar proyecto
COPY . .

# Instalar dependencias PHP
RUN composer install --no-dev --optimize-autoloader

# Compilar frontend
RUN npm install && npm run build

# Crear carpetas necesarias
RUN mkdir -p \
    /app/storage/app/public \
    /app/storage/framework \
    /app/storage/logs \
    /app/bootstrap/cache

# Configurar nginx
COPY <<'EOF' /etc/nginx/http.d/default.conf
server {
    listen 8001;
    client_max_body_size 50M;

    root /app/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
EOF

EXPOSE 8001

# Script de inicio
COPY start.sh /app/start.sh
RUN chmod +x /app/start.sh

CMD ["/app/start.sh"]
