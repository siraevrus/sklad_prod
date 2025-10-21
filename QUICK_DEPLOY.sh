#!/bin/bash

# 🚀 БЫСТРОЕ РАЗВЁРТЫВАНИЕ volume_per_unit
# Просто скопируй содержимое этого файла и вставь в терминал на сервере

cd /var/www/warehouse

echo "1️⃣  Проверяем новые коммиты..."
git log --oneline | head -5

echo ""
echo "2️⃣  Запускаем миграцию БД..."
php artisan migrate --force

echo ""
echo "3️⃣  Проверяем статус миграции..."
php artisan migrate:status | grep volume_per_unit

echo ""
echo "4️⃣  Проверяем колонку в БД..."
mysql -u root -p'123456' sklad -e "DESCRIBE products;" | grep volume_per_unit

echo ""
echo "5️⃣  Проверяем данные..."
mysql -u root -p'123456' sklad -e "SELECT id, name, quantity, calculated_volume, volume_per_unit FROM products WHERE volume_per_unit IS NOT NULL LIMIT 3;"

echo ""
echo "6️⃣  Очищаем кеш..."
php artisan cache:clear
php artisan config:clear

echo ""
echo "✅ ГОТОВО! volume_per_unit работает!"

