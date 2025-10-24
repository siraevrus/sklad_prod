<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceiptExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_export_receipts(): void
    {
        $admin = User::factory()->admin()->create();
        $warehouse = Warehouse::factory()->create();

        // Создаем товар для приемки
        Product::factory()
            ->forReceipt()
            ->create([
                'warehouse_id' => $warehouse->id,
                'name' => 'Тестовый товар для приемки',
            ]);

        $response = $this->actingAs($admin)
            ->get(route('receipts.export'));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=utf-8');
        $response->assertHeader('content-disposition');

        // Проверяем, что в ответе есть наш товар
        $content = $response->streamedContent();
        $this->assertStringContainsString('Тестовый товар для приемки', $content);
    }

    public function test_operator_can_export_receipts_from_their_warehouse(): void
    {
        $warehouse = Warehouse::factory()->create();
        $operator = User::factory()->operator()->create([
            'warehouse_id' => $warehouse->id,
        ]);

        // Создаем товар для приемки в складе оператора
        Product::factory()
            ->forReceipt()
            ->create([
                'warehouse_id' => $warehouse->id,
                'name' => 'Товар оператора',
            ]);

        $response = $this->actingAs($operator)
            ->get(route('receipts.export'));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=utf-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('Товар оператора', $content);
    }

    public function test_guest_cannot_export_receipts(): void
    {
        $response = $this->get(route('receipts.export'));

        $response->assertRedirect(route('filament.admin.auth.login'));
    }
}
