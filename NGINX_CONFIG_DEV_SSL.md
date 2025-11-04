# 🔧 Конфигурация Nginx с SSL для DEV сервера

## Правильная конфигурация для test.warehouse.expwood.ru

### Обновить конфигурацию SSL блока

```bash
ssh my
sudo nano /etc/nginx/sites-available/test.warehouse.conf
```

### Правильная конфигурация для SSL блока (443):

```nginx
server {
    listen 443 ssl http2;
    server_name test.warehouse.expwood.ru;

    # SSL сертификаты от Let's Encrypt
    ssl_certificate /etc/letsencrypt/live/test.warehouse.expwood.ru/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/test.warehouse.expwood.ru/privkey.pem;

    # Современные SSL настройки
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;

    # HSTS header
    add_header Strict-Transport-Security "max-age=63072000" always;

    # ВАЖНО: Правильный root для Laravel
    root /var/www/test.warehouse/public;
    index index.php;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    access_log /var/log/nginx/test.warehouse.access.log;
    error_log /var/log/nginx/test.warehouse.error.log;
}
```

## Ключевые изменения:

1. ✅ **root** изменен с `/var/www/test.warehouse` на `/var/www/test.warehouse/public`
2. ✅ Добавлен **index index.php** для Laravel
3. ✅ Добавлена обработка PHP через **fastcgi_pass**
4. ✅ Добавлен правильный **try_files** для Laravel routing

## После обновления конфигурации:

```bash
# Проверить конфигурацию
sudo nginx -t

# Перезагрузить nginx
sudo systemctl reload nginx

# Проверить права доступа
sudo chown -R www-data:www-data /var/www/test.warehouse
sudo chmod -R 755 /var/www/test.warehouse
sudo chmod -R 775 /var/www/test.warehouse/storage
sudo chmod -R 775 /var/www/test.warehouse/bootstrap/cache
```

## Проверка:

```bash
# Проверить доступность
curl -I https://test.warehouse.expwood.ru

# Должен вернуть 200 OK или 302 (редирект на логин)
```

