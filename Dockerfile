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

# Instalar extensiones PHP necesarias para Laravel

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

# Carpeta de trabajo

WORKDIR /app

# Copiar archivos del proyecto

COPY . .

# Instalar dependencias PHP

RUN composer install --no-dev --optimize-autoloader

# Instalar dependencias frontend y compilar assets

RUN npm install && npm run build

# Crear carpetas necesarias (si el volumen aún no existe)

RUN mkdir -p /app/storage 
/app/storage/app 
/app/storage/app/public 
/app/storage/framework 
/app/storage/logs 
/app/bootstrap/cache

# Configurar Nginx

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

# Exponer puerto

EXPOSE 8001

# Script de inicio

RUN printf "#!/bin/sh\n
echo 'Configurando permisos...'\n
chown -R www-data:www-data /app/storage\n
chmod -R 775 /app/storage\n
chmod -R 775 /app/bootstrap/cache\n
\n
echo 'Creando enlace de storage...'\n
php artisan storage:link --force || true\n
\n
echo 'Iniciando PHP-FPM...'\n
php-fpm -D\n
\n
echo 'Iniciando Nginx...'\n
nginx -g 'daemon off;'\n" > /app/start.sh 
&& chmod +x /app/start.sh

CMD ["/app/start.sh"]
