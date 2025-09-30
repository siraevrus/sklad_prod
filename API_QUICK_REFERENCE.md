# 🚀 Быстрая справка по API

## 🔗 Базовый URL
```
http://93.189.230.65/api
```

## 🔐 Аутентификация
```bash
Authorization: Bearer YOUR_TOKEN
```

---

## 📋 Основные эндпоинты

### 🔑 Аутентификация
| Метод | URL | Описание |
|-------|-----|----------|
| POST | `/auth/login` | Вход в систему |
| POST | `/auth/register` | Регистрация |
| POST | `/auth/logout` | Выход |
| GET | `/auth/me` | Текущий пользователь |
| PUT | `/auth/profile` | Обновление профиля |

### 📦 Товары
| Метод | URL | Описание |
|-------|-----|----------|
| GET | `/products` | Список товаров |
| GET | `/products/{id}` | Товар по ID |
| POST | `/products` | Создание товара |
| PUT | `/products/{id}` | Обновление товара |
| DELETE | `/products/{id}` | Удаление товара |
| GET | `/products/stats` | Статистика товаров |
| GET | `/products/export` | Экспорт товаров |

### 💰 Продажи
| Метод | URL | Описание |
|-------|-----|----------|
| GET | `/sales` | Список продаж |
| GET | `/sales/{id}` | Продажа по ID |
| POST | `/sales` | Создание продажи |
| PUT | `/sales/{id}` | Обновление продажи |
| DELETE | `/sales/{id}` | Удаление продажи |
| POST | `/sales/{id}/process` | Обработка продажи |
| POST | `/sales/{id}/cancel` | Отмена продажи |
| GET | `/sales/stats` | Статистика продаж |
| GET | `/sales/export` | Экспорт продаж |

### 🏢 Компании
| Метод | URL | Описание |
|-------|-----|----------|
| GET | `/companies` | Список компаний |
| GET | `/companies/{id}` | Компания по ID |
| POST | `/companies` | Создание компании |
| PUT | `/companies/{id}` | Обновление компании |
| DELETE | `/companies/{id}` | Удаление компании |
| POST | `/companies/{id}/archive` | Архивирование |
| POST | `/companies/{id}/restore` | Восстановление |

### 🏭 Склады
| Метод | URL | Описание |
|-------|-----|----------|
| GET | `/warehouses` | Список складов |
| GET | `/warehouses/{id}` | Склад по ID |
| POST | `/warehouses` | Создание склада |
| PUT | `/warehouses/{id}` | Обновление склада |
| DELETE | `/warehouses/{id}` | Удаление склада |
| GET | `/warehouses/{id}/stats` | Статистика склада |
| GET | `/warehouses/{id}/products` | Товары склада |

### 📊 Остатки
| Метод | URL | Описание |
|-------|-----|----------|
| GET | `/stocks` | Список остатков |
| GET | `/stocks/{id}` | Остаток по ID |

### 🏭 Производители
| Метод | URL | Описание |
|-------|-----|----------|
| GET | `/producers` | Список производителей |
| GET | `/producers/{id}` | Производитель по ID |
| POST | `/producers` | Создание производителя |
| PUT | `/producers/{id}` | Обновление производителя |
| DELETE | `/producers/{id}` | Удаление производителя |

### 📥 Приемка
| Метод | URL | Описание |
|-------|-----|----------|
| GET | `/receipts` | Товары в пути |
| POST | `/receipts` | Создание товара в пути |
| GET | `/receipts/{id}` | Товар в пути по ID |
| POST | `/receipts/{id}/receive` | Приемка товара |
| POST | `/receipts/{id}/correction` | Добавление уточнения |

---

## 📝 Примеры запросов

### 1. Вход в систему
```bash
curl -X POST -H "Content-Type: application/json" \
  -d '{"username": "admin", "password": "password"}' \
  "http://93.189.230.65/api/auth/login"
```

### 2. Получение товаров
```bash
curl -H "Authorization: Bearer TOKEN" \
  "http://93.189.230.65/api/products?status=in_stock&per_page=10"
```

### 3. Создание продажи
```bash
curl -X POST -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": 268,
    "warehouse_id": 13,
    "customer_name": "Клиент",
    "quantity": 5,
    "unit_price": 1000,
    "payment_method": "cash",
    "sale_date": "2025-09-30"
  }' \
  "http://93.189.230.65/api/sales"
```

### 4. Получение статистики
```bash
curl -H "Authorization: Bearer TOKEN" \
  "http://93.189.230.65/api/sales/stats"
```

---

## 🔍 Фильтрация и поиск

### Общие параметры:
- `search` — текстовый поиск
- `per_page` — количество записей на странице (по умолчанию 15)
- `page` — номер страницы
- `sort` — поле для сортировки
- `order` — направление сортировки (`asc`, `desc`)

### Специфичные фильтры:

#### Товары:
- `status` — статус товара
- `warehouse_id` — склад
- `producer_id` — производитель
- `include` — связанные данные

#### Продажи:
- `warehouse_id` — склад
- `payment_status` — статус оплаты
- `payment_method` — способ оплаты
- `date_from` — дата от
- `date_to` — дата до

#### Остатки:
- `warehouse_id` — склад
- `status` — статус

---

## 📊 Статусы и коды

### Статусы товаров:
- `in_stock` — на складе
- `in_transit` — в пути
- `for_receipt` — готов к приемке
- `correction` — уточнение

### Статусы оплаты:
- `pending` — ожидает оплаты
- `paid` — оплачено
- `partially_paid` — частично оплачено
- `cancelled` — отменено

### Способы оплаты:
- `cash` — наличные
- `card` — карта
- `bank_transfer` — банковский перевод
- `other` — другое

### Роли пользователей:
- `admin` — администратор
- `operator` — оператор
- `warehouse_worker` — работник склада
- `sales_manager` — менеджер по продажам

---

## ⚠️ Коды ошибок

| Код | Описание |
|-----|----------|
| 200 | Успешно |
| 201 | Создано |
| 400 | Неверный запрос |
| 401 | Не авторизован |
| 403 | Доступ запрещен |
| 404 | Не найдено |
| 422 | Ошибка валидации |
| 500 | Ошибка сервера |

---

## 🔄 Формат ответов

### Успешный ответ:
```json
{
  "data": [/* массив данных */],
  "links": {/* пагинация */},
  "meta": {/* мета-информация */}
}
```

### Ошибка:
```json
{
  "message": "Описание ошибки",
  "errors": {
    "field": ["Сообщение об ошибке"]
  }
}
```

---

## 📱 Мобильное приложение

### Основные сценарии:
1. **Аутентификация** → `/auth/login`
2. **Получение товаров** → `/products`
3. **Создание продажи** → `/sales`
4. **Обработка продажи** → `/sales/{id}/process`
5. **Статистика** → `/sales/stats`

### Кэширование:
- Токены сохранять в secure storage
- Данные кэшировать локально
- Обновлять при изменении статуса

---

**Версия API: 1.0** | **Обновлено: 30.09.2025** 📅
