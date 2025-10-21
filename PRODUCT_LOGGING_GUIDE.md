# 📊 Руководство по детальному логированию при добавлении товара

## 🎯 Обзор

Добавлено **комплексное логирование** для отслеживания каждого шага процесса создания и редактирования товара. Каждый процесс имеет уникальный `log_id` для полного отслеживания цепочки операций.

---

## 📍 СОЗДАНИЕ ТОВАРА (CreateProduct)

### STEP 1: START PRODUCT CREATION
```
Логирует: Начало процесса, пользователя, склад
Уровень: INFO
Ключ: log_id (для отслеживания)
```

**Пример лога:**
```json
{
  "log_id": "product_create_670b0e1d12345.67890abc",
  "timestamp": "2025-10-21T14:33:45.123456Z",
  "user_id": 1,
  "warehouse_id": 1
}
```

### STEP 2: SET CREATED_BY
```
Логирует: Установка пользователя создателя
Уровень: INFO
```

### STEP 3: WAREHOUSE_ID HANDLING
```
Логирует: Проверка и установка склада для обычных пользователей
Уровень: INFO
Условие: Проверяет, админ ли пользователь
```

### STEP 4: EXTRACT ATTRIBUTES
```
Логирует: Извлечение характеристик из полей формы
Уровень: INFO (основное), DEBUG (детали каждого атрибута)

Детали:
- Всего полей в форме
- Количество извлеченных атрибутов
- Каждый атрибут отдельным DEBUG логом
- Пропущенные null-атрибуты
```

**Пример:**
```json
{
  "log_id": "...",
  "total_form_fields": 15,
  "extracted_count": 3,
  "attributes": {
    "length": 2000,
    "width": 100,
    "height": 25
  }
}
```

### STEP 5: REMOVE TEMPORARY FIELDS
```
Логирует: Удаление временных полей attribute_X
Уровень: INFO
Отслеживает: Количество удаленных полей
```

### STEP 6: ENSURE ATTRIBUTES FIELD
```
Логирует: Гарантирует, что поле attributes существует
Уровень: INFO
Проверяет: Установлено ли поле, пусто ли оно
```

### STEP 7: BASIC PRODUCT INFO
```
Логирует: Основную информацию о товаре
Уровень: INFO

Содержит:
- product_template_id
- name
- quantity
- is_active
- producer_id
- arrival_date
```

### STEP 8: TEMPLATE LOADING
```
Логирует: Загрузку шаблона товара
Уровень: INFO (основное), WARNING (если не найден)

Содержит:
- ID шаблона
- Найден ли шаблон
- Название шаблона
- Есть ли формула
- Саму формулу
```

**Пример успеха:**
```json
{
  "log_id": "...",
  "template_id": 1,
  "template_found": true,
  "template_name": "Доска",
  "has_formula": true,
  "formula": "length * width * height / 1000000"
}
```

**Пример ошибки:**
```json
{
  "log_id": "...",
  "template_id": null,
  "message": "WARNING: No template_id provided"
}
```

### STEP 9: FORMULA PROCESSING

#### 9.1: Prepare formula attributes
```
Логирует: Подготовку атрибутов для формулы
Уровень: INFO (основное), DEBUG (детали)

Содержит:
- formula_attributes (с quantity если задан)
```

#### 9.2: Build product name
```
Логирует: Построение названия товара
Уровень: INFO

Содержит:
- generated_name (итоговое название)
- formula_parts (части для формулы)
- regular_parts (обычные части)
```

**Пример:**
```json
{
  "log_id": "...",
  "generated_name": "Доска: 2000 x 100 x 25",
  "formula_parts": ["2000", "100", "25"],
  "regular_parts": []
}
```

#### 9.3: Call testFormula()
```
Логирует: Вызов метода вычисления объема
Уровень: INFO

Содержит:
- Саму формулу
- Атрибуты для подстановки
- Результат (success, result, error)
```

#### 9.4: Process test result
```
Логирует: Результат вычисления
Уровень: INFO (успех), WARNING (ошибка)

SUCCESS:
{
  "log_id": "...",
  "message": "✓ Volume calculated successfully",
  "calculated_volume": 5.0,
  "type": "double"
}

FAILURE:
{
  "log_id": "...",
  "message": "✗ Volume calculation FAILED",
  "error": "Missing variables: width, height",
  "formula_attributes": {...}
}
```

### STEP 10: FINAL DATA BEFORE CREATE
```
Логирует: Финальные данные перед созданием
Уровень: INFO

Содержит все важные поля товара:
- product_template_id
- name
- quantity
- calculated_volume
- attributes
- producer_id
- warehouse_id
- created_by
- is_active
```

