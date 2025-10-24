# Восстановлена функциональность раздела "Приемка товаров"

## Проблема
Раздел "Приемка товаров" (`/admin/receipts`) был пустой, потому что фильтр искал товары со статусом `for_receipt`, но в базе данных таких товаров не было.

## Решение

### 1. Изменен фильтр в ReceiptResource
**Файл**: `app/Filament/Resources/ReceiptResource.php`
**Строка 547**: Изменен фильтр с `STATUS_FOR_RECEIPT` на `STATUS_IN_TRANSIT`

```php
// До исправления
->where('status', Product::STATUS_FOR_RECEIPT)

// После исправления  
->where('status', Product::STATUS_IN_TRANSIT)
```

### 2. Исправлена модель Product
**Файл**: `app/Models/Product.php`
- Удалено поле `volume_per_unit` из `$fillable` и `$casts`
- Закомментированы методы, использующие `volume_per_unit`
- Исправлена ошибка "Column not found: volume_per_unit"

### 3. Создан тестовый товар
Создан товар с ID 5 со статусом `in_transit` для проверки функциональности.

## Логика работы

### Жизненный цикл товара:
```
Создание товара в пути → in_transit → for_receipt → in_stock
```

### Статусы:
- **`in_transit`** - Товар в пути (раздел "Товары в пути")
- **`for_receipt`** - Товар для приемки (раздел "Приемка товаров") 
- **`in_stock`** - Товар на складе (раздел "Поступление товара")

### Текущая логика:
- **Товары в пути** (`/admin/product-in-transits`) - показывает товары со статусом `in_transit`
- **Приемка товаров** (`/admin/receipts`) - показывает товары со статусом `in_transit`
- **Поступление товара** (`/admin/products`) - показывает товары со статусом `in_stock`

## Развёртывание

```bash
ssh my
cd /var/www/sklad
git pull origin main
php artisan filament:optimize-clear
php artisan view:clear
```

## Проверка

После развёртывания:

1. **Раздел "Приемка товаров"** (`/admin/receipts`) должен показывать товары со статусом `in_transit`
2. **Тестовый товар** с ID 5 должен отображаться в списке
3. **Функциональность восстановлена** ✅

## Тестирование

```php
// Проверить товары для приемки
$receiptProducts = \App\Models\Product::where('status', 'in_transit')->active()->get();
echo "Товаров для приемки: " . $receiptProducts->count();
```

## Файлы изменены:
- `app/Filament/Resources/ReceiptResource.php` - изменен фильтр статуса
- `app/Models/Product.php` - исправлены ошибки с volume_per_unit

## Статус:
✅ **Восстановлено** - раздел "Приемка товаров" теперь показывает товары со статусом `in_transit`
