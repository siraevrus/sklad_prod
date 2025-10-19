# Калькулятор - Быстрая Справка 🚀

## 📍 Где находится код?

| Файл | Строки | Описание |
|------|--------|---------|
| `app/Support/ProductAttributeCalculator.php` | 1-188 | Главный класс для всех расчётов |
| `app/Models/ProductTemplate.php` | 68-311 | Вычисление формул и парсинг выражений |
| `app/Filament/Resources/ReceiptResource.php` | 186-238, 573-642 | Форма приёмки товара |
| `app/Filament/Resources/SaleResource.php` | 61-78 | Форма расчёта суммы продажи |
| `app/Models/Product.php` | - | Модель товара |
| `app/Models/Sale.php` | - | Модель продажи |

---

## 🎯 Вопрос → Ответ (QA)

### Q: Как работает калькулятор объёма при вводе характеристики?
**A:**
1. Пользователь вводит значение (например, "50,5")
2. Livewire ждёт 400ms без изменений (`debounce: 400`)
3. Вызывается `afterStateUpdated` → `calculateVolumeForItem()`
4. Данные передаются в `ProductAttributeCalculator::calculateAndUpdate()`
5. Атрибуты извлекаются и нормализуются (`50,5` → `50.5`)
6. Вызывается `ProductTemplate::testFormula()` с числовыми значениями
7. Формула вычисляется безопасным парсером (не `eval()`)
8. Результат отображается в поле "Объем" (readonly)

---

### Q: Какая нормализация происходит с числами?
**A:**
```php
// Входные данные могут быть:
'attribute_width' => '50,5'     // запятая вместо точки
'attribute_height' => '2.5'     // точка (норма)
'attribute_length' => '200'     // целое число

// После нормализации:
$normalizedValue = str_replace(',', '.', $value);
// Результат: ['width' => 50.5, 'height' => 2.5, 'length' => 200.0]
```

---

### Q: Что такое "is_in_formula" и как он влияет на имя товара?
**A:**
- **is_in_formula = true**: характеристика участвует в ФОРМУЛЕ расчёта
  - Пример: length, width, height (для объёма)
  - Отображаются в имени с крестиками: "Доска: 200 x 50.5 x 2.5"
  
- **is_in_formula = false**: характеристика НЕ участвует в формуле
  - Пример: wood_type, surface_type (описание)
  - Отображаются в имени с запятыми: "Доска: 200 x 50.5 x 2.5, Сосна"

---

### Q: Где находится формула товара?
**A:**
```php
// В таблице product_templates
// Поле: formula
// Пример значения: "length * width * height / 1000"

// Таблица: product_templates
// Столбцы:
// - id
// - name (Доска)
// - formula (length * width * height / 1000)
// - unit (м³)
// - description
// - is_active
```

---

### Q: Как вычисляется формула? Используется ли eval()?
**A:**
**НЕТ**, используется БЕЗОПАСНЫЙ рекурсивный парсер:

```php
// НЕБЕЗОПАСНЫЙ способ (старый код в ReceiptResource):
$result = eval("return $expression;");  // ❌ ОПАСНО!

// БЕЗОПАСНЫЙ способ (ProductTemplate::testFormula):
$result = $this->evaluateExpression($expression);  // ✅ БЕЗОПАСНО
  │
  ├─ validateExpression() - проверка regex
  │  └─ /^[0-9+\-*\/\(\)\.]+$/ (только цифры и операторы)
  │
  ├─ parseExpression() - парсинг со скобками
  │
  └─ evaluateExpressionRecursive() - рекурсивное вычисление
     ├─ Обработка скобок
     ├─ Обработка * и / (приоритет 1)
     ├─ Обработка + и - (приоритет 2)
     └─ Возврат числового результата
```

---

### Q: Что такое "debounce" и почему он нужен?
**A:**
```php
->live(debounce: 400)  // Задержка 400ms перед отправкой

// Сценарий БЕЗ debounce:
// Пользователь вводит "200" (3 символа)
// Отправляется 3 AJAX-запроса на каждый символ
// Сервер перегружен, медленно ✗

// Сценарий С debounce: 400ms
// Пользователь вводит "200" (3 символа за 100ms)
// Система ждёт 400ms после последнего символа
// Отправляется 1 AJAX-запрос для "200"
// Сервер спокойно, быстро ✓
```

---

