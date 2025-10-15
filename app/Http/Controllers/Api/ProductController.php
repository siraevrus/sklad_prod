<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    // (helper removed)

    /**
     * Получение списка товаров (агрегированных)
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        // Проверяем, нужна ли агрегация (для остатков на складе)
        $aggregate = $request->boolean('aggregate', false);

        if ($aggregate && $request->status === 'in_stock') {
            return $this->getAggregatedProducts($request, $user);
        }

        // Обычный запрос без агрегации
        $query = Product::with(['template', 'warehouse', 'creator', 'producer'])
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('producer', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            })
            ->when($request->warehouse_id, function ($query, $warehouseId) {
                $query->where('warehouse_id', $warehouseId);
            })
            ->when($request->template_id, function ($query, $templateId) {
                $query->where('product_template_id', $templateId);
            })
            ->when($request->producer_id, function ($query, $producerId) {
                $query->where('producer_id', $producerId);
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->in_stock, function ($query) {
                $query->where('quantity', '>', 0);
            })
            ->when($request->low_stock, function ($query) {
                $query->where('quantity', '<=', 10)->where('quantity', '>', 0);
            })
            ->when($request->active, function ($query) {
                $query->where('is_active', true);
            });

        // Фильтры по датам (диапазоны)
        // created_at
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }
        // shipping_date
        if ($request->filled('shipping_date_from')) {
            $query->whereDate('shipping_date', '>=', $request->input('shipping_date_from'));
        }
        if ($request->filled('shipping_date_to')) {
            $query->whereDate('shipping_date', '<=', $request->input('shipping_date_to'));
        }
        // expected_arrival_date
        if ($request->filled('expected_arrival_date_from')) {
            $query->whereDate('expected_arrival_date', '>=', $request->input('expected_arrival_date_from'));
        }
        if ($request->filled('expected_arrival_date_to')) {
            $query->whereDate('expected_arrival_date', '<=', $request->input('expected_arrival_date_to'));
        }
        // actual_arrival_date
        if ($request->filled('actual_arrival_date_from')) {
            $query->whereDate('actual_arrival_date', '>=', $request->input('actual_arrival_date_from'));
        }
        if ($request->filled('actual_arrival_date_to')) {
            $query->whereDate('actual_arrival_date', '<=', $request->input('actual_arrival_date_to'));
        }

        // Применяем права доступа: не админ видит только свой склад
        if (! $user->isAdmin()) {
            if ($user->warehouse_id) {
                $query->where('warehouse_id', $user->warehouse_id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        // Сортировка по дате обновления (новые сначала)
        $query->orderBy('updated_at', 'desc');

        $products = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => $products->items(),
            'links' => [
                'first' => $products->url(1),
                'last' => $products->url($products->lastPage()),
                'prev' => $products->previousPageUrl(),
                'next' => $products->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    /**
     * Получение агрегированных товаров (по аналогии со StockResource)
     */
    private function getAggregatedProducts(Request $request, $user): JsonResponse
    {
        $baseQuery = Product::query()
            ->where('status', Product::STATUS_IN_STOCK)
            ->where('is_active', true);

        // Применяем права доступа
        if (! $user->isAdmin()) {
            if ($user->warehouse_id) {
                $baseQuery->where('warehouse_id', $user->warehouse_id);
            } else {
                $baseQuery->whereRaw('1 = 0');
            }
        }

        // Фильтр по складу
        if ($request->warehouse_id) {
            $baseQuery->where('warehouse_id', $request->warehouse_id);
        }

        // Получаем переменные характеристик для группировки (только number и select типы)
        $groupingVariables = \App\Models\ProductAttribute::query()
            ->whereIn('type', ['number', 'select'])
            ->distinct()
            ->pluck('variable')
            ->toArray();

        // Создаем GROUP BY условия для группировки по характеристикам
        $groupByAttributes = [];
        $jsonExtracts = [];

        foreach ($groupingVariables as $variable) {
            $groupByAttributes[] = \Illuminate\Support\Facades\DB::raw("JSON_EXTRACT(attributes, \"$.{$variable}\")");
            $jsonExtracts[] = "COALESCE(JSON_EXTRACT(attributes, \"$.{$variable}\"), \"\")";
        }

        // Агрегированный запрос
        $query = $baseQuery
            ->select([
                \Illuminate\Support\Facades\DB::raw('MIN(name) as name'),
                'product_template_id',
                'warehouse_id',
                'producer_id',
                \Illuminate\Support\Facades\DB::raw('MIN(attributes) as attributes'),
                \Illuminate\Support\Facades\DB::raw('SUM(quantity) as quantity'),
                \Illuminate\Support\Facades\DB::raw('SUM(quantity - COALESCE(sold_quantity, 0)) as available_quantity'),
                \Illuminate\Support\Facades\DB::raw('SUM(COALESCE(sold_quantity, 0)) as sold_quantity'),
                \Illuminate\Support\Facades\DB::raw('SUM(calculated_volume) as total_volume'),
            ])
            ->with(['producer', 'productTemplate', 'warehouse'])
            ->groupBy(array_merge([
                'product_template_id',
                'warehouse_id',
                'producer_id',
            ], $groupByAttributes))
            ->orderBy('name')
            ->orderBy('producer_id');

        $perPage = $request->get('per_page', 15);
        $products = $query->paginate($perPage);

        // Форматируем данные для вывода
        $formattedData = $products->getCollection()->map(function ($product) {
            return [
                'product_template_id' => $product->product_template_id,
                'name' => $product->name,
                'warehouse' => $product->warehouse ? $product->warehouse->name : null,
                'producer' => $product->producer ? $product->producer->name : null,
                'quantity' => (float) $product->quantity,
                'available_quantity' => (float) $product->available_quantity,
                'sold_quantity' => (float) $product->sold_quantity,
                'total_volume' => $product->total_volume ? round((float) $product->total_volume, 3) : 0,
            ];
        });

        return response()->json([
            'data' => $formattedData,
            'links' => [
                'first' => $products->url(1),
                'last' => $products->url($products->lastPage()),
                'prev' => $products->previousPageUrl(),
                'next' => $products->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    /**
     * Получение товара по ID
     */
    public function showById(int $id): JsonResponse
    {
        $user = Auth::user();
        $product = Product::with(['template', 'warehouse', 'creator'])->find($id);
        if (! $product) {
            return response()->json(['message' => 'Товар не найден'], 404);
        }
        if (! $user->isAdmin() && $user->warehouse_id !== $product->warehouse_id) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        return response()->json($product);
    }

    /**
     * Создание нового товара
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'product_template_id' => 'required|exists:product_templates,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'name' => 'nullable|string|max:255', // Теперь не обязательно, генерируется автоматически
            'description' => 'nullable|string',
            'attributes' => 'sometimes|array',
            'quantity' => 'required|numeric|min:0',
            'calculated_volume' => 'nullable|numeric|min:0',
            'transport_number' => 'nullable|string|max:255', // Номер транспортного средства
            'producer' => 'nullable|string|max:255',
            'producer_id' => 'nullable|exists:producers,id',
            'arrival_date' => 'sometimes|date',
            'is_active' => 'boolean',
            // Дополнительные поля для статуса "в пути"
            'status' => 'sometimes|in:in_stock,in_transit,for_receipt',
            'shipping_location' => 'nullable|string|max:255',
            'shipping_date' => 'nullable|date',
            'expected_arrival_date' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
            'document_path' => 'nullable|array|max:5',
            'document_path.*' => 'string|max:255',
        ]);

        $user = Auth::user();

        // Проверяем права доступа к складу
        if (! $user->isAdmin()) {
            if (! $user->warehouse_id || (int) $request->warehouse_id !== (int) $user->warehouse_id) {
                return response()->json(['message' => 'Доступ к складу запрещен'], 403);
            }
        }

        // Генерируем простое имя из атрибутов, если оно не предоставлено
        $name = $request->name;
        if (! $name && $request->has('attributes')) {
            // Важно: $request->attributes — это служебный ParameterBag, а не входные данные
            // Забираем пользовательские атрибуты корректно из input
            $inputAttributes = (array) $request->input('attributes', []);
            $template = ProductTemplate::find($request->product_template_id);
            if ($template) {
                $formulaParts = [];
                $regularParts = [];
                // Если у шаблона есть явные поля формулы
                $formulaAttrKeys = method_exists($template, 'formulaAttributes')
                    ? $template->formulaAttributes->pluck('variable')->toArray()
                    : [];

                foreach ($template->attributes as $templateAttribute) {
                    $attributeKey = $templateAttribute->variable;
                    if ($templateAttribute->type !== 'text' && array_key_exists($attributeKey, $inputAttributes) && $inputAttributes[$attributeKey] !== null && $inputAttributes[$attributeKey] !== '') {
                        if (! empty($formulaAttrKeys) && in_array($attributeKey, $formulaAttrKeys, true)) {
                            $formulaParts[] = $inputAttributes[$attributeKey];
                        } else {
                            $regularParts[] = $inputAttributes[$attributeKey];
                        }
                    }
                }

                $generatedName = $template->name ?? 'Товар';
                if (! empty($formulaParts)) {
                    $generatedName .= ': '.implode(' x ', $formulaParts);
                }
                if (! empty($regularParts)) {
                    $generatedName .= ! empty($formulaParts)
                        ? ', '.implode(', ', $regularParts)
                        : ': '.implode(', ', $regularParts);
                }

                $name = $generatedName;
            }
        }

        $product = Product::create([
            'product_template_id' => $request->product_template_id,
            'warehouse_id' => $request->warehouse_id,
            'created_by' => $user->id,
            'name' => $name ?? 'Товар без названия',
            'description' => $request->description,
            'attributes' => $request->get('attributes', []),
            'quantity' => (int) $request->quantity,
            'calculated_volume' => $request->get('calculated_volume'),
            'transport_number' => $request->get('transport_number'), // Номер транспортного средства
            'producer_id' => $request->producer_id, // Используем producer_id
            'arrival_date' => $request->get('arrival_date', now()->toDateString()),
            'is_active' => $request->get('is_active', true),
            // Поля доставки, если переданы
            'shipping_location' => $request->get('shipping_location'),
            'shipping_date' => $request->get('shipping_date'),
            'expected_arrival_date' => $request->get('expected_arrival_date'),
            'notes' => $request->get('notes'),
            'document_path' => $request->get('document_path', []),
            // Статус, если передан, иначе по умолчанию остаётся in_stock (по миграции)
            'status' => $request->get('status', Product::STATUS_IN_STOCK),
        ]);

        // Рассчитываем объем только если он не был передан вручную
        if (! $request->has('calculated_volume') || $request->get('calculated_volume') === null) {
            $product->updateCalculatedVolume();
        }

        // Если создаём как "в пути" — фиксируем это и создаём запись в таблице товаров в пути
        if ($request->get('status') === Product::STATUS_IN_TRANSIT) {
            $product->markInTransit();
        }

        return response()->json([
            'message' => 'Товар создан',
            'product' => $product->load(['template', 'warehouse', 'creator']),
        ], 201);
    }

    /**
     * Обновление товара
     */
    public function update(Request $request, Product $product): JsonResponse
    {
        $user = Auth::user();

        // Проверяем права доступа
        if (! $user->isAdmin() && $user->warehouse_id !== $product->warehouse_id) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'attributes' => 'sometimes|array',
            'quantity' => 'sometimes|numeric|min:0',
            'calculated_volume' => 'nullable|numeric|min:0',
            'transport_number' => 'nullable|string|max:255', // Номер транспортного средства
            'producer' => 'nullable|string|max:255',
            'producer_id' => 'sometimes|integer|exists:producers,id',
            'arrival_date' => 'sometimes|date',
            'is_active' => 'boolean',
            'notes' => 'nullable|string|max:1000',
            'warehouse_id' => 'sometimes|integer|exists:warehouses,id',
        ]);

        $updateData = $request->only([
            'name', 'description', 'attributes', 'quantity', 'calculated_volume',
            'transport_number', 'producer', 'producer_id', 'arrival_date', 'is_active', 'notes',
        ]);

        // Перенос между складами разрешим только администратору
        if ($request->has('warehouse_id')) {
            if (! $user->isAdmin()) {
                return response()->json(['message' => 'Изменение склада запрещено'], 403);
            }
            $updateData['warehouse_id'] = (int) $request->integer('warehouse_id');
        }

        // Нормализуем десятичный разделитель для quantity
        if (array_key_exists('quantity', $updateData)) {
            $q = $updateData['quantity'];
            if (is_string($q)) {
                $q = str_replace(',', '.', $q);
            }
            $updateData['quantity'] = (float) $q;
        }

        $product->update($updateData);

        // Пересчитываем объем если изменились атрибуты или количество, но не если передан calculated_volume
        if (($request->has('attributes') || $request->has('quantity')) && ! $request->has('calculated_volume')) {
            $product->updateCalculatedVolume();
        }

        return response()->json([
            'message' => 'Товар обновлен',
            'product' => $product->load(['template', 'warehouse', 'creator']),
        ]);
    }

    /**
     * Удаление товара
     */
    public function destroy(Product $product): JsonResponse
    {
        $user = Auth::user();

        // Проверяем права доступа
        if (! $user->isAdmin() && $user->warehouse_id !== $product->warehouse_id) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        $product->delete();

        return response()->json([
            'message' => 'Товар удален',
        ]);
    }

    /**
     * Получение статистики по товарам
     */
    public function stats(): JsonResponse
    {
        $user = Auth::user();

        // Кешируем статистику на 5 минут (v2 — чтобы сбросить старый кэш)
        $cacheKey = "product_stats_v2_{$user->id}";

        $stats = Cache::remember($cacheKey, 300, function () use ($user) {
            $baseQuery = Product::query();

            // Применяем права доступа: не админ видит только свой склад
            if (! $user->isAdmin()) {
                if ($user->warehouse_id) {
                    $baseQuery->where('warehouse_id', $user->warehouse_id);
                } else {
                    $baseQuery->whereRaw('1 = 0');
                }
            }

            $totalProducts = (clone $baseQuery)->count();
            $activeProducts = (clone $baseQuery)->where('is_active', true)->count();
            $inStock = (clone $baseQuery)->where('quantity', '>', 0)->count();
            $lowStock = (clone $baseQuery)->where('quantity', '<=', 10)->where('quantity', '>', 0)->count();
            $outOfStock = (clone $baseQuery)->where('quantity', '<=', 0)->count();
            $totalQuantity = (clone $baseQuery)->sum('quantity');
            $totalVolume = (clone $baseQuery)->sum('calculated_volume');

            return [
                'total_products' => $totalProducts,
                'active_products' => $activeProducts,
                'in_stock' => $inStock,
                'low_stock' => $lowStock,
                'out_of_stock' => $outOfStock,
                'total_quantity' => $totalQuantity,
                'total_volume' => $totalVolume,
            ];
        });

        // Старый формат, ожидаемый тестами
        return response()->json(array_merge($stats, [
            'low_stock_count' => $stats['low_stock'],
            'out_of_stock_count' => $stats['out_of_stock'],
        ]));
    }

    /**
     * Получение популярных товаров
     */
    public function popular(): JsonResponse
    {
        $user = Auth::user();

        $query = Product::with(['template', 'warehouse'])
            ->withCount(['sales as total_sales'])
            ->withSum(['sales as total_revenue'], 'total_price')
            ->orderByDesc('total_sales')
            ->limit(10);

        // Применяем права доступа
        if (! $user->isAdmin()) {
            if ($user->warehouse_id) {
                $query->where('warehouse_id', $user->warehouse_id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $products = $query->get();

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    /**
     * Экспорт товаров
     */
    public function export(Request $request): JsonResponse
    {
        $user = Auth::user();

        $query = Product::with(['template', 'warehouse', 'creator', 'producer'])
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('producer', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            })
            ->when($request->warehouse_id, function ($query, $warehouseId) {
                $query->where('warehouse_id', $warehouseId);
            })
            ->when($request->template_id, function ($query, $templateId) {
                $query->where('product_template_id', $templateId);
            })
            ->when($request->producer_id, function ($query, $producerId) {
                $query->where('producer_id', $producerId);
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->in_stock, function ($query) {
                $query->where('quantity', '>', 0);
            })
            ->when($request->low_stock, function ($query) {
                $query->where('quantity', '<=', 10)->where('quantity', '>', 0);
            })
            ->when($request->active, function ($query) {
                $query->where('is_active', true);
            });

        // Применяем права доступа
        if (! $user->isAdmin()) {
            if ($user->warehouse_id) {
                $query->where('warehouse_id', $user->warehouse_id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $products = $query->get();

        // Формируем данные для экспорта
        $exportData = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'producer_id' => $product->producer_id, // Используем producer_id
                'quantity' => $product->quantity,
                'calculated_volume' => $product->calculated_volume,
                'warehouse' => $product->warehouse->name ?? '',
                'template' => $product->template->name ?? '',
                'arrival_date' => $product->arrival_date,
                'is_active' => $product->is_active ? 'Да' : 'Нет',
                'created_at' => $product->created_at->format('Y-m-d H:i:s'),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $exportData,
            'total' => $exportData->count(),
        ]);
    }
}
