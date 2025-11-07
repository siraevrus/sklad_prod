<?php

namespace Tests\Feature\Api;

use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Models\Warehouse;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesNewCountTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_index_returns_new_count(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create([
            'role' => UserRole::WAREHOUSE_WORKER,
            'warehouse_id' => $warehouse->id,
            'last_app_opened_at' => now()->subHour(),
        ]);

        $product = Product::factory()->inStock()->create([
            'warehouse_id' => $warehouse->id,
            'created_by' => $user->id,
        ]);

        Sale::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $user->id,
            'created_at' => now()->subHours(2),
            'updated_at' => now()->subHours(2),
        ]);

        Sale::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $user->id,
            'created_at' => now()->subMinutes(10),
            'updated_at' => now()->subMinutes(10),
        ]);

        $this->actingAs($user);

        $response = $this->getJson('/api/sales')->assertOk();

        $this->assertSame(1, $response->json('new_count'));
        $this->assertSame(
            $user->last_app_opened_at?->toIso8601String(),
            $response->json('last_app_opened_at')
        );
    }

    public function test_sales_new_count_endpoint_respects_last_app_opened_time(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create([
            'role' => UserRole::WAREHOUSE_WORKER,
            'warehouse_id' => $warehouse->id,
            'last_app_opened_at' => now()->subMinutes(30),
        ]);

        $product = Product::factory()->inStock()->create([
            'warehouse_id' => $warehouse->id,
            'created_by' => $user->id,
        ]);

        Sale::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $user->id,
            'created_at' => now()->subHours(1),
            'updated_at' => now()->subHours(1),
        ]);

        Sale::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $user->id,
            'created_at' => now()->subMinutes(5),
            'updated_at' => now()->subMinutes(5),
        ]);

        $this->actingAs($user);

        $response = $this->getJson('/api/sales/new-count')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(1, $response->json('data.new_count'));
        $this->assertSame(
            $user->last_app_opened_at?->toIso8601String(),
            $response->json('data.last_app_opened_at')
        );
    }
}
