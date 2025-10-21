# 📋 Руководство по просмотру логов продуктов

## 📁 Расположение логов

```
/Users/rabota/sklad/storage/logs/laravel.log
```

---

## 🔍 ОСНОВНЫЕ КОМАНДЫ

### 1. Просмотреть последние 50 строк логов

```bash
tail -50 /Users/rabota/sklad/storage/logs/laravel.log
```

### 2. Следить за логами в real-time (live)

```bash
tail -f /Users/rabota/sklad/storage/logs/laravel.log
```

**Для выхода:** `Ctrl+C`

### 3. Просмотреть последние 100 строк с более длинной историей

```bash
tail -100 /Users/rabota/sklad/storage/logs/laravel.log
```

### 4. Просмотреть весь файл логов (если он не очень большой)

```bash
cat /Users/rabota/sklad/storage/logs/laravel.log
```

---

## 🔎 ПОИСК ПО ЛОГАМ (grep)

### 1. Найти все логи продукта с конкретным log_id

```bash
grep "product_create_670b0e1d12345" /Users/rabota/sklad/storage/logs/laravel.log
```

### 2. Найти только шаги (STEP)

```bash
grep "STEP" /Users/rabota/sklad/storage/logs/laravel.log | tail -20
```

### 3. Найти только ошибки (WARNING/ERROR)

```bash
grep "WARNING\|ERROR" /Users/rabota/sklad/storage/logs/laravel.log | tail -20
```

### 4. Найти логи testFormula()

```bash
grep "testFormula\|Formula calculation" /Users/rabota/sklad/storage/logs/laravel.log | tail -20
```

### 5. Найти логи конкретного пользователя (ID 1)

```bash
grep '"user_id":1' /Users/rabota/sklad/storage/logs/laravel.log | tail -20
```

### 6. Найти все создания товаров (CREATE)

```bash
grep "START PRODUCT CREATION" /Users/rabota/sklad/storage/logs/laravel.log
```

### 7. Найти все редактирования товаров (EDIT)

```bash
grep "START PRODUCT EDITING" /Users/rabota/sklad/storage/logs/laravel.log
```

---

## 📊 ФИЛЬТРАЦИЯ И АНАЛИЗ

### 1. Показать логи за конкретный log_id с форматированием

```bash
grep "product_create_670b0e1d12345" /Users/rabota/sklad/storage/logs/laravel.log | jq '.' 2>/dev/null || grep "product_create_670b0e1d12345" /Users/rabota/sklad/storage/logs/laravel.log
```

### 2. Показать только INFO логи

```bash
grep '".INFO"' /Users/rabota/sklad/storage/logs/laravel.log | tail -20
```

### 3. Показать только WARNING логи

```bash
grep '".WARNING"' /Users/rabota/sklad/storage/logs/laravel.log | tail -20
```

### 4. Показать только DEBUG логи

```bash
grep '".DEBUG"' /Users/rabota/sklad/storage/logs/laravel.log | tail -20
```

### 5. Подсчитать количество создаданий товаров

```bash
grep "START PRODUCT CREATION" /Users/rabota/sklad/storage/logs/laravel.log | wc -l
```

### 6. Подсчитать количество ошибок при расчете объема

```bash
grep "Volume calculation FAILED" /Users/rabota/sklad/storage/logs/laravel.log | wc -l
```

---

## 🚀 КОМПЛЕКСНЫЕ КОМАНДЫ

### 1. Просмотреть весь процесс создания товара с красивым форматом

```bash
LOG_ID="product_create_670b0e1d12345"
echo "=== Процесс: $LOG_ID ==="
grep "$LOG_ID" /Users/rabota/sklad/storage/logs/laravel.log | grep "STEP"
```

### 2. Показать все шаги и ошибки за последний час

```bash
grep -E "STEP|WARNING|ERROR" /Users/rabota/sklad/storage/logs/laravel.log | tail -50
```

### 3. Сохранить логи в отдельный файл для анализа

