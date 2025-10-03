# API документация: Шаблоны товаров (Product Templates)

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

## 1. ОСНОВНЫЕ ОПЕРАЦИИ С ШАБЛОНАМИ

### 1.1 Получение списка шаблонов

**Endpoint:** `GET /api/product-templates`

**Описание:** Получение списка всех шаблонов товаров с фильтрацией и пагинацией

**Query параметры:**
```
is_active    - Фильтр по активности (boolean)
search       - Поиск по названию шаблона
sort         - Поле для сортировки (по умолчанию: created_at)
order        - Порядок сортировки (asc/desc, по умолчанию: desc)
per_page     - Количество на странице (по умолчанию: 15)
page         - Номер страницы
```

**Пример запроса:**
```bash
GET /api/product-templates?is_active=true&search=доска&per_page=10&page=1
```

**Ответ:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Доска",
      "description": "Строительная доска",
      "formula": "length * width * height / 1000000000",
      "unit": "м³",
      "is_active": true,
      "created_at": "2024-01-15T10:00:00.000000Z",
      "updated_at": "2024-01-15T10:00:00.000000Z",
      "attributes": [
        {
          "id": 1,
          "product_template_id": 1,
          "name": "Длина",
          "variable": "length",
          "type": "number",
          "unit": "мм",
          "is_required": true,
          "is_in_formula": true,
          "sort_order": 1,
          "options": null,
          "created_at": "2024-01-15T10:00:00.000000Z",
          "updated_at": "2024-01-15T10:00:00.000000Z"
        },
        {
          "id": 2,
          "product_template_id": 1,
          "name": "Ширина",
          "variable": "width",
          "type": "number",
          "unit": "мм",
          "is_required": true,
          "is_in_formula": true,
          "sort_order": 2,
          "options": null,
          "created_at": "2024-01-15T10:00:00.000000Z",
          "updated_at": "2024-01-15T10:00:00.000000Z"
        },
        {
          "id": 3,
          "product_template_id": 1,
          "name": "Сорт",
          "variable": "grade",
          "type": "select",
          "unit": null,
          "is_required": false,
          "is_in_formula": false,
          "sort_order": 3,
          "options": ["1 сорт", "2 сорт", "3 сорт"],
          "created_at": "2024-01-15T10:00:00.000000Z",
          "updated_at": "2024-01-15T10:00:00.000000Z"
        }
      ]
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

### 1.2 Получение шаблона по ID

**Endpoint:** `GET /api/product-templates/{id}`

**Описание:** Получение детальной информации о шаблоне товара

**Пример запроса:**
```bash
GET /api/product-templates/1
```

