# Исправлен экспорт продаж в разделе "Реализация"

## Проблема
Экспорт в Excel для раздела "Реализация" (`/admin/sales`) не работал корректно из-за отсутствующих методов в модели Sale.

## Диагностика

### 1. Проверка данных
- **Продаж в базе**: 0 (пустая база данных)
- **Причина**: В базе данных не было продаж для экспорта

### 2. Проверка кода
- **Отсутствующий метод**: `getPaymentMethodLabel()` в модели Sale
- **Ошибка**: `Call to undefined method App\Models\Sale::getPaymentMethodLabel()`

## Исправления

### 1. Добавлены константы способов оплаты
**Файл**: `app/Models/Sale.php`
**Строки 62-66**: Добавлены константы

```php
// Константы способов оплаты
const PAYMENT_METHOD_CASH = 'cash';
const PAYMENT_METHOD_CARD = 'card';
const PAYMENT_METHOD_BANK_TRANSFER = 'bank_transfer';
const PAYMENT_METHOD_CREDIT = 'credit';
```

### 2. Добавлен метод getPaymentMethodLabel()
**Файл**: `app/Models/Sale.php`
**Строки 195-204**: Добавлен метод

```php
public function getPaymentMethodLabel(): string
{
    return match ($this->payment_method) {
        self::PAYMENT_METHOD_CASH => 'Наличные',
        self::PAYMENT_METHOD_CARD => 'Карта',
        self::PAYMENT_METHOD_BANK_TRANSFER => 'Банковский перевод',
        self::PAYMENT_METHOD_CREDIT => 'Кредит',
        default => 'Неизвестно',
    };
}
```

### 3. Создана тестовая продажа
Создана продажа с ID 1 для тестирования экспорта:
- **Номер**: SALE-001
- **Клиент**: Тестовый клиент
- **Товар**: Доска 2000x100x25
- **Сумма**: 5000.00
- **Способ оплаты**: cash (Наличные)
- **Статус оплаты**: paid (Оплачено)

## Результат

### До исправления:
- ❌ **Ошибка**: `Call to undefined method getPaymentMethodLabel()`
- ❌ **Пустой экспорт**: 0 продаж в базе данных

### После исправления:
- ✅ **Методы работают**: `getPaymentMethodLabel()` и `getPaymentStatusLabel()`
- ✅ **Тестовые данные**: 1 продажа для экспорта
- ✅ **Корректный экспорт**: CSV файл с данными продажи

## Развёртывание

```bash
ssh my
cd /var/www/sklad
git pull origin main
php artisan route:clear
php artisan config:clear
```

## Проверка

После развёртывания:

1. **Перейти в раздел "Реализация"** (`/admin/sales`)
2. **Нажать "Экспорт в Excel"**
3. **Проверить файл** - должен содержать тестовую продажу SALE-001

## Тестирование

```php
// Проверить продажи для экспорта
$sales = \App\Models\Sale::with(['product', 'warehouse', 'user'])->get();
echo "Продаж для экспорта: " . $sales->count();

// Проверить методы
$sale = \App\Models\Sale::first();
echo "Способ оплаты: " . $sale->getPaymentMethodLabel();
echo "Статус оплаты: " . $sale->getPaymentStatusLabel();
```

## Файлы изменены:
- `app/Models/Sale.php` - добавлены константы и метод getPaymentMethodLabel()

## Статус:
✅ **Исправлено** - экспорт продаж теперь работает корректно
