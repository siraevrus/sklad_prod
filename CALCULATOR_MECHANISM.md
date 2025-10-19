# Механизм калькулятора в приложении Sklad

## 📋 Обзор

Приложение содержит два основных места ввода данных с расчётом объёма товаров:
1. **Поступление товара** (ReceiptResource) - расчёт объёма по формуле
2. **Реализация** (SaleResource) - расчёт общей суммы продажи

Механизм работает на основе **Filament** (SDUI фреймворк) с использованием **Livewire** для реактивности.

---

## 🧮 ЧАСТЬ 1: КАЛЬКУЛЯТОР ОБЪЁМА В "ПОСТУПЛЕНИЕ ТОВАРА"

### Архитектура

```
ReceiptResource (Filament)
    ├─> Repeater (Товары в приёмке)
    │   ├─> Select: product_template_id (Выбор шаблона)
    │   ├─> TextInput: quantity (Количество)
    │   ├─> TextInput: attribute_* (Динамические характеристики)
    │   └─> TextInput: calculated_volume (Результат расчёта - readonly)
    │
    └─> ProductAttributeCalculator (Класс-помощник)
        └─> ProductTemplate::testFormula()
            └─> Вычисление выражения по формуле
```

### Процесс ввода данных

#### Шаг 1: Выбор шаблона товара

```php
Select::make('product_template_id')
    ->live()
    ->afterStateUpdated(function (Set $set) {
        $set('name', '');
        $set('calculated_volume', null);
    })
```

**Что происходит:**
- При выборе шаблона очищаются характеристики
- Очищается вычисленный объём
- Triggering перезагрузки динамических полей

#### Шаг 2: Динамическое отображение характеристик

```php
Grid::make(2)->schema(function (Get $get) {
    $templateId = $get('product_template_id');
    $template = ProductTemplate::find($templateId);
    
    // Для каждой характеристики создаётся поле
    foreach ($template->attributes as $attribute) {
        $fieldName = "attribute_{$attribute->variable}";
        
        // Тип поля зависит от type характеристики
        switch ($attribute->type) {
            case 'number':
                // TextInput с валидацией regex
                // live(debounce: 400) - отправляет данные спустя 400ms после ввода
                break;
            case 'text':
                // TextInput без числовых ограничений
                break;
            case 'select':
                // Select с предопределёнными опциями
                break;
        }
    }
})
```

**Ключевые свойства:**
- `->live()` - реактивное обновление при вводе
- `->debounce(400)` - задержка 400ms перед отправкой
- `->afterStateUpdated()` - вызывает функцию расчёта при изменении

#### Шаг 3: Расчёт объёма при изменении поля

```php
->afterStateUpdated(function (Set $set, Get $get) {
    self::calculateVolumeForItem($set, $get);
})

// Метод в ReceiptResource:
private static function calculateVolumeForItem(Set $set, Get $get): void
{
    $templateId = $get('product_template_id');
    $quantity = $get('quantity');
    $formData = $get();
    
    // Передаём в калькулятор
    ProductAttributeCalculator::calculateAndUpdate($set, $formData, $templateId, $quantity);
}
```

### Ядро: ProductAttributeCalculator

Файл: `app/Support/ProductAttributeCalculator.php`

#### Метод: `calculateAndUpdate()`

```php
public static function calculateAndUpdate(
    Set $set, 
    array $formData, 
    int $templateId, 
    mixed $quantity = null
): void {
    
    // 1. Валидация templateId
    if (!$templateId) {
        $set('calculated_volume', 'Выберите шаблон');
        return;
    }
    
    // 2. Получение шаблона из БД (с кешированием)
    $template = self::getTemplateFromCache($templateId);
    if (!$template) {
        $set('calculated_volume', 'Шаблон не найден');
        return;
    }
    
    // 3. Извлечение атрибутов из formData
    $attributes = self::extractAttributes($formData);
    // Результат: ['length' => 10, 'width' => 5, 'height' => 2]
    
    // 4. Добавление количества (используется в формулах)
    if ($quantity !== null && is_numeric($quantity) && $quantity > 0) {
        $attributes['quantity'] = (int) $quantity;
    }
    
    // 5. Генерация наименования
    $generatedName = self::generateName($template, $attributes);
    if ($generatedName) {
        $set('name', $generatedName);
    }
    // Пример: "Доска: 10 x 5 x 2, Сосна"
    
    // 6. Расчёт объёма
    self::calculateVolume($set, $template, $attributes);
}
```

#### Вспомогательный метод: `extractAttributes()`

