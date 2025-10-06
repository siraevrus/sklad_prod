# Детальная API документация для разделов складского учета

## Базовая информация

**Base URL:** `https://your-domain.com/api`  
**Аутентификация:** Bearer Token (Sanctum)  
**Content-Type:** `application/json`  
**Заголовки:**
```
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

---

## 1. ПОСТУПЛЕНИЕ ТОВАРОВ (Products API)

### 1.1 Получение списка товаров

**Endpoint:** `GET /api/products`

**Описание:** Получение списка всех товаров с фильтрацией и пагинацией

**Query параметры:**
```
search          - Поиск по названию, описанию, производителю
warehouse_id    - Фильтр по складу (ID)
template_id     - Фильтр по шаблону товара (ID)
producer_id     - Фильтр по производителю (ID)
status          - Фильтр по статусу (in_stock, in_transit, for_receipt, correction)
in_stock        - Только товары в наличии (boolean)
low_stock       - Товары с низким остатком (boolean)
active          - Только активные товары (boolean)
per_page        - Количество на странице (по умолчанию: 15)
page            - Номер страницы
```

**Пример запроса:**
```bash
GET /api/products?warehouse_id=1&status=in_stock&per_page=20&page=1
```

**Ответ:**
```json
{
  "data": [
    {
      "id": 1,
      "product_template_id": 1,
      "warehouse_id": 1,
      "created_by": 1,
      "name": "Доска: 200 x 50 x 6000",
      "description": "Строительная доска",
      "attributes": {
        "length": "6000",
        "width": "200",
        "height": "50"
      },
      "calculated_volume": "60.0000",
      "quantity": "100.000",
      "sold_quantity": 0,
      "transport_number": "AB1234CD",
      "producer_id": 1,
      "arrival_date": "2024-01-15",
      "status": "in_stock",
      "is_active": true,
      "shipping_location": null,
      "shipping_date": null,
      "expected_arrival_date": null,
      "actual_arrival_date": null,
      "document_path": [],
      "notes": null,
      "correction": null,
      "correction_status": null,
      "revised_at": null,
      "created_at": "2024-01-15T10:00:00.000000Z",
      "updated_at": "2024-01-15T10:00:00.000000Z",
      "template": {
        "id": 1,
        "name": "Доска",
        "unit": "м³"
      },
      "warehouse": {
        "id": 1,
        "name": "Основной склад"
      },
      "creator": {
        "id": 1,
        "name": "Иван Иванов"
      },
      "producer": {
        "id": 1,
        "name": "ООО Лесопилка"
      }
    }
  ],
  "links": {
    "first": "https://domain.com/api/products?page=1",
    "last": "https://domain.com/api/products?page=5",
    "prev": null,
    "next": "https://domain.com/api/products?page=2"
  },
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 75
  }
}
```

### 1.2 Получение товара по ID

**Endpoint:** `GET /api/products/{id}`

**Описание:** Получение детальной информации о товаре

**Пример запроса:**
```bash
GET /api/products/1
```

**Ответ:**
```json
{
  "id": 1,
  "product_template_id": 1,
  "warehouse_id": 1,
  "created_by": 1,
  "name": "Доска: 200 x 50 x 6000",
  "description": "Строительная доска",
  "attributes": {
    "length": "6000",
    "width": "200", 
    "height": "50"
  },
  "calculated_volume": "60.0000",
  "quantity": "100.000",
  "sold_quantity": 0,
  "transport_number": "AB1234CD",
  "producer_id": 1,
  "arrival_date": "2024-01-15",
  "status": "in_stock",
  "is_active": true,
  "shipping_location": null,
  "shipping_date": null,
  "expected_arrival_date": null,
  "actual_arrival_date": null,
  "document_path": [],
  "notes": null,
  "correction": null,
  "correction_status": null,
  "revised_at": null,
  "created_at": "2024-01-15T10:00:00.000000Z",
  "updated_at": "2024-01-15T10:00:00.000000Z",
  "template": {
    "id": 1,
    "name": "Доска",
    "unit": "м³",
    "formula": "length * width * height / 1000000000",
    "attributes": [
      {
        "id": 1,
        "variable": "length",
        "full_name": "Длина (мм)",
        "type": "number",
        "is_required": true,
        "is_in_formula": true
      }
    ]
  },
  "warehouse": {
    "id": 1,
    "name": "Основной склад",
    "address": "ул. Складская, 1"
  },
  "creator": {
    "id": 1,
    "name": "Иван Иванов",
    "email": "ivan@example.com"
  },
  "producer": {
    "id": 1,
    "name": "ООО Лесопилка",
    "region": "Московская область"
  }
}
```

### 1.3 Создание товара

**Endpoint:** `POST /api/products`

**Описание:** Создание нового товара в системе

**Тело запроса:**
```json
{
  "product_template_id": 1,
  "warehouse_id": 1,
  "name": "Доска: 200 x 50 x 6000",
  "description": "Строительная доска высокого качества",
  "attributes": {
    "length": "6000",
    "width": "200",
    "height": "50",
    "grade": "1 сорт"
  },
  "quantity": "100.5",
  "calculated_volume": "60.6000",
  "transport_number": "AB1234CD",
  "producer_id": 1,
  "arrival_date": "2024-01-15",
  "is_active": true,
  "status": "in_stock",
  "shipping_location": "Москва",
  "shipping_date": "2024-01-10",
  "expected_arrival_date": "2024-01-15",
  "notes": "Товар высокого качества",
  "document_path": [
    "/storage/documents/invoice_123.pdf",
    "/storage/documents/certificate_456.pdf"
  ]
}
```

**Обязательные поля:**
- `product_template_id` - ID шаблона товара
- `warehouse_id` - ID склада
- `quantity` - Количество товара

**Необязательные поля:**
- `name` - Название (генерируется автоматически из атрибутов)
- `description` - Описание
- `attributes` - Атрибуты товара (объект)
- `calculated_volume` - Рассчитанный объем (рассчитывается автоматически)
- `transport_number` - Номер транспорта
- `producer_id` - ID производителя
- `arrival_date` - Дата поступления (по умолчанию: текущая дата)
- `is_active` - Активность (по умолчанию: true)
- `status` - Статус (по умолчанию: "in_stock")
- `shipping_location` - Место отгрузки
- `shipping_date` - Дата отгрузки
- `expected_arrival_date` - Ожидаемая дата прибытия
- `notes` - Заметки
- `document_path` - Пути к документам (массив)

**Ответ:**
```json
{
  "message": "Товар создан",
  "product": {
    "id": 1,
    "product_template_id": 1,
    "warehouse_id": 1,
    "created_by": 1,
    "name": "Доска: 200 x 50 x 6000",
    "description": "Строительная доска высокого качества",
    "attributes": {
      "length": "6000",
      "width": "200",
      "height": "50",
      "grade": "1 сорт"
    },
    "calculated_volume": "60.6000",
    "quantity": "100.500",
    "sold_quantity": 0,
    "transport_number": "AB1234CD",
    "producer_id": 1,
    "arrival_date": "2024-01-15",
    "status": "in_stock",
    "is_active": true,
    "shipping_location": "Москва",
    "shipping_date": "2024-01-10",
    "expected_arrival_date": "2024-01-15",
    "actual_arrival_date": null,
    "document_path": [
      "/storage/documents/invoice_123.pdf",
      "/storage/documents/certificate_456.pdf"
    ],
    "notes": "Товар высокого качества",
    "correction": null,
    "correction_status": null,
    "revised_at": null,
    "created_at": "2024-01-15T10:00:00.000000Z",
    "updated_at": "2024-01-15T10:00:00.000000Z",
    "template": {
      "id": 1,
      "name": "Доска",
      "unit": "м³"
    },
    "warehouse": {
      "id": 1,
      "name": "Основной склад"
    },
    "creator": {
      "id": 1,
      "name": "Иван Иванов"
    }
  }
}
```

### 1.4 Обновление товара

**Endpoint:** `PUT /api/products/{id}`

**Описание:** Обновление существующего товара

**Тело запроса:**
```json
{
  "name": "Доска: 200 x 50 x 6000 (обновлено)",
  "description": "Обновленное описание",
  "attributes": {
    "length": "6000",
    "width": "200",
    "height": "50",
    "grade": "высший сорт"
  },
  "quantity": "95.5",
  "transport_number": "CD5678EF",
  "producer_id": 2,
  "arrival_date": "2024-01-16",
  "is_active": true,
  "notes": "Обновленные заметки"
}
```

**Ответ:**
```json
{
  "message": "Товар обновлен",
  "product": {
    // Обновленные данные товара
  }
}
```

### 1.5 Удаление товара

**Endpoint:** `DELETE /api/products/{id}`

**Описание:** Удаление товара из системы

**Ответ:**
```json
{
  "message": "Товар удален"
}
```

### 1.6 Статистика по товарам

**Endpoint:** `GET /api/products/stats`

**Описание:** Получение статистики по товарам

**Ответ:**
```json
{
  "total_products": 150,
  "active_products": 145,
  "in_stock": 120,
  "low_stock": 15,
  "out_of_stock": 10,
  "total_quantity": 5000,
  "total_volume": 1250.5000,
  "low_stock_count": 15,
  "out_of_stock_count": 10
}
```

### 1.7 Популярные товары

**Endpoint:** `GET /api/products/popular`

**Описание:** Получение списка популярных товаров (топ-10 по продажам)

**Ответ:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Доска: 200 x 50 x 6000",
      "total_sales": 25,
      "total_revenue": "125000.00",
      "template": {
        "id": 1,
        "name": "Доска"
      },
      "warehouse": {
        "id": 1,
        "name": "Основной склад"
      }
    }
  ]
}
```

