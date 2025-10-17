<?php

namespace App\Support;

class AttributeNormalizer
{
    /**
     * Нормализирует атрибуты товара
     * Преобразует строковые числа в числа для консистентности
     */
    public static function normalize(array $attributes): array
    {
        $normalized = [];

        foreach ($attributes as $key => $value) {
            if (is_string($value)) {
                // Проверяем, это ли число
                if (is_numeric($value)) {
                    // Если это целое число, преобразуем в int
                    if ((int) $value === (float) $value) {
                        $normalized[$key] = (int) $value;
                    } else {
                        // Если дробное число, преобразуем в float
                        $normalized[$key] = (float) $value;
                    }
                } else {
                    // Иначе оставляем как строка
                    $normalized[$key] = $value;
                }
            } else {
                // Остальное оставляем без изменений
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }
}
