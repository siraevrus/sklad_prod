# Исправлен статус при создании товаров в пути

## Проблема
При создании товаров в пути через `/admin/product-in-transits/create` товары создавались со статусом `for_receipt` вместо `in_transit`.

## Исправление

### Что было изменено:
- **Файл**: `app/Filament/Resources/ProductInTransitResource/Pages/CreateProductInTransit.php`
- **Строки**: 53 и 178
- **Изменение**: `Product::STATUS_FOR_RECEIPT` → `Product::STATUS_IN_TRANSIT`

### До исправления:
```php
'status' => Product::STATUS_FOR_RECEIPT,  // ❌ Неправильно
```

### После исправления:
```php
'status' => Product::STATUS_IN_TRANSIT,   // ✅ Правильно
```

## Логика работы

### Статусы товаров:
- **`in_transit`** - Товар в пути (для раздела "Товары в пути")
- **`for_receipt`** - Товар для приемки (для раздела "Приемка товаров")
- **`in_stock`** - Товар на складе (для раздела "Поступление товара")

### Жизненный цикл:
```
Создание товара в пути → in_transit → for_receipt → in_stock
```

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

1. **Создайте товар в пути** через `/admin/product-in-transits/create`
2. **Проверьте статус** - должен быть `in_transit`
3. **Товар должен появиться** в разделе "Товары в пути" (`/admin/product-in-transits`)

## Тестирование

```php
// Проверить создание товара в пути
$product = \App\Models\Product::create([
    'name' => 'Тестовый товар',
    'status' => \App\Models\Product::STATUS_IN_TRANSIT,
    'warehouse_id' => 2,
    'is_active' => true,
]);

echo "Статус товара: " . $product->status; // Должно быть: in_transit
```

## Файлы изменены:
- `app/Filament/Resources/ProductInTransitResource/Pages/CreateProductInTransit.php`

## Статус:
✅ **Исправлено** - товары в пути теперь создаются со статусом `in_transit`
