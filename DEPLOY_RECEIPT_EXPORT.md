# Развёртывание исправления экспорта приемок

## Шаги для развёртывания на сервере

### 1. Подключиться к серверу
```bash
ssh my
cd /var/www/sklad
```

### 2. Подтянуть изменения из Git
```bash
git pull origin main
```

### 3. Очистить кэши
```bash
php artisan route:clear
php artisan filament:optimize-clear
php artisan config:clear
php artisan view:clear
```

### 4. Проверить работу
Перейти на страницу приемок и нажать кнопку "Экспорт в Excel":
```
https://warehouse.expwood.ru/admin/receipts
```

## Что было исправлено
- Кнопка "Экспорт в Excel" теперь работает корректно
- Экспорт создаёт CSV файл с правильной кодировкой UTF-8
- Файл открывается в Excel без проблем с кириллицей

## Проверка
После развёртывания проверить:
1. ✅ Кнопка "Экспорт в Excel" видна на странице `/admin/receipts`
2. ✅ При нажатии на кнопку скачивается файл `receipts_YYYY-MM-DD.csv`
3. ✅ Файл открывается в Excel с корректным отображением русских букв
4. ✅ В файле присутствуют все приемки (для админа) или только приемки своего склада (для оператора)

## Откат (если что-то пошло не так)
```bash
git reset --hard HEAD~1
php artisan route:clear
php artisan filament:optimize-clear
```

