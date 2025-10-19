<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductTemplate;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentPathJsonTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_path_serializes_correctly_in_api_response(): void
    {
        // Создаем тестовые данные
        $user = User::factory()->create(['role' => 'admin']);
        $template = ProductTemplate::factory()->create();
        $warehouse = Warehouse::factory()->create();

        // Создаем товар с пустым document_path
        $product = Product::factory()->create([
            'product_template_id' => $template->id,
            'warehouse_id' => $warehouse->id,
            'created_by' => $user->id,
            'document_path' => [],
        ]);

        // Делаем запрос к API
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/products');

        $response->assertStatus(200);

        $responseData = $response->json();

        // Проверяем, что document_path присутствует и является массивом
        $this->assertArrayHasKey('data', $responseData);
        $this->assertNotEmpty($responseData['data']);

        $productData = $responseData['data'][0];
        $this->assertArrayHasKey('document_path', $productData);
        $this->assertIsArray($productData['document_path']);
        $this->assertEquals([], $productData['document_path']);
    }

    public function test_document_path_handles_null_values(): void
    {
        // Создаем тестовые данные
        $user = User::factory()->create(['role' => 'admin']);
        $template = ProductTemplate::factory()->create();
        $warehouse = Warehouse::factory()->create();

        // Создаем товар с null document_path
        $product = Product::factory()->create([
            'product_template_id' => $template->id,
            'warehouse_id' => $warehouse->id,
            'created_by' => $user->id,
            'document_path' => null,
        ]);

        // Делаем запрос к API
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/products');

        $response->assertStatus(200);

        $responseData = $response->json();

        // Проверяем, что document_path присутствует и является пустым массивом
        $this->assertArrayHasKey('data', $responseData);
        $this->assertNotEmpty($responseData['data']);

        $productData = $responseData['data'][0];
        $this->assertArrayHasKey('document_path', $productData);
        $this->assertIsArray($productData['document_path']);
        $this->assertEquals([], $productData['document_path']);
    }

    public function test_document_path_handles_string_values(): void
    {
        // Создаем тестовые данные
        $user = User::factory()->create(['role' => 'admin']);
        $template = ProductTemplate::factory()->create();
        $warehouse = Warehouse::factory()->create();

        // Создаем товар с document_path как JSON строка
        $product = Product::factory()->create([
            'product_template_id' => $template->id,
            'warehouse_id' => $warehouse->id,
            'created_by' => $user->id,
            'document_path' => '["file1.pdf", "file2.pdf"]',
        ]);

        // Делаем запрос к API
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/products');

        $response->assertStatus(200);

        $responseData = $response->json();

        // Проверяем, что document_path правильно декодирован
        $this->assertArrayHasKey('data', $responseData);
        $this->assertNotEmpty($responseData['data']);

        $productData = $responseData['data'][0];
        $this->assertArrayHasKey('document_path', $productData);
        $this->assertIsArray($productData['document_path']);
        $this->assertEquals(['file1.pdf', 'file2.pdf'], $productData['document_path']);
    }

    public function test_json_response_is_valid(): void
    {
        // Создаем тестовые данные
        $user = User::factory()->create(['role' => 'admin']);
        $template = ProductTemplate::factory()->create();
        $warehouse = Warehouse::factory()->create();

        // Создаем товар
        $product = Product::factory()->create([
            'product_template_id' => $template->id,
            'warehouse_id' => $warehouse->id,
            'created_by' => $user->id,
            'document_path' => [],
        ]);

        // Делаем запрос к API
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/products');

        $response->assertStatus(200);

        // Проверяем, что ответ является валидным JSON
        $jsonString = $response->getContent();
        $this->assertJson($jsonString);

        // Проверяем, что можно декодировать JSON без ошибок
        $decoded = json_decode($jsonString, true);
        $this->assertNotNull($decoded);
        $this->assertIsArray($decoded);
    }
}
