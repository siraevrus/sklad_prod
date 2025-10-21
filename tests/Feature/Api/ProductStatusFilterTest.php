<?php

namespace Tests\Feature\Api;

use App\Models\Product;
use App\Models\ProductTemplate;
use App\Models\User;
use App\Models\Warehouse;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductStatusFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_filter_products_by_for_receipt_status(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create([
            'role' => UserRole::WAREHOUSE_WORKER,
            'warehouse_id' => $warehouse->id,
        ]);
        $template = ProductTemplate::factory()->create();

        // Создаем товары с разными статусами
        $forReceiptProduct = Product::factory()->create([
            'product_template_id' => $template->id,
            'warehouse_id' => $warehouse->id,
            'status' => Product::STATUS_FOR_RECEIPT,
            'name' => 'Товар готов к приемке',
        ]);

        $inStockProduct = Product::factory()->create([
            'product_template_id' => $template->id,
            'warehouse_id' => $warehouse->id,
            'status' => Product::STATUS_IN_STOCK,
            'name' => 'Товар в остатках',
        ]);

        $this->actingAs($user);

        // Тест: получение товаров со статусом for_receipt
        $response = $this->getJson('/api/products?status=for_receipt&per_page=15')
            ->assertOk();

        $data = $response->json('data');
        $this->assertIsArray($data);

        // Проверяем, что в ответе только товары со статусом for_receipt
        foreach ($data as $product) {
            $this->assertEquals(Product::STATUS_FOR_RECEIPT, $product['status'],
                'Ответ содержит товар со статусом '.$product['status'].', а не for_receipt');
        }

        // Проверяем, что наш товар в результатах
        $productNames = array_column($data, 'name');
        $this->assertContains('Товар готов к приемке', $productNames);
    }

    public function test_can_filter_products_by_in_stock_status(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create([
            'role' => UserRole::WAREHOUSE_WORKER,
            'warehouse_id' => $warehouse->id,
        ]);
        $template = ProductTemplate::factory()->create();

        // Создаем товары с разными статусами
        $forReceiptProduct = Product::factory()->create([
            'product_template_id' => $template->id,
            'warehouse_id' => $warehouse->id,
            'status' => Product::STATUS_FOR_RECEIPT,
            'name' => 'Товар готов к приемке',
        ]);

        $inStockProduct = Product::factory()->create([
            'product_template_id' => $template->id,
            'warehouse_id' => $warehouse->id,
            'status' => Product::STATUS_IN_STOCK,
            'name' => 'Товар в остатках',
        ]);

        $this->actingAs($user);

        // Тест: получение товаров со статусом in_stock
        $response = $this->getJson('/api/products?status=in_stock&per_page=15')
            ->assertOk();

        $data = $response->json('data');
        $this->assertIsArray($data);

        // Проверяем, что в ответе только товары со статусом in_stock
        foreach ($data as $product) {
            $this->assertEquals(Product::STATUS_IN_STOCK, $product['status'],
                'Ответ содержит товар со статусом '.$product['status'].', а не in_stock');
        }

        // Проверяем, что наш товар в результатах
        $productNames = array_column($data, 'name');
        $this->assertContains('Товар в остатках', $productNames);
    }

    public function test_search_respects_status_filter(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create([
            'role' => UserRole::WAREHOUSE_WORKER,
            'warehouse_id' => $warehouse->id,
        ]);
        $template = ProductTemplate::factory()->create();

        // Создаем товары с разными статусами но одинаковым названием
        $forReceiptProduct = Product::factory()->create([
            'product_template_id' => $template->id,
            'warehouse_id' => $warehouse->id,
            'status' => Product::STATUS_FOR_RECEIPT,
            'name' => 'Пило материалы тестовый',
        ]);

        $inStockProduct = Product::factory()->create([
            'product_template_id' => $template->id,
            'warehouse_id' => $warehouse->id,
            'status' => Product::STATUS_IN_STOCK,
            'name' => 'Пило материалы тестовый',
        ]);

        $this->actingAs($user);

        // Ищем "пило" с фильтром status=for_receipt
        $response = $this->getJson('/api/products?search=пило&status=for_receipt&per_page=15')
            ->assertOk();

        $data = $response->json('data');

        // Проверяем, что ВСЕ результаты имеют статус for_receipt
        foreach ($data as $product) {
            $this->assertEquals(Product::STATUS_FOR_RECEIPT, $product['status'],
                'При поиске с фильтром status=for_receipt вернулся товар со статусом '.$product['status']);
        }
    }
}