### 1.8 Экспорт товаров

**Endpoint:** `GET /api/products/export`

**Описание:** Экспорт данных о товарах (поддерживает те же фильтры, что и список товаров)

**Query параметры:** Те же, что и для `GET /api/products`

**Ответ:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Доска: 200 x 50 x 6000",
      "description": "Строительная доска",
      "producer_id": 1,
      "quantity": "100.000",
      "calculated_volume": "60.0000",
      "warehouse": "Основной склад",
      "template": "Доска",
      "arrival_date": "2024-01-15",
      "is_active": "Да",
      "created_at": "2024-01-15 10:00:00"
    }
  ],
  "total": 1
}
```

---

## 2. ТОВАРЫ В ПУТИ (Products in Transit API)

### 2.1 Получение списка товаров в пути

**Endpoint:** `GET /api/products-in-transit`

**Описание:** Получение списка товаров в пути (статус "in_transit")

**Query параметры:**
```
status              - Фильтр по статусу (по умолчанию: in_transit)
warehouse_id        - Фильтр по складу
shipping_location   - Фильтр по месту отгрузки
search             - Поиск по названию, производителю, месту отгрузки
sort               - Поле для сортировки (по умолчанию: created_at)
order              - Порядок сортировки (asc/desc, по умолчанию: desc)
per_page           - Количество на странице (по умолчанию: 15)
```

**Пример запроса:**
```bash
GET /api/products-in-transit?warehouse_id=1&shipping_location=Москва&per_page=10
```

**Ответ:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "product_template_id": 1,
      "warehouse_id": 1,
      "created_by": 1,
      "name": "Доска: 200 x 50 x 6000",
      "description": "Строительная доска",
      "attributes": {
        "length": "6000",
        "width": "200",
        "height": "50"
      },
      "calculated_volume": "60.0000",
      "quantity": "100.000",
      "transport_number": "AB1234CD",
      "producer_id": 1,
      "status": "in_transit",
      "is_active": true,
      "shipping_location": "Москва",
      "shipping_date": "2024-01-10",
      "expected_arrival_date": "2024-01-15",
      "actual_arrival_date": null,
      "document_path": [
        "/storage/documents/invoice_123.pdf"
      ],
      "notes": "Товар в пути",
      "created_at": "2024-01-10T08:00:00.000000Z",
      "updated_at": "2024-01-10T08:00:00.000000Z",
      "warehouse": {
        "id": 1,
        "name": "Основной склад"
      },
      "template": {
        "id": 1,
        "name": "Доска",
        "unit": "м³"
      },
      "creator": {
        "id": 1,
        "name": "Иван Иванов"
      }
    }
  ],
  "pagination": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 10,
    "total": 25
  }
}
```

