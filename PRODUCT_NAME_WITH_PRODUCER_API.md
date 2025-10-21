# Добавление производителя в название товара при выборе для продажи

## Описание

При создании новой реализации (продажи) в выпадающем поле **Товар**, теперь автоматически добавляется **производитель** к названию товара.

## Было
```
"name": "Пиломатериалы: 95 x 200 x 6000: 2, Транспортная, Сосна"
```

## Стало
```
"name": "Пиломатериалы: 95 x 200 x 6000: 2, Транспортная, Сосна, Производитель XYZ"
```

## Как это работает

### API эндпоинт
**GET /api/products?aggregate=true&status=in_stock&warehouse_id={warehouse_id}**

Этот эндпоинт возвращает агрегированный список товаров для выбора при создании продажи.

### Пример ответа API

```json
{
  "data": [
    {
      "product_template_id": 1,
      "composite_product_key": "1|1|5|Пиломатериалы: 95 x 200 x 6000: 2, Транспортная, Сосна",
      "name": "Пиломатериалы: 95 x 200 x 6000: 2, Транспортная, Сосна, Производитель XYZ",
      "warehouse": "Главный склад",
      "producer": "Производитель XYZ",
      "quantity": 100,
      "available_quantity": 85,
      "sold_quantity": 15,
      "total_volume": 1020.5
    },
    {
      "product_template_id": 2,
      "composite_product_key": "2|1|6|Доска обрезная: 50 x 150 x 4000",
      "name": "Доска обрезная: 50 x 150 x 4000, Производитель ABC",
      "warehouse": "Главный склад",
      "producer": "Производитель ABC",
      "quantity": 250,
      "available_quantity": 230,
      "sold_quantity": 20,
      "total_volume": 2750.0
    }
  ]
}
```

## Технические изменения

### Файл: `app/Http/Controllers/Api/ProductController.php`

**Метод:** `getAggregatedProducts()` (строка 127-218)

**Что изменилось:**
- Добавлена логика формирования названия товара с производителем в методе форматирования данных
- Если у товара есть производитель, к названию добавляется строка: `, {producer_name}`

**Код:**
```php
$formattedData = $products->getCollection()->map(function ($product) {
    // Формируем имя товара с производителем
    $productName = $product->name;
    if ($product->producer) {
        $productName .= ', '.$product->producer->name;
    }

    return [
        'product_template_id' => $product->product_template_id,
        'composite_product_key' => "{$product->product_template_id}|{$product->warehouse_id}|{$product->producer_id}|{$product->name}",
        'name' => $productName,  // ← Здесь используется новое имя с производителем
        'warehouse' => $product->warehouse ? $product->warehouse->name : null,
        'producer' => $product->producer ? $product->producer->name : null,
        'quantity' => (float) $product->quantity,
        'available_quantity' => (float) $product->available_quantity,
        'sold_quantity' => (float) $product->sold_quantity,
        'total_volume' => $product->total_volume ? round((float) $product->total_volume, 3) : 0,
    ];
});
```

## Тесты

Добавлен новый тест: `test_aggregated_products_includes_producer_in_name()`

Тест проверяет:
- Получение списка агрегированных товаров через API
- Наличие производителя в названии товара
- Что название содержит имя производителя

## Влияние на клиентов (Flutter приложение)

### До изменений
При создании продажи в выпадающем списке товаров клиент видит только название товара без производителя:
```
Пиломатериалы: 95 x 200 x 6000: 2, Транспортная, Сосна
```

### После изменений
Теперь в выпадающем списке отображается название товара **с производителем**:
```
Пиломатериалы: 95 x 200 x 6000: 2, Транспортная, Сосна, Производитель XYZ
```

Это помогает пользователю легче идентифицировать товар при выборе, особенно если одинаковые товары от разных производителей.

## Совместимость

- ✅ Обратная совместимость полная
- ✅ Поле `composite_product_key` не изменилось (использует исходное имя)
- ✅ Поле `producer` отдельное (также доступно)
- ✅ Полностью опционально для клиентов

## Развертывание

1. На локальной машине:
```bash
git push origin main
```

2. На боевом сервере:
```bash
cd /var/www/sklad
git pull origin main
```

**Никаких дополнительных команд не требуется!**

## Тестирование

Для локального тестирования:

```bash
# Получить список товаров для выбора при создании продажи
curl -H "Authorization: Bearer TOKEN" \
  'https://example.com/api/products?aggregate=true&status=in_stock&warehouse_id=1' | jq '.data[0]'

# Результат должен показать название товара с производителем:
# "name": "Пиломатериалы: 95 x 200 x 6000: 2, Транспортная, Сосна, Производитель XYZ"
```

## Контакты

Для вопросов по реализации см. коммит: `09d37eb`
