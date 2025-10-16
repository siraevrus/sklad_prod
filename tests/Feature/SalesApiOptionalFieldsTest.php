<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesApiOptionalFieldsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Тест создания продажи с минимальными обязательными полями
     */
    public function test_can_create_sale_with_minimal_required_fields(): void
    {
        $user = User::first();
        $product = Product::first();
        
        if (!$product) {
            $this->markTestSkipped('Нет товаров для тестирования');
        }

        $compositeKey = $product->product_template_id . '|' . $product->warehouse_id . '|' . $product->producer_id . '|' . $product->name;

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/sales', [
            'composite_product_key' => $compositeKey,
            'warehouse_id' => $product->warehouse_id,
            'customer_name' => 'Тестовый клиент',
            'quantity' => 5,
            'sale_date' => now()->format('Y-m-d'),
        ]);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'message',
                    'sale' => [
                        'id',
                        'sale_number',
                        'unit_price',
                        'payment_method',
                        'total_price',
                        'vat_amount',
                        'price_without_vat',
                    ]
                ]);

        // Проверяем, что значения по умолчанию установлены корректно
        $sale = Sale::latest()->first();
        $this->assertEquals(0.00, $sale->unit_price);
        $this->assertEquals('other', $sale->payment_method);
        $this->assertEquals(20.00, $sale->vat_rate);
        $this->assertEquals(0.00, $sale->total_price);
        $this->assertEquals(0.00, $sale->vat_amount);
        $this->assertEquals(0.00, $sale->price_without_vat);
    }

    /**
     * Тест создания продажи с указанными unit_price и payment_method
     */
    public function test_can_create_sale_with_specified_optional_fields(): void
    {
        $user = User::first();
        $product = Product::first();
        
        if (!$product) {
            $this->markTestSkipped('Нет товаров для тестирования');
        }

        $compositeKey = $product->product_template_id . '|' . $product->warehouse_id . '|' . $product->producer_id . '|' . $product->name;

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/sales', [
            'composite_product_key' => $compositeKey,
            'warehouse_id' => $product->warehouse_id,
            'customer_name' => 'Тестовый клиент',
            'quantity' => 10,
            'unit_price' => 1500.00,
            'payment_method' => 'cash',
            'sale_date' => now()->format('Y-m-d'),
        ]);

        $response->assertStatus(201);

        // Проверяем, что указанные значения сохранились
        $sale = Sale::latest()->first();
        $this->assertEquals(1500.00, $sale->unit_price);
        $this->assertEquals('cash', $sale->payment_method);
        $this->assertEquals(15000.00, $sale->price_without_vat); // 1500 * 10
        $this->assertEquals(3000.00, $sale->vat_amount); // 15000 * 0.2
        $this->assertEquals(18000.00, $sale->total_price); // 15000 + 3000
    }

    /**
     * Тест валидации payment_method с неверным значением
     */
    public function test_validates_payment_method_enum_values(): void
    {
        $user = User::first();
        $product = Product::first();
        
        if (!$product) {
            $this->markTestSkipped('Нет товаров для тестирования');
        }

        $compositeKey = $product->product_template_id . '|' . $product->warehouse_id . '|' . $product->producer_id . '|' . $product->name;

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/sales', [
            'composite_product_key' => $compositeKey,
            'warehouse_id' => $product->warehouse_id,
            'customer_name' => 'Тестовый клиент',
            'quantity' => 5,
            'payment_method' => 'invalid_method',
            'sale_date' => now()->format('Y-m-d'),
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['payment_method']);
    }
}