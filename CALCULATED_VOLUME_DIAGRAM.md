# 📊 Диаграмма заполнения calculated_volume

## 🎯 Простая схема процесса

```
┌─────────────────────────────────────────────────────────────────┐
│                    ФОРМА СОЗДАНИЯ ТОВАРА                         │
│                    (ProductResource.php)                          │
└─────────────────────────────────────────────────────────────────┘
                              ↓
                    ┌─────────────────┐
                    │ Выбираете       │
                    │ Шаблон (№1)     │
                    └─────────────────┘
                              ↓
                    ┌─────────────────┐
                    │ afterStateUpdated│ (строка 123)
                    │ - Генерируются  │
                    │   поля атрибутов│
                    └─────────────────┘
                              ↓
        ┌─────────────────────────────────────┐
        │ ПОЛЯ АТРИБУТОВ ГЕНЕРИРУЮТСЯ         │
        │ ─────────────────────────────────── │
        │ attribute_length    [TextInput]      │
        │ attribute_width     [TextInput]      │
        │ attribute_height    [TextInput]      │
        │ calculated_volume   [TextInput, disabled] │
        └─────────────────────────────────────┘
                              ↓
            ┌──────────────────────────────┐
            │ ВЫ ВВОДИТЕ ЗНАЧЕНИЯ          │
            │ length: 2000                 │
            │ width: 100                   │
            │ height: 25                   │
            └──────────────────────────────┘
                              ↓
                    ┌─────────────────┐
                    │ live onBlur     │ (строка 268)
                    │ afterStateUpdated│
                    └─────────────────┘
                              ↓
        ┌─────────────────────────────────────────────┐
        │ СОБИРАЮТСЯ NUMERIC АТРИБУТЫ                 │
        │ {                                           │
        │   "length": 2000,                           │
        │   "width": 100,                             │
        │   "height": 25                              │
        │ }                                           │
        └─────────────────────────────────────────────┘
                              ↓
        ┌─────────────────────────────────────────────┐
        │ ВЫЗЫВАЕТСЯ testFormula()                    │
        │ (ProductTemplate.php, строка 68)           │
        │                                             │
        │ Входные данные:                             │
        │   Формула: "length * width * height / 1M"  │
        │   Атрибуты: {length:2000, width:100, ...}  │
        └─────────────────────────────────────────────┘
                              ↓
        ┌─────────────────────────────────────────────┐
        │ ВНУТРИ testFormula():                       │
        │ 1. Извлечь переменные: [length, width, h] │
        │ 2. Проверить все значения заполнены       │
        │ 3. Заменить: "2000 * 100 * 25 / 1M"       │
        │ 4. Вычислить результат:                   │
        │    5000000 / 1000000 = 5                   │
        │ 5. Вернуть: {success:true, result:5}      │
        └─────────────────────────────────────────────┘
                              ↓
        ┌─────────────────────────────────────────────┐
        │ РЕЗУЛЬТАТ ПОДСТАВЛЯЕТСЯ В ФОРМУ            │
        │                                             │
        │ calculated_volume: 5.0000                  │
        │ (отображается в disabled поле)             │
        └─────────────────────────────────────────────┘
                              ↓
            ┌──────────────────────────────┐
            │ ВЫ НАЖИМАЕТЕ СОХРАНИТЬ       │
            └──────────────────────────────┘
                              ↓
        ┌─────────────────────────────────────────────┐
        │ CreateProduct::                             │
        │ mutateFormDataBeforeCreate()                │
        │ (CreateProduct.php, строка 17)             │
        └─────────────────────────────────────────────┘
                              ↓
        ┌─────────────────────────────────────────────┐
        │ ФИНАЛЬНЫЙ РАСЧЕТ И ПОДГОТОВКА ДАННЫХ       │
        │ 1. Собрать атрибуты из attribute_X         │
        │ 2. Вызвать testFormula() ещё раз           │
        │ 3. Установить data['calculated_volume'] = 5│
        │ 4. Сформировать название товара            │
        └─────────────────────────────────────────────┘
                              ↓
        ┌─────────────────────────────────────────────┐
        │ СОХРАНЕНИЕ В БД                             │
        │                                             │
        │ INSERT INTO products (                      │
        │   product_template_id: 1,                   │
        │   name: "Доска: 2000 x 100 x 25",         │
        │   attributes: {                            │
        │     "length": 2000,                        │
        │     "width": 100,                          │
        │     "height": 25                           │
        │   },                                        │
        │   calculated_volume: 5.0000,  ← СОХРАНЕНО  │
        │   quantity: 100                            │
        │ )                                           │
        └─────────────────────────────────────────────┘
```

---

## 🔄 Полный процесс с кодом

### Шаг 1: Выбор шаблона (ProductResource.php:123)
```php
Select::make('product_template_id')
    ->live(onBlur: true)
    ->afterStateUpdated(function (Set $set, Get $get) {
        // Загружаем атрибуты шаблона
        $template = ProductTemplate::find($get('product_template_id'));
        
        // Генерируем поля для каждого атрибута
        foreach ($template->attributes as $attribute) {
            $set("attribute_{$attribute->variable}", null);
        }
        
        // Устанавливаем сообщение
        $set('calculated_volume', 'Заполните характеристики...');
    })
```

