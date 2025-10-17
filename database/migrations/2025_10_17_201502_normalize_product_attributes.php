<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Получаем все товары с атрибутами
        $products = DB::table('products')->whereNotNull('attributes')->get();

        foreach ($products as $product) {
            if ($product->attributes) {
                $attributes = json_decode($product->attributes, true);

                if (is_array($attributes)) {
                    $normalized = $this->normalizeAttributes($attributes);
                    $normalizedJson = json_encode($normalized, JSON_UNESCAPED_UNICODE);

                    // Обновляем если изменилось
                    if ($normalizedJson !== $product->attributes) {
                        DB::table('products')
                            ->where('id', $product->id)
                            ->update(['attributes' => $normalizedJson]);
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Откатываем нельзя, так как это было исходное состояние
    }

    /**
     * Нормализирует атрибуты товара
     * Преобразует строковые числа в числа
     */
    private function normalizeAttributes(array $attributes): array
    {
        $normalized = [];

        foreach ($attributes as $key => $value) {
            // Если это строка, пытаемся конвертировать в число
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
};
