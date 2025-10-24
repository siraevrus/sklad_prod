<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class ProductExportController extends Controller
{
    public function export(Request $request)
    {
        $user = Auth::user();

        // Получаем товары с учетом прав доступа
        $query = Product::with(['template', 'warehouse', 'creator', 'producer']);

        // Фильтруем только активные товары со статусом in_stock
        $query->where('is_active', true)
            ->where('status', Product::STATUS_IN_STOCK);

        if ($user->role !== 'admin') {
            if ($user->warehouse_id) {
                $query->where('warehouse_id', $user->warehouse_id);
            } else {
                // Если у пользователя нет склада, показываем все товары
                // или можно добавить фильтр по компании
            }
        }

        // Применяем фильтры
        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->has('product_template_id')) {
            $query->where('product_template_id', $request->product_template_id);
        }

        if ($request->has('producer_id')) { // Используем producer_id
            $query->where('producer_id', $request->producer_id);
        }

        if ($request->has('in_stock')) {
            $query->where('quantity', '>', 0);
        }

        $products = $query->get();

        // Отладочная информация
        \Log::info('ProductExport: Found products', [
            'count' => $products->count(),
            'user_role' => $user->role->value ?? 'unknown',
            'user_warehouse_id' => $user->warehouse_id ?? 'null',
        ]);

        // Формируем CSV
        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="products_'.date('Y-m-d').'.csv"',
        ];

        $callback = function () use ($products) {
            $file = fopen('php://output', 'w');

            // BOM для корректного отображения в Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Заголовки
            fputcsv($file, [
                'ID',
                'Название',
                'Описание',
                'Шаблон',
                'Склад',
                'Производитель',
                'Количество',
                'Объем (м³)',
                'Дата поступления',
                'Статус',
                'Сотрудник',
                'Дата создания',
            ], ';');

            // Данные
            foreach ($products as $product) {
                fputcsv($file, [
                    $product->id,
                    $product->name,
                    $product->description,
                    $product->template?->name ?? 'Не указан',
                    $product->warehouse?->name ?? 'Не указан',
                    $product->producer ? $product->producer->name : 'Не указан', // Используем связь
                    $product->quantity,
                    $product->calculated_volume ? round($product->calculated_volume, 3) : '—',
                    $product->arrival_date?->format('d.m.Y') ?? 'Не указана',
                    $product->is_active ? 'Активен' : 'Неактивен',
                    $product->creator?->name ?? 'Не указан',
                    $product->created_at->format('d.m.Y H:i'),
                ], ';');
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }
}
