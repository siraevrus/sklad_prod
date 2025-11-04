# 🔧 Ручное исправление SSL конфигурации nginx

## Текущая проблема

В SSL блоке (443 порт) конфигурации указано:
- `root /var/www/test.warehouse;` (неправильно - должно быть `/public`)
- `index index.html index.htm;` (неправильно - должно быть `index.php`)
- Нет обработки PHP файлов
- Неправильный `try_files` для Laravel

## Быстрое исправление через SSH

```bash
ssh my
sudo nano /etc/nginx/sites-available/test.warehouse.conf
```

### Найдите блок с `listen 443 ssl http2` и замените на:

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

## Или быстрые команды sed (если нужно быстро)

```bash
ssh my

# Создать резервную копию
sudo cp /etc/nginx/sites-available/test.warehouse.conf /etc/nginx/sites-available/test.warehouse.conf.backup

# Исправить root
sudo sed -i 's|root /var/www/test\.warehouse;|root /var/www/test.warehouse/public;|g' /etc/nginx/sites-available/test.warehouse.conf

# Исправить index
sudo sed -i 's|index index.html index.htm;|index index.php;|g' /etc/nginx/sites-available/test.warehouse.conf

# Исправить try_files (только в SSL блоке)
sudo sed -i '/listen 443 ssl http2/,/}/ s|try_files \$uri \$uri/ =404;|try_files \$uri \$uri/ /index.php?\$query_string;|g' /etc/nginx/sites-available/test.warehouse.conf

# Проверить конфигурацию
sudo nginx -t

# Перезагрузить
sudo systemctl reload nginx
```

## После исправления

```bash
# Исправить права доступа
sudo chown -R www-data:www-data /var/www/test.warehouse
sudo chmod -R 755 /var/www/test.warehouse
sudo chmod -R 775 /var/www/test.warehouse/storage
sudo chmod -R 775 /var/www/test.warehouse/bootstrap/cache

# Проверить доступность
curl -I https://test.warehouse.expwood.ru
```

## Проверка версии PHP-FPM

Если версия PHP не 8.4, проверьте:

```bash
ls -la /var/run/php/
```

И измените в конфигурации `php8.4-fpm.sock` на нужную версию (например, `php8.2-fpm.sock`).

