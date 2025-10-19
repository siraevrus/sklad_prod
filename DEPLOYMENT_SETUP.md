# Настройка деплоя на новый сервер

## 1. Подготовка сервера (31.184.253.122)

### Шаг 1: Подключитесь к серверу
```bash
ssh root@31.184.253.122
```

### Шаг 2: Установите необходимое ПО
```bash
# Обновите систему
apt update && apt upgrade -y

# Установите Git
apt install git -y

# Установите PHP 8.4 и расширения
apt install software-properties-common -y
add-apt-repository ppa:ondrej/php -y
apt update
apt install php8.4 php8.4-fpm php8.4-mysql php8.4-xml php8.4-mbstring php8.4-curl php8.4-zip php8.4-gd php8.4-intl php8.4-bcmath -y

# Установите Composer
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

# Установите Node.js и npm
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install nodejs -y

# Установите Nginx (если еще не установлен)
apt install nginx -y

# Установите MySQL (если еще не установлен)
apt install mysql-server -y
```

### Шаг 3: Создайте директорию проекта
```bash
mkdir -p /var/www/warehouse
cd /var/www/warehouse
```

### Шаг 4: Настройте SSH ключ для GitHub

#### Вариант А: Использовать существующий SSH ключ со старого сервера

На **старом сервере** (93.189.230.65):
```bash
# Покажите приватный ключ
cat ~/.ssh/id_rsa
```

Скопируйте содержимое, затем на **новом сервере**:
```bash
# Создайте директорию .ssh
mkdir -p ~/.ssh
chmod 700 ~/.ssh

# Создайте приватный ключ
nano ~/.ssh/id_rsa
# Вставьте скопированный ключ, сохраните (Ctrl+O, Enter, Ctrl+X)

chmod 600 ~/.ssh/id_rsa

# Создайте публичный ключ
ssh-keygen -y -f ~/.ssh/id_rsa > ~/.ssh/id_rsa.pub
```

#### Вариант Б: Создать новый SSH ключ

```bash
# Создайте новый SSH ключ
ssh-keygen -t rsa -b 4096 -C "deployment@31.184.253.122" -f ~/.ssh/id_rsa -N ""

# Покажите публичный ключ
cat ~/.ssh/id_rsa.pub
```

Затем добавьте публичный ключ в GitHub:
1. Скопируйте содержимое `~/.ssh/id_rsa.pub`
2. Перейдите в GitHub → Settings → Deploy keys (для конкретного репозитория)
   Или Settings → SSH and GPG keys (для всех репозиториев)
3. Нажмите "New SSH key" / "Add deploy key"
4. Вставьте ключ и сохраните

### Шаг 5: Проверьте подключение к GitHub
```bash
ssh -T git@github.com
# Должно быть: Hi username! You've successfully authenticated...
```

### Шаг 6: Клонируйте репозиторий
```bash
cd /var/www/warehouse
git clone git@github.com:siraevrus/sklad_prod.git .
```

Если возникнет ошибка, используйте HTTPS:
```bash
git clone https://github.com/siraevrus/sklad_prod.git .
```

### Шаг 7: Первоначальная настройка проекта
```bash
cd /var/www/warehouse

# Установите зависимости Composer
composer install --no-interaction --prefer-dist --optimize-autoloader

# Скопируйте .env файл
cp .env.example .env

# Сгенерируйте ключ приложения
php artisan key:generate

# Отредактируйте .env для настройки БД и других параметров
nano .env
```

### Шаг 8: Настройте базу данных
```bash
# Войдите в MySQL
mysql -u root -p

# Создайте базу данных и пользователя
CREATE DATABASE sklad_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'sklad_user'@'localhost' IDENTIFIED BY 'secure_password_here';
GRANT ALL PRIVILEGES ON sklad_db.* TO 'sklad_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Обновите `.env`:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sklad_db
DB_USERNAME=sklad_user
DB_PASSWORD=secure_password_here
```

### Шаг 9: Запустите миграции
```bash
php artisan migrate --seed
```

