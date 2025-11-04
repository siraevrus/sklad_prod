#!/bin/bash

# 🔧 Исправление ошибки 403 Forbidden на dev сервере

echo "🔧 === ИСПРАВЛЕНИЕ 403 FORBIDDEN ==="
echo ""

# Цвета
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo "1️⃣  Проверяем права доступа к директории проекта..."
ssh my "ls -la /var/www/test.warehouse/public"

echo ""
echo "2️⃣  Проверяем наличие index.php..."
ssh my "ls -la /var/www/test.warehouse/public/index.php"

echo ""
echo "3️⃣  Проверяем конфигурацию nginx..."
ssh my "cat /etc/nginx/sites-available/test.warehouse.conf 2>/dev/null || cat /etc/nginx/sites-enabled/test.warehouse.conf 2>/dev/null || echo 'Конфигурация не найдена'"

echo ""
echo "4️⃣  Исправляем права доступа..."
ssh my "cd /var/www/test.warehouse && chmod -R 755 . && chmod -R 775 storage bootstrap/cache && chown -R www-data:www-data ."

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Права доступа исправлены${NC}"
else
    echo -e "${RED}❌ Ошибка при исправлении прав${NC}"
fi

echo ""
echo "5️⃣  Проверяем права еще раз..."
ssh my "ls -la /var/www/test.warehouse/public | head -5"

echo ""
echo "6️⃣  Проверяем конфигурацию nginx и перезагружаем..."
ssh my "sudo nginx -t && sudo systemctl reload nginx"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Nginx перезагружен${NC}"
else
    echo -e "${RED}❌ Ошибка конфигурации nginx${NC}"
fi

echo ""
echo -e "${YELLOW}📋 Если проблема осталась, проверьте:${NC}"
echo "   1. Конфигурация nginx для test.warehouse.expwood.ru"
echo "   2. Root директория должна указывать на /var/www/test.warehouse/public"
echo "   3. Права доступа к файлам (755 для директорий, 644 для файлов)"
echo "   4. Владелец файлов должен быть www-data:www-data"

