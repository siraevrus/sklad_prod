# API документация: Компании (Companies)

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

## 1. ОСНОВНЫЕ ОПЕРАЦИИ С КОМПАНИЯМИ

### 1.1 Получение списка компаний

**Endpoint:** `GET /api/companies`

**Описание:** Получение списка всех компаний с фильтрацией и пагинацией

**Query параметры:**
```
is_active    - Фильтр по активности (boolean, true = не архивные)
search       - Поиск по названию, email или ИНН
sort         - Поле для сортировки (по умолчанию: created_at)
order        - Порядок сортировки (asc/desc, по умолчанию: desc)
per_page     - Количество на странице (по умолчанию: 15)
page         - Номер страницы
```

**Пример запроса:**
```bash
GET /api/companies?is_active=true&search=ООО&per_page=10&page=1
```

**Ответ:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "ООО \"Строительная компания\"",
      "legal_address": "г. Москва, ул. Строителей, д. 1",
      "postal_address": "г. Москва, ул. Строителей, д. 1",
      "phone_fax": "+7 (495) 123-45-67",
      "general_director": "Иванов Иван Иванович",
      "email": "info@stroycompany.ru",
      "inn": "1234567890",
      "kpp": "123456789",
      "ogrn": "1234567890123",
      "bank": "ПАО \"Сбербанк\"",
      "account_number": "40702810123456789012",
      "correspondent_account": "30101810400000000225",
      "bik": "044525225",
      "employees_count": 25,
      "warehouses_count": 3,
      "is_archived": false,
      "archived_at": null,
      "created_at": "2024-01-15T10:00:00.000000Z",
      "updated_at": "2024-01-15T10:00:00.000000Z"
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

### 1.2 Получение компании по ID

**Endpoint:** `GET /api/companies/{id}`

**Описание:** Получение детальной информации о компании

**Пример запроса:**
```bash
GET /api/companies/1
```

