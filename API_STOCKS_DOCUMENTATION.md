# API Документация: Остатки на складе

## Обзор

API для работы с остатками товаров на складе с поддержкой агрегации по производителям, складам и компаниям.

**Base URL:** `http://93.189.230.65/api`

**Аутентификация:** Bearer Token (Sanctum)

---

## 📊 Endpoints для агрегации

### 1. Агрегация по производителям

#### Получить список производителей с агрегацией

**Endpoint:** `GET /api/stocks/producers`

**Описание:** Возвращает список всех производителей с агрегированными данными по остаткам.

**Пример запроса:**
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  "http://93.189.230.65/api/stocks/producers"
```

**Пример ответа:**
```json
{
  "success": true,
  "data": [
    {
      "producer_id": 5,
      "producer": "Производитель А",
      "positions_count": 15,
      "total_volume": 125.450
    },
    {
      "producer_id": 8,
      "producer": "Производитель Б",
      "positions_count": 23,
      "total_volume": 345.780
    }
  ]
}
```

**Карточка производителя содержит:**
- `producer_id` - ID производителя
- `producer` - Наименование производителя
- `positions_count` - Количество позиций (уникальных наименований товаров на всех складах)
- `total_volume` - Общий объем (м³) суммарно по всем позициям

---

#### Детальная информация по производителю

**Endpoint:** `GET /api/stocks/by-producer/{producer_id}`

**Параметры:**
- `producer_id` - ID производителя
- `per_page` (опционально) - Количество записей на странице (по умолчанию: 15)

**Описание:** Возвращает детальную информацию по всем товарам конкретного производителя с группировкой по шаблону, складу и производителю.

**Пример запроса:**
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  "http://93.189.230.65/api/stocks/by-producer/5?per_page=15"
```

**Пример ответа:**
```json
{
  "success": true,
  "data": [
    {
      "name": "Доска обрезная: 50 x 150 x 6000",
      "warehouse": "Склад №1",
      "producer": "Производитель А",
      "quantity": 150.5,
      "available_quantity": 120.3,
      "sold_quantity": 30.2,
      "total_volume": 67.500
    },
    {
      "name": "Доска обрезная: 40 x 100 x 4000",
      "warehouse": "Склад №2",
      "producer": "Производитель А",
      "quantity": 85.0,
      "available_quantity": 85.0,
      "sold_quantity": 0.0,
      "total_volume": 34.000
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 2
  }
}
```

**Поля детальной информации:**
- `name` - Наименование товара
- `warehouse` - Склад
- `producer` - Производитель
- `quantity` - Количество (суммированное)
- `available_quantity` - Доступно для продажи (суммированное)
- `sold_quantity` - Продано (суммированное)
- `total_volume` - Объем м³ (суммированное)

---

### 2. Агрегация по складам

#### Получить список складов с агрегацией

**Endpoint:** `GET /api/stocks/warehouses`

**Описание:** Возвращает список всех складов с агрегированными данными по остаткам.

**Пример запроса:**
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  "http://93.189.230.65/api/stocks/warehouses"
```

**Пример ответа:**
```json
{
  "success": true,
  "data": [
    {
      "warehouse_id": 20,
      "warehouse": "Склад №1",
      "company": "OOO EXPERT WOOD",
      "address": "г. Москва, ул. Складская, д. 1",
      "positions_count": 45,
      "total_volume": 567.890
    },
    {
      "warehouse_id": 21,
      "warehouse": "Склад №2",
      "company": "OOO EXPERT WOOD",
      "address": "г. Санкт-Петербург, пр. Невский, д. 10",
      "positions_count": 32,
      "total_volume": 234.560
    }
  ]
}
```

**Карточка склада содержит:**
- `warehouse_id` - ID склада
- `warehouse` - Наименование склада
- `company` - Наименование компании, к которой относится склад
- `address` - Адрес склада
- `positions_count` - Количество позиций (уникальных наименований товаров на этом складе)
- `total_volume` - Общий объем (м³) суммарно по всем позициям на этом складе

---

#### Детальная информация по складу

**Endpoint:** `GET /api/stocks/by-warehouse/{warehouse_id}`

**Параметры:**
- `warehouse_id` - ID склада
- `per_page` (опционально) - Количество записей на странице (по умолчанию: 15)

**Описание:** Возвращает детальную информацию по всем товарам конкретного склада с группировкой.

**Пример запроса:**
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  "http://93.189.230.65/api/stocks/by-warehouse/20?per_page=15"
```

