<?php

namespace Tests\Feature\Api;

use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\ProductTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCorrectionFieldsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Создаем тестовые данные
        $warehouse = Warehouse::factory()->create();
        $template = ProductTemplate::factory()->create();
        $user = User::factory()->create(['role' => 'admin']);
        
        $this->actingAs($user);
    }

    public function test_api_returns_correction_fields_for_products_with_correction(): void
    {
        // Создаем товар с уточнением
        $product = Product::factory()->create([
            'name' => 'Товар с уточнением',
            'status' => Product::STATUS_IN_STOCK,
            'correction' => 'Это уточнение для товара',
            'correction_status' => 'correction',
            'warehouse_id' => Warehouse::first()->id,
        ]);

        // Проверяем API ответ
        $response = $this->getJson('/api/products');
        $response->assertStatus(200);
        
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'correction',
                    'correction_status',
                    'revised_at',
                    'status',
                    'warehouse',
                    'template',
                    'creator'
                ]
            ]
        ]);

        // Проверяем, что поля correction присутствуют в ответе
        $response->assertJsonFragment([
            'correction' => 'Это уточнение для товара',
            'correction_status' => 'correction'
        ]);
    }

    public function test_api_returns_correction_fields_for_revised_products(): void
    {
        // Создаем товар со статусом revised
        $product = Product::factory()->create([
            'name' => 'Скорректированный товар',
            'status' => Product::STATUS_IN_STOCK,
            'correction' => 'Было уточнение',
            'correction_status' => 'revised',
            'warehouse_id' => Warehouse::first()->id,
        ]);

        // Проверяем API ответ
        $response = $this->getJson('/api/products');
        $response->assertStatus(200);
        
        // Проверяем, что поля correction присутствуют в ответе
        $response->assertJsonFragment([
            'correction' => 'Было уточнение',
            'correction_status' => 'revised'
        ]);
    }

    public function test_api_returns_null_correction_fields_for_normal_products(): void
    {
        // Создаем обычный товар без уточнений
        $product = Product::factory()->create([
            'name' => 'Обычный товар',
            'status' => Product::STATUS_IN_STOCK,
            'correction' => null,
            'correction_status' => null,
            'warehouse_id' => Warehouse::first()->id,
        ]);

        // Проверяем API ответ
        $response = $this->getJson('/api/products');
        $response->assertStatus(200);
        
        // Проверяем, что поля correction присутствуют в ответе как null
        $response->assertJsonFragment([
            'correction' => null,
            'correction_status' => null
        ]);
    }

    public function test_single_product_api_returns_correction_fields(): void
    {
        // Создаем товар с уточнением
        $product = Product::factory()->create([
            'name' => 'Товар с уточнением',
            'status' => Product::STATUS_IN_STOCK,
            'correction' => 'Детальное уточнение',
            'correction_status' => 'correction',
            'warehouse_id' => Warehouse::first()->id,
        ]);

        // Проверяем API ответ для одного товара
        $response = $this->getJson("/api/products/{$product->id}");
        $response->assertStatus(200);
        
        $response->assertJsonStructure([
            'id',
            'name',
            'correction',
            'correction_status',
            'revised_at',
            'status',
            'warehouse',
            'template',
            'creator'
        ]);

        // Проверяем конкретные значения
        $response->assertJson([
            'id' => $product->id,
            'correction' => 'Детальное уточнение',
            'correction_status' => 'correction'
        ]);
    }
}
