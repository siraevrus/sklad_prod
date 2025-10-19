# 🚀 Настройка локального окружения для разработки

**Дата:** 19 октября 2025  
**Статус:** ✅ Готово к использованию

---

## 📋 Требования

- ✅ **PHP 8.4+** (установлен XAMPP)
- ✅ **MySQL 9.4+** (установлен через Homebrew)
- ✅ **Composer** (для PHP зависимостей)
- ✅ **Node.js / npm** (для фронтенда)

---

## 🔧 Быстрая настройка (3 шага)

### Шаг 1: Клонировать репозиторий
```bash
git clone https://github.com/flamedeluxe/sklad.git
cd sklad
```

### Шаг 2: Установить зависимости
```bash
composer install
npm install
```

### Шаг 3: Проверить MySQL и запустить
```bash
# Убедиться, что MySQL запущена
ps aux | grep mysqld

# Генерировать APP_KEY
php artisan key:generate

# Запустить миграции (если нужно)
php artisan migrate

# Запустить dev сервер
php artisan serve
```

**Готово! 🎉 Приложение запущено на http://localhost:8000**

---

## 🗄️ Конфигурация базы данных

### `.env` файл (уже настроен)

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sklad
DB_USERNAME=root
DB_PASSWORD=          # Пустой пароль (XAMPP/Homebrew default)
```

### ✅ Статус БД

```
✅ MySQL ........................... 9.4.0
✅ Connection ..................... mysql
✅ Database ....................... sklad
✅ Tables ......................... 74
✅ Size ........................... 3.67 MB
```

---

## 🎯 Основные команды

### Развитие (Development)
```bash
# Запустить dev сервер
php artisan serve

# Запустить фронтенд вотчер
npm run dev

# Оба одновременно (отдельные терминалы)
terminal 1: php artisan serve
terminal 2: npm run dev
```

### Тестирование
```bash
# Все тесты
php artisan test

# Конкретный файл
php artisan test tests/Feature/SaleResourceTest.php

# С покрытием
php artisan test --coverage
```

### Очистка
```bash
# Очистить кеш
php artisan cache:clear

# Пересобрать конфиг
php artisan config:cache

# Очистить логи
php artisan log:clear
```

### Миграции
```bash
# Запустить миграции
php artisan migrate

# Откатить последнюю миграцию
php artisan migrate:rollback

# Пересоздать БД (с миграциями)
php artisan migrate:fresh

# С сидами (заполнение тестовыми данными)
php artisan migrate:fresh --seed
```

---

## 📊 Система управления

### Филамент (Admin Panel)
```
URL: http://localhost:8000/admin
```

### API
```
URL: http://localhost:8000/api
Docs: http://localhost:8000/api/docs
```

---

## 🛠️ Решение проблем

### Проблема: "Can't connect to MySQL"
```bash
# Решение: Убедиться, что MySQL запущена
ps aux | grep mysqld

# Если не запущена, запустить через Homebrew
brew services start mysql

# Или через XAMPP
/Applications/XAMPP/bin/mysqld_safe
```

### Проблема: "Application key not set"
```bash
# Решение: Генерировать ключ
php artisan key:generate
```

### Проблема: "Port 8000 already in use"
```bash
# Решение: Использовать другой порт
php artisan serve --port=8001
```

### Проблема: "intl.so not found"
```
⚠️ Это просто warning от PHP, не влияет на работу
Можно игнорировать
```

---

## 📱 Структура приложения

### Backend (Laravel 12)
```
app/
├─ Models/              # Eloquent модели
├─ Controllers/         # HTTP контроллеры
├─ Filament/            # Admin UI (Filament v3)
├─ Providers/           # Service providers
└─ Support/             # Вспомогательные классы

routes/
├─ api.php              # API routes
├─ web.php              # Web routes
└─ console.php          # Console commands

database/
├─ migrations/          # Schema миграции
├─ seeders/             # Test data seeders
└─ factories/           # Model factories
```

### Frontend (Vite + Livewire)
```
resources/
├─ views/               # Blade шаблоны
├─ css/                 # Tailwind CSS
├─ js/                  # JavaScript (Alpine.js)
└─ components/          # Компоненты
```

---

## 🚀 Рекомендуемый workflow

### 1️⃣ Создание новой фичи
```bash
# Создать миграцию
php artisan make:migration create_users_table

# Создать модель с фабрикой
php artisan make:model User -mf

# Создать контроллер
php artisan make:controller UserController --resource

# Запустить миграции
php artisan migrate
```

### 2️⃣ Разработка
```bash
# Terminal 1: Dev сервер
php artisan serve

# Terminal 2: Frontend вотчер
npm run dev

# Terminal 3: Логи (опционально)
tail -f storage/logs/laravel.log
```

### 3️⃣ Тестирование
```bash
# Создать тест
php artisan make:test UserTest

# Запустить тесты
php artisan test
```

### 4️⃣ Коммит и push
```bash
# Форматировать код
./vendor/bin/pint

# Коммитить изменения
git add .
git commit -m "feat: add user management"

# Загрузить
git push origin main
```

---

## 📚 Документация

- **Основной проект:** [README.md](./README.md)
- **Калькулятор:** [CALCULATOR_MECHANISM.md](./CALCULATOR_MECHANISM.md)
- **Развёртывание:** [DEPLOYMENT_SETUP.md](./DEPLOYMENT_SETUP.md)
- **API:** [API_DOCUMENTATION.md](./API_DOCUMENTATION.md)

---

## 🔗 Полезные ссылки

- **Laravel docs:** https://laravel.com/docs/12.x
- **Filament docs:** https://filamentphp.com
- **Livewire docs:** https://livewire.laravel.com
- **Tailwind docs:** https://tailwindcss.com

---

## ✅ Чек-лист готовности

- [x] PHP 8.4+ установлен
- [x] MySQL запущена и доступна
- [x] Composer установлен
- [x] .env файл настроен
- [x] APP_KEY установлен
- [x] Миграции выполнены
- [x] npm зависимости установлены
- [x] Artisan команды работают
- [x] БД подключена (74 таблицы)

**🎉 Всё готово к разработке!**

---

## 📞 Поддержка

Если возникают проблемы:
1. Проверьте логи: `storage/logs/laravel.log`
2. Запустите `php artisan doctor`
3. Посмотрите документацию в проекте
4. Создайте Issue в GitHub

---

**Удачной разработки! 🚀**
