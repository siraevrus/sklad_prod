<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class ReceiptExportController extends Controller
{
    public function export(Request $request)
    {
        $user = Auth::user();

        // Получаем все товары с учетом прав доступа
        $query = Product::query()
            ->active()
            ->with(['warehouse', 'producer', 'productTemplate', 'creator'])
            ->orderByDesc('created_at');

        // Применяем ограничения доступа
        if (! $user->isAdmin()) {
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

        if ($request->has('shipping_location')) {
            $query->where('shipping_location', $request->shipping_location);
        }

        if ($request->has('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        $receipts = $query->get();

        // Отладочная информация
        \Log::info('ReceiptExport: Found receipts', [
            'count' => $receipts->count(),
            'user_role' => $user->role->value ?? 'unknown',
            'user_warehouse_id' => $user->warehouse_id ?? 'null',
        ]);

        // Формируем CSV
        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="receipts_'.date('Y-m-d').'.csv"',
        ];

        $callback = function () use ($receipts) {
            $file = fopen('php://output', 'w');

            // BOM для корректного отображения в Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Заголовки
            fputcsv($file, [
                'ID',
                'Наименование',
                'Склад',
                'Производитель',
                'Шаблон',
                'Количество',
                'Объем',
                'Номер транспорта',
                'Место отгрузки',
                'Дата отгрузки',
                'Ожидаемая дата',
                'Статус',
                'Сотрудник',
                'Дата создания',
            ], ';');

            // Данные
            foreach ($receipts as $receipt) {
                fputcsv($file, [
                    $receipt->id,
                    $receipt->name,
                    $receipt->warehouse?->name ?? '—',
                    $receipt->producer?->name ?? '—',
                    $receipt->productTemplate?->name ?? '—',
                    $receipt->quantity,
                    $receipt->calculated_volume ? round($receipt->calculated_volume, 3) : '—',
                    $receipt->transport_number ?? '—',
                    $receipt->shipping_location ?? '—',
                    $receipt->shipping_date?->format('d.m.Y') ?? '—',
                    $receipt->expected_arrival_date?->format('d.m.Y') ?? '—',
                    $receipt->status,
                    $receipt->creator?->name ?? '—',
                    $receipt->created_at->format('d.m.Y H:i'),
                ], ';');
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }
}
