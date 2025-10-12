# Исправление ошибки PHP intl Extension

## Проблема

```
RuntimeException: The "intl" PHP extension is required to use the [format] method.
```

Эта ошибка появляется на странице `/admin/products` и других страницах, где используется форматирование чисел.

## Быстрое решение

### Шаг 1: Активировать расширение intl

Откройте **новый терминал** и выполните:

```bash
echo "extension=intl.so" | sudo tee -a /Applications/XAMPP/xamppfiles/etc/php.ini
```

Введите пароль администратора когда система попросит.

### Шаг 2: Проверить активацию

```bash
php -m | grep intl
```

Должно вывести: `intl`

Если не выводит, проверьте файл:
```bash
grep intl /Applications/XAMPP/xamppfiles/etc/php.ini
```

### Шаг 3: Перезапустить Laravel сервер

1. В терминале где работает сервер нажмите `Ctrl+C`
2. Запустите заново:
   ```bash
   cd /Users/rabota/sklad
   php artisan serve --host=127.0.0.1 --port=8000
   ```
3. Обновите страницу в браузере (F5)

## Результат

После выполнения всех шагов страница http://127.0.0.1:8000/admin/products должна открыться без ошибок и показать список товаров.

## Альтернативный способ (ручное редактирование)

Если команда выше не работает:

1. Откройте файл в редакторе:
   ```bash
   sudo nano /Applications/XAMPP/xamppfiles/etc/php.ini
   ```

2. Найдите строку 873:
   ```
   ;extension=php_intl.dll
   ```

3. Замените на:
   ```
   extension=intl.so
   ```

4. Сохраните: `Ctrl+O`, `Enter`, `Ctrl+X`

5. Перезапустите Laravel сервер

## Проверка работы

После исправления откройте в браузере:

- http://127.0.0.1:8000/admin/products - Список товаров
- http://127.0.0.1:8000/admin/sales - Продажи
- http://127.0.0.1:8000/admin/stocks - Остатки

Все страницы должны открываться без ошибок.

---

**Создано:** 13 октября 2025
**Статус:** Требуется активация intl расширения