```php
private static function extractAttributes(array $formData): array
{
    $attributes = [];
    
    foreach ($formData as $key => $value) {
        // Ищем ключи вида: attribute_length, attribute_width
        if (str_starts_with($key, 'attribute_') && $value !== null && $value !== '') {
            $attributeName = str_replace('attribute_', '', $key);
            
            // ВАЖНО: Нормализуем запятые на точки!
            // Пользователь может ввести "2,5" - преобразуем в "2.5"
            $normalizedValue = is_string($value) 
                ? str_replace(',', '.', $value) 
                : $value;
            
            $attributes[$attributeName] = $normalizedValue;
        }
    }
    
    return $attributes;
}
// Вход: ['attribute_length' => '10,5', 'attribute_width' => '5', 'field_other' => 'skip']
// Выход: ['length' => '10.5', 'width' => '5']
```

#### Вспомогательный метод: `generateName()`

```php
private static function generateName(ProductTemplate $template, array $attributes): ?string
{
    if (empty($template->attributes)) {
        return null;
    }
    
    $formulaParts = [];    // Характеристики, участвующие в формуле
    $regularParts = [];    // Дополнительные характеристики
    
    foreach ($template->attributes as $templateAttribute) {
        $attributeKey = $templateAttribute->variable;
        
        if (isset($attributes[$attributeKey]) && $attributes[$attributeKey] !== null) {
            if ($templateAttribute->is_in_formula) {
                // Добавляем в формулу: "10, 5, 2"
                $formulaParts[] = $attributes[$attributeKey];
            } else {
                // Добавляем в доп. информацию: "Сосна, Сушённая"
                $regularParts[] = $attributes[$attributeKey];
            }
        }
    }
    
    // Формирование названия
    $templateName = $template->name ?? 'Товар';
    $generatedName = $templateName;
    
    if (!empty($formulaParts)) {
        $generatedName .= ': ' . implode(' x ', $formulaParts);
        // Результат: "Доска: 10 x 5 x 2"
    }
    
    if (!empty($regularParts)) {
        $generatedName .= !empty($formulaParts)
            ? ', ' . implode(', ', $regularParts)
            : ': ' . implode(', ', $regularParts);
        // Результат: "Доска: 10 x 5 x 2, Сосна, Сушённая"
    }
    
    return $generatedName;
}
```

#### Основной метод: `calculateVolume()`

```php
private static function calculateVolume(
    Set $set, 
    ProductTemplate $template, 
    array $attributes
): void {
    
    // 1. Проверка наличия формулы
    if (!$template->formula) {
        $set('calculated_volume', 'Формула не задана');
        return;
    }
    
    // 2. Отфильтровка только числовых значений > 0
    $numericAttributes = [];
    foreach ($attributes as $key => $value) {
        if (is_numeric($value) && $value > 0) {
            $numericAttributes[$key] = (float) $value;
        }
    }
    
    // 3. Проверка наличия данных для расчёта
    if (empty($numericAttributes)) {
        $set('calculated_volume', 'Заполните числовые характеристики');
        return;
    }
    
    // 4. Вызов метода тестирования формулы из модели
    $testResult = $template->testFormula($numericAttributes);
    
    // 5. Обработка результата
    if ($testResult['success']) {
        $result = $testResult['result'];
        $set('calculated_volume', $result);
        
        // Логирование в debug режиме
        if (config('app.debug')) {
            Log::debug('Volume calculated successfully', [
                'template' => $template->name,
                'attributes' => $numericAttributes,
                'result' => $result,
            ]);
        }
    } else {
        // Ошибка при вычислении
        $set('calculated_volume', 'Ошибка формулы: ' . ($testResult['error'] ?? 'Неизвестная ошибка'));
        Log::warning('Volume calculation failed', [
            'template' => $template->name,
            'attributes' => $numericAttributes,
            'error' => $testResult['error'] ?? 'Unknown',
        ]);
    }
}
```

### Вычисление формулы: ProductTemplate::testFormula()

Файл: `app/Models/ProductTemplate.php`

```php
public function testFormula(array $values = []): array
{
    if (!$this->formula) {
        return ['success' => false, 'error' => 'Формула не задана', 'result' => null];
    }
    
    try {
        // 1. Извлечение переменных из формулы
        $variables = $this->extractVariablesFromFormula();
        // Для формулы "length * width * height" получим: ['length', 'width', 'height']
        
        // 2. Проверка наличия всех переменных
        $missingVariables = array_diff($variables, array_keys($values));
        if (!empty($missingVariables)) {
            // Получаем человекочитаемые названия переменных
            $missingVariableNames = [];
            foreach ($missingVariables as $variable) {
                $attribute = $this->attributes()->where('variable', $variable)->first();
                if ($attribute) {
                    $missingVariableNames[] = $attribute->name;
                }
            }
            
            return [
                'success' => false,
                'error' => implode(', ', $missingVariableNames),
                'result' => null,
            ];
        }
        
        // 3. Подстановка переменных в формулу
        $expression = $this->formula;
        foreach ($values as $variable => $value) {
            if (is_numeric($value)) {
                // Используем regex для точной замены слов (не частей слов)
                $pattern = '/\b' . preg_quote($variable, '/') . '\b/';
                $expression = preg_replace($pattern, $value, $expression);
            }
        }
        // Для formula "length * width * height" и values {length: 10, width: 5, height: 2}
        // результат: "10 * 5 * 2"
        
        Log::info('Formula calculation', [
            'original_formula' => $this->formula,
            'values' => $values,
            'final_expression' => $expression,
        ]);
        
        // 4. Безопасное вычисление выражения
        $result = $this->evaluateExpression($expression);
        
        return [
            'success' => true,
            'error' => null,
            'result' => round($result, 3),
            // Для примера: 100 (10 * 5 * 2)
        ];
    } catch (\Exception $e) {
        return [
            'success' => false,
            'error' => 'Ошибка вычисления: ' . $e->getMessage(),
            'result' => null,
        ];
    }
}
```

