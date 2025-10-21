# Механизм заполнения calculated_volume

## 🎯 Общий процесс

`calculated_volume` это вычисленный объем товара за одну единицу, рассчитываемый по формуле, определённой в шаблоне товара (ProductTemplate).

### Структура данных:
- **ProductTemplate** - содержит формулу (например: "length * width * height")
- **ProductAttribute** - характеристики шаблона (переменные в формуле)
- **Product** - товар с атрибутами (значения переменных) и calculated_volume (результат расчета)

---

## 📋 Пошаговый процесс

### 1️⃣ ВЫ ВЫБИРАЕТЕ ШАБЛОН ТОВАРА
```
Form: Select -> product_template_id
Событие: afterStateUpdated
```

**Что происходит:**
- Загружаются атрибуты из выбранного шаблона
- Генерируются поля для ввода значений характеристик (attribute_X)
- `calculated_volume` устанавливается в "Заполните характеристики для расчета объема"

**Код:** ProductResource.php, строка 123-135

---

### 2️⃣ ВЫ ЗАПОЛНЯЕТЕ ЗНАЧЕНИЯ ХАРАКТЕРИСТИК
```
Form: TextInput -> attribute_length, attribute_width, attribute_height (и т.д.)
Событие: afterStateUpdated (на каждое изменение)
```

**Что происходит:**
1. Собираются все заполненные numeric характеристики
2. Добавляется quantity в атрибуты для формулы
3. Вызывается $template->testFormula($attributes)
4. Результат подставляется в `calculated_volume`

**Формула вычисления:**
```php
// Собираем атрибуты с вашими характеристиками
$attributes = [
    'length' => 10,      // Что вы ввели
    'width' => 5,        // Что вы ввели
    'height' => 2,       // Что вы ввели
    'quantity' => 100    // Берется из поля Quantity
];

// Берется формула из ProductTemplate, например: "length * width * height"
// Подставляются значения: "10 * 5 * 2"
// Результат = 100 (это объем за одну единицу!)
```

**Код:** ProductResource.php, строка 268-332

---

### 3️⃣ ВЫ МЕНЯЕТЕ КОЛИЧЕСТВО (QUANTITY)
```
Form: TextInput -> quantity
Событие: afterStateUpdated
```

**Что происходит:**
- Количество добавляется в атрибуты для формулы (если formula его использует)
- Пересчитывается `calculated_volume`

**Код:** ProductResource.php, строка 152-234

---

### 4️⃣ ВЫ СОХРАНЯЕТЕ ФОРМУ (СОЗДАНИЕ ТОВАРА)
```
CreateProduct::mutateFormDataBeforeCreate()
```

**Что происходит:**
1. Собираются все атрибуты (attribute_X)
2. Вызывается финальный расчет `$template->testFormula()`
3. Рассчитанное значение сохраняется в `calculated_volume`
4. Товар создается в БД

**Код:** CreateProduct.php, строка 59-129

---

### 5️⃣ ВЫ РЕДАКТИРУЕТЕ ТОВАР (ИЗМЕНЕНИЕ ТОВАРА)
```
EditProduct::mutateFormDataBeforeSave()
```

**Что происходит:**
- То же самое, что при создании, но для существующего товара

**Код:** EditProduct.php, строка 46-96

---

## 🔧 Ключевые методы

### Product::calculateVolume()
```php
// Рассчитывает объем на основе формулы и характеристик товара
$volume = $product->calculateVolume();
// Возвращает float или null
```

**Локация:** Product.php, строка 336-380

### Product::updateCalculatedVolume()
```php
// Пересчитывает и сохраняет объем
$product->updateCalculatedVolume();
// Сохраняет в БД
```

**Локация:** Product.php, строка 385-390

### ProductTemplate::testFormula(array $values)
```php
// Проверяет формулу и вычисляет результат
$result = $template->testFormula([
    'length' => 10,
    'width' => 5,
    'height' => 2
]);
// Возвращает ['success' => true/false, 'error' => '...', 'result' => number]
```

**Локация:** ProductTemplate.php, строка 68-140

---

## 🧮 Формула расчета

**ProductTemplate::testFormula()** работает так:

1. **Извлекает переменные из формулы**
   ```php
   // Formula: "length * width * height"
   // Найденные переменные: ['length', 'width', 'height']
   ```

2. **Проверяет, что все переменные заполнены**
   ```php
   if (!isset($values['length']) || !isset($values['width']) || ...) {
       return ['success' => false, 'error' => 'Missing variables'];
   }
   ```

3. **Заменяет переменные на значения**
   ```php
   // Оригинальная формула: "length * width * height"
   // Становится: "10 * 5 * 2"
   ```

4. **Безопасно вычисляет результат**
   - Использует рекурсивный парсер выражений
   - Поддерживает операторы: +, -, *, /, скобки
   - Соблюдает приоритет операций

---

## 📊 Где используется calculated_volume

1. **StockResource** - таблица Остатки выводит как "Объем (за ед.)"
2. **Расчет total_volume** - средневзвешенный объем
3. **Остаток Объем (м³)** - общий объем товара на складе
4. **API** - StockController использует для расчета общих объемов
5. **ProductInTransit** - для товаров в пути

---

## ✅ Механизм валидации

### На уровне модели (Product.php)
```php
public function setCalculatedVolumeAttribute($value)
{
    $maxValue = 999999999.9999; // decimal(15,4)
    
    if ($value > $maxValue) {
        Log::warning('Volume exceeds maximum');
        $this->attributes['calculated_volume'] = null;
        return;
    }
    
    $this->attributes['calculated_volume'] = $value;
}
```

### На уровне формы (ProductResource.php)
```php
if ($result > 999999999.9999) {
    $set('calculated_volume', 'Объем превышает максимальное значение');
}
```

---

## 🔄 Полный цикл жизни

```
1. ВЫ выбираете шаблон
   ↓
2. FILAMENT генерирует поля характеристик
   ↓
3. ВЫ вводите значения
   ↓
4. JAVASCRIPT отслеживает изменения (live)
   ↓
5. LARAVEL вычисляет calculated_volume
   ↓
6. FILAMENT показывает результат
   ↓
7. ВЫ сохраняете форму
   ↓
8. БАЗА ДАННЫХ сохраняет calculated_volume и attributes
```

---