```bash
grep "product_create" /Users/rabota/sklad/storage/logs/laravel.log > /tmp/product_logs.txt
cat /tmp/product_logs.txt
```

### 4. Показать все успешные создания товаров

```bash
grep "READY FOR SAVE" /Users/rabota/sklad/storage/logs/laravel.log | tail -10
```

---

## 📈 ИНФОРМАЦИЯ О ЛОГЕ

### 1. Размер файла логов

```bash
ls -lh /Users/rabota/sklad/storage/logs/laravel.log
```

### 2. Количество строк в логе

```bash
wc -l /Users/rabota/sklad/storage/logs/laravel.log
```

### 3. Статистика по типам логов

```bash
echo "=== INFO логи ===" && grep -c '".INFO"' /Users/rabota/sklad/storage/logs/laravel.log
echo "=== WARNING логи ===" && grep -c '".WARNING"' /Users/rabota/sklad/storage/logs/laravel.log
echo "=== DEBUG логи ===" && grep -c '".DEBUG"' /Users/rabota/sklad/storage/logs/laravel.log
echo "=== ERROR логи ===" && grep -c '".ERROR"' /Users/rabota/sklad/storage/logs/laravel.log
```

---

## 🔄 РОТАЦИЯ ЛОГОВ

### Если логи занимают слишком много места

```bash
# Очистить логи (осторожно!)
> /Users/rabota/sklad/storage/logs/laravel.log

# Или сохранить в архив перед очисткой
mv /Users/rabota/sklad/storage/logs/laravel.log /Users/rabota/sklad/storage/logs/laravel.backup.log
```

---

## 🛠️ ПРИМЕРЫ ИСПОЛЬЗОВАНИЯ

### Пример 1: Отследить процесс создания товара

```bash
# 1. Создаёте товар в Filament
# 2. Смотрите логи
tail -f /Users/rabota/sklad/storage/logs/laravel.log

# 3. Ищете log_id в выводе
# 4. Анализируете все шаги
grep "product_create_XXXXX" /Users/rabota/sklad/storage/logs/laravel.log
```

### Пример 2: Найти, почему calculated_volume не рассчитался

```bash
# Смотрим WARNING логи
grep "WARNING" /Users/rabota/sklad/storage/logs/laravel.log | grep -i "volume\|formula" | tail -10

# Или смотрим ошибки testFormula
grep "FAILED" /Users/rabota/sklad/storage/logs/laravel.log | tail -5
```

### Пример 3: Отследить все ошибки за день

```bash
# Сегодняшние ошибки
date_today=$(date +%Y-%m-%d)
grep "$date_today" /Users/rabota/sklad/storage/logs/laravel.log | grep "WARNING\|ERROR"
```

---

## 💡 СОВЕТЫ

1. **Используйте log_id для отслеживания**
   ```bash
   grep "product_create_670b0e1d12345" file.log
   ```

2. **Комбинируйте grep с tail для последних логов**
   ```bash
   grep "STEP" /Users/rabota/sklad/storage/logs/laravel.log | tail -20
   ```

3. **Используйте live tail для мониторинга**
   ```bash
   tail -f /Users/rabota/sklad/storage/logs/laravel.log | grep "STEP\|WARNING"
   ```

4. **Ищите по timestampам для определённого периода**
   ```bash
   grep "2025-10-21 14:" /Users/rabota/sklad/storage/logs/laravel.log
   ```

---

## 📊 СТРУКТУРА ЛОГОВ

### Формат строки в логе

```
[TIMESTAMP] ENVIRONMENT.LEVEL: MESSAGE {"context_data"}
```

**Пример:**
```
[2025-10-21 14:33:45] local.INFO: === STEP 1: START PRODUCT CREATION === {"log_id":"product_create_1","timestamp":"2025-10-21T14:33:45.123456Z","user_id":1}
```

---

**Дата:** October 21, 2025  
**Версия:** 1.0  
**Статус:** ✅ Полное руководство

