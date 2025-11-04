# 🧪 Настройка DEV окружения

## 📋 Обзор

Создана отдельная ветка `dev` для тестирования изменений перед деплоем на прод.

### Структура окружений

- **PROD** (main ветка) → `warehouse.expwood.ru`
- **DEV** (dev ветка) → `test.warehouse.expwood.ru`

## 🚀 Быстрый старт

### 1. Переключение на dev ветку

```bash
cd /Users/rabota/sklad
git checkout dev
```

### 2. Деплой на dev сервер

```bash
./QUICK_DEPLOY_DEV.sh
```

Или вручную:

```bash
# 1. Отправить изменения в GitHub
git push origin dev

# 2. Подключиться к серверу и обновить код
ssh my "cd /var/www/test.warehouse && git checkout dev && git pull origin dev"

# 3. Запустить миграции
ssh my "cd /var/www/test.warehouse && php artisan migrate --force"

# 4. Очистить кеш
ssh my "cd /var/www/test.warehouse && php artisan cache:clear && php artisan config:clear"
```

## 📝 Workflow работы

### Разработка новой функциональности

```bash
# 1. Переключиться на dev
git checkout dev

# 2. Создать новую ветку для фичи (опционально)
git checkout -b feature/my-feature

# 3. Внести изменения, сделать коммиты
git add .
git commit -m "Добавлена новая функциональность"

# 4. Задеплоить на dev для тестирования
./QUICK_DEPLOY_DEV.sh

# 5. Протестировать на test.warehouse.expwood.ru

# 6. Если всё ок, переключиться на main и смержить
git checkout main
git merge dev
git push origin main

# 7. Задеплоить на прод
./QUICK_DEPLOY.sh  # или существующий процесс деплоя
```

### Горячие фиксы (hotfix)

Если нужно срочно исправить что-то на проде:

```bash
# 1. Переключиться на main
git checkout main

# 2. Создать ветку hotfix
git checkout -b hotfix/critical-fix

# 3. Внести исправления
# ... изменения ...

# 4. Закоммитить и задеплоить на прод
git add .
git commit -m "Critical fix: описание"
git push origin main
# Деплой на прод

# 5. Смержить в dev
git checkout dev
git merge hotfix/critical-fix
git push origin dev
# Деплой на dev
```

## 🔧 Настройка сервера

### Первоначальная настройка dev сервера

```bash
# Подключиться к серверу
ssh my

# Создать директорию для dev проекта
cd /var/www
git clone git@github.com:siraevrus/sklad_prod.git test.warehouse
cd test.warehouse

# Переключиться на dev ветку
git checkout dev

# Скопировать .env файл
cp .env.example .env

# Настроить .env для dev окружения
# - APP_ENV=local или staging
# - APP_DEBUG=true
# - APP_URL=http://test.warehouse.expwood.ru
# - Настроить базу данных (отдельная БД для dev)

# Установить зависимости
composer install
npm install
npm run build

# Настроить права
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Запустить миграции
php artisan migrate --force

# Очистить кеш
php artisan cache:clear
php artisan config:clear
```

### Настройка веб-сервера (nginx/apache)

Создать конфигурацию для `test.warehouse.expwood.ru`:

```nginx
server {
    listen 80;
    server_name test.warehouse.expwood.ru;
    root /var/www/test.warehouse/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## 📊 Сравнение окружений

| Параметр | PROD | DEV |
|----------|------|-----|
| Домен | warehouse.expwood.ru | test.warehouse.expwood.ru |
| Ветка | main | dev |
| База данных | sklad_prod | sklad_dev |
| APP_ENV | production | local/staging |
| APP_DEBUG | false | true |
| Путь на сервере | /var/www/sklad | /var/www/test.warehouse |

## ⚠️ Важные замечания

1. **Никогда не коммитьте .env файлы** - они должны быть в .gitignore
2. **Используйте отдельную БД для dev** - не тестируйте на продакшн данных
3. **После тестирования на dev всегда проверяйте на проде** перед финальным деплоем
4. **Регулярно синхронизируйте dev с main** - чтобы dev не ушёл далеко вперёд

## 🔄 Синхронизация dev с main

```bash
# На локальной машине
git checkout dev
git merge main
git push origin dev

# На сервере
ssh my "cd /var/www/test.warehouse && git pull origin dev && php artisan migrate --force"
```

## 📝 Чеклист перед деплоем на прод

- [ ] Все изменения протестированы на dev
- [ ] Нет ошибок в логах dev сервера
- [ ] Миграции применены успешно
- [ ] Тесты пройдены (если есть)
- [ ] Код отформатирован (Pint)
- [ ] Изменения закоммичены в dev
- [ ] Изменения смержены в main
- [ ] Готов к деплою на прод

## 🆘 Troubleshooting

### Ошибка: "dev ветка не существует"

```bash
git checkout -b dev
git push -u origin dev
```

### Ошибка: "Permission denied" при деплое

Проверьте права доступа на сервере:
```bash
ssh my "chmod +x /var/www/test.warehouse"
```

### Ошибка: "Database connection failed"

Проверьте настройки БД в .env файле на dev сервере.