#### Безопасное вычисление: `evaluateExpression()`

```php
private function evaluateExpression(string $expression): float
{
    // 1. Удаляем пробелы
    $expression = str_replace(' ', '', $expression);
    
    // 2. Валидация безопасности (только математические операции!)
    if (!preg_match('/^[0-9+\-*\/\(\)\.]+$/', $expression)) {
        throw new \Exception('Выражение содержит недопустимые символы');
    }
    
    // 3. Вычисление (рекурсивный парсер с соблюдением приоритета операций)
    try {
        $result = $this->parseExpression($expression);
        
        if (!is_numeric($result)) {
            throw new \Exception('Результат не является числом');
        }
        
        return (float) $result;
        
    } catch (\DivisionByZeroError $e) {
        throw new \Exception('Деление на ноль');
    } catch (\Exception $e) {
        throw new \Exception('Ошибка вычисления выражения: ' . $e->getMessage());
    }
}
```

#### Парсер выражений: `parseExpression()`

```php
private function parseExpression(string $expression): float
{
    // 1. Удаляем пробелы
    $expression = preg_replace("/\s+/", '', $expression);
    
    // 2. Валидация скобок (баланс открытия/закрытия)
    if (!$this->validateParentheses($expression)) {
        throw new \Exception('Неправильное использование скобок');
    }
    
    // 3. Рекурсивное вычисление с приоритетом операций
    return $this->evaluateExpressionRecursive($expression);
}

// Рекурсивное вычисление соблюдает приоритет:
// 1. Скобки ()
// 2. Умножение (*) и деление (/)
// 3. Сложение (+) и вычитание (-)
// 
// Для "2 + 3 * 4" получим 14, а не 20
```

---

## 💰 ЧАСТЬ 2: КАЛЬКУЛЯТОР СУММЫ В "РЕАЛИЗАЦИЯ"

### Архитектура

```
SaleResource (Filament)
    ├─> TextInput: cash_amount (Сумма наличными)
    ├─> TextInput: nocash_amount (Сумма безналичными)
    ├─> TextInput: total_price (Итого - readonly, calculated)
    │
    └─> calculateTotalPrice() (Метод класса)
```

### Процесс ввода данных

#### Форма в SaleResource

```php
Grid::make(5)->schema([
    // Первый столбец
    TextInput::make('cash_amount')
        ->label('Сумма (нал)')
        ->numeric()
        ->required()
        ->mask(RawJs::make('$number($input, { 
            decimalPlaces: 2, 
            thousandsSeparator: " ", 
            decimalSeparator: "," 
        })'))
        ->live(onBlur: true)  // Обновляет при потере фокуса
        ->afterStateUpdated(function (Set $set, Get $get) {
            static::calculateTotalPrice($set, $get);
        }),
    
    TextInput::make('nocash_amount')
        ->label('Сумма (безнал)')
        ->numeric()
        ->required()
        ->mask(RawJs::make('$number($input, { 
            decimalPlaces: 2, 
            thousandsSeparator: " ", 
            decimalSeparator: "," 
        })'))
        ->live(onBlur: true)
        ->afterStateUpdated(function (Set $set, Get $get) {
            static::calculateTotalPrice($set, $get);
        }),
    
    // Второй столбец
    TextInput::make('total_price')
        ->label('Общая сумма')
        ->numeric()
        ->disabled()  // Только для чтения
        ->required(),
]);
```

#### Метод расчёта: `calculateTotalPrice()`

```php
private static function calculateTotalPrice(Set $set, Get $get): void
{
    // 1. Получение значений (могут содержать запятые вместо точек)
    $cashRaw = (string) ($get('cash_amount') ?? '0');
    $nocashRaw = (string) ($get('nocash_amount') ?? '0');
    
    // 2. Нормализация: запятая -> точка для корректного преобразования
    $cashAmount = (float) str_replace(',', '.', $cashRaw);
    $nocashAmount = (float) str_replace(',', '.', $nocashRaw);
    
    // 3. Расчёт суммы
    $totalPrice = $cashAmount + $nocashAmount;
    
    // 4. Установка результата
    $set('total_price', $totalPrice);
    
    // 5. Логирование
    Log::info('Sale form: calculateTotalPrice', [
        'cash_amount' => $cashAmount,
        'nocash_amount' => $nocashAmount,
        'total_price' => $totalPrice,
    ]);
}
```

