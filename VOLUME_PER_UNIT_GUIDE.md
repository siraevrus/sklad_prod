# 📊 Новая колонка volume_per_unit - Объём за единицу товара

## 🎯 Обзор

Добавлена новая колонка **`volume_per_unit`** в таблицу **`products`** для автоматического хранения объёма одной единицы товара.

---

## 📋 Формула

```
volume_per_unit = calculated_volume / quantity

Где:
  calculated_volume = ВЕСЬ объём партии товара (м³)
  quantity = количество единиц в партии
  volume_per_unit = объем одной единицы (м³)
```

## 📊 Пример

```
calculated_volume: 5.0 м³  (весь объём партии)
quantity: 100              (количество единиц)

volume_per_unit = 5.0 / 100 = 0.05 м³ (объём за 1 единицу)
```

---

## 📁 Структура в таблице

| Колонка | Тип | Значение | Описание |
|---------|-----|----------|----------|
| **calculated_volume** | DECIMAL(8,4) | 5.0000 | ВЕСЬ объём партии товара |
| **quantity** | INT | 100 | Количество единиц в партии |
| **volume_per_unit** | DECIMAL(8,4) | 0.0500 | Объём за 1 единицу (рассчитывается автоматически) |

---

## 🔧 Как работает автоматический расчёт

### 1. **При создании товара (CreateProduct)**

Когда вы создаёте товар в Filament:

```
1. Система рассчитывает calculated_volume
2. При сохранении товара boot() метод автоматически:
   - Берёт calculated_volume и quantity
   - Вычисляет: volume_per_unit = calculated_volume / quantity
   - Сохраняет результат в таблицу
```

### 2. **При редактировании товара (EditProduct)**

Когда вы редактируете товар:

```
1. Изменяется quantity или calculated_volume
2. При сохранении boot() метод автоматически:
   - Пересчитывает volume_per_unit
   - Обновляет в таблице
```

### 3. **При работе с товарами в пути (ProductInTransit)**

Когда товар создаётся/обновляется в пути:

```
1. System создаёт записьProductInTransit
2. Boot метод автоматически рассчитывает volume_per_unit
3. Сохраняется в таблицу product_in_transit
```

---

## 💾 Где это используется

### В таблице products:

```sql
SELECT 
  id,
  name,
  quantity,
  calculated_volume,        -- ВЕСЬ объём партии
  volume_per_unit,          -- Объём за 1 единицу (НОВОЕ!)
FROM products;

-- Пример вывода:
-- id | name | quantity | calculated_volume | volume_per_unit
-- 1  | Доска| 100      | 5.0000            | 0.0500
```

### В таблице product_in_transit:

```sql
SELECT 
  id,
  name,
  quantity,
  calculated_volume,        -- ВЕСЬ объём партии
  volume_per_unit,          -- Объём за 1 единицу (НОВОЕ!)
FROM product_in_transit;
```

---

## 🔍 Логирование

### В CreateProduct логировании:

```
STEP 10: Final data before create
{
  "calculated_volume": 5.0,
  "volume_per_unit": 0.05,    ← НОВОЕ!
  "quantity": 100,
  ...
}
```

### В EditProduct логировании:

```
STEP 8: Final data before save
{
  "calculated_volume": 5.0,
  "volume_per_unit": 0.05,    ← НОВОЕ!
  "quantity": 100,
  ...
}
```

---

## 🛠️ Методы в Product модели

### Новые методы:

```php
// Рассчитать объём за одну единицу
$volumePerUnit = $product->calculateVolumePerUnit();
// Результат: 0.05 м³

// Обновить оба значения: calculated_volume и volume_per_unit
$product->updateCalculatedVolume();
// Рассчитывает оба поля и сохраняет
```

### Boot метод (автоматический расчёт):

```php
protected static function boot(): void
{
    parent::boot();

    static::saving(function (Product $product) {
        if ($product->calculated_volume !== null && $product->quantity > 0) {
            $product->volume_per_unit = round($product->calculated_volume / $product->quantity, 4);
        } else {
            $product->volume_per_unit = null;
        }
    });
}
```

**Это означает:** каждый раз при сохранении товара volume_per_unit пересчитывается автоматически!

---

## 📊 Примеры рассчётов

### Пример 1: Доски

```
Товар: Доска
quantity = 100 досок
calculated_volume = 5.0 м³ (весь объём партии)

volume_per_unit = 5.0 / 100 = 0.05 м³

Это означает: каждая доска занимает 0.05 м³ объёма
```

### Пример 2: Брусья

```
Товар: Брус
quantity = 50 шт
calculated_volume = 10.0 м³ (весь объём партии)

volume_per_unit = 10.0 / 50 = 0.2 м³

Это означает: каждый брус занимает 0.2 м³ объёма
```

### Пример 3: NULL значение

```
Если calculated_volume = NULL или quantity = 0:
volume_per_unit = NULL

Потому что невозможно рассчитать объём за единицу
```

---

## ⚠️ Важные моменты

1. **Автоматический расчёт**
   - Не нужно вручную заполнять volume_per_unit
   - Рассчитывается автоматически при каждом сохранении

2. **Точность**
   - Рассчитывается с точностью до 4 знаков после запятой (DECIMAL 8,4)
   - Используется функция `round()` для корректного округления

3. **NULL значения**
   - Если calculated_volume = NULL, то volume_per_unit = NULL
   - Если quantity ≤ 0, то volume_per_unit = NULL

4. **Обновление существующих данных**
   - При миграции новая колонка добавляется как NULL
   - При редактировании старых товаров volume_per_unit пересчитывается

---

## 🚀 Миграция

### Файл миграции:

```
database/migrations/2025_10_21_115021_add_volume_per_unit_to_products.php
```

### Что добавляет:

```php
$table->decimal('volume_per_unit', 8, 4)->nullable()
    ->after('calculated_volume')
    ->comment('Объём одной единицы товара (calculated_volume / quantity)');
```

### Для применения на боевом сервере:

```bash
ssh root@31.184.253.122
cd /var/www/warehouse
php artisan migrate --force
```

---

## 📝 Изменённые файлы

1. **Миграция**
   - `database/migrations/2025_10_21_115021_add_volume_per_unit_to_products.php`

2. **Модели**
   - `app/Models/Product.php` - добавлены методы и boot()
   - `app/Models/ProductInTransit.php` - добавлена boot() для автоматического расчёта

3. **Filament Pages**
   - `app/Filament/Resources/ProductResource/Pages/CreateProduct.php` - добавлено логирование
   - `app/Filament/Resources/ProductResource/Pages/EditProduct.php` - добавлено логирование

---

## 🔗 Связанная информация

- [[memory:10167262]] calculated_volume хранит ВЕСЬ объём партии товара
- CALCULATED_VOLUME_CORRECTION.md - объяснение различия между calculated_volume и volume_per_unit

---

**Дата добавления:** October 21, 2025  
**Commit:** 95b9dac feat: Add volume_per_unit column and automatic calculation  
**Статус:** ✅ Готово к развёртыванию

