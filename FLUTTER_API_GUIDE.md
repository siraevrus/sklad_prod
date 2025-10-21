# EXPERT WOOD - Flutter API Guide

Полное руководство для интеграции мобильного приложения Flutter с backend API системы складского учета.

## 📋 Оглавление

1. [Основная информация](#основная-информация)
2. [Аутентификация](#аутентификация)
3. [API Endpoints](#api-endpoints)
4. [Примеры запросов](#примеры-запросов)
5. [Обработка ошибок](#обработка-ошибок)
6. [Кэширование](#кэширование)

---

## Основная информация

### Base URL

**Production:** `https://warehouse.expwood.ru/api`  
**Local Dev:** `http://localhost:8001/api`

### Headers (для всех защищённых запросов)

```
Content-Type: application/json
Authorization: Bearer {access_token}
Accept: application/json
```

### Rate Limiting

- **Login:** 5 запросов в минуту
- **Register:** 3 запроса в минуту
- **Остальные:** без ограничений

---

## Аутентификация

### 1. Регистрация

```http
POST /auth/register
Content-Type: application/json

{
  "login": "username",
  "email": "user@example.com",
  "password": "password",
  "password_confirmation": "password"
}
```

**Response:**

```json
{
  "message": "Пользователь успешно зарегистрирован",
  "user": {
    "id": 1,
    "login": "username",
    "email": "user@example.com",
    "role": "viewer"
  },
  "access_token": "token_string_here"
}
```

### 2. Вход в систему

```http
POST /auth/login
Content-Type: application/json

{
  "login": "username",
  "password": "password"
}
```

**Response:**

```json
{
  "access_token": "token_string_here",
  "token_type": "Bearer",
  "user": {
    "id": 1,
    "login": "username",
    "email": "user@example.com",
    "role": "admin",
    "warehouse_id": 1,
    "company_id": 1
  }
}
```

### 3. Получение текущего пользователя

```http
GET /auth/me
Authorization: Bearer {access_token}
```

**Response:**

```json
{
  "id": 1,
  "login": "username",
  "email": "user@example.com",
  "role": "admin",
  "warehouse_id": 1,
  "company_id": 1
}
```

### 4. Обновление профиля

```http
PUT /auth/profile
Authorization: Bearer {access_token}
Content-Type: application/json

{
  "email": "newemail@example.com",
  "password": "newpassword"
}
```

### 5. Выход из системы

```http
POST /auth/logout
Authorization: Bearer {access_token}
```

---

## API Endpoints

### 📦 Товары (Products)

#### Получить список товаров

```http
GET /products?status=in_stock&page=1&per_page=15
Authorization: Bearer {access_token}
```

**Query Parameters:**

| Параметр | Тип | Описание |
|----------|-----|---------|
| `status` | string | `in_stock`, `in_transit`, `for_receipt` |
| `warehouse_id` | int | Фильтр по складу |
| `producer_id` | int | Фильтр по производителю |
| `search` | string | Поиск по названию |
| `page` | int | Номер страницы (по умолчанию 1) |
| `per_page` | int | Товаров на странице (по умолчанию 15) |
| `in_stock` | boolean | Только товары в наличии |

**Response:**

```json
{
  "data": [
    {
      "id": 1,
      "name": "Доска дубовая 25х100",
      "quantity": 50,
      "calculated_volume": 0.125,
      "producer_id": 1,
      "warehouse_id": 1,
      "status": "in_stock",
      "arrival_date": "2025-01-15",
      "created_at": "2025-01-15T10:30:00Z"
    }
  ],
  "links": {
    "first": "...",
    "last": "...",
    "prev": null,
    "next": "..."
  },
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 75
  }
}
```

#### Получить одного товара

```http
GET /products/{id}?include=producer
Authorization: Bearer {access_token}
```

**Query Parameters:**

| Параметр | Описание |
|----------|---------|
| `include` | Дополнительные связи: `producer`, `template`, `warehouse` (разделены запятой) |

**Response:**

```json
{
  "id": 1,
  "name": "Доска дубовая 25х100",
  "quantity": 50,
  "calculated_volume": 0.125,
  "description": "Высокого качества",
  "attributes": {
    "length": 2000,
    "width": 100,
    "height": 25
  },
  "producer": {
    "id": 1,
    "name": "EXPERT WOOD"
  },
  "warehouse": {
    "id": 1,
    "name": "Основной склад"
  },
  "status": "in_stock",
  "arrival_date": "2025-01-15",
  "created_at": "2025-01-15T10:30:00Z"
}
```

#### Статистика товаров

```http
GET /products/stats
Authorization: Bearer {access_token}
```

**Response:**

```json
{
  "total_products": 500,
  "total_quantity": 5000,
  "total_volume": 625.5,
  "by_status": {
    "in_stock": 400,
    "in_transit": 80,
    "for_receipt": 20
  }
}
```

### 💵 Продажи (Sales)

#### Получить список продаж

```http
GET /sales?status=completed&page=1
Authorization: Bearer {access_token}
```

**Response:**

```json
{
  "data": [
    {
      "id": 1,
      "product_id": 1,
      "quantity": 10,
      "total_amount": 5000,
      "currency": "RUB",
      "client_name": "ООО Компания",
      "status": "completed",
      "sale_date": "2025-01-15T14:30:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 150
  }
}
```

#### Создать продажу

```http
POST /sales
Authorization: Bearer {access_token}
Content-Type: application/json

{
  "product_id": 1,
  "quantity": 10,
  "total_amount": 5000,
  "currency": "RUB",
  "client_name": "ООО Компания"
}
```

### 📊 Остатки (Stocks)

#### Остатки по производителям

```http
GET /stocks/producers
Authorization: Bearer {access_token}
```

**Response:**

```json
{
  "data": [
    {
      "id": "producer_1",
      "name": "EXPERT WOOD",
      "total_quantity": 1000,
      "total_volume": 125.5,
      "product_count": 25
    }
  ]
}
```

#### Остатки по складам

```http
GET /stocks/warehouses
Authorization: Bearer {access_token}
```

#### Остатки по компаниям

```http
GET /stocks/companies
Authorization: Bearer {access_token}
```

### 🏭 Производители (Producers)

#### Список производителей

```http
GET /producers
Authorization: Bearer {access_token}
```

**Response:**

```json
{
  "data": [
    {
      "id": 1,
      "name": "EXPERT WOOD",
      "created_at": "2025-01-01T00:00:00Z"
    }
  ]
}
```

### 🏪 Склады (Warehouses)

#### Список складов

```http
GET /warehouses
Authorization: Bearer {access_token}
```

**Response:**

```json
{
  "data": [
    {
      "id": 1,
      "name": "Основной склад",
      "location": "г. Москва",
      "is_active": true,
      "created_at": "2025-01-01T00:00:00Z"
    }
  ]
}
```

#### Статистика склада

```http
GET /warehouses/{id}/stats
Authorization: Bearer {access_token}
```

**Response:**

```json
{
  "warehouse_id": 1,
  "name": "Основной склад",
  "total_products": 100,
  "total_quantity": 1000,
  "total_volume": 125.5,
  "employees": 5
}
```

### 📋 Шаблоны товаров (Product Templates)

#### Список шаблонов

```http
GET /product-templates
Authorization: Bearer {access_token}
```

**Response:**

```json
{
  "data": [
    {
      "id": 1,
      "name": "Доска",
      "unit": "м³",
      "formula": "length * width * height / 1000000",
      "attributes": [
        {
          "id": 1,
          "name": "Длина",
          "variable": "length",
          "type": "number",
          "unit": "мм"
        }
      ]
    }
  ]
}
```

### 📱 Инфопанель (Dashboard)

#### Сводка для мобильного приложения

```http
GET /dashboard/summary
Authorization: Bearer {access_token}
```

**Response:**

```json
{
  "total_warehouses": 3,
  "total_products": 500,
  "total_quantity": 5000,
  "total_volume": 625.5,
  "latest_sales": [
    {
      "id": 1,
      "product_name": "Доска",
      "client_name": "ООО Компания",
      "quantity": 10,
      "total_amount": 5000,
      "currency": "RUB",
      "sale_date": "2025-01-15T14:30:00Z"
    }
  ],
  "warehouse_stats": [
    {
      "id": 1,
      "name": "Основной склад",
      "total_quantity": 1000,
      "total_volume": 125.5
    }
  ]
}
```

---

## Примеры запросов

### Flutter (Dart)

#### 1. Вход в систему

```dart
import 'package:http/http.dart' as http;
import 'dart:convert';

Future<Map<String, dynamic>> login(String login, String password) async {
  final response = await http.post(
    Uri.parse('https://warehouse.expwood.ru/api/auth/login'),
    headers: {
      'Content-Type': 'application/json',
    },
    body: jsonEncode({
      'login': login,
      'password': password,
    }),
  );

  if (response.statusCode == 200) {
    return jsonDecode(response.body);
  } else {
    throw Exception('Ошибка входа: ${response.statusCode}');
  }
}
```

#### 2. Получение списка товаров

```dart
Future<List<dynamic>> getProducts(String token, {int page = 1}) async {
  final response = await http.get(
    Uri.parse('https://warehouse.expwood.ru/api/products?page=$page'),
    headers: {
      'Authorization': 'Bearer $token',
      'Accept': 'application/json',
    },
  );

  if (response.statusCode == 200) {
    final data = jsonDecode(response.body);
    return data['data'];
  } else {
    throw Exception('Ошибка загрузки товаров');
  }
}
```

#### 3. Получение товара с производителем

```dart
Future<Map<String, dynamic>> getProduct(String token, int productId) async {
  final response = await http.get(
    Uri.parse('https://warehouse.expwood.ru/api/products/$productId?include=producer'),
    headers: {
      'Authorization': 'Bearer $token',
      'Accept': 'application/json',
    },
  );

  if (response.statusCode == 200) {
    return jsonDecode(response.body);
  } else {
    throw Exception('Товар не найден');
  }
}
```

#### 4. Создание продажи

```dart
Future<Map<String, dynamic>> createSale(
  String token, {
  required int productId,
  required int quantity,
  required double totalAmount,
  required String currency,
  required String clientName,
}) async {
  final response = await http.post(
    Uri.parse('https://warehouse.expwood.ru/api/sales'),
    headers: {
      'Authorization': 'Bearer $token',
      'Content-Type': 'application/json',
    },
    body: jsonEncode({
      'product_id': productId,
      'quantity': quantity,
      'total_amount': totalAmount,
      'currency': currency,
      'client_name': clientName,
    }),
  );

  if (response.statusCode == 201) {
    return jsonDecode(response.body);
  } else {
    throw Exception('Ошибка создания продажи');
  }
}
```

#### 5. Получение инфопанели

```dart
Future<Map<String, dynamic>> getDashboard(String token) async {
  final response = await http.get(
    Uri.parse('https://warehouse.expwood.ru/api/dashboard/summary'),
    headers: {
      'Authorization': 'Bearer $token',
      'Accept': 'application/json',
    },
  );

  if (response.statusCode == 200) {
    return jsonDecode(response.body);
  } else {
    throw Exception('Ошибка загрузки данных');
  }
}
```

### cURL примеры

#### Вход в систему

```bash
curl -X POST https://warehouse.expwood.ru/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "login": "admin",
    "password": "password"
  }'
```

#### Получение товаров

```bash
curl -X GET "https://warehouse.expwood.ru/api/products?status=in_stock" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

#### Получение товара с производителем

```bash
curl -X GET "https://warehouse.expwood.ru/api/products/1?include=producer" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

---

## Обработка ошибок

### Коды ошибок

| Code | Описание |
|------|---------|
| 200 | OK - Успешный запрос |
| 201 | Created - Ресурс создан |
| 400 | Bad Request - Некорректные данные |
| 401 | Unauthorized - Требуется аутентификация |
| 403 | Forbidden - Доступ запрещён |
| 404 | Not Found - Ресурс не найден |
| 422 | Validation Error - Ошибка валидации |
| 429 | Too Many Requests - Превышен лимит запросов |
| 500 | Server Error - Ошибка сервера |

### Пример ошибки валидации

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "login": ["The login field is required."],
    "password": ["The password must be at least 6 characters."]
  }
}
```

### Пример обработки ошибок в Flutter

```dart
Future<void> handleError(dynamic error) {
  if (error is Exception) {
    if (error.toString().contains('401')) {
      // Требуется переаутентификация
      print('Сессия истекла, требуется вход');
    } else if (error.toString().contains('403')) {
      // Доступ запрещён
      print('У вас нет прав доступа');
    } else if (error.toString().contains('404')) {
      // Ресурс не найден
      print('Ресурс не найден');
    } else {
      print('Ошибка: $error');
    }
  }
}
```

---

## Кэширование

### Рекомендации

1. **Товары:** Кэшировать на 5-10 минут
2. **Остатки:** Кэшировать на 2-5 минут (часто меняется)
3. **Производители:** Кэшировать на 1 час (редко меняется)
4. **Склады:** Кэшировать на 1 час (редко меняется)
5. **Профиль пользователя:** Кэшировать на 15 минут

### Пример с HTTP кэшем

```dart
final httpClient = HttpClientWithCache();

// Кэшировать на 5 минут
httpClient.cacheTime = Duration(minutes: 5);

// Получить данные (используется кэш если доступен)
final response = await httpClient.get(
  Uri.parse('https://warehouse.expwood.ru/api/products'),
);
```

---

## Важные замечания

1. **Токен доступа** - сохраняйте в безопасном хранилище (KeyChain/Keystore)
2. **HTTPS** - используйте только для production
3. **Include параметр** - используйте для загрузки связанных данных
4. **Пагинация** - всегда проверяйте поле `meta` для получения информации о страницах
5. **Rate limiting** - обрабатывайте ошибку 429 с экспоненциальной задержкой

---

## Контакты поддержки

- **Backend API:** `/api/dashboard/summary` для проверки статуса
- **Документация:** https://warehouse.expwood.ru/api
- **Версия API:** v1
- **Последнее обновление:** 2025-01-21
