# 📚 ПОЛНЫЙ РАЗБОР calculated_volume

## ✨ Краткий ответ

`calculated_volume` — это объем товара за одну единицу, **автоматически рассчитываемый** из:
- **Формулы шаблона** (например: `length * width * height / 1000000`)
- **Значений характеристик** (которые вы вводите в форме: length=2000, width=100, height=25)

**Результат:** `5.0000` м³ — это объем 1 единицы товара. При quantity=100, общий объем = 500 м³.

---

## 🔍 ГДЕ ВСЕ ВЫЧИСЛЯЕТСЯ

### 1. **FILAMENT ФОРМА** (ProductResource.php)

При заполнении формы создания/редактирования товара:

```
┌─ Выбор шаблона (product_template_id)
│  └─ Генерируются поля характеристик
│
├─ Заполнение характеристик (attribute_X fields)
│  └─ live обновление → автоматический расчет calculated_volume
│
└─ Сохранение
   └─ Финальная проверка и сохранение в БД
```

**Ключевые события:**
- `product_template_id` → `afterStateUpdated()` (строка 123)
- `attribute_X` → `afterStateUpdated()` (строка 268)
- `quantity` → `afterStateUpdated()` (строка 152)

### 2. **BACKEND ЛОГИКА**

**ProductTemplate::testFormula()** (ProductTemplate.php:68)
- Берёт формулу из шаблона
- Берёт значения атрибутов
- Подставляет и безопасно вычисляет результат
- Возвращает `['success' => bool, 'result' => float]`

**Product::calculateVolume()** (Product.php:336)
- Вызывает `$template->testFormula()`
- Возвращает вычисленный объем

**CreateProduct::mutateFormDataBeforeCreate()** (CreateProduct.php:59)
- Финальный расчет перед сохранением
- Устанавливает `data['calculated_volume']`
- Вызывает `Product::create($data)`

### 3. **БАЗА ДАННЫХ** (таблица products)

```sql
CREATE TABLE products (
    id INT PRIMARY KEY,
    product_template_id INT,
    attributes JSON,  -- {"length": 2000, "width": 100, "height": 25}
    calculated_volume DECIMAL(8,4),  -- ← ЗДЕСЬ СОХРАНЯЕТСЯ РЕЗУЛЬТАТ
    quantity INT,
    ...
);
```

---

## 📊 ПОШАГОВЫЙ ПРИМЕР

### Вы создаёте товар:

```
1. Выбираете Шаблон: "Доска"
   → Загружается формула: "length * width * height / 1000000"
   → Генерируются поля: length, width, height

2. Вводите: length = 2000 мм
   → calculated_volume = "Заполните все поля..."
   → Вызывается: testFormula({length: 2000})
   → Результат: ОШИБКА (не хватает width, height)

3. Вводите: width = 100 мм
   → calculated_volume = "Заполните все поля..."
   → Вызывается: testFormula({length: 2000, width: 100})
   → Результат: ОШИБКА (не хватает height)

4. Вводите: height = 25 мм
   → calculated_volume = 5.0000 ✓
   → Вызывается: testFormula({length: 2000, width: 100, height: 25})
   → Результат: SUCCESS! (5)

5. Нажимаете СОХРАНИТЬ
   → CreateProduct::mutateFormDataBeforeCreate()
   → Финальный расчет: testFormula({length: 2000, width: 100, height: 25}) → 5
   → INSERT INTO products (calculated_volume: 5.0000, ...)
   → Товар создан! ✓
```

---

## 🧮 КАК РАБОТАЕТ testFormula()

```php
// ВЫ ВЫЗЫВАЕТЕ:
$template->testFormula([
    'length' => 2000,
    'width' => 100,
    'height' => 25
]);

// ВНУТРИ ФУНКЦИИ:
// 1. Извлекаются переменные из формулы
//    "length * width * height / 1000000"
//    → ['length', 'width', 'height']

// 2. Проверяется, что все заполнены
//    ✓ length = 2000
//    ✓ width = 100
//    ✓ height = 25

// 3. Подставляются значения
//    "2000 * 100 * 25 / 1000000"

// 4. Вычисляется результат
//    2000 * 100 = 200 000
//    200 000 * 25 = 5 000 000
//    5 000 000 / 1 000 000 = 5.0

// 5. Возвращается результат
// [
//     'success' => true,
//     'error' => null,
//     'result' => 5.0
// ]
```

---

## 💡 ВАЖНЫЕ ДЕТАЛИ

### Атрибуты хранятся как JSON

```php
// В БД в таблице products:
$product->attributes; // Массив, но в БД как JSON

// Когда вы получаете товар:
$product = Product::find(1);
$product->attributes; // Автоматически преобразуется в массив
// ['length' => 2000, 'width' => 100, 'height' => 25]
```

### calculated_volume — это за ОДНУ единицу

```php
// Формула рассчитывает объем за 1 единицу
$product->calculated_volume; // 5.0000 (за 1 штуку)

// Общий объем = calculated_volume * quantity
$product->getTotalVolume(); // 5.0000 * 100 = 500.0000 м³
```

### Валидация значения

```php
// Максимальное значение: 999999999.9999 (decimal(15,4))
if ($calculated_volume > 999999999.9999) {
    // Ошибка: значение слишком большое
    $set('calculated_volume', 'Объем превышает максимальное значение');
}
```

---

## 🎯 ЧТО НУЖНО ЗНАТЬ РАЗРАБОТЧИКУ

