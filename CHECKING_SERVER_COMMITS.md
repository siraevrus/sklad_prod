# Как проверить последние коммиты на сервере

## 📍 Основная информация

**Сервер:** `ssh my /var/www/sklad`
**Ветка:** `main`

---

## 🚀 Быстрые команды

### 1. Просмотр последних 10 коммитов
```bash
ssh my "cd /var/www/sklad && git log --oneline -n 10"
```

**Пример вывода:**
```
4f615c1 Добавлено резюме по экспорту приемок в Excel
d8f0856 Добавлена функциональность экспорта приемок товара в Excel с периодом
72169a2 Добавлено резюме по реализации поиска в API остатков
0ef2178 Добавлена документация по поиску в API остатков на складе
ddf819d Добавлен поиск по названию товара в API раздела Остатки на складе
```

### 2. Подробная информация (автор и дата)
```bash
ssh my "cd /var/www/sklad && git log --pretty=format:'%h %an %ad %s' --date=short -n 10"
```

**Пример вывода:**
```
4f615c1 rabota 2025-10-23 Добавлено резюме по экспорту приемок в Excel
d8f0856 rabota 2025-10-23 Добавлена функциональность экспорта приемок товара в Excel с периодом
72169a2 rabota 2025-10-23 Добавлено резюме по реализации поиска в API остатков
0ef2178 rabota 2025-10-23 Добавлена документация по поиску в API остатков на складе
ddf819d rabota 2025-10-23 Добавлен поиск по названию товара в API раздела Остатки на складе
```

### 3. Графический вид веток
```bash
ssh my "cd /var/www/sklad && git log --graph --oneline -n 15"
```

**Пример вывода:**
```
* 4f615c1 Добавлено резюме по экспорту приемок в Excel
* d8f0856 Добавлена функциональность экспорта приемок товара в Excel с периодом
* 72169a2 Добавлено резюме по реализации поиска в API остатков
* 0ef2178 Добавлена документация по поиску в API остатков на складе
* ddf819d Добавлен поиск по названию товара в API раздела Остатки на складе
```

### 4. Статус репозитория
```bash
ssh my "cd /var/www/sklad && git status"
```

**Пример вывода:**
```
On branch main
Your branch is up to date with 'origin/main'.

nothing to commit, working tree clean
```

### 5. Последний коммит (полная информация)
```bash
ssh my "cd /var/www/sklad && git show --stat"
```

**Пример вывода:**
```
commit 4f615c1d8f0856...
Author: rabota <rabota@example.com>
Date:   Wed Oct 23 10:30:00 2025 +0000

    Добавлено резюме по экспорту приемок в Excel

 RECEIPT_EXPORT_SUMMARY.md | 143 +++++++++++++++++++++++++++++++++++++++++++
 1 file changed, 143 insertions(+)
```

---

## 🔍 Расширенная проверка

### Показать файлы, измененные в последнем коммите
```bash
ssh my "cd /var/www/sklad && git show --name-only"
```

### Показать конкретный коммит
```bash
ssh my "cd /var/www/sklad && git show 4f615c1"
```

### Найти коммиты, касающиеся конкретного файла
```bash
ssh my "cd /var/www/sklad && git log --oneline -- app/Filament/Resources/ReceiptResource.php"
```

### Показать, когда файл был последний раз изменен
```bash
ssh my "cd /var/www/sklad && git log -1 --format='%ai' -- app/Filament/Resources/ReceiptResource.php"
```

---

## 📊 Полезные команды для анализа

### 1. История определенного файла
```bash
ssh my "cd /var/www/sklad && git log --oneline -- RECEIPT_EXPORT_DOCUMENTATION.md"
```

### 2. Кто делал последние изменения
```bash
ssh my "cd /var/www/sklad && git log -1 --pretty=format:'Автор: %an %nДата: %ad %nСообщение: %s' --date=short"
```

### 3. Разница между локальным и удаленным
```bash
ssh my "cd /var/www/sklad && git log --oneline origin/main..HEAD"
```

### 4. Коммиты за последний день
```bash
ssh my "cd /var/www/sklad && git log --since='1 day ago' --oneline"
```

### 5. Статистика по авторам
```bash
ssh my "cd /var/www/sklad && git shortlog -sn"
```

---

## 🛠️ Автоматическая проверка (скрипт)

Создайте скрипт для быстрой проверки:

**Файл: check_commits.sh**
```bash
#!/bin/bash

echo "=== Проверка последних коммитов на сервере ==="
echo ""

ssh my "cd /var/www/sklad && git log --pretty=format:'%h %an %ad %s' --date=short -n 5"

echo ""
echo "=== Статус репозитория ==="
ssh my "cd /var/www/sklad && git status --short"

echo ""
echo "=== Текущая ветка ==="
ssh my "cd /var/www/sklad && git branch -v"
```

Использование:
```bash
chmod +x check_commits.sh
./check_commits.sh
```

---

## 📋 Чек-лист проверки

Используйте эту последовательность для полной проверки:

```bash
# 1. Проверить, на какой ветке находитесь
ssh my "cd /var/www/sklad && git branch -v"

# 2. Показать последние 5 коммитов
ssh my "cd /var/www/sklad && git log --oneline -n 5"

# 3. Проверить статус
ssh my "cd /var/www/sklad && git status"

# 4. Показать файлы в последнем коммите
ssh my "cd /var/www/sklad && git show --name-only"

# 5. Проверить, нет ли незафиксированных изменений
ssh my "cd /var/www/sklad && git diff --name-only"
```

---

## 🐛 Решение проблем

### Коммит не видно на сервере
```bash
# Проверить все ветки
ssh my "cd /var/www/sklad && git branch -a"

# Обновить локальное отражение удаленных веток
ssh my "cd /var/www/sklad && git fetch origin"

# Пересинхронизировать
ssh my "cd /var/www/sklad && git pull origin main"
```

### Файлы не обновились
```bash
# Проверить текущее состояние
ssh my "cd /var/www/sklad && git status"

# Проверить, на каком коммите находитесь
ssh my "cd /var/www/sklad && git rev-parse HEAD"

# Показать содержимое файла на сервере
ssh my "cat /var/www/sklad/app/Filament/Resources/ReceiptResource.php | head -20"
```

---

## 💡 Советы

1. **Используйте алиас для быстрого доступа:**
   ```bash
   alias sklad_log="ssh my 'cd /var/www/sklad && git log --oneline -n 10'"
   sklad_log
   ```

2. **Комбинируйте команды:**
   ```bash
   ssh my "cd /var/www/sklad && echo '=== Последние коммиты ===' && git log --oneline -n 3 && echo '' && echo '=== Статус ===' && git status --short"
   ```

3. **Используйте grep для поиска:**
   ```bash
   ssh my "cd /var/www/sklad && git log --oneline | grep -i 'export'"
   ```

---

## 🔗 Ссылки на коммиты

Если у вас есть GitHub, вы можете проверить коммиты онлайн:
- GitHub: `https://github.com/yourusername/sklad/commits/main`
- GitLab: `https://gitlab.com/yourusername/sklad/-/commits/main`

