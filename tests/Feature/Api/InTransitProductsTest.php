<?php

namespace Tests\Feature\Api;

use App\Models\ProductTemplate;
use App\Models\User;
use App\Models\Warehouse;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InTransitProductsTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_in_transit_product_and_list_in_receipts(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create([
            'role' => UserRole::WAREHOUSE_WORKER,
            'warehouse_id' => $warehouse->id,
        ]);
        $this->actingAs($user);

        $template = ProductTemplate::factory()->create([
            'name' => 'Доска',
        ]);

        $payload = [
            'product_template_id' => $template->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 5,
            'status' => 'in_transit',
            'attributes' => [
                'length' => 2,
                'width' => 1,
            ],
            'shipping_location' => 'Центральный склад',
            'shipping_date' => now()->toDateString(),
        ];

        $res = $this->postJson('/api/products', $payload);
        $res->assertCreated();

        $response = $this->getJson('/api/receipts')
            ->assertOk()
            ->assertJsonPath('success', true);

        $items = $response->json('data');
        $this->assertIsArray($items);
        $this->assertNotEmpty($items);
    }
}
