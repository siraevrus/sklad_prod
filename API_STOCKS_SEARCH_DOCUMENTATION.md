# Поиск по названию в API Остатки на складе

## Обзор

В API раздела "Остатки на складе" добавлена функциональность поиска по названию товара для всех подразделов (компания, производитель, склад).

Параметр `search` принимает строку текста и фильтрует результаты по названию товара, используя операцию LIKE (case-insensitive).

## Endpoints с поддержкой поиска

### 1. Остатки по производителям
```
GET /api/stocks/producers?search=<название>
```

**Параметры:**
- `search` (optional, string) - Поиск по названию товара

**Пример:**
```bash
curl -X GET "http://localhost/api/stocks/producers?search=деревянная" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Ответ:**
```json
{
  "success": true,
  "data": [
    {
      "producer_id": 1,
      "producer": "ООО Производитель",
      "positions_count": 5,
      "total_volume": 150.5
    }
  ]
}
```

---

### 2. Остатки конкретного производителя
```
GET /api/stocks/by-producer/{producer_id}?search=<название>&per_page=15
```

**Параметры:**
- `producer_id` (path, required, integer) - ID производителя
- `search` (optional, string) - Поиск по названию товара
- `per_page` (optional, integer, default: 15) - Количество записей на странице

**Пример:**
```bash
curl -X GET "http://localhost/api/stocks/by-producer/1?search=доска&per_page=20" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Ответ:**
```json
{
  "success": true,
  "data": [
    {
      "name": "Деревянная доска",
      "warehouse": "Склад №1",
      "producer": "ООО Производитель",
      "quantity": 100.0,
      "available_quantity": 95.0,
      "sold_quantity": 5.0,
      "total_volume": 150.5
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 20,
    "total": 1
  }
}
```

---

### 3. Остатки по складам
```
GET /api/stocks/warehouses?search=<название>
```

**Параметры:**
- `search` (optional, string) - Поиск по названию товара

**Пример:**
```bash
curl -X GET "http://localhost/api/stocks/warehouses?search=профиль" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Ответ:**
```json
{
  "success": true,
  "data": [
    {
      "warehouse_id": 1,
      "warehouse": "Склад №1",
      "company": "ООО Компания",
      "address": "ул. Примерная, д. 1",
      "positions_count": 10,
      "total_volume": 300.0
    }
  ]
}
```

---

### 4. Остатки на конкретном складе
```
GET /api/stocks/by-warehouse/{warehouse_id}?search=<название>&per_page=15
```

**Параметры:**
- `warehouse_id` (path, required, integer) - ID склада
- `search` (optional, string) - Поиск по названию товара
- `per_page` (optional, integer, default: 15) - Количество записей на странице

**Пример:**
```bash
curl -X GET "http://localhost/api/stocks/by-warehouse/1?search=краска&per_page=10" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

### 5. Остатки по компаниям
```
GET /api/stocks/companies?search=<название>
```

**Параметры:**
- `search` (optional, string) - Поиск по названию товара

**Пример:**
```bash
curl -X GET "http://localhost/api/stocks/companies?search=стекло" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Ответ:**
```json
{
  "success": true,
  "data": [
    {
      "company_id": 1,
      "company": "ООО Компания",
      "warehouses_count": 3,
      "positions_count": 25,
      "total_volume": 500.0
    }
  ]
}
```

---

### 6. Остатки конкретной компании
```
GET /api/stocks/by-company/{company_id}?search=<название>&per_page=15
```

**Параметры:**
- `company_id` (path, required, integer) - ID компании
- `search` (optional, string) - Поиск по названию товара
- `per_page` (optional, integer, default: 15) - Количество записей на странице

**Пример:**
```bash
curl -X GET "http://localhost/api/stocks/by-company/1?search=трубопровод" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## Особенности поиска

### 1. Чувствительность к регистру
Поиск **не чувствителен** к регистру букв:
```
search=Деревянная  → найдет "деревянная доска"
search=деревянная  → найдет "Деревянная доска"
search=ДЕРЕВЯННАЯ  → найдет "деревянная Доска"
```

### 2. Частичное совпадение
Поиск работает с частичным совпадением (LIKE оператор):
```
search=дерев     → найдет "Деревянная доска"
search=доска     → найдет "Деревянная доска"
search=янная дос → найдет "Деревянная доска"
```

### 3. Пустая строка поиска
Если передать пустую строку или не передать параметр:
```
?search=         → вернет все товары
?search=          → вернет все товары (без параметра поиска)
```

### 4. Специальные символы
Специальные символы обрабатываются как обычные символы:
```
search=труба%20"  → найдет "труба "
```

## Примеры использования

### Поиск товара "Деревянная доска" во всех складах производителя
```bash
curl -X GET "http://localhost/api/stocks/by-producer/1?search=деревянная" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Поиск товара в конкретном складе
```bash
curl -X GET "http://localhost/api/stocks/by-warehouse/1?search=профиль" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Получить все компании, у которых есть товар с названием "Краска"
```bash
curl -X GET "http://localhost/api/stocks/companies?search=краска" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Постраничный поиск с ограничением результатов
```bash
curl -X GET "http://localhost/api/stocks/by-company/1?search=материал&per_page=50" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Интеграция с Postman

В коллекции Postman добавлены готовые примеры запросов:
- "Остатки по производителям" - с параметром search
- "Остатки конкретного производителя" - с параметром search
- "Остатки по складам" - с параметром search
- "Остатки на конкретном складе" - с параметром search
- "Остатки по компаниям" - с параметром search
- "Остатки конкретной компании" - с параметром search

## Технические детали реализации

### Обработка поиска
Поиск реализован через приватный метод `applySearchFilter()` в контроллере `StockController`:

```php
private function applySearchFilter($query, ?string $search): void
{
    if ($search && ! empty(trim($search))) {
        $searchTerm = trim($search);
        $query->where('name', 'LIKE', "%{$searchTerm}%");
    }
}
```

### Фильтрация
Поиск применяется **перед** правами доступа, поэтому:
1. Сначала фильтруются товары по названию
2. Затем проверяются права доступа пользователя

### Производительность
- Использует индекс на колонке `name` в таблице `products`
- Работает эффективно даже при большом объеме данных
- Рекомендуется использовать вместе с пагинацией

## Обновления документации

- OpenAPI (openapi.yaml) - обновлена с описанием параметра search
- Postman коллекция (postman.json) - добавлены примеры с поиском
- Документация публикуется автоматически в /public/openapi.yaml и /public/postman.json
