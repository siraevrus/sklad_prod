# 🚀 Развёртывание volume_per_unit на боевом сервере

## ⚠️ СТАТУС

Код и миграция готовы в git, но **миграция ещё НЕ ПРИМЕНЕНА** на боевом сервере!

---

## 🔍 Что нужно сделать

### На боевом сервере (31.184.253.122):

```bash
# 1. Подключитесь к серверу
ssh root@31.184.253.122

# 2. Перейдите в директорию проекта
cd /var/www/warehouse

# 3. Обновите код из git
git pull origin main

# 4. Запустите миграцию
php artisan migrate --force
```

---

## ✅ Проверка после миграции

### 1. Проверить структуру таблицы products

```bash
mysql -u root -p'123456' sklad << 'SQL'
DESCRIBE products;
SQL
```

Должна появиться колонка:
```
volume_per_unit | decimal(8,4) | YES | | NULL |
```

### 2. Проверить структуру таблицы product_in_transit

```bash
mysql -u root -p'123456' sklad << 'SQL'
DESCRIBE product_in_transit;
SQL
```

Должна появиться колонка:
```
volume_per_unit | decimal(8,4) | YES | | NULL |
```

### 3. Посмотреть примеры из products

```bash
mysql -u root -p'123456' sklad << 'SQL'
SELECT 
  id, 
  name, 
  quantity, 
  calculated_volume, 
  volume_per_unit 
FROM products 
LIMIT 5;
SQL
```

**Ожидаемый результат:**
```
+----+--------+----------+-------------------+----------------+
| id | name   | quantity | calculated_volume | volume_per_unit |
+----+--------+----------+-------------------+----------------+
|  1 | Доска  |      100 |            5.0000 |          0.0500 |
|  2 | Брус   |       50 |           10.0000 |          0.2000 |
+----+--------+----------+-------------------+----------------+
```

### 4. Посмотреть примеры из product_in_transit

```bash
mysql -u root -p'123456' sklad << 'SQL'
SELECT 
  id, 
  name, 
  quantity, 
  calculated_volume, 
  volume_per_unit 
FROM product_in_transit 
LIMIT 5;
SQL
```

---

## 📝 Что изменилось

### Таблица products

```sql
ALTER TABLE products ADD COLUMN volume_per_unit DECIMAL(8,4) NULL AFTER calculated_volume;
```

### Таблица product_in_transit

```sql
ALTER TABLE product_in_transit ADD COLUMN volume_per_unit DECIMAL(8,4) NULL AFTER calculated_volume;
```

---

## 🔧 Как работает

### При создании/редактировании товара

Метод `boot()` в моделях автоматически рассчитывает:

```php
volume_per_unit = round(calculated_volume / quantity, 4)
```

### Примеры

```
1. Доска
   quantity: 100
   calculated_volume: 5.0 м³
   volume_per_unit = 5.0 / 100 = 0.05 м³ ✅

2. Брус
   quantity: 50
   calculated_volume: 10.0 м³
   volume_per_unit = 10.0 / 50 = 0.2 м³ ✅

3. NULL случай
   quantity: 0 или calculated_volume: NULL
   volume_per_unit = NULL ✅
```

---

## 🐛 Если что-то не работает

### Проверить логи на сервере

```bash
ssh root@31.184.253.122
tail -100 /var/www/warehouse/storage/logs/laravel.log | grep -i "volume\|error\|exception"
```

### Проверить статус миграций

```bash
ssh root@31.184.253.122
cd /var/www/warehouse
php artisan migrate:status
```

### Откатить миграцию (если нужно)

```bash
ssh root@31.184.253.122
cd /var/www/warehouse
php artisan migrate:rollback
```

---

## 📋 Файлы миграции

```
database/migrations/2025_10_21_115021_add_volume_per_unit_to_products.php
```

### Содержимое:

```php
public function up(): void
{
    Schema::table('products', function (Blueprint $table) {
        $table->decimal('volume_per_unit', 8, 4)->nullable()
            ->after('calculated_volume')
            ->comment('Объём одной единицы товара (calculated_volume / quantity)');
    });
}

public function down(): void
{
    Schema::table('products', function (Blueprint $table) {
        $table->dropColumn('volume_per_unit');
    });
}
```

---

## 💾 Git commits

```
95b9dac feat: Add volume_per_unit column and automatic calculation
fae5485 docs: Add volume_per_unit guide and explanation
```

---

## ✅ Чек-лист развёртывания

- [ ] SSH подключение к серверу
- [ ] `git pull origin main` выполнен
- [ ] `php artisan migrate --force` выполнен успешно
- [ ] Проверена колонка в products
- [ ] Проверена колонка в product_in_transit
- [ ] Проверены примеры данных
- [ ] Создан/отредактирован товар через Filament
- [ ] Проверено, что volume_per_unit заполнилась автоматически
- [ ] Проверены логи на наличие ошибок

---

**Дата создания:** October 21, 2025  
**Статус:** ⏳ Ожидает развёртывания на боевом сервере

