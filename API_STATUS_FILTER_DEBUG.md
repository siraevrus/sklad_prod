# Отладка фильтра status в API /api/products

## Проблема

При запросе:
```
https://warehouse.expwood.ru/api/products?search=%D0%BF%D0%B8%D0%BB%D0%BE&per_page=15&page=1&status=for_receipt
```

Сервер возвращает товары со статусом `in_stock`, а не `for_receipt`.

## Возможные причины

### 1. Логирование добавлено
Добавлено логирование в метод `index()` контроллера для отладки.

**Файл:** `app/Http/Controllers/Api/ProductController.php`

**Что логируется:**
- `search` - параметр поиска
- `status` - запрошенный статус
- `aggregate` - флаг агрегации

### 2. Как просмотреть логи на боевом сервере

```bash
# Просмотр последних логов
tail -f /var/www/sklad/storage/logs/laravel.log

# Фильтр по Products API
tail -f /var/www/sklad/storage/logs/laravel.log | grep "Products API"

# Количество последних строк
tail -100 /var/www/sklad/storage/logs/laravel.log
```

### 3. Возможные проблемы и решения

#### Проблема 1: Нет товаров со статусом `for_receipt`
**Проверка:**
```bash
# На боевом сервере подключиться к MySQL
mysql -u root -p sklad

# Выполнить
SELECT COUNT(*) as for_receipt_count FROM products WHERE status = 'for_receipt';
SELECT COUNT(*) as in_stock_count FROM products WHERE status = 'in_stock';
```

**Решение:** Если товаров со статусом `for_receipt` нет, то это нормально - сервер просто возвращает пустой список.

#### Проблема 2: Статус игнорируется
**Проверка в логах:**
```
"requested_status": "for_receipt",
"returned_statuses": ["in_stock"],
```

**Это означает:** Параметр `status` игнорируется, и возвращаются все товары.

**Возможные причины:**
1. Параметр `status` не передается в запрос (проверить URL)
2. Фильтр `->when($request->status, ...)` не работает
3. Проблема с кодированием параметра (URL encoding)

#### Проблема 3: Ошибка "aggregate"
**Проверка:** Если `aggregate=true` и `status=in_stock`, используется метод `getAggregatedProducts()`, который **всегда** возвращает только `in_stock` товары (строка 131).

**Решение:** Это правильное поведение для агрегированного запроса.

## Шаги отладки

### Шаг 1: Проверить параметры в запросе

```bash
# Тест с curl
curl -H "Authorization: Bearer TOKEN" \
  'https://warehouse.expwood.ru/api/products?status=for_receipt&per_page=1'

# Проверить, что в логах появится:
# "requested_status": "for_receipt"
```

### Шаг 2: Проверить код SQL

В логах Laravel должны быть SQL запросы. Ищите:
```sql
WHERE `status` = 'for_receipt'
```

Если этого WHERE нет, то фильтр не применяется.

### Шаг 3: Проверить кодирование URL

Параметр `%D0%BF%D0%B8%D0%BB%D0%BE` - это кодированное "пило" (UTF-8).

Убедитесь, что `status=for_receipt` передается правильно:
- ✅ `?status=for_receipt` - правильно
- ❌ `?status=for%5Freceipt` - неправильно (кодирован весь status)

## Техническая информация

### Код фильтра

```php
->when($request->status, function ($query, $status) {
    $query->where('status', $status);
})
```

Это означает:
- Если параметр `status` присутствует в запросе, добавляется WHERE условие
- Если параметра нет, фильтр не применяется

### Возможные статусы

```php
const STATUS_IN_STOCK = 'in_stock';
const STATUS_IN_TRANSIT = 'in_transit';
const STATUS_FOR_RECEIPT = 'for_receipt';
const STATUS_CORRECTION = 'correction';
```

## Коммит с логированием

Коммит: `3a7ed1b`

**Изменение:** Добавлено логирование в метод `index()` контроллера ProductController.

## Как получить логи

### На локальной машине (после pull):
```bash
tail -100 storage/logs/laravel.log | grep "Products API"
```

### На боевом сервере:
```bash
ssh user@192.168.x.x
cd /var/www/sklad
tail -100 storage/logs/laravel.log | grep "Products API"
```

## Если проблема сохраняется

1. Проверьте точный URL в браузере (консоль Network)
2. Проверьте логи на боевом сервере сразу после запроса
3. Проверьте, что используется правильный token для авторизации
4. Проверьте, что товары с нужным статусом существуют в БД
