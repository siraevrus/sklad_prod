<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Traits\HasLoadingIndicator;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateProduct extends CreateRecord
{
    use HasLoadingIndicator;

    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $startTime = microtime(true);
        $logId = uniqid('product_create_', true);
        
        \Log::info("=== STEP 1: START PRODUCT CREATION ===", [
            'log_id' => $logId,
            'timestamp' => now()->toIso8601String(),
            'user_id' => Auth::id(),
            'warehouse_id' => $data['warehouse_id'] ?? null,
        ]);

        // ============ STEP 2: Set created_by ============
        $data['created_by'] = Auth::id();
        \Log::info("STEP 2: Set created_by", [
            'log_id' => $logId,
            'created_by' => $data['created_by'],
        ]);

        // ============ STEP 3: Handle warehouse_id for non-admin users ============
        $user = Auth::user();
        if (! isset($data['warehouse_id']) && $user && ! $user->isAdmin()) {
            $data['warehouse_id'] = $user->warehouse_id;
            \Log::info("STEP 3: Set warehouse_id for non-admin user", [
                'log_id' => $logId,
                'user_is_admin' => false,
                'warehouse_id' => $data['warehouse_id'],
            ]);
        } else {
            \Log::info("STEP 3: Warehouse_id handling", [
                'log_id' => $logId,
                'warehouse_id_already_set' => isset($data['warehouse_id']),
                'user_is_admin' => $user?->isAdmin() ?? false,
                'warehouse_id' => $data['warehouse_id'] ?? null,
            ]);
        }

        // ============ STEP 4: Extract attributes from form fields ============
        \Log::info("STEP 4: Starting attribute extraction", [
            'log_id' => $logId,
            'total_form_fields' => count($data),
            'form_field_keys' => array_keys($data),
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
                } else {
                    \Log::debug("  ⊘ Skipped null attribute", [
                        'log_id' => $logId,
                        'field_key' => $key,
                    ]);
                }
            }
        }
        
        $data['attributes'] = $attributes;
        \Log::info("STEP 4: Attributes extracted", [
            'log_id' => $logId,
            'extracted_count' => $attributeCount,
            'attributes' => $data['attributes'],
        ]);

        // ============ STEP 5: Remove temporary attribute fields ============
        $removedCount = 0;
        foreach ($data as $key => $value) {
            if (str_starts_with($key, 'attribute_')) {
                unset($data[$key]);
                $removedCount++;
            }
        }
        \Log::info("STEP 5: Removed temporary fields", [
            'log_id' => $logId,
            'removed_count' => $removedCount,
        ]);

        // ============ STEP 6: Ensure attributes is always set ============
        if (! isset($data['attributes'])) {
            $data['attributes'] = [];
        }
        \Log::info("STEP 6: Ensured attributes field", [
            'log_id' => $logId,
            'attributes_set' => isset($data['attributes']),
            'attributes_empty' => empty($data['attributes']),
        ]);

        // ============ STEP 7: Collect basic product info ============
        \Log::info("STEP 7: Basic product info", [
            'log_id' => $logId,
            'product_template_id' => $data['product_template_id'] ?? null,
            'name' => $data['name'] ?? null,
            'quantity' => $data['quantity'] ?? null,
            'is_active' => $data['is_active'] ?? null,
            'producer_id' => $data['producer_id'] ?? null,
            'arrival_date' => $data['arrival_date'] ?? null,
        ]);

        // ============ STEP 8: Load template and validate ============
        $template = null;
        if (isset($data['product_template_id'])) {
            $template = \App\Models\ProductTemplate::find($data['product_template_id']);
            \Log::info("STEP 8: Template loaded", [
                'log_id' => $logId,
                'template_id' => $data['product_template_id'],
                'template_found' => $template !== null,
                'template_name' => $template?->name,
                'has_formula' => $template?->formula ? true : false,
                'formula' => $template?->formula,
            ]);
        } else {
            \Log::warning("STEP 8: No template_id provided", [
                'log_id' => $logId,
            ]);
        }

        // ============ STEP 9: Process formula and calculate volume ============
        if ($template && $template->formula && ! empty($data['attributes'])) {
            \Log::info("STEP 9: Starting formula processing", [
                'log_id' => $logId,
                'formula' => $template->formula,
                'attributes_count' => count($data['attributes']),
                'attributes' => $data['attributes'],
            ]);

            // ---- STEP 9.1: Prepare formula attributes ----
            $formulaAttributes = $data['attributes'];
            if (isset($data['quantity']) && is_numeric($data['quantity']) && $data['quantity'] > 0) {
                $formulaAttributes['quantity'] = $data['quantity'];
                \Log::debug("  9.1: Added quantity to formula attributes", [
                    'log_id' => $logId,
                    'quantity' => $data['quantity'],
                ]);
            }
            \Log::info("  9.1: Formula attributes prepared", [
                'log_id' => $logId,
                'formula_attributes' => $formulaAttributes,
            ]);

            // ---- STEP 9.2: Build product name from attributes ----
            $formulaParts = [];
            $regularParts = [];
            $templateAttributes = $template->attributes ?? [];
            
            \Log::debug("  9.2: Building product name", [
                'log_id' => $logId,
                'template_attributes_count' => count($templateAttributes),
            ]);

            foreach ($templateAttributes as $templateAttribute) {
                $attributeKey = $templateAttribute->variable;
                if (isset($data['attributes'][$attributeKey]) && $data['attributes'][$attributeKey] !== null) {
                    if ($templateAttribute->type !== 'text') {
                        if ($templateAttribute->is_in_formula) {
                            $formulaParts[] = $data['attributes'][$attributeKey];
                            \Log::debug("    ✓ Added to formula parts", [
                                'log_id' => $logId,
                                'variable' => $attributeKey,
                                'value' => $data['attributes'][$attributeKey],
                            ]);
                        } else {
                            $regularParts[] = $data['attributes'][$attributeKey];
                            \Log::debug("    ✓ Added to regular parts", [
                                'log_id' => $logId,
                                'variable' => $attributeKey,
                                'value' => $data['attributes'][$attributeKey],
                            ]);
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
                \Log::info("  9.2: Product name generated", [
                    'log_id' => $logId,
                    'generated_name' => $generatedName,
                    'formula_parts' => $formulaParts,
                    'regular_parts' => $regularParts,
                ]);
            } else {
                $data['name'] = $template->name ?? 'Товар';
                \Log::info("  9.2: Used template name (no parts)", [
                    'log_id' => $logId,
                    'name' => $data['name'],
                ]);
            }

            // ---- STEP 9.3: Call testFormula ----
            \Log::info("  9.3: Calling testFormula()", [
                'log_id' => $logId,
                'formula' => $template->formula,
                'formula_attributes' => $formulaAttributes,
            ]);

            $testResult = $template->testFormula($formulaAttributes);
            
            \Log::info("  9.3: testFormula() result", [
                'log_id' => $logId,
                'success' => $testResult['success'],
                'result' => $testResult['result'] ?? null,
                'error' => $testResult['error'] ?? null,
            ]);

            // ---- STEP 9.4: Process test result ----
            if ($testResult['success']) {
                $result = $testResult['result'];
                $data['calculated_volume'] = $result;
                \Log::info("  9.4: ✓ Volume calculated successfully", [
                    'log_id' => $logId,
                    'calculated_volume' => $result,
                    'type' => gettype($result),
                ]);
            } else {
                \Log::warning("  9.4: ✗ Volume calculation FAILED", [
                    'log_id' => $logId,
                    'error' => $testResult['error'],
                    'formula_attributes' => $formulaAttributes,
                ]);
            }
        } else {
            \Log::warning("STEP 9: Skipped formula processing", [
                'log_id' => $logId,
                'reason' => [
                    'has_template' => $template !== null,
                    'has_formula' => $template?->formula ? true : false,
                    'has_attributes' => ! empty($data['attributes']),
                ],
            ]);
        }

        // ============ STEP 10: Final data preparation ============
        \Log::info("STEP 10: Final data before create", [
            'log_id' => $logId,
            'data_keys' => array_keys($data),
            'data' => [
                'product_template_id' => $data['product_template_id'] ?? null,
                'name' => $data['name'] ?? null,
                'quantity' => $data['quantity'] ?? null,
                'calculated_volume' => $data['calculated_volume'] ?? null,
                'attributes' => $data['attributes'] ?? [],
                'producer_id' => $data['producer_id'] ?? null,
                'warehouse_id' => $data['warehouse_id'] ?? null,
                'created_by' => $data['created_by'] ?? null,
                'is_active' => $data['is_active'] ?? null,
            ],
        ]);

        // ============ STEP 11: Ready for save ============
        $duration = microtime(true) - $startTime;
        \Log::info("=== STEP 11: READY FOR SAVE ===", [
            'log_id' => $logId,
            'processing_time_ms' => round($duration * 1000, 2),
            'timestamp' => now()->toIso8601String(),
        ]);

        return $data;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Рассчитываем объем при загрузке формы, если есть шаблон
        if (isset($data['product_template_id'])) {
            $template = \App\Models\ProductTemplate::find($data['product_template_id']);
            if ($template && $template->formula) {
                // Если есть характеристики, рассчитываем объем
                if (isset($data['attributes']) && is_array($data['attributes']) && ! empty($data['attributes'])) {
                    // Создаем копию атрибутов для формулы, включая quantity
                    $formulaAttributes = $data['attributes'];
                    if (isset($data['quantity']) && is_numeric($data['quantity']) && $data['quantity'] > 0) {
                        $formulaAttributes['quantity'] = $data['quantity'];
                    }

                    \Log::info('BeforeFill: Attributes for formula', [
                        'template' => $template->name,
                        'attributes' => $data['attributes'],
                        'formula_attributes' => $formulaAttributes,
                        'quantity' => $data['quantity'] ?? 'not set',
                    ]);

                    $testResult = $template->testFormula($formulaAttributes);
                    if ($testResult['success']) {
                        $result = $testResult['result'];
                        $data['calculated_volume'] = $result;
                        \Log::info('BeforeFill: Volume calculated', ['result' => $result]);
                    } else {
                        \Log::warning('BeforeFill: Volume calculation failed', [
                            'error' => $testResult['error'],
                            'attributes' => $formulaAttributes,
                        ]);
                    }
                }
            }
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Товар создан')
            ->body('Товар успешно создан и добавлен в систему.')
            ->send();
    }
}
