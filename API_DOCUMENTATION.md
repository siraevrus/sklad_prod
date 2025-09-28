# 📚 API Документация - Система управления складом

## 🚀 Обзор

API для системы управления складом построен на Laravel 12 с использованием Laravel Sanctum для аутентификации. Система поддерживает полное управление товарами, продажами, приемкой, пользователями и компаниями с гибкой системой ролей и прав доступа.

### Основные возможности:
- 🔐 Аутентификация с Bearer токенами
- 📦 Управление товарами и шаблонами
- 🚚 Система приемки товаров в пути
- 💰 Управление продажами
- 👥 Система пользователей и ролей
- 🏢 Управление компаниями и складами
- 📋 Система запросов
- 📊 Статистика и аналитика

### Базовый URL
- **Локальная разработка**: `http://localhost/api`
- **Продакшн**: `https://your-domain.com/api`

## 🔐 Аутентификация

### Bearer Token
Все защищенные эндпоинты требуют Bearer токен в заголовке:
```
Authorization: Bearer {your-token}
```

## 🚀 Быстрый старт

### 1. Регистрация (только в dev окружении)
```http
POST /api/auth/register
Content-Type: application/json

{
  "name": "Иван Иванов",
  "username": "ivan",
  "email": "ivan@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

### 2. Получение токена
```http
POST /api/auth/login
Content-Type: application/json

{
  "login": "ivan@example.com",
  "password": "password123"
}
```

**Ответ:**
```json
{
  "message": "Успешный вход",
  "user": {
    "id": 1,
    "name": "Иван Иванов",
    "username": "ivan",
    "email": "ivan@example.com",
    "role": "warehouse_worker",
    "warehouse_id": 1,
    "company_id": 1,
    "is_blocked": false
  },
  "token": "1|abc123..."
}
```

### 3. Использование токена
Добавьте полученный токен в заголовки всех запросов:
```http
Authorization: Bearer 1|abc123...
Content-Type: application/json
Accept: application/json
```

## 📦 Товары (Products)

### Получение списка товаров
```http
GET /api/products?page=1&per_page=20&search=название&has_correction=true
```

**Параметры запроса:**
- `page` - номер страницы (по умолчанию: 1)
- `per_page` - количество записей на странице (по умолчанию: 15, максимум: 200)
- `search` - поиск по названию, описанию или производителю
- `warehouse_id` - фильтр по складу
- `template_id` - фильтр по шаблону товара
- `producer` - фильтр по производителю
- `in_stock` - только товары в наличии
- `low_stock` - товары с низким остатком (≤10)
- `active` - только активные товары
- `has_correction` - только товары с уточнениями

**Ответ:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Кирпич красный М-150",
      "description": "Строительный кирпич",
      "quantity": 1000,
      "attributes": {
        "марка": "М-150",
        "цвет": "красный"
      },
      "transport_number": "А123БВ77",
      "producer": "ООО Кирпичный завод",
      "arrival_date": "2025-09-08",
      "is_active": true,
      "calculated_volume": 2.5,
      "correction": "В товаре ошибка с количеством - всего пришло 90",
      "correction_status": "correction",
      "document_path": ["documents/invoice_001.pdf", "documents/spec_001.pdf"],
      "template": {
        "id": 1
      },
      "warehouse": {
        "id": 1
      },
      "creator": {
        "id": 1
      }
    }
  ],
  "links": {
    "first": "http://localhost:8000/api/products?page=1",
    "last": "http://localhost:8000/api/products?page=5",
    "prev": null,
    "next": "http://localhost:8000/api/products?page=2"
  },
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 20,
    "total": 100
  }
}
```

### Создание товара
```http
POST /api/products
Content-Type: application/json

{
  "product_template_id": 1,
  "warehouse_id": 1,
  "quantity": 1000,
  "description": "Строительный кирпич",
  "attributes": {
    "марка": "М-150",
    "цвет": "красный"
  },
  "transport_number": "А123БВ77",
  "producer": "ООО Кирпичный завод",
  "arrival_date": "2025-09-08",
  "is_active": true
}
```

### Обновление товара
```http
PUT /api/products/{id}
Content-Type: application/json

{
  "quantity": 950,
  "description": "Обновленное описание"
}
```

### Удаление товара
```http
DELETE /api/products/{id}
```

## 🔧 Управление уточнениями

### Добавление уточнения к товару
```http
POST /api/products/{id}/correction
Content-Type: application/json

{
  "correction": "В товаре ошибка с количеством - всего пришло 90"
}
```

