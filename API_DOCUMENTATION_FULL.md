# 📚 Полная документация API системы складского учета

## 🔐 Аутентификация

### Базовый URL
```
http://93.189.230.65/api
```

### Авторизация
Все защищенные эндпоинты требуют Bearer токен в заголовке:
```
Authorization: Bearer YOUR_TOKEN
```

---

## 🔑 Аутентификация и авторизация

### 1. Регистрация
```http
POST /api/auth/register
```

**Параметры:**
```json
{
  "name": "Имя Пользователя",
  "username": "username",
  "email": "user@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "first_name": "Имя",
  "last_name": "Фамилия",
  "middle_name": "Отчество",
  "phone": "+7(999)123-45-67",
  "role": "warehouse_worker",
  "company_id": 1,
  "warehouse_id": 13
}
```

**Ответ:**
```json
{
  "message": "Пользователь зарегистрирован",
  "user": { /* данные пользователя */ },
  "token": "1|abc123..."
}
```

### 2. Вход в систему
```http
POST /api/auth/login
```

**Параметры:**
```json
{
  "username": "username",
  "password": "password123"
}
```

**Ответ:**
```json
{
  "message": "Успешный вход",
  "user": { /* данные пользователя */ },
  "token": "1|abc123..."
}
```

### 3. Выход из системы
```http
POST /api/auth/logout
```

### 4. Информация о текущем пользователе
```http
GET /api/auth/me
```

### 5. Обновление профиля
```http
PUT /api/auth/profile
```

---

## 📦 Товары

### 1. Список товаров
```http
GET /api/products
```

**Параметры фильтрации:**
- `status` — статус товара (`in_stock`, `in_transit`, `for_receipt`, `correction`)
- `warehouse_id` — фильтр по складу
- `producer_id` — фильтр по производителю
- `search` — поиск по названию
- `include` — включить связанные данные (`template,warehouse,creator,producer`)
- `aggregate` — режим агрегации (`true` для группировки по характеристикам)
- `per_page` — количество записей на странице
- `page` — номер страницы

**Режим агрегации** (`aggregate=true&status=in_stock`):
Группирует товары по шаблону, складу и производителю. Возвращает суммированные данные.

**Пример ответа (обычный режим):**
```json
{
  "data": [
    {
      "id": 268,
      "product_template_id": 38,
      "warehouse_id": 13,
      "created_by": 1,
      "name": "Ворон: 33 x 33 x 33, 2",
      "description": null,
      "quantity": "133.000",
      "calculated_volume": "238981.0500",
      "status": "in_stock",
      "is_active": true,
      "template": { /* данные шаблона */ },
      "warehouse": { /* данные склада */ },
      "creator": { /* данные создателя */ },
      "producer": { /* данные производителя */ }
    }
  ],
  "links": { /* пагинация */ },
  "meta": { /* мета-информация */ }
}
```

**Пример ответа (агрегированный режим):**
```json
{
  "data": [
    {
      "product_template_id": 1,
      "composite_product_key": "1|13|1|Шаблон товара: характеристика 1 x характеристика 2",
      "name": "Шаблон товара: характеристика 1 x характеристика 2",
      "warehouse": "Название склада",
      "producer": "Название производителя",
      "quantity": 150.0,
      "available_quantity": 150.0,
      "sold_quantity": 0.0,
      "total_volume": 12.5
    }
  ],
  "links": { /* пагинация */ },
  "meta": { /* мета-информация */ }
}
```

### 2. Получение товара по ID
```http
GET /api/products/{id}
```

### 3. Создание товара
```http
POST /api/products
```

**Обязательные поля:**
```json
{
  "product_template_id": 38,
  "warehouse_id": 13,
  "quantity": 100,
  "unit_price": 1000.50
}
```