### 2.2 Получение товара в пути по ID

**Endpoint:** `GET /api/products-in-transit/{id}`

**Описание:** Получение детальной информации о товаре в пути

**Пример запроса:**
```bash
GET /api/products-in-transit/1
```

**Ответ:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "product_template_id": 1,
    "warehouse_id": 1,
    "created_by": 1,
    "name": "Доска: 200 x 50 x 6000",
    "description": "Строительная доска",
    "attributes": {
      "length": "6000",
      "width": "200",
      "height": "50"
    },
    "calculated_volume": "60.0000",
    "quantity": "100.000",
    "transport_number": "AB1234CD",
    "producer_id": 1,
    "status": "in_transit",
    "is_active": true,
    "shipping_location": "Москва",
    "shipping_date": "2024-01-10",
    "expected_arrival_date": "2024-01-15",
    "actual_arrival_date": null,
    "document_path": [
      "/storage/documents/invoice_123.pdf",
      "/storage/documents/transport_456.pdf"
    ],
    "notes": "Товар в пути, ожидается прибытие 15 января",
    "created_at": "2024-01-10T08:00:00.000000Z",
    "updated_at": "2024-01-10T08:00:00.000000Z",
    "warehouse": {
      "id": 1,
      "name": "Основной склад",
      "address": "ул. Складская, 1"
    },
    "template": {
      "id": 1,
      "name": "Доска",
      "unit": "м³",
      "formula": "length * width * height / 1000000000"
    },
    "creator": {
      "id": 1,
      "name": "Иван Иванов",
      "email": "ivan@example.com"
    }
  }
}
```

### 2.3 Создание товара в пути

**Endpoint:** `POST /api/products-in-transit`

**Описание:** Создание нового товара в пути (статус "in_transit")

**Тело запроса (одиночный товар):**
```json
{
  "warehouse_id": 1,
  "product_template_id": 1,
  "quantity": "100.5",
  "producer": "ООО Лесопилка",
  "description": "Строительная доска высокого качества",
  "name": "Доска: 200 x 50 x 6000",
  "attributes": {
    "length": "6000",
    "width": "200",
    "height": "50",
    "grade": "1 сорт"
  },
  "shipping_location": "Москва",
  "shipping_date": "2024-01-10",
  "transport_number": "AB1234CD",
  "expected_arrival_date": "2024-01-15",
  "notes": "Товар в пути",
  "document_path": [
    "/storage/documents/invoice_123.pdf"
  ]
}
```

**Тело запроса (множественные товары):**
```json
{
  "warehouse_id": 1,
  "shipping_location": "Москва",
  "shipping_date": "2024-01-10",
  "transport_number": "AB1234CD",
  "expected_arrival_date": "2024-01-15",
  "notes": "Партия товаров в пути",
  "document_path": [
    "/storage/documents/invoice_123.pdf"
  ],
  "products": [
    {
      "product_template_id": 1,
      "quantity": "100.5",
      "producer": "ООО Лесопилка",
      "description": "Доска строительная",
      "name": "Доска: 200 x 50 x 6000",
      "attributes": {
        "length": "6000",
        "width": "200",
        "height": "50"
      }
    },
    {
      "product_template_id": 2,
      "quantity": "50.0",
      "producer": "ООО Металлург",
      "description": "Профиль металлический",
      "name": "Профиль: 40 x 40 x 3000",
      "attributes": {
        "length": "3000",
        "width": "40",
        "height": "40"
      }
    }
  ]
}
```

**Обязательные поля:**
- `warehouse_id` - ID склада (только для админов)
- `product_template_id` - ID шаблона товара (для одиночного товара)
- `products.*.product_template_id` - ID шаблона товара (для каждого товара в массиве)

**Необязательные поля:**
- `quantity` - Количество (по умолчанию: 1)
- `producer` - Название производителя
- `description` - Описание
- `name` - Название (генерируется автоматически)
- `attributes` - Атрибуты товара
- `shipping_location` - Место отгрузки
- `shipping_date` - Дата отгрузки (по умолчанию: текущая дата)
- `transport_number` - Номер транспорта
- `expected_arrival_date` - Ожидаемая дата прибытия
- `notes` - Заметки
- `document_path` - Пути к документам

**Ответ:**
```json
{
  "success": true,
  "message": "Товар(ы) в пути успешно созданы",
  "data": {
    "id": 1,
    "product_template_id": 1,
    "warehouse_id": 1,
    "created_by": 1,
    "name": "Доска: 200 x 50 x 6000",
    "description": "Строительная доска высокого качества",
    "attributes": {
      "length": "6000",
      "width": "200",
      "height": "50",
      "grade": "1 сорт"
    },
    "calculated_volume": "60.6000",
    "quantity": "100.500",
    "transport_number": "AB1234CD",
    "producer": "ООО Лесопилка",
    "status": "in_transit",
    "is_active": true,
    "shipping_location": "Москва",
    "shipping_date": "2024-01-10",
    "expected_arrival_date": "2024-01-15",
    "actual_arrival_date": null,
    "document_path": [
      "/storage/documents/invoice_123.pdf"
    ],
    "notes": "Товар в пути",
    "created_at": "2024-01-10T08:00:00.000000Z",
    "updated_at": "2024-01-10T08:00:00.000000Z"
  }
}
```

---

## 3. ПРИЕМКА (Receipts API)

### 3.1 Получение списка товаров для приемки

**Endpoint:** `GET /api/receipts`

**Описание:** Получение списка товаров готовых к приемке (статус "in_transit")

**Query параметры:** Те же, что и для товаров в пути

**Пример запроса:**
```bash
GET /api/receipts?warehouse_id=1&per_page=10
```

**Ответ:** Аналогичен ответу для товаров в пути

### 3.2 Получение товара для приемки по ID

**Endpoint:** `GET /api/receipts/{id}`

**Описание:** Получение детальной информации о товаре для приемки

**Пример запроса:**
```bash
GET /api/receipts/1
```

**Ответ:** Аналогичен ответу для товара в пути

### 3.3 Создание товара для приемки

**Endpoint:** `POST /api/receipts`

**Описание:** Создание нового товара для приемки (аналогично созданию товара в пути)

**Тело запроса:** Аналогично созданию товара в пути

**Ответ:** Аналогично ответу создания товара в пути

### 3.4 Принять товар (перевести в остатки)

**Endpoint:** `POST /api/receipts/{id}/receive`

**Описание:** Принятие товара на склад (изменение статуса с "in_transit" на "in_stock")

**Пример запроса:**
```bash
POST /api/receipts/1/receive
```

**Тело запроса:** Пустое

**Ответ:**
```json
{
  "success": true,
  "message": "Товар принят",
  "data": {
    "id": 1,
    "product_template_id": 1,
    "warehouse_id": 1,
    "created_by": 1,
    "name": "Доска: 200 x 50 x 6000",
    "description": "Строительная доска",
    "attributes": {
      "length": "6000",
      "width": "200",
      "height": "50"
    },
    "calculated_volume": "60.0000",
    "quantity": "100.000",
    "transport_number": "AB1234CD",
    "producer_id": 1,
    "arrival_date": "2024-01-15",
    "status": "in_stock",
    "is_active": true,
    "shipping_location": "Москва",
    "shipping_date": "2024-01-10",
    "expected_arrival_date": "2024-01-15",
    "actual_arrival_date": "2024-01-15",
    "document_path": [
      "/storage/documents/invoice_123.pdf"
    ],
    "notes": "Товар в пути",
    "correction": null,
    "correction_status": null,
    "revised_at": null,
    "created_at": "2024-01-10T08:00:00.000000Z",
    "updated_at": "2024-01-15T14:30:00.000000Z"
  }
}
```

#### Поведение кнопки «Принять товар» (UI)

- Доступна для записей со статусом in_transit или for_receipt.
- По нажатию отправляет POST `/api/receipts/{id}/receive` без тела.
- При успехе: показать уведомление «Товар принят», обновить запись в списке (status -> in_stock, actual_arrival_date -> now()).
- Ошибки:
  - 404 — если запись не принадлежит складу пользователя или не найдена;
  - 403 — при отсутствии прав;
  - 500 — при внутренней ошибке.

### 3.5 Добавить уточнение и принять товар

**Endpoint:** `POST /api/receipts/{id}/correction`

**Описание:** Добавление уточнения к товару и его принятие на склад

**Пример запроса:**
```bash
POST /api/receipts/1/correction
```

**Тело запроса:**
```json
{
  "correction": "Товар прибыл с небольшими повреждениями упаковки, но содержимое в порядке. Количество соответствует заявленному."
}
```

**Обязательные поля:**
- `correction` - Текст уточнения (минимум 10 символов, максимум 1000)

**Ответ:**
```json
{
  "success": true,
  "message": "Уточнение сохранено и товар принят",
  "data": {
    "id": 1,
    "product_template_id": 1,
    "warehouse_id": 1,
    "created_by": 1,
    "name": "Доска: 200 x 50 x 6000",
    "description": "Строительная доска",
    "attributes": {
      "length": "6000",
      "width": "200",
      "height": "50"
    },
    "calculated_volume": "60.0000",
    "quantity": "100.000",
    "transport_number": "AB1234CD",
    "producer_id": 1,
    "arrival_date": "2024-01-15",
    "status": "in_stock",
    "is_active": true,
    "shipping_location": "Москва",
    "shipping_date": "2024-01-10",
    "expected_arrival_date": "2024-01-15",
    "actual_arrival_date": "2024-01-15T14:30:00.000000Z",
    "document_path": [
      "/storage/documents/invoice_123.pdf"
    ],
    "notes": "Товар в пути",
    "correction": "Товар прибыл с небольшими повреждениями упаковки, но содержимое в порядке. Количество соответствует заявленному.",
    "correction_status": "correction",
    "revised_at": "2024-01-15T14:30:00.000000Z",
    "created_at": "2024-01-10T08:00:00.000000Z",
    "updated_at": "2024-01-15T14:30:00.000000Z",
    "template": {
      "id": 1,
      "name": "Доска",
      "unit": "м³"
    },
    "warehouse": {
      "id": 1,
      "name": "Основной склад"
    },
    "creator": {
      "id": 1,
      "name": "Иван Иванов"
    }
  }
}
```

#### Поведение кнопки «Уточнение» (UI)

- Доступна для записей со статусом in_transit или for_receipt.
- Открывает модальное окно с полем ввода текста (textarea, 10–1000 символов).
- По подтверждению отправляет POST `/api/receipts/{id}/correction` с телом `{ "correction": "..." }`.
- При успехе: показать уведомление «Уточнение сохранено и товар принят», обновить запись:
  - `status` -> `in_stock`
  - `correction` -> введённый текст
  - `correction_status` -> `correction`
  - `actual_arrival_date` -> now()
- Ошибки валидации (422): подсветить поле и показать текст ошибки.
- Ошибки доступа: 403/404 — показать уведомление и не менять состояние UI.

---

## 4. ОБЩИЕ СТАТУСЫ ТОВАРОВ

### Возможные статусы товаров:
- `in_stock` - На складе (в остатках)
- `in_transit` - В пути
- `for_receipt` - Готов к приемке
- `correction` - С уточнением

### Возможные статусы уточнений:
- `null` - Без уточнений
- `correction` - С уточнением

---

## 5. КОДЫ ОШИБОК

### HTTP коды ответов:
- `200` - Успешный запрос
- `201` - Ресурс создан
- `400` - Неверный запрос
- `401` - Не авторизован
- `403` - Доступ запрещен
- `404` - Ресурс не найден
- `422` - Ошибка валидации
- `500` - Внутренняя ошибка сервера

### Примеры ошибок:

**Ошибка валидации (422):**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "product_template_id": [
      "The product template id field is required."
    ],
    "quantity": [
      "The quantity must be at least 0."
    ]
  }
}
```

