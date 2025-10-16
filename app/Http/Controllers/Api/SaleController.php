<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SaleController extends Controller
{
    /**
     * Получение списка продаж
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        $query = Sale::with(['product', 'warehouse', 'user'])
            ->when($request->search, function ($query, $search) {
                $query->where('sale_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%");
            })
            ->when($request->warehouse_id, function ($query, $warehouseId) {
                $query->where('warehouse_id', $warehouseId);
            })
            ->when($request->payment_status, function ($query, $status) {
                $query->where('payment_status', $status);
            })
            ->when($request->payment_method, function ($query, $method) {
                $query->where('payment_method', $method);
            })
            ->when($request->date_from, function ($query, $date) {
                $query->where('sale_date', '>=', $date);
            })
            ->when($request->date_to, function ($query, $date) {
                $query->where('sale_date', '<=', $date);
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

        $sales = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => $sales->items(),
            'links' => [
                'first' => $sales->url(1),
                'last' => $sales->url($sales->lastPage()),
                'prev' => $sales->previousPageUrl(),
                'next' => $sales->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $sales->currentPage(),
                'last_page' => $sales->lastPage(),
                'per_page' => $sales->perPage(),
                'total' => $sales->total(),
            ],
        ]);
    }

    /**
     * Получение продажи по ID
     */
    public function showById(int $id): JsonResponse
    {
        $user = Auth::user();
        $sale = Sale::with(['product', 'warehouse', 'user'])->find($id);
        if (! $sale) {
            return response()->json(['message' => 'Продажа не найдена'], 404);
        }

        if (! $user->isAdmin() && $user->warehouse_id !== $sale->warehouse_id) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        return response()->json($sale);
    }

    /**
     * Создание новой продажи
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'composite_product_key' => 'required|string',
            'warehouse_id' => 'required|exists:warehouses,id',
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'nullable|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'customer_address' => 'nullable|string',
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'nullable|numeric|min:0',
            'total_price' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string|in:cash,card,bank_transfer,other',
            'payment_status' => 'nullable|string|in:pending,paid,partially_paid,cancelled',
            'currency' => 'nullable|string|max:3',
            'exchange_rate' => 'nullable|numeric|min:0',
            'cash_amount' => 'nullable|numeric|min:0',
            'nocash_amount' => 'nullable|numeric|min:0',
            'invoice_number' => 'nullable|string|max:255',
            'reason_cancellation' => 'nullable|string|max:500',
            'notes' => 'nullable|string',
            'sale_date' => 'required|date',
            'is_active' => 'boolean',
        ]);

        $user = Auth::user();

        // Проверяем права доступа к складу
        if (! $user->isAdmin()) {
            if (! $user->warehouse_id || (int) $request->warehouse_id !== (int) $user->warehouse_id) {
                return response()->json(['message' => 'Доступ к складу запрещен'], 403);
            }
        }

        // Обрабатываем составной ключ товара
        $compositeKey = $request->composite_product_key;

        if (! str_contains($compositeKey, '|')) {
            return response()->json(['message' => 'Неверный формат ключа товара'], 400);
        }

        $parts = explode('|', $compositeKey, 4);
        if (count($parts) !== 4) {
            return response()->json(['message' => 'Неверный формат составного ключа товара'], 400);
        }

        [$productTemplateId, $warehouseId, $producerId, $name] = $parts;

        // Находим конкретный товар для списания
        $product = Product::where('product_template_id', $productTemplateId)
            ->where('warehouse_id', $warehouseId)
            ->where('producer_id', $producerId)
            ->where('name', $name)
            ->where('status', Product::STATUS_IN_STOCK)
            ->where('is_active', true)
            ->whereRaw('(quantity - COALESCE(sold_quantity, 0)) >= ?', [$request->quantity])
            ->first();

        if (! $product) {
            return response()->json(['message' => 'Товар не найден или недостаточно на складе'], 400);
        }

        // Устанавливаем значения по умолчанию для необязательных полей
        $unitPrice = $request->get('unit_price');
        $totalPrice = $request->get('total_price');
        $quantity = $request->quantity;

        // Если unit_price не указана, рассчитываем из total_price
        if ($unitPrice === null && $totalPrice !== null) {
            $unitPrice = $totalPrice / $quantity;
        } elseif ($unitPrice === null) {
            $unitPrice = 0.00;
        }

        // Если total_price не указана, рассчитываем из unit_price
        if ($totalPrice === null) {
            $totalPrice = $unitPrice * $quantity;
        }

        // Упрощенная логика без НДС
        $priceWithoutVat = $totalPrice;
        $vatAmount = 0.00;
        $vatRate = 0.00;

        try {
            $sale = Sale::create([
                'product_id' => $product->id,
                'composite_product_key' => $compositeKey,
                'warehouse_id' => $request->warehouse_id,
                'user_id' => $user->id,
                'sale_number' => Sale::generateSaleNumber(),
                'customer_name' => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'customer_email' => $request->customer_email,
                'customer_address' => $request->customer_address,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'price_without_vat' => $priceWithoutVat,
                'vat_rate' => $vatRate,
                'vat_amount' => $vatAmount,
                'total_price' => $totalPrice,
                'currency' => $request->get('currency', 'RUB'),
                'exchange_rate' => $request->get('exchange_rate', 1.0000),
                'cash_amount' => $request->get('cash_amount', 0.00),
                'nocash_amount' => $request->get('nocash_amount', 0.00),
                'payment_method' => $request->get('payment_method', 'other'),
                'payment_status' => $request->get('payment_status', Sale::PAYMENT_STATUS_PENDING),
                'invoice_number' => $request->invoice_number,
                'reason_cancellation' => $request->reason_cancellation,
                'notes' => $request->notes,
                'sale_date' => $request->sale_date,
                'is_active' => $request->get('is_active', true),
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // Обработка ошибки дубликата номера продажи
            if ($e->getCode() == 23000 && str_contains($e->getMessage(), 'sale_number')) {
                return response()->json([
                    'message' => 'Ошибка генерации номера продажи. Попробуйте еще раз.',
                    'error' => 'duplicate_sale_number',
                ], 409);
            }
            throw $e;
        }

        // Обновляем sold_quantity товара
        if (! $sale->processSale()) {
            // Если не удалось обновить sold_quantity, удаляем созданную продажу
            $sale->delete();

            return response()->json([
                'message' => 'Ошибка при обновлении остатков товара',
                'error' => 'failed_to_process_sale',
            ], 500);
        }

        return response()->json([
            'message' => 'Продажа создана',
            'sale' => $sale->load(['product', 'warehouse', 'user']),
        ], 201);
    }

    /**
     * Обновление продажи
     */
    public function update(Request $request, Sale $sale): JsonResponse
    {
        $user = Auth::user();

        // Проверяем права доступа
        if (! $user->isAdmin() && $user->warehouse_id !== $sale->warehouse_id) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        $request->validate([
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'customer_address' => 'nullable|string',
            'quantity' => 'sometimes|integer|min:1',
            'unit_price' => 'sometimes|numeric|min:0',
            'total_price' => 'sometimes|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'exchange_rate' => 'nullable|numeric|min:0',
            'cash_amount' => 'nullable|numeric|min:0',
            'nocash_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'sometimes|string|in:cash,card,bank_transfer,other',
            'payment_status' => 'sometimes|string|in:pending,paid,partially_paid,cancelled',
            'invoice_number' => 'nullable|string|max:255',
            'reason_cancellation' => 'nullable|string|max:500',
            'notes' => 'nullable|string',
            'sale_date' => 'sometimes|date',
            'is_active' => 'boolean',
        ]);

        $sale->update($request->only([
            'customer_name', 'customer_phone', 'customer_email', 'customer_address',
            'quantity', 'unit_price', 'total_price', 'currency', 'exchange_rate',
            'cash_amount', 'nocash_amount', 'payment_method', 'payment_status',
            'invoice_number', 'reason_cancellation', 'notes', 'sale_date', 'is_active',
        ]));

        // Пересчитываем цены если изменились количество или цена
        if ($request->has('quantity') || $request->has('unit_price') || $request->has('total_price')) {
            $sale->calculatePrices();
            $sale->save();
        }

        return response()->json([
            'message' => 'Продажа обновлена',
            'sale' => $sale->load(['product', 'warehouse', 'user']),
        ]);
    }

    /**
     * Удаление продажи
     */
    public function destroy(Sale $sale): JsonResponse
    {
        $user = Auth::user();

        // Проверяем права доступа
        if (! $user->isAdmin() && $user->company_id && $sale->warehouse->company_id !== $user->company_id) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        // Возвращаем товар на склад перед удалением продажи
        $sale->cancelSale('Продажа удалена');

        $sale->delete();

        return response()->json([
            'message' => 'Продажа удалена',
        ]);
    }

    /**
     * Оформление продажи (списание товара)
     */
    public function process(Sale $sale): JsonResponse
    {
        $user = Auth::user();

        // Проверяем права доступа
        if (! $user->isAdmin() && $user->warehouse_id !== $sale->warehouse_id) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        // Проверим остаток по товару свежим запросом
        $product = Product::find($sale->product_id);
        if (! $product) {
            return response()->json(['message' => 'Товар не найден'], 404);
        }
        if ($product->quantity < $sale->quantity) {
            return response()->json(['message' => 'Недостаточно товара на складе'], 400);
        }

        if ($sale->processSale()) {
            return response()->json([
                'message' => 'Продажа оформлена',
                'sale' => $sale->load(['product', 'warehouse', 'user']),
            ]);
        }

        return response()->json(['message' => 'Ошибка при оформлении продажи'], 500);
    }

    /**
     * Отмена продажи
     */
    public function cancel(Sale $sale): JsonResponse
    {
        $user = Auth::user();

        // Проверяем права доступа
        if (! $user->isAdmin() && $user->warehouse_id !== $sale->warehouse_id) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        if ($sale->cancelSale()) {
            return response()->json([
                'message' => 'Продажа отменена',
                'sale' => $sale->load(['product', 'warehouse', 'user']),
            ]);
        }

        return response()->json(['message' => 'Ошибка при отмене продажи'], 500);
    }

    /**
     * Получение статистики продаж
     */
    public function stats(Request $request): JsonResponse
    {
        $user = Auth::user();

        $query = Sale::query();

        // Применяем права доступа: не админ видит только свой склад
        if (! $user->isAdmin()) {
            if ($user->warehouse_id) {
                $query->where('warehouse_id', $user->warehouse_id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        // Фильтры по датам
        if ($request->date_from) {
            $query->where('sale_date', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->where('sale_date', '<=', $request->date_to);
        }

        // Фильтр по статусу оплаты
        if ($request->payment_status) {
            $query->where('payment_status', $request->payment_status);
        }

        $stats = [
            'total_sales' => (clone $query)->count(),
            'paid_sales' => (clone $query)->where('payment_status', Sale::PAYMENT_STATUS_PAID)->count(),
            'pending_payments' => (clone $query)->where('payment_status', Sale::PAYMENT_STATUS_PENDING)->count(),
            'today_sales' => (clone $query)->whereDate('sale_date', today())->count(),
            'month_revenue' => (clone $query)->where('payment_status', Sale::PAYMENT_STATUS_PAID)
                ->whereMonth('sale_date', now()->month)
                ->whereYear('sale_date', now()->year)
                ->sum('total_price'),
            'total_revenue' => (clone $query)->where('payment_status', Sale::PAYMENT_STATUS_PAID)->sum('total_price'),
            'total_quantity' => (clone $query)->sum('quantity'),
            'average_sale' => (clone $query)->where('payment_status', Sale::PAYMENT_STATUS_PAID)->avg('total_price'),
        ];

        return response()->json($stats);
    }

    /**
     * Экспорт продаж
     */
    public function export(Request $request): JsonResponse
    {
        $user = Auth::user();

        $query = Sale::with(['product', 'warehouse', 'user'])
            ->when($request->search, function ($query, $search) {
                $query->where('sale_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%");
            })
            ->when($request->warehouse_id, function ($query, $warehouseId) {
                $query->where('warehouse_id', $warehouseId);
            })
            ->when($request->payment_status, function ($query, $status) {
                $query->where('payment_status', $status);
            })
            ->when($request->payment_method, function ($query, $method) {
                $query->where('payment_method', $method);
            })
            ->when($request->date_from, function ($query, $date) {
                $query->where('sale_date', '>=', $date);
            })
            ->when($request->date_to, function ($query, $date) {
                $query->where('sale_date', '<=', $date);
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

        $sales = $query->orderBy('created_at', 'desc')->get();

        // Формируем данные для экспорта
        $exportData = $sales->map(function ($sale) {
            return [
                'id' => $sale->id,
                'sale_number' => $sale->sale_number,
                'customer_name' => $sale->customer_name,
                'customer_phone' => $sale->customer_phone,
                'customer_email' => $sale->customer_email,
                'product_name' => $sale->product->name ?? '',
                'warehouse' => $sale->warehouse->name ?? '',
                'quantity' => $sale->quantity,
                'unit_price' => $sale->unit_price,
                'total_price' => $sale->total_price,
                'payment_status' => $sale->payment_status,
                'payment_method' => $sale->payment_method,
                'sale_date' => $sale->sale_date,
                'created_by' => $sale->user->name ?? '',
                'created_at' => $sale->created_at->format('Y-m-d H:i:s'),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $exportData,
            'total' => $exportData->count(),
        ]);
    }
}