**Дополнительные поля:**
```json
{
  "name": "Название товара",
  "description": "Описание товара",
  "attributes": {
    "length": 100,
    "width": 50,
    "height": 25
  },
  "calculated_volume": 2.5,
  "transport_number": "А123БВ77",
  "producer_id": 1,
  "arrival_date": "2025-09-30",
  "status": "in_stock",
  "shipping_location": "Москва",
  "shipping_date": "2025-09-25",
  "expected_arrival_date": "2025-09-30",
  "notes": "Примечания",
  "document_path": ["documents/file1.pdf"],
  "is_active": true
}
```

### 4. Обновление товара
```http
PUT /api/products/{id}
```

### 5. Удаление товара
```http
DELETE /api/products/{id}
```

### 6. Статистика товаров
```http
GET /api/products/stats
```

### 7. Популярные товары
```http
GET /api/products/popular
```

### 8. Экспорт товаров
```http
GET /api/products/export
```

---

## 💰 Продажи (Реализация)

> **🛡️ Защита от Race Condition:** Система автоматически генерирует уникальные номера продаж в формате `SALE-YYYYMM-NNNN`. При одновременном создании продаж система защищена от дубликатов номеров благодаря уникальному ограничению в БД и улучшенному алгоритму генерации.

### 1. Список продаж
```http
GET /api/sales
```

**Параметры фильтрации:**
- `search` — поиск по номеру продажи, имени клиента, телефону
- `warehouse_id` — фильтр по складу
- `payment_status` — статус оплаты (`pending`, `paid`, `partially_paid`, `cancelled`)
- `payment_method` — способ оплаты (`cash`, `card`, `bank_transfer`, `other`)
- `date_from` — дата от
- `date_to` — дата до
- `active` — только активные продажи

**Пример ответа:**
```json
{
  "data": [
    {
      "id": 65,
      "product_id": 268,
      "warehouse_id": 13,
      "user_id": 1,
      "sale_number": "SALE-202509-0002",
      "customer_name": "Тестовый клиент",
      "customer_phone": "+7(999)123-45-67",
      "customer_email": "test@example.com",
      "customer_address": "г. Москва, ул. Тестовая, д. 1",
      "quantity": 3,
      "unit_price": "1500.00",
      "total_price": "5400.00",
      "vat_rate": "20.00",
      "vat_amount": "900.00",
      "price_without_vat": "4500.00",
      "currency": "RUB",
      "exchange_rate": "1.0000",
      "cash_amount": "0.00",
      "nocash_amount": "0.00",
      "payment_method": "cash",
      "payment_status": "paid",
      "invoice_number": null,
      "reason_cancellation": null,
      "notes": "Тестовая продажа",
      "sale_date": "2025-09-30T00:00:00.000000Z",
      "is_active": true,
      "product": { /* данные товара */ },
      "warehouse": { /* данные склада */ },
      "user": { /* данные пользователя */ }
    }
  ],
  "links": { /* пагинация */ },
  "meta": { /* мета-информация */ }
}
```

### 2. Создание продажи
```http
POST /api/sales
```

**Обязательные поля:**
```json
{
  "composite_product_key": "1|13|1|Название товара",
  "warehouse_id": 13,
  "customer_name": "Имя клиента",
  "quantity": 5,
  "unit_price": 1000.50,
  "payment_method": "cash",
  "sale_date": "2025-09-30"
}
```

**Формат `composite_product_key`:**
- `product_template_id|warehouse_id|producer_id|name`
- Пример: `"38|13|1|Труба стальная: 100x100x1000"`

**Описание полей:**
- `composite_product_key` — составной ключ товара для группировки (обязательное)
- `warehouse_id` — ID склада (обязательное)
- `customer_name` — имя клиента (обязательное)
- `quantity` — количество товара (обязательное)
- `unit_price` — цена за единицу (обязательное)
- `payment_method` — способ оплаты (обязательное)
- `sale_date` — дата продажи (обязательное)

