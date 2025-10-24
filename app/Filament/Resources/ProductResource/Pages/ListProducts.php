<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export')
                ->label('Экспорт в Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(route('products.export'))
                ->openUrlInNewTab(false),
        ];
    }
}
