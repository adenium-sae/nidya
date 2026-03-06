FROM php:8.2-fpm-alpine

# Instalar dependencias del sistema

RUN apk add --no-cache 
nginx 
wget 
git 
nodejs 
npm 
postgresql-dev 
libpq 
libpng-dev 
libjpeg-turbo-dev 
freetype-dev 
oniguruma-dev 
icu-dev 
bash

# Instalar extensiones PHP necesarias

RUN docker-php-ext-configure gd --with-freetype --with-jpeg 
&& docker-php-ext-install 
pdo 
pdo_pgsql 
gd 
intl 
mbstring 
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

RUN mkdir -p 
/app/storage/app/public 
/app/storage/framework 
/app/storage/logs 
/app/bootstrap/cache

# Configurar nginx

RUN cat <<EOF > /etc/nginx/http.d/default.conf
server {
listen 8001;
client_max_body_size 50M;

```
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
```

}
EOF

EXPOSE 8001

# Script de inicio

RUN printf "#!/bin/sh\n
echo 'Fixing permissions...'\n
chown -R www-data:www-data /app/storage\n
chmod -R 775 /app/storage\n
chmod -R 775 /app/bootstrap/cache\n
\n
echo 'Creating storage link...'\n
php artisan storage:link --force || true\n
\n
php-fpm -D\n
nginx -g 'daemon off;'\n" > /app/start.sh && chmod +x /app/start.sh

CMD ["/app/start.sh"]