### Маска ввода (RawJs)

```javascript
// Пример преобразования:
// Ввод: "1000"
// Отобразится: "1 000,00" (с тысячами разделителем)
// Сохраняется: "1000" (числовое значение)

$number($input, {
    decimalPlaces: 2,          // Два знака после запятой
    thousandsSeparator: " ",   // Пробел как разделитель тысяч
    decimalSeparator: ","      // Запятая как десятичный разделитель
})
```

---

## 🔄 КЛЮЧЕВЫЕ ОТЛИЧИЯ

### Поступление товара vs Реализация

| Аспект | Поступление | Реализация |
|--------|-----------|-----------|
| **Входные данные** | Характеристики товара (длина, ширина, высота, etc.) | Денежные суммы (наличные, безналичные) |
| **Тип расчёта** | Формула (математическое выражение) | Простое сложение двух полей |
| **Реактивность** | `live(debounce: 400)` - задержка 400ms | `live(debounce: 400)` - задержка 400ms ✨ **УНИФИЦИРОВАНО** |
| **Вычисления** | Парсер выражений в `ProductTemplate::testFormula()` | Простое сложение float чисел |
| **Генерация имени** | Автоматическая из характеристик | Не применяется |
| **Количество полей** | Динамические (зависит от шаблона) | Статические |
| **Нормализация ввода** | str_replace(',', '.', $value) | str_replace(',', '.', $value) |

---

## 🛡️ БЕЗОПАСНОСТЬ

### Валидация ввода

1. **Regex валидация в TextInput (числовые характеристики):**
   ```php
   ->rules(['regex:/^\d*([.,]\d*)?$/'])
   ->validationMessages(['regex' => 'Поле должно содержать только цифры и одну запятую или точку'])
   ```
   ✅ Разрешены: `10`, `10.5`, `10,5`
   ❌ Запрещены: `abc`, `10,,5`, `10.5.2`

2. **Безопасное вычисление формул:**
   ```php
   // Регулярное выражение проверяет, что в выражении только математические символы
   if (!preg_match('/^[0-9+\-*\/\(\)\.]+$/', $expression)) {
       throw new \Exception('Выражение содержит недопустимые символы');
   }
   ```
   ✅ Разрешены: `10 * 5 + 2`, `(10 + 5) * 2`
   ❌ Запрещены: `exec()`, `system()`, любой код

3. **Использование рекурсивного парсера вместо eval():**
   - В `ReceiptResource::calculateVolumeForItem()` старый код использовал `eval()`
   - В `ProductTemplate::testFormula()` используется собственный парсер - **безопаснее!**

---

## 📊 ПРИМЕР ПОЛНОГО ЦИКЛА

### Сценарий: Приём доски

1. **Пользователь выбирает шаблон:**
   ```
   Шаблон: "Доска"
   Характеристики: length, width, height, wood_type
   Формула: "length * width * height / 1000"
   ```

2. **Система показывает динамические поля:**
   ```
   ☐ Длина (см): [____]
   ☐ Ширина (см): [____]
   ☐ Высота (см): [____]
   ☐ Тип древесины: [выпадающий список]
   ```

3. **Пользователь вводит данные:**
   ```
   Длина: 200
   Ширина: 50,5      ← нотация с запятой
   Высота: 2,5       ← нотация с запятой
   Тип: Сосна
   Количество: 10
   ```

4. **Система нормализует и извлекает:**
   ```php
   $attributes = [
       'length' => 200,       // float
       'width' => 50.5,       // float - запятая заменена
       'height' => 2.5,       // float - запятая заменена
       'wood_type' => 'Сосна',
       'quantity' => 10
   ]
   ```

5. **Система генерирует имя:**
   ```
   Шаблон: "Доска: 200 x 50.5 x 2.5, Сосна"
   ```

6. **Система вычисляет объём:**
   ```
   Формула: "length * width * height / 1000"
   Подстановка: "200 * 50.5 * 2.5 / 1000"
   Парсинг и вычисление: (200 * 50.5 * 2.5) / 1000 = 25.25
   Результат: 25.25 м³
   ```

7. **Поле отображает результат:**
   ```
   Объем: 25.250 м³
   ```

---

## 🐛 ИЗВЕСТНЫЕ ОСОБЕННОСТИ И ПОТЕНЦИАЛЬНЫЕ ПРОБЛЕМЫ

### 1. Двойная реализация расчётов в ReceiptResource

**Проблема:** В `ReceiptResource::calculateVolumeForItem()` старый код использует `eval()`:
```php
$result = eval("return $expression;");  // ⚠️ УЯЗВИМОСТЬ!
```

