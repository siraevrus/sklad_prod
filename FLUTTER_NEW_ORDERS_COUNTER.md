# Инструкция по интеграции счетчика новых заказов в Flutter приложение

## Обзор

Реализован функционал отслеживания количества новых оформлений заказа с момента последнего открытия приложения для разделов:
- **Поступление товара** (`/api/receipts`)
- **Товары в пути** (`/api/products-in-transit`)

## API Эндпоинты

### 1. Отметить открытие приложения
**POST** `/api/app/opened`

Обновляет время последнего открытия приложения для текущего пользователя.

**Заголовки:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Ответ:**
```json
{
  "success": true,
  "message": "Время открытия обновлено",
  "data": {
    "last_app_opened_at": "2025-01-15T10:30:00+00:00"
  }
}
```

### 2. Получить время последнего открытия
**GET** `/api/app/last-opened`

**Заголовки:**
```
Authorization: Bearer {token}
```

**Ответ:**
```json
{
  "success": true,
  "data": {
    "last_app_opened_at": "2025-01-15T10:30:00+00:00"
  }
}
```

### 3. Получить список поступлений с счетчиком
**GET** `/api/receipts`

**Параметры запроса:**
- `page` (int) - номер страницы (по умолчанию 1)
- `per_page` (int) - количество записей на странице (по умолчанию 15)
- `status` (string, опционально) - фильтр по статусу
- `search` (string, опционально) - поиск по названию, производителю и т.д.

**Заголовки:**
```
Authorization: Bearer {token}
```

**Ответ:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Товар",
      "status": "in_transit",
      // ... другие поля товара
    }
  ],
  "new_count": 5,
  "last_app_opened_at": "2025-01-15T10:30:00+00:00",
  "pagination": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 15,
    "total": 42
  }
}
```

### 4. Получить только счетчик новых поступлений
**GET** `/api/receipts/new-count`

**Параметры запроса:**
- `status` (string, опционально) - фильтр по статусу
- `search` (string, опционально) - поиск

**Заголовки:**
```
Authorization: Bearer {token}
```

**Ответ:**
```json
{
  "success": true,
  "data": {
    "new_count": 5,
    "last_app_opened_at": "2025-01-15T10:30:00+00:00"
  }
}
```

### 5. Получить список товаров в пути с счетчиком
**GET** `/api/products-in-transit`

Параметры и формат ответа аналогичны `/api/receipts`

### 6. Получить только счетчик новых товаров в пути
**GET** `/api/products-in-transit/new-count`

Параметры и формат ответа аналогичны `/api/receipts/new-count`

---

## Реализация в Flutter

### 1. Модели данных

Создайте файл `lib/models/app_opened_response.dart`:

```dart
class AppOpenedResponse {
  final bool success;
  final String? message;
  final AppOpenedData? data;

  AppOpenedResponse({
    required this.success,
    this.message,
    this.data,
  });

  factory AppOpenedResponse.fromJson(Map<String, dynamic> json) {
    return AppOpenedResponse(
      success: json['success'] ?? false,
      message: json['message'],
      data: json['data'] != null ? AppOpenedData.fromJson(json['data']) : null,
    );
  }
}

class AppOpenedData {
  final String? lastAppOpenedAt;

  AppOpenedData({
    this.lastAppOpenedAt,
  });

  factory AppOpenedData.fromJson(Map<String, dynamic> json) {
    return AppOpenedData(
      lastAppOpenedAt: json['last_app_opened_at'],
    );
  }
}
```

Создайте файл `lib/models/receipts_response.dart`:

```dart
class ReceiptsResponse {
  final bool success;
  final List<dynamic> data;
  final int newCount;
  final String? lastAppOpenedAt;
  final Pagination? pagination;

  ReceiptsResponse({
    required this.success,
    required this.data,
    required this.newCount,
    this.lastAppOpenedAt,
    this.pagination,
  });

  factory ReceiptsResponse.fromJson(Map<String, dynamic> json) {
    return ReceiptsResponse(
      success: json['success'] ?? false,
      data: json['data'] ?? [],
      newCount: json['new_count'] ?? 0,
      lastAppOpenedAt: json['last_app_opened_at'],
      pagination: json['pagination'] != null 
          ? Pagination.fromJson(json['pagination']) 
          : null,
    );
  }
}

class NewCountResponse {
  final bool success;
  final NewCountData? data;

  NewCountResponse({
    required this.success,
    this.data,
  });

