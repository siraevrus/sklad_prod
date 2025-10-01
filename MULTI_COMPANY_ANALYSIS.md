# 📊 Отчет: Возможность добавления нескольких компаний в профиль сотрудника

**Дата анализа:** 1 октября 2025  
**Текущая архитектура:** Один пользователь → Одна компания → Один склад

---

## 📋 Краткое резюме

### ✅ **Вердикт:** Реализация возможна, но потребует ЗНАЧИТЕЛЬНЫХ изменений

**Сложность:** 🔴 **ВЫСОКАЯ** (7/10)  
**Объем работы:** ~40-50 часов разработки  
**Количество файлов для изменения:** ~45-50 файлов  
**Риски:** Средние (возможны ошибки в логике доступа)

---

## 🏗️ Текущая архитектура

### **Модель данных:**
```
User (Пользователь)
  ├─ company_id (одна компания)
  ├─ warehouse_id (один склад)
  └─ role (роль)

Company (Компания)
  └─ hasMany(Warehouse) → склады компании
  └─ hasMany(User) → сотрудники компании

Warehouse (Склад)
  └─ belongsTo(Company)
```

### **Принципы доступа:**
1. **Администратор** - видит все данные всех компаний
2. **Менеджер/Оператор** - видит только данные своей компании (`company_id`)
3. **Складской работник** - видит только данные своего склада (`warehouse_id`)

---

## 📊 Анализ использования company_id и warehouse_id

### **Статистика:**
- **`company_id` используется:** в 15 файлах, 47 вхождений
- **`warehouse_id` используется:** в 33 файлах, 179 вхождений

### **Места использования:**

#### 1. **Модели (4 файла):**
- `app/Models/User.php` - определение связей
- `app/Models/Company.php` - связь с пользователями
- `app/Models/Warehouse.php` - связь с компанией
- `app/Models/Sale.php`, `Product.php`, etc. - фильтрация по складу

#### 2. **API Контроллеры (7 файлов):**
- `app/Http/Controllers/Api/UserController.php` - фильтрация по company_id
- `app/Http/Controllers/Api/WarehouseController.php` - проверка warehouse_id доступа
- `app/Http/Controllers/Api/ProductController.php` - фильтрация товаров по складу
- `app/Http/Controllers/Api/SaleController.php` - проверка доступа к продажам
- `app/Http/Controllers/Api/RequestController.php` - фильтрация запросов
- `app/Http/Controllers/Api/StockController.php` - фильтрация остатков
- `app/Http/Controllers/Api/ReceiptController.php` - приемка товаров

#### 3. **Filament Resources (15 файлов):**
- `app/Filament/Resources/UserResource.php` - создание/редактирование пользователей
- `app/Filament/Resources/ProductResource.php` - фильтрация товаров
- `app/Filament/Resources/SaleResource.php` - фильтрация продаж
- `app/Filament/Resources/WarehouseResource.php` - управление складами
- `app/Filament/Resources/StockResource.php` - складские остатки
- И др. (еще 10 ресурсов)

#### 4. **Filament Pages (5 файлов):**
- `app/Filament/Pages/StockOverview.php` - обзор складов
- Create/Edit страницы для различных ресурсов

#### 5. **Policies (2 файла):**
- `app/Policies/UserPolicy.php` - права доступа к пользователям
- `app/Policies/ProductPolicy.php` - права доступа к товарам

#### 6. **Миграции (2 файла):**
- `database/migrations/2025_08_04_073800_add_role_and_company_fields_to_users_table.php`

---

## 🔄 Необходимые изменения для поддержки множества компаний

### **1. База данных (3-4 часа)**

#### **Создать новую таблицу `company_user` (many-to-many):**
```php
Schema::create('company_user', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->foreignId('company_id')->constrained()->onDelete('cascade');
    $table->boolean('is_primary')->default(false); // основная компания
    $table->string('role_in_company')->nullable(); // роль в конкретной компании
    $table->timestamps();
    
    $table->unique(['user_id', 'company_id']);
});
```

