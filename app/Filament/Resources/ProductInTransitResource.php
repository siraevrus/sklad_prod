<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductInTransitResource\Pages;
use App\Models\Product;
use App\Models\ProductTemplate;
use App\Models\Warehouse;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ProductInTransitResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'Товары';

    protected static ?string $modelLabel = 'Товар в пути';

    protected static ?string $pluralModelLabel = 'Товары в пути';

    protected static ?int $navigationSort = 6;

    public static function canViewAny(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        return in_array($user->role->value, [
            'admin',
            'operator',
            'warehouse_worker',
            'sales_manager',
        ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Основная информация')
                    ->schema([
                        Grid::make(5)
                            ->schema([
                                TextInput::make('shipping_location')
                                    ->label('Место отгрузки')
                                    ->maxLength(255)
                                    ->required(),

                                Select::make('warehouse_id')
                                    ->label('Склад назначения')
                                    ->options(fn () => Warehouse::optionsForCurrentUser())
                                    ->required()
                                    ->dehydrated()
                                    ->default(function () {
                                        $user = Auth::user();
                                        if (! $user) {
                                            return null;
                                        }

                                        return $user->isAdmin() ? null : $user->warehouse_id;
                                    })
                                    ->visible(function () {
                                        $user = Auth::user();
                                        if (! $user) {
                                            return false;
                                        }

                                        return $user->isAdmin();
                                    })
                                    ->searchable(),

                                DatePicker::make('shipping_date')
                                    ->label('Дата отгрузки')
                                    ->required()
                                    ->default(now()),

                                DatePicker::make('expected_arrival_date')
                                    ->label('Ожидаемая дата прибытия')
                                    ->default(null),

                                TextInput::make('transport_number')
                                    ->label('Номер транспорта')
                                    ->maxLength(255),
                            ]),

                    ]),

                Section::make('Товары')
                    ->schema([
                        Repeater::make('products')
                            ->label('Список товаров')
                            ->schema([
                                Grid::make(5)
                                    ->schema([
                                        Select::make('product_template_id')
                                            ->label('Шаблон товара')
                                            ->options(function () {
                                                return ProductTemplate::pluck('name', 'id');
                                            })
                                            ->required()
                                            ->searchable()
                                            ->live()
                                            ->afterStateUpdated(function (Set $set, Get $get) {
                                                // Очищаем характеристики при смене шаблона
                                                $set('attributes', []);
                                                $set('calculated_volume', null);
                                                $set('name', '');
                                            }),

                                        TextInput::make('name')
                                            ->label('Наименование')
                                            ->maxLength(255)
                                            ->disabled()
                                            ->hidden(fn () => true)
                                            ->helperText('Автоматически формируется из характеристик товара'),

                                        Select::make('producer_id')
                                            ->label('Производитель')
                                            ->options(function () {
                                                return \App\Models\Producer::pluck('name', 'id');
                                            })
                                            ->searchable()
                                            ->required(),

                                        TextInput::make('quantity')
                                            ->label('Количество')
                                            ->inputMode('decimal')
                                            ->default(1)
                                            ->maxLength(10)
                                            ->required()
                                            ->rules(['regex:/^\d*([.,]\d*)?$/'])
                                            ->validationMessages([
                                                'regex' => 'Поле должно содержать только цифры и одну запятую или точку',
                                            ])
                                            ->live(debounce: 30)
                                            ->afterStateUpdated(function (Set $set, Get $get) {
                                                self::calculateVolumeForItem($set, $get);
                                            })
                                            ->helperText('Объем рассчитывается автоматически при изменении характеристик или количества.'),

                                    ]),

                                // Динамические поля характеристик
                                Grid::make(5)
                                    ->schema(function (Get $get) {
                                        $templateId = $get('product_template_id');
                                        if (! $templateId) {
                                            return [];
                                        }

                                        $template = ProductTemplate::with('attributes')->find($templateId);
                                        if (! $template) {
                                            return [];
                                        }

                                        $fields = [];
                                        foreach ($template->attributes as $attribute) {
                                            $fieldName = "attribute_{$attribute->variable}";

                                            switch ($attribute->type) {
                                                case 'number':
                                                    $fields[] = TextInput::make($fieldName)
                                                        ->label($attribute->name)
                                                        ->inputMode('decimal')
                                                        ->maxLength(10)
                                                        ->required($attribute->is_required)
                                                        ->rules(['regex:/^\d*([.,]\d*)?$/'])
                                                        ->validationMessages([
                                                            'regex' => 'Поле должно содержать только цифры и одну запятую или точку',
                                                        ])
                                                        ->key("number_attr_{$attribute->id}_{$attribute->variable}")
                                                        ->live(debounce: 30)
                                                        ->afterStateUpdated(function (Set $set, Get $get) {
                                                            self::calculateVolumeForItem($set, $get);
                                                        })
                                                        ->helperText('Максимальное значение: 9999');
                                                    break;

                                                case 'text':
                                                    $fields[] = TextInput::make($fieldName)
                                                        ->label($attribute->name)
                                                        ->maxLength(255)
                                                        ->required($attribute->is_required)
                                                        ->key("text_attr_{$attribute->id}_{$attribute->variable}")
                                                        ->live(debounce: 30)
                                                        ->afterStateUpdated(function (Set $set, Get $get) {
                                                            self::calculateVolumeForItem($set, $get);
                                                        });
                                                    break;

                                                case 'select':
                                                    $options = $attribute->options_array;
                                                    $fields[] = Select::make($fieldName)
                                                        ->label($attribute->name)
                                                        ->options($options)
                                                        ->required($attribute->is_required)
                                                        ->key("select_attr_{$attribute->id}_{$attribute->variable}")
                                                        ->live(debounce: 500)
                                                        ->afterStateUpdated(function (Set $set, Get $get) {
                                                            self::calculateVolumeForItem($set, $get);
                                                        });
                                                    break;
                                            }
                                        }

                                        return $fields;
                                    })
                                    ->visible(fn (Get $get) => $get('product_template_id') !== null),

                                // Поле рассчитанного объема в конце блока
                                Grid::make(1)
                                    ->schema([
                                        TextInput::make('calculated_volume')
                                            ->label('Рассчитанный объем')
                                            ->disabled()
                                            ->key(fn (Get $get) => 'calculated_volume_'.($get('product_template_id') ?? 'none'))
                                            ->formatStateUsing(function ($state) {
                                                // Если это число - форматируем, если строка - показываем как есть
                                                if (is_numeric($state)) {
                                                    return number_format($state, 3, '.', ' ');
                                                }

                                                return $state ?: '0.000';
                                            })
                                            ->suffix(function (Get $get) {
                                                $templateId = $get('product_template_id');
                                                if ($templateId) {
                                                    $template = ProductTemplate::find($templateId);

                                                    return $template ? $template->unit : '';
                                                }

                                                return '';
                                            })
                                            ->helperText('Автоматически рассчитывается при заполнении характеристик или изменении количества'),
                                    ])
                                    ->visible(fn (Get $get) => $get('product_template_id') !== null),
                            ])
                            ->addActionLabel('Добавить товар')
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? 'Товар')
                            ->defaultItems(1)
                            ->minItems(1),
                    ]),

                Section::make('Документы')
                    ->schema([
                        FileUpload::make('document_path')
                            ->label('Документы')
                            ->directory('documents')
                            ->multiple()
                            ->maxFiles(5)
                            ->maxSize(51200) // 50MB
                            ->acceptedFileTypes(['application/pdf', 'image/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain'])
                            ->preserveFilenames()
                            ->downloadable()
                            ->openable()
                            ->previewable()
                            ->imagePreviewHeight('250'),
                    ]),

                Section::make('Дополнительная информация')
                    ->schema([
                        Grid::make(1)
                            ->schema([
                                Textarea::make('notes')
                                    ->label('Заметки')
                                    ->rows(3)
                                    ->maxLength(1000)
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Наименование')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('calculated_volume')
                    ->label('Объем')
                    ->formatStateUsing(function ($state) {
                        return $state ? number_format($state, 3).' м³' : '-';
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Склад')
                    ->sortable(),

                Tables\Columns\TextColumn::make('shipping_location')
                    ->label('Место отгрузки')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('shipping_date')
                    ->label('Дата отгрузки')
                    ->date('d.m.Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('producer.name')
                    ->label('Производитель')
                    ->formatStateUsing(function (
                        $state
                    ) {
                        return $state ?: 'Не указан';
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('expected_arrival_date')
                    ->label('Ожидаемая дата')
                    ->date('d.m.Y')
                    ->sortable()
                    ->color(function (Product $record): string {
                        $expected = $record->expected_arrival_date;
                        if (! $expected) {
                            return 'success';
                        }

                        return (($record->status === Product::STATUS_IN_TRANSIT || $record->status === Product::STATUS_FOR_RECEIPT) && $expected < now()) ? 'danger' : 'success';
                    }),

                Tables\Columns\TextColumn::make('actual_arrival_date')
                    ->label('Фактическая дата')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Количество')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('transport_number')
                    ->label('Номер транспорта')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ordered' => 'gray',
                        'in_transit' => 'warning',
                        'arrived' => 'info',
                        'received' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'ordered' => 'Заказан',
                        'in_transit' => 'В пути',
                        'arrived' => 'Прибыл',
                        'received' => 'Получен',
                        'cancelled' => 'Отменен',
                        default => $state,
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('notes')
                    ->label('Заметки')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }

                        return $state;
                    }),

                Tables\Columns\ViewColumn::make('document_path')
                    ->label('Документы')
                    ->view('tables.columns.documents')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Дата создания')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Дата обновления')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Сотрудник')
                    ->sortable(),

            ])
            ->filters([
                SelectFilter::make('warehouse')
                    ->relationship('warehouse', 'name')
                    ->multiple()
                    ->searchable(false)
                    ->preload()
                    ->label('Склад'),

                SelectFilter::make('producer')
                    ->relationship('producer', 'name')
                    ->multiple()
                    ->searchable(false)
                    ->preload()
                    ->label('Производитель'),

                Filter::make('shipping_date')
                    ->form([
                        DatePicker::make('shipping_from')
                            ->label('Дата отгрузки от'),

                        DatePicker::make('shipping_until')
                            ->label('Дата отгрузки до'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['shipping_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('shipping_date', '>=', $date),
                            )
                            ->when(
                                $data['shipping_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('shipping_date', '<=', $date),
                            );
                    }),
            ])
            ->emptyStateHeading('Нет товаров в пути')
            ->emptyStateDescription('Создайте первый товар в пути, чтобы начать работу.')
            ->actions([
                Tables\Actions\ViewAction::make()->label(''),
                Tables\Actions\EditAction::make()->label(''),
                Tables\Actions\DeleteAction::make()->label(''),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductInTransit::route('/'),
            'create' => Pages\CreateProductInTransit::route('/create'),
            'view' => Pages\ViewProductInTransit::route('/{record}'),
            'edit' => Pages\EditProductInTransit::route('/{record}/edit'),
        ];
    }

    /**
     * Рассчитать объем для элемента товара
     */
    private static function calculateVolumeForItem(Set $set, Get $get): void
    {
        $templateId = $get('product_template_id');
        if (! $templateId) {
            $set('calculated_volume', 'Выберите шаблон');

            return;
        }

        // Используем кэш для шаблона с атрибутами, чтобы избежать повторных запросов к БД
        static $templateCache = [];
        if (! isset($templateCache[$templateId])) {
            $templateCache[$templateId] = ProductTemplate::with('attributes')->find($templateId);
        }

        $template = $templateCache[$templateId];
        if (! $template) {
            $set('calculated_volume', 'Шаблон не найден');

            return;
        }

        // Собираем все значения характеристик
        $attributes = [];
        $formData = $get();

        foreach ($formData as $key => $value) {
            if (str_starts_with($key, 'attribute_') && $value !== null && $value !== '') {
                $attributeName = str_replace('attribute_', '', $key);
                // Нормализуем числовые значения: заменяем запятую на точку
                $normalizedValue = is_string($value) ? str_replace(',', '.', $value) : $value;
                $attributes[$attributeName] = $normalizedValue;
            }
        }

        // Добавляем количество
        $quantity = $get('quantity') ?? 1;
        // Нормализуем количество: заменяем запятую на точку
        $normalizedQuantity = is_string($quantity) ? str_replace(',', '.', $quantity) : $quantity;
        if (is_numeric($normalizedQuantity) && $normalizedQuantity > 0) {
            $attributes['quantity'] = $normalizedQuantity;
        }

        // Формируем наименование из характеристик с правильным разделителем
        $formulaParts = [];
        $regularParts = [];

        foreach ($template->attributes as $templateAttribute) {
            $attributeKey = $templateAttribute->variable;
            if ($templateAttribute->type !== 'text' && isset($attributes[$attributeKey]) && $attributes[$attributeKey] !== null && $attributes[$attributeKey] !== '') {
                if ($templateAttribute->is_in_formula) {
                    $formulaParts[] = $attributes[$attributeKey];
                } else {
                    $regularParts[] = $attributes[$attributeKey];
                }
            }
        }

        if (! empty($formulaParts) || ! empty($regularParts)) {
            $templateName = $template->name ?? 'Товар';
            $generatedName = $templateName;

            if (! empty($formulaParts)) {
                $generatedName .= ': '.implode(' x ', $formulaParts);
            }

            if (! empty($regularParts)) {
                if (! empty($formulaParts)) {
                    $generatedName .= ', '.implode(', ', $regularParts);
                } else {
                    $generatedName .= ': '.implode(', ', $regularParts);
                }
            }

            $set('name', $generatedName);
        } else {
            $set('name', $template->name ?? 'Товар');
        }

        // Рассчитываем объем только если есть формула
        if ($template->formula && ! empty($attributes)) {
            // Рассчитываем объем только для числовых характеристик
            $numericAttributes = [];
            foreach ($attributes as $key => $value) {
                if (is_numeric($value) && $value > 0) {
                    $numericAttributes[$key] = (float) $value;
                }
            }

            if (! empty($numericAttributes)) {
                $testResult = $template->testFormula($numericAttributes);
                if ($testResult['success']) {
                    $set('calculated_volume', $testResult['result']);
                } else {
                    $set('calculated_volume', 'Ошибка формулы: '.($testResult['error'] ?? 'Неизвестная ошибка'));
                }
            } else {
                $set('calculated_volume', 'Заполните числовые характеристики');
            }
        } else {
            $set('calculated_volume', $template->formula ? 'Заполните характеристики' : 'Формула не задана');
        }
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        $base = parent::getEloquentQuery()->where('status', Product::STATUS_FOR_RECEIPT);

        if (! $user) {
            return $base->whereRaw('1 = 0');
        }

        if ($user->role->value === 'admin') {
            return $base;
        }

        if ($user->warehouse_id) {
            return $base->where('warehouse_id', $user->warehouse_id);
        }

        return $base->whereRaw('1 = 0');
    }
}
