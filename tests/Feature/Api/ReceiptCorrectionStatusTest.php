<?php

namespace Tests\Feature\Api;

use App\Models\Product;
use App\Models\ProductTemplate;
use App\Models\User;
use App\Models\Warehouse;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceiptCorrectionStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_correction_works_with_in_transit_status(): void
    {
        // Создаем склад и шаблон продукта
        $warehouse = Warehouse::factory()->create();
        $template = ProductTemplate::factory()->create();

        // Создаем пользователя
        $user = User::factory()->create([
            'role' => UserRole::WAREHOUSE_WORKER,
            'warehouse_id' => $warehouse->id,
        ]);

        // Создаем товар в статусе "в пути"
        $product = Product::factory()->create([
            'product_template_id' => $template->id,
            'warehouse_id' => $warehouse->id,
            'created_by' => $user->id,
            'status' => Product::STATUS_IN_TRANSIT,
            'is_active' => true,
        ]);

        $this->actingAs($user);

        // Тестируем correction с товаром в статусе in_transit
        $response = $this->postJson("/api/receipts/{$product->id}/correction", [
            'correction' => 'Уточнение для товара в пути',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Уточнение сохранено и товар принят',
            ]);

        // Проверяем, что товар переведен в статус in_stock
        $product->refresh();
        $this->assertEquals(Product::STATUS_IN_STOCK, $product->status);
        $this->assertEquals('Уточнение для товара в пути', $product->correction);
        $this->assertEquals('correction', $product->correction_status);
    }

    public function test_correction_works_with_for_receipt_status(): void
    {
        // Создаем склад и шаблон продукта
        $warehouse = Warehouse::factory()->create();
        $template = ProductTemplate::factory()->create();

        // Создаем пользователя
        $user = User::factory()->create([
            'role' => UserRole::WAREHOUSE_WORKER,
            'warehouse_id' => $warehouse->id,
        ]);

        // Создаем товар в статусе "готов к приемке"
        $product = Product::factory()->create([
            'product_template_id' => $template->id,
            'warehouse_id' => $warehouse->id,
            'created_by' => $user->id,
            'status' => Product::STATUS_FOR_RECEIPT,
            'is_active' => true,
        ]);

        $this->actingAs($user);

        // Тестируем correction с товаром в статусе for_receipt
        $response = $this->postJson("/api/receipts/{$product->id}/correction", [
            'correction' => 'Уточнение для товара готового к приемке',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Уточнение сохранено и товар принят',
            ]);

        // Проверяем, что товар переведен в статус in_stock
        $product->refresh();
        $this->assertEquals(Product::STATUS_IN_STOCK, $product->status);
        $this->assertEquals('Уточнение для товара готового к приемке', $product->correction);
        $this->assertEquals('correction', $product->correction_status);
    }

    public function test_correction_fails_with_in_stock_status(): void
    {
        // Создаем склад и шаблон продукта
        $warehouse = Warehouse::factory()->create();
        $template = ProductTemplate::factory()->create();

        // Создаем пользователя
        $user = User::factory()->create([
            'role' => UserRole::WAREHOUSE_WORKER,
            'warehouse_id' => $warehouse->id,
        ]);

        // Создаем товар в статусе "в остатках"
        $product = Product::factory()->create([
            'product_template_id' => $template->id,
            'warehouse_id' => $warehouse->id,
            'created_by' => $user->id,
            'status' => Product::STATUS_IN_STOCK,
            'is_active' => true,
        ]);

        $this->actingAs($user);

        // Тестируем correction с товаром в статусе in_stock
        $response = $this->postJson("/api/receipts/{$product->id}/correction", [
            'correction' => 'Уточнение для товара в остатках',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Товар не найден или не находится в пути/готов к приемке',
            ]);
    }

    public function test_correction_fails_with_inactive_product(): void
    {
        // Создаем склад и шаблон продукта
        $warehouse = Warehouse::factory()->create();
        $template = ProductTemplate::factory()->create();

        // Создаем пользователя
        $user = User::factory()->create([
            'role' => UserRole::WAREHOUSE_WORKER,
            'warehouse_id' => $warehouse->id,
        ]);

        // Создаем неактивный товар в статусе "в пути"
        $product = Product::factory()->create([
            'product_template_id' => $template->id,
            'warehouse_id' => $warehouse->id,
            'created_by' => $user->id,
            'status' => Product::STATUS_IN_TRANSIT,
            'is_active' => false, // Неактивный товар
        ]);

        $this->actingAs($user);

        // Тестируем correction с неактивным товаром
        $response = $this->postJson("/api/receipts/{$product->id}/correction", [
            'correction' => 'Уточнение для неактивного товара',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Товар не найден или не находится в пути/готов к приемке',
            ]);
    }
}
