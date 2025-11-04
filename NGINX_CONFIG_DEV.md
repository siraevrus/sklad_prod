# 🔧 Конфигурация Nginx для DEV сервера

## Пример конфигурации для test.warehouse.expwood.ru

### Создание конфигурационного файла

```bash
ssh my
sudo nano /etc/nginx/sites-available/test_warehouse.conf
```

### Содержимое конфигурации:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name test.warehouse.expwood.ru;
    root /var/www/test_warehouse/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

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
}
```

### Активация конфигурации

```bash
# Создать символическую ссылку
sudo ln -s /etc/nginx/sites-available/test_warehouse.conf /etc/nginx/sites-enabled/

# Проверить конфигурацию
sudo nginx -t

# Перезагрузить nginx
sudo systemctl reload nginx
```

## Исправление прав доступа

```bash
# Установить правильные права
cd /var/www/test_warehouse
sudo chown -R www-data:www-data .
sudo chmod -R 755 .
sudo chmod -R 775 storage bootstrap/cache

# Проверить права
ls -la public/
```

## Проверка работы PHP-FPM

```bash
# Проверить статус PHP-FPM
sudo systemctl status php8.4-fpm

# Перезапустить если нужно
sudo systemctl restart php8.4-fpm
```

## Диагностика проблем

### 1. Проверка логов nginx

```bash
sudo tail -f /var/log/nginx/error.log
```

### 2. Проверка логов PHP-FPM

```bash
sudo tail -f /var/log/php8.4-fpm.log
```

### 3. Проверка прав доступа

```bash
# Проверить владельца
ls -la /var/www/test_warehouse/public/

# Должно быть:
# drwxr-xr-x www-data www-data
```

### 4. Проверка конфигурации

```bash
# Проверить синтаксис
sudo nginx -t

# Проверить активные конфигурации
ls -la /etc/nginx/sites-enabled/
```

## Частые проблемы и решения

### Ошибка 403 Forbidden

**Причина:** Неправильные права доступа или неправильный root в nginx

**Решение:**
```bash
# Исправить права
sudo chown -R www-data:www-data /var/www/test_warehouse
sudo chmod -R 755 /var/www/test_warehouse
sudo chmod -R 775 /var/www/test_warehouse/storage
sudo chmod -R 775 /var/www/test_warehouse/bootstrap/cache

# Проверить root в nginx конфигурации
# Должно быть: root /var/www/test_warehouse/public;
```

### Ошибка 502 Bad Gateway

**Причина:** PHP-FPM не запущен или неправильный путь к сокету

**Решение:**
```bash
# Проверить статус PHP-FPM
sudo systemctl status php8.4-fpm

# Перезапустить
sudo systemctl restart php8.4-fpm

# Проверить путь к сокету в nginx конфигурации
# Должно совпадать с php-fpm конфигурацией
```

### Ошибка "File not found"

**Причина:** Неправильный root в nginx или отсутствует index.php

**Решение:**
```bash
# Проверить наличие index.php
ls -la /var/www/test_warehouse/public/index.php

# Проверить root в nginx конфигурации
# Должно быть: root /var/www/test_warehouse/public;
```

## Проверка после настройки

```bash
# Проверить доступность сайта
curl -I http://test.warehouse.expwood.ru

# Должен вернуть 200 OK или 302 (редирект на логин)
```

