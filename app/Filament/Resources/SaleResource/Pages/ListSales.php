<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSales extends ListRecords
{
    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export')
                ->label('Экспорт в Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(route('sales.export'))
                ->openUrlInNewTab(false),
            Actions\CreateAction::make()
                ->label('Создать продажу'),
        ];
    }
}