  factory NewCountResponse.fromJson(Map<String, dynamic> json) {
    return NewCountResponse(
      success: json['success'] ?? false,
      data: json['data'] != null ? NewCountData.fromJson(json['data']) : null,
    );
  }
}

class NewCountData {
  final int newCount;
  final String? lastAppOpenedAt;

  NewCountData({
    required this.newCount,
    this.lastAppOpenedAt,
  });

  factory NewCountData.fromJson(Map<String, dynamic> json) {
    return NewCountData(
      newCount: json['new_count'] ?? 0,
      lastAppOpenedAt: json['last_app_opened_at'],
    );
  }
}

class Pagination {
  final int currentPage;
  final int lastPage;
  final int perPage;
  final int total;

  Pagination({
    required this.currentPage,
    required this.lastPage,
    required this.perPage,
    required this.total,
  });

  factory Pagination.fromJson(Map<String, dynamic> json) {
    return Pagination(
      currentPage: json['current_page'] ?? 1,
      lastPage: json['last_page'] ?? 1,
      perPage: json['per_page'] ?? 15,
      total: json['total'] ?? 0,
    );
  }
}
```

### 2. API Service

Обновите ваш API сервис (например, `lib/services/api_service.dart`):

```dart
import 'package:dio/dio.dart';
import '../models/app_opened_response.dart';
import '../models/receipts_response.dart';

class ApiService {
  final Dio _dio;
  final String baseUrl = 'https://your-api-domain.com/api';

  ApiService(this._dio);

  Future<String> _getToken() async {
    // Ваша логика получения токена из secure storage
    // Например: return await SecureStorage.getToken();
    return '';
  }

  /// Отметить открытие приложения
  Future<AppOpenedResponse> markAppOpened() async {
    try {
      final response = await _dio.post(
        '$baseUrl/app/opened',
        options: Options(
          headers: {
            'Authorization': 'Bearer ${await _getToken()}',
            'Content-Type': 'application/json',
          },
        ),
      );
      
      return AppOpenedResponse.fromJson(response.data);
    } catch (e) {
      throw Exception('Ошибка при отметке открытия приложения: $e');
    }
  }

  /// Получить время последнего открытия
  Future<AppOpenedResponse> getLastOpened() async {
    try {
      final response = await _dio.get(
        '$baseUrl/app/last-opened',
        options: Options(
          headers: {
            'Authorization': 'Bearer ${await _getToken()}',
          },
        ),
      );
      
      return AppOpenedResponse.fromJson(response.data);
    } catch (e) {
      throw Exception('Ошибка при получении времени открытия: $e');
    }
  }

  /// Получить список поступлений с счетчиком новых
  Future<ReceiptsResponse> getReceipts({
    int page = 1,
    int perPage = 15,
    String? status,
    String? search,
  }) async {
    try {
      final queryParams = <String, dynamic>{
        'page': page,
        'per_page': perPage,
        if (status != null) 'status': status,
        if (search != null) 'search': search,
      };

      final response = await _dio.get(
        '$baseUrl/receipts',
        queryParameters: queryParams,
        options: Options(
          headers: {
            'Authorization': 'Bearer ${await _getToken()}',
          },
        ),
      );
      
      return ReceiptsResponse.fromJson(response.data);
    } catch (e) {
      throw Exception('Ошибка при получении поступлений: $e');
    }
  }

  /// Получить количество новых поступлений
  Future<NewCountResponse> getReceiptsNewCount({
    String? status,
    String? search,
  }) async {
    try {
      final queryParams = <String, dynamic>{
        if (status != null) 'status': status,
        if (search != null) 'search': search,
      };

      final response = await _dio.get(
        '$baseUrl/receipts/new-count',
        queryParameters: queryParams,
        options: Options(
          headers: {
            'Authorization': 'Bearer ${await _getToken()}',
          },
        ),
      );
      
      return NewCountResponse.fromJson(response.data);
    } catch (e) {
      throw Exception('Ошибка при получении счетчика новых поступлений: $e');
    }
  }

  /// Получить список товаров в пути с счетчиком новых
  Future<ReceiptsResponse> getProductsInTransit({
    int page = 1,
    int perPage = 15,
    String? status,
    String? search,
  }) async {
    try {
      final queryParams = <String, dynamic>{
        'page': page,
        'per_page': perPage,
        if (status != null) 'status': status,
        if (search != null) 'search': search,
      };

      final response = await _dio.get(
        '$baseUrl/products-in-transit',
        queryParameters: queryParams,
        options: Options(
          headers: {
            'Authorization': 'Bearer ${await _getToken()}',
          },
        ),
      );
      
      return ReceiptsResponse.fromJson(response.data);
    } catch (e) {
      throw Exception('Ошибка при получении товаров в пути: $e');
    }
  }