**Решение:** Используется `ProductAttributeCalculator` и `ProductTemplate::testFormula()` с безопасным парсером.

### 2. Нормализация запятых/точек

Система обрабатывает как `2.5`, так и `2,5`, что хорошо для UX, но:
- При редактировании числа может быть непредсказуемое преобразование
- Рекомендуется для пользователей документировать привычный разделитель

### 3. Debounce в поступлении vs onBlur в реализации

- **Поступление:** `live(debounce: 400)` - отправляет данные спустя 400ms
  - ✅ Не перегружает сервер постоянными запросами
  - ❌ Есть задержка перед расчётом

- **Реализация:** `live(onBlur: true)` - отправляет при потере фокуса
  - ✅ Расчёт происходит только когда пользователь готов
  - ❌ Если забыл кликнуть - расчёт не произойдёт

### 4. Кеширование шаблонов

```php
private static array $templateCache = [];  // Кеш в памяти

public static function clearCache(): void {
    self::$templateCache = [];
}
```

- ✅ Избегает повторных запросов к БД
- ❌ Если шаблон обновится во время сессии - не обновится до очистки кеша

---

## 📚 ИСПОЛЬЗУЕМЫЕ КЛАССЫ И ФАЙЛЫ

### Core Files
- `app/Support/ProductAttributeCalculator.php` - основной класс для расчётов
- `app/Models/ProductTemplate.php` - модель шаблона с формулами
- `app/Models/Product.php` - модель товара
- `app/Models/Sale.php` - модель продажи

### Filament Resources
- `app/Filament/Resources/ReceiptResource.php` - форма приёмки
- `app/Filament/Resources/SaleResource.php` - форма продажи
- `app/Filament/Resources/ProductInTransitResource.php` - товары в пути

### Controllers
- `app/Http/Controllers/Api/ProductController.php` - API endpoints
- `app/Http/Controllers/ProductWebController.php` - веб-контроллер

---

## 🔧 РЕКОМЕНДАЦИИ ПО УЛУЧШЕНИЮ

1. **Унифицировать debounce/onBlur стратегию** - выбрать одну логику для всех форм
2. **Добавить кеширование на уровне Redis** вместо памяти приложения
3. **Использовать JavaScript валидацию** перед отправкой на сервер для лучшей UX
4. **Добавить тестирование формул** - пользователь может протестировать перед сохранением
5. **Документировать поддерживаемые форматы чисел** в UI (справка/tooltip)
6. **Кешировать результаты расчётов** для одинаковых входных данных

---

## 📖 ССЫЛКИ НА КОД

- ProductAttributeCalculator: `app/Support/ProductAttributeCalculator.php` (188 строк)
- ProductTemplate формулы: `app/Models/ProductTemplate.php` (lines 68-311)
- ReceiptResource: `app/Filament/Resources/ReceiptResource.php` (lines 186-238)
- SaleResource: `app/Filament/Resources/SaleResource.php` (lines 61-78)

---

## 🔀 ДИАГРАММЫ ПОТОКОВ ДАННЫХ

### Диаграмма 1: Поток данных при вводе характеристики в "Поступление товара"