**Ответ:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "ООО \"Строительная компания\"",
    "legal_address": "г. Москва, ул. Строителей, д. 1",
    "postal_address": "г. Москва, ул. Строителей, д. 1",
    "phone_fax": "+7 (495) 123-45-67",
    "general_director": "Иванов Иван Иванович",
    "email": "info@stroycompany.ru",
    "inn": "1234567890",
    "kpp": "123456789",
    "ogrn": "1234567890123",
    "bank": "ПАО \"Сбербанк\"",
    "account_number": "40702810123456789012",
    "correspondent_account": "30101810400000000225",
    "bik": "044525225",
    "employees_count": 25,
    "warehouses_count": 3,
    "is_archived": false,
    "archived_at": null,
    "created_at": "2024-01-15T10:00:00.000000Z",
    "updated_at": "2024-01-15T10:00:00.000000Z"
  }
}
```

**Ответ при ошибке (компания архивирована):**
```json
{
  "success": false,
  "message": "Компания не найдена"
}
```

### 1.3 Создание компании

**Endpoint:** `POST /api/companies`

**Описание:** Создание новой компании

**Тело запроса:**
```json
{
  "name": "ООО \"Новая компания\"",
  "legal_address": "г. Москва, ул. Новая, д. 1",
  "postal_address": "г. Москва, ул. Новая, д. 1",
  "phone_fax": "+7 (495) 987-65-43",
  "general_director": "Петров Петр Петрович",
  "email": "info@newcompany.ru",
  "inn": "9876543210",
  "kpp": "987654321",
  "ogrn": "9876543210987",
  "bank": "ПАО \"ВТБ\"",
  "account_number": "40702810987654321098",
  "correspondent_account": "30101810700000000777",
  "bik": "044525777",
  "employees_count": 0,
  "warehouses_count": 0
}
```

**Обязательные поля:**
- `name` - Название компании

**Необязательные поля:**
- `legal_address` - Юридический адрес
- `postal_address` - Почтовый адрес
- `phone_fax` - Телефон/факс
- `general_director` - Генеральный директор
- `email` - Email
- `inn` - ИНН (уникальный)
- `kpp` - КПП
- `ogrn` - ОГРН
- `bank` - Банк
- `account_number` - Расчетный счет
- `correspondent_account` - Корреспондентский счет
- `bik` - БИК
- `employees_count` - Количество сотрудников (по умолчанию: 0)
- `warehouses_count` - Количество складов (по умолчанию: 0)

**Ответ:**
```json
{
  "success": true,
  "message": "Компания успешно создана",
  "data": {
    "id": 2,
    "name": "ООО \"Новая компания\"",
    "legal_address": "г. Москва, ул. Новая, д. 1",
    "postal_address": "г. Москва, ул. Новая, д. 1",
    "phone_fax": "+7 (495) 987-65-43",
    "general_director": "Петров Петр Петрович",
    "email": "info@newcompany.ru",
    "inn": "9876543210",
    "kpp": "987654321",
    "ogrn": "9876543210987",
    "bank": "ПАО \"ВТБ\"",
    "account_number": "40702810987654321098",
    "correspondent_account": "30101810700000000777",
    "bik": "044525777",
    "employees_count": 0,
    "warehouses_count": 0,
    "is_archived": false,
    "archived_at": null,
    "created_at": "2024-01-15T11:00:00.000000Z",
    "updated_at": "2024-01-15T11:00:00.000000Z"
  }
}
```

### 1.4 Обновление компании

**Endpoint:** `PUT /api/companies/{id}`

**Описание:** Обновление существующей компании

**Тело запроса:**
```json
{
  "name": "ООО \"Обновленная компания\"",
  "legal_address": "г. Москва, ул. Обновленная, д. 2",
  "email": "new@updatedcompany.ru",
  "employees_count": 30
}
```

**Ответ:**
```json
{
  "success": true,
  "message": "Компания успешно обновлена",
  "data": {
    "id": 2,
    "name": "ООО \"Обновленная компания\"",
    "legal_address": "г. Москва, ул. Обновленная, д. 2",
    "postal_address": "г. Москва, ул. Новая, д. 1",
    "phone_fax": "+7 (495) 987-65-43",
    "general_director": "Петров Петр Петрович",
    "email": "new@updatedcompany.ru",
    "inn": "9876543210",
    "kpp": "987654321",
    "ogrn": "9876543210987",
    "bank": "ПАО \"ВТБ\"",
    "account_number": "40702810987654321098",
    "correspondent_account": "30101810700000000777",
    "bik": "044525777",
    "employees_count": 30,
    "warehouses_count": 0,
    "is_archived": false,
    "archived_at": null,
    "created_at": "2024-01-15T11:00:00.000000Z",
    "updated_at": "2024-01-15T12:00:00.000000Z"
  }
}
```

### 1.5 Удаление компании

**Endpoint:** `DELETE /api/companies/{id}`

**Описание:** Удаление компании (архивирование)

**Пример запроса:**
```bash
DELETE /api/companies/2
```

**Ответ при успехе:**
```json
{
  "success": true,
  "message": "Компания успешно удалена"
}
```

**Ответ при ошибке (есть связанные записи):**
```json
{
  "success": false,
  "message": "Нельзя удалить компанию с привязанными складами или сотрудниками. Архивируйте или удалите связанные записи.",
  "details": {
    "warehouses_count": 3,
    "employees_count": 25,
    "suggestion": "Используйте POST /api/companies/2/archive для архивирования"
  }
}
```

---

## 2. АРХИВИРОВАНИЕ И ВОССТАНОВЛЕНИЕ

### 2.1 Архивирование компании

**Endpoint:** `POST /api/companies/{id}/archive`

**Описание:** Архивирование компании (мягкое удаление)

**Пример запроса:**
```bash
POST /api/companies/2/archive
```

**Ответ:**
```json
{
  "success": true,
  "message": "Компания успешно архивирована",
  "data": {
    "id": 2,
    "name": "ООО \"Обновленная компания\"",
    "legal_address": "г. Москва, ул. Обновленная, д. 2",
    "postal_address": "г. Москва, ул. Новая, д. 1",
    "phone_fax": "+7 (495) 987-65-43",
    "general_director": "Петров Петр Петрович",
    "email": "new@updatedcompany.ru",
    "inn": "9876543210",
    "kpp": "987654321",
    "ogrn": "9876543210987",
    "bank": "ПАО \"ВТБ\"",
    "account_number": "40702810987654321098",
    "correspondent_account": "30101810700000000777",
    "bik": "044525777",
    "employees_count": 30,
    "warehouses_count": 0,
    "is_archived": true,
    "archived_at": "2024-01-15T13:00:00.000000Z",
    "created_at": "2024-01-15T11:00:00.000000Z",
    "updated_at": "2024-01-15T13:00:00.000000Z"
  }
}
```

**Ответ при ошибке (уже архивирована):**
```json
{
  "success": false,
  "message": "Компания уже архивирована"
}
```

### 2.2 Восстановление компании

**Endpoint:** `POST /api/companies/{id}/restore`

**Описание:** Восстановление компании из архива

**Пример запроса:**
```bash
POST /api/companies/2/restore
```

**Ответ:**
```json
{
  "success": true,
  "message": "Компания успешно восстановлена",
  "data": {
    "id": 2,
    "name": "ООО \"Обновленная компания\"",
    "legal_address": "г. Москва, ул. Обновленная, д. 2",
    "postal_address": "г. Москва, ул. Новая, д. 1",
    "phone_fax": "+7 (495) 987-65-43",
    "general_director": "Петров Петр Петрович",
    "email": "new@updatedcompany.ru",
    "inn": "9876543210",
    "kpp": "987654321",
    "ogrn": "9876543210987",
    "bank": "ПАО \"ВТБ\"",
    "account_number": "40702810987654321098",
    "correspondent_account": "30101810700000000777",
    "bik": "044525777",
    "employees_count": 30,
    "warehouses_count": 0,
    "is_archived": false,
    "archived_at": null,
    "created_at": "2024-01-15T11:00:00.000000Z",
    "updated_at": "2024-01-15T14:00:00.000000Z"
  }
}
```

**Ответ при ошибке (не архивирована):**
```json
{
  "success": false,
  "message": "Компания не архивирована"
}
```

---

## 3. РАБОТА СО СКЛАДАМИ КОМПАНИИ

### 3.1 Получение складов компании

**Endpoint:** `GET /api/companies/{id}/warehouses`

**Описание:** Получение списка складов конкретной компании

**Query параметры:**
```
is_active    - Фильтр по активности складов (boolean)
search       - Поиск по названию склада
sort         - Поле для сортировки (по умолчанию: name)
order        - Порядок сортировки (asc/desc, по умолчанию: asc)
per_page     - Количество на странице (по умолчанию: 15)
page         - Номер страницы
```

**Пример запроса:**
```bash
GET /api/companies/1/warehouses?is_active=true&per_page=10
```

**Ответ:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "company_id": 1,
      "name": "Основной склад",
      "address": "г. Москва, ул. Складская, д. 1",
      "phone": "+7 (495) 111-22-33",
      "manager": "Сидоров Сидор Сидорович",
      "is_active": true,
      "created_at": "2024-01-15T10:00:00.000000Z",
      "updated_at": "2024-01-15T10:00:00.000000Z"
    },
    {
      "id": 2,
      "company_id": 1,
      "name": "Дополнительный склад",
      "address": "г. Москва, ул. Складская, д. 2",
      "phone": "+7 (495) 222-33-44",
      "manager": "Козлов Козел Козлович",
      "is_active": true,
      "created_at": "2024-01-15T10:30:00.000000Z",
      "updated_at": "2024-01-15T10:30:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 10,
    "total": 2
  }
}
```

