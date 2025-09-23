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
    /**
     * Build product name from template and attributes (UI-consistent).
     */
    private function buildNameFromAttributes(ProductTemplate $template, array $attributes): string
    {
        $formulaParts = [];
        $regularParts = [];

        foreach ($template->attributes as $templateAttribute) {
            $key = $templateAttribute->variable;
            if ($templateAttribute->type !== 'text' && array_key_exists($key, $attributes) && $attributes[$key] !== null && $attributes[$key] !== '') {
                $value = $attributes[$key];
                if (property_exists($templateAttribute, 'is_in_formula') && $templateAttribute->is_in_formula) {
                    $formulaParts[] = $value;
                } else {
                    $regularParts[] = $value;
                }
            }
        }

        $templateName = $template->name ?? 'Товар';
        if (empty($formulaParts) && empty($regularParts)) {
            return $templateName;
        }

        $name = $templateName;
        if (! empty($formulaParts)) {
            $name .= ': '.implode(' x ', $formulaParts);
        }
        if (! empty($regularParts)) {
            $name .= empty($formulaParts)
                ? ': '.implode(', ', $regularParts)
                : ', '.implode(', ', $regularParts);
        }

        return $name;
    }

    /**
     * Получение списка товаров
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        $query = Product::with(['template', 'warehouse', 'creator'])
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('producer', 'like', "%{$search}%");
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
            ->when($request->in_stock, function ($query) {
                $query->where('quantity', '>', 0);
            })
            ->when($request->low_stock, function ($query) {
                $query->where('quantity', '<=', 10)->where('quantity', '>', 0);
            })
            ->when($request->active, function ($query) {
                $query->where('is_active', true);
            });

        // Применяем права доступа: не админ видит только свой склад
        if (! $user->isAdmin()) {
            if ($user->warehouse_id) {
                $query->where('warehouse_id', $user->warehouse_id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

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
            'quantity' => 'required|integer|min:1',
            'transport_number' => 'nullable|string|max:255', // Номер транспортного средства
            'producer' => 'nullable|string|max:255',
            'arrival_date' => 'sometimes|date',
            'is_active' => 'boolean',
        ]);

        $user = Auth::user();

        // Проверяем права доступа к складу
        if (! $user->isAdmin()) {
            if (! $user->warehouse_id || (int) $request->warehouse_id !== (int) $user->warehouse_id) {
                return response()->json(['message' => 'Доступ к складу запрещен'], 403);
            }
        }

        // Генерируем имя из атрибутов, если оно не предоставлено (UI-consistent)
        $name = $request->name;
        $attributes = $request->get('attributes', []);
        if (! $name && ! empty($attributes)) {
            $template = ProductTemplate::find($request->product_template_id);
            if ($template) {
                $name = $this->buildNameFromAttributes($template, $attributes);
            }
        }

        $product = Product::create([
            'product_template_id' => $request->product_template_id,
            'warehouse_id' => $request->warehouse_id,
            'created_by' => $user->id,
            'name' => $name ?? 'Товар без названия',
            'description' => $request->description,
            'attributes' => $attributes,
            'quantity' => $request->quantity,
            'transport_number' => $request->get('transport_number'), // Номер транспортного средства
            'producer_id' => $request->producer_id, // Используем producer_id
            'arrival_date' => $request->get('arrival_date', now()->toDateString()),
            'is_active' => $request->get('is_active', true),
        ]);

        // Рассчитываем объем
        $product->updateCalculatedVolume();

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
            'quantity' => 'sometimes|integer|min:0',
            'transport_number' => 'nullable|string|max:255', // Номер транспортного средства
            'producer' => 'nullable|string|max:255',
            'arrival_date' => 'sometimes|date',
            'is_active' => 'boolean',
        ]);

        // If attributes provided and name not explicitly provided, regenerate name
        $payload = $request->only([
            'name', 'description', 'attributes', 'quantity',
            'transport_number', 'producer', 'arrival_date', 'is_active',
        ]);

        if ($request->has('attributes') && ! $request->filled('name')) {
            $template = ProductTemplate::find($product->product_template_id);
            if ($template) {
                $payload['name'] = $this->buildNameFromAttributes($template, $request->get('attributes', []));
            }
        }

        $product->update($payload);

        // Пересчитываем объем если изменились атрибуты
        if ($request->has('attributes')) {
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

        $query = Product::with(['template', 'warehouse', 'creator'])
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('producer', 'like', "%{$search}%");
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