```
┌─────────────────────────────────────────────────────────────────┐
│  ПОЛЬЗОВАТЕЛЬ ВВОДИТ ЗНАЧЕНИЕ В ПОЛЕ ХАРАКТЕРИСТИКИ             │
│  (например: TextInput "Ширина" = "50,5")                        │
└────────────────┬────────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────────┐
│  LIVEWIRE ОБНАРУЖИВАЕТ ИЗМЕНЕНИЕ (live: debounce: 400ms)       │
│  ✓ Ожидает 400ms без новых изменений                           │
│  ✓ Отправляет AJAX-запрос на сервер                            │
└────────────────┬────────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────────┐
│  FILAMENT ВЫЗЫВАЕТ afterStateUpdated()                           │
│  └─> ReceiptResource::calculateVolumeForItem($set, $get)        │
└────────────────┬────────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────────┐
│  ИЗВЛЕЧЕНИЕ ДАННЫХ ИЗ ФОРМЫ ($get())                           │
│  {                                                               │
│    'product_template_id' => 1,                                   │
│    'quantity' => 10,                                             │
│    'attribute_length' => '200',                                  │
│    'attribute_width' => '50,5',        ← ВВОД ПОЛЬЗОВАТЕЛЯ      │
│    'attribute_height' => '2,5',                                  │
│    'attribute_wood_type' => 'Сосна'                             │
│  }                                                               │
└────────────────┬────────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────────┐
│  ProductAttributeCalculator::calculateAndUpdate()               │
│  1. Валидация templateId                                        │
│  2. Загрузка шаблона из кеша                                    │
│  3. Извлечение атрибутов (extractAttributes)                    │
│     ✓ Находит все ключи вида "attribute_*"                     │
│     ✓ Нормализует: "50,5" → 50.5                              │
│  4. Генерация имени (generateName)                              │
│  5. Расчёт объёма (calculateVolume)                             │
└────────────────┬────────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────────┐
│  extractAttributes(): Нормализация чисел                        │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │ INPUT: 'attribute_width' => '50,5'                       │   │
│  │ REGEX: str_replace(',', '.', '50,5')                    │   │
│  │ OUTPUT: 'width' => 50.5 (float)                         │   │
│  └──────────────────────────────────────────────────────────┘   │
│                                                                   │
│  Результат: $attributes = [                                    │
│    'length' => 200,                                             │
│    'width' => 50.5,      ← НОРМАЛИЗОВАНО                       │
│    'height' => 2.5,      ← НОРМАЛИЗОВАНО                       │
│    'wood_type' => 'Сосна'                                       │
│  ]                                                              │
└────────────────┬────────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────────┐
│  generateName(): Создание наименования товара                   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │ Берёт характеристики с is_in_formula = true             │   │
│  │ formulaParts = [200, 50.5, 2.5]                         │   │
│  │                                                           │   │
│  │ Берёт остальные характеристики                          │   │
│  │ regularParts = ['Сосна']                                │   │
│  │                                                           │   │
│  │ Результат: "Доска: 200 x 50.5 x 2.5, Сосна"           │   │
│  └──────────────────────────────────────────────────────────┘   │
│  $set('name', 'Доска: 200 x 50.5 x 2.5, Сосна')               │
└────────────────┬────────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────────┐
│  calculateVolume(): Основной расчёт                             │
│  1. Фильтр числовых атрибутов (> 0)                            │
│     $numericAttributes = ['length' => 200, 'width' => 50.5,    │
│                          'height' => 2.5]                      │
│  2. Проверка наличия формулы                                   │
│  3. Вызов ProductTemplate::testFormula($numericAttributes)     │
└────────────────┬────────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────────┐
│  ProductTemplate::testFormula()                                 │
│  1. Извлечение переменных из формулы                           │
│     formula: "length * width * height / 1000"                  │
│     variables: ['length', 'width', 'height']                   │
│                                                                  │
│  2. Подстановка значений в формулу (regex с \b - границы слов) │
│     expression = "200 * 50.5 * 2.5 / 1000"                    │
│                                                                  │
│  3. Безопасное вычисление (evaluateExpression)                 │
│     ✓ Валидация regex: только [0-9+\-*\/\(\)\.]              │
│     ✓ Рекурсивный парсер с приоритетом операций              │
│     ✓ Вычисление: (200 * 50.5 * 2.5) / 1000 = 25.25          │
│                                                                  │
│  4. Возврат результата                                         │
│     return [                                                    │
│       'success' => true,                                        │
│       'error' => null,                                          │
│       'result' => 25.250  ← ОКРУГЛЕНО ДО 3 ЗНАКОВ             │
│     ]                                                           │
└────────────────┬────────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────────┐
│  УСТАНОВКА РЕЗУЛЬТАТА В ФОРМУ                                   │
│  $set('calculated_volume', 25.250)                             │
└────────────────┬────────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────────┐
│  LIVEWIRE ОБНОВЛЯЕТ UI (реактивное обновление)                  │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │ Поле "Объем" обновляется:                               │   │
│  │ Было: [пусто]                                           │   │
│  │ Стало: 25.250 м³                                        │   │
│  └──────────────────────────────────────────────────────────┘   │
│                                                                  │
│  ✓ Форматирование при отображении (number_format)             │
│  ✓ Пользователь видит результат в реальном времени            │
└─────────────────────────────────────────────────────────────────┘
```

### Диаграмма 2: Поток данных при вводе суммы в "Реализация"

