#!/bin/bash

# 🔧 Автоматическая настройка nginx для dev сервера

echo "🔧 === НАСТРОЙКА NGINX ДЛЯ DEV СЕРВЕРА ==="
echo ""

# Цвета
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

CONFIG_FILE="/etc/nginx/sites-available/test.warehouse.conf"
NGINX_CONFIG='server {
    listen 80;
    listen [::]:80;
    server_name test.warehouse.expwood.ru;
    root /var/www/test.warehouse/public;

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
}'

echo "1️⃣  Создаем конфигурационный файл nginx..."
ssh my "sudo tee $CONFIG_FILE > /dev/null << 'EOF'
$NGINX_CONFIG
EOF"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Конфигурационный файл создан${NC}"
else
    echo -e "${RED}❌ Ошибка при создании конфигурации${NC}"
    exit 1
fi

echo ""
echo "2️⃣  Создаем символическую ссылку..."
ssh my "sudo ln -sf $CONFIG_FILE /etc/nginx/sites-enabled/test.warehouse.conf"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Символическая ссылка создана${NC}"
else
    echo -e "${YELLOW}⚠️  Символическая ссылка уже существует или ошибка${NC}"
fi

echo ""
echo "3️⃣  Исправляем права доступа к проекту..."
ssh my "cd /var/www/test.warehouse && sudo chown -R www-data:www-data . && sudo chmod -R 755 . && sudo chmod -R 775 storage bootstrap/cache"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Права доступа исправлены${NC}"
else
    echo -e "${RED}❌ Ошибка при исправлении прав${NC}"
fi

echo ""
echo "4️⃣  Проверяем конфигурацию nginx..."
ssh my "sudo nginx -t"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Конфигурация nginx корректна${NC}"
else
    echo -e "${RED}❌ Ошибка в конфигурации nginx!${NC}"
    echo "Проверьте конфигурацию вручную"
    exit 1
fi

echo ""
echo "5️⃣  Перезагружаем nginx..."
ssh my "sudo systemctl reload nginx"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Nginx перезагружен${NC}"
else
    echo -e "${RED}❌ Ошибка при перезагрузке nginx${NC}"
    exit 1
fi

echo ""
echo "6️⃣  Проверяем статус nginx..."
ssh my "sudo systemctl status nginx --no-pager | head -5"

echo ""
echo -e "${GREEN}✅ НАСТРОЙКА ЗАВЕРШЕНА!${NC}"
echo ""
echo "🌐 Проверьте сайт: http://test.warehouse.expwood.ru"
echo ""
echo "Если проблема осталась, проверьте:"
echo "  1. DNS запись для test.warehouse.expwood.ru указывает на правильный IP"
echo "  2. Логи nginx: sudo tail -f /var/log/nginx/error.log"
echo "  3. Права доступа: ls -la /var/www/test.warehouse/public"

