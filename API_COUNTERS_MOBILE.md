# API Документация: Счетчики новых записей для мобильного приложения

## Базовый URL
```
https://warehouse.expwood.ru/api
```

## Аутентификация
Все запросы требуют Bearer токен в заголовке:
```
Authorization: Bearer {your_token}
```

---

## Обзор функционала

Система отслеживает количество новых записей в трех разделах:
- **Поступление товара** (`receipts`)
- **Товары в пути** (`products_in_transit`)
- **Реализация** (`sales`)

Каждый раздел имеет независимое время последнего просмотра. Счетчик показывает количество записей, созданных после времени последнего просмотра раздела.

---

## Эндпоинты

### 1. Отметить открытие приложения

**`POST /api/app/opened`**

Вызывается при запуске приложения или возврате из фонового режима. Обновляет общее время последнего открытия приложения.

**Заголовки:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Тело запроса:** Пустое (или можно не отправлять)

**Успешный ответ (200):**
```json
{
  "success": true,
  "message": "Время открытия обновлено",
  "data": {
    "last_app_opened_at": "2025-11-10T07:30:00+00:00",
    "sections": {
      "receipts": "2025-11-10T07:19:48+00:00",
      "products_in_transit": "2025-11-10T07:23:00+00:00",
      "sales": null
    }
  }
}
```

**Ошибки:**
- `401` - Пользователь не авторизован

---

### 2. Получить время последнего открытия

**`GET /api/app/last-opened`**

Возвращает время последнего открытия приложения и время просмотра всех разделов.

**Заголовки:**
```
Authorization: Bearer {token}
```

**Успешный ответ (200):**
```json
{
  "success": true,
  "data": {
    "last_app_opened_at": "2025-11-10T07:30:00+00:00",
    "sections": {
      "receipts": "2025-11-10T07:19:48+00:00",
      "products_in_transit": "2025-11-10T07:23:00+00:00",
      "sales": null
    }
  }
}
```

**Ошибки:**
- `401` - Пользователь не авторизован

---

### 3. Отметить просмотр раздела

**`POST /api/app/sections/viewed`**

Фиксирует время просмотра конкретного раздела. Вызывается при открытии экрана раздела для обнуления счетчика.

**Заголовки:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Тело запроса:**
```json
{
  "section": "receipts",
  "viewed_at": "2025-11-10T07:30:00+00:00"  // опционально, по умолчанию текущее серверное время
}
```

**Допустимые значения `section`:**
- `receipts` - Поступление товара
- `products_in_transit` - Товары в пути
- `sales` - Реализация

**Успешный ответ (200):**
```json
{
  "success": true,
  "data": {
    "section": "receipts",
    "last_viewed_at": "2025-11-10T07:30:00+00:00",
    "sections": {
      "receipts": "2025-11-10T07:30:00+00:00",
      "products_in_transit": "2025-11-10T07:23:00+00:00",
      "sales": null
    }
  }
}
```

**Ошибки:**
- `401` - Пользователь не авторизован
- `422` - Неверное значение `section` или формат `viewed_at`

---

### 4. Получить счетчик новых поступлений

**`GET /api/receipts/new-count`**

Возвращает количество новых записей в разделе "Поступление товара".

**Заголовки:**
```
Authorization: Bearer {token}
```

**Query параметры (опционально):**
- `status` (string) - фильтр по статусу
- `search` (string) - поиск по названию, производителю и т.д.

**Успешный ответ (200):**
```json
{
  "success": true,
  "data": {
    "new_count": 1,
    "last_viewed_at": "2025-11-10T07:19:48+00:00",
    "last_app_opened_at": "2025-11-07T11:27:39+00:00"
  }
}
```

**Поля ответа:**
- `new_count` (int) - количество новых записей после `last_viewed_at`
- `last_viewed_at` (string|null) - время последнего просмотра раздела (ISO 8601)
- `last_app_opened_at` (string|null) - время последнего открытия приложения (ISO 8601)

**Ошибки:**
- `401` - Пользователь не авторизован
- `403` - Недостаточно прав (требуется роль `warehouse_worker`)

