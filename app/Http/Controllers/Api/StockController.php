<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Producer;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    /**
     * Агрегация по производителям
     * GET /api/stocks/by-producer
     */
    public function byProducer(Request $request): JsonResponse
    {
        $user = Auth::user();

        $query = Product::query()
            ->select([
                'producer_id',
                DB::raw('COUNT(DISTINCT CONCAT(product_template_id, "_", name)) as positions_count'),
                DB::raw('SUM(calculated_volume) as total_volume'),
            ])
            ->where('status', Product::STATUS_IN_STOCK)
            ->where('is_active', true)
            ->whereNotNull('producer_id')
            ->groupBy('producer_id');

        // Применяем права доступа
        if (! $user->isAdmin()) {
            if ($user->warehouse_id) {
                $query->where('warehouse_id', $user->warehouse_id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $results = $query->with('producer')->get();

        $data = $results->map(function ($item) {
            return [
                'producer_id' => $item->producer_id,
                'producer' => $item->producer?->name ?? 'Не указан',
                'positions_count' => (int) $item->positions_count,
                'total_volume' => $item->total_volume ? round((float) $item->total_volume, 3) : 0,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Агрегация по складам
     * GET /api/stocks/by-warehouse
     */
    public function byWarehouse(Request $request): JsonResponse
    {
        $user = Auth::user();

        $query = Product::query()
            ->select([
                'warehouse_id',
                DB::raw('COUNT(DISTINCT CONCAT(product_template_id, "_", producer_id, "_", name)) as positions_count'),
                DB::raw('SUM(calculated_volume) as total_volume'),
            ])
            ->where('status', Product::STATUS_IN_STOCK)
            ->where('is_active', true)
            ->groupBy('warehouse_id');

        // Применяем права доступа
        if (! $user->isAdmin()) {
            if ($user->warehouse_id) {
                $query->where('warehouse_id', $user->warehouse_id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $results = $query->with('warehouse.company')->get();

        $data = $results->map(function ($item) {
            return [
                'warehouse_id' => $item->warehouse_id,
                'warehouse' => $item->warehouse?->name ?? 'Не указан',
                'company' => $item->warehouse?->company?->name ?? 'Не указана',
                'address' => $item->warehouse?->address ?? 'Не указан',
                'positions_count' => (int) $item->positions_count,
                'total_volume' => $item->total_volume ? round((float) $item->total_volume, 3) : 0,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Агрегация по компаниям
     * GET /api/stocks/by-company
     */
    public function byCompany(Request $request): JsonResponse
    {
        $user = Auth::user();

        // Получаем все компании с их складами
        $companiesQuery = Company::query()
            ->with(['warehouses' => function ($query) use ($user) {
                if (! $user->isAdmin() && $user->warehouse_id) {
                    $query->where('id', $user->warehouse_id);
                }
            }])
            ->where('is_archived', false);

        $companies = $companiesQuery->get();

        $data = $companies->map(function ($company) {
            // Получаем warehouse_ids для этой компании
            $warehouseIds = $company->warehouses->pluck('id')->toArray();

            if (empty($warehouseIds)) {
                return [
                    'company_id' => $company->id,
                    'company' => $company->name,
                    'warehouses_count' => 0,
                    'positions_count' => 0,
                    'total_volume' => 0,
                ];
            }

            // Агрегируем товары по складам этой компании
            $stats = Product::query()
                ->select([
                    DB::raw('COUNT(DISTINCT CONCAT(product_template_id, "_", producer_id, "_", name)) as positions_count'),
                    DB::raw('SUM(calculated_volume) as total_volume'),
                ])
                ->where('status', Product::STATUS_IN_STOCK)
                ->where('is_active', true)
                ->whereIn('warehouse_id', $warehouseIds)
                ->first();

            return [
                'company_id' => $company->id,
                'company' => $company->name,
                'warehouses_count' => $company->warehouses->count(),
                'positions_count' => (int) ($stats->positions_count ?? 0),
                'total_volume' => $stats->total_volume ? round((float) $stats->total_volume, 3) : 0,
            ];
        })->filter(function ($item) {
            // Фильтруем компании без позиций (опционально)
            return $item['positions_count'] > 0 || $item['warehouses_count'] > 0;
        })->values();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Детальная информация по производителю
     * GET /api/stocks/by-producer/{producer_id}
     */
    public function showProducer(Request $request, int $producerId): JsonResponse
    {
        $user = Auth::user();

        $producer = Producer::find($producerId);
        if (! $producer) {
            return response()->json([
                'success' => false,
                'message' => 'Производитель не найден',
            ], 404);
        }

        $query = Product::query()
            ->select([
                DB::raw('MIN(name) as name'),
                'product_template_id',
                'warehouse_id',
                'producer_id',
                DB::raw('SUM(quantity) as quantity'),
                DB::raw('SUM(quantity - COALESCE(sold_quantity, 0)) as available_quantity'),
                DB::raw('SUM(COALESCE(sold_quantity, 0)) as sold_quantity'),
                DB::raw('SUM(calculated_volume) as total_volume'),
            ])
            ->where('status', Product::STATUS_IN_STOCK)
            ->where('is_active', true)
            ->where('producer_id', $producerId)
            ->with(['producer', 'productTemplate', 'warehouse'])
            ->groupBy(['product_template_id', 'warehouse_id', 'producer_id']);

        // Применяем права доступа
        if (! $user->isAdmin()) {
            if ($user->warehouse_id) {
                $query->where('warehouse_id', $user->warehouse_id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $perPage = $request->get('per_page', 15);
        $products = $query->paginate($perPage);

        $formattedData = $products->getCollection()->map(function ($product) {
            return [
                'name' => $product->name,
                'warehouse' => $product->warehouse?->name,
                'producer' => $product->producer?->name,
                'quantity' => (float) $product->quantity,
                'available_quantity' => (float) $product->available_quantity,
                'sold_quantity' => (float) $product->sold_quantity,
                'total_volume' => $product->total_volume ? round((float) $product->total_volume, 3) : 0,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedData,
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    /**
     * Детальная информация по складу
     * GET /api/stocks/by-warehouse/{warehouse_id}
     */
    public function showWarehouse(Request $request, int $warehouseId): JsonResponse
    {
        $user = Auth::user();

        $warehouse = Warehouse::with('company')->find($warehouseId);
        if (! $warehouse) {
            return response()->json([
                'success' => false,
                'message' => 'Склад не найден',
            ], 404);
        }

        // Проверяем права доступа
        if (! $user->isAdmin() && $user->warehouse_id !== $warehouseId) {
            return response()->json([
                'success' => false,
                'message' => 'Доступ запрещен',
            ], 403);
        }

        $query = Product::query()
            ->select([
                DB::raw('MIN(name) as name'),
                'product_template_id',
                'warehouse_id',
                'producer_id',
                DB::raw('SUM(quantity) as quantity'),
                DB::raw('SUM(quantity - COALESCE(sold_quantity, 0)) as available_quantity'),
                DB::raw('SUM(COALESCE(sold_quantity, 0)) as sold_quantity'),
                DB::raw('SUM(calculated_volume) as total_volume'),
            ])
            ->where('status', Product::STATUS_IN_STOCK)
            ->where('is_active', true)
            ->where('warehouse_id', $warehouseId)
            ->with(['producer', 'productTemplate', 'warehouse'])
            ->groupBy(['product_template_id', 'warehouse_id', 'producer_id']);

        $perPage = $request->get('per_page', 15);
        $products = $query->paginate($perPage);

        $formattedData = $products->getCollection()->map(function ($product) {
            return [
                'name' => $product->name,
                'warehouse' => $product->warehouse?->name,
                'producer' => $product->producer?->name,
                'quantity' => (float) $product->quantity,
                'available_quantity' => (float) $product->available_quantity,
                'sold_quantity' => (float) $product->sold_quantity,
                'total_volume' => $product->total_volume ? round((float) $product->total_volume, 3) : 0,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedData,
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    /**
     * Детальная информация по компании
     * GET /api/stocks/by-company/{company_id}
     */
    public function showCompany(Request $request, int $companyId): JsonResponse
    {
        $user = Auth::user();

        $company = Company::with('warehouses')->find($companyId);
        if (! $company || $company->is_archived) {
            return response()->json([
                'success' => false,
                'message' => 'Компания не найдена',
            ], 404);
        }

        // Получаем warehouse_ids для этой компании с учетом прав доступа
        $warehouseIds = $company->warehouses->pluck('id')->toArray();

        if (! $user->isAdmin() && $user->warehouse_id) {
            if (! in_array($user->warehouse_id, $warehouseIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Доступ запрещен',
                ], 403);
            }
            $warehouseIds = [$user->warehouse_id];
        }

        if (empty($warehouseIds)) {
            return response()->json([
                'success' => true,
                'data' => [],
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 15,
                    'total' => 0,
                ],
            ]);
        }

        $query = Product::query()
            ->select([
                DB::raw('MIN(name) as name'),
                'product_template_id',
                'warehouse_id',
                'producer_id',
                DB::raw('SUM(quantity) as quantity'),
                DB::raw('SUM(quantity - COALESCE(sold_quantity, 0)) as available_quantity'),
                DB::raw('SUM(COALESCE(sold_quantity, 0)) as sold_quantity'),
                DB::raw('SUM(calculated_volume) as total_volume'),
            ])
            ->where('status', Product::STATUS_IN_STOCK)
            ->where('is_active', true)
            ->whereIn('warehouse_id', $warehouseIds)
            ->with(['producer', 'productTemplate', 'warehouse'])
            ->groupBy(['product_template_id', 'warehouse_id', 'producer_id']);

        $perPage = $request->get('per_page', 15);
        $products = $query->paginate($perPage);

        $formattedData = $products->getCollection()->map(function ($product) {
            return [
                'name' => $product->name,
                'warehouse' => $product->warehouse?->name,
                'producer' => $product->producer?->name,
                'quantity' => (float) $product->quantity,
                'available_quantity' => (float) $product->available_quantity,
                'sold_quantity' => (float) $product->sold_quantity,
                'total_volume' => $product->total_volume ? round((float) $product->total_volume, 3) : 0,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedData,
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    /**
     * Получение списка производителей с агрегацией
     * GET /api/stocks/producers
     */
    public function producers(Request $request): JsonResponse
    {
        $user = Auth::user();

        $query = Product::query()
            ->select([
                'producer_id',
                DB::raw('COUNT(DISTINCT CONCAT(product_template_id, "_", name)) as positions_count'),
                DB::raw('SUM(calculated_volume) as total_volume'),
            ])
            ->where('status', Product::STATUS_IN_STOCK)
            ->where('is_active', true)
            ->whereNotNull('producer_id')
            ->groupBy('producer_id');

        // Применяем права доступа
        if (! $user->isAdmin()) {
            if ($user->warehouse_id) {
                $query->where('warehouse_id', $user->warehouse_id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $results = $query->with('producer')->get();

        $data = $results->map(function ($item) {
            return [
                'producer_id' => $item->producer_id,
                'producer' => $item->producer?->name ?? 'Не указан',
                'positions_count' => (int) $item->positions_count,
                'total_volume' => $item->total_volume ? round((float) $item->total_volume, 3) : 0,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Получение списка складов с агрегацией
     * GET /api/stocks/warehouses
     */
    public function warehouses(Request $request): JsonResponse
    {
        $user = Auth::user();

        $query = Product::query()
            ->select([
                'warehouse_id',
                DB::raw('COUNT(DISTINCT CONCAT(product_template_id, "_", producer_id, "_", name)) as positions_count'),
                DB::raw('SUM(calculated_volume) as total_volume'),
            ])
            ->where('status', Product::STATUS_IN_STOCK)
            ->where('is_active', true)
            ->groupBy('warehouse_id');

        // Применяем права доступа
        if (! $user->isAdmin()) {
            if ($user->warehouse_id) {
                $query->where('warehouse_id', $user->warehouse_id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $results = $query->with('warehouse.company')->get();

        $data = $results->map(function ($item) {
            return [
                'warehouse_id' => $item->warehouse_id,
                'warehouse' => $item->warehouse?->name ?? 'Не указан',
                'company' => $item->warehouse?->company?->name ?? 'Не указана',
                'address' => $item->warehouse?->address ?? 'Не указан',
                'positions_count' => (int) $item->positions_count,
                'total_volume' => $item->total_volume ? round((float) $item->total_volume, 3) : 0,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Получение списка компаний с агрегацией
     * GET /api/stocks/companies
     */
    public function companies(Request $request): JsonResponse
    {
        $user = Auth::user();

        // Получаем все компании с их складами
        $companiesQuery = Company::query()
            ->with(['warehouses' => function ($query) use ($user) {
                if (! $user->isAdmin() && $user->warehouse_id) {
                    $query->where('id', $user->warehouse_id);
                }
            }])
            ->where('is_archived', false);

        $companies = $companiesQuery->get();

        $data = $companies->map(function ($company) {
            // Получаем warehouse_ids для этой компании
            $warehouseIds = $company->warehouses->pluck('id')->toArray();

            if (empty($warehouseIds)) {
                return [
                    'company_id' => $company->id,
                    'company' => $company->name,
                    'warehouses_count' => 0,
                    'positions_count' => 0,
                    'total_volume' => 0,
                ];
            }

            // Агрегируем товары по складам этой компании
            $stats = Product::query()
                ->select([
                    DB::raw('COUNT(DISTINCT CONCAT(product_template_id, "_", producer_id, "_", name)) as positions_count'),
                    DB::raw('SUM(calculated_volume) as total_volume'),
                ])
                ->where('status', Product::STATUS_IN_STOCK)
                ->where('is_active', true)
                ->whereIn('warehouse_id', $warehouseIds)
                ->first();

            return [
                'company_id' => $company->id,
                'company' => $company->name,
                'warehouses_count' => $company->warehouses->count(),
                'positions_count' => (int) ($stats->positions_count ?? 0),
                'total_volume' => $stats->total_volume ? round((float) $stats->total_volume, 3) : 0,
            ];
        })->filter(function ($item) {
            // Фильтруем компании без позиций (опционально)
            return $item['positions_count'] > 0 || $item['warehouses_count'] > 0;
        })->values();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