### STEP 11: READY FOR SAVE
```
Логирует: Готовность к сохранению
Уровень: INFO

Содержит:
- Время обработки (в миллисекундах)
- Временную метку
- log_id для сопоставления
```

---

## 📍 РЕДАКТИРОВАНИЕ ТОВАРА (EditProduct)

Похож на CreateProduct, но с 9 шагами вместо 11:

### STEP 1: START PRODUCT EDITING
```
Логирует: Начало редактирования
Уровень: INFO
Содержит: ID товара, пользователя
```

### STEP 2-7: Аналогично CreateProduct
```
- STEP 2: Extract attributes
- STEP 3: Remove temporary fields
- STEP 4: Ensure attributes field
- STEP 5: Basic product info
- STEP 6: Template loading
- STEP 7: Formula processing (с подшагами 7.1-7.4)
```

### STEP 8: FINAL DATA BEFORE SAVE
```
Логирует: Финальные данные перед обновлением
Уровень: INFO
```

### STEP 9: READY FOR SAVE
```
Логирует: Готовность к сохранению
Уровень: INFO
Содержит: Время обработки, ID товара
```

---

## 📊 АНАЛИЗ ЛОГОВ

### Как отследить полный процесс

1. **По log_id в файле логов:**
```bash
# Просмотреть все логи для конкретного товара
grep "product_create_670b0e1d12345" storage/logs/laravel.log

# Или с вывод только важных логов
grep "product_create_670b0e1d12345" storage/logs/laravel.log | grep "STEP"
```

2. **Просмотреть лог Laravel:**
```bash
# Последние 100 строк
tail -100 storage/logs/laravel.log

# Следить в real-time
tail -f storage/logs/laravel.log
```

### Структура лога (JSON Format)

```
[2025-10-21 14:33:45] production.INFO: === STEP 1: START PRODUCT CREATION === 
{
  "log_id": "product_create_670b0e1d12345.67890abc",
  "timestamp": "2025-10-21T14:33:45.123456Z",
  "user_id": 1,
  "warehouse_id": 1
}
```

---

## 🔍 ОТЛАДКА ПРОБЛЕМ

### Проблема: Calculated_volume не рассчитывается

**Что смотреть в логах:**

1. STEP 8 - загружен ли шаблон?
```json
{
  "template_found": false  // ❌ Шаблон не найден!
}
```

2. STEP 9 - почему пропущена обработка?
```json
{
  "reason": {
    "has_template": false,  // ❌ Нет шаблона
    "has_formula": false,   // ❌ Нет формулы
    "has_attributes": false // ❌ Нет атрибутов
  }
}
```

3. STEP 9.3-9.4 - результат testFormula():
```json
{
  "success": false,  // ❌ Расчет не удался
  "error": "Missing variables: height",  // Какой переменной не хватает
  "formula_attributes": {...}  // Какие атрибуты были отправлены
}
```

### Проблема: Неверное название товара

**Что смотреть в логах:**

- STEP 9.2 - как построилось название?
```json
{
  "generated_name": "Доска: 2000 x 100 x 25",
  "formula_parts": ["2000", "100", "25"],
  "regular_parts": []
}
```

### Проблема: Долгая обработка

**Что смотреть в логах:**

- STEP 11 - время обработки:
```json
{
  "processing_time_ms": 245.67  // Слишком долго?
}
```

---

## 📈 ПРИМЕРЫ ПОЛНОГО ЛОГИРОВАНИЯ

### Успешное создание товара