```
┌──────────────────────────────────────────────────────────────────┐
│  ПОЛЬЗОВАТЕЛЬ ВВОДИТ СУММУ В ПОЛЕ                                │
│  Наличные: [____]  →  Ввод: "1000"                              │
│  Безналичные: [____]  →  Ввод: "500,50"                         │
└────────────────┬─────────────────────────────────────────────────┘
                 │
                 ▼
┌──────────────────────────────────────────────────────────────────┐
│  LIVEWIRE ОБНАРУЖИВАЕТ ПОТЕРЮ ФОКУСА (live: onBlur: true)       │
│  ✓ Маска преобразует значение для отображения                  │
│    "1000" → "1 000,00" (с разделителем тысяч и 2 знака)       │
│  ✓ Отправляет AJAX-запрос на сервер при потере фокуса         │
└────────────────┬─────────────────────────────────────────────────┘
                 │
                 ▼
┌──────────────────────────────────────────────────────────────────┐
│  FILAMENT ВЫЗЫВАЕТ afterStateUpdated()                            │
│  └─> SaleResource::calculateTotalPrice($set, $get)              │
└────────────────┬─────────────────────────────────────────────────┘
                 │
                 ▼
┌──────────────────────────────────────────────────────────────────┐
│  ПОЛУЧЕНИЕ ЗНАЧЕНИЙ ИЗ ФОРМЫ ($get())                            │
│  {                                                                │
│    'cash_amount' => '1 000,00',      ← ОТФОРМАТИРОВАНО         │
│    'nocash_amount' => '500,50'        ← ОТФОРМАТИРОВАНО         │
│  }                                                                │
└────────────────┬─────────────────────────────────────────────────┘
                 │
                 ▼
┌──────────────────────────────────────────────────────────────────┐
│  calculateTotalPrice() - Нормализация и расчёт                   │
│  ┌─────────────────────────────────────────────────────────────┐ │
│  │ 1. Преобразование строк в float                            │ │
│  │    $cashRaw = (string) '1 000,00'                          │ │
│  │    str_replace(',', '.', '1 000,00')  ← ОШИБКА!           │ │
│  │    Результат: '1 000.00' ← пробелы не удалены!            │ │
│  │                                                             │ │
│  │    PHP ПРЕОБРАЗУЕТ К FLOAT (игнорирует пробел):           │ │
│  │    (float) '1 000.00' = 1.0 ← НЕПРАВИЛЬНО!                │ │
│  │                                                             │ │
│  │ ВАЖНО: Это потенциальная ошибка!                          │ │
│  │ Нужно удалять пробелы перед преобразованием                │ │
│  └─────────────────────────────────────────────────────────────┘ │
│                                                                    │
│  2. Расчёт суммы (при корректной нормализации)                  │
│    $cashAmount = 1000.00                                          │
│    $nocashAmount = 500.50                                         │
│    $totalPrice = 1000.00 + 500.50 = 1500.50                     │
│                                                                    │
│  3. Установка результата                                         │
│    $set('total_price', 1500.50)                                  │
└────────────────┬─────────────────────────────────────────────────┘
                 │
                 ▼
┌──────────────────────────────────────────────────────────────────┐
│  ЛОГИРОВАНИЕ                                                      │
│  Log::info('Sale form: calculateTotalPrice', [                   │
│    'cash_amount' => 1000.00,                                     │
│    'nocash_amount' => 500.50,                                    │
│    'total_price' => 1500.50,                                     │
│  ])                                                               │
└────────────────┬─────────────────────────────────────────────────┘
                 │
                 ▼
┌──────────────────────────────────────────────────────────────────┐
│  LIVEWIRE ОБНОВЛЯЕТ UI                                            │
│  ┌─────────────────────────────────────────────────────────────┐ │
│  │ Поле "Общая сумма" обновляется:                            │ │
│  │ Было: [пусто]                                              │ │
│  │ Стало: 1500.50                                             │ │
│  │ Отображение: 1 500,50 (с форматированием)                 │ │
│  └─────────────────────────────────────────────────────────────┘ │
└──────────────────────────────────────────────────────────────────┘
```

### Диаграмма 3: Иерархия классов и методов

```
                    FILAMENT RESOURCE
                          │
        ┌─────────────────┼─────────────────┐
        │                 │                 │
   ReceiptResource   SaleResource   ProductInTransitResource
        │                 │                 │
        ├─ Form()         ├─ Form()        └─ Form()
        │  │              │  │
        │  ├─ Repeater    │  └─ Grid (cash_amount, nocash_amount, total_price)
        │  │  │           │     │
        │  │  ├─ Select    │     └─ afterStateUpdated → calculateTotalPrice()
        │  │  │  (template_id)    │
        │  │  │  │                └─ norms: ',.' → '.', adds numbers
        │  │  │  └─ afterStateUpdated
        │  │  │
        │  │  ├─ TextInput (quantity, attributes)
        │  │  │  │
        │  │  │  └─ live(debounce: 400)
        │  │  │     └─ afterStateUpdated → calculateVolumeForItem()
        │  │  │        │
        │  │  │        └─ CALLS
        │  │  │           │
        │  │  └─ TextInput (calculated_volume, readonly)
        │  │
        │  └─ ProductAttributeCalculator
        │     │
        │     ├─ calculateAndUpdate()
        │     │  ├─ 1️⃣  extractAttributes()     ← Extract attribute_* keys
        │     │  │       ├─ Normalize: ',' → '.'
        │     │  │       └─ Return: $attributes
        │     │  │
        │     │  ├─ 2️⃣  generateName()          ← Build product name
        │     │  │       ├─ Separate formula vs regular parts
        │     │  │       └─ Return: "Product: 200 x 50.5, Sосна"
        │     │  │
        │     │  └─ 3️⃣  calculateVolume()        ← Compute volume
        │     │         │
        │     │         └─ CALLS
        │     │
        │     └─ ProductTemplate::testFormula()
        │        │
        │        ├─ 1️⃣  extractVariablesFromFormula()
        │        │       ├─ Regex: /[a-zA-Z_][a-zA-Z0-9_]*/
        │        │       └─ Filter out: sin, cos, sqrt, etc.
        │        │
        │        ├─ 2️⃣  Substitute variables in formula
        │        │       ├─ Regex: /\b{variable}\b/
        │        │       └─ Result: "200 * 50.5 * 2.5 / 1000"
        │        │
        │        └─ 3️⃣  evaluateExpression()
        │                ├─ Validate: /^[0-9+\-*\/\(\)\.]+$/
        │                ├─ parseExpression()
        │                │   ├─ validateParentheses()
        │                │   └─ evaluateExpressionRecursive()
        │                │       ├─ Handle operator precedence
        │                │       └─ Calculate recursively
        │                └─ Return: 25.25
```