### Q: Какая реактивность используется в обоих разделах?
**A:**
```php
// Поступление товара:
->live(debounce: 400)
// ✓ Быстрая обратная связь при вводе размеров
// ✗ Может быть много calculs if числа меняются часто

// Реализация (Продажа):
->live(debounce: 400)  # ← ОБНОВЛЕНО: было live(onBlur: true)
```

---

## 💻 Copy-Paste Код

### Копировать: Вызов калькулятора

```php
// В afterStateUpdated() метода поля:
->afterStateUpdated(function (Set $set, Get $get) {
    $templateId = $get('product_template_id');
    $quantity = $get('quantity');
    $formData = $get();
    
    ProductAttributeCalculator::calculateAndUpdate($set, $formData, $templateId, $quantity);
})
```

### Копировать: Нормализация запятых/точек

```php
// Для любого числового значения:
$normalizedValue = is_string($value) 
    ? str_replace(',', '.', $value) 
    : $value;

// Использование в целом числе:
$numericAttributes = [];
foreach ($attributes as $key => $value) {
    if (is_numeric($value) && $value > 0) {
        $numericAttributes[$key] = (float) $value;
    }
}
```

### Копировать: Валидация числового поля

```php
TextInput::make('attribute_width')
    ->label('Ширина (см)')
    ->inputMode('decimal')
    ->maxLength(10)
    ->required($attribute->is_required)
    ->rules(['regex:/^\d*([.,]\d*)?$/'])  // ← Валидация
    ->validationMessages([
        'regex' => 'Поле должно содержать только цифры и одну запятую или точку',
    ])
    ->live(debounce: 400)
    ->afterStateUpdated(function (Set $set, Get $get) {
        self::calculateVolumeForItem($set, $get);
    })
```

### Копировать: Расчёт суммы в Реализации

```php
private static function calculateTotalPrice(Set $set, Get $get): void
{
    $cashRaw = (string) ($get('cash_amount') ?? '0');
    $nocashRaw = (string) ($get('nocash_amount') ?? '0');
    
    $cashAmount = (float) str_replace(',', '.', $cashRaw);
    $nocashAmount = (float) str_replace(',', '.', $nocashRaw);
    $totalPrice = $cashAmount + $nocashAmount;
    
    $set('total_price', $totalPrice);
    
    Log::info('Sale form: calculateTotalPrice', [
        'cash_amount' => $cashAmount,
        'nocash_amount' => $nocashAmount,
        'total_price' => $totalPrice,
    ]);
}
```

---

## 🔍 Отладка

### Как проверить, работает ли калькулятор?

1. **Откройте DevTools (F12)**
2. **Вкладка Network**
3. **Вводите значение в поле характеристики**
4. **Ищите AJAX-запрос "wire" (Livewire)**
5. **Проверьте Response - должен содержать calculated_volume**

### Как посмотреть логи расчёта?

```php
// storage/logs/laravel.log

// Поиск логов:
tail -f storage/logs/laravel.log | grep "Volume calculated"
tail -f storage/logs/laravel.log | grep "Sale form"
```

### Как протестировать формулу?

1. **Перейдите в админ панель → Шаблоны товаров**
2. **Откройте шаблон**
3. **Внизу кнопка "Тестировать формулу"**
4. **Введите тестовые значения и кликните**

---

## ⚠️ Частые Ошибки

### Ошибка 1: Нормализация пробелов в суммах

```php
// ❌ НЕПРАВИЛЬНО:
$cashAmount = (float) str_replace(',', '.', '1 000,00');
// Результат: (float) '1 000.00' = 1.0 ← НЕПРАВИЛЬНО!

// ✅ ПРАВИЛЬНО:
$cashAmount = (float) str_replace([' ', ','], [''  , '.'], '1 000,00');
// Результат: (float) '1000.00' = 1000.0 ← ПРАВИЛЬНО!
```

### Ошибка 2: Использование eval()

```php
// ❌ ОПАСНО:
$result = eval("return $expression;");

// ✅ БЕЗОПАСНО:
$result = $template->testFormula($attributes);
```

### Ошибка 3: Забыли debounce на характеристике

```php
// ❌ БЕЗ DEBOUNCE (много запросов):
->live()  // отправляет при каждом символе

// ✅ С DEBOUNCE (оптимально):
->live(debounce: 400)  // ждёт 400ms
```

### Ошибка 4: Неправильное вычисление приоритета операций