**Пример ответа:**
```json
{
  "success": true,
  "data": [
    {
      "name": "Доска обрезная: 50 x 150 x 6000",
      "warehouse": "Склад №1",
      "producer": "Производитель А",
      "quantity": 150.5,
      "available_quantity": 120.3,
      "sold_quantity": 30.2,
      "total_volume": 67.500
    },
    {
      "name": "Брус: 100 x 100 x 6000",
      "warehouse": "Склад №1",
      "producer": "Производитель Б",
      "quantity": 200.0,
      "available_quantity": 180.0,
      "sold_quantity": 20.0,
      "total_volume": 120.000
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 15,
    "total": 45
  }
}
```

---

### 3. Агрегация по компаниям

#### Получить список компаний с агрегацией

**Endpoint:** `GET /api/stocks/companies`

**Описание:** Возвращает список всех компаний с агрегированными данными по остаткам на всех складах компании.

**Пример запроса:**
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  "http://93.189.230.65/api/stocks/companies"
```

**Пример ответа:**
```json
{
  "success": true,
  "data": [
    {
      "company_id": 13,
      "company": "OOO EXPERT WOOD",
      "warehouses_count": 3,
      "positions_count": 125,
      "total_volume": 1250.450
    },
    {
      "company_id": 14,
      "company": "OOO TIMBER LLC",
      "warehouses_count": 2,
      "positions_count": 87,
      "total_volume": 890.120
    }
  ]
}
```

**Карточка компании содержит:**
- `company_id` - ID компании
- `company` - Название компании
- `warehouses_count` - Количество складов
- `positions_count` - Количество позиций (уникальных наименований товаров на всех складах этой компании)
- `total_volume` - Общий объем (м³) суммарно по всем позициям на всех складах этой компании

---

#### Детальная информация по компании

**Endpoint:** `GET /api/stocks/by-company/{company_id}`

**Параметры:**
- `company_id` - ID компании
- `per_page` (опционально) - Количество записей на странице (по умолчанию: 15)

**Описание:** Возвращает детальную информацию по всем товарам на всех складах конкретной компании с группировкой.

**Пример запроса:**
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  "http://93.189.230.65/api/stocks/by-company/13?per_page=15"
```

**Пример ответа:**
```json
{
  "success": true,
  "data": [
    {
      "name": "Доска обрезная: 50 x 150 x 6000",
      "warehouse": "Склад №1",
      "producer": "Производитель А",
      "quantity": 150.5,
      "available_quantity": 120.3,
      "sold_quantity": 30.2,
      "total_volume": 67.500
    },
    {
      "name": "Доска обрезная: 50 x 150 x 6000",
      "warehouse": "Склад №2",
      "producer": "Производитель А",
      "quantity": 85.0,
      "available_quantity": 75.0,
      "sold_quantity": 10.0,
      "total_volume": 38.250
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 9,
    "per_page": 15,
    "total": 125
  }
}
```

---

## 📝 Общая структура ответа

### Карточки (списки)

Все endpoints для списков (`/producers`, `/warehouses`, `/companies`) возвращают:

```json
{
  "success": true,
  "data": [/* массив карточек */]
}
```

### Детальная информация (при открытии карточки)

Все endpoints для деталей (`/by-producer/{id}`, `/by-warehouse/{id}`, `/by-company/{id}`) возвращают:

```json
{
  "success": true,
  "data": [/* массив товаров с агрегацией */],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 75
  }
}
```

---

## 🔒 Права доступа

- **Администратор:** Видит все данные по всем складам и компаниям
- **Не администратор:** Видит только данные своего склада
- Все endpoints требуют аутентификации через `Authorization: Bearer TOKEN`

---

## 🎯 Примеры использования

### Пример 1: Получить карточки всех производителей
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  "http://93.189.230.65/api/stocks/producers"
```

### Пример 2: Открыть детали производителя #5
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  "http://93.189.230.65/api/stocks/by-producer/5?per_page=20"
```

### Пример 3: Получить карточки всех складов
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  "http://93.189.230.65/api/stocks/warehouses"
```

### Пример 4: Открыть детали склада #20
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  "http://93.189.230.65/api/stocks/by-warehouse/20?per_page=15"
```

### Пример 5: Получить карточки всех компаний
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  "http://93.189.230.65/api/stocks/companies"
```

### Пример 6: Открыть детали компании #13
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  "http://93.189.230.65/api/stocks/by-company/13?per_page=15"
```

---

## 📊 Сводная таблица endpoints

