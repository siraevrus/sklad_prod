<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Product;
use App\Models\Request as RequestModel;
use App\Models\Sale;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    /**
     * Return aggregated dashboard summary for the mobile app.
     */
    public function summary(): JsonResponse
    {
        $companiesActive = Company::query()->where('is_archived', false)->count();
        $employeesActive = User::query()->where('is_blocked', false)->count();
        $warehousesActive = Warehouse::query()->where('is_active', true)->count();

        $productsTotal = Product::query()->count();

        $productsInTransit = Product::query()
            ->when(defined(Product::class.'::STATUS_IN_TRANSIT'), function ($q) {
                /** @var \Illuminate\Database\Eloquent\Builder $q */
                $q->where('status', Product::STATUS_IN_TRANSIT);
            }, function ($q) {
                /** @var \Illuminate\Database\Eloquent\Builder $q */
                $q->where('status', 'in_transit');
            })
            ->when(schema_has_column('products', 'is_active'), fn ($q) => $q->where('is_active', true))
            ->count();

        $requestsPending = RequestModel::query()
            ->when(method_exists(RequestModel::class, 'byStatus'), function ($q) {
                /** @var \Illuminate\Database\Eloquent\Builder $q */
                $status = defined(RequestModel::class.'::STATUS_PENDING') ? RequestModel::STATUS_PENDING : 'pending';
                $q->byStatus($status);
            }, function ($q) {
                /** @var \Illuminate\Database\Eloquent\Builder $q */
                $q->where('status', 'pending');
            })
            ->count();

        $latestSales = Sale::query()
            ->with(['product'])
            ->where('payment_status', '!=', Sale::PAYMENT_STATUS_CANCELLED)
            ->when(schema_has_column('sales', 'sale_date'), fn ($q) => $q->latest('sale_date'), fn ($q) => $q->latest())
            ->limit(10)
            ->get()
            ->map(function (Sale $sale) {
                $total = $sale->total_amount ?? $sale->total_price ?? null;
                $client = $sale->client_name ?? $sale->customer_name ?? null;

                return [
                    'id' => $sale->id,
                    'product_name' => optional($sale->product)->name,
                    'client_name' => $client,
                    'quantity' => $sale->quantity,
                    'total_amount' => $total,
                    'sale_date' => $sale->sale_date ?? ($sale->created_at ? $sale->created_at->toDateTimeString() : null),
                ];
            });

        return response()->json([
            'companies_active' => $companiesActive,
            'employees_active' => $employeesActive,
            'warehouses_active' => $warehousesActive,
            'products_total' => $productsTotal,
            'products_in_transit' => $productsInTransit,
            'requests_pending' => $requestsPending,
            'latest_sales' => $latestSales,
        ]);
    }

    /**
     * Revenue by currencies for the selected period.
     */
    public function revenue(): JsonResponse
    {
        $period = request('period', 'day'); // day, week, month, custom
        $dateFrom = request('date_from');
        $dateTo = request('date_to');

        // Resolve date range
        if ($period !== 'custom') {
            switch ($period) {
                case 'week':
                    $start = now()->startOfWeek();
                    $end = now()->endOfWeek();
                    break;
                case 'month':
                    $start = now()->startOfMonth();
                    $end = now()->endOfMonth();
                    break;
                case 'day':
                default:
                    $start = now()->startOfDay();
                    $end = now()->endOfDay();
                    $period = 'day';
                    break;
            }
        } else {
            // custom
            $start = $dateFrom ? \Illuminate\Support\Carbon::parse($dateFrom)->startOfDay() : now()->startOfDay();
            $end = $dateTo ? \Illuminate\Support\Carbon::parse($dateTo)->endOfDay() : now()->endOfDay();
        }

        // Base query: only paid sales in date range
        $base = Sale::query()
            ->where('payment_status', Sale::PAYMENT_STATUS_PAID)
            ->when(schema_has_column('sales', 'sale_date'), function ($q) use ($start, $end) {
                /** @var \Illuminate\Database\Eloquent\Builder $q */
                $q->whereBetween('sale_date', [$start->toDateString(), $end->toDateString()]);
            }, function ($q) use ($start, $end) {
                /** @var \Illuminate\Database\Eloquent\Builder $q */
                $q->whereBetween('created_at', [$start, $end]);
            });

        // Sum by currency
        $currencies = ['USD', 'RUB', 'UZS'];
        $revenue = [];
        foreach ($currencies as $currency) {
            $amount = (clone $base)->where('currency', $currency)->sum('total_price');
            $revenue[$currency] = [
                'amount' => (float) $amount,
                'formatted' => $this->formatCurrency((float) $amount, $currency),
            ];
        }

        return response()->json([
            'period' => $period,
            'date_from' => $start->toDateString(),
            'date_to' => $end->toDateString(),
            'revenue' => $revenue,
        ]);
    }

    private function formatCurrency(float $amount, string $currency): string
    {
        return match ($currency) {
            'USD' => number_format($amount, 2, '.', ' ').' $',
            'RUB' => number_format($amount, 2, '.', ' ').' ₽',
            'UZS' => number_format($amount, 2, '.', ' ').' UZS',
            default => number_format($amount, 2, '.', ' ').' '.$currency,
        };
    }
}

if (! function_exists('schema_has_column')) {
    /**
     * Safe schema column check avoiding runtime issues in production.
     */
    function schema_has_column(string $table, string $column): bool
    {
        try {
            return \Illuminate\Support\Facades\Schema::hasColumn($table, $column);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
