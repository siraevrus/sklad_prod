#!/bin/bash

# 🧪 БЫСТРОЕ РАЗВЁРТЫВАНИЕ НА DEV СЕРВЕР
# Тестовый сервер: test.warehouse.expwood.ru

echo "🧪 === ДЕПЛОЙ НА DEV СЕРВЕР ==="
echo ""

# Цвета для вывода
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Проверяем, что мы на ветке dev
CURRENT_BRANCH=$(git branch --show-current)
if [ "$CURRENT_BRANCH" != "dev" ]; then
    echo -e "${RED}❌ ОШИБКА: Вы не на ветке dev!${NC}"
    echo -e "${YELLOW}Текущая ветка: $CURRENT_BRANCH${NC}"
    echo -e "${YELLOW}Переключитесь на dev: git checkout dev${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Ветка: $CURRENT_BRANCH${NC}"
echo ""

# Проверяем, есть ли изменения
if [ -n "$(git status --porcelain)" ]; then
    echo -e "${YELLOW}⚠️  ВНИМАНИЕ: Есть незакоммиченные изменения!${NC}"
    echo "Хотите продолжить? (y/n)"
    read -r response
    if [ "$response" != "y" ]; then
        echo "Отменено."
        exit 1
    fi
fi

echo "1️⃣  Отправляем изменения в GitHub (dev ветка)..."
git push origin dev

if [ $? -ne 0 ]; then
    echo -e "${RED}❌ Ошибка при отправке в GitHub!${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Изменения отправлены в GitHub${NC}"
echo ""

echo "2️⃣  Подключаемся к dev серверу и обновляем код..."
ssh my "cd /var/www/test_warehouse && git fetch origin && git checkout dev && git pull origin dev"

if [ $? -ne 0 ]; then
    echo -e "${RED}❌ Ошибка при обновлении на сервере!${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Код обновлен на сервере${NC}"
echo ""

echo "3️⃣  Запускаем миграции на dev сервере..."
ssh my "cd /var/www/test_warehouse && php artisan migrate --force"

if [ $? -ne 0 ]; then
    echo -e "${YELLOW}⚠️  Предупреждение: Ошибка при миграции (возможно, миграции уже применены)${NC}"
fi

echo ""

echo "4️⃣  Очищаем кеш на dev сервере..."
ssh my "cd /var/www/test_warehouse && php artisan cache:clear && php artisan config:clear && php artisan view:clear && php artisan route:clear"

echo ""

echo -e "${GREEN}✅ ДЕПЛОЙ НА DEV ЗАВЕРШЕН!${NC}"
echo ""
echo "🌐 Dev сервер: http://test.warehouse.expwood.ru"
echo "📊 Проверьте работоспособность на тестовом сервере перед деплоем на прод"