**Ответ при ошибке (компания архивирована):**
```json
{
  "success": false,
  "message": "Компания архивирована"
}
```

---

## 4. ТИПЫ ДАННЫХ И ВАЛИДАЦИЯ

### 4.1 Структура компании

```json
{
  "id": "integer",
  "name": "string (обязательное, max:255)",
  "legal_address": "string (max:500)",
  "postal_address": "string (max:500)",
  "phone_fax": "string (max:100)",
  "general_director": "string (max:255)",
  "email": "string (email, max:255)",
  "inn": "string (max:12, уникальный)",
  "kpp": "string (max:9)",
  "ogrn": "string (max:15)",
  "bank": "string (max:255)",
  "account_number": "string (max:20)",
  "correspondent_account": "string (max:20)",
  "bik": "string (max:9)",
  "employees_count": "integer (min:0)",
  "warehouses_count": "integer (min:0)",
  "is_archived": "boolean",
  "archived_at": "datetime|null",
  "created_at": "datetime",
  "updated_at": "datetime"
}
```

### 4.2 Правила валидации

#### Создание компании:
- `name` - обязательное, строка, максимум 255 символов
- `inn` - необязательное, строка, максимум 12 символов, уникальное
- `email` - необязательное, валидный email, максимум 255 символов
- `employees_count` - необязательное, integer, минимум 0 (по умолчанию: 0)
- `warehouses_count` - необязательное, integer, минимум 0 (по умолчанию: 0)

