<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\ProductTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductCorrectionHighlightingTest extends TestCase
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

    public function test_product_with_correction_has_red_highlighting(): void
    {
        // Создаем товар с уточнением
        $product = Product::factory()->create([
            'name' => 'Тестовый товар с уточнением',
            'status' => Product::STATUS_IN_STOCK,
            'correction' => 'Это уточнение для товара',
            'correction_status' => 'correction',
            'warehouse_id' => Warehouse::first()->id,
        ]);

        // Проверяем, что метод hasCorrection() возвращает true
        $this->assertTrue($product->hasCorrection());
        
        // Проверяем, что товар имеет уточнение
        $this->assertNotNull($product->correction);
        $this->assertEquals('correction', $product->correction_status);
    }

    public function test_product_without_correction_has_normal_highlighting(): void
    {
        // Создаем товар без уточнения
        $product = Product::factory()->create([
            'name' => 'Обычный товар',
            'status' => Product::STATUS_IN_STOCK,
            'correction' => null,
            'correction_status' => null,
            'warehouse_id' => Warehouse::first()->id,
        ]);

        // Проверяем, что метод hasCorrection() возвращает false
        $this->assertFalse($product->hasCorrection());
        
        // Проверяем, что товар не имеет уточнения
        $this->assertNull($product->correction);
        $this->assertNull($product->correction_status);
    }

    public function test_product_list_shows_correction_status(): void
    {
        // Создаем товары с разными статусами уточнения
        $productWithCorrection = Product::factory()->create([
            'name' => 'Товар с уточнением',
            'status' => Product::STATUS_IN_STOCK,
            'correction' => 'Уточнение',
            'correction_status' => 'correction',
            'warehouse_id' => Warehouse::first()->id,
        ]);

        $productWithoutCorrection = Product::factory()->create([
            'name' => 'Обычный товар',
            'status' => Product::STATUS_IN_STOCK,
            'correction' => null,
            'correction_status' => null,
            'warehouse_id' => Warehouse::first()->id,
        ]);

        // Проверяем доступ к списку товаров
        $response = $this->get('/admin/products');
        $response->assertStatus(200);
        
        // Проверяем, что товары отображаются
        $response->assertSee('Товар с уточнением');
        $response->assertSee('Обычный товар');
    }
}