**Ответ:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Доска",
    "description": "Строительная доска для различных целей",
    "formula": "length * width * height / 1000000000",
    "unit": "м³",
    "is_active": true,
    "created_at": "2024-01-15T10:00:00.000000Z",
    "updated_at": "2024-01-15T10:00:00.000000Z",
    "attributes": [
      {
        "id": 1,
        "product_template_id": 1,
        "name": "Длина",
        "variable": "length",
        "type": "number",
        "unit": "мм",
        "is_required": true,
        "is_in_formula": true,
        "sort_order": 1,
        "options": null,
        "created_at": "2024-01-15T10:00:00.000000Z",
        "updated_at": "2024-01-15T10:00:00.000000Z"
      },
      {
        "id": 2,
        "product_template_id": 1,
        "name": "Ширина",
        "variable": "width",
        "type": "number",
        "unit": "мм",
        "is_required": true,
        "is_in_formula": true,
        "sort_order": 2,
        "options": null,
        "created_at": "2024-01-15T10:00:00.000000Z",
        "updated_at": "2024-01-15T10:00:00.000000Z"
      },
      {
        "id": 3,
        "product_template_id": 1,
        "name": "Высота",
        "variable": "height",
        "type": "number",
        "unit": "мм",
        "is_required": true,
        "is_in_formula": true,
        "sort_order": 3,
        "options": null,
        "created_at": "2024-01-15T10:00:00.000000Z",
        "updated_at": "2024-01-15T10:00:00.000000Z"
      },
      {
        "id": 4,
        "product_template_id": 1,
        "name": "Сорт",
        "variable": "grade",
        "type": "select",
        "unit": null,
        "is_required": false,
        "is_in_formula": false,
        "sort_order": 4,
        "options": ["1 сорт", "2 сорт", "3 сорт"],
        "created_at": "2024-01-15T10:00:00.000000Z",
        "updated_at": "2024-01-15T10:00:00.000000Z"
      }
    ],
    "products": [
      {
        "id": 1,
        "name": "Доска: 6000 x 200 x 50, 1 сорт",
        "quantity": "100.000",
        "warehouse_id": 1,
        "status": "in_stock",
        "is_active": true,
        "warehouse": {
          "id": 1,
          "name": "Основной склад"
        }
      }
    ]
  }
}
```

### 1.3 Создание шаблона

**Endpoint:** `POST /api/product-templates`

**Описание:** Создание нового шаблона товара

**Тело запроса:**
```json
{
  "name": "Профиль металлический",
  "description": "Металлический профиль для строительства",
  "formula": "length * width * height / 1000000000",
  "unit": "м³",
  "is_active": true
}
```

**Обязательные поля:**
- `name` - Название шаблона
- `unit` - Единица измерения

**Необязательные поля:**
- `description` - Описание шаблона
- `formula` - Формула для расчета объема
- `is_active` - Активность (по умолчанию: true)

**Ответ:**
```json
{
  "success": true,
  "message": "Шаблон товара успешно создан",
  "data": {
    "id": 2,
    "name": "Профиль металлический",
    "description": "Металлический профиль для строительства",
    "formula": "length * width * height / 1000000000",
    "unit": "м³",
    "is_active": true,
    "created_at": "2024-01-15T11:00:00.000000Z",
    "updated_at": "2024-01-15T11:00:00.000000Z"
  }
}
```

### 1.4 Обновление шаблона

**Endpoint:** `PUT /api/product-templates/{id}`

**Описание:** Обновление существующего шаблона товара

**Тело запроса:**
```json
{
  "name": "Профиль металлический (обновлено)",
  "description": "Обновленное описание профиля",
  "formula": "length * width * height / 1000000000",
  "unit": "м³",
  "is_active": true
}
```

**Ответ:**
```json
{
  "success": true,
  "message": "Шаблон товара успешно обновлен",
  "data": {
    "id": 2,
    "name": "Профиль металлический (обновлено)",
    "description": "Обновленное описание профиля",
    "formula": "length * width * height / 1000000000",
    "unit": "м³",
    "is_active": true,
    "created_at": "2024-01-15T11:00:00.000000Z",
    "updated_at": "2024-01-15T12:00:00.000000Z",
    "attributes": []
  }
}
```

### 1.5 Удаление шаблона

**Endpoint:** `DELETE /api/product-templates/{id}`

**Описание:** Удаление шаблона товара (только если нет связанных товаров)

**Пример запроса:**
```bash
DELETE /api/product-templates/2
```

**Ответ при успехе:**
```json
{
  "success": true,
  "message": "Шаблон товара успешно удален"
}
```

**Ответ при ошибке (есть связанные товары):**
```json
{
  "success": false,
  "message": "Нельзя удалить шаблон, который используется в товарах"
}
```

### 1.6 Активация шаблона

**Endpoint:** `POST /api/product-templates/{id}/activate`

**Описание:** Активация шаблона товара

**Пример запроса:**
```bash
POST /api/product-templates/2/activate
```

**Ответ:**
```json
{
  "success": true,
  "message": "Шаблон товара активирован",
  "data": {
    "id": 2,
    "name": "Профиль металлический",
    "description": "Металлический профиль для строительства",
    "formula": "length * width * height / 1000000000",
    "unit": "м³",
    "is_active": true,
    "created_at": "2024-01-15T11:00:00.000000Z",
    "updated_at": "2024-01-15T12:30:00.000000Z",
    "attributes": []
  }
}
```

### 1.7 Деактивация шаблона

**Endpoint:** `POST /api/product-templates/{id}/deactivate`

**Описание:** Деактивация шаблона товара

**Пример запроса:**
```bash
POST /api/product-templates/2/deactivate
```

**Ответ:**
```json
{
  "success": true,
  "message": "Шаблон товара деактивирован",
  "data": {
    "id": 2,
    "name": "Профиль металлический",
    "description": "Металлический профиль для строительства",
    "formula": "length * width * height / 1000000000",
    "unit": "м³",
    "is_active": false,
    "created_at": "2024-01-15T11:00:00.000000Z",
    "updated_at": "2024-01-15T12:35:00.000000Z",
    "attributes": []
  }
}
```

---

## 2. РАБОТА С АТРИБУТАМИ ШАБЛОНА

### 2.1 Получение атрибутов шаблона

**Endpoint:** `GET /api/product-templates/{id}/attributes`

**Описание:** Получение списка атрибутов конкретного шаблона

**Пример запроса:**
```bash
GET /api/product-templates/1/attributes
```

**Ответ:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "product_template_id": 1,
      "name": "Длина",
      "variable": "length",
      "type": "number",
      "unit": "мм",
      "is_required": true,
      "is_in_formula": true,
      "sort_order": 1,
      "options": null,
      "created_at": "2024-01-15T10:00:00.000000Z",
      "updated_at": "2024-01-15T10:00:00.000000Z"
    },
    {
      "id": 2,
      "product_template_id": 1,
      "name": "Ширина",
      "variable": "width",
      "type": "number",
      "unit": "мм",
      "is_required": true,
      "is_in_formula": true,
      "sort_order": 2,
      "options": null,
      "created_at": "2024-01-15T10:00:00.000000Z",
      "updated_at": "2024-01-15T10:00:00.000000Z"
    },
    {
      "id": 3,
      "product_template_id": 1,
      "name": "Сорт",
      "variable": "grade",
      "type": "select",
      "unit": null,
      "is_required": false,
      "is_in_formula": false,
      "sort_order": 3,
      "options": ["1 сорт", "2 сорт", "3 сорт"],
      "created_at": "2024-01-15T10:00:00.000000Z",
      "updated_at": "2024-01-15T10:00:00.000000Z"
    }
  ]
}
```

