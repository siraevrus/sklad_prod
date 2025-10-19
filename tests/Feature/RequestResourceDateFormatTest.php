<?php

use App\Filament\Resources\RequestResource;
use App\Models\Request;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

class RequestResourceDateFormatTest extends \Tests\TestCase
{
    use RefreshDatabase;

    public function test_displays_created_at_date_in_dd_mm_yyyy_format_in_requests_table()
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        $warehouse = Warehouse::factory()->create();

        $request = Request::factory()->create([
            'user_id' => $user->id,
            'warehouse_id' => $warehouse->id,
            'created_at' => '2025-01-15 10:30:00',
        ]);

        $this->actingAs($user);

        Livewire::test(RequestResource\Pages\ListRequests::class)
            ->assertSee('15.01.2025')
            ->assertDontSee('2025-01-15');
    }

    public function test_displays_shipping_date_in_dd_mm_yyyy_format_in_product_in_transit_table()
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        $product = \App\Models\Product::factory()->create([
            'status' => 'for_receipt',
            'shipping_date' => '2025-02-20 14:20:00',
        ]);

        $this->actingAs($user);

        Livewire::test(\App\Filament\Resources\ProductInTransitResource\Pages\ListProductInTransit::class)
            ->assertSee('20.02.2025')
            ->assertDontSee('2025-02-20');
    }

    public function test_displays_expected_arrival_date_in_dd_mm_yyyy_format_in_product_in_transit_table()
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        $product = \App\Models\Product::factory()->create([
            'status' => 'for_receipt',
            'expected_arrival_date' => '2025-03-25 09:15:00',
        ]);

        $this->actingAs($user);

        Livewire::test(\App\Filament\Resources\ProductInTransitResource\Pages\ListProductInTransit::class)
            ->assertSee('25.03.2025')
            ->assertDontSee('2025-03-25');
    }
}
