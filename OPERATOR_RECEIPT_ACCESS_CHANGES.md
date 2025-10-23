# Предоставление прав Operator на редактирование карточки товара в Поступлении

**Дата:** 23 октября 2025  
**Статус:** ✅ Готово

## 📋 Обзор изменений

Роли **Operator (Оператор ПК)** были предоставлены права на **просмотр, редактирование и обновление** карточек товара в разделе **Поступление товара**.

## 🔄 Внесенные изменения

### 1. **app/UserRole.php** (Enum ролей)

Добавлено разрешение `'product_receipt'` в список разрешений для роли `Operator`:

```php
self::OPERATOR => [
    'products',
    'inventory',
    'products_in_transit',
    'product_receipt',  // ✨ Новое разрешение
],
```

**Ранее доступные разделы для Operator:**
- ✅ `products` (Товары)
- ✅ `inventory` (Остатки)
- ✅ `products_in_transit` (Товар в пути)

**Теперь добавлено:**
- ✅ `product_receipt` (Поступление товара)

---

### 2. **app/Filament/Resources/ReceiptResource.php** (Ресурс Приемки)

#### canViewAny()
Добавлена роль `'operator'` для просмотра раздела:

```php
public static function canViewAny(): bool
{
    $user = Auth::user();
    if (! $user) {
        return false;
    }

    // Приемка доступна админу, оператору и работнику склада
    return in_array($user->role->value, [
        'admin',
        'operator',        // ✨ Добавлено
        'warehouse_worker',
    ]);
}
```

#### canEdit()
Добавлена роль `'operator'` для редактирования карточки:

```php
public static function canEdit($record): bool
{
    $user = Auth::user();
    if (! $user) {
        return false;
    }

    // Редактирование доступно админу, оператору и работнику склада
    return in_array($user->role->value, [
        'admin',
        'operator',        // ✨ Добавлено
        'warehouse_worker',
    ]);
}
```

---

### 3. **app/Filament/Resources/ReceiptResource/Pages/EditReceipt.php** (Страница редактирования)

Добавлена роль `'operator'` в метод `canEdit()`:

```php
public static function canEdit($record): bool
{
    $user = \Illuminate\Support\Facades\Auth::user();
    if (! $user) {
        return false;
    }

    // Редактирование доступно админу, оператору и работнику склада
    return in_array($user->role->value, [
        'admin',
        'operator',        // ✨ Добавлено
        'warehouse_worker',
    ]);
}
```

---

### 4. **app/Policies/ProductPolicy.php** (Политика авторизации)

✅ **Уже поддерживает Operator!**

Политика уже содержит логику, которая разрешает Operator создавать и обновлять товары:

```php
public function create(User $user): bool
{
    return in_array($user->role, [
        UserRole::ADMIN,
        UserRole::OPERATOR,        // ✅ Уже поддерживается
        UserRole::WAREHOUSE_WORKER,
        UserRole::SALES_MANAGER
    ]);
}

public function update(User $user, Product $product): bool
{
    if ($user->role === UserRole::ADMIN) {
        return true;
    }

    // Остальные пользователи могут обновлять только товары на своем складе
    return (int) $product->warehouse_id === (int) $user->warehouse_id;
}
```

---

## 🧪 Тестирование

Созданы unit тесты в файле `tests/Unit/OperatorReceiptAccessTest.php`:

```php
✓ operator can view receipt resource          // Оператор может просматривать
✓ operator can edit receipt product            // Оператор может редактировать
✓ operator has product receipt permission      // Оператор имеет разрешение
✓ admin can edit receipt product               // Админ может редактировать
✓ warehouse worker can edit receipt product    // Работник склада может редактировать

Tests: 5 passed (8 assertions)
```

**Запуск тестов:**
```bash
php artisan test tests/Unit/OperatorReceiptAccessTest.php
```

---

## 📊 Таблица доступа после изменений

### Раздел: Поступление товара (ReceiptResource)

| Роль | Просмотр | Редактирование | Примечание |
|------|----------|----------------|-----------|
| ✅ Admin | Да | Да | Полный доступ |
| ✅ Operator | **Да** | **Да** | **Новое!** |
| ✅ Warehouse Worker | Да | Да | Существующий доступ |
| ❌ Sales Manager | Нет | Нет | Нет доступа |

---

## 🎯 Доступные операции для Operator

### В разделе Поступление товара:

1. **Просмотр списка товаров в приемке** ✅
   - Фильтрация по складу
   - Поиск по наименованию
   - Сортировка

2. **Просмотр деталей товара** ✅
   - Все информационные данные
   - Документы
   - Характеристики

3. **Редактирование карточки товара** ✅
   - Изменение основной информации
   - Обновление количества
   - Изменение производителя
   - Добавление заметок
   - Загрузка документов

4. **Обновление данных товара** ✅
   - Сохранение изменений
   - Автоматический расчет объема
   - Валидация данных

---

## 🔒 Ограничения доступа

Следующие операции **остаются ограниченными** для Operator:

- ❌ Удаление товара (только для Admin)
- ❌ Восстановление удаленного товара (только для Admin)
- ❌ Принятие товара (через кнопку "Принять товар" - выполняется действие, но рекомендуется проверить)

---

## 📝 Правила доступа на уровне склада

Operator может редактировать **только товары своего склада**:

```php
// В ReceiptResource::getEloquentQuery()
if ($user->warehouse_id) {
    return $base->where('warehouse_id', $user->warehouse_id);
}
```

Если Operator назначен на Склад #1, он может редактировать только товары Склада #1.

---

## 🚀 Развертывание

Для применения изменений на боевом сервере:

1. **Загрузить изменения:**
   ```bash
   git pull origin main
   ```

2. **Запустить миграции** (если они есть):
   ```bash
   php artisan migrate
   ```

3. **Очистить кеш** (рекомендуется):
   ```bash
   php artisan config:cache
   php artisan route:cache
   ```

4. **Проверить логи:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

---

## 🔍 Проверка в Filament Admin Panel

1. Авторизоваться как Operator
2. Перейти в раздел "Приемка" → "Приемка товаров"
3. Нажать на товар для просмотра
4. Проверить наличие кнопки "Редактировать" или перейти по URL `/admin/receipts/{id}/edit`
5. Убедиться что все поля доступны для редактирования

---

## 📚 Документация API

Если используется API:

**Endpoint для обновления товара:**
```
PUT /api/products/{id}
```

**Требуемые права:**
- Operator может обновлять товары своего склада
- Admin может обновлять товары всех складов

---

## ✅ Контрольный список

- ✅ UserRole enum обновлен
- ✅ ReceiptResource обновлен (canViewAny + canEdit)
- ✅ EditReceipt страница обновлена
- ✅ ProductPolicy не требует изменений (уже поддерживает)
- ✅ Unit тесты созданы и проходят
- ✅ Код отформатирован (Pint)
- ✅ Коммит произведен
- ✅ Нет linter ошибок

---

## 📞 Контакты

По вопросам или проблемам обращайтесь к администратору системы.

**Дата завершения:** 23 октября 2025
