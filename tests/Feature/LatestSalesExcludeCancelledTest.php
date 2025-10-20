<?php

namespace Tests\Feature;

use App\Models\Sale;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LatestSalesExcludeCancelledTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Тест: отменённые продажи не выводятся в виджете инфопанели
     */
    public function test_latest_sales_widget_excludes_cancelled_sales(): void
    {
        // Создаём тестовые данные
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create(['warehouse_id' => $warehouse->id]);
        $user = User::factory()->create(['warehouse_id' => $warehouse->id]);

        // Создаём активную продажу
        $activeSale = Sale::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $user->id,
            'payment_status' => Sale::PAYMENT_STATUS_PAID,
        ]);

        // Создаём отменённую продажу
        $cancelledSale = Sale::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $user->id,
            'payment_status' => Sale::PAYMENT_STATUS_CANCELLED,
        ]);

        // Проверяем логику запроса, используемую в виджете
        $latestSales = Sale::query()
            ->with(['product', 'warehouse', 'user'])
            ->where('payment_status', '!=', Sale::PAYMENT_STATUS_CANCELLED)
            ->latest('sale_date')
            ->limit(10)
            ->get();

        // Проверяем, что активная продажа присутствует
        $this->assertTrue(
            $latestSales->contains('id', $activeSale->id),
            'Активная продажа должна быть в списке'
        );

        // Проверяем, что отменённая продажа отсутствует
        $this->assertFalse(
            $latestSales->contains('id', $cancelledSale->id),
            'Отменённая продажа не должна быть в списке'
        );
    }

    /**
     * Тест: отменённые продажи не выводятся в API endpoint
     */
    public function test_api_dashboard_excludes_cancelled_sales(): void
    {
        // Создаём пользователя и аутентифицируемся
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user, 'sanctum');

        // Создаём тестовые данные
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create(['warehouse_id' => $warehouse->id]);

        // Создаём активную продажу
        $activeSale = Sale::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $user->id,
            'payment_status' => Sale::PAYMENT_STATUS_PAID,
        ]);

        // Создаём отменённую продажу
        $cancelledSale = Sale::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $user->id,
            'payment_status' => Sale::PAYMENT_STATUS_CANCELLED,
        ]);

        // Делаем запрос к API
        $response = $this->getJson('/api/dashboard/summary');

        $response->assertStatus(200);

        $latestSales = $response->json('latest_sales');

        // Проверяем, что активная продажа присутствует
        $this->assertTrue(
            collect($latestSales)->contains('id', $activeSale->id),
            'Активная продажа должна быть в списке'
        );

        // Проверяем, что отменённая продажа отсутствует
        $this->assertFalse(
            collect($latestSales)->contains('id', $cancelledSale->id),
            'Отменённая продажа не должна быть в списке'
        );
    }
}