**Ответ:**
```json
{
  "success": true,
  "message": "Уточнение успешно добавлено к товару",
  "data": {
    "id": 1,
    "correction": "В товаре ошибка с количеством - всего пришло 90",
    "correction_status": "correction",
    "updated_at": "2025-09-09T09:54:41.000000Z"
  }
}
```

### Удаление уточнения
```http
DELETE /api/products/{id}/correction
```

**Ответ:**
```json
{
  "success": true,
  "message": "Уточнение успешно удалено",
  "data": {
    "id": 1,
    "correction": null,
    "correction_status": null,
    "updated_at": "2025-09-09T10:00:00.000000Z"
  }
}
```

## 📊 Статистика товаров
```http
GET /api/products/stats
```

**Ответ:**
```json
{
  "success": true,
  "data": {
    "total_products": 150,
    "active_products": 140,
    "in_stock": 120,
    "low_stock": 15,
    "out_of_stock": 5,
    "total_quantity": 50000,
    "total_volume": 1250.5
  }
}
```

## 🚚 Приемка / Товары в пути (Receipts)

### Получение списка товаров в пути
```http
GET /api/receipts?status=in_transit&per_page=15&warehouse_id=1
```

**Параметры запроса:**
- `status` — статус товара (`in_transit`, `for_receipt`, `in_stock`). По умолчанию: `in_transit`
- `warehouse_id` — фильтр по складу
- `shipping_location` — фильтр по месту отправки
- `search` — поиск по названию, производителю или месту отправки
- `sort` — поле для сортировки (по умолчанию: `created_at`)
- `order` — направление сортировки (`asc`, `desc`)
- `per_page` — количество записей на странице (по умолчанию: 15)

**Пример ответа:**
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "product_template_id": 1,
      "warehouse_id": 1,
      "name": "Доска: 100 x 50 x 25",
      "status": "in_transit",
      "quantity": 50,
      "calculated_volume": 6.25,
      "producer_id": 1,
      "shipping_location": "Москва",
      "shipping_date": "2025-09-25",
      "transport_number": "А123БВ77",
      "expected_arrival_date": "2025-09-28",
      "notes": "Срочная доставка",
      "document_path": ["documents/ttn_001.pdf"],
      "attributes": {
        "length": 100,
        "width": 50,
        "height": 25
      },
      "is_active": true,
      "created_by": 2,
      "warehouse": {
        "id": 1,
        "name": "Центральный склад"
      },
      "template": {
        "id": 1,
        "name": "Доска"
      },
      "creator": {
        "id": 2,
        "name": "Иван Иванов"
      }
    }
  ],
  "pagination": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 15,
    "total": 25
  }
}
```

### Создание товара в пути
```http
POST /api/receipts
Content-Type: application/json

{
  "warehouse_id": 1,
  "shipping_location": "Москва",
  "shipping_date": "2025-01-10",
  "transport_number": "А123БВ77",
  "expected_arrival_date": "2025-01-15",
  "notes": "Срочная доставка",
  "document_path": ["documents/ttn_001.pdf"],
  "products": [
    {
      "product_template_id": 1,
      "quantity": 10,
      "producer": "ООО Производитель",
      "name": "Доска специальная",
      "description": "Доска для строительства",
      "attributes": {
        "length": 100,
        "width": 50,
        "height": 25
      }
    }
  ]
}
```

**Альтернативный формат (один товар):**
```http
POST /api/receipts
Content-Type: application/json

{
  "warehouse_id": 1,
  "product_template_id": 1,
  "quantity": 10,
  "shipping_location": "Москва",
  "transport_number": "А123БВ77",
  "expected_arrival_date": "2025-01-15"
}
```

### Получить товар в пути
```http
GET /api/receipts/{id}
```

### Принять товар (перевести в остатки)
```http
POST /api/receipts/{id}/receive
```

**Ответ:**
```json
{
  "success": true,
  "message": "Товар принят",
  "data": {
    "id": 123,
    "status": "in_stock",
    "arrival_date": "2025-01-15"
  }
}
```

## 🏪 Склады (Warehouses)

### Список складов
```http
GET /api/warehouses?company_id=1&is_active=true
```

### Создание склада
```http
POST /api/warehouses
Content-Type: application/json

