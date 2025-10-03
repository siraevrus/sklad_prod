# API документация: Загрузка документов (File Upload)

## Базовая информация

**Base URL:** `https://your-domain.com/api`  
**Аутентификация:** Bearer Token (Sanctum)  
**Content-Type:** `multipart/form-data` (для загрузки файлов)  
**Заголовки:**
```
Authorization: Bearer {token}
Accept: application/json
```

---

## 1. ОСНОВНЫЕ ОПЕРАЦИИ С ФАЙЛАМИ

### 1.1 Загрузка одного документа

**Endpoint:** `POST /api/files/upload`

**Описание:** Загрузка одного документа на сервер

**Content-Type:** `multipart/form-data`

**Параметры:**
- `file` (обязательный) - Файл для загрузки
- `description` (необязательный) - Описание файла

**Ограничения:**
- Максимальный размер: 50MB
- Поддерживаемые типы: PDF, JPG, JPEG, PNG, DOC, DOCX, TXT

**Пример запроса:**
```bash
curl -X POST "https://domain.com/api/files/upload" \
  -H "Authorization: Bearer {token}" \
  -F "file=@document.pdf" \
  -F "description=Документ поставки"
```

**Ответ:**
```json
{
  "success": true,
  "message": "Документ успешно загружен",
  "data": {
    "file_info": {
      "path": "documents/550e8400-e29b-41d4-a716-446655440000.pdf",
      "original_name": "document.pdf",
      "size": 1024000,
      "mime_type": "application/pdf",
      "extension": "pdf",
      "uploaded_at": "2024-01-15T10:00:00.000000Z",
      "uploaded_by": 1,
      "description": "Документ поставки"
    },
    "url": "https://domain.com/storage/documents/550e8400-e29b-41d4-a716-446655440000.pdf",
    "download_url": "https://domain.com/api/files/550e8400-e29b-41d4-a716-446655440000.pdf/download"
  }
}
```

### 1.2 Загрузка нескольких документов

**Endpoint:** `POST /api/files/upload-multiple`

**Описание:** Загрузка нескольких документов одновременно

**Content-Type:** `multipart/form-data`

**Параметры:**
- `files[]` (обязательный) - Массив файлов для загрузки (максимум 5)
- `description` (необязательный) - Общее описание для всех файлов

**Пример запроса:**
```bash
curl -X POST "https://domain.com/api/files/upload-multiple" \
  -H "Authorization: Bearer {token}" \
  -F "files[]=@document1.pdf" \
  -F "files[]=@document2.jpg" \
  -F "description=Документы поставки"
```

**Ответ:**
```json
{
  "success": true,
  "message": "2 файлов загружено успешно",
  "data": {
    "uploaded_files": [
      {
        "file_info": {
          "path": "documents/550e8400-e29b-41d4-a716-446655440000.pdf",
          "original_name": "document1.pdf",
          "size": 1024000,
          "mime_type": "application/pdf",
          "extension": "pdf",
          "uploaded_at": "2024-01-15T10:00:00.000000Z",
          "uploaded_by": 1,
          "description": "Документы поставки"
        },
        "url": "https://domain.com/storage/documents/550e8400-e29b-41d4-a716-446655440000.pdf",
        "download_url": "https://domain.com/api/files/550e8400-e29b-41d4-a716-446655440000.pdf/download"
      },
      {
        "file_info": {
          "path": "documents/550e8400-e29b-41d4-a716-446655440001.jpg",
          "original_name": "document2.jpg",
          "size": 512000,
          "mime_type": "image/jpeg",
          "extension": "jpg",
          "uploaded_at": "2024-01-15T10:00:00.000000Z",
          "uploaded_by": 1,
          "description": "Документы поставки"
        },
        "url": "https://domain.com/storage/documents/550e8400-e29b-41d4-a716-446655440001.jpg",
        "download_url": "https://domain.com/api/files/550e8400-e29b-41d4-a716-446655440001.jpg/download"
      }
    ],
    "errors": [],
    "total_uploaded": 2,
    "total_errors": 0
  }
}
```

### 1.3 Скачивание документа

**Endpoint:** `GET /api/files/{file}/download`

**Описание:** Скачивание документа по имени файла

**Пример запроса:**
```bash
curl -X GET "https://domain.com/api/files/550e8400-e29b-41d4-a716-446655440000.pdf/download" \
  -H "Authorization: Bearer {token}" \
  -o "downloaded_document.pdf"
```

**Ответ:** Файл в бинарном виде для скачивания

### 1.4 Получение информации о документе

**Endpoint:** `GET /api/files/{file}/info`

**Описание:** Получение информации о документе без скачивания

**Пример запроса:**
```bash
curl -X GET "https://domain.com/api/files/550e8400-e29b-41d4-a716-446655440000.pdf/info" \
  -H "Authorization: Bearer {token}"
```

