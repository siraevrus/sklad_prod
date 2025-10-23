# 🚀 Как загрузить изменения на сервер

## 🔴 ПРОБЛЕМА

В данный момент локальная версия впереди на **6 коммитов**:

```
Your branch is ahead of 'origin/main' by 6 commits.
```

**Коммиты, которые нужно загрузить:**
1. Добавлено руководство по проверке коммитов на сервере
2. Добавлено резюме по экспорту приемок в Excel
3. Добавлена функциональность экспорта приемок товара в Excel с периодом
4. Добавлено резюме по реализации поиска в API остатков
5. Добавлена документация по поиску в API остатков на складе
6. Добавлен поиск по названию товара в API раздела Остатки на складе

---

## ✅ РЕШЕНИЕ

### Способ 1: SSH ключ (рекомендуется)

**Шаг 1: Переключиться на SSH URL**

```bash
cd /Users/rabota/sklad
git remote set-url origin git@github.com:siraevrus/sklad_prod.git
```

**Шаг 2: Проверить**

```bash
git remote -v
# Должно быть:
# origin	git@github.com:siraevrus/sklad_prod.git (fetch)
# origin	git@github.com:siraevrus/sklad_prod.git (push)
```

**Шаг 3: Загрузить коммиты**

```bash
git push origin main
```

---

### Способ 2: Personal Access Token (GitHub)

**Шаг 1: Создать токен на GitHub**
1. Перейдите на https://github.com/settings/tokens/new
2. Выберите `repo` (полный доступ к репозиториям)
3. Нажмите "Generate token"
4. Скопируйте токен

**Шаг 2: Сохранить токен локально**

```bash
# Макос/Linux
git config --global credential.helper osxkeychain

# Или сохранить в .netrc
echo "machine github.com
login siraevrus
password YOUR_TOKEN_HERE" >> ~/.netrc
chmod 600 ~/.netrc
```

**Шаг 3: Загрузить коммиты**

```bash
git push origin main
# Введите токен вместо пароля, когда будет запрос
```

---

### Способ 3: Через VS Code / GitHub Desktop

1. Откройте VS Code
2. В левой боковой панели нажмите на "Source Control"
3. Нажмите на три точки (...)
4. Выберите "Push"

ИЛИ используйте GitHub Desktop приложение.

---

## 🔧 ПОЛНАЯ ИНСТРУКЦИЯ ДЛЯ SSH

### 1. Проверить, есть ли SSH ключ

```bash
ls -la ~/.ssh/
```

Должны быть файлы `id_rsa` или `id_ed25519`

### 2. Если ключа нет, создать его

```bash
ssh-keygen -t ed25519 -C "ваш-email@example.com"
# Нажать Enter несколько раз
```

### 3. Скопировать публичный ключ

```bash
# Макос
cat ~/.ssh/id_ed25519.pub | pbcopy

# Linux
cat ~/.ssh/id_ed25519.pub | xclip -selection clipboard
```

### 4. Добавить ключ на GitHub

1. Перейдите на https://github.com/settings/ssh/new
2. Вставьте скопированный ключ
3. Нажмите "Add SSH key"

### 5. Протестировать подключение

```bash
ssh -T git@github.com
# Должно появиться:
# Hi siraevrus! You've successfully authenticated...
```

### 6. Переключить на SSH URL

```bash
git remote set-url origin git@github.com:siraevrus/sklad_prod.git
```

### 7. Загрузить коммиты

```bash
git push origin main
```

---

## 📊 ШАГ ЗА ШАГОМ (БЫСТРО)

**Если вы уже настроили SSH:**

```bash
cd /Users/rabota/sklad
git push origin main -v
```

**Должно вывести:**
```
Pushing to git@github.com:siraevrus/sklad_prod.git
Counting objects: 25, done.
Compressing objects: 100% (20/20), done.
Writing objects: 100% (25/25), 8.5 KB, done.
Total 25 (delta 15), reused 0 (delta 0)
remote: Resolving deltas: 100% (15/15), done.
To git@github.com:siraevrus/sklad_prod.git
   72169a2..997ed7a  main -> main
```

---

## ✨ ПОСЛЕ ЗАГРУЗКИ НА GITHUB

Коммиты будут видны:
- На GitHub: https://github.com/siraevrus/sklad_prod/commits/main
- Локально: `git log --oneline -n 10`

Затем нужно подтянуть изменения на боевой сервер:

```bash
ssh my "cd /var/www/sklad && git pull origin main"
```

---

## 🐛 ЕСЛИ ЧТО-ТО НЕ РАБОТАЕТ

### Ошибка: "fatal: could not read Username"

**Решение:** Переключитесь на SSH
```bash
git remote set-url origin git@github.com:siraevrus/sklad_prod.git
```

### Ошибка: "Permission denied (publickey)"

**Решение:** Проверьте SSH ключ
```bash
ssh -T git@github.com
```

Если ошибка, создайте новый ключ (см. шаги выше)

### Ошибка: "The requested URL returned error: 403"

**Решение:** Проверьте, правильный ли токен
```bash
git config --global --unset credential.helper
git remote set-url origin git@github.com:siraevrus/sklad_prod.git
```

---

## 🔐 БЕЗОПАСНОСТЬ

**⚠️ ВАЖНО:**
- Никогда не публикуйте токены или SSH ключи
- Используйте SSH ключи вместо токенов
- Регулярно ротируйте ключи (раз в год)

---

## 📝 ТЕКУЩЕЕ СОСТОЯНИЕ

```
Repository: siraevrus/sklad_prod
Current Branch: main
Commits Ahead: 6
Remote URL: https://github.com/siraevrus/sklad_prod.git
```

**Файлы для загрузки:**
- app/Filament/Resources/ReceiptResource/Pages/ExportReceipts.php
- app/Filament/Resources/ReceiptResource/Pages/ListReceipts.php
- app/Filament/Resources/ReceiptResource.php
- resources/views/filament/resources/receipt-resource/pages/export-receipts.blade.php
- RECEIPT_EXPORT_DOCUMENTATION.md
- RECEIPT_EXPORT_SUMMARY.md
- CHECKING_SERVER_COMMITS.md
- И еще документация по поиску