{
  "name": "Основной склад",
  "address": "ул. Складская, 1",
  "company_id": 1
}
```

## 🏢 Компании (Companies)

### Список компаний
```http
GET /api/companies?is_active=true&search=название
```

### Архивирование компании
```http
POST /api/companies/{id}/archive
```

**Ответ:**
```json
{
  "success": true,
  "message": "Компания успешно архивирована",
  "data": {
    "id": 1,
    "name": "ООО Компания",
    "is_archived": true,
    "archived_at": "2025-09-09T10:00:00.000000Z"
  }
}
```

## 💰 Продажи (Sales)

### Создание продажи
```http
POST /api/sales
Content-Type: application/json

{
  "product_id": 1,
  "warehouse_id": 1,
  "customer_name": "Иван Иванов",
  "customer_phone": "+7 (999) 123-45-67",
  "customer_email": "ivan@example.com",
  "quantity": 5,
  "unit_price": 1000.00,
  "vat_rate": 20.00,
  "payment_method": "cash",
  "sale_date": "2025-09-09"
}
```

### Обработка продажи
```http
POST /api/sales/{id}/process
```

### Отмена продажи
```http
POST /api/sales/{id}/cancel
```

## 📋 Запросы (Requests)

### Создание запроса
```http
POST /api/requests
Content-Type: application/json

{
  "warehouse_id": 1,
  "product_template_id": 1,
  "title": "Запрос на пополнение кирпича",
  "quantity": 1000,
  "priority": "high",
  "description": "Необходимо пополнить склад"
}
```

## 👥 Пользователи (Users)

### Список пользователей
```http
GET /api/users?role=admin&company_id=1
```

### Блокировка пользователя
```http
POST /api/users/{id}/block
```

## 🏗️ Шаблоны товаров (Product Templates)

### Список шаблонов
```http
GET /api/product-templates?is_active=true
```

### Тестирование формулы
```http
POST /api/product-templates/{id}/test-formula
Content-Type: application/json

{
  "values": {
    "length": 10,
    "width": 5,
    "height": 2
  }
}
```

## 📈 Остатки (Stocks)

### Агрегированные остатки
```http
GET /api/stocks?warehouse_id=1&in_stock=true&low_stock=true
```

**Ответ:**
```json
{
  "data": [
    {
      "id": "template_1_warehouse_1",
      "product_template_id": 1,
      "warehouse_id": 1,
      "producer": "ООО Кирпичный завод",
      "name": "Кирпич красный М-150",
      "available_quantity": 950,
      "available_volume": 2.375,
      "items_count": 1,
      "first_arrival": "2025-09-08",
      "last_arrival": "2025-09-08",
      "template": {
        "id": 1,
        "name": "Кирпич"
      },
      "warehouse": {
        "id": 1,
        "name": "Основной склад"
      }
    }
  ]
}
```

## 🔍 Фильтрация и поиск

### Общие параметры фильтрации:
- `page` - номер страницы
- `per_page` - количество записей на странице
- `search` - текстовый поиск
- `sort` - поле для сортировки
- `order` - направление сортировки (asc/desc)

### Специфичные фильтры:
- **Товары**: `warehouse_id`, `template_id`, `producer`, `in_stock`, `low_stock`, `active`, `has_correction`
- **Продажи**: `warehouse_id`, `payment_status`, `delivery_status`, `payment_method`, `date_from`, `date_to`
- **Запросы**: `status`, `priority`, `user_id`, `warehouse_id`, `product_template_id`
- **Пользователи**: `role`, `company_id`, `warehouse_id`, `is_blocked`
- **Компании**: `is_active`, `is_archived`
- **Склады**: `company_id`, `is_active`

## ⚠️ Коды ошибок

### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

### 403 Forbidden
```json
{
  "message": "This action is unauthorized."
}
```

### 404 Not Found
```json
{
  "message": "No query results for model [App\\Models\\Product] 999"
}
```

### 422 Validation Error
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

### 500 Internal Server Error
```json
{
  "message": "Server Error"
}
```

## 📝 Особенности системы

### Роли пользователей:
- `admin` - полный доступ ко всем функциям системы
- `operator` - работа с товарами, остатками и товарами в пути
- `warehouse_worker` - доступ к запросам, остатками, товарами в пути, продажами и приемке
- `sales_manager` - работа с запросами, остатками и товарами в пути

### Система прав доступа:
- **Администраторы** видят все данные системы
- **Не-администраторы** видят только данные своего склада
- Некоторые операции требуют специальных ролей (например, приемка только для `warehouse_worker`)
- Архивирование вместо удаления для критичных данных (компании)

### Ключевые возможности:
- ✅ **Система товаров в пути** - полный цикл от создания до приемки
- ✅ **Гибкие шаблоны товаров** с формулами расчета объема
- ✅ **Система уточнений** для товаров с проблемами
- ✅ **Управление документами** - прикрепление файлов к товарам
- ✅ **Продажи с автоматическим списанием** товаров
- ✅ **Агрегированные остатки** по складам
- ✅ **Статистика и аналитика** по всем разделам
- ✅ **Многоуровневая фильтрация** и поиск

### Безопасность:
- Регистрация отключена в продакшн окружении
- Все новые пользователи создаются с ролью `warehouse_worker`
- Создание пользователей с другими ролями только через админа
- Блокировка пользователей без удаления данных

## 🏭 Производители (Producers)

### Список производителей
```http
GET /api/producers
```

### Создание производителя
```http
POST /api/producers
Content-Type: application/json

