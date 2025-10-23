<?php

namespace App\Filament\Resources\ReceiptResource\Pages;

use App\Filament\Resources\ReceiptResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListReceipts extends ListRecords
{
    protected static string $resource = ReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Экспорт в Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(route('filament.admin.resources.receipts.export'))
                ->openUrlInNewTab(false),
        ];
    }
}