**Ответ:**
```json
{
  "success": true,
  "data": {
    "path": "documents/550e8400-e29b-41d4-a716-446655440000.pdf",
    "size": 1024000,
    "mime_type": "application/pdf",
    "last_modified": 1705312800,
    "url": "https://domain.com/storage/documents/550e8400-e29b-41d4-a716-446655440000.pdf",
    "download_url": "https://domain.com/api/files/550e8400-e29b-41d4-a716-446655440000.pdf/download"
  }
}
```

### 1.5 Удаление документа

**Endpoint:** `DELETE /api/files/{file}`

**Описание:** Удаление документа с сервера

**Пример запроса:**
```bash
curl -X DELETE "https://domain.com/api/files/550e8400-e29b-41d4-a716-446655440000.pdf" \
  -H "Authorization: Bearer {token}"
```

**Ответ:**
```json
{
  "success": true,
  "message": "Документ успешно удален"
}
```

### 1.6 Получение списка документов

**Endpoint:** `GET /api/files/list`

**Описание:** Получение списка всех документов с пагинацией

**Query параметры:**
```
per_page - Количество на странице (по умолчанию: 20)
page     - Номер страницы (по умолчанию: 1)
```

**Пример запроса:**
```bash
curl -X GET "https://domain.com/api/files/list?per_page=10&page=1" \
  -H "Authorization: Bearer {token}"
```

**Ответ:**
```json
{
  "success": true,
  "data": [
    {
      "name": "550e8400-e29b-41d4-a716-446655440000.pdf",
      "path": "documents/550e8400-e29b-41d4-a716-446655440000.pdf",
      "size": 1024000,
      "mime_type": "application/pdf",
      "last_modified": 1705312800,
      "url": "https://domain.com/storage/documents/550e8400-e29b-41d4-a716-446655440000.pdf",
      "download_url": "https://domain.com/api/files/550e8400-e29b-41d4-a716-446655440000.pdf/download"
    },
    {
      "name": "550e8400-e29b-41d4-a716-446655440001.jpg",
      "path": "documents/550e8400-e29b-41d4-a716-446655440001.jpg",
      "size": 512000,
      "mime_type": "image/jpeg",
      "last_modified": 1705312700,
      "url": "https://domain.com/storage/documents/550e8400-e29b-41d4-a716-446655440001.jpg",
      "download_url": "https://domain.com/api/files/550e8400-e29b-41d4-a716-446655440001.jpg/download"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 25,
    "last_page": 3
  }
}
```

---

## 2. ИНТЕГРАЦИЯ С ТОВАРАМИ

### 2.1 Добавление документов к товару

После загрузки файла через API, вы можете добавить его к товару:

**Пример:**
```bash
# 1. Загружаем документ
curl -X POST "https://domain.com/api/files/upload" \
  -H "Authorization: Bearer {token}" \
  -F "file=@invoice.pdf"

# Ответ: {"data": {"file_info": {"path": "documents/abc123.pdf"}}}

# 2. Создаем товар с документом
curl -X POST "https://domain.com/api/products" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "product_template_id": 1,
    "warehouse_id": 1,
    "quantity": 100,
    "document_path": ["documents/abc123.pdf"]
  }'
```

### 2.2 Обновление документов товара

**Пример:**
```bash
# Добавляем новый документ к существующему товару
curl -X PUT "https://domain.com/api/products/1" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "document_path": ["documents/abc123.pdf", "documents/def456.jpg"]
  }'
```

### 2.3 Получение товара с документами

**Пример:**
```bash
curl -X GET "https://domain.com/api/products/1" \
  -H "Authorization: Bearer {token}"
```

**Ответ включает:**
```json
{
  "id": 1,
  "name": "Доска: 6000 x 200 x 50",
  "document_path": ["documents/abc123.pdf", "documents/def456.jpg"],
  "document_urls": [
    "https://domain.com/storage/documents/abc123.pdf",
    "https://domain.com/storage/documents/def456.jpg"
  ],
  "documents_count": 2,
  "has_documents": true
}
```

---

## 3. МЕТОДЫ МОДЕЛИ PRODUCT

### 3.1 Работа с документами через модель

```php
// Получить URL документов
$product = Product::find(1);
$urls = $product->document_urls; // Массив URL

// Добавить документ
$product->addDocument('documents/new_file.pdf');

// Удалить документ
$product->removeDocument('documents/old_file.pdf');

// Проверить наличие документов
if ($product->hasDocuments()) {
    echo "У товара есть документы";
}

// Получить количество документов
$count = $product->documents_count;
```

---

## 4. ВАЛИДАЦИЯ И ОГРАНИЧЕНИЯ

### 4.1 Ограничения файлов

- **Максимальный размер:** 50MB (51,200 KB)
- **Максимальное количество:** 5 файлов за раз
- **Поддерживаемые типы:**
  - PDF: `application/pdf`
  - Изображения: `image/jpeg`, `image/jpg`, `image/png`
  - Документы Word: `application/msword`, `application/vnd.openxmlformats-officedocument.wordprocessingml.document`
  - Текстовые файлы: `text/plain`

