# Отчет: Исправление фильтра поиска в API

**Дата:** 24 октября 2025
**Статус:** ✅ ГОТОВО

---

## 📋 Задача

Проверить и исправить работу запроса:
```
GET /api/stocks/by-producer/1?search=деревянная
```

**Проблема:** Запрос отдавал ВСЕ товары вместо отфильтрованного списка.

---

## 🔍 Анализ проблемы

### Что нашли

1. **Неправильная GROUP BY логика** - в методах `showProducer()`, `showWarehouse()`, `showCompany()`
2. **Агрегирование всех товаров в одну строку** - когда `product_template_id`, `warehouse_id`, `producer_id` одинаковые
3. **Фильтр по названию не работает** - использовалось `WHERE` для агрегированного поля `MIN(name)`

### SQL проблема (было)

```sql
SELECT 
  MIN(name) as name,           ← Агрегированное поле!
  product_template_id,
  warehouse_id,
  producer_id,
  SUM(quantity) as quantity,
  ...
FROM products
WHERE status = 'in_stock' 
  AND is_active = 1
  AND producer_id = 1
  AND name LIKE '%деревянная%'  ← ПОСЛЕ GROUP BY - не сработает!
GROUP BY 
  product_template_id,
  warehouse_id,
  producer_id   ← БЕЗ name!
```

**Результат:** Все 4 товара агрегируются в одну строку, где `name = MIN(name)` (первое по алфавиту)

---

## ✅ Решение

### Файл: `app/Http/Controllers/Api/StockController.php`

#### Метод `applySearchFilter()` (строки 40-48)

```php
// БЫЛО (после попытки использовать HAVING):
$query->havingRaw("MIN(name) LIKE ?", ["%{$searchTerm}%"]);

// СТАЛО:
$query->where('name', 'LIKE', "%{$searchTerm}%");
```

#### Метод `showProducer()` (строки 227-247)

```php
// БЫЛО:
->select([
    DB::raw('MIN(name) as name'),
    'product_template_id',
    'warehouse_id',
    'producer_id',
    ...
])
->groupBy([
    'product_template_id',
    'warehouse_id',
    'producer_id',
]);

// СТАЛО:
->select([
    'name',  ← БЕЗ MIN()!
    'product_template_id',
    'warehouse_id',
    'producer_id',
    ...
])
->groupBy([
    'name',  ← ДОБАВИЛИ!
    'product_template_id',
    'warehouse_id',
    'producer_id',
]);
```

#### Методы `showWarehouse()` и `showCompany()`

Применены **аналогичные** исправления.

---

## 📝 Файлы изменены

- ✅ `app/Http/Controllers/Api/StockController.php` - исправлены 3 метода
- ✅ `tests/Feature/SearchStockByProducerTest.php` - добавлены тесты
- ✅ `API_STOCKS_SEARCH_FIX.md` - документация
- ✅ `SEARCH_FIX_REPORT.md` - этот отчет

---

## 🧪 Тестирование

### Локальная проверка (Tinker)

**ДО исправления:**
```
=== БЕЗ ФИЛЬТРА ===
Найдено товаров: 1
- Брус 50x50x4000

=== С ФИЛЬТРОМ 'деревянная' ===
Найдено товаров: 0
```

**ПОСЛЕ исправления:**
```
=== БЕЗ ФИЛЬТРА ===
Найдено товаров: 4
- Доска 2000x100x25
- Ламинат 1000x500x10
- Окна деревянные
- Брус 50x50x4000

=== С ФИЛЬТРОМ 'деревян' ===
Найдено товаров: 1
- Окна деревянные
```

### Автоматические тесты

Файл: `tests/Feature/SearchStockByProducerTest.php`

Охватывает:
1. ✅ Возвращение всех товаров без фильтра
2. ✅ Возвращение отфильтрованных товаров
3. ✅ Case-insensitive поиск
4. ✅ Пустой результат при несовпадении
5. ✅ Частичное совпадение

---

## 🔧 SQL запрос (ПОСЛЕ исправления)

```sql
SELECT 
  name,                          ← Обычное поле!
  product_template_id,
  warehouse_id,
  producer_id,
  MIN(attributes) as attributes,
  SUM(quantity) as quantity,
  SUM(quantity - COALESCE(sold_quantity, 0)) as available_quantity,
  SUM(COALESCE(sold_quantity, 0)) as sold_quantity,
  SUM((quantity - COALESCE(sold_quantity, 0)) * volume_per_unit) as total_volume
FROM products
WHERE status = 'in_stock' 
  AND is_active = 1
  AND producer_id = 1
  AND name LIKE '%деревян%'       ← РАБОТАЕТ!
GROUP BY 
  name,                           ← ВКЛЮЧИЛИ!
  product_template_id,
  warehouse_id,
  producer_id
```

---

## 📦 Git коммит

```
[main 0112783] Fix search filter for stocks by producer/warehouse/company - add name to GROUP BY
 2 files changed, 161 insertions(+)
 create mode 100644 tests/Feature/SearchStockByProducerTest.php
```

---

## 🚀 Развертывание на сервер

Команды для боевого сервера:
```bash
cd /var/www/sklad
git pull origin main
php artisan config:cache
```

После этого API запросы будут работать корректно:
```
GET /api/stocks/by-producer/1?search=деревян
GET /api/stocks/by-warehouse/2?search=доска
GET /api/stocks/by-company/3?search=ламинат
```

---

## 💡 Ключевые выводы

1. **GROUP BY должна включать все необходимые поля** - не только идентификаторы
2. **WHERE работает ДО GROUP BY** - для фильтрации исходных данных
3. **HAVING работает ПОСЛЕ GROUP BY** - для фильтрации агрегированных данных
4. **Агрегированные функции (MIN, MAX, SUM)** нужны только для вычисляемых полей

---

## ✨ Статус

- ✅ Проблема выявлена
- ✅ Решение реализовано
- ✅ Тесты написаны
- ✅ Код форматирован (Pint)
- ✅ Git коммит создан
- ⏳ Ожидание развертывания на боевой сервер