  /// Получить количество новых товаров в пути
  Future<NewCountResponse> getProductsInTransitNewCount({
    String? status,
    String? search,
  }) async {
    try {
      final queryParams = <String, dynamic>{
        if (status != null) 'status': status,
        if (search != null) 'search': search,
      };

      final response = await _dio.get(
        '$baseUrl/products-in-transit/new-count',
        queryParameters: queryParams,
        options: Options(
          headers: {
            'Authorization': 'Bearer ${await _getToken()}',
          },
        ),
      );
      
      return NewCountResponse.fromJson(response.data);
    } catch (e) {
      throw Exception('Ошибка при получении счетчика новых товаров в пути: $e');
    }
  }
}
```

### 3. Главный экран приложения

Пример использования в главном экране (`lib/screens/main_screen.dart`):

```dart
import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../models/receipts_response.dart';

class MainScreen extends StatefulWidget {
  @override
  _MainScreenState createState() => _MainScreenState();
}

class _MainScreenState extends State<MainScreen> with WidgetsBindingObserver {
  final ApiService _apiService = ApiService(Dio());
  int _receiptsNewCount = 0;
  int _productsInTransitNewCount = 0;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _initializeApp();
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      // Приложение вернулось из фонового режима
      _initializeApp();
    }
  }

  Future<void> _initializeApp() async {
    // Отмечаем открытие приложения
    try {
      await _apiService.markAppOpened();
    } catch (e) {
      print('Ошибка при отметке открытия: $e');
    }

    // Загружаем счетчики новых записей
    await _loadNewCounts();
  }

  Future<void> _loadNewCounts() async {
    try {
      // Получаем счетчик для поступлений
      final receiptsCount = await _apiService.getReceiptsNewCount();
      if (receiptsCount.success && receiptsCount.data != null) {
        setState(() {
          _receiptsNewCount = receiptsCount.data!.newCount;
        });
      }

      // Получаем счетчик для товаров в пути
      final productsCount = await _apiService.getProductsInTransitNewCount();
      if (productsCount.success && productsCount.data != null) {
        setState(() {
          _productsInTransitNewCount = productsCount.data!.newCount;
        });
      }
    } catch (e) {
      print('Ошибка при загрузке счетчиков: $e');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Главная'),
      ),
      body: ListView(
        children: [
          // Раздел "Поступление товара"
          ListTile(
            leading: Icon(Icons.receipt),
            title: Text('Поступление товара'),
            trailing: _receiptsNewCount > 0
                ? Badge(
                    label: Text('$_receiptsNewCount'),
                    child: Icon(Icons.notifications),
                  )
                : null,
            onTap: () {
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (context) => ReceiptsScreen(
                    onScreenOpened: () {
                      // Обновляем счетчик при открытии экрана
                      _loadNewCounts();
                    },
                  ),
                ),
              );
            },
          ),

          // Раздел "Товары в пути"
          ListTile(
            leading: Icon(Icons.local_shipping),
            title: Text('Товары в пути'),
            trailing: _productsInTransitNewCount > 0
                ? Badge(
                    label: Text('$_productsInTransitNewCount'),
                    child: Icon(Icons.notifications),
                  )
                : null,
            onTap: () {
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (context) => ProductsInTransitScreen(
                    onScreenOpened: () {
                      // Обновляем счетчик при открытии экрана
                      _loadNewCounts();
                    },
                  ),
                ),
              );
            },
          ),
        ],
      ),
    );
  }
}
```

### 4. Экран списка поступлений

Пример экрана списка (`lib/screens/receipts_screen.dart`):

```dart
import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../models/receipts_response.dart';

class ReceiptsScreen extends StatefulWidget {
  final VoidCallback? onScreenOpened;

  ReceiptsScreen({this.onScreenOpened});

  @override
  _ReceiptsScreenState createState() => _ReceiptsScreenState();
}

class _ReceiptsScreenState extends State<ReceiptsScreen> {
  final ApiService _apiService = ApiService(Dio());
  List<dynamic> _receipts = [];
  int _newCount = 0;
  bool _isLoading = true;
  int _currentPage = 1;
  bool _hasMore = true;