| Метод | Endpoint | Описание |
|-------|----------|----------|
| GET | `/api/stocks/producers` | Список производителей (карточки) |
| GET | `/api/stocks/by-producer/{producer_id}` | Детали производителя |
| GET | `/api/stocks/warehouses` | Список складов (карточки) |
| GET | `/api/stocks/by-warehouse/{warehouse_id}` | Детали склада |
| GET | `/api/stocks/companies` | Список компаний (карточки) |
| GET | `/api/stocks/by-company/{company_id}` | Детали компании |

---

## 🔑 Поля в детальной информации

При открытии любой карточки (производитель/склад/компания) возвращаются товары с полями:

| Поле | Тип | Описание |
|------|-----|----------|
| `name` | string | Наименование товара |
| `warehouse` | string | Название склада |
| `producer` | string | Название производителя |
| `quantity` | float | Общее количество (суммированное) |
| `available_quantity` | float | Доступно для продажи (суммированное: quantity - sold_quantity) |
| `sold_quantity` | float | Продано (суммированное) |
| `total_volume` | float | Объем в м³ (суммированное) |

---

## ⚠️ Особенности

1. **Агрегация на уровне БД:** Все суммирования происходят через SQL GROUP BY и SUM()
2. **Группировка:** Товары группируются по шаблону, складу и производителю
3. **Фильтрация:** Показываются только товары со статусом `in_stock` и `is_active = true`
4. **Округление:** Объем округляется до 3 знаков после запятой
5. **Пагинация:** Поддерживается пагинация для детальных endpoints

---

## 🧪 Тестирование

### Postman Collection

Импортируйте следующий JSON в Postman для тестирования:

```json
{
  "info": {
    "name": "Stocks API",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "item": [
    {
      "name": "Get Producers",
      "request": {
        "method": "GET",
        "header": [
          {
            "key": "Authorization",
            "value": "Bearer {{token}}"
          }
        ],
        "url": {
          "raw": "{{base_url}}/api/stocks/producers",
          "host": ["{{base_url}}"],
          "path": ["api", "stocks", "producers"]
        }
      }
    },
    {
      "name": "Get Producer Details",
      "request": {
        "method": "GET",
        "header": [
          {
            "key": "Authorization",
            "value": "Bearer {{token}}"
          }
        ],
        "url": {
          "raw": "{{base_url}}/api/stocks/by-producer/5?per_page=15",
          "host": ["{{base_url}}"],
          "path": ["api", "stocks", "by-producer", "5"],
          "query": [
            {
              "key": "per_page",
              "value": "15"
            }
          ]
        }
      }
    },
    {
      "name": "Get Warehouses",
      "request": {
        "method": "GET",
        "header": [
          {
            "key": "Authorization",
            "value": "Bearer {{token}}"
          }
        ],
        "url": {
          "raw": "{{base_url}}/api/stocks/warehouses",
          "host": ["{{base_url}}"],
          "path": ["api", "stocks", "warehouses"]
        }
      }
    },
    {
      "name": "Get Warehouse Details",
      "request": {
        "method": "GET",
        "header": [
          {
            "key": "Authorization",
            "value": "Bearer {{token}}"
          }
        ],
        "url": {
          "raw": "{{base_url}}/api/stocks/by-warehouse/20?per_page=15",
          "host": ["{{base_url}}"],
          "path": ["api", "stocks", "by-warehouse", "20"],
          "query": [
            {
              "key": "per_page",
              "value": "15"
            }
          ]
        }
      }
    },
    {
      "name": "Get Companies",
      "request": {
        "method": "GET",
        "header": [
          {
            "key": "Authorization",
            "value": "Bearer {{token}}"
          }
        ],
        "url": {
          "raw": "{{base_url}}/api/stocks/companies",
          "host": ["{{base_url}}"],
          "path": ["api", "stocks", "companies"]
        }
      }
    },
    {
      "name": "Get Company Details",
      "request": {
        "method": "GET",
        "header": [
          {
            "key": "Authorization",
            "value": "Bearer {{token}}"
          }
        ],
        "url": {
          "raw": "{{base_url}}/api/stocks/by-company/13?per_page=15",
          "host": ["{{base_url}}"],
          "path": ["api", "stocks", "by-company", "13"],
          "query": [
            {
              "key": "per_page",
              "value": "15"
            }
          ]
        }
      }
    }
  ],
  "variable": [
    {
      "key": "base_url",
      "value": "http://93.189.230.65"
    },
    {
      "key": "token",
      "value": "YOUR_TOKEN_HERE"
    }
  ]
}
```

---

## 📌 Версия API

**Версия:** 1.0  
**Дата создания:** 10.10.2025  
**Последнее обновление:** 10.10.2025

