# 📋 Сводка API эндпоинтов

## 🔐 Аутентификация
- `POST /api/auth/login` - Вход в систему
- `POST /api/auth/register` - Регистрация
- `POST /api/auth/logout` - Выход
- `GET /api/auth/me` - Текущий пользователь
- `PUT /api/auth/profile` - Обновление профиля

## 📦 Товары
- `GET /api/products` - Список товаров
- `GET /api/products/{id}` - Товар по ID
- `POST /api/products` - Создание товара
- `PUT /api/products/{id}` - Обновление товара
- `DELETE /api/products/{id}` - Удаление товара
- `GET /api/products/stats` - Статистика товаров
- `GET /api/products/export` - Экспорт товаров

## 💰 Продажи
- `GET /api/sales` - Список продаж
- `GET /api/sales/{id}` - Продажа по ID
- `POST /api/sales` - Создание продажи
- `PUT /api/sales/{id}` - Обновление продажи
- `DELETE /api/sales/{id}` - Удаление продажи
- `POST /api/sales/{id}/process` - Обработка продажи
- `POST /api/sales/{id}/cancel` - Отмена продажи
- `GET /api/sales/stats` - Статистика продаж
- `GET /api/sales/export` - Экспорт продаж

## 🏢 Компании
- `GET /api/companies` - Список компаний
- `GET /api/companies/{id}` - Компания по ID
- `POST /api/companies` - Создание компании
- `PUT /api/companies/{id}` - Обновление компании
- `DELETE /api/companies/{id}` - Удаление компании
- `POST /api/companies/{id}/archive` - Архивирование
- `POST /api/companies/{id}/restore` - Восстановление

## 🏭 Склады
- `GET /api/warehouses` - Список складов
- `GET /api/warehouses/{id}` - Склад по ID
- `POST /api/warehouses` - Создание склада
- `PUT /api/warehouses/{id}` - Обновление склада
- `DELETE /api/warehouses/{id}` - Удаление склада
- `GET /api/warehouses/{id}/stats` - Статистика склада
- `GET /api/warehouses/{id}/products` - Товары склада

## 📊 Остатки
- `GET /api/stocks` - Список остатков
- `GET /api/stocks/{id}` - Остаток по ID

## 🏭 Производители
- `GET /api/producers` - Список производителей
- `GET /api/producers/{id}` - Производитель по ID
- `POST /api/producers` - Создание производителя
- `PUT /api/producers/{id}` - Обновление производителя
- `DELETE /api/producers/{id}` - Удаление производителя

## 📥 Приемка
- `GET /api/receipts` - Товары в пути
- `POST /api/receipts` - Создание товара в пути
- `GET /api/receipts/{id}` - Товар в пути по ID
- `POST /api/receipts/{id}/receive` - Приемка товара
- `POST /api/receipts/{id}/correction` - Добавление уточнения

## 👥 Пользователи
- `GET /api/users` - Список пользователей
- `GET /api/users/{id}` - Пользователь по ID
- `POST /api/users` - Создание пользователя
- `PUT /api/users/{id}` - Обновление пользователя
- `DELETE /api/users/{id}` - Удаление пользователя
- `POST /api/users/{id}/block` - Блокировка пользователя
- `POST /api/users/{id}/unblock` - Разблокировка пользователя

## 📈 Расхождения
- `GET /api/discrepancies` - Список расхождений
- `GET /api/discrepancies/{id}` - Расхождение по ID
- `POST /api/discrepancies` - Создание расхождения
- `PUT /api/discrepancies/{id}` - Обновление расхождения
- `DELETE /api/discrepancies/{id}` - Удаление расхождения

## 📊 Инфопанель
- `GET /api/dashboard/summary` - Общая сводка
- `GET /api/dashboard/revenue` - Доходы

---

**Базовый URL:** `http://93.189.230.65/api`  
**Авторизация:** `Authorization: Bearer TOKEN`
