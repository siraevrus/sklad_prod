<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Producer;
use App\Models\Product;
use App\Models\ProductTemplate;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchStockByProducerTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected Producer $producer;

    protected Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();

        // Создаем тестовые данные
        $this->adminUser = User::factory()->create(['role' => 'admin']);

        $company = Company::factory()->create();
        $this->warehouse = Warehouse::factory()->create(['company_id' => $company->id]);
        $this->producer = Producer::factory()->create();
    }

    public function test_search_by_producer_returns_matching_products(): void
    {
        // Создаем продукты с разными названиями
        $productTemplate1 = ProductTemplate::factory()->create();
        $productTemplate2 = ProductTemplate::factory()->create();

        Product::factory()->create([
            'product_template_id' => $productTemplate1->id,
            'producer_id' => $this->producer->id,
            'warehouse_id' => $this->warehouse->id,
            'name' => 'Деревянная доска 50х150х6000',
            'status' => Product::STATUS_IN_STOCK,
            'is_active' => true,
            'quantity' => 100,
            'volume_per_unit' => 0.045,
        ]);

        Product::factory()->create([
            'product_template_id' => $productTemplate2->id,
            'producer_id' => $this->producer->id,
            'warehouse_id' => $this->warehouse->id,
            'name' => 'Металлическая труба 25х25х2000',
            'status' => Product::STATUS_IN_STOCK,
            'is_active' => true,
            'quantity' => 50,
            'volume_per_unit' => 0.02,
        ]);

        // Запрос БЕЗ фильтра - должны вернуться оба товара
        $response = $this->actingAs($this->adminUser)
            ->getJson("/api/stocks/by-producer/{$this->producer->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertCount(2, $response->json('data'));

        // Запрос С фильтром "деревянная" - должен вернуться только первый товар
        $response = $this->actingAs($this->adminUser)
            ->getJson("/api/stocks/by-producer/{$this->producer->id}?search=деревянная");

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertCount(1, $response->json('data'));
        $this->assertStringContainsString('Деревянная', $response->json('data.0.name'));
    }

    public function test_search_by_producer_case_insensitive(): void
    {
        $productTemplate = ProductTemplate::factory()->create();

        Product::factory()->create([
            'product_template_id' => $productTemplate->id,
            'producer_id' => $this->producer->id,
            'warehouse_id' => $this->warehouse->id,
            'name' => 'ДЕРЕВЯННАЯ доска обрезная',
            'status' => Product::STATUS_IN_STOCK,
            'is_active' => true,
            'quantity' => 100,
            'volume_per_unit' => 0.045,
        ]);

        // Поиск с разными вариантами написания
        $searchTerms = ['деревянная', 'ДЕРЕВЯННАЯ', 'Деревянная', 'дерев'];

        foreach ($searchTerms as $term) {
            $response = $this->actingAs($this->adminUser)
                ->getJson("/api/stocks/by-producer/{$this->producer->id}?search={$term}");

            $response->assertStatus(200);
            $this->assertCount(1, $response->json('data'), "Search term '$term' should find the product");
        }
    }

    public function test_search_by_producer_empty_result(): void
    {
        $productTemplate = ProductTemplate::factory()->create();

        Product::factory()->create([
            'product_template_id' => $productTemplate->id,
            'producer_id' => $this->producer->id,
            'warehouse_id' => $this->warehouse->id,
            'name' => 'Деревянная доска',
            'status' => Product::STATUS_IN_STOCK,
            'is_active' => true,
            'quantity' => 100,
            'volume_per_unit' => 0.045,
        ]);

        // Поиск с несуществующим поисковым запросом
        $response = $this->actingAs($this->adminUser)
            ->getJson("/api/stocks/by-producer/{$this->producer->id}?search=несуществующий");

        $response->assertStatus(200);
        $response->assertJsonPath('data', []);
        $response->assertJsonPath('meta.total', 0);
    }

    public function test_search_by_producer_partial_match(): void
    {
        $productTemplate = ProductTemplate::factory()->create();

        Product::factory()->create([
            'product_template_id' => $productTemplate->id,
            'producer_id' => $this->producer->id,
            'warehouse_id' => $this->warehouse->id,
            'name' => 'Доска обрезная строганая',
            'status' => Product::STATUS_IN_STOCK,
            'is_active' => true,
            'quantity' => 100,
            'volume_per_unit' => 0.045,
        ]);

        // Поиск по части названия
        $response = $this->actingAs($this->adminUser)
            ->getJson("/api/stocks/by-producer/{$this->producer->id}?search=обрез");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }
}
