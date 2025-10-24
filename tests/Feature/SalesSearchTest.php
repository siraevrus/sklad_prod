<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Тест поиска продаж по названию товара
     */
    public function test_can_search_sales_by_product_name(): void
    {
        $user = User::first();
        $product = Product::first();

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

        // Ищем по части названия товара
        $searchTerm = substr($product->name, 0, 10); // Берем первые 10 символов
        $response = $this->actingAs($user, 'sanctum')->getJson("/api/sales?search={$searchTerm}");

        $response->assertStatus(200);
        $sales = $response->json('data');

        $this->assertGreaterThan(0, count($sales), 'Должна быть найдена хотя бы одна продажа');

        // Проверяем, что найденная продажа содержит искомый термин в названии товара
        $found = false;
        foreach ($sales as $sale) {
            if (str_contains(strtolower($sale['product']['name']), strtolower($searchTerm))) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Должна быть найдена продажа с товаром, содержащим искомый термин');
    }

    /**
     * Тест поиска продаж по названию шаблона товара
     */
    public function test_can_search_sales_by_product_template_name(): void
    {
        $user = User::first();
        $product = Product::with('template')->first();

        if (! $product || ! $product->template) {
            $this->markTestSkipped('Нет товаров с шаблонами для тестирования');
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

        // Ищем по названию шаблона
        $templateName = $product->template->name;
        $response = $this->actingAs($user, 'sanctum')->getJson("/api/sales?search={$templateName}");

        $response->assertStatus(200);
        $sales = $response->json('data');

        $this->assertGreaterThan(0, count($sales), 'Должна быть найдена хотя бы одна продажа');

        // Проверяем, что найденная продажа содержит искомый термин в названии шаблона
        $found = false;
        foreach ($sales as $sale) {
            if (isset($sale['product']['template']['name']) &&
                str_contains(strtolower($sale['product']['template']['name']), strtolower($templateName))) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Должна быть найдена продажа с шаблоном товара, содержащим искомый термин');
    }

    /**
     * Тест поиска продаж по номеру продажи
     */
    public function test_can_search_sales_by_sale_number(): void
    {
        $user = User::first();

        // Создаем тестовый товар с правильными данными
        $warehouse = \App\Models\Warehouse::first();
        $producer = \App\Models\Producer::first();
        $template = \App\Models\ProductTemplate::first();

        if (! $warehouse || ! $producer || ! $template) {
            $this->markTestSkipped('Нет необходимых данных для тестирования');
        }

        $product = \App\Models\Product::create([
            'product_template_id' => $template->id,
            'warehouse_id' => $warehouse->id,
            'producer_id' => $producer->id,
            'created_by' => $user->id,
            'name' => 'Тестовый товар',
            'description' => 'Товар для тестирования',
            'quantity' => 100,
            'status' => \App\Models\Product::STATUS_IN_STOCK,
            'arrival_date' => now()->format('Y-m-d'),
            'is_active' => true,
        ]);

        // Устанавливаем склад пользователю, чтобы он мог создавать продажи
        $user->warehouse_id = $product->warehouse_id;
        $user->save();

        // Создаем продажу
        $compositeKey = $product->product_template_id.'|'.$product->warehouse_id.'|'.$product->producer_id.'|'.$product->name;

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/sales', [
            'composite_product_key' => $compositeKey,
            'warehouse_id' => $product->warehouse_id,
            'customer_name' => 'Тестовый клиент',
            'quantity' => 5,
            'total_price' => 100.00,
            'sale_date' => now()->format('Y-m-d'),
        ]);

        $response->assertStatus(201);

        $saleNumber = $response->json('sale.sale_number');

        // Ищем по номеру продажи
        $response = $this->actingAs($user, 'sanctum')->getJson("/api/sales?search={$saleNumber}");

        $response->assertStatus(200);
        $sales = $response->json('data');

        $this->assertGreaterThan(0, count($sales), 'Должна быть найдена хотя бы одна продажа');

        // Проверяем, что найденная продажа имеет правильный номер
        $found = false;
        foreach ($sales as $sale) {
            if ($sale['sale_number'] === $saleNumber) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Должна быть найдена продажа с правильным номером');
    }

    /**
     * Тест поиска продаж по имени клиента
     */
    public function test_can_search_sales_by_customer_name(): void
    {
        $user = User::first();

        // Создаем тестовый товар с правильными данными
        $warehouse = \App\Models\Warehouse::first();
        $producer = \App\Models\Producer::first();
        $template = \App\Models\ProductTemplate::first();

        if (! $warehouse || ! $producer || ! $template) {
            $this->markTestSkipped('Нет необходимых данных для тестирования');
        }

        $product = \App\Models\Product::create([
            'product_template_id' => $template->id,
            'warehouse_id' => $warehouse->id,
            'producer_id' => $producer->id,
            'created_by' => $user->id,
            'name' => 'Тестовый товар для клиента',
            'description' => 'Товар для тестирования поиска по клиенту',
            'quantity' => 100,
            'status' => \App\Models\Product::STATUS_IN_STOCK,
            'arrival_date' => now()->format('Y-m-d'),
            'is_active' => true,
        ]);

        // Устанавливаем склад пользователю, чтобы он мог создавать продажи
        $user->warehouse_id = $product->warehouse_id;
        $user->save();

        $customerName = 'Иван Петров';

        // Создаем продажу
        $compositeKey = $product->product_template_id.'|'.$product->warehouse_id.'|'.$product->producer_id.'|'.$product->name;
        $this->actingAs($user, 'sanctum')->postJson('/api/sales', [
            'composite_product_key' => $compositeKey,
            'warehouse_id' => $product->warehouse_id,
            'customer_name' => $customerName,
            'quantity' => 5,
            'total_price' => 100.00,
            'sale_date' => now()->format('Y-m-d'),
        ]);

        // Ищем по имени клиента
        $response = $this->actingAs($user, 'sanctum')->getJson("/api/sales?search={$customerName}");

        $response->assertStatus(200);
        $sales = $response->json('data');

        $this->assertGreaterThan(0, count($sales), 'Должна быть найдена хотя бы одна продажа');

        // Проверяем, что найденная продажа имеет правильное имя клиента
        $found = false;
        foreach ($sales as $sale) {
            if ($sale['customer_name'] === $customerName) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Должна быть найдена продажа с правильным именем клиента');
    }

    /**
     * Тест поиска продаж по телефону клиента
     */
    public function test_can_search_sales_by_customer_phone(): void
    {
        $user = User::first();

        // Создаем тестовый товар с правильными данными
        $warehouse = \App\Models\Warehouse::first();
        $producer = \App\Models\Producer::first();
        $template = \App\Models\ProductTemplate::first();

        if (! $warehouse || ! $producer || ! $template) {
            $this->markTestSkipped('Нет необходимых данных для тестирования');
        }

        $product = \App\Models\Product::create([
            'product_template_id' => $template->id,
            'warehouse_id' => $warehouse->id,
            'producer_id' => $producer->id,
            'created_by' => $user->id,
            'name' => 'Тестовый товар для телефона',
            'description' => 'Товар для тестирования поиска по телефону',
            'quantity' => 100,
            'status' => \App\Models\Product::STATUS_IN_STOCK,
            'arrival_date' => now()->format('Y-m-d'),
            'is_active' => true,
        ]);

        // Устанавливаем склад пользователю, чтобы он мог создавать продажи
        $user->warehouse_id = $product->warehouse_id;
        $user->save();

        $customerPhone = '+998901234567';

        // Создаем продажу
        $compositeKey = $product->product_template_id.'|'.$product->warehouse_id.'|'.$product->producer_id.'|'.$product->name;
        $this->actingAs($user, 'sanctum')->postJson('/api/sales', [
            'composite_product_key' => $compositeKey,
            'warehouse_id' => $product->warehouse_id,
            'customer_name' => 'Тестовый клиент',
            'customer_phone' => $customerPhone,
            'quantity' => 5,
            'total_price' => 100.00,
            'sale_date' => now()->format('Y-m-d'),
        ]);

        // Ищем по телефону клиента
        $response = $this->actingAs($user, 'sanctum')->getJson("/api/sales?search={$customerPhone}");

        $response->assertStatus(200);
        $sales = $response->json('data');

        $this->assertGreaterThan(0, count($sales), 'Должна быть найдена хотя бы одна продажа');

        // Проверяем, что найденная продажа имеет правильный телефон
        $found = false;
        foreach ($sales as $sale) {
            if ($sale['customer_phone'] === $customerPhone) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Должна быть найдена продажа с правильным телефоном клиента');
    }

    /**
     * Тест экспорта продаж с поиском по названию товара
     */
    public function test_can_export_sales_with_product_name_search(): void
    {
        $user = User::first();
        $product = Product::first();

        if (! $product) {
            $this->markTestSkipped('Нет товаров для тестирования');
        }

        // Устанавливаем склад пользователю, чтобы он мог создавать продажи
        $user->warehouse_id = $product->warehouse_id;
        $user->save();

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

        // Экспортируем с поиском по части названия товара
        $searchTerm = substr($product->name, 0, 10);
        $response = $this->actingAs($user, 'sanctum')->getJson("/api/sales/export?search={$searchTerm}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'sale_number',
                        'customer_name',
                        'product_name',
                        'quantity',
                        'total_price',
                    ],
                ],
                'total',
            ]);

        $exportData = $response->json('data');
        $this->assertGreaterThan(0, count($exportData), 'Должны быть найдены данные для экспорта');

        // Проверяем, что экспортированные данные содержат искомый термин
        $found = false;
        foreach ($exportData as $sale) {
            if (str_contains(strtolower($sale['product_name']), strtolower($searchTerm))) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Экспортированные данные должны содержать искомый термин в названии товара');
    }
}