### 2.2 Добавление атрибута к шаблону

**Endpoint:** `POST /api/product-templates/{id}/attributes`

**Описание:** Добавление нового атрибута к шаблону товара

**Тело запроса:**
```json
{
  "name": "Материал",
  "variable": "material",
  "type": "select",
  "unit": null,
  "is_required": false,
  "is_in_formula": false,
  "sort_order": 4,
  "options": ["Сосна", "Ель", "Дуб", "Береза"]
}
```

**Обязательные поля:**
- `name` - Название атрибута
- `variable` - Переменная (только латинские буквы, цифры и подчеркивания)
- `type` - Тип атрибута (number, text, select)

**Необязательные поля:**
- `unit` - Единица измерения
- `is_required` - Обязательность (по умолчанию: false)
- `is_in_formula` - Участие в формуле (по умолчанию: false)
- `sort_order` - Порядок сортировки (по умолчанию: 0)
- `options` - Опции для типа select (массив строк)

**Ответ:**
```json
{
  "success": true,
  "message": "Характеристика успешно добавлена",
  "data": {
    "id": 4,
    "product_template_id": 1,
    "name": "Материал",
    "variable": "material",
    "type": "select",
    "unit": null,
    "is_required": false,
    "is_in_formula": false,
    "sort_order": 4,
    "options": ["Сосна", "Ель", "Дуб", "Береза"],
    "created_at": "2024-01-15T13:00:00.000000Z",
    "updated_at": "2024-01-15T13:00:00.000000Z"
  }
}
```

### 2.3 Обновление атрибута

**Endpoint:** `PUT /api/product-templates/{templateId}/attributes/{attributeId}`

**Описание:** Обновление существующего атрибута шаблона