```php
// Формула: "2 + 3 * 4"
// ❌ НЕПРАВИЛЬНО: 2 + 3 * 4 = 5 * 4 = 20
// ✅ ПРАВИЛЬНО: 2 + 3 * 4 = 2 + 12 = 14
// Причина: * имеет больший приоритет
```

---

## 📚 Документация

- **Основной файл:** `CALCULATOR_MECHANISM.md` - полная документация
- **Этот файл:** `CALCULATOR_QUICK_REFERENCE.md` - быстрая справка
- **API Docs:** Check `API_DOCUMENTATION.md` for endpoints

---

## 🧪 Тестирование

### Unit тест для ProductTemplate::testFormula()

```php
public function test_product_template_formula_calculation(): void
{
    $template = ProductTemplate::factory()
        ->has(ProductAttribute::factory()->count(3))
        ->create([
            'formula' => 'length * width * height / 1000',
        ]);
    
    $result = $template->testFormula([
        'length' => 200,
        'width' => 50,
        'height' => 2,
    ]);
    
    $this->assertTrue($result['success']);
    $this->assertEquals(20.0, $result['result']);
}
```

### Feature тест для ReceiptResource

```php
public function test_receipt_resource_calculates_volume(): void
{
    $template = ProductTemplate::factory()
        ->has(ProductAttribute::factory()->count(3))
        ->create(['formula' => 'length * width * height / 1000']);
    
    livewire(CreateReceipt::class)
        ->fillForm([
            'product_template_id' => $template->id,
            'quantity' => 10,
            'attribute_length' => '200',
            'attribute_width' => '50',
            'attribute_height' => '2',
        ])
        ->assertSee('20.000')  // Проверяем, что объём рассчитан
        ->call('create')
        ->assertRedirect();
}
```

---

## 🎓 Обучение новичков

### Шаг 1: Понять поток данных
Прочитайте "ДИАГРАММА 1" в `CALCULATOR_MECHANISM.md`

### Шаг 2: Посмотреть исходный код
- Откройте `app/Support/ProductAttributeCalculator.php`
- Найдите метод `calculateAndUpdate()`
- Прочитайте каждый шаг в комментариях

### Шаг 3: Trace through the code
```bash
# В productInTransitResource.php найдите:
private static function calculateVolumeForItem(Set $set, Get $get): void
{
    # Это точка входа в калькулятор
}

# Что она делает:
# 1. Получает templateId
# 2. Получает количество
# 3. Получает все данные формы
# 4. Вызывает ProductAttributeCalculator::calculateAndUpdate()
```

### Шаг 4: Практика
1. Создайте новый шаблон товара с формулой
2. Добавьте товар в приёмку
3. Откройте DevTools и смотрите AJAX запросы
4. Наблюдайте, как обновляется calculated_volume
5. Проверьте логи: `tail -f storage/logs/laravel.log`

---

## 🔗 Связанные классы

```
ProductAttributeCalculator (main logic)
    ↓
ProductTemplate (formula parsing & evaluation)
    ↓
ProductAttribute (characteristic definitions)
    ↓
Product (storage of calculated values)
    ↓
ReceiptResource / SaleResource (UI forms)
    ↓
Livewire (real-time updates)
```

---

## 💡 Pro Tips

1. **Кешируйте шаблоны**, если много расчётов:
   ```php
   private static array $templateCache = [];
   ```

2. **Логируйте в debug режиме**, не в продакшене:
   ```php
   if (config('app.debug')) {
       Log::debug('Volume calculated', [...]);
   }
   ```

3. **Используйте transactions для atomic saves**:
   ```php
   DB::transaction(function () {
       Product::create($data);
       // Уверены, что всё сохранится или ничего
   });
   ```

4. **Добавьте кеширование результатов** для одинаковых входов:
   ```php
   $cacheKey = md5(json_encode($numericAttributes));
   $result = Cache::remember($cacheKey, 3600, fn () => $template->testFormula(...));
   ```

---

**Последнее обновление:** October 19, 2025  
**Версия:** 1.0  
**Автор:** AI Assistant

> **ПРИМЕЧАНИЕ:** В версии от 19.10.2025 унифицирована реактивность на `live(debounce: 400)` для обоих разделов (Поступление товара и Реализация). Ранее Реализация использовала `live(onBlur: true)`, но это было изменено для консистентности и лучшего UX. Теперь расчёт происходит автоматически при вводе, без необходимости кликать.

