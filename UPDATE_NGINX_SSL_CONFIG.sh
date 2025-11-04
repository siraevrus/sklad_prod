#!/bin/bash

# 🔧 Обновление SSL конфигурации nginx для dev сервера

echo "🔧 === ОБНОВЛЕНИЕ SSL КОНФИГУРАЦИИ NGINX ==="
echo ""

# Цвета
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

CONFIG_FILE="/etc/nginx/sites-available/test.warehouse.conf"

# Правильная SSL конфигурация для Laravel
SSL_CONFIG='server {
    listen 443 ssl http2;
    server_name test.warehouse.expwood.ru;

    # SSL сертификаты от Let'\''s Encrypt
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
}'

echo "1️⃣  Проверяем текущую конфигурацию..."
ssh my "cat $CONFIG_FILE"

echo ""
echo -e "${YELLOW}⚠️  ВНИМАНИЕ: Этот скрипт обновит SSL блок (443) в конфигурации nginx${NC}"
echo "Хотите продолжить? (y/n)"
read -r response
if [ "$response" != "y" ]; then
    echo "Отменено."
    exit 1
fi

echo ""
echo "2️⃣  Создаем резервную копию текущей конфигурации..."
ssh my "sudo cp $CONFIG_FILE ${CONFIG_FILE}.backup.$(date +%Y%m%d_%H%M%S)"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Резервная копия создана${NC}"
else
    echo -e "${YELLOW}⚠️  Не удалось создать резервную копию${NC}"
fi

echo ""
echo "3️⃣  Обновляем SSL блок конфигурации..."

# Создаем временный файл с правильной конфигурацией
ssh my "cat > /tmp/nginx_ssl_block_update.sh << 'EOFSCRIPT'
#!/bin/bash
CONFIG_FILE=\"$CONFIG_FILE\"
SSL_CONFIG='$SSL_CONFIG'

# Читаем текущий файл
CURRENT=\$(cat \"\$CONFIG_FILE\")

# Находим начало SSL блока (server { listen 443)
# И конец этого блока (последняя закрывающая скобка перед следующим server или концом)
# Простая замена: находим блок \"server { listen 443\" и заменяем до следующего \"server {\" или конца файла

# Создаем новую конфигурацию
# Сначала берем всё до SSL блока
BEFORE_SSL=\$(echo \"\$CURRENT\" | sed -n '/^server {$/,/listen 443 ssl http2;/p' | head -n -1)
AFTER_SSL=\$(echo \"\$CURRENT\" | sed -n '/^server {$/,/listen 443 ssl http2;/p' | tail -n +2)

# Проще: используем sed для замены всего блока server { listen 443
# Находим строки между \"server { listen 443 ssl http2;\" и закрывающей \"}\" этого блока

# Более надежный способ: используем Python для правильной замены
python3 << PYTHON
import re

with open(\"\$CONFIG_FILE\", \"r\") as f:
    content = f.read()

# Находим блок server { listen 443 ssl http2
pattern = r'server \{[^}]*listen 443 ssl http2[^}]*server_name test\.warehouse\.expwood\.ru;[^}]*\}'
replacement = '''$SSL_CONFIG'''

new_content = re.sub(pattern, replacement, content, flags=re.DOTALL)

with open(\"\$CONFIG_FILE\", \"w\") as f:
    f.write(new_content)
PYTHON
EOFSCRIPT
chmod +x /tmp/nginx_ssl_block_update.sh
sudo /tmp/nginx_ssl_block_update.sh"

# Более простой способ - использовать sed для замены конкретных строк
echo ""
echo "Используем более простой метод - заменяем ключевые строки..."

ssh my "sudo sed -i 's|root /var/www/test\.warehouse;|root /var/www/test.warehouse/public;|g' $CONFIG_FILE"
ssh my "sudo sed -i 's|index index.html index.htm;|index index.php;|g' $CONFIG_FILE"
ssh my "sudo sed -i 's|try_files \$uri \$uri/ =404;|try_files \$uri \$uri/ /index.php?\$query_string;|g' $CONFIG_FILE"

# Добавляем обработку PHP если её нет
if ! ssh my "grep -q 'location ~ \\\\\.php' $CONFIG_FILE"; then
    echo "4️⃣  Добавляем обработку PHP..."
    
    # Находим место после location / { ... } и добавляем PHP обработку
    ssh my "sudo sed -i '/location \/ {/,/}/ {
        /}/ a\
    location = /favicon.ico { access_log off; log_not_found off; }\
    location = /robots.txt  { access_log off; log_not_found off; }\
\
    error_page 404 /index.php;\
\
    location ~ \\\\.php\$ {\
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;\
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;\
        include fastcgi_params;\
        fastcgi_hide_header X-Powered-By;\
    }\
\
    location ~ /\\\\.(?!well-known).* {\
        deny all;\
    }
    }' $CONFIG_FILE"
fi

# Добавляем заголовки безопасности если их нет
if ! ssh my "grep -q 'X-Frame-Options' $CONFIG_FILE"; then
    echo "5️⃣  Добавляем заголовки безопасности..."
    ssh my "sudo sed -i '/charset utf-8;/a\
    add_header X-Frame-Options \"SAMEORIGIN\";\
    add_header X-Content-Type-Options \"nosniff\";' $CONFIG_FILE"
fi

echo ""
echo "6️⃣  Проверяем конфигурацию nginx..."
ssh my "sudo nginx -t"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Конфигурация nginx корректна${NC}"
else
    echo -e "${RED}❌ Ошибка в конфигурации nginx!${NC}"
    echo "Восстанавливаем из резервной копии..."
    ssh my "sudo cp ${CONFIG_FILE}.backup.* $CONFIG_FILE 2>/dev/null || echo 'Резервная копия не найдена'"
    exit 1
fi

echo ""
echo "7️⃣  Перезагружаем nginx..."
ssh my "sudo systemctl reload nginx"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Nginx перезагружен${NC}"
else
    echo -e "${RED}❌ Ошибка при перезагрузке nginx${NC}"
    exit 1
fi

echo ""
echo "8️⃣  Исправляем права доступа..."
ssh my "cd /var/www/test.warehouse && sudo chown -R www-data:www-data . && sudo chmod -R 755 . && sudo chmod -R 775 storage bootstrap/cache"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Права доступа исправлены${NC}"
fi

echo ""
echo -e "${GREEN}✅ КОНФИГУРАЦИЯ ОБНОВЛЕНА!${NC}"
echo ""
echo "🌐 Проверьте сайт: https://test.warehouse.expwood.ru"
echo ""
echo "Основные изменения:"
echo "  ✅ root изменен на /var/www/test.warehouse/public"
echo "  ✅ index изменен на index.php"
echo "  ✅ Добавлена обработка PHP"
echo "  ✅ Добавлен правильный try_files для Laravel"