**Дополнительные поля:**
```json
{
  "customer_phone": "+7(999)123-45-67",
  "customer_email": "client@example.com",
  "customer_address": "г. Москва, ул. Тестовая, д. 1",
  "vat_rate": 20,
  "currency": "RUB",
  "exchange_rate": 1.0000,
  "cash_amount": 3000.00,
  "nocash_amount": 2400.00,
  "payment_status": "paid",
  "invoice_number": "INV-2025-001",
  "reason_cancellation": "Причина отмены",
  "notes": "Дополнительные заметки",
  "is_active": true
}
```

**Возможные ошибки:**

**409 Conflict - Дубликат номера продажи:**
```json
{
  "message": "Ошибка генерации номера продажи. Попробуйте еще раз.",
  "error": "duplicate_sale_number"
}
```

**400 Bad Request - Недостаточно товара:**
```json
{
  "message": "Недостаточно товара на складе"
}
```

**422 Unprocessable Entity - Ошибки валидации:**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "product_id": ["The product id field is required."],
    "quantity": ["The quantity must be at least 1."]
  }
}
```

### 3. Получение продажи по ID
```http
GET /api/sales/{id}
```

### 4. Обновление продажи
```http
PUT /api/sales/{id}
```

### 5. Удаление продажи
```http
DELETE /api/sales/{id}
```

### 6. Обработка продажи (списание товара)
```http
POST /api/sales/{id}/process
```

### 7. Отмена продажи
```http
POST /api/sales/{id}/cancel
```

### 8. Статистика продаж
```http
GET /api/sales/stats
```

**Параметры:**
- `date_from` — дата от
- `date_to` — дата до
- `payment_status` — статус оплаты

**Пример ответа:**
```json
{
  "total_sales": 1,
  "paid_sales": 1,
  "pending_payments": 0,
  "today_sales": 0,
  "month_revenue": "6003.00",
  "total_revenue": "6003.00",
  "total_quantity": "5.000",
  "average_sale": "6003.000000"
}
```

### 9. Экспорт продаж
```http
GET /api/sales/export
```

---

## 📋 Запросы

### 1. Список запросов
```http
GET /api/requests
```

### 2. Создание запроса
```http
POST /api/requests
```

### 3. Получение запроса по ID
```http
GET /api/requests/{id}
```

### 4. Обновление запроса
```http
PUT /api/requests/{id}
```

### 5. Удаление запроса
```http
DELETE /api/requests/{id}
```

### 6. Одобрение запроса
```http
POST /api/requests/{id}/approve
```

### 7. Статистика запросов
```http
GET /api/requests/stats
```

---

## 🏢 Компании

### 1. Список компаний
```http
GET /api/companies
```

### 2. Создание компании
```http
POST /api/companies
```

### 3. Получение компании по ID
```http
GET /api/companies/{id}
```

### 4. Обновление компании
```http
PUT /api/companies/{id}
```

### 5. Удаление компании
```http
DELETE /api/companies/{id}
```

### 6. Архивирование компании
```http
POST /api/companies/{id}/archive
```

### 7. Восстановление компании
```http
POST /api/companies/{id}/restore
```

### 8. Склады компании
```http
GET /api/companies/{id}/warehouses
```

---

## 🏭 Склады

### 1. Список складов
```http
GET /api/warehouses
```

### 2. Создание склада
```http
POST /api/warehouses
```

### 3. Получение склада по ID
```http
GET /api/warehouses/{id}
```

### 4. Обновление склада
```http
PUT /api/warehouses/{id}
```

### 5. Удаление склада
```http
DELETE /api/warehouses/{id}
```

### 6. Активация склада
```http
POST /api/warehouses/{id}/activate
```

### 7. Деактивация склада
```http
POST /api/warehouses/{id}/deactivate
```

### 8. Статистика склада
```http
GET /api/warehouses/{id}/stats
```

### 9. Товары склада
```http
GET /api/warehouses/{id}/products
```

### 10. Сотрудники склада
```http
GET /api/warehouses/{id}/employees
```

### 11. Общая статистика складов
```http
GET /api/warehouses/stats
```

---

## 📦 Шаблоны товаров

### 1. Список шаблонов
```http
GET /api/product-templates
```

### 2. Создание шаблона
```http
POST /api/product-templates
```

### 3. Получение шаблона по ID
```http
GET /api/product-templates/{id}
```

### 4. Обновление шаблона
```http
PUT /api/product-templates/{id}
```

### 5. Удаление шаблона
```http
DELETE /api/product-templates/{id}
```

### 6. Активация шаблона
```http
POST /api/product-templates/{id}/activate
```

### 7. Деактивация шаблона
```http
POST /api/product-templates/{id}/deactivate
```

### 8. Статистика шаблонов
```http
GET /api/product-templates/stats
```

### 9. Единицы измерения
```http
GET /api/product-templates/units
```

### 10. Атрибуты шаблона
```http
GET /api/product-templates/{id}/attributes
```

### 11. Товары шаблона
```http
GET /api/product-templates/{id}/products
```

### 12. Тестирование формулы
```http
POST /api/product-templates/{id}/test-formula
```

### 13. Добавление атрибута
```http
POST /api/product-templates/{id}/attributes
```

### 14. Обновление атрибута
```http
PUT /api/product-templates/{id}/attributes/{attribute}
```

### 15. Удаление атрибута
```http
DELETE /api/product-templates/{id}/attributes/{attribute}
```

---

## 📊 Остатки товаров

### 1. Список остатков
```http
GET /api/stocks
```

**Параметры фильтрации:**
- `warehouse_id` — фильтр по складу
- `status` — фильтр по статусу
- `per_page` — количество записей на странице

**Пример ответа:**
```json
{
  "success": true,
  "data": [
    {
      "id": "37_13_1",
      "product_template_id": 37,
      "warehouse_id": 13,
      "producer_id": 1,
      "total_quantity": "10.000",
      "total_volume": "0.0000000",
      "name": "Руслан: 22 x 22 x 22",
      "status": "in_stock",
      "is_active": true,
      "product_template": { /* данные шаблона */ },
      "warehouse": { /* данные склада */ },
      "producer": { /* данные производителя */ }
    }
  ],
  "pagination": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 3
  }
}
```

### 2. Получение остатка по ID
```http
GET /api/stocks/{id}
```

---

## 🏭 Производители

### 1. Список производителей
```http
GET /api/producers
```

### 2. Создание производителя
```http
POST /api/producers
```

### 3. Получение производителя по ID
```http
GET /api/producers/{id}
```

### 4. Обновление производителя
```http
PUT /api/producers/{id}
```

### 5. Удаление производителя
```http
DELETE /api/producers/{id}
```

---

## 📥 Приемка товаров

### 1. Список товаров в пути
```http
GET /api/receipts
```

**Параметры:**
- `status` — статус товара (`in_transit`, `for_receipt`, `in_stock`)
- `warehouse_id` — фильтр по складу
- `shipping_location` — фильтр по месту отправки
- `search` — поиск по названию, производителю или месту отправки
- `sort` — поле для сортировки
- `order` — направление сортировки (`asc`, `desc`)
- `per_page` — количество записей на странице

### 2. Создание товара в пути
```http
POST /api/receipts
```

### 3. Получение товара в пути по ID
```http
GET /api/receipts/{id}
```

### 4. Приемка товара
```http
POST /api/receipts/{id}/receive
```

### 5. Добавление уточнения
```http
POST /api/receipts/{id}/correction
```

**Параметры:**
```json
{
  "correction": "Текст уточнения (минимум 10 символов)"
}
```

---

## 👥 Пользователи

### 1. Список пользователей
```http
GET /api/users
```

### 2. Создание пользователя
```http
POST /api/users
```

### 3. Получение пользователя по ID
```http
GET /api/users/{id}
```

### 4. Обновление пользователя
```http
PUT /api/users/{id}
```

### 5. Удаление пользователя
```http
DELETE /api/users/{id}
```

### 6. Блокировка пользователя
```http
POST /api/users/{id}/block
```

### 7. Разблокировка пользователя
```http
POST /api/users/{id}/unblock
```

### 8. Профиль пользователя
```http
GET /api/users/profile
```

### 9. Обновление профиля
```http
PUT /api/users/profile
```

### 10. Статистика пользователей
```http
GET /api/users/stats
```

---

## 📈 Расхождения

### 1. Список расхождений
```http
GET /api/discrepancies
```

### 2. Создание расхождения
```http
POST /api/discrepancies
```

### 3. Получение расхождения по ID
```http
GET /api/discrepancies/{id}
```

### 4. Обновление расхождения
```http
PUT /api/discrepancies/{id}
```

### 5. Удаление расхождения
```http
DELETE /api/discrepancies/{id}
```

---

## 📊 Инфопанель

### 1. Общая сводка
```http
GET /api/dashboard/summary
```

### 2. Доходы
```http
GET /api/dashboard/revenue
```

---

## 🔒 Роли и права доступа

### Роли пользователей:
- `admin` — Полный доступ ко всем данным
- `operator` — Оператор ПК
- `warehouse_worker` — Работник склада
- `sales_manager` — Менеджер по продажам

### Права доступа:
- **Администраторы** — полный доступ ко всем данным
- **Не-администраторы** — видят только данные своего склада/компании
- **Приемка товаров** — доступ только для `warehouse_worker`

---

## 📱 Примеры использования для мобильного приложения

### 1. Аутентификация
```javascript
// Вход в систему
const login = async (username, password) => {
  const response = await fetch('/api/auth/login', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ username, password })
  });
  return response.json();
};
```

### 2. Получение товаров
```javascript
// Список товаров с фильтрацией
const getProducts = async (filters = {}) => {
  const params = new URLSearchParams(filters);
  const response = await fetch(`/api/products?${params}`, {
    headers: { 'Authorization': `Bearer ${token}` }
  });
  return response.json();
};
```

### 3. Создание продажи
```javascript
// Создание продажи
const createSale = async (saleData) => {
  const response = await fetch('/api/sales', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(saleData)
  });
  return response.json();
};
```

### 4. Получение статистики
```javascript
// Статистика продаж
const getSalesStats = async (filters = {}) => {
  const params = new URLSearchParams(filters);
  const response = await fetch(`/api/sales/stats?${params}`, {
    headers: { 'Authorization': `Bearer ${token}` }
  });
  return response.json();
};
```

---

## ⚠️ Обработка ошибок

### Стандартные коды ответов:
- `200` — Успешный запрос
- `201` — Ресурс создан
- `400` — Неверный запрос
- `401` — Не авторизован
- `403` — Доступ запрещен
- `404` — Ресурс не найден
- `422` — Ошибка валидации
- `500` — Внутренняя ошибка сервера

### Формат ошибок:
```json
{
  "message": "Описание ошибки",
  "errors": {
    "field_name": ["Сообщение об ошибке"]
  }
}
```

---

## 🔄 Пагинация

### Стандартный формат пагинации:
```json
{
  "data": [/* массив данных */],
  "links": {
    "first": "http://api.example.com/endpoint?page=1",
    "last": "http://api.example.com/endpoint?page=10",
    "prev": null,
    "next": "http://api.example.com/endpoint?page=2"
  },
  "meta": {
    "current_page": 1,
    "last_page": 10,
    "per_page": 15,
    "total": 150
  }
}
```

---

## 📝 Примечания

1. **Все даты** передаются в формате ISO 8601: `2025-09-30T00:00:00.000000Z`
2. **Все суммы** передаются как строки с точностью до 2 знаков после запятой
3. **Токены** имеют ограниченный срок действия
4. **Запросы** ограничены по частоте (rate limiting)
5. **Все эндпоинты** возвращают JSON

---

**Документация актуальна на: 30 сентября 2025 года** 📅