```
=== STEP 1: START PRODUCT CREATION ===
{log_id: product_create_1, timestamp: ..., user_id: 1}

STEP 2: Set created_by
{log_id: product_create_1, created_by: 1}

STEP 3: Warehouse_id handling
{log_id: product_create_1, warehouse_id_already_set: true, user_is_admin: false}

STEP 4: Starting attribute extraction
{log_id: product_create_1, total_form_fields: 15}

  ✓ Extracted attribute
  {log_id: product_create_1, field_key: attribute_length, attribute_name: length, value: 2000}
  
  ✓ Extracted attribute
  {log_id: product_create_1, field_key: attribute_width, attribute_name: width, value: 100}
  
  ✓ Extracted attribute
  {log_id: product_create_1, field_key: attribute_height, attribute_name: height, value: 25}

STEP 4: Attributes extracted
{log_id: product_create_1, extracted_count: 3, attributes: {length: 2000, width: 100, height: 25}}

STEP 5: Removed temporary fields
{log_id: product_create_1, removed_count: 3}

STEP 6: Ensured attributes field
{log_id: product_create_1, attributes_set: true, attributes_empty: false}

STEP 7: Basic product info
{log_id: product_create_1, product_template_id: 1, name: null, quantity: 100, ...}

STEP 8: Template loaded
{log_id: product_create_1, template_id: 1, template_found: true, template_name: "Доска", has_formula: true, formula: "length * width * height / 1000000"}

STEP 9: Starting formula processing
{log_id: product_create_1, formula: "length * width * height / 1000000", attributes_count: 3, attributes: {...}}

  9.1: Formula attributes prepared
  {log_id: product_create_1, formula_attributes: {length: 2000, width: 100, height: 25, quantity: 100}}

  9.2: Building product name
  {log_id: product_create_1, template_attributes_count: 3}
  
    ✓ Added to formula parts
    {log_id: product_create_1, variable: length, value: 2000}
    
    ✓ Added to formula parts
    {log_id: product_create_1, variable: width, value: 100}
    
    ✓ Added to formula parts
    {log_id: product_create_1, variable: height, value: 25}

  9.2: Product name generated
  {log_id: product_create_1, generated_name: "Доска: 2000 x 100 x 25", formula_parts: ["2000", "100", "25"], regular_parts: []}

  9.3: Calling testFormula()
  {log_id: product_create_1, formula: "length * width * height / 1000000", formula_attributes: {...}}

  9.3: testFormula() result
  {log_id: product_create_1, success: true, result: 5.0, error: null}

  9.4: ✓ Volume calculated successfully
  {log_id: product_create_1, calculated_volume: 5.0, type: "double"}

STEP 10: Final data before create
{log_id: product_create_1, data: {product_template_id: 1, name: "Доска: 2000 x 100 x 25", quantity: 100, calculated_volume: 5.0, ...}}

=== STEP 11: READY FOR SAVE ===
{log_id: product_create_1, processing_time_ms: 123.45, timestamp: ..., product_id: null}
```

### Ошибка: Не хватает переменной

```
...
STEP 8: Template loaded
{..., formula: "length * width * height / 1000000"}

STEP 9: Starting formula processing
{..., attributes: {length: 2000, height: 25}}  // ❌ Нет width!

  9.1: Formula attributes prepared
  {...}

  9.3: Calling testFormula()
  {...}

  9.3: testFormula() result
  {success: false, error: "Missing variables: width", ...}  // ❌ ОШИБКА!

  9.4: ✗ Volume calculation FAILED
  {error: "Missing variables: width", formula_attributes: {...}}

STEP 9: Skipped formula processing
{reason: {has_template: true, has_formula: true, has_attributes: false}}
```

---

## 🔧 УРОВНИ ЛОГИРОВАНИЯ

| Уровень | Использование | Пример |
|---------|---|---|
| **DEBUG** | Детальная информация | Каждый извлеченный атрибут |
| **INFO** | Основные шаги процесса | STEP 1, STEP 2, ... STEP 11 |
| **WARNING** | Потенциальные проблемы | Шаблон не найден, расчет не удался |
| **ERROR** | Критические ошибки | Не используется в текущей логике |

---

## 📊 ПРОИЗВОДИТЕЛЬНОСТЬ

### Отслеживаемое время

```json
{
  "processing_time_ms": 123.45
}
```

**Что считается нормой:**
- Быстро: < 50 ms
- Нормально: 50-200 ms
- Медленно: > 200 ms

**Если медленно, смотрите:**
1. Размер attributes (много ли полей?)
2. Сложность формулы (много операций?)
3. Количество атрибутов шаблона (много ли их?)

---

## 🛠️ КАК ИСПОЛЬЗОВАТЬ ДЛЯ ОТЛАДКИ

### 1. Создать товар и получить log_id

```
Смотрите STEP 1 в логах, скопируйте log_id:
product_create_670b0e1d12345.67890abc
```

### 2. Отследить весь процесс

```bash
grep "product_create_670b0e1d12345" storage/logs/laravel.log
```

### 3. Анализировать каждый шаг

```bash
# Только шаги
grep "STEP" storage/logs/laravel.log | grep "product_create_670b0e1d12345"

# Только ошибки
grep "WARNING\|ERROR" storage/logs/laravel.log | grep "product_create_670b0e1d12345"

# Результаты testFormula
grep "testFormula" storage/logs/laravel.log | grep "product_create_670b0e1d12345"
```

---

## 📝 ФАЙЛЫ, ГДЕ ДОБАВЛЕНО ЛОГИРОВАНИЕ

| Файл | Шагов | Описание |
|------|-------|---------|
| CreateProduct.php | 11 | Создание нового товара |
| EditProduct.php | 9 | Редактирование товара |

---

**Дата:** October 21, 2025  
**Версия:** 1.0  
**Статус:** ✅ Полная интеграция

