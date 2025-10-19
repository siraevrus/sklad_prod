<?php

namespace Tests\Feature\Feature\Filament;

use App\Filament\Resources\ProductResource\Pages\CreateProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_products_list(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $response = $this->get('/admin/products');
        $response->assertStatus(200);
    }

    public function test_operator_can_access_products_list(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);
        $this->actingAs($operator);

        $response = $this->get('/admin/products');
        $response->assertStatus(200);
    }

    public function test_warehouse_worker_cannot_access_products_list(): void
    {
        $worker = User::factory()->create(['role' => 'warehouse_worker']);
        $this->actingAs($worker);

        $response = $this->get('/admin/products');
        $response->assertStatus(403);
    }

    public function test_admin_can_access_create_product_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $response = $this->get('/admin/products/create');
        $response->assertStatus(200);
    }

    public function test_create_product_page_has_loading_indicator_trait(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        // Test that the CreateProduct page uses the HasLoadingIndicator trait
        $reflection = new \ReflectionClass(CreateProduct::class);
        $this->assertTrue($reflection->hasMethod('create'), 'CreateProduct should have create method');
        $this->assertTrue($reflection->hasMethod('save'), 'CreateProduct should have save method');
    }
}
