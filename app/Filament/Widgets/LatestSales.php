<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestSales extends BaseWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Последние продажи';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Sale::query()
                    ->with(['product', 'warehouse', 'user'])
                    ->where('payment_status', '!=', Sale::PAYMENT_STATUS_CANCELLED)
                    ->latest('sale_date')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('sale_number')
                    ->label('Номер продажи')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('product.name')
                    ->label('Товар')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Клиент')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Не указан'),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Количество')
                    ->sortable()
                    ->badge(),

                Tables\Columns\TextColumn::make('total_price')
                    ->label('Сумма')
                    ->formatStateUsing(function (Sale $record): string {
                        $currency = $record->currency ?? 'RUB';
                        $amount = number_format($record->total_price, 2, '.', ' ');

                        return match ($currency) {
                            'USD' => $amount.' $',
                            'UZS' => $amount.' UZS',
                            'RUB' => $amount.' ₽',
                            default => $amount.' '.$currency,
                        };
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('currency')
                    ->label('Валюта')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'USD' => 'blue',
                        'RUB' => 'red',
                        'UZS' => 'green',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'USD' => 'USD',
                        'RUB' => 'Рубли',
                        'UZS' => 'Сумы',
                        default => $state,
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('sale_date')
                    ->label('Дата продажи')
                    ->formatStateUsing(function ($state): string {
                        if (! $state) {
                            return '—';
                        }

                        return Carbon::parse($state)->format('d.m.Y');
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Продавец')
                    ->sortable(),
            ])
            ->paginated(false);
    }
}