---

### 5. Получить список поступлений с счетчиком

**`GET /api/receipts`**

Возвращает список поступлений с пагинацией и счетчиком новых записей.

**Заголовки:**
```
Authorization: Bearer {token}
```

**Query параметры:**
- `page` (int, default: 1) - номер страницы
- `per_page` (int, default: 15) - количество записей на странице
- `status` (string, optional) - фильтр по статусу
- `search` (string, optional) - поиск
- `warehouse_id` (int, optional) - фильтр по складу
- `shipping_location` (string, optional) - фильтр по месту отправки
- `date_from` (date, optional) - фильтр по дате создания (от)
- `date_to` (date, optional) - фильтр по дате создания (до)

**Успешный ответ (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Товар",
      "status": "in_transit",
      "warehouse_id": 1,
      "created_at": "2025-11-10T08:00:00+00:00",
      // ... другие поля товара
    }
  ],
  "new_count": 1,
  "last_viewed_at": "2025-11-10T07:19:48+00:00",
  "last_app_opened_at": "2025-11-07T11:27:39+00:00",
  "pagination": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 15,
    "total": 42
  }
}
```

**Ошибки:**
- `401` - Пользователь не авторизован
- `403` - Недостаточно прав

---

### 6. Получить счетчик новых товаров в пути

**`GET /api/products-in-transit/new-count`**

Возвращает количество новых записей в разделе "Товары в пути".

**Заголовки:**
```
Authorization: Bearer {token}
```

**Query параметры (опционально):**
- `status` (string) - фильтр по статусу
- `search` (string) - поиск

**Успешный ответ (200):**
```json
{
  "success": true,
  "data": {
    "new_count": 1,
    "last_viewed_at": "2025-11-10T07:23:00+00:00",
    "last_app_opened_at": "2025-11-07T11:27:39+00:00"
  }
}
```

**Ошибки:**
- `401` - Пользователь не авторизован
- `403` - Недостаточно прав (требуется роль `warehouse_worker`)

---

### 7. Получить список товаров в пути с счетчиком

**`GET /api/products-in-transit`**

Возвращает список товаров в пути с пагинацией и счетчиком новых записей.

**Заголовки:**
```
Authorization: Bearer {token}
```

**Query параметры:** Аналогичны `/api/receipts`

**Успешный ответ (200):** Аналогичен `/api/receipts`

**Ошибки:**
- `401` - Пользователь не авторизован
- `403` - Недостаточно прав

---

### 8. Получить счетчик новых продаж

**`GET /api/sales/new-count`**

Возвращает количество новых записей в разделе "Реализация".

**Заголовки:**
```
Authorization: Bearer {token}
```

**Query параметры (опционально):**
- `search` (string) - поиск
- `warehouse_id` (int) - фильтр по складу
- `payment_status` (string) - фильтр по статусу оплаты
- `payment_method` (string) - фильтр по способу оплаты
- `date_from` (date) - фильтр по дате продажи (от)
- `date_to` (date) - фильтр по дате продажи (до)
- `active` (boolean) - фильтр по активности

**Успешный ответ (200):**
```json
{
  "success": true,
  "data": {
    "new_count": 2,
    "last_viewed_at": null,
    "last_app_opened_at": "2025-11-07T11:27:39+00:00"
  }
}
```

**Ошибки:**
- `401` - Пользователь не авторизован

---

### 9. Получить список продаж с счетчиком

**`GET /api/sales`**

Возвращает список продаж с пагинацией и счетчиком новых записей.

**Заголовки:**
```
Authorization: Bearer {token}
```

**Query параметры:** Аналогичны `/api/sales/new-count`, плюс:
- `page` (int, default: 1) - номер страницы
- `per_page` (int, default: 15) - количество записей на странице

**Успешный ответ (200):**
```json
{
  "data": [
    {
      "id": 1,
      "sale_number": "S-001",
      "customer_name": "Иван Иванов",
      "total_price": 1500.00,
      "created_at": "2025-11-10T08:00:00+00:00",
      // ... другие поля продажи
    }
  ],
  "new_count": 2,
  "last_viewed_at": null,
  "last_app_opened_at": "2025-11-07T11:27:39+00:00",
  "links": {
    "first": "https://warehouse.expwood.ru/api/sales?page=1",
    "last": "https://warehouse.expwood.ru/api/sales?page=5",
    "prev": null,
    "next": "https://warehouse.expwood.ru/api/sales?page=2"
  },
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 72
  }
}
```

**Ошибки:**
- `401` - Пользователь не авторизован

---

## Логика работы счетчиков

### Определение "новых" записей

1. **Если `last_viewed_at` равно `null`** (раздел никогда не просматривался):
   - Все записи считаются новыми
   - `new_count` = общее количество записей в разделе

2. **Если `last_viewed_at` установлено**:
   - Новыми считаются записи, где `created_at > last_viewed_at`
   - `new_count` = количество таких записей

### Независимость разделов

Каждый раздел имеет свое независимое время просмотра:
- Просмотр раздела "Поступление товара" не влияет на счетчик "Товары в пути"
- Просмотр раздела "Реализация" не влияет на другие разделы

---

## Рекомендуемый сценарий использования

### При запуске приложения

1. Вызвать `POST /api/app/opened` для обновления общего времени открытия
2. Загрузить счетчики для всех разделов:
   - `GET /api/receipts/new-count`
   - `GET /api/products-in-transit/new-count`
   - `GET /api/sales/new-count`
3. Отобразить badge с количеством новых записей на главном экране

### При открытии раздела

1. Вызвать `POST /api/app/sections/viewed` с параметром `section`
2. Загрузить список записей раздела (например, `GET /api/receipts`)
3. Счетчик в ответе будет обновлен (обычно `new_count = 0` после просмотра)

### При возврате из фонового режима

1. Вызвать `POST /api/app/opened`
2. Обновить счетчики всех разделов

### Периодическое обновление счетчиков

Для обновления счетчиков без загрузки полного списка используйте эндпоинты `/new-count`:
- `GET /api/receipts/new-count`
- `GET /api/products-in-transit/new-count`
- `GET /api/sales/new-count`

---

## Примеры запросов

### cURL

**Отметить просмотр раздела:**
```bash
curl -X POST https://warehouse.expwood.ru/api/app/sections/viewed \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"section":"receipts"}'
```

**Получить счетчик:**
```bash
curl https://warehouse.expwood.ru/api/receipts/new-count \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### JavaScript (Fetch API)