#### **Опционально: таблица `user_warehouse` для множества складов:**
```php
Schema::create('user_warehouse', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->foreignId('warehouse_id')->constrained()->onDelete('cascade');
    $table->boolean('is_primary')->default(false);
    $table->timestamps();
    
    $table->unique(['user_id', 'warehouse_id']);
});
```

#### **Миграция для существующих данных:**
- Перенести данные из `users.company_id` в `company_user`
- Пометить существующие связи как `is_primary = true`

---

### **2. Модель User (2-3 часа)**

#### **Изменения в `app/Models/User.php`:**

```php
// Убрать:
// protected $fillable = ['company_id', 'warehouse_id'];

// Добавить:
public function companies(): BelongsToMany
{
    return $this->belongsToMany(Company::class, 'company_user')
        ->withPivot('is_primary', 'role_in_company')
        ->withTimestamps();
}

public function warehouses(): BelongsToMany
{
    return $this->belongsToMany(Warehouse::class, 'user_warehouse')
        ->withPivot('is_primary')
        ->withTimestamps();
}

// Получить основную компанию
public function getPrimaryCompanyAttribute(): ?Company
{
    return $this->companies()->wherePivot('is_primary', true)->first();
}

// Получить основной склад
public function getPrimaryWarehouseAttribute(): ?Warehouse
{
    return $this->warehouses()->wherePivot('is_primary', true)->first();
}

// Получить ID всех компаний пользователя
public function getCompanyIdsAttribute(): array
{
    return $this->companies()->pluck('companies.id')->toArray();
}

// Получить ID всех складов пользователя
public function getWarehouseIdsAttribute(): array
{
    return $this->warehouses()->pluck('warehouses.id')->toArray();
}

// Проверить доступ к компании
public function hasAccessToCompany(int $companyId): bool
{
    if ($this->isAdmin()) {
        return true;
    }
    return $this->companies()->where('companies.id', $companyId)->exists();
}

// Проверить доступ к складу
public function hasAccessToWarehouse(int $warehouseId): bool
{
    if ($this->isAdmin()) {
        return true;
    }
    return $this->warehouses()->where('warehouses.id', $warehouseId)->exists();
}
```

**Поддержка обратной совместимости (accessor для старого кода):**
```php
// Для старого кода, который использует $user->company_id
public function getCompanyIdAttribute(): ?int
{
    return $this->primary_company?->id;
}

// Для старого кода, который использует $user->warehouse_id
public function getWarehouseIdAttribute(): ?int
{
    return $this->primary_warehouse?->id;
}
```

---

### **3. API Контроллеры (8-10 часов)**

#### **Файлы, требующие изменений:**

##### **3.1. UserController.php**
```php
// Было:
if (!$user->isAdmin()) {
    if ($user->warehouse_id) {
        $query->where('warehouse_id', $user->warehouse_id);
    }
}

// Станет:
if (!$user->isAdmin()) {
    $warehouseIds = $user->warehouse_ids;
    if (!empty($warehouseIds)) {
        $query->whereIn('warehouse_id', $warehouseIds);
    }
}
```

##### **3.2. WarehouseController.php**
```php
// Было:
if ($user->warehouse_id) {
    $query->where('id', $user->warehouse_id);
}

// Станет:
$warehouseIds = $user->warehouse_ids;
if (!empty($warehouseIds)) {
    $query->whereIn('id', $warehouseIds);
}
```

##### **3.3. ProductController.php**
```php
// Было:
if ($user->warehouse_id) {
    $query->where('warehouse_id', $user->warehouse_id);
}

// Станет:
$warehouseIds = $user->warehouse_ids;
if (!empty($warehouseIds)) {
    $query->whereIn('warehouse_id', $warehouseIds);
}
```

