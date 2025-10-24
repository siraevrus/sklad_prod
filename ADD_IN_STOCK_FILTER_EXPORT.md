# Добавлен фильтр по статусу in_stock в экспорт товаров

## Изменения

Добавлен фильтр по статусу `in_stock` в экспорт товаров для раздела "Поступление товара" (`/admin/products`).

### Что изменено:

**Файл**: `app/Http/Controllers/ProductExportController.php`
**Строки 19-21**: Добавлен фильтр по статусу

```php
// До изменения
$query->where('is_active', true);

// После изменения
$query->where('is_active', true)
      ->where('status', Product::STATUS_IN_STOCK);
```

## Логика работы

### Фильтрация экспорта:
- ✅ **Активные товары**: `is_active = true`
- ✅ **Статус in_stock**: `status = 'in_stock'`
- ❌ **Исключены**: товары со статусом `in_transit`, `for_receipt`, `correction`

### Статусы товаров:
- **`in_stock`** - Товар на складе (включается в экспорт) ✅
- **`in_transit`** - Товар в пути (исключается из экспорта) ❌
- **`for_receipt`** - Товар для приемки (исключается из экспорта) ❌
- **`correction`** - Товар на корректировке (исключается из экспорта) ❌

## Результат

### До изменения:
- Экспортировались все активные товары (включая `in_transit`)
- В экспорте было 5 товаров (4 со статусом `in_stock` + 1 со статусом `in_transit`)

### После изменения:
- Экспортируются только товары со статусом `in_stock`
- В экспорте будет 4 товара (только со статусом `in_stock`)
- Исключен товар с ID 5 со статусом `in_transit`

## Развёртывание

```bash
ssh my
cd /var/www/sklad
git pull origin main
php artisan route:clear
php artisan config:clear
```

## Проверка

После развёртывания:

1. **Перейти в раздел "Поступление товара"** (`/admin/products`)
2. **Нажать "Экспорт в Excel"**
3. **Проверить файл** - должен содержать только товары со статусом `in_stock`

## Тестирование

```php
// Проверить товары для экспорта
$exportProducts = \App\Models\Product::where('is_active', true)
    ->where('status', \App\Models\Product::STATUS_IN_STOCK)
    ->get();

echo "Товаров для экспорта: " . $exportProducts->count();
// Ожидаемый результат: 4 товара
```

## Файлы изменены:
- `app/Http/Controllers/ProductExportController.php` - добавлен фильтр по статусу

## Статус:
✅ **Добавлено** - экспорт товаров теперь включает только товары со статусом `in_stock`
