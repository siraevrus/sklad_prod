<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Models\Warehouse;
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

        if (! $product) {
            $this->markTestSkipped('Нет товаров для тестирования');
        }

        $compositeKey = $product->product_template_id.'|'.$product->warehouse_id.'|'.$product->producer_id.'|'.$product->name;

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
                    'quantity',
                    'total_price',
                    'price_without_vat',
                ],
            ]);

        // Проверяем, что данные сохранены корректно
        $sale = Sale::latest()->first();
        $this->assertEquals(5, $sale->quantity);
        $this->assertEquals(0.00, $sale->total_price); // По умолчанию 0 если не отправлено
        $this->assertEquals('other', $sale->payment_method);
    }

    /**
     * Тест создания продажи с указанием общей суммы
     */
    public function test_can_create_sale_with_total_price(): void
    {
        $user = User::first();
        $product = Product::first();

        if (! $product) {
            $this->markTestSkipped('Нет товаров для тестирования');
        }

        $compositeKey = $product->product_template_id.'|'.$product->warehouse_id.'|'.$product->producer_id.'|'.$product->name;

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/sales', [
            'composite_product_key' => $compositeKey,
            'warehouse_id' => $product->warehouse_id,
            'customer_name' => 'Тестовый клиент',
            'quantity' => 5,
            'total_price' => 121.00,
            'cash_amount' => 121.00,
            'payment_method' => 'cash',
            'sale_date' => now()->format('Y-m-d'),
        ]);

        $response->assertStatus(201);

        // Проверяем, что сумма была сохранена как есть
        $sale = Sale::latest()->first();
        $this->assertEquals(121.00, $sale->total_price);
        $this->assertEquals(121.00, $sale->price_without_vat); // Равна total_price
        $this->assertEquals(121.00, $sale->cash_amount);
        $this->assertEquals('cash', $sale->payment_method);
    }

    /**
     * Тест создания продажи со смешанными платежами
     */
    public function test_can_create_sale_with_mixed_payments(): void
    {
        $user = User::first();
        $product = Product::first();

        if (! $product) {
            $this->markTestSkipped('Нет товаров для тестирования');
        }

        $compositeKey = $product->product_template_id.'|'.$product->warehouse_id.'|'.$product->producer_id.'|'.$product->name;

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/sales', [
            'composite_product_key' => $compositeKey,
            'warehouse_id' => $product->warehouse_id,
            'customer_name' => 'Тестовый клиент',
            'quantity' => 10,
            'total_price' => 1000.00,
            'cash_amount' => 500.00,
            'nocash_amount' => 500.00,
            'payment_method' => 'card',
            'sale_date' => now()->format('Y-m-d'),
        ]);

        $response->assertStatus(201);

        // Проверяем, что все суммы сохранены как есть
        $sale = Sale::latest()->first();
        $this->assertEquals(1000.00, $sale->total_price);
        $this->assertEquals(500.00, $sale->cash_amount);
        $this->assertEquals(500.00, $sale->nocash_amount);
    }

    /**
     * Тест валидации payment_method с неверным значением
     */
    public function test_validates_payment_method_enum_values(): void
    {
        $user = User::first();
        $product = Product::first();

        if (! $product) {
            $this->markTestSkipped('Нет товаров для тестирования');
        }

        $compositeKey = $product->product_template_id.'|'.$product->warehouse_id.'|'.$product->producer_id.'|'.$product->name;

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

    /**
     * Тест включения производителя в API ответ
     */
    public function test_sale_response_includes_producer(): void
    {
        $user = User::first();
        $product = Product::with('producer')->first();

        if (! $product) {
            $this->markTestSkipped('Нет товаров для тестирования');
        }

        $compositeKey = $product->product_template_id.'|'.$product->warehouse_id.'|'.$product->producer_id.'|'.$product->name;

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/sales', [
            'composite_product_key' => $compositeKey,
            'warehouse_id' => $product->warehouse_id,
            'customer_name' => 'Тестовый клиент',
            'quantity' => 5,
            'total_price' => 100.00,
            'sale_date' => now()->format('Y-m-d'),
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'sale' => [
                    'id',
                    'sale_number',
                    'product' => [
                        'id',
                        'name',
                        'producer' => [
                            'id',
                            'name',
                        ],
                    ],
                ],
            ]);

        // Проверяем, что производитель присутствует и совпадает
        $responseData = $response->json('sale.product.producer');
        $this->assertNotNull($responseData);
        $this->assertEquals($product->producer->id, $responseData['id']);
        $this->assertEquals($product->producer->name, $responseData['name']);
    }

    /**
     * Тест включения производителя в список продаж
     */
    public function test_sales_index_includes_producer(): void
    {
        $user = User::first();
        $product = Product::with('producer')->first();

        if (! $product) {
            $this->markTestSkipped('Нет товаров для тестирования');
        }

        // Создаем продажу
        $compositeKey = $product->product_template_id.'|'.$product->warehouse_id.'|'.$product->producer_id.'|'.$product->name;
        $this->actingAs($user, 'sanctum')->postJson('/api/sales', [
            'composite_product_key' => $compositeKey,
            'warehouse_id' => $product->warehouse_id,
            'customer_name' => 'Тестовый клиент',
            'quantity' => 5,
            'total_price' => 100.00,
            'sale_date' => now()->format('Y-m-d'),
        ]);

        // Получаем список продаж
        $response = $this->actingAs($user, 'sanctum')->getJson('/api/sales');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'sale_number',
                        'product' => [
                            'id',
                            'name',
                            'producer' => [
                                'id',
                                'name',
                            ],
                        ],
                    ],
                ],
            ]);

        // Проверяем, что производитель присутствует в каждой продаже
        $sales = $response->json('data');
        foreach ($sales as $sale) {
            $this->assertArrayHasKey('producer', $sale['product']);
            $this->assertNotNull($sale['product']['producer']);
        }
    }

    /**
     * Тест включения производителя в название товара при получении списка для выбора
     */
    public function test_aggregated_products_includes_producer_in_name(): void
    {
        $user = User::first();
        $warehouse = Warehouse::first();

        if (! $warehouse) {
            $this->markTestSkipped('Нет склада для тестирования');
        }

        // Получаем агрегированный список товаров для выбора при создании продажи
        $response = $this->actingAs($user, 'sanctum')->getJson('/api/products', [
            'aggregate' => true,
            'status' => 'in_stock',
            'warehouse_id' => $warehouse->id,
        ]);

        $response->assertStatus(200);

        // Проверяем, что в ответе есть товары
        $products = $response->json('data');
        if (count($products) > 0) {
            // Берем первый товар
            $product = $products[0];

            // Проверяем, что название содержит производителя
            if ($product['producer']) {
                $this->assertStringContainsString(
                    $product['producer'],
                    $product['name'],
                    "Название товара должно содержать имя производителя: '{$product['name']}'"
                );
            }
        }
    }
}
