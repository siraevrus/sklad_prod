<?php

namespace Tests\Feature\Api;

use App\Models\Product;
use App\Models\ProductTemplate;
use App\Models\User;
use App\Models\Warehouse;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCreatorNameTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_creator_always_has_name_field_in_api_response(): void
    {
        // Создаем склад и шаблон продукта
        $warehouse = Warehouse::factory()->create();
        $template = ProductTemplate::factory()->create();

        // Создаем пользователя с заполненными first_name и last_name, но пустым name
        $creator = User::factory()->create([
            'role' => UserRole::WAREHOUSE_WORKER,
            'warehouse_id' => $warehouse->id,
            'name' => '', // Пустое поле name - имитируем проблему
            'first_name' => 'Администратор',
            'last_name' => 'Системы',
            'username' => 'admin',
            'email' => 'admin@sklad.ru',
        ]);

        // Создаем продукт
        $product = Product::factory()->create([
            'product_template_id' => $template->id,
            'warehouse_id' => $warehouse->id,
            'created_by' => $creator->id,
        ]);

        // Авторизуемся как администратор
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->actingAs($admin);

        // Запрашиваем список продуктов
        $response = $this->getJson('/api/products?include=creator')
            ->assertOk();

        $data = $response->json('data');
        $this->assertNotEmpty($data);

        // Проверяем, что у первого продукта есть creator с полем name
        $firstProduct = $data[0];
        $this->assertArrayHasKey('creator', $firstProduct);
        $this->assertArrayHasKey('name', $firstProduct['creator']);
        $this->assertNotEmpty($firstProduct['creator']['name']);

        // Проверяем, что name сформировано правильно из first_name и last_name
        $this->assertEquals('Администратор Системы', $firstProduct['creator']['name']);
    }

    public function test_product_creator_name_fallback_to_username_when_no_names(): void
    {
        // Создаем склад и шаблон продукта
        $warehouse = Warehouse::factory()->create();
        $template = ProductTemplate::factory()->create();

        // Создаем пользователя только с username, без имен
        $creator = User::factory()->create([
            'role' => UserRole::WAREHOUSE_WORKER,
            'warehouse_id' => $warehouse->id,
            'name' => '',
            'first_name' => '',
            'last_name' => '',
            'username' => 'test_user',
            'email' => 'test@example.com',
        ]);

        // Создаем продукт
        $product = Product::factory()->create([
            'product_template_id' => $template->id,
            'warehouse_id' => $warehouse->id,
            'created_by' => $creator->id,
        ]);

        // Авторизуемся как администратор
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->actingAs($admin);

        // Запрашиваем список продуктов
        $response = $this->getJson('/api/products?include=creator')
            ->assertOk();

        $data = $response->json('data');
        $this->assertNotEmpty($data);

        // Проверяем, что у первого продукта есть creator с полем name
        $firstProduct = $data[0];
        $this->assertArrayHasKey('creator', $firstProduct);
        $this->assertArrayHasKey('name', $firstProduct['creator']);
        $this->assertEquals('test_user', $firstProduct['creator']['name']);
    }

    public function test_product_creator_name_fallback_to_email_when_no_username(): void
    {
        // Создаем склад и шаблон продукта
        $warehouse = Warehouse::factory()->create();
        $template = ProductTemplate::factory()->create();

        // Создаем пользователя только с email
        $creator = User::factory()->create([
            'role' => UserRole::WAREHOUSE_WORKER,
            'warehouse_id' => $warehouse->id,
            'name' => '',
            'first_name' => '',
            'last_name' => '',
            'username' => '',
            'email' => 'fallback@example.com',
        ]);

        // Создаем продукт
        $product = Product::factory()->create([
            'product_template_id' => $template->id,
            'warehouse_id' => $warehouse->id,
            'created_by' => $creator->id,
        ]);

        // Авторизуемся как администратор
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->actingAs($admin);

        // Запрашиваем список продуктов
        $response = $this->getJson('/api/products?include=creator')
            ->assertOk();

        $data = $response->json('data');
        $this->assertNotEmpty($data);

        // Проверяем, что у первого продукта есть creator с полем name
        $firstProduct = $data[0];
        $this->assertArrayHasKey('creator', $firstProduct);
        $this->assertArrayHasKey('name', $firstProduct['creator']);
        $this->assertEquals('Пользователь', $firstProduct['creator']['name']);
    }
}