{
  "name": "ООО Производитель",
  "region": "Московская область"
}
```

### Обновление производителя
```http
PUT /api/producers/{id}
Content-Type: application/json

{
  "name": "ООО Новый производитель",
  "region": "Ленинградская область"
}
```

## 📈 Остатки (Stocks)

### Получить остатки
```http
GET /api/stocks?warehouse_id=1&per_page=20
```

**Параметры:**
- `warehouse_id` - фильтр по складу
- `per_page` - количество записей
- `in_stock` - только товары в наличии
- `low_stock` - товары с низким остатком

## 📊 Дашборд

### Сводная информация
```http
GET /api/dashboard/summary
```

**Ответ:**
```json
{
  "companies_active": 5,
  "employees_active": 25,
  "warehouses_active": 8,
  "products_total": 1500,
  "products_in_transit": 120,
  "requests_pending": 15,
  "latest_sales": [...]
}
```

### Выручка по валютам
```http
GET /api/dashboard/revenue?period=month
```

**Параметры:**
- `period` - период (`day`, `week`, `month`, `custom`)
- `date_from` - дата начала (для custom)
- `date_to` - дата окончания (для custom)

## 🔄 Расхождения (Discrepancies)

### Список расхождений
```http
GET /api/discrepancies?per_page=15
```

### Создание расхождения
```http
POST /api/discrepancies
Content-Type: application/json

{
  "product_id": 1,
  "expected_quantity": 100,
  "actual_quantity": 95,
  "description": "Недостача 5 единиц",
  "type": "shortage"
}
```

## 🔗 Полезные ссылки

- **OpenAPI спецификация**: `/openapi.yaml`
- **Postman коллекция**: `/postman.json`
- **Swagger UI**: `/docs.html` (если настроен)

## 📋 Примеры использования

### Полный цикл работы с товаром в пути:

1. **Создание товара в пути**:
```bash
curl -X POST http://localhost/api/receipts \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "warehouse_id": 1,
    "product_template_id": 1,
    "quantity": 100,
    "shipping_location": "Москва",
    "transport_number": "А123БВ77"
  }'
```

2. **Получение списка товаров в пути**:
```bash
curl -X GET "http://localhost/api/receipts?status=in_transit" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

3. **Приемка товара**:
```bash
curl -X POST http://localhost/api/receipts/1/receive \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Создание продажи:

```bash
curl -X POST http://localhost/api/sales \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": 1,
    "warehouse_id": 1,
    "customer_name": "Иван Покупатель",
    "quantity": 5,
    "unit_price": 1000,
    "payment_method": "cash",
    "sale_date": "2025-01-15"
  }'
```

## 🚨 Важные замечания

### Ограничения доступа:
- Пользователи с ролью `warehouse_worker` могут работать только с приемкой (`/api/receipts`)
- Не-администраторы видят только данные своего склада
- Регистрация через API доступна только в dev окружении

### Особенности работы с товарами:
- При создании товара автоматически генерируется название на основе шаблона и атрибутов
- Объем рассчитывается автоматически по формуле шаблона
- Товары в пути автоматически переводятся в остатки при приемке

### Обработка ошибок:
- Всегда проверяйте код ответа HTTP
- В случае ошибки 422 проверьте поле `errors` в ответе
- Для отладки используйте заголовок `Accept: application/json`

## 📞 Поддержка

Для получения помощи по API:
- Изучите OpenAPI спецификацию в `/openapi.yaml`
- Используйте Postman коллекцию из `/postman.json`
- Обращайтесь к разработчикам системы