**Тело запроса:**
```json
{
  "name": "Материал (обновлено)",
  "variable": "material",
  "type": "select",
  "unit": null,
  "is_required": true,
  "is_in_formula": false,
  "sort_order": 3,
  "options": ["Сосна", "Ель", "Дуб", "Береза", "Лиственница"]
}
```

**Ответ:**
```json
{
  "success": true,
  "message": "Характеристика успешно обновлена",
  "data": {
    "id": 4,
    "product_template_id": 1,
    "name": "Материал (обновлено)",
    "variable": "material",
    "type": "select",
    "unit": null,
    "is_required": true,
    "is_in_formula": false,
    "sort_order": 3,
    "options": ["Сосна", "Ель", "Дуб", "Береза", "Лиственница"],
    "created_at": "2024-01-15T13:00:00.000000Z",
    "updated_at": "2024-01-15T13:30:00.000000Z"
  }
}
```

### 2.4 Удаление атрибута

**Endpoint:** `DELETE /api/product-templates/{templateId}/attributes/{attributeId}`

**Описание:** Удаление атрибута из шаблона

**Пример запроса:**
```bash
DELETE /api/product-templates/1/attributes/4
```

**Ответ:**
```json
{
  "success": true,
  "message": "Характеристика успешно удалена"
}
```

---

## 3. ТЕСТИРОВАНИЕ ФОРМУЛ

### 3.1 Тестирование формулы шаблона

**Endpoint:** `POST /api/product-templates/{id}/test-formula`

**Описание:** Тестирование формулы шаблона с заданными значениями

**Тело запроса:**
```json
{
  "values": {
    "length": 6000,
    "width": 200,
    "height": 50,
    "quantity": 10
  }
}
```

**Ответ при успехе:**
```json
{
  "success": true,
  "data": {
    "success": true,
    "result": 0.6,
    "error": null
  }
}
```

**Ответ при ошибке:**
```json
{
  "success": false,
  "data": {
    "success": false,
    "result": null,
    "error": "Длина, Ширина"
  }
}
```

---

## 4. РАБОТА С ТОВАРАМИ ШАБЛОНА

### 4.1 Получение товаров шаблона

**Endpoint:** `GET /api/product-templates/{id}/products`

**Описание:** Получение списка товаров, созданных по конкретному шаблону

**Query параметры:**
```
is_active     - Фильтр по активности товаров (boolean)
warehouse_id  - Фильтр по складу
search        - Поиск по названию товара
sort          - Поле для сортировки (по умолчанию: created_at)
order         - Порядок сортировки (asc/desc, по умолчанию: desc)
per_page      - Количество на странице (по умолчанию: 15)
page          - Номер страницы
```

**Пример запроса:**
```bash
GET /api/product-templates/1/products?is_active=true&warehouse_id=1&per_page=10
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
      "name": "Доска: 6000 x 200 x 50, 1 сорт",
      "description": "Строительная доска",
      "attributes": {
        "length": "6000",
        "width": "200",
        "height": "50",
        "grade": "1 сорт"
      },
      "calculated_volume": "60.0000",
      "quantity": "100.000",
      "sold_quantity": 0,
      "transport_number": "AB1234CD",
      "producer_id": 1,
      "arrival_date": "2024-01-15",
      "status": "in_stock",
      "is_active": true,
      "created_at": "2024-01-15T10:00:00.000000Z",
      "updated_at": "2024-01-15T10:00:00.000000Z",
      "warehouse": {
        "id": 1,
        "name": "Основной склад"
      }
    }
  ],
  "pagination": {
    "current_page": 1,
    "last_page": 2,
    "per_page": 10,
    "total": 15
  }
}
```

---

## 5. СТАТИСТИКА И СПРАВОЧНИКИ

### 5.1 Статистика шаблонов

**Endpoint:** `GET /api/product-templates/stats`

**Описание:** Получение статистики по шаблонам товаров

**Пример запроса:**
```bash
GET /api/product-templates/stats
```

**Ответ:**
```json
{
  "success": true,
  "data": {
    "total": 25,
    "active": 20,
    "inactive": 5,
    "with_formula": 18,
    "without_formula": 7
  }
}
```