### Если изменяете формулу в шаблоне:

```php
// ДО: "length * width * height / 1000000"
// ПОСЛЕ: "length * width * height * 0.000001"

// Старые товары с этим шаблоном:
// - НЕ пересчитываются автоматически
// - Их calculated_volume остаётся старым
// - При редактировании товара → пересчет → новое значение
```

### Если создаёте новый товар:

```php
// 1. Выбираете шаблон
// 2. Заполняете характеристики
// 3. calculated_volume рассчитывается автоматически
// 4. Сохраняете → calculated_volume сохраняется в БД

// Больше ничего делать не нужно!
```

### Если редактируете товар:

```php
// 1. Изменяете характеристики
// 2. calculated_volume пересчитывается автоматически
// 3. Сохраняете → новое значение сохраняется в БД
```

### Если нужно пересчитать старые товары:

```php
// НИКОГДА не делайте UPDATE напрямую!
// Используйте метод модели:

Product::find(1)->updateCalculatedVolume();
// или
$product = Product::find(1);
$product->updateCalculatedVolume(); // Пересчет + сохранение
```

---

## 🚀 ФАЙЛЫ, ГДЕ ПРОИСХОДИТ МАГИЯ

| Файл | Строка | Что происходит |
|------|--------|---|
| ProductResource.php | 123 | Выбор шаблона → генерация полей |
| ProductResource.php | 152 | Изменение quantity → пересчет |
| ProductResource.php | 268 | Изменение характеристики → пересчет |
| ProductResource.php | 607 | Отображение calculated_volume |
| ProductTemplate.php | 68 | testFormula() — главный расчет |
| ProductTemplate.php | 145 | extractVariablesFromFormula() |
| ProductTemplate.php | 167 | evaluateExpression() |
| ProductTemplate.php | 196 | parseExpression() — парсер |
| CreateProduct.php | 17 | mutateFormDataBeforeCreate() |
| CreateProduct.php | 59 | Финальный расчет перед сохранением |
| EditProduct.php | 21 | mutateFormDataBeforeSave() |
| EditProduct.php | 46 | Финальный расчет при редактировании |
| Product.php | 161 | setCalculatedVolumeAttribute() — валидация |
| Product.php | 336 | calculateVolume() — публичный метод |
| Product.php | 385 | updateCalculatedVolume() — пересчет + сохранение |
| Product.php | 395 | getTotalVolume() — total_volume = cv * qty |

---

## 📌 ЗАПОМНИТЕ

```
ВХОД:
  Шаблон с формулой: "length * width * height / 1000000"
  Атрибуты товара: {length: 2000, width: 100, height: 25}

ПРОЦЕСС:
  ProductTemplate::testFormula($attributes)
  → Подставить значения в формулу
  → Безопасно вычислить
  → Вернуть результат

ВЫХОД:
  calculated_volume: 5.0000 м³ (за 1 единицу)
  
СОХРАНЕНИЕ:
  INSERT INTO products (calculated_volume: 5.0000, ...)
  
ИСПОЛЬЗОВАНИЕ:
  total_volume = 5.0000 * quantity
  total_volume = 5.0000 * 100 = 500 м³
```

---

## ❓ ЧАСТЫЕ ВОПРОСЫ

### Q: Где я вижу calculated_volume?
A: В форме создания/редактирования товара как "Рассчитанный объем" (disabled поле)

### Q: Почему calculated_volume равен null?
A: Потому что не заполнены все необходимые характеристики

### Q: Может ли я вручную изменить calculated_volume?
A: Нет, это disabled поле. Оно рассчитывается автоматически.

### Q: Как пересчитать calculated_volume для старого товара?
A: Откройте товар в форме, изменитесь любое поле (или просто сохраните) → пересчет

### Q: Что если я меняю формулу в шаблоне?
A: Новые товары будут использовать новую формулу. Старые товары нужно пересчитать вручную.

### Q: Как рассчитать общий объем?
A: `total_volume = calculated_volume * quantity`

---

## 🎬 ПОСЛЕДОВАТЕЛЬНОСТЬ СОБЫТИЙ

```
СОЗДАНИЕ ТОВАРА:
  1. Открыть форму
  2. Выбрать шаблон
  3. Заполнить характеристики
  4. Нажать СОХРАНИТЬ
  5. CreateProduct::mutateFormDataBeforeCreate()
     └─ testFormula()
     └─ data['calculated_volume'] = result
  6. Product::create($data)
  7. Товар создан с calculated_volume ✓

РЕДАКТИРОВАНИЕ ТОВАРА:
  1. Открыть товар в форме
  2. Изменить характеристику
  3. calculated_volume пересчитывается (live)
  4. Нажать СОХРАНИТЬ
  5. EditProduct::mutateFormDataBeforeSave()
     └─ testFormula()
     └─ data['calculated_volume'] = result
  6. $product->update($data)
  7. calculated_volume обновлён ✓
```

---

## 🎓 ЗАКЛЮЧЕНИЕ

**calculated_volume** — это автоматический расчёт, который:
- ✅ Происходит в real-time при заполнении формы
- ✅ Основан на формуле из шаблона
- ✅ Использует значения введённых характеристик
- ✅ Сохраняется в БД при создании/редактировании товара
- ✅ Используется для расчёта общих объёмов
- ✅ Не может быть изменён вручную

**Вам не нужно беспокоиться об этом — система всё делает сама!** 🚀

