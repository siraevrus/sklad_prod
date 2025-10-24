# Исправление пустых файлов экспорта

## Проблема
Кнопки экспорта работали, но файлы были пустыми (только заголовки, без данных).

## Причина
1. **Фильтрация по company_id** - контроллеры искали записи по company_id, но у пользователей не было этого поля
2. **Фильтрация по статусу** - контроллер приемок искал только товары со статусом `for_receipt`, но в базе все товары имеют статус `in_stock`
3. **Отсутствие warehouse_id** - у пользователей не было привязанных складов

## Исправления

### 1. ProductExportController
- ✅ Убрана фильтрация по `company_id`
- ✅ Добавлена фильтрация только активных товаров (`is_active = true`)
- ✅ Если у пользователя нет `warehouse_id`, показываются все товары

### 2. ReceiptExportController  
- ✅ Убрана фильтрация по статусу `STATUS_FOR_RECEIPT`
- ✅ Теперь экспортируются все активные товары
- ✅ Если у пользователя нет `warehouse_id`, показываются все товары

### 3. SaleExportController
- ✅ Убрана фильтрация по `company_id`
- ✅ Если у пользователя нет `warehouse_id`, показываются все продажи

### 4. Добавлена отладка
- ✅ В каждый контроллер добавлено логирование количества найденных записей
- ✅ Логируется роль пользователя и warehouse_id

## Развёртывание

```bash
# Подключиться к серверу
ssh my
cd /var/www/sklad

# Подтянуть изменения
git pull origin main

# Очистить кэши
php artisan route:clear
php artisan filament:optimize-clear
php artisan config:clear
php artisan view:clear
```

## Проверка

После развёртывания проверить:

1. **Поступление товара** (`/admin/products`)
   - Кнопка "Экспорт в Excel" должна скачать файл с 4 товарами
   - Файл: `products_YYYY-MM-DD.csv`

2. **Приемка товаров** (`/admin/receipts`) 
   - Кнопка "Экспорт в Excel" должна скачать файл с 4 товарами
   - Файл: `receipts_YYYY-MM-DD.csv`

3. **Реализация** (`/admin/sales`)
   - Кнопка "Экспорт в Excel" должна скачать пустой файл (нет продаж)
   - Файл: `sales_YYYY-MM-DD.csv`

## Отладка

Если файлы всё ещё пустые, проверить логи:
```bash
tail -f storage/logs/laravel.log | grep "Export:"
```

В логах должно быть:
```
ProductExport: Found products {"count":4,"user_role":"admin","user_warehouse_id":"null"}
ReceiptExport: Found receipts {"count":4,"user_role":"admin","user_warehouse_id":"null"}
SaleExport: Found sales {"count":0,"user_role":"admin","user_warehouse_id":"null"}
```
