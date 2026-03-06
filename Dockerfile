FROM php:8.2-fpm-alpine

# Dependencias y extensiones
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

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer install --no-dev --optimize-autoloader
RUN npm install && npm run build

# Permisos iniciales
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache

# Configuración de Nginx usando HEREDOC (evita errores de sintaxis)
RUN cat <<EOF > /etc/nginx/http.d/default.conf
server {
    listen 8001;
    client_max_body_size 20M;
    root /app/public;
    index index.php index.html;
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }
    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
    }
}
EOF

EXPOSE 8001

# Script de arranque para asegurar el enlace simbólico cada vez
RUN printf "#!/bin/sh\n\
php artisan storage:link --force\n\
php-fpm -D\n\
nginx -g 'daemon off;'\n" > /app/start.sh && chmod +x /app/start.sh

CMD ["/app/start.sh"]