### Диаграмма 4: Жизненный цикл Livewire компонента при вводе данных

```
┌────────────────────────────────────────────────────────────────┐
│  1️⃣  ИНИЦИАЛИЗАЦИЯ КОМПОНЕНТА                                 │
│     ├─ mount() вызывается                                      │
│     ├─ $template = ProductTemplate::find(1)                    │
│     └─ schema() генерирует поля на основе $template            │
└────────────────┬───────────────────────────────────────────────┘
                 │
                 ▼
┌────────────────────────────────────────────────────────────────┐
│  2️⃣  ПОЛЬЗОВАТЕЛЬ ВЫБИРАЕТ ШАБЛОН (live)                      │
│     ├─ AJAX: product_template_id = 1                           │
│     ├─ Livewire обновляет состояние                            │
│     ├─ afterStateUpdated вызывается                            │
│     │  ├─ $set('name', '')                                     │
│     │  ├─ $set('calculated_volume', null)                      │
│     │  └─ schema() ПЕРЕГЕНЕРИРУЕТСЯ с новыми полями!           │
│     └─ HTML обновляется (новые поля для характеристик)        │
└────────────────┬───────────────────────────────────────────────┘
                 │
                 ▼
┌────────────────────────────────────────────────────────────────┐
│  3️⃣  ПОЛЬЗОВАТЕЛЬ ВВОДИТ ХАРАКТЕРИСТИКУ (live debounce: 400)  │
│     ├─ Ввод: TextInput "width" = "50,5"                        │
│     ├─ ЖДЁМ 400ms БЕЗ НОВЫХ ИЗМЕНЕНИЙ                         │
│     ├─ AJAX запрос: attribute_width = "50,5"                   │
│     ├─ Livewire обновляет состояние                            │
│     ├─ afterStateUpdated вызывается                            │
│     │  └─ calculateVolumeForItem($set, $get)                   │
│     │     └─ ProductAttributeCalculator::calculateAndUpdate()  │
│     └─ $set('calculated_volume', 25.25) обновляет состояние   │
└────────────────┬───────────────────────────────────────────────┘
                 │
                 ▼
┌────────────────────────────────────────────────────────────────┐
│  4️⃣  LIVEWIRE RE-RENDERS КОМПОНЕНТ                             │
│     ├─ Генерируется новый HTML                                 │
│     ├─ Только измененные части отправляются клиенту            │
│     ├─ JavaScript обновляет DOM                                │
│     └─ Поле "Объем" теперь показывает: 25.250 м³             │
└────────────────┬───────────────────────────────────────────────┘
                 │
                 ▼
┌────────────────────────────────────────────────────────────────┐
│  5️⃣  СОХРАНЕНИЕ ДАННЫХ (Save)                                  │
│     ├─ Пользователь кликает "Сохранить"                        │
│     ├─ Форма валидируется                                      │
│     ├─ mutateFormDataBeforeSave() вызывается                   │
│     │  └─ Обработка данных перед сохранением                  │
│     ├─ Product::create($data) сохраняет в БД                  │
│     └─ Редирект на список товаров                              │
└────────────────────────────────────────────────────────────────┘
```

---

## 📝 ОБНОВЛЕНИЕ (19.10.2025)

### Унификация реактивности

**Изменение:** Раздел "Реализация" был обновлён для использования единого механизма реактивности со всеми остальными калькуляторами.

**До:**
```php
// SaleResource - Реализация
TextInput::make('cash_amount')
    ->live(onBlur: true)  // Расчёт только при потере фокуса
```

**После:**
```php
// SaleResource - Реализация
TextInput::make('cash_amount')
    ->live(debounce: 400)  // Расчёт автоматически через 400ms
```

**Преимущества:**
- ✅ Консистентный UX - один и тот же механизм везде
- ✅ Лучшая отзывчивость - результат видно сразу без кликов
- ✅ Меньше ошибок пользователей - забыли кликнуть = расчёт не произойдёт
- ✅ Оптимизирована нагрузка на сервер - 400ms debounce эффективен

**Файлы, измененные:**
- `app/Filament/Resources/SaleResource.php` (строки 359, 369)