##### **3.4. SaleController.php**
```php
// Было:
if (!$user->isAdmin()) {
    if ($user->warehouse_id !== $request->warehouse_id) {
        return response()->json(['message' => 'Доступ запрещен'], 403);
    }
}

// Станет:
if (!$user->isAdmin()) {
    if (!$user->hasAccessToWarehouse($request->warehouse_id)) {
        return response()->json(['message' => 'Доступ запрещен'], 403);
    }
}
```

**Аналогичные изменения потребуются в:**
- RequestController.php
- StockController.php
- ReceiptController.php
- ProductTemplateController.php

---

### **4. Filament Resources (10-12 часов)**

#### **4.1. UserResource.php - добавить управление компаниями**
```php
Forms\Components\Select::make('companies')
    ->label('Компании')
    ->multiple()
    ->relationship('companies', 'name')
    ->preload()
    ->required(),

Forms\Components\Select::make('primary_company_id')
    ->label('Основная компания')
    ->relationship('companies', 'name')
    ->required(),

Forms\Components\Select::make('warehouses')
    ->label('Склады')
    ->multiple()
    ->relationship('warehouses', 'name')
    ->preload(),
```

#### **4.2. Обновить фильтрацию во всех Resources:**
- ProductResource.php
- SaleResource.php
- StockResource.php
- WarehouseResource.php
- RequestResource.php
- ProductInTransitResource.php
- ReceiptResource.php

**Пример изменения в getEloquentQuery():**
```php
// Было:
if ($user->company_id) {
    $query->whereHas('warehouse', function ($q) use ($user) {
        $q->where('company_id', $user->company_id);
    });
}

// Станет:
$companyIds = $user->company_ids;
if (!empty($companyIds)) {
    $query->whereHas('warehouse', function ($q) use ($companyIds) {
        $q->whereIn('company_id', $companyIds);
    });
}
```

#### **4.3. Создание записей (Create Pages):**
Обновить логику в Pages/Create*.php файлах:
- CreateProduct.php
- CreateSale.php
- CreateRequest.php
- CreateProductInTransit.php

---

### **5. Policies (2-3 часа)**

#### **5.1. UserPolicy.php**
```php
// Было:
return $user->company_id === $model->company_id;

// Станет:
return $user->hasAccessToCompany($model->primary_company->id ?? 0);
```

#### **5.2. ProductPolicy.php**
```php
// Было:
return (int) $product->warehouse_id === (int) $user->warehouse_id;

// Станет:
return $user->hasAccessToWarehouse($product->warehouse_id);
```

---

### **6. Filament Pages (3-4 часа)**

#### **6.1. StockOverview.php**
```php
// Было:
if ($user->company_id) {
    $query->whereHas('warehouse', function ($q) use ($user) {
        $q->where('company_id', $user->company_id);
    });
}

// Станет:
$companyIds = $user->company_ids;
if (!empty($companyIds)) {
    $query->whereHas('warehouse', function ($q) use ($companyIds) {
        $q->whereIn('company_id', $companyIds);
    });
}
```

---

### **7. Экспорт контроллеры (2 часа)**
- ProductExportController.php
- SaleExportController.php

---

### **8. Factories и Seeders (2 часа)**
Обновить фабрики для создания связей many-to-many

---

### **9. Тесты (5-6 часов)**
Переписать/обновить существующие тесты:
- Feature/UserTest.php
- Feature/ProductTest.php
- Feature/SaleTest.php
- Feature/WarehouseTest.php
- И другие...

---

### **10. API Документация (1-2 часа)**
Обновить OpenAPI спецификацию:
- Добавить новые эндпоинты для управления множественными компаниями
- Обновить схемы User

---

## 📈 Детальная оценка объема работ

