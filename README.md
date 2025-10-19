# 🏭 Система управления складом ()

Полнофункциональная система управления складом на Laravel 12 + Filament с REST API для мобильного приложения.

## 🎯 Возможности

### ✅ Основные модули
- **Управление товарами** - создание, редактирование, отслеживание остатков
- **Товары в пути** - отслеживание доставки и приемка товаров
- **Система запросов** - создание и обработка запросов на получение товаров
- **Реализация товаров** - продажи с автоматическим списанием со склада
- **Дашборд и отчеты** - статистика и аналитика
- **REST API** - полный API для мобильного приложения

### 🔐 Безопасность
- Система ролей (Администратор, Оператор ПК, Работник склада, Менеджер по продажам)
- Аутентификация через токены (Laravel Sanctum)
- Валидация всех входных данных
- Права доступа на уровне данных

### 📊 Аналитика
- Статистика по товарам, продажам, запросам
- Популярные товары
- Просроченные доставки и запросы
- Экспорт данных в CSV

### 🚀 Оптимизация
- Кеширование статистики
- Сжатие API ответов (gzip)
- Пагинация результатов
- Оптимизированные запросы к БД
- **Улучшенная форма создания товаров** - визуальная обратная связь при отправке форм

## 🛠 Технологии

- **Backend**: Laravel 12
- **Admin Panel**: Filament 3
- **API**: Laravel Sanctum
- **Database**: MySQL/PostgreSQL
- **Frontend**: Blade + Livewire
- **Authentication**: Laravel Sanctum

## 📋 Требования

- PHP 8.2+
- Composer
- MySQL 8.0+ или PostgreSQL 13+
- Node.js 18+ (для сборки фронтенда)

## 🚀 Установка

### 1. Клонирование репозитория
```bash
git clone <repository-url>
cd sklad
```

### 2. Установка зависимостей
```bash
composer install
npm install
```

### 3. Настройка окружения
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Настройка базы данных
Отредактируйте `.env` файл:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sklad
DB_USERNAME=root
DB_PASSWORD=
```

### 5. Миграции и сидеры
```bash
php artisan migrate:fresh --seed
```

### 6. Сборка фронтенда
```bash
npm run build
```

### 7. Запуск сервера
```bash
php artisan serve
```

## 👥 Тестовые аккаунты

После установки доступны следующие аккаунты:

| Роль | Email | Пароль |
|------|-------|--------|
| **Администратор** | admin@sklad.ru | password |
| **Оператор ПК** | operator@sklad.ru | password |
| **Работник склада** | worker@sklad.ru | password |
| **Менеджер по продажам** | manager@sklad.ru | password |

## 🌐 Доступ к системе

- **Веб-интерфейс**: http://localhost:8000/admin
- **API**: http://localhost:8000/api

## 📚 API Документация

Полная документация API доступна в файле [API_DOCUMENTATION.md](API_DOCUMENTATION.md)

### Основные эндпоинты:

#### Аутентификация
- `POST /api/auth/register` - Регистрация
- `POST /api/auth/login` - Вход
- `POST /api/auth/logout` - Выход
- `GET /api/auth/me` - Профиль пользователя

#### Товары
- `GET /api/products` - Список товаров
- `GET /api/products/{id}` - Получение товара
- `POST /api/products` - Создание товара
- `PUT /api/products/{id}` - Обновление товара
- `DELETE /api/products/{id}` - Удаление товара
- `GET /api/products/stats` - Статистика товаров
- `GET /api/products/popular` - Популярные товары

#### Продажи
- `GET /api/sales` - Список продаж
- `GET /api/sales/{id}` - Получение продажи
- `POST /api/sales` - Создание продажи
- `PUT /api/sales/{id}` - Обновление продажи
- `DELETE /api/sales/{id}` - Удаление продажи
- `POST /api/sales/{id}/process` - Оформление продажи
- `POST /api/sales/{id}/cancel` - Отмена продажи
- `GET /api/sales/stats` - Статистика продаж

## 📊 Структура базы данных

### Основные таблицы:
- `users` - Пользователи системы
- `companies` - Компании
- `warehouses` - Склады
- `product_templates` - Шаблоны товаров
- `product_attributes` - Характеристики товаров
- `products` - Товары на складе
- `product_in_transit` - Товары в пути
- `requests` - Запросы на получение товаров
- `sales` - Продажи

## 🔧 Конфигурация

### Настройка ролей
Роли определяются в `app/UserRole.php`:
- `admin` - Полный доступ ко всем данным
- `operator` - Оператор ПК
- `worker` - Работник склада
- `manager` - Менеджер по продажам

### Настройка прав доступа
Права доступа настраиваются в каждом Resource файле через метод `getEloquentQuery()`.

## 📈 Мониторинг и логи

- Логи приложения: `storage/logs/laravel.log`
- Логи API запросов: `storage/logs/api.log`
- Кеш статистики: Redis/Memcached (опционально)

## 🧪 Тестирование

```bash
# Запуск всех тестов
php artisan test

# Запуск тестов API
php artisan test --filter=Api

# Запуск тестов с покрытием
php artisan test --coverage
```

## 📦 Развертывание

### Production настройки
1. Установите `APP_ENV=production` в `.env`
2. Настройте кеширование: `php artisan config:cache`
3. Оптимизируйте автозагрузчик: `composer install --optimize-autoloader --no-dev`
4. Настройте веб-сервер (Nginx/Apache)

### Docker (опционально)
```bash
docker-compose up -d
```

## 📝 Документация по улучшениям

- [FORM_SUBMISSION_IMPROVEMENTS.md](FORM_SUBMISSION_IMPROVEMENTS.md) - Улучшения формы создания товаров

## 🤝 Вклад в проект# Auto-deploy test понедельник, 20 октября 2025 г. 00:02:33 (MSK)
