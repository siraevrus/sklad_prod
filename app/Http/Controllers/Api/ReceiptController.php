<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductTemplate;
use App\Support\AttributeNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReceiptController extends Controller
{
    /**
     * Создать товар(ы) в пути
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        $isAdmin = $user && method_exists($user, 'isAdmin') ? $user->isAdmin() : false;

        $validated = $request->validate([
            'warehouse_id' => $isAdmin ? ['required', 'integer', 'exists:warehouses,id'] : ['nullable'],
            'shipping_location' => ['nullable', 'string', 'max:255'],
            'shipping_date' => ['nullable', 'date'],
            'transport_number' => ['nullable', 'string', 'max:255'],
            'expected_arrival_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'document_path' => ['nullable', 'array', 'max:5'],
            'document_path.*' => ['string', 'max:255'],
            'products' => ['nullable', 'array', 'min:1'],
            'products.*.product_template_id' => ['required_without:product_template_id', 'integer', 'exists:product_templates,id'],
            'products.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'products.*.producer' => ['nullable', 'string', 'max:255'],
            'products.*.description' => ['nullable', 'string', 'max:1000'],
            'products.*.name' => ['nullable', 'string', 'max:255'],
            'products.*.attributes' => ['nullable', 'array'],

            // Плоский вариант (без массива products)
            'product_template_id' => ['required_without:products', 'integer', 'exists:product_templates,id'],
            'quantity' => ['nullable', 'numeric', 'min:0'],
            'producer' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'name' => ['nullable', 'string', 'max:255'],
            'attributes' => ['nullable', 'array'],
        ]);

        $warehouseId = $isAdmin ? ($validated['warehouse_id'] ?? null) : ($user?->warehouse_id);
        if (! $warehouseId) {
            return response()->json([
                'success' => false,
                'message' => 'Склад не указан',
            ], 422);
        }

        $common = [
            'warehouse_id' => $warehouseId,
            'shipping_location' => $validated['shipping_location'] ?? null,
            'shipping_date' => $validated['shipping_date'] ?? now()->toDateString(),
            'transport_number' => $validated['transport_number'] ?? null,
            'expected_arrival_date' => $validated['expected_arrival_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'document_path' => $validated['document_path'] ?? [],
            'status' => Product::STATUS_IN_TRANSIT,
            'is_active' => true,
            'created_by' => $user?->id,
        ];

        $created = [];

        DB::beginTransaction();
        try {
            $items = [];
            if (! empty($validated['products']) && is_array($validated['products'])) {
                $items = $validated['products'];
            } else {
                $items[] = [
                    'product_template_id' => $validated['product_template_id'],
                    'quantity' => $validated['quantity'] ?? 1,
                    'producer' => $validated['producer'] ?? null,
                    'description' => $validated['description'] ?? null,
                    'name' => $validated['name'] ?? null,
                    'attributes' => $validated['attributes'] ?? [],
                ];
            }

            foreach ($items as $item) {
                // normalize quantity decimal separator
                $q = $item['quantity'] ?? 1;
                if (is_string($q)) {
                    $q = str_replace(',', '.', $q);
                }

                $productData = array_merge($common, [
                    'product_template_id' => $item['product_template_id'],
                    'quantity' => (float) ($q ?? 1),
                    'producer' => $item['producer'] ?? null,
                    'description' => $item['description'] ?? null,
                    'name' => $item['name'] ?? null,
                    'attributes' => AttributeNormalizer::normalize($item['attributes'] ?? []),
                ]);

                // Генерация имени и объёма по формуле шаблона
                $template = ProductTemplate::find($productData['product_template_id']);
                if ($template) {
                    // Имя — формируем в стиле формулы: формульные поля через "x", остальные через запятую
                    if (empty($productData['name'])) {
                        $formulaParts = [];
                        $regularParts = [];
                        $formulaAttrKeys = method_exists($template, 'formulaAttributes')
                            ? $template->formulaAttributes->pluck('variable')->toArray()
                            : [];

                        foreach ($template->attributes as $templateAttribute) {
                            $key = $templateAttribute->variable;
                            if ($templateAttribute->type !== 'text' && isset($productData['attributes'][$key]) && $productData['attributes'][$key] !== null && $productData['attributes'][$key] !== '') {
                                if (! empty($formulaAttrKeys) && in_array($key, $formulaAttrKeys, true)) {
                                    $formulaParts[] = $productData['attributes'][$key];
                                } else {
                                    $regularParts[] = $productData['attributes'][$key];
                                }
                            }
                        }

                        if (! empty($formulaParts) || ! empty($regularParts)) {
                            $generatedName = ($template->name ?? 'Товар');
                            if (! empty($formulaParts)) {
                                $generatedName .= ': '.implode(' x ', $formulaParts);
                            }
                            if (! empty($regularParts)) {
                                $generatedName .= ! empty($formulaParts)
                                    ? ', '.implode(', ', $regularParts)
                                    : ': '.implode(', ', $regularParts);
                            }
                            $productData['name'] = $generatedName;
                        }
                    }

                    // Объём
                    if ($template->formula && ! empty($productData['attributes'])) {
                        $attrsForFormula = [];
                        foreach ($productData['attributes'] as $k => $v) {
                            if (is_numeric($v)) {
                                $attrsForFormula[$k] = (float) $v;
                            }
                        }
                        $attrsForFormula['quantity'] = $productData['quantity'];

                        if (! empty($attrsForFormula)) {
                            $test = $template->testFormula($attrsForFormula);
                            if (is_array($test) && ($test['success'] ?? false)) {
                                $productData['calculated_volume'] = (float) $test['result'];
                            }
                        }
                    }
                }

                $created[] = Product::create($productData);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при создании товара(ов) в пути',
                'error' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Товар(ы) в пути успешно созданы',
            'data' => count($created) === 1 ? $created[0] : $created,
        ], 201);
    }

    /**
     * Список приемок (товары со статусом «Прибыл»)
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        // Новый блок: поддержка фильтрации по статусу
        $status = $request->get('status');
        $query = Product::query()
            ->where('is_active', true)
            ->with(['warehouse', 'template', 'creator']);
        if ($status) {
            $query->where('status', $status);
        } else {
            $query->where('status', Product::STATUS_IN_TRANSIT);
        }

        // Ограничение по складу для не-админа
        if ($user && ! $user->isAdmin()) {
            if ($user->warehouse_id) {
                $query->where('warehouse_id', $user->warehouse_id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        // Фильтры
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', (int) $request->input('warehouse_id'));
        }
        if ($request->filled('shipping_location')) {
            $query->where('shipping_location', $request->input('shipping_location'));
        }
        // Фильтр по датам (диапазоны)
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
        // actual_arrival_date (актуально для принятого товара)
        if ($request->filled('actual_arrival_date_from')) {
            $query->whereDate('actual_arrival_date', '>=', $request->input('actual_arrival_date_from'));
        }
        if ($request->filled('actual_arrival_date_to')) {
            $query->whereDate('actual_arrival_date', '<=', $request->input('actual_arrival_date_to'));
        }
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('producer', 'like', "%{$search}%")
                    ->orWhere('shipping_location', 'like', "%{$search}%")
                    ->orWhere('transport_number', 'like', "%{$search}%");
            });
        }

        // Сортировка
        $sortBy = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Пагинация
        $perPage = (int) $request->get('per_page', 15);
        $receipts = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $receipts->items(),
            'pagination' => [
                'current_page' => $receipts->currentPage(),
                'last_page' => $receipts->lastPage(),
                'per_page' => $receipts->perPage(),
                'total' => $receipts->total(),
            ],
        ]);
    }

    /**
     * Просмотр приемки
     */
    public function show(Product $receipt): JsonResponse
    {
        // Доступ только к активным в пути
        if ($receipt->status !== Product::STATUS_IN_TRANSIT || ! $receipt->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Запись не найдена',
            ], 404);
        }

        // Ограничение по складу для не-админа
        $user = Auth::user();
        if ($user && ! $user->isAdmin()) {
            if ($user->warehouse_id !== $receipt->warehouse_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Запись не найдена',
                ], 404);
            }
        }

        $receipt->load(['warehouse', 'template', 'creator']);

        return response()->json([
            'success' => true,
            'data' => $receipt,
        ]);
    }

    /**
     * Принять товар (перевести в остатки)
     */
    public function receive(Product $receipt): JsonResponse
    {
        // Ограничение по складу для не-админа
        $user = Auth::user();
        if ($user && ! $user->isAdmin()) {
            if ($user->warehouse_id !== $receipt->warehouse_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Запись не найдена',
                ], 404);
            }
        }

        $receipt->markInStock();

        return response()->json([
            'success' => true,
            'message' => 'Товар принят',
            'data' => $receipt->refresh(),
        ]);
    }

    /**
     * Добавить уточнение к товару и принять его
     */
    public function addCorrection(Request $request, Product $receipt): JsonResponse
    {
        // Проверяем, что товар в пути или готов к приемке
        if (! in_array($receipt->status, [Product::STATUS_IN_TRANSIT, Product::STATUS_FOR_RECEIPT]) || ! $receipt->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Товар не найден или не находится в пути/готов к приемке',
            ], 404);
        }

        // Ограничение по складу для не-админа
        $user = Auth::user();
        if ($user && ! $user->isAdmin()) {
            if ($user->warehouse_id !== $receipt->warehouse_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Доступ запрещен',
                ], 403);
            }
        }

        // Валидация данных
        $validated = $request->validate([
            'correction' => 'required|string|min:10|max:1000',
        ]);

        // Добавляем уточнение и принимаем товар
        $receipt->update([
            'correction' => $validated['correction'],
            'correction_status' => 'correction',
            'status' => Product::STATUS_IN_STOCK,
            'actual_arrival_date' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Уточнение сохранено и товар принят',
            'data' => $receipt->load(['template', 'warehouse', 'creator']),
        ]);
    }

    /**
     * Подтвердить корректировку товара (только смена статуса на revised)
     */
    public function confirmCorrection(Product $product): JsonResponse
    {
        // Проверяем, что товар принят и имеет уточнение
        if ($product->status !== Product::STATUS_IN_STOCK || $product->correction_status !== 'correction' || ! $product->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Товар не найден или не готов для подтверждения корректировки',
            ], 404);
        }

        // Ограничение по складу для не-админа
        $user = Auth::user();
        if ($user && ! $user->isAdmin()) {
            if ($user->warehouse_id !== $product->warehouse_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Доступ запрещен',
                ], 403);
            }
        }

        // Подтверждаем корректировку
        $product->markAsRevised();

        return response()->json([
            'success' => true,
            'message' => 'Корректировка подтверждена',
            'data' => $product->refresh()->load(['template', 'warehouse', 'creator']),
        ]);
    }
}
