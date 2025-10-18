<?php

namespace App\Support;

use App\Models\ProductTemplate;
use Filament\Forms\Set;
use Illuminate\Support\Facades\Log;

class ProductAttributeCalculator
{
    /**
     * Кэш для шаблонов с атрибутами
     */
    private static array $templateCache = [];

    /**
     * Рассчитать объем и обновить наименование для элемента товара
     *
     * @param  Set  $set  Filament Set контекст
     * @param  array  $formData  Данные формы с атрибутами
     * @param  int  $templateId  ID шаблона товара
     * @param  mixed  $quantity  Количество товара
     */
    public static function calculateAndUpdate(Set $set, array $formData, int $templateId, mixed $quantity = null): void
    {
        if (! $templateId) {
            $set('calculated_volume', 'Выберите шаблон');

            return;
        }

        $template = self::getTemplateFromCache($templateId);
        if (! $template) {
            $set('calculated_volume', 'Шаблон не найден');

            return;
        }

        // Собираем атрибуты
        $attributes = self::extractAttributes($formData);

        // Добавляем количество
        if ($quantity !== null && is_numeric($quantity) && $quantity > 0) {
            $attributes['quantity'] = (int) $quantity;
        }

        // Генерируем наименование
        $generatedName = self::generateName($template, $attributes);
        if ($generatedName) {
            $set('name', $generatedName);
        }

        // Рассчитываем объем
        self::calculateVolume($set, $template, $attributes);
    }

    /**
     * Получить шаблон из кэша или БД
     */
    private static function getTemplateFromCache(int $templateId): ?ProductTemplate
    {
        if (! isset(self::$templateCache[$templateId])) {
            self::$templateCache[$templateId] = ProductTemplate::with('attributes')->find($templateId);
        }

        return self::$templateCache[$templateId];
    }

    /**
     * Извлечь атрибуты из данных формы
     */
    private static function extractAttributes(array $formData): array
    {
        $attributes = [];

        foreach ($formData as $key => $value) {
            if (str_starts_with($key, 'attribute_') && $value !== null && $value !== '') {
                $attributeName = str_replace('attribute_', '', $key);
                // Нормализуем числовые значения: заменяем запятую на точку
                $normalizedValue = is_string($value) ? str_replace(',', '.', $value) : $value;
                $attributes[$attributeName] = $normalizedValue;
            }
        }

        return $attributes;
    }

    /**
     * Сгенерировать наименование товара из атрибутов
     */
    private static function generateName(ProductTemplate $template, array $attributes): ?string
    {
        if (empty($template->attributes)) {
            return null;
        }

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

        if (empty($formulaParts) && empty($regularParts)) {
            return null;
        }

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

        return $generatedName;
    }

    /**
     * Рассчитать объем товара
     */
    private static function calculateVolume(Set $set, ProductTemplate $template, array $attributes): void
    {
        if (! $template->formula) {
            $set('calculated_volume', 'Формула не задана');

            return;
        }

        // Рассчитываем объем только для числовых характеристик
        $numericAttributes = [];
        foreach ($attributes as $key => $value) {
            if (is_numeric($value) && $value > 0) {
                $numericAttributes[$key] = (float) $value;
            }
        }

        if (empty($numericAttributes)) {
            $set('calculated_volume', 'Заполните числовые характеристики');

            return;
        }

        $testResult = $template->testFormula($numericAttributes);

        if ($testResult['success']) {
            $result = $testResult['result'];
            $set('calculated_volume', $result);

            if (config('app.debug')) {
                Log::debug('Volume calculated successfully', [
                    'template' => $template->name,
                    'attributes' => $numericAttributes,
                    'result' => $result,
                ]);
            }
        } else {
            $set('calculated_volume', 'Ошибка формулы: '.($testResult['error'] ?? 'Неизвестная ошибка'));
            Log::warning('Volume calculation failed', [
                'template' => $template->name,
                'attributes' => $numericAttributes,
                'error' => $testResult['error'] ?? 'Unknown',
            ]);
        }
    }

    /**
     * Очистить кэш (используется в тестах)
     */
    public static function clearCache(): void
    {
        self::$templateCache = [];
    }
}
