<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Удалить'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $startTime = microtime(true);
        $logId = uniqid('product_edit_', true);
        
        \Log::info("=== STEP 1: START PRODUCT EDITING ===", [
            'log_id' => $logId,
            'timestamp' => now()->toIso8601String(),
            'product_id' => $this->record?->id,
            'user_id' => \Illuminate\Support\Facades\Auth::id(),
        ]);

        // ============ STEP 2: Extract attributes from form fields ============
        \Log::info("STEP 2: Starting attribute extraction", [
            'log_id' => $logId,
            'total_form_fields' => count($data),
        ]);

        $attributes = [];
        $attributeCount = 0;
        foreach ($data as $key => $value) {
            if (str_starts_with($key, 'attribute_')) {
                if ($value !== null) {
                    $attributeName = str_replace('attribute_', '', $key);
                    $attributes[$attributeName] = $value;
                    $attributeCount++;
                    
                    \Log::debug("  ✓ Extracted attribute", [
                        'log_id' => $logId,
                        'field_key' => $key,
                        'attribute_name' => $attributeName,
                        'value' => $value,
                    ]);
                }
            }
        }
        
        $data['attributes'] = $attributes;
        \Log::info("STEP 2: Attributes extracted", [
            'log_id' => $logId,
            'extracted_count' => $attributeCount,
            'attributes' => $data['attributes'],
        ]);

        // ============ STEP 3: Remove temporary attribute fields ============
        $removedCount = 0;
        foreach ($data as $key => $value) {
            if (str_starts_with($key, 'attribute_')) {
                unset($data[$key]);
                $removedCount++;
            }
        }
        \Log::info("STEP 3: Removed temporary fields", [
            'log_id' => $logId,
            'removed_count' => $removedCount,
        ]);

        // ============ STEP 4: Ensure attributes is always set ============
        if (! isset($data['attributes'])) {
            $data['attributes'] = [];
        }
        \Log::info("STEP 4: Ensured attributes field", [
            'log_id' => $logId,
            'attributes_set' => isset($data['attributes']),
            'attributes_empty' => empty($data['attributes']),
        ]);

        // ============ STEP 5: Collect basic product info ============
        \Log::info("STEP 5: Basic product info", [
            'log_id' => $logId,
            'product_template_id' => $data['product_template_id'] ?? null,
            'quantity' => $data['quantity'] ?? null,
            'producer_id' => $data['producer_id'] ?? null,
        ]);

        // ============ STEP 6: Load template and validate ============
        $template = null;
        if (isset($data['product_template_id'])) {
            $template = \App\Models\ProductTemplate::find($data['product_template_id']);
            \Log::info("STEP 6: Template loaded", [
                'log_id' => $logId,
                'template_id' => $data['product_template_id'],
                'template_found' => $template !== null,
                'template_name' => $template?->name,
                'has_formula' => $template?->formula ? true : false,
            ]);
        } else {
            \Log::warning("STEP 6: No template_id provided", [
                'log_id' => $logId,
            ]);
        }

        // ============ STEP 7: Process formula and calculate volume ============
        if ($template && $template->formula && ! empty($data['attributes'])) {
            \Log::info("STEP 7: Starting formula processing", [
                'log_id' => $logId,
                'formula' => $template->formula,
                'attributes_count' => count($data['attributes']),
            ]);

            // ---- STEP 7.1: Prepare formula attributes ----
            $formulaAttributes = $data['attributes'];
            if (isset($data['quantity'])) {
                $formulaAttributes['quantity'] = $data['quantity'];
                \Log::debug("  7.1: Added quantity to formula attributes", [
                    'log_id' => $logId,
                    'quantity' => $data['quantity'],
                ]);
            }
            \Log::info("  7.1: Formula attributes prepared", [
                'log_id' => $logId,
                'formula_attributes' => $formulaAttributes,
            ]);

            // ---- STEP 7.2: Build product name from attributes ----
            $formulaParts = [];
            $regularParts = [];
            $templateAttributes = $template->attributes ?? [];

            foreach ($templateAttributes as $templateAttribute) {
                $attributeKey = $templateAttribute->variable;
                if (isset($data['attributes'][$attributeKey]) && $data['attributes'][$attributeKey] !== null) {
                    if ($templateAttribute->type !== 'text') {
                        if ($templateAttribute->is_in_formula) {
                            $formulaParts[] = $data['attributes'][$attributeKey];
                        } else {
                            $regularParts[] = $data['attributes'][$attributeKey];
                        }
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

                $data['name'] = $generatedName;
                \Log::info("  7.2: Product name generated", [
                    'log_id' => $logId,
                    'generated_name' => $generatedName,
                    'formula_parts' => $formulaParts,
                    'regular_parts' => $regularParts,
                ]);
            }

            // ---- STEP 7.3: Call testFormula ----
            \Log::info("  7.3: Calling testFormula()", [
                'log_id' => $logId,
                'formula' => $template->formula,
                'formula_attributes' => $formulaAttributes,
            ]);

            $testResult = $template->testFormula($formulaAttributes);
            
            \Log::info("  7.3: testFormula() result", [
                'log_id' => $logId,
                'success' => $testResult['success'],
                'result' => $testResult['result'] ?? null,
                'error' => $testResult['error'] ?? null,
            ]);

            // ---- STEP 7.4: Process test result ----
            if ($testResult['success']) {
                $result = $testResult['result'];
                $data['calculated_volume'] = $result;
                \Log::info("  7.4: ✓ Volume calculated successfully", [
                    'log_id' => $logId,
                    'calculated_volume' => $result,
                    'type' => gettype($result),
                ]);
            } else {
                \Log::warning("  7.4: ✗ Volume calculation FAILED", [
                    'log_id' => $logId,
                    'error' => $testResult['error'],
                    'formula_attributes' => $formulaAttributes,
                ]);
            }
        } else {
            \Log::warning("STEP 7: Skipped formula processing", [
                'log_id' => $logId,
                'reason' => [
                    'has_template' => $template !== null,
                    'has_formula' => $template?->formula ? true : false,
                    'has_attributes' => ! empty($data['attributes']),
                ],
            ]);
        }

        // ============ STEP 8: Final data preparation ============
        \Log::info("STEP 8: Final data before save", [
            'log_id' => $logId,
            'data' => [
                'product_template_id' => $data['product_template_id'] ?? null,
                'name' => $data['name'] ?? null,
                'quantity' => $data['quantity'] ?? null,
                'calculated_volume' => $data['calculated_volume'] ?? null,
                'attributes' => $data['attributes'] ?? [],
                'producer_id' => $data['producer_id'] ?? null,
            ],
        ]);

        // ============ STEP 9: Ready for save ============
        $duration = microtime(true) - $startTime;
        \Log::info("=== STEP 9: READY FOR SAVE ===", [
            'log_id' => $logId,
            'processing_time_ms' => round($duration * 1000, 2),
            'product_id' => $this->record?->id,
            'timestamp' => now()->toIso8601String(),
        ]);

        return $data;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Загружаем характеристики в отдельные поля для формы
        if (isset($data['attributes']) && is_array($data['attributes'])) {
            $template = \App\Models\ProductTemplate::find($data['product_template_id']);

            foreach ($data['attributes'] as $key => $value) {
                if ($template) {
                    // Находим атрибут шаблона
                    $templateAttribute = $template->attributes->where('variable', $key)->first();
                    if ($templateAttribute && $templateAttribute->type === 'select') {
                        // Для селектов находим индекс значения
                        $options = $templateAttribute->options_array;
                        $index = array_search($value, $options);
                        $data["attribute_{$key}"] = $index !== false ? $index : null;
                    } else {
                        $data["attribute_{$key}"] = $value;
                    }
                } else {
                    $data["attribute_{$key}"] = $value;
                }
            }
        }

        // Рассчитываем объем при загрузке данных
        if (isset($data['product_template_id']) && isset($data['attributes']) && is_array($data['attributes'])) {
            $template = \App\Models\ProductTemplate::find($data['product_template_id']);
            if ($template && $template->formula && ! empty($data['attributes'])) {
                // Создаем копию атрибутов для формулы, включая quantity
                $formulaAttributes = $data['attributes'];
                if (isset($data['quantity']) && is_numeric($data['quantity']) && $data['quantity'] > 0) {
                    $formulaAttributes['quantity'] = $data['quantity'];
                }

                \Log::info('BeforeFill (EditProduct): Attributes for formula', [
                    'template' => $template->name,
                    'attributes' => $data['attributes'],
                    'formula_attributes' => $formulaAttributes,
                    'quantity' => $data['quantity'] ?? 'not set',
                ]);

                $testResult = $template->testFormula($formulaAttributes);
                if ($testResult['success']) {
                    $result = $testResult['result'];

                    // Применяем валидацию как в ProductResource
                    $maxValue = 999999999.9999; // Максимум для decimal(15,4)
                    if ($result > $maxValue) {
                        \Log::warning('BeforeFill (EditProduct): Volume exceeds maximum value', [
                            'calculated_volume' => $result,
                            'max_value' => $maxValue,
                        ]);
                        $data['calculated_volume'] = null;
                    } else {
                        $data['calculated_volume'] = $result;
                    }

                    \Log::info('BeforeFill (EditProduct): Volume calculated', ['result' => $result]);
                } else {
                    \Log::warning('BeforeFill (EditProduct): Volume calculation failed', [
                        'error' => $testResult['error'],
                        'attributes' => $formulaAttributes,
                    ]);
                }
            }
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