### Шаг 2: Заполнение атрибутов (ProductResource.php:268)
```php
TextInput::make($fieldName) // например: attribute_length
    ->live(onBlur: true)
    ->afterStateUpdated(function (Set $set, Get $get) use ($template) {
        // Собираем атрибуты
        $attributes = [];
        foreach ($get() as $key => $value) {
            if (str_starts_with($key, 'attribute_') && $value !== null) {
                $attributeName = str_replace('attribute_', '', $key);
                $attributes[$attributeName] = $value;
            }
        }
        
        // Вычисляем объем
        $testResult = $template->testFormula($attributes);
        
        if ($testResult['success']) {
            $set('calculated_volume', $testResult['result']); // 5.0000
        }
    })
```

### Шаг 3: Создание товара (CreateProduct.php:59)
```php
protected function mutateFormDataBeforeCreate(array $data): array
{
    // Обрабатываем атрибуты
    $attributes = [];
    foreach ($data as $key => $value) {
        if (str_starts_with($key, 'attribute_')) {
            $attributeName = str_replace('attribute_', '', $key);
            $attributes[$attributeName] = $value;
        }
    }
    $data['attributes'] = $attributes;
    
    // Рассчитываем объем (финальный)
    $template = ProductTemplate::find($data['product_template_id']);
    $testResult = $template->testFormula($attributes);
    
    if ($testResult['success']) {
        $data['calculated_volume'] = $testResult['result']; // Сохранить: 5.0000
    }
    
    return $data;
    // Product::create($data) → БД сохранит calculated_volume
}
```

---

## 📌 Ключевые моменты

| Что | Где | Результат |
|-----|-----|-----------|
| **Выбор шаблона** | ProductResource.php:123 | Генерируются поля атрибутов |
| **Ввод значений** | ProductResource.php:250+ (TextInput) | Значения собираются в $attributes |
| **Live обновление** | ProductResource.php:268 | Каждое изменение → расчет → обновление UI |
| **testFormula()** | ProductTemplate.php:68 | Вычисляет объем по формуле |
| **Сохранение** | CreateProduct.php:59 | Финальный расчет → БД |
| **Результат** | products таблица | calculated_volume = 5.0000 |

---

## 🧮 Внутри testFormula() (ProductTemplate.php:68)

```
ВХОД:
  Формула: "length * width * height / 1000000"
  Атрибуты: {length: 2000, width: 100, height: 25}

ЭТАП 1 - Извлечение переменных:
  preg_match_all('/[a-zA-Z_][a-zA-Z0-9_]*/', formula)
  Результат: ['length', 'width', 'height']

ЭТАП 2 - Проверка полноты:
  Требуемые: [length, width, height]
  Имеющиеся: {length, width, height}
  ✓ Все есть

ЭТАП 3 - Подстановка значений:
  preg_replace('/\blength\b/', '2000', formula)
  → "2000 * width * height / 1000000"
  → "2000 * 100 * height / 1000000"
  → "2000 * 100 * 25 / 1000000"

ЭТАП 4 - Безопасное вычисление:
  parseExpression("2000 * 100 * 25 / 1000000")
  → evaluateExpressionRecursive()
  → Обрабатывает приоритет: * / перед + -
  → 2000 * 100 = 200000
  → 200000 * 25 = 5000000
  → 5000000 / 1000000 = 5.0

ВЫХОД:
  {
    success: true,
    error: null,
    result: 5.0
  }
```

---

## 💾 Где хранится данные

```
PostgreSQL/MySQL
├── products
│   ├── id: 1
│   ├── name: "Доска 2000x100x25"
│   ├── product_template_id: 1
│   ├── quantity: 100
│   ├── calculated_volume: 5.0000  ← ЗДЕСЬ
│   ├── attributes: {
│   │   "length": 2000,
│   │   "width": 100,
│   │   "height": 25
│   │}
│   └── ...
│
└── product_templates
    ├── id: 1
    ├── name: "Доска"
    ├── formula: "length * width * height / 1000000"
    └── attributes (1:many)
        ├── length (variable: "length", is_in_formula: true)
        ├── width  (variable: "width", is_in_formula: true)
        └── height (variable: "height", is_in_formula: true)
```

---

## 🎬 Временная последовательность

```
ВРЕМЯ    ДЕЙСТВИЕ                        РЕЗУЛЬТАТ
─────    ────────                        ─────────
T=0      Открыть форму                   Пустая форма
T=1      Выбрать шаблон #1               Генерируются поля для length, width, height
T=2      Ввести length: 2000             calculated_volume = "Заполните поля..."
T=3      Ввести width: 100               calculated_volume = "Заполните поля..."
T=4      Ввести height: 25               calculated_volume = 5.0000 ✓
T=5      Нажать СОХРАНИТЬ                CreateProduct::mutateFormDataBeforeCreate()
T=6      Финальный расчет                calculated_volume = 5.0000
T=7      INSERT в БД                     ✓ Товар создан
```

---

## ⚠️ Что может пойти не так

```
ПРОБЛЕМА                       ЧТО ПРОИЗОЙДЁТ
────────────────────────────── ─────────────────────
1. Не выбран шаблон            calculated_volume остаётся null
2. Не заполнены все поля       "Заполните поля: length, width"
3. Значение > 999999999.9999   "Объем превышает максимальное значение"
4. Неверная формула            "Ошибка вычисления: ..."
5. calculated_volume как строка Автоматически кастируется к decimal(8,4)
```

