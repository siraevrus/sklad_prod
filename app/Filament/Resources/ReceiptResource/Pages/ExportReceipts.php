<?php

namespace App\Filament\Resources\ReceiptResource\Pages;

use App\Models\Product;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportReceipts extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.resources.receipt-resource.pages.export-receipts';

    protected static ?string $title = 'Экспорт приемок';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'date_from' => now()->startOfMonth(),
            'date_to' => now(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Параметры экспорта')
                    ->schema([
                        DatePicker::make('date_from')
                            ->label('Дата начала')
                            ->required(),

                        DatePicker::make('date_to')
                            ->label('Дата окончания')
                            ->required(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function export(): StreamedResponse
    {
        $this->validate();

        $dateFrom = $this->data['date_from'];
        $dateTo = $this->data['date_to'];

        $user = Auth::user();

        // Получаем все приемки в выбранном периоде
        $query = Product::query()
            ->where('status', Product::STATUS_FOR_RECEIPT)
            ->active()
            ->whereBetween('created_at', [
                $dateFrom->startOfDay(),
                $dateTo->endOfDay(),
            ])
            ->with(['warehouse', 'producer', 'productTemplate', 'creator'])
            ->orderByDesc('created_at');

        // Применяем ограничения доступа
        if (! $user->isAdmin()) {
            if ($user->warehouse_id) {
                $query->where('warehouse_id', $user->warehouse_id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $receipts = $query->get();

        // Генерируем Excel файл
        return $this->generateExcel($receipts, $dateFrom, $dateTo);
    }

    protected function generateExcel($receipts, $dateFrom, $dateTo)
    {
        $fileName = 'Приемки_'.$dateFrom->format('d.m.Y').'_-_'.$dateTo->format('d.m.Y').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename='.$fileName,
        ];

        $handle = fopen('php://output', 'w');

        // BOM для корректного отображения в Excel
        fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

        // Заголовки таблицы
        $tableHeaders = [
            'Наименование',
            'Склад',
            'Производитель',
            'Шаблон',
            'Количество',
            'Объем',
            'Дата отгрузки',
            'Ожидаемая дата',
            'Статус',
            'Сотрудник',
            'Дата создания',
        ];

        fputcsv($handle, $tableHeaders, ';');

        // Данные
        foreach ($receipts as $receipt) {
            fputcsv($handle, [
                $receipt->name,
                $receipt->warehouse?->name ?? '—',
                $receipt->producer?->name ?? '—',
                $receipt->productTemplate?->name ?? '—',
                $receipt->quantity,
                $receipt->calculated_volume ? round($receipt->calculated_volume, 3) : '—',
                $receipt->shipping_date?->format('d.m.Y') ?? '—',
                $receipt->expected_arrival_date?->format('d.m.Y') ?? '—',
                $receipt->status,
                $receipt->creator?->name ?? '—',
                $receipt->created_at->format('d.m.Y H:i'),
            ], ';');
        }

        fclose($handle);

        return response()->streamDownload(function () use ($receipts, $tableHeaders) {
            $handle = fopen('php://output', 'w');

            // BOM для корректного отображения в Excel
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, $tableHeaders, ';');

            foreach ($receipts as $receipt) {
                fputcsv($handle, [
                    $receipt->name,
                    $receipt->warehouse?->name ?? '—',
                    $receipt->producer?->name ?? '—',
                    $receipt->productTemplate?->name ?? '—',
                    $receipt->quantity,
                    $receipt->calculated_volume ? round($receipt->calculated_volume, 3) : '—',
                    $receipt->shipping_date?->format('d.m.Y') ?? '—',
                    $receipt->expected_arrival_date?->format('d.m.Y') ?? '—',
                    $receipt->status,
                    $receipt->creator?->name ?? '—',
                    $receipt->created_at->format('d.m.Y H:i'),
                ], ';');
            }

            fclose($handle);
        }, 'Приемки_'.$dateFrom->format('d.m.Y').'_-_'.$dateTo->format('d.m.Y').'.csv', [
            'Content-Type' => 'text/csv; charset=utf-8',
        ]);
    }
}
