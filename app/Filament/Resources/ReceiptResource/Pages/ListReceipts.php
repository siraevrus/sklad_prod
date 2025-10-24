<?php

namespace App\Filament\Resources\ReceiptResource\Pages;

use App\Filament\Resources\ReceiptResource;
use Filament\Resources\Pages\ListRecords;

class ListReceipts extends ListRecords
{
    protected static string $resource = ReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('export')
                ->label('Экспорт в Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(route('receipts.export'))
                ->openUrlInNewTab(false),
        ];
    }
}
