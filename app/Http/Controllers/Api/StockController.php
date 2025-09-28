<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockGroup;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    /**
     * Получить список остатков товаров
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Product::query()
                ->select([
                    'product_template_id',
                    'warehouse_id',
                    'producer_id',
                    DB::raw('CONCAT(product_template_id, "_", warehouse_id, "_", COALESCE(producer_id, "null")) as id'),
                    DB::raw('SUM(COALESCE(quantity, 0) - COALESCE(sold_quantity, 0)) as total_quantity'),
                    DB::raw('SUM(COALESCE(calculated_volume, 0) * COALESCE(quantity, 0)) as total_volume'),
                    DB::raw('MIN(name) as name'),
                    DB::raw('MIN(status) as status'),
                    DB::raw('MIN(is_active) as is_active')
                ])
                ->where('is_active', 1)
                ->groupBy('product_template_id', 'warehouse_id', 'producer_id')
                ->having('total_quantity', '>', 0);

            // Фильтрация по складу
            if ($request->has('warehouse_id')) {
                $query->where('warehouse_id', $request->warehouse_id);
            }

            // Фильтрация по статусу
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $stocks = $query->with(['productTemplate', 'warehouse', 'producer'])
                ->orderBy('name')
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $stocks->items(),
                'pagination' => [
                    'current_page' => $stocks->currentPage(),
                    'last_page' => $stocks->lastPage(),
                    'per_page' => $stocks->perPage(),
                    'total' => $stocks->total(),
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('StockController index error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения остатков: ' . $e->getMessage(),
                'debug' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * Получить детальную информацию об остатке
     */
    public function show(string $id): JsonResponse
    {
        try {
            // Разбираем составной ID
            $parts = explode('_', $id);
            if (count($parts) !== 3) {
                return response()->json([
                    'success' => false,
                    'message' => 'Неверный формат ID'
                ], 400);
            }

            [$productTemplateId, $warehouseId, $producerId] = $parts;
            if ($producerId === 'null') {
                $producerId = null;
            }

            $stock = Product::query()
                ->select([
                    'product_template_id',
                    'warehouse_id',
                    'producer_id',
                    DB::raw('SUM(COALESCE(quantity, 0) - COALESCE(sold_quantity, 0)) as total_quantity'),
                    DB::raw('SUM(COALESCE(calculated_volume, 0) * COALESCE(quantity, 0)) as total_volume'),
                    DB::raw('MIN(name) as name'),
                    DB::raw('MIN(status) as status'),
                    DB::raw('MIN(is_active) as is_active')
                ])
                ->where('product_template_id', $productTemplateId)
                ->where('warehouse_id', $warehouseId)
                ->where('producer_id', $producerId)
                ->where('is_active', 1)
                ->groupBy('product_template_id', 'warehouse_id', 'producer_id')
                ->with(['productTemplate', 'warehouse', 'producer'])
                ->first();

            if (!$stock) {
                return response()->json([
                    'success' => false,
                    'message' => 'Остаток не найден'
                ], 404);
            }

            // Получаем детальную информацию о товарах
            $products = Product::where('product_template_id', $productTemplateId)
                ->where('warehouse_id', $warehouseId)
                ->where('producer_id', $producerId)
                ->where('is_active', 1)
                ->where('quantity', '>', 0)
                ->with(['productTemplate', 'warehouse', 'producer'])
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'stock' => $stock,
                    'products' => $products
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('StockController show error: ' . $e->getMessage(), [
                'id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения остатка: ' . $e->getMessage(),
                'debug' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }
}
