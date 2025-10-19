# GitHub Actions - Автоматический деплой и CI/CD

## 📋 Описание

Проект использует GitHub Actions для автоматизации:
- ✅ **Тестирование** - запуск всех тестов при каждом push
- 🎨 **Линтинг кода** - проверка кода с Laravel Pint
- 🚀 **Автодеплой** - автоматический деплой на production при merge в main

---

## 🔧 Доступные Workflows

### 1. **Tests** (`tests.yml`)
Запускает всё тесты приложения на каждый push в `main` и `develop`.

**Что происходит:**
- Устанавливает PHP 8.4
- Кэширует Composer зависимости
- Создает SQLite БД в памяти
- Запускает все тесты
- Загружает результаты в Codecov

**Триггеры:** Push или Pull Request в `main`/`develop`

---

### 2. **Code Style** (`lint.yml`)
Проверяет стиль кода с помощью Laravel Pint.

**Что происходит:**
- Устанавливает PHP 8.4
- Запускает `./vendor/bin/pint --test`
- Проверяет соответствие стилю кода

**Триггеры:** Push или Pull Request в `main`/`develop`

---

### 3. **Deploy to Production** (`simple-deploy.yml`)
Автоматический деплой на production серверы при merge в `main`.

**Что происходит:**
- Деплойит одновременно на оба сервера:
  - **Старый сервер:** 93.189.230.65 (путь: /var/www/sklad)
  - **Новый сервер:** 31.184.253.122 (путь: /var/www/warehouse)
- Подключается к серверу по SSH
- Выполняет `git pull origin main`
- Устанавливает Composer зависимости
- Запускает миграции БД
- Строит фронтенд (npm build)
- Очищает кэши
- Оптимизирует приложение

**Триггеры:** Push в `main` (обычно после merge Pull Request)

---

## 🔐 Необходимые Secrets

Для работы workflows нужно настроить следующие secrets в репозитории.

### 📍 Как добавить Secrets

1. Перейдите в **Settings** репозитория
2. **Secrets and variables** → **Actions**
3. Нажмите **New repository secret**
4. Добавьте необходимые секреты

### 🔑 Требуемые Secrets

#### Для СТАРОГО сервера (93.189.230.65)

| Secret | Значение |
|--------|----------|
| `HOST_OLD` | `93.189.230.65` |
| `USERNAME_OLD` | `root` |
| `SSH_KEY_OLD` | Приватный SSH ключ (OpenSSH) |
| `PORT_OLD` | `22` (опционально) |

#### Для НОВОГО сервера (31.184.253.122)

| Secret | Значение |
|--------|----------|
| `HOST_NEW` | `31.184.253.122` |
| `USERNAME_NEW` | `root` |
| `SSH_KEY_NEW` | Приватный SSH ключ (OpenSSH) |
| `PORT_NEW` | `22` (опционально) |

⚠️ **Важно:** Используйте формат OpenSSH для SSH ключей:
```
-----BEGIN OPENSSH PRIVATE KEY-----
MIIEpAIBAAKCAQEA1234567890...
...конец ключа...
-----END OPENSSH PRIVATE KEY-----
```

---

## 🚀 Как это работает

### Deploy Flow

```
1. Разработчик пушит код в main
   ↓
2. GitHub Actions запускает workflow
   ↓
3. Проверяет тесты и стиль кода
   ↓
4. Если все ОК → подключается к серверу по SSH
   ↓
5. Выполняет git pull, миграции, npm build
   ↓
6. Очищает кэши и оптимизирует приложение
   ↓
7. Сервер получает свежий код автоматически!
```

---

## 📊 Мониторинг Workflows

### Просмотр логов

1. Перейдите на вкладку **Actions** репозитория
2. Выберите нужный workflow run
3. Нажмите на job для просмотра логов

### Статус Deploy

Когда видите зелёный ✅ — deployment прошел успешно!
Красный ❌ — нужно проверить логи и исправить ошибки.

---

## 🛡️ Безопасность

### Best Practices

1. **SSH ключ должен быть только для чтения:**
   ```bash
   chmod 600 ~/.ssh/id_rsa
   ```

2. **Добавьте публичный ключ на сервер:**
   ```bash
   cat ~/.ssh/id_rsa.pub >> ~/.ssh/authorized_keys
   chmod 600 ~/.ssh/authorized_keys
   ```

3. **Используйте отдельного пользователя** с ограниченными правами (если возможно)

4. **Никогда не коммитьте секреты** в репозиторий

5. **Регулярно ротируйте SSH ключи**

---

## 🔄 Синхронизация с Двумя Репозиториями

Если используете несколько remotes, можно автоматизировать push:

```bash
# В .git/config добавьте:
[remote "all"]
    url = https://github.com/flamedeluxe/sklad.git
    url = https://github.com/siraevrus/sklad_prod.git
```

Затем:
```bash
git push all main
```

---

## 📝 Кастомизация Workflows

### Добавить новый workflow

Создайте новый файл в `.github/workflows/name.yml`:

```yaml
name: My Custom Workflow

on:
  push:
    branches: [ main ]

jobs:
  my-job:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - run: echo "Hello World!"
```

### Условные запуски

```yaml
on:
  push:
    branches: [ main ]
    paths:
      - 'app/**'      # Запускать только если изменилась папка app
      - '!app/temp/*' # Кроме этой папки
```

---

## 🐛 Устранение Неполадок

### Workflow не запускается
- Проверьте что trigger условия верные (ветка, пути)
- Убедитесь что файл в `.github/workflows/`

### Deploy падает с ошибкой SSH
- Проверьте Host, Username, SSH_KEY в Secrets
- Убедитесь SSH ключ в формате OpenSSH (не PuTTY)

### Тесты падают локально но проходят в CI
- Проверьте переменные окружения в `.env`
- Убедитесь что БД конфигурация правильная

---

## 📚 Полезные Ссылки

- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [appleboy/ssh-action](https://github.com/appleboy/ssh-action)
- [Laravel Testing](https://laravel.com/docs/testing)
- [Laravel Pint](https://laravel.com/docs/pint)

---

## ✅ Статус Workflows

Все workflows готовы к использованию! Просто добавьте secrets в GitHub и начинайте работать. 🎉
