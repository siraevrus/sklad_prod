<?php

namespace App\Filament\Pages;

use App\Models\Product;
use App\Models\Warehouse;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class StockOverview extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'Остатки на складе';

    protected static ?string $title = 'Остатки на складе';

    protected static ?int $navigationSort = 9;

    protected static string $view = 'filament.pages.stock-overview';

    public static function canAccess(): bool
    {
        // Доступ открыт всем не заблокированным пользователям, чтобы был стартовый раздел
        $user = Auth::user();

        return $user && ! $user->isBlocked();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Наименование')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('producer.name')
                    ->label('Производитель')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Склад')
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Количество')
                    ->numeric()
                    ->sortable()
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Итого')
                    ),
                Tables\Columns\TextColumn::make('calculated_volume')
                    ->label('Объем (м³)')
                    ->formatStateUsing(function ($state) {
                        return number_format((float) $state, 3, '.', ' ');
                    })
                    ->sortable()
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Итого (м³)')
                            ->formatStateUsing(function ($state) {
                                return number_format((float) $state, 3, '.', ' ');
                            })
                    ),
                Tables\Columns\TextColumn::make('productTemplate.name')
                    ->label('Шаблон')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('warehouse_id')
                    ->label('Склад')
                    ->options(fn () => Warehouse::optionsForCurrentUser()),
                Tables\Filters\SelectFilter::make('producer_id')
                    ->label('Производитель (по id)')
                    ->options(fn () => \App\Models\Producer::pluck('name', 'id')),
                Tables\Filters\SelectFilter::make('producers')
                    ->label('Производитель (по имени)')
                    ->options(fn () => \App\Models\Producer::pluck('name', 'name')),
                Tables\Filters\Filter::make('in_stock')
                    ->label('В наличии')
                    ->query(fn (Builder $query): Builder => $query->where('quantity', '>', 0)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    protected function getTableQuery(): Builder
    {
        $user = Auth::user();
        if (! $user) {
            return Product::query()->whereRaw('1 = 0');
        }

        $query = Product::query()
            ->with('producer')
            ->where('status', Product::STATUS_IN_STOCK)
            ->where('products.is_active', true);

        // Фильтрация по компании пользователя
        if ($user->company_id) {
            $query->whereHas('warehouse', function ($q) use ($user) {
                $q->where('company_id', $user->company_id);
            });
        }

        // Применяем фильтр по компании из URL параметра (для просмотра складов компании)
        $companyId = request()->get('company_id');
        if ($companyId) {
            $query->whereHas('warehouse', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            });
        }

        // Применяем фильтр по производителю, если он установлен
        $producerId = request()->get('tableFilters.producer_id.value');
        if ($producerId) {
            $query->where('producer_id', $producerId);
        }

        // Применяем фильтр по имени производителя, если он установлен
        $producerName = request()->get('tableFilters.producers.value');
        if ($producerName) {
            $query->whereHas('producer', function ($q) use ($producerName) {
                $q->where('name', $producerName);
            });
        }

        // Применяем фильтр по складу, если он установлен
        $warehouseId = request()->get('tableFilters.warehouse_id.value');
        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        // Применяем фильтр "В наличии", если он активен
        $inStockFilter = request()->get('tableFilters.in_stock.isActive');
        if ($inStockFilter === 'true') {
            $query->where('quantity', '>', 0);
        }

        return $query;
    }

    public function getProducers(): array
    {
        $user = Auth::user();
        $query = Product::query()
            ->where('status', Product::STATUS_IN_STOCK)
            ->where('products.is_active', true);

        // Фильтрация по компании пользователя
        if ($user->company_id) {
            $query->whereHas('warehouse', function ($q) use ($user) {
                $q->where('company_id', $user->company_id);
            });
        }

        // Группируем по производителю через связь
        return $query->whereHas('producer')
            ->with('producer')
            ->get()
            ->groupBy('producer.id')
            ->map(function ($products) {
                $producer = $products->first()->producer;

                return $producer ? $producer->name : null;
            })
            ->filter() // Убираем null значения
            ->toArray();
    }

    public function getWarehouses(): \Illuminate\Database\Eloquent\Collection
    {
        $user = Auth::user();
        $query = Warehouse::query();

        // Фильтрация по компании пользователя
        if ($user->company_id) {
            $query->where('company_id', $user->company_id);
        }

        return $query->get();
    }

    /**
     * Получить агрегированные данные по производителям
     */
    public function getProducerStats(): array
    {
        $producers = \App\Models\Producer::with(['products' => function ($query) {
            $query->where('status', Product::STATUS_IN_STOCK)
                ->where('products.is_active', true);
        }])->get();

        $result = [];
        foreach ($producers as $producer) {
            $result[$producer->id] = [
                'name' => $producer->name,
                'total_products' => $producer->products->count(),
                'total_quantity' => $producer->products->sum('quantity'),
                'total_volume' => $producer->products->sum('calculated_volume'),
            ];
        }

        return $result;
    }

    /**
     * Получить агрегированные данные по складам
     */
    public function getWarehouseStats(): array
    {
        $user = Auth::user();
        $query = Product::query()
            ->where('status', Product::STATUS_IN_STOCK)
            ->where('products.is_active', true);

        // Фильтрация по компании пользователя
        if ($user->company_id) {
            $query->whereHas('warehouse', function ($q) use ($user) {
                $q->where('company_id', $user->company_id);
            });
        }

        // Применяем фильтр по компании из URL параметра (для просмотра складов компании)
        $companyId = request()->get('company_id');
        if ($companyId) {
            $query->whereHas('warehouse', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            });
        }

        // Группируем по складу, не учитывая наименование и характеристики
        return $query->select('warehouse_id')
            ->selectRaw('COUNT(*) as total_products')
            ->selectRaw('SUM(quantity) as total_quantity')
            ->selectRaw('SUM((quantity - COALESCE(sold_quantity, 0)) * calculated_volume) as total_volume')
            ->groupBy('warehouse_id')
            ->get()
            ->keyBy('warehouse_id')
            ->toArray();
    }

    /**
     * Получить агрегированные данные по компаниям
     */
    public function getCompanyStats(): array
    {
        $user = Auth::user();
        $query = Product::query()
            ->where('status', Product::STATUS_IN_STOCK)
            ->where('products.is_active', true);

        // Фильтрация по компании пользователя (если не админ)
        if ($user->company_id) {
            $query->whereHas('warehouse', function ($q) use ($user) {
                $q->where('company_id', $user->company_id);
            });
        }

        // Группируем по компании через склад
        return $query->join('warehouses', 'products.warehouse_id', '=', 'warehouses.id')
            ->select('warehouses.company_id')
            ->selectRaw('COUNT(*) as total_products')
            ->selectRaw('SUM(products.quantity) as total_quantity')
            ->selectRaw('SUM(products.calculated_volume) as total_volume')
            ->groupBy('warehouses.company_id')
            ->get()
            ->keyBy('company_id')
            ->toArray();
    }
}