| Категория | Файлов | Время (часы) | Сложность |
|-----------|--------|--------------|-----------|
| **База данных** | 3 | 3-4 | Средняя |
| **Модели** | 3 | 2-3 | Средняя |
| **API Контроллеры** | 7 | 8-10 | Высокая |
| **Filament Resources** | 15 | 10-12 | Высокая |
| **Filament Pages** | 5 | 3-4 | Средняя |
| **Policies** | 2 | 2-3 | Средняя |
| **Экспорт контроллеры** | 2 | 2 | Низкая |
| **Factories/Seeders** | 3 | 2 | Низкая |
| **Тесты** | 10+ | 5-6 | Средняя |
| **Документация** | 3 | 1-2 | Низкая |
| **Тестирование и отладка** | - | 5-8 | Высокая |
| **ИТОГО:** | **~50** | **43-54 часа** | **Высокая** |

---

## ⚠️ Риски и проблемы

### **1. Обратная совместимость**
- Старый код использует `$user->company_id` напрямую
- Нужно обеспечить accessors для совместимости
- Риск пропустить места использования

### **2. Сложность логики доступа**
- Усложняется логика проверки прав
- Множество мест для проверки доступа
- Риск создания дыр в безопасности

### **3. Производительность**
- N+1 проблемы при загрузке множества связей
- Нужно использовать eager loading
- Более сложные запросы к БД

### **4. Миграция данных**
- Необходимо корректно перенести существующие связи
- Риск потери данных при миграции
- Нужен rollback план

### **5. UX сложность**
- Пользователь может работать с несколькими компаниями
- Нужен UI для переключения контекста компании
- Может запутать пользователей

---

## 💡 Альтернативные решения

### **Вариант 1: Оставить как есть (РЕКОМЕНДУЕТСЯ)**
**Плюсы:**
- Нет затрат на разработку
- Система стабильна и проверена
- Простая логика доступа

**Минусы:**
- Сотрудник может быть только в одной компании
- Нужно создавать дубли пользователей для работы в нескольких компаниях

### **Вариант 2: Роли на уровне компаний**
Добавить поддержку множественных компаний, но оставить один активный склад:
```
User ←→ Companies (many-to-many)
User → Warehouse (one-to-one, активный склад)
```
**Плюсы:**
- Меньше изменений кода
- Проще логика доступа
- Легче реализовать

**Минусы:**
- Все еще ограничение одним активным складом

### **Вариант 3: Полная реализация (текущее предложение)**
**Плюсы:**
- Максимальная гибкость
- Один пользователь = доступ ко всем нужным компаниям/складам

**Минусы:**
- Высокая сложность
- Большой объем работы
- Риски безопасности

---

## 🎯 Рекомендации

### **Если нужна множественная принадлежность:**

1. **Начать с MVP (Вариант 2):**
   - Реализовать множественные компании
   - Оставить один активный склад
   - ~20-25 часов работы
   - Меньше рисков

2. **Поэтапная реализация:**
   - **Фаза 1:** База данных и модели (1 неделя)
   - **Фаза 2:** API контроллеры (1-1.5 недели)
   - **Фаза 3:** Filament админка (1.5 недели)
   - **Фаза 4:** Тесты и отладка (1 неделя)
   - **Итого:** 4.5-5 недель

3. **Обязательные шаги:**
   - Написать полное техническое задание
   - Создать backup БД перед миграцией
   - Написать автотесты для критичных сценариев
   - Провести security audit после реализации

### **Если можно обойтись без изменений:**
**РЕКОМЕНДУЮ оставить текущую архитектуру!**
- Создавать несколько аккаунтов для сотрудников
- Использовать SSO для упрощения входа
- Намного проще и безопаснее

---

## 📝 Заключение

Добавление поддержки множественных компаний в профиль сотрудника **технически возможно**, но требует:

- ✅ **~45-54 часа** разработки
- ✅ **~50 файлов** для изменения
- ✅ **Высокая сложность** реализации
- ⚠️ **Средние риски** для безопасности и стабильности
- 💰 **Высокая стоимость** разработки

**Рекомендация:** Оценить реальную потребность в этой функции. Если она не критична, лучше оставить текущую архитектуру "один пользователь = одна компания + один склад" как более простое и надежное решение.

Если функция критична - начать с MVP (множественные компании, один активный склад) и оценить результаты перед полной реализацией.