#### Обновление компании:
- Все поля необязательные
- `inn` - уникальное, исключая текущую компанию
- `employees_count` и `warehouses_count` - не могут быть NULL

---

## 5. КОДЫ ОШИБОК

### HTTP коды ответов:
- `200` - Успешный запрос
- `201` - Компания создана
- `400` - Неверный запрос
- `401` - Не авторизован
- `403` - Доступ запрещен
- `404` - Компания не найдена
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
    "inn": [
      "The inn has already been taken."
    ],
    "email": [
      "The email must be a valid email address."
    ]
  }
}
```

**Компания не найдена (404):**
```json
{
  "success": false,
  "message": "Компания не найдена"
}
```

**Нельзя удалить (400):**
```json
{
  "success": false,
  "message": "Нельзя удалить компанию с привязанными складами или сотрудниками. Архивируйте или удалите связанные записи.",
  "details": {
    "warehouses_count": 3,
    "employees_count": 25,
    "suggestion": "Используйте POST /api/companies/1/archive для архивирования"
  }
}
```

---

## 6. ПРИМЕРЫ ИСПОЛЬЗОВАНИЯ

### 6.1 Полный цикл работы с компанией

```bash
# 1. Создаем компанию
curl -X POST "https://domain.com/api/companies" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "ООО \"Тестовая компания\"",
    "legal_address": "г. Москва, ул. Тестовая, д. 1",
    "email": "test@company.ru",
    "inn": "1234567890"
  }'

# 2. Получаем информацию о компании
curl -X GET "https://domain.com/api/companies/3" \
  -H "Authorization: Bearer {token}"

# 3. Обновляем компанию
curl -X PUT "https://domain.com/api/companies/3" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "ООО \"Обновленная тестовая компания\"",
    "employees_count": 10
  }'

# 4. Получаем склады компании
curl -X GET "https://domain.com/api/companies/3/warehouses" \
  -H "Authorization: Bearer {token}"

# 5. Архивируем компанию
curl -X POST "https://domain.com/api/companies/3/archive" \
  -H "Authorization: Bearer {token}"

# 6. Восстанавливаем компанию
curl -X POST "https://domain.com/api/companies/3/restore" \
  -H "Authorization: Bearer {token}"
```

### 6.2 Поиск и фильтрация

```bash
# Поиск активных компаний по названию
curl -X GET "https://domain.com/api/companies?is_active=true&search=ООО" \
  -H "Authorization: Bearer {token}"

# Сортировка по названию
curl -X GET "https://domain.com/api/companies?sort=name&order=asc" \
  -H "Authorization: Bearer {token}"

# Пагинация
curl -X GET "https://domain.com/api/companies?per_page=5&page=2" \
  -H "Authorization: Bearer {token}"
```

---

## 7. ОСОБЕННОСТИ АРХИВИРОВАНИЯ

### 7.1 Мягкое удаление
- Компании не удаляются физически из базы данных
- Устанавливается флаг `is_archived = true`
- Записывается дата архивирования в `archived_at`
- Архивированные компании не отображаются в обычных запросах

### 7.2 Защита от удаления
- Нельзя удалить компанию с привязанными складами
- Нельзя удалить компанию с привязанными сотрудниками
- Рекомендуется использовать архивирование вместо удаления

### 7.3 Восстановление
- Архивированные компании можно восстановить
- При восстановлении сбрасываются флаги архивирования
- Все связанные данные остаются нетронутыми

---

## 8. СВЯЗИ И ЗАВИСИМОСТИ

### 8.1 Связанные модели
- **Warehouse** - склады компании
- **User** - сотрудники компании

### 8.2 Автоматические счетчики
- `employees_count` - количество сотрудников
- `warehouses_count` - количество складов
- Счетчики обновляются автоматически при изменении связанных записей

### 8.3 Методы модели
```php
// Архивирование
$company->archive();

// Восстановление
$company->restore();

// Обновление счетчиков
$company->updateEmployeesCount();
$company->updateWarehousesCount();
$company->updateCounts();

// Получение динамических счетчиков
$company->dynamic_employees_count;
$company->dynamic_warehouses_count;
```

---

Эта документация покрывает все основные операции с компаниями, включая создание, обновление, архивирование, восстановление и работу со складами.
