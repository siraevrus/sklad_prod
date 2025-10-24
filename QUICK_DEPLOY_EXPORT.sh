#!/bin/bash

# Быстрое развёртывание кнопок экспорта в Excel
# Использование: ssh my "bash -s" < QUICK_DEPLOY_EXPORT.sh

echo "=== Развёртывание кнопок экспорта в Excel ==="

cd /var/www/sklad || exit 1

echo "1. Подтягивание изменений из Git..."
git pull origin main

echo "2. Очистка кэшей..."
php artisan route:clear
php artisan filament:optimize-clear
php artisan config:clear
php artisan view:clear

echo "=== Развёртывание завершено! ==="
echo ""
echo "Проверьте работу кнопок на страницах:"
echo "  - https://warehouse.expwood.ru/admin/receipts"
echo "  - https://warehouse.expwood.ru/admin/sales"
echo "  - https://warehouse.expwood.ru/admin/products"

