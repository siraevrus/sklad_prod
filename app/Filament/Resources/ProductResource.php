<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use App\Models\ProductTemplate;
use App\Models\Warehouse;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ProductResource extends Resource
{
    use \App\Traits\SafeFilamentFormatting;

    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Товары';

    protected static ?string $modelLabel = 'Товар';

    protected static ?string $pluralModelLabel = 'Поступления товаров';

    protected static ?int $navigationSort = 5;

    public static function canViewAny(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        return in_array($user->role->value, [
            'admin',
            'operator',
        ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Основная информация')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                Select::make('warehouse_id')
                                    ->label('Склад')
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

                                Select::make('producer_id')
                                    ->label('Производитель')
                                    ->options(\App\Models\Producer::pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Выберите производителя')
                                    ->required(),

                                DatePicker::make('arrival_date')
                                    ->label('Дата поступления')
                                    ->required()
                                    ->default(now()),

                                TextInput::make('transport_number')
                                    ->label('Номер транспорта')
                                    ->maxLength(255),

                                Toggle::make('is_active')
                                    ->label('Активен')
                                    ->hidden()
                                    ->default(true),
                            ]),

                    ]),

                Section::make('Товары')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                Select::make('product_template_id')
                                    ->label('Шаблон товара')
                                    ->options(ProductTemplate::pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        $set('calculated_volume', null);
                                        $set('name', '');
                                        $template = ProductTemplate::find($get('product_template_id'));
                                        if ($template) {
                                            foreach ($template->attributes as $attribute) {
                                                $set("attribute_{$attribute->variable}", null);
                                            }
                                            if ($template->formula) {
                                                $set('calculated_volume', 'Заполните характеристики для расчета объема');
                                            }
                                        }
                                    }),

                                TextInput::make('name')
                                    ->label('Наименование')
                                    ->maxLength(255)
                                    ->disabled()
                                    ->helperText('Автоматически формируется из характеристик товара'),

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
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        // Пересчитываем объем при изменении количества
                                        $templateId = $get('product_template_id');
                                        if (! $templateId) {
                                            return;
                                        }

                                        $template = ProductTemplate::with('attributes')->find($templateId);
                                        if (! $template) {
                                            return;
                                        }

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

                                        // Рассчитываем объем для заполненных числовых характеристик
                                        $numericAttributes = [];
                                        foreach ($attributes as $key => $value) {
                                            if (is_numeric($value) && $value > 0) {
                                                $numericAttributes[$key] = $value;
                                            }
                                        }

                                        // Добавляем количество в атрибуты для формулы
                                        $quantity = $get('quantity') ?? 1;
                                        // Нормализуем количество: заменяем запятую на точку
                                        $normalizedQuantity = is_string($quantity) ? str_replace(',', '.', $quantity) : $quantity;
                                        if (is_numeric($normalizedQuantity) && $normalizedQuantity > 0) {
                                            $numericAttributes['quantity'] = $normalizedQuantity;
                                        }

                                        // Если есть заполненные числовые характеристики и формула, рассчитываем объем
                                        if (! empty($numericAttributes) && $template->formula) {
                                            $testResult = $template->testFormula($numericAttributes);
                                            if ($testResult['success']) {
                                                $result = $testResult['result'];
                                                // Проверяем на превышение лимита
                                                $maxValue = 999999999.9999; // Максимум для decimal(15,4)
                                                if ($result > $maxValue) {
                                                    $set('calculated_volume', 'Объем превышает максимальное значение');
                                                    Log::warning('Volume exceeds maximum value from quantity change', [
                                                        'template' => $template->name,
                                                        'attributes' => $numericAttributes,
                                                        'result' => $result,
                                                        'max_value' => $maxValue,
                                                    ]);
                                                } else {
                                                    $set('calculated_volume', $result);

                                                    // Логируем для отладки
                                                    if (config('app.debug')) {
                                                        Log::debug('Volume calculated from quantity change', [
                                                            'template' => $template->name,
                                                            'attributes' => $numericAttributes,
                                                            'result' => $result,
                                                        ]);
                                                    }
                                                }
                                            } else {
                                                // Если расчет не удался, показываем ошибку
                                                $set('calculated_volume', 'Заполните поля: '.($testResult['error'] ?? 'Неизвестная ошибка'));
                                                Log::warning('Volume calculation failed from quantity change', [
                                                    'template' => $template->name,
                                                    'attributes' => $numericAttributes,
                                                    'error' => $testResult['error'],
                                                ]);
                                            }
                                        } else {
                                            // Если недостаточно данных для расчета, показываем подсказку
                                            if (empty($numericAttributes)) {
                                                $set('calculated_volume', 'Заполните числовые характеристики');
                                            } else {
                                                $set('calculated_volume', 'Формула не задана');
                                            }
                                        }
                                    }),
                            ]),

                        // Шаблоны товара
                        Grid::make(3)
                            ->visible(fn (Get $get) => $get('product_template_id') !== null)
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
                                                ->label($attribute->full_name)
                                                ->inputMode('decimal')
                                                ->maxLength(10)
                                                ->required($attribute->is_required)
                                                ->rules(['regex:/^\d*([.,]\d*)?$/'])
                                                ->validationMessages([
                                                    'regex' => 'Поле должно содержать только цифры и одну запятую или точку',
                                                ])
                                                ->key("number_attr_{$attribute->id}_{$attribute->variable}")
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function (Set $set, Get $get) use ($template) {
                                                    // Рассчитываем объем при изменении характеристики
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

                                                    // Рассчитываем объем для заполненных числовых характеристик
                                                    $numericAttributes = [];
                                                    foreach ($attributes as $key => $value) {
                                                        if (is_numeric($value) && $value > 0) {
                                                            $numericAttributes[$key] = $value;
                                                        }
                                                    }

                                                    // Добавляем количество в атрибуты для формулы
                                                    $quantity = $get('quantity') ?? 1;
                                                    if (is_numeric($quantity) && $quantity > 0) {
                                                        $numericAttributes['quantity'] = $quantity;
                                                    }

                                                    // Логируем атрибуты для отладки
                                                    if (config('app.debug')) {
                                                        Log::debug('Attributes for volume calculation (number)', [
                                                            'template' => $template->name,
                                                            'all_attributes' => $attributes,
                                                            'numeric_attributes' => $numericAttributes,
                                                            'quantity' => $quantity,
                                                            'formula' => $template->formula,
                                                        ]);
                                                    }

                                                    // Если есть заполненные числовые характеристики и формула, рассчитываем объем
                                                    if (! empty($numericAttributes) && $template->formula) {
                                                        $testResult = $template->testFormula($numericAttributes);
                                                        if ($testResult['success']) {
                                                            $result = $testResult['result'];
                                                            // Проверяем на превышение лимита
                                                            $maxValue = 999999999.9999; // Максимум для decimal(15,4)
                                                            if ($result > $maxValue) {
                                                                $set('calculated_volume', 'Объем превышает максимальное значение');
                                                                Log::warning('Volume exceeds maximum value', [
                                                                    'template' => $template->name,
                                                                    'attributes' => $numericAttributes,
                                                                    'result' => $result,
                                                                    'max_value' => $maxValue,
                                                                ]);
                                                            } else {
                                                                $set('calculated_volume', $result);

                                                                // Логируем для отладки
                                                                if (config('app.debug')) {
                                                                    Log::debug('Volume calculated', [
                                                                        'template' => $template->name,
                                                                        'attributes' => $numericAttributes,
                                                                        'result' => $result,
                                                                    ]);
                                                                }
                                                            }
                                                        } else {
                                                            // Если расчет не удался, показываем ошибку
                                                            $set('calculated_volume', 'Заполните поля: '.($testResult['error'] ?? 'Неизвестная ошибка'));
                                                            Log::warning('Volume calculation failed', [
                                                                'template' => $template->name,
                                                                'attributes' => $numericAttributes,
                                                                'error' => $testResult['error'],
                                                            ]);
                                                        }
                                                    } else {
                                                        // Если недостаточно данных для расчета, показываем подсказку
                                                        if (empty($numericAttributes)) {
                                                            $set('calculated_volume', 'Заполните числовые характеристики');
                                                        } else {
                                                            $set('calculated_volume', 'Формула не задана');
                                                        }
                                                    }

                                                    // Формируем наименование из заполненных характеристик с правильным разделителем
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
                                                });
                                            break;

                                        case 'text':
                                            $fields[] = TextInput::make($fieldName)
                                                ->label($attribute->full_name)
                                                ->maxLength(255)
                                                ->required($attribute->is_required)
                                                ->key("text_attr_{$attribute->id}_{$attribute->variable}")
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function (Set $set, Get $get) use ($template) {
                                                    // Рассчитываем объем при изменении характеристики
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

                                                    // Рассчитываем объем для заполненных числовых характеристик
                                                    $numericAttributes = [];
                                                    foreach ($attributes as $key => $value) {
                                                        if (is_numeric($value) && $value > 0) {
                                                            $numericAttributes[$key] = $value;
                                                        }
                                                    }

                                                    // Добавляем количество в атрибуты для формулы
                                                    $quantity = $get('quantity') ?? 1;
                                                    if (is_numeric($quantity) && $quantity > 0) {
                                                        $numericAttributes['quantity'] = $quantity;
                                                    }

                                                    // Если есть заполненные числовые характеристики и формула, рассчитываем объем
                                                    if (! empty($numericAttributes) && $template->formula) {
                                                        $testResult = $template->testFormula($numericAttributes);
                                                        if ($testResult['success']) {
                                                            $result = $testResult['result'];
                                                            // Проверяем на превышение лимита
                                                            $maxValue = 999999999.9999; // Максимум для decimal(15,4)
                                                            if ($result > $maxValue) {
                                                                $set('calculated_volume', 'Объем превышает максимальное значение');
                                                                Log::warning('Volume exceeds maximum value', [
                                                                    'template' => $template->name,
                                                                    'attributes' => $numericAttributes,
                                                                    'result' => $result,
                                                                    'max_value' => $maxValue,
                                                                ]);
                                                            } else {
                                                                $set('calculated_volume', $result);

                                                                // Логируем для отладки
                                                                if (config('app.debug')) {
                                                                    Log::debug('Volume calculated', [
                                                                        'template' => $template->name,
                                                                        'attributes' => $numericAttributes,
                                                                        'result' => $result,
                                                                    ]);
                                                                }
                                                            }
                                                        } else {
                                                            // Если расчет не удался, показываем ошибку
                                                            $set('calculated_volume', 'Заполните поля: '.($testResult['error'] ?? 'Неизвестная ошибка'));
                                                            Log::warning('Volume calculation failed', [
                                                                'template' => $template->name,
                                                                'attributes' => $numericAttributes,
                                                                'error' => $testResult['error'],
                                                            ]);
                                                        }
                                                    } else {
                                                        // Если недостаточно данных для расчета, показываем подсказку
                                                        if (empty($numericAttributes)) {
                                                            $set('calculated_volume', 'Заполните числовые характеристики');
                                                        } else {
                                                            $set('calculated_volume', 'Формула не задана');
                                                        }
                                                    }
                                                });
                                            break;

                                        case 'select':
                                            $options = $attribute->options_array;
                                            $fields[] = Select::make($fieldName)
                                                ->label($attribute->full_name)
                                                ->options($options)
                                                ->required($attribute->is_required)
                                                ->key("select_attr_{$attribute->id}_{$attribute->variable}")
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function (Set $set, Get $get) use ($template) {
                                                    // Рассчитываем объем при изменении характеристики
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

                                                    // Рассчитываем объем для заполненных числовых характеристик
                                                    $numericAttributes = [];
                                                    foreach ($attributes as $key => $value) {
                                                        if (is_numeric($value) && $value > 0) {
                                                            $numericAttributes[$key] = $value;
                                                        }
                                                    }

                                                    // Добавляем количество в атрибуты для формулы
                                                    $quantity = $get('quantity') ?? 1;
                                                    if (is_numeric($quantity) && $quantity > 0) {
                                                        $numericAttributes['quantity'] = $quantity;
                                                    }

                                                    // Если есть заполненные числовые характеристики и формула, рассчитываем объем
                                                    if (! empty($numericAttributes) && $template->formula) {
                                                        $testResult = $template->testFormula($numericAttributes);
                                                        if ($testResult['success']) {
                                                            $result = $testResult['result'];
                                                            // Проверяем на превышение лимита
                                                            $maxValue = 999999999.9999; // Максимум для decimal(15,4)
                                                            if ($result > $maxValue) {
                                                                $set('calculated_volume', 'Объем превышает максимальное значение');
                                                                Log::warning('Volume exceeds maximum value', [
                                                                    'template' => $template->name,
                                                                    'attributes' => $numericAttributes,
                                                                    'result' => $result,
                                                                    'max_value' => $maxValue,
                                                                ]);
                                                            } else {
                                                                $set('calculated_volume', $result);

                                                                // Логируем для отладки
                                                                if (config('app.debug')) {
                                                                    Log::debug('Volume calculated', [
                                                                        'template' => $template->name,
                                                                        'attributes' => $numericAttributes,
                                                                        'result' => $result,
                                                                    ]);
                                                                }
                                                            }
                                                        } else {
                                                            // Если расчет не удался, показываем ошибку
                                                            $set('calculated_volume', 'Заполните поля: '.($testResult['error'] ?? 'Неизвестная ошибка'));
                                                            Log::warning('Volume calculation failed', [
                                                                'template' => $template->name,
                                                                'attributes' => $numericAttributes,
                                                                'error' => $testResult['error'],
                                                            ]);
                                                        }
                                                    } else {
                                                        // Если недостаточно данных для расчета, показываем подсказку
                                                        if (empty($numericAttributes)) {
                                                            $set('calculated_volume', 'Заполните числовые характеристики');
                                                        } else {
                                                            $set('calculated_volume', 'Формула не задана');
                                                        }
                                                    }

                                                    // Формируем наименование из заполненных характеристик с правильным разделителем
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
                                                })
                                                ->dehydrateStateUsing(function ($state, $get) use ($options) {
                                                    // Преобразуем индекс в значение для селектов
                                                    if ($state !== null && is_numeric($state) && isset($options[$state])) {
                                                        return $options[$state];
                                                    }

                                                    return $state;
                                                });
                                            break;
                                    }
                                }

                                return $fields;
                            }),

                        Grid::make(3)
                            ->visible(fn (Get $get) => $get('product_template_id') !== null)
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
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'in_stock'))
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

                Tables\Columns\TextColumn::make('arrival_date')
                    ->label('Дата поступления')
                    ->date()
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

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Количество')
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(function ($state) {
                        return (string) $state;  // Выводим как строку, без форматирования
                    }),

                Tables\Columns\TextColumn::make('transport_number')
                    ->label('Номер транспорта')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
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
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }

                        return $state;
                    }),
            ])
            ->filters([
                SelectFilter::make('warehouse')
                    ->relationship('warehouse', 'name')
                    ->multiple()
                    ->label('Склад'),

                SelectFilter::make('producer')
                    ->relationship('producer', 'name')
                    ->multiple()
                    ->label('Производитель'),

                Filter::make('arrival_date')
                    ->form([
                        DatePicker::make('arrival_from')
                            ->label('Дата поступления от'),

                        DatePicker::make('arrival_until')
                            ->label('Дата поступления до'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['arrival_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('arrival_date', '>=', $date),
                            )
                            ->when(
                                $data['arrival_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('arrival_date', '<=', $date),
                            );
                    }),

                Filter::make('status')
                    ->form([
                        Select::make('status')
                            ->label('Статус')
                            ->options([
                                'ordered' => 'Заказан',
                                'in_transit' => 'В пути',
                                'arrived' => 'Прибыл',
                                'received' => 'Получен',
                                'cancelled' => 'Отменен',
                            ])
                            ->multiple(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['status'],
                            fn (Builder $query, $status): Builder => $query->whereIn('status', $status),
                        );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->iconButton()
                    ->tooltip('Просмотр'),
                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->tooltip('Изменить'),
                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->tooltip('Удалить'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'view' => Pages\ViewProduct::route('/{record}'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}