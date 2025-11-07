<?php

namespace Tests\Feature\Api;

use App\Models\Product;
use App\Models\User;
use App\Models\UserSectionView;
use App\Models\Warehouse;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SectionViewTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_receipts_new_count_respects_section_timestamp(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create([
            'role' => UserRole::WAREHOUSE_WORKER,
            'warehouse_id' => $warehouse->id,
        ]);

        $viewedAt = now()->subHour();
        $user->markSectionViewed(UserSectionView::SECTION_RECEIPTS, $viewedAt);

        Product::factory()->inTransit()->active()->create([
            'warehouse_id' => $warehouse->id,
            'created_by' => $user->id,
            'created_at' => now()->subHours(2),
            'updated_at' => now()->subHours(2),
        ]);

        Product::factory()->inTransit()->active()->create([
            'warehouse_id' => $warehouse->id,
            'created_by' => $user->id,
            'created_at' => now()->subMinutes(10),
            'updated_at' => now()->subMinutes(10),
        ]);

        $this->actingAs($user);

        $response = $this->getJson('/api/receipts/new-count')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(1, $response->json('data.new_count'));
        $this->assertSame($viewedAt->toIso8601String(), $response->json('data.last_viewed_at'));
    }

    public function test_products_in_transit_new_count_uses_own_section_timestamp(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create([
            'role' => UserRole::WAREHOUSE_WORKER,
            'warehouse_id' => $warehouse->id,
        ]);

        $viewedAt = now()->subMinutes(45);
        $user->markSectionViewed(UserSectionView::SECTION_PRODUCTS_IN_TRANSIT, $viewedAt);

        Product::factory()->inTransit()->active()->create([
            'warehouse_id' => $warehouse->id,
            'created_by' => $user->id,
            'created_at' => now()->subHours(3),
            'updated_at' => now()->subHours(3),
        ]);

        Product::factory()->inTransit()->active()->create([
            'warehouse_id' => $warehouse->id,
            'created_by' => $user->id,
            'created_at' => now()->subMinutes(5),
            'updated_at' => now()->subMinutes(5),
        ]);

        $this->actingAs($user);

        $response = $this->getJson('/api/products-in-transit/new-count')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(1, $response->json('data.new_count'));
        $this->assertSame($viewedAt->toIso8601String(), $response->json('data.last_viewed_at'));
    }

    public function test_mark_section_viewed_endpoint_updates_timestamp(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $viewedAt = now();

        $this->actingAs($user);

        $response = $this->postJson('/api/app/sections/viewed', [
            'section' => UserSectionView::SECTION_RECEIPTS,
            'viewed_at' => $viewedAt->toIso8601String(),
        ])->assertOk();

        $response->assertJsonPath('success', true);
        $this->assertSame(
            $viewedAt->toIso8601String(),
            $response->json('data.last_viewed_at')
        );

        $this->assertDatabaseHas('user_section_views', [
            'user_id' => $user->id,
            'section' => UserSectionView::SECTION_RECEIPTS,
        ]);

        $this->assertSame(
            $viewedAt->toIso8601String(),
            $user->fresh()
                ->getSectionLastViewedAt(UserSectionView::SECTION_RECEIPTS)
                ?->toIso8601String()
        );
    }
}