**Ошибка доступа (403):**
```json
{
  "message": "Доступ запрещен"
}
```

**Ресурс не найден (404):**
```json
{
  "message": "Товар не найден"
}
```

**Ошибка авторизации (401):**
```json
{
  "message": "Unauthenticated."
}
```

---

## 6. ПРИМЕРЫ ИСПОЛЬЗОВАНИЯ

### Пример полного цикла работы с товаром:

1. **Создание товара в пути:**
```bash
curl -X POST "https://domain.com/api/products-in-transit" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "warehouse_id": 1,
    "product_template_id": 1,
    "quantity": "100.5",
    "shipping_location": "Москва",
    "transport_number": "AB1234CD",
    "expected_arrival_date": "2024-01-15",
    "attributes": {
      "length": "6000",
      "width": "200",
      "height": "50"
    }
  }'
```

2. **Получение списка товаров для приемки:**
```bash
curl -X GET "https://domain.com/api/receipts?warehouse_id=1" \
  -H "Authorization: Bearer {token}"
```

3. **Принятие товара с уточнением:**
```bash
curl -X POST "https://domain.com/api/receipts/1/correction" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "correction": "Товар прибыл в хорошем состоянии, количество соответствует заявленному."
  }'
```

4. **Проверка товара в остатках:**
```bash
curl -X GET "https://domain.com/api/products?status=in_stock&warehouse_id=1" \
  -H "Authorization: Bearer {token}"
```

---

## 7. ПРАВА ДОСТУПА

### Роли пользователей:
- `admin` - Полный доступ ко всем складам и операциям
- `warehouse_worker` - Доступ только к своему складу
- `operator` - Ограниченный доступ

### Ограничения доступа:
- Не-админы видят только товары своего склада
- Создание товаров на других складах доступно только админам
- Перемещение товаров между складами доступно только админам

---

Эта документация покрывает все основные операции с товарами в системе складского учета, включая создание, получение, обновление, приемку и добавление комментариев.
