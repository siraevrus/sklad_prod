# Поиск по номеру транспортного средства в API /api/products

## Описание

В API `/api/products` добавлена возможность **поиска по номеру транспортного средства** (номеру машины).

Теперь пользователь может ввести номер машины в поле поиска и найти все товары, которые везла эта машина, среди всех товаров на складе.

## Как это работает

### API запрос

```bash
# Получить товары по номеру транспортного средства
GET /api/products?search=А123БВ77&per_page=15&page=1

# Или с фильтром по статусу
GET /api/products?search=А123БВ77&status=in_stock&per_page=15

# Или агрегированный список (для выбора при создании продажи)
GET /api/products?search=А123БВ77&aggregate=true&status=in_stock
```

### Пример ответа

```json
{
  "data": [
    {
      "id": 5,
      "name": "Доска обрезная: 50 x 150 x 4000",
      "transport_number": "А123БВ77",
      "warehouse_id": 2,
      "quantity": 100,
      "status": "in_stock",
      "producer": {
        "id": 3,
        "name": "ООО Производство"
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 1
  }
}
```

## Технические изменения

### Файл: `app/Http/Controllers/Api/ProductController.php`

**Изменения в двух методах:**

#### 1. Метод `index()` (строка 34-40)
Добавлена строка поиска по номеру транспорта:
```php
->orWhere('transport_number', 'like', "%{$search}%")
```

#### 2. Метод `getAggregatedProducts()` (строка 148-158)
Добавлен блок поиска:
```php
if ($request->filled('search')) {
    $search = $request->input('search');
    $baseQuery->where(function ($q) use ($search) {
        $q->where('name', 'like', "%{$search}%")
            ->orWhere('transport_number', 'like', "%{$search}%")
            ->orWhereHas('producer', function ($subQ) use ($search) {
                $subQ->where('name', 'like', "%{$search}%");
            });
    });
}
```

## Параметры поиска

Теперь поле поиска (`search`) ищет по:

| Поле | Тип | Пример |
|------|-----|--------|
| `name` | текст | "Доска" |
| `description` | текст | "обрезная" |
| `transport_number` | текст | "А123БВ77" |
| `producer.name` | текст | "ООО Производство" |

## Примеры использования

### Поиск по номеру машины
```bash
curl -H "Authorization: Bearer TOKEN" \
  'https://warehouse.expwood.ru/api/products?search=А123БВ77&per_page=15&page=1'
```

### Поиск товара в пути по номеру транспорта
```bash
curl -H "Authorization: Bearer TOKEN" \
  'https://warehouse.expwood.ru/api/products?search=А123БВ77&status=in_transit&per_page=15'
```

### Поиск в остатках по номеру транспорта
```bash
curl -H "Authorization: Bearer TOKEN" \
  'https://warehouse.expwood.ru/api/products?search=А123БВ77&status=in_stock&per_page=15'
```

### Flutter пример
```dart
// Поиск товаров по номеру транспорта
final response = await http.get(
  Uri.parse('https://warehouse.expwood.ru/api/products?search=А123БВ77&per_page=15'),
  headers: {
    'Authorization': 'Bearer $token',
  },
);

if (response.statusCode == 200) {
  final data = jsonDecode(response.body);
  final products = data['data'] as List;
  print('Найдено товаров: ${products.length}');
}
```

## Совместимость

- ✅ Работает на основном запросе без агрегации
- ✅ Работает на агрегированном запросе для выбора при создании продажи
- ✅ Работает вместе с фильтрами по статусу
- ✅ Полностью обратно совместимо
- ✅ Не нарушает существующий функционал

## Сценарии использования

### Сценарий 1: Администратор ищет товар по номеру машины
Администратор хочет увидеть, какие товары везла машина "А123БВ77":
```
GET /api/products?search=А123БВ77
```

### Сценарий 2: Склад ищет товар в пути
Работник склада хочет найти товар, который еще в пути, по номеру машины:
```
GET /api/products?search=А123БВ77&status=in_transit
```

### Сценарий 3: Создание продажи с поиском
При создании продажи пользователь ищет товар по номеру машины:
```
GET /api/products?search=А123БВ77&aggregate=true&status=in_stock
```

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
# Протестировать поиск по номеру транспорта
curl -H "Authorization: Bearer TOKEN" \
  'https://warehouse.expwood.ru/api/products?search=А123БВ77&per_page=15' | jq '.data[0]'

# Должен вернуть товар с transport_number: "А123БВ77"
```

## История изменений

- **2025-10-21** - добавлен поиск по номеру транспортного средства в /api/products (коммит: `c871588`)