### 4.2 Валидация в API

```php
// Для товаров
'document_path' => 'nullable|array|max:5',
'document_path.*' => 'string|max:255',

// Для загрузки файлов
'file' => 'required|file|max:51200|mimes:pdf,jpg,jpeg,png,doc,docx,txt',
'files' => 'required|array|min:1|max:5',
'files.*' => 'file|max:51200|mimes:pdf,jpg,jpeg,png,doc,docx,txt',
```

---

## 5. КОДЫ ОШИБОК

### HTTP коды ответов:
- `200` - Успешный запрос
- `201` - Файл загружен
- `400` - Неверный запрос
- `401` - Не авторизован
- `403` - Доступ запрещен
- `404` - Файл не найден
- `413` - Файл слишком большой
- `422` - Ошибка валидации
- `500` - Внутренняя ошибка сервера

### Примеры ошибок:

**Файл слишком большой (413):**
```json
{
  "message": "The file field must not be greater than 51200 kilobytes.",
  "errors": {
    "file": [
      "The file field must not be greater than 51200 kilobytes."
    ]
  }
}
```

**Неподдерживаемый тип файла (422):**
```json
{
  "message": "The file field must be a file of type: pdf, jpg, jpeg, png, doc, docx, txt.",
  "errors": {
    "file": [
      "The file field must be a file of type: pdf, jpg, jpeg, png, doc, docx, txt."
    ]
  }
}
```

**Файл не найден (404):**
```json
{
  "success": false,
  "message": "Файл не найден"
}
```

---

## 6. ПРИМЕРЫ ИСПОЛЬЗОВАНИЯ

### 6.1 Полный цикл работы с документами

```bash
# 1. Загружаем документ
curl -X POST "https://domain.com/api/files/upload" \
  -H "Authorization: Bearer {token}" \
  -F "file=@invoice.pdf" \
  -F "description=Счет-фактура"

# 2. Получаем информацию о загруженном файле
curl -X GET "https://domain.com/api/files/550e8400-e29b-41d4-a716-446655440000.pdf/info" \
  -H "Authorization: Bearer {token}"

# 3. Создаем товар с документом
curl -X POST "https://domain.com/api/products" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "product_template_id": 1,
    "warehouse_id": 1,
    "quantity": 100,
    "document_path": ["documents/550e8400-e29b-41d4-a716-446655440000.pdf"]
  }'

# 4. Скачиваем документ
curl -X GET "https://domain.com/api/files/550e8400-e29b-41d4-a716-446655440000.pdf/download" \
  -H "Authorization: Bearer {token}" \
  -o "downloaded_invoice.pdf"

# 5. Удаляем документ
curl -X DELETE "https://domain.com/api/files/550e8400-e29b-41d4-a716-446655440000.pdf" \
  -H "Authorization: Bearer {token}"
```

### 6.2 Загрузка нескольких документов

```bash
# Загружаем несколько файлов
curl -X POST "https://domain.com/api/files/upload-multiple" \
  -H "Authorization: Bearer {token}" \
  -F "files[]=@invoice.pdf" \
  -F "files[]=@photo.jpg" \
  -F "files[]=@contract.docx" \
  -F "description=Документы поставки"

# Создаем товар с несколькими документами
curl -X POST "https://domain.com/api/products" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "product_template_id": 1,
    "warehouse_id": 1,
    "quantity": 100,
    "document_path": [
      "documents/550e8400-e29b-41d4-a716-446655440000.pdf",
      "documents/550e8400-e29b-41d4-a716-446655440001.jpg",
      "documents/550e8400-e29b-41d4-a716-446655440002.docx"
    ]
  }'
```

---

## 7. БЕЗОПАСНОСТЬ

### 7.1 Аутентификация
- Все эндпоинты требуют Bearer Token
- Токен должен быть действительным и не истекшим

### 7.2 Авторизация
- Пользователи могут загружать и управлять только своими файлами
- Администраторы имеют доступ ко всем файлам

### 7.3 Валидация файлов
- Проверка MIME-типа
- Ограничение размера файла
- Генерация уникальных имен файлов
- Сохранение в изолированной директории

### 7.4 Очистка
- Регулярная очистка неиспользуемых файлов
- Логирование всех операций с файлами
- Мониторинг использования дискового пространства

---

## 8. МОНИТОРИНГ И ЛОГИРОВАНИЕ

### 8.1 Логирование операций
- Загрузка файлов
- Удаление файлов
- Ошибки валидации
- Попытки несанкционированного доступа

### 8.2 Метрики
- Количество загруженных файлов
- Общий размер файлов
- Популярные типы файлов
- Частота использования API

---

Эта документация покрывает все аспекты работы с файлами через API, включая загрузку, управление, интеграцию с товарами и безопасность.
