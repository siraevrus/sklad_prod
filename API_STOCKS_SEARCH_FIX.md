# Исправление фильтра поиска в API эндпоинтах GET /api/stocks/by-*

## Проблема

Запрос `GET /api/stocks/by-producer/1?search=деревянная` отдавал **все товары** вместо фильтрованного списка.

### Корневая причина

В методе `showProducer()` и аналогичных методах в `StockController` была неправильная группировка товаров:

```php
// НЕПРАВИЛЬНО (было раньше):
$query->select([
    DB::raw('MIN(name) as name'),  // Агрегированное поле
    'product_template_id',
    'warehouse_id',
    'producer_id',
    // ...
])
->groupBy([
    'product_template_id',
    'warehouse_id',
    'producer_id',
    // БЕЗ 'name' в GROUP BY!
]);

// Фильтр пытался искать по WHERE, но это не работает 
// после GROUP BY для агрегированного поля
$query->where('name', 'LIKE', "%{$searchTerm}%");  // Не сработает!
```

**Результат:** Все товары с одинаковыми `product_template_id`, `warehouse_id`, `producer_id` агрегировались в **ОДНУ** строку с названием `MIN(name)`, поэтому фильтр не работал, и возвращались все товары.

## Решение

### Изменение 1: Добавить 'name' в SELECT БЕЗ агрегации

```php
// ПРАВИЛЬНО (теперь):
$query->select([
    'name',  // Обычное поле, НЕ MIN(name)!
    'product_template_id',
    'warehouse_id',
    'producer_id',
    DB::raw('MIN(attributes) as attributes'),
    // ...
])
```

### Изменение 2: Добавить 'name' в GROUP BY

```php
->groupBy(array_merge([
    'name',  // ДОБАВЛЕНО!
    'product_template_id',
    'warehouse_id',
    'producer_id',
], $groupByAttributes));
```

### Изменение 3: Обновить фильтр - использовать WHERE (не HAVING)

```php
// БЫЛО:
$query->havingRaw("MIN(name) LIKE ?", ["%{$searchTerm}%"]);

// СТАЛО:
$query->where('name', 'LIKE', "%{$searchTerm}%");
```

## Затронутые методы

Исправлены в файле `app/Http/Controllers/Api/StockController.php`:

1. **`showProducer()`** - GET `/api/stocks/by-producer/{producer_id}`
2. **`showWarehouse()`** - GET `/api/stocks/by-warehouse/{warehouse_id}`
3. **`showCompany()`** - GET `/api/stocks/by-company/{company_id}`

## Результат

Теперь запрос работает правильно:

```bash
# БЕЗ фильтра - вернет ВСЕ товары производителя
GET /api/stocks/by-producer/1

# С фильтром - вернет ТОЛЬКО товары, чье имя содержит "деревян"
GET /api/stocks/by-producer/1?search=деревян

# Поиск не чувствителен к регистру (благодаря MySQL LIKE)
GET /api/stocks/by-producer/1?search=ДЕРЕВЯН
GET /api/stocks/by-producer/1?search=Деревян
```

## Примеры результатов

### До исправления
```
GET /api/stocks/by-producer/1?search=деревянная
Response: {
  "success": true,
  "data": [
    { "name": "Доска 2000x100x25", "quantity": 100, ... },
    { "name": "Ламинат 1000x500x10", "quantity": 50, ... },
    { "name": "Окна деревянные", "quantity": 75, ... },
    { "name": "Брус 50x50x4000", "quantity": 200, ... }
  ],  // ВСЕ товары! Фильтр не сработал
  "meta": { "total": 4, ... }
}
```

### После исправления
```
GET /api/stocks/by-producer/1?search=деревян
Response: {
  "success": true,
  "data": [
    { "name": "Окна деревянные", "quantity": 75, ... }
  ],  // ТОЛЬКО релевантные товары
  "meta": { "total": 1, ... }
}
```

## Замечание о поиске

- Поиск **не чувствителен к регистру** (LIKE в MySQL по умолчанию case-insensitive)
- Поиск ищет **частичные совпадения** - "деревян" найдет "деревянные"
- Поиск работает с **начальной частью слова** - "дерев" тоже найдет "деревянные"
- Полное совпадение слова не требуется

## Тесты

Добавлен файл тестов: `tests/Feature/SearchStockByProducerTest.php`

Тесты проверяют:
1. ✅ Возвращение всех товаров без фильтра
2. ✅ Возвращение отфильтрованных товаров с фильтром
3. ✅ Поиск, не чувствительный к регистру
4. ✅ Пустой результат при несовпадении
5. ✅ Частичное совпадение названия

Запуск тестов:
```bash
php artisan test tests/Feature/SearchStockByProducerTest.php
```