### 5.2 Доступные единицы измерения

**Endpoint:** `GET /api/product-templates/units`

**Описание:** Получение списка доступных единиц измерения

**Пример запроса:**
```bash
GET /api/product-templates/units
```

**Ответ:**
```json
{
  "success": true,
  "data": {
    "мм": "мм",
    "см": "см",
    "метр": "метр",
    "радиус": "радиус",
    "м³": "м³",
    "м²": "м²",
    "кг": "кг",
    "грамм": "грамм"
  }
}
```

---

## 6. ТИПЫ ДАННЫХ И ВАЛИДАЦИЯ

### 6.1 Типы атрибутов

- **`number`** - Числовые значения (длина, ширина, количество)
- **`text`** - Текстовые значения (описание, примечания)
- **`select`** - Значения из предопределенного списка

### 6.2 Правила валидации

#### Шаблон товара:
- `name` - обязательное, строка, максимум 255 символов
- `unit` - обязательное, строка, максимум 50 символов
- `description` - необязательное, строка
- `formula` - необязательное, строка
- `is_active` - необязательное, boolean

#### Атрибут шаблона:
- `name` - обязательное, строка, максимум 255 символов
- `variable` - обязательное, строка, максимум 100 символов, только латинские буквы, цифры и подчеркивания
- `type` - обязательное, одно из: number, text, select
- `unit` - необязательное, строка, максимум 50 символов
- `is_required` - необязательное, boolean
- `is_in_formula` - необязательное, boolean
- `sort_order` - необязательное, integer
- `options` - необязательное, массив строк (для типа select)

---

## 7. КОДЫ ОШИБОК

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
    "name": [
      "The name field is required."
    ],
    "variable": [
      "The variable format is invalid."
    ]
  }
}
```

**Ошибка удаления (400):**
```json
{
  "success": false,
  "message": "Нельзя удалить шаблон, который используется в товарах"
}
```

**Ресурс не найден (404):**
```json
{
  "success": false,
  "message": "Шаблон не найден"
}
```

---

## 8. ПРИМЕРЫ ИСПОЛЬЗОВАНИЯ

### Пример полного цикла работы с шаблоном:

1. **Создание шаблона:**
```bash
curl -X POST "https://domain.com/api/product-templates" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Труба",
    "description": "Металлическая труба",
    "formula": "3.14159 * (diameter/2) * (diameter/2) * length / 1000000000",
    "unit": "м³"
  }'
```

2. **Добавление атрибутов:**
```bash
curl -X POST "https://domain.com/api/product-templates/3/attributes" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Диаметр",
    "variable": "diameter",
    "type": "number",
    "unit": "мм",
    "is_required": true,
    "is_in_formula": true,
    "sort_order": 1
  }'
```

3. **Тестирование формулы:**
```bash
curl -X POST "https://domain.com/api/product-templates/3/test-formula" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "values": {
      "diameter": 100,
      "length": 6000
    }
  }'
```

4. **Получение шаблона с атрибутами:**
```bash
curl -X GET "https://domain.com/api/product-templates/3" \
  -H "Authorization: Bearer {token}"
```

---

## 9. ОСОБЕННОСТИ ФОРМУЛ

### Поддерживаемые операции:
- Сложение: `+`
- Вычитание: `-`
- Умножение: `*`
- Деление: `/`
- Скобки: `()`
- Числа: `123.45`

### Примеры формул:
- **Объем доски:** `length * width * height / 1000000000`
- **Площадь:** `length * width / 1000000`
- **Вес:** `volume * density`
- **Сложная формула:** `(length + width) * height / 2 * quantity`

### Переменные в формулах:
- Используются переменные из атрибутов с `is_in_formula = true`
- Автоматически добавляется переменная `quantity`
- Поддерживаются только латинские буквы, цифры и подчеркивания

---

Эта документация покрывает все основные операции с шаблонами товаров, включая создание, управление атрибутами, тестирование формул и получение связанных товаров.