```javascript
// Отметить просмотр раздела
async function markSectionViewed(token, section) {
  const response = await fetch('https://warehouse.expwood.ru/api/app/sections/viewed', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ section }),
  });
  return await response.json();
}

// Получить счетчик
async function getNewCount(token, section) {
  const endpoint = {
    receipts: '/api/receipts/new-count',
    products_in_transit: '/api/products-in-transit/new-count',
    sales: '/api/sales/new-count',
  }[section];
  
  const response = await fetch(`https://warehouse.expwood.ru${endpoint}`, {
    headers: {
      'Authorization': `Bearer ${token}`,
    },
  });
  return await response.json();
}
```

---

## Коды ошибок

- `200` - Успешный запрос
- `401` - Не авторизован (неверный или отсутствующий токен)
- `403` - Доступ запрещен (недостаточно прав)
- `422` - Ошибка валидации (неверные параметры запроса)
- `500` - Внутренняя ошибка сервера

---

## Форматы дат

Все даты в API используют формат ISO 8601:
```
2025-11-10T07:30:00+00:00
```

При отправке `viewed_at` можно использовать тот же формат или опустить поле (будет использовано текущее серверное время).

---

## Примечания

1. Все эндпоинты требуют аутентификации через Bearer токен
2. Счетчики обновляются автоматически при каждом запросе
3. Для оптимизации используйте эндпоинты `/new-count` для быстрого получения только счетчика без загрузки полного списка
4. Время синхронизируется с сервером, поэтому используется серверное время
5. Разделы `receipts` и `products_in_transit` требуют роль `warehouse_worker`