### Шаг 10: Соберите фронтенд
```bash
npm install
npm run build
```

### Шаг 11: Настройте права доступа
```bash
chown -R www-data:www-data /var/www/warehouse
chmod -R 755 /var/www/warehouse
chmod -R 775 /var/www/warehouse/storage
chmod -R 775 /var/www/warehouse/bootstrap/cache
```

### Шаг 12: Настройте Nginx

Создайте конфигурацию:
```bash
nano /etc/nginx/sites-available/warehouse
```

Вставьте:
```nginx
server {
    listen 80;
    server_name 31.184.253.122;
    root /var/www/warehouse/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Активируйте конфигурацию:
```bash
ln -s /etc/nginx/sites-available/warehouse /etc/nginx/sites-enabled/
nginx -t
systemctl reload nginx
```

---

## 2. Настройка GitHub Secrets

Перейдите в репозиторий: https://github.com/siraevrus/sklad_prod

### Settings → Secrets and variables → Actions → New repository secret

Добавьте следующие secrets:

#### Для НОВОГО сервера:

1. **HOST_NEW**
   ```
   31.184.253.122
   ```

2. **USERNAME_NEW**
   ```
   root
   ```

3. **SSH_KEY_NEW**
   
   На **новом сервере** выполните:
   ```bash
   cat ~/.ssh/id_rsa
   ```
   
   Скопируйте **весь** вывод (включая строки BEGIN и END) и вставьте в secret:
   ```
   -----BEGIN OPENSSH PRIVATE KEY-----
   b3BlbnNzaC1rZXktdjEAAAAABG5vbmUAAAAEbm9uZQAAAAAAAAABAAABlwAAAAdzc2gtcn
   ...
   ...весь ключ...
   ...
   -----END OPENSSH PRIVATE KEY-----
   ```

4. **PORT_NEW** (опционально)
   ```
   22
   ```

#### Для СТАРОГО сервера (если еще не настроено):

1. **HOST_OLD**: `93.189.230.65`
2. **USERNAME_OLD**: `root`
3. **SSH_KEY_OLD**: приватный SSH ключ старого сервера
4. **PORT_OLD**: `22`

---

## 3. Проверка работы

После настройки секретов:

1. Сделайте любое изменение и запушьте в main:
   ```bash
   git add .
   git commit -m "test: Check deployment to new server"
   git push origin main
   ```

2. Перейдите в GitHub → Actions и проверьте статус деплоя

3. Откройте в браузере:
   - Старый сервер: http://93.189.230.65
   - Новый сервер: http://31.184.253.122

---

## Troubleshooting

### Если деплой не работает:

1. **Проверьте SSH ключ:**
   ```bash
   ssh -T git@github.com
   ```

2. **Проверьте права доступа:**
   ```bash
   ls -la ~/.ssh/
   # id_rsa должен иметь права 600
   ```

3. **Проверьте логи GitHub Actions:**
   - GitHub → Actions → выберите workflow → смотрите лог

4. **Проверьте формат SSH ключа:**
   - Должен начинаться с `-----BEGIN OPENSSH PRIVATE KEY-----`
   - Если начинается с `-----BEGIN RSA PRIVATE KEY-----`, конвертируйте:
     ```bash
     ssh-keygen -p -m PEM -f ~/.ssh/id_rsa
     ```

5. **Проверьте доступ к серверу:**
   ```bash
   ssh -v root@31.184.253.122
   ```

---

## Полезные команды

### На сервере:

```bash
# Проверить текущую ветку
cd /var/www/warehouse && git branch

# Вручную обновить код
cd /var/www/warehouse && git pull origin main

# Проверить логи Laravel
tail -f /var/www/warehouse/storage/logs/laravel.log

# Проверить статус PHP-FPM
systemctl status php8.4-fpm

# Проверить статус Nginx
systemctl status nginx

# Пересобрать фронтенд
cd /var/www/warehouse && npm run build

# Очистить все кэши
cd /var/www/warehouse && php artisan optimize:clear
```

# Test Deployment Sun Oct 19 23:24:31 MSK 2025
