<?php

namespace Tests\Feature\Api;

use App\Models\Product;
use App\Models\ProductTemplate;
use App\Models\Warehouse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EditInTransitProductFieldsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Warehouse $warehouse;
    protected ProductTemplate $template;

    protected function setUp(): void
    {
        parent::setUp();

        $this->warehouse = Warehouse::factory()->create();
        $this->user = User::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'role' => 'manager',
        ]);
        $this->template = ProductTemplate::factory()->create();
    }

    public function test_can_update_in_transit_product_with_shipping_location(): void
    {
        $product = Product::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'product_template_id' => $this->template->id,
            'status' => Product::STATUS_IN_TRANSIT,
            'shipping_location' => 'Москва',
        ]);

        $response = $this->actingAs($this->user)->putJson("/api/products/{$product->id}", [
            'shipping_location' => 'Санкт-Петербург',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'shipping_location' => 'Санкт-Петербург',
        ]);
    }

    public function test_can_update_in_transit_product_with_shipping_date(): void
    {
        $product = Product::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'product_template_id' => $this->template->id,
            'status' => Product::STATUS_IN_TRANSIT,
        ]);

        $response = $this->actingAs($this->user)->putJson("/api/products/{$product->id}", [
            'shipping_date' => '2025-10-20',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'shipping_date' => '2025-10-20',
        ]);
    }

    public function test_can_update_in_transit_product_with_expected_arrival_date(): void
    {
        $product = Product::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'product_template_id' => $this->template->id,
            'status' => Product::STATUS_IN_TRANSIT,
        ]);

        $response = $this->actingAs($this->user)->putJson("/api/products/{$product->id}", [
            'expected_arrival_date' => '2025-10-25',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'expected_arrival_date' => '2025-10-25',
        ]);
    }

    public function test_can_update_all_shipping_fields_together(): void
    {
        $product = Product::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'product_template_id' => $this->template->id,
            'status' => Product::STATUS_IN_TRANSIT,
        ]);

        $response = $this->actingAs($this->user)->putJson("/api/products/{$product->id}", [
            'shipping_location' => 'Санкт-Петербург',
            'shipping_date' => '2025-10-20',
            'expected_arrival_date' => '2025-10-25',
            'transport_number' => 'А123БВ77',
            'notes' => 'Обновление информации о доставке',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('product.shipping_location', 'Санкт-Петербург')
            ->assertJsonPath('product.shipping_date', '2025-10-25') // Дата в формате Y-m-d
            ->assertJsonPath('product.expected_arrival_date', '2025-10-25')
            ->assertJsonPath('product.transport_number', 'А123БВ77')
            ->assertJsonPath('product.notes', 'Обновление информации о доставке');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'shipping_location' => 'Санкт-Петербург',
            'transport_number' => 'А123БВ77',
            'notes' => 'Обновление информации о доставке',
        ]);
    }

    public function test_shipping_date_must_be_valid_date(): void
    {
        $product = Product::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'product_template_id' => $this->template->id,
            'status' => Product::STATUS_IN_TRANSIT,
        ]);

        $response = $this->actingAs($this->user)->putJson("/api/products/{$product->id}", [
            'shipping_date' => 'invalid-date',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('shipping_date');
    }

    public function test_shipping_location_respects_max_length(): void
    {
        $product = Product::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'product_template_id' => $this->template->id,
            'status' => Product::STATUS_IN_TRANSIT,
        ]);

        $tooLongLocation = str_repeat('A', 256);

        $response = $this->actingAs($this->user)->putJson("/api/products/{$product->id}", [
            'shipping_location' => $tooLongLocation,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('shipping_location');
    }

    public function test_can_clear_shipping_fields_by_sending_null(): void
    {
        $product = Product::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'product_template_id' => $this->template->id,
            'status' => Product::STATUS_IN_TRANSIT,
            'shipping_location' => 'Москва',
            'shipping_date' => '2025-10-20',
            'expected_arrival_date' => '2025-10-25',
        ]);

        $response = $this->actingAs($this->user)->putJson("/api/products/{$product->id}", [
            'shipping_location' => null,
            'shipping_date' => null,
            'expected_arrival_date' => null,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'shipping_location' => null,
            'shipping_date' => null,
            'expected_arrival_date' => null,
        ]);
    }
}