  @override
  void initState() {
    super.initState();
    _loadReceipts();
    widget.onScreenOpened?.call();
  }

  Future<void> _loadReceipts({bool refresh = false}) async {
    if (refresh) {
      setState(() {
        _currentPage = 1;
        _hasMore = true;
        _receipts.clear();
      });
    }

    setState(() {
      _isLoading = true;
    });

    try {
      final response = await _apiService.getReceipts(page: _currentPage);
      if (response.success) {
        setState(() {
          if (refresh || _currentPage == 1) {
            _receipts = response.data;
          } else {
            _receipts.addAll(response.data);
          }
          _newCount = response.newCount;
          _hasMore = response.pagination != null && 
                     _currentPage < response.pagination!.lastPage;
        });
      }
    } catch (e) {
      print('Ошибка при загрузке поступлений: $e');
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Ошибка при загрузке данных')),
      );
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  Future<void> _loadMore() async {
    if (!_hasMore || _isLoading) return;

    setState(() {
      _currentPage++;
    });

    await _loadReceipts();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Поступление товара'),
        actions: [
          if (_newCount > 0)
            Padding(
              padding: EdgeInsets.all(8.0),
              child: Badge(
                label: Text('$_newCount'),
                child: Icon(Icons.new_releases),
              ),
            ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () => _loadReceipts(refresh: true),
        child: _isLoading && _receipts.isEmpty
            ? Center(child: CircularProgressIndicator())
            : ListView.builder(
                itemCount: _receipts.length + (_hasMore ? 1 : 0),
                itemBuilder: (context, index) {
                  if (index == _receipts.length) {
                    // Показать индикатор загрузки следующей страницы
                    _loadMore();
                    return Center(
                      child: Padding(
                        padding: EdgeInsets.all(16.0),
                        child: CircularProgressIndicator(),
                      ),
                    );
                  }

                  final receipt = _receipts[index];
                  // Ваша логика отображения элемента списка
                  return ListTile(
                    title: Text(receipt['name'] ?? ''),
                    subtitle: Text(receipt['status'] ?? ''),
                    // ... другие поля
                  );
                },
              ),
      ),
    );
  }
}
```

### 5. Виджет Badge

Если у вас нет встроенного виджета Badge, создайте `lib/widgets/badge.dart`:

```dart
import 'package:flutter/material.dart';

class Badge extends StatelessWidget {
  final Widget child;
  final Widget label;

  Badge({required this.child, required this.label});

  @override
  Widget build(BuildContext context) {
    return Stack(
      clipBehavior: Clip.none,
      children: [
        child,
        Positioned(
          right: -8,
          top: -8,
          child: Container(
            padding: EdgeInsets.all(4),
            decoration: BoxDecoration(
              color: Colors.red,
              shape: BoxShape.circle,
            ),
            constraints: BoxConstraints(
              minWidth: 16,
              minHeight: 16,
            ),
            child: Center(
              child: DefaultTextStyle(
                style: TextStyle(
                  color: Colors.white,
                  fontSize: 10,
                  fontWeight: FontWeight.bold,
                ),
                child: label,
              ),
            ),
          ),
        ),
      ],
    );
  }
}
```

---

## Резюме интеграции

1. **При открытии приложения** вызывайте `POST /api/app/opened` для обновления времени последнего открытия.

2. **При загрузке списков** используйте `GET /api/receipts` или `GET /api/products-in-transit` — они возвращают поле `new_count` с количеством новых записей.

3. **Отображайте badge** с количеством новых записей из поля `new_count` на главном экране и в заголовках экранов списков.

4. **Обновляйте счетчики** при возврате на главный экран (через `didChangeAppLifecycleState`) или при pull-to-refresh.

5. **Для быстрого получения только счетчика** используйте эндпоинты `/api/receipts/new-count` и `/api/products-in-transit/new-count`.

---

## Логика работы

- Если `last_app_opened_at` равно `null` (первое открытие), все записи считаются новыми.
- Если `last_app_opened_at` установлено, считаются записи, созданные после этого времени (`created_at > last_app_opened_at`).
- Время синхронизируется с сервером, поэтому используется серверное время.

---

## Примечания

- Все эндпоинты требуют аутентификации через Bearer токен.
- Счетчики обновляются автоматически при каждом запросе списка.
- Для оптимизации можно использовать отдельные эндпоинты `/new-count` для быстрого получения только счетчика без загрузки полного списка.

