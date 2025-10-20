<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrencyDisplayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Тест: API возвращает правильный тип валюты для последних продаж
     */
    public function test_api_dashboard_includes_currency_for_latest_sales(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user, 'sanctum');

        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create(['warehouse_id' => $warehouse->id]);

        // Создаём продажу с USD
        $saleUsd = Sale::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $user->id,
            'payment_status' => Sale::PAYMENT_STATUS_PAID,
            'currency' => 'USD',
            'total_price' => 100.50,
        ]);

        // Создаём продажу с RUB
        $saleRub = Sale::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $user->id,
            'payment_status' => Sale::PAYMENT_STATUS_PAID,
            'currency' => 'RUB',
            'total_price' => 5000.00,
        ]);

        // Создаём продажу с UZS
        $saleUzs = Sale::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $user->id,
            'payment_status' => Sale::PAYMENT_STATUS_PAID,
            'currency' => 'UZS',
            'total_price' => 12500.00,
        ]);

        $response = $this->getJson('/api/dashboard/summary');

        $response->assertStatus(200);

        $latestSales = $response->json('latest_sales');

        // Проверяем, что USD валюта присутствует
        $usdSale = collect($latestSales)->firstWhere('id', $saleUsd->id);
        $this->assertNotNull($usdSale);
        $this->assertEquals('USD', $usdSale['currency']);
        $this->assertEquals(100.50, $usdSale['total_amount']);

        // Проверяем, что RUB валюта присутствует
        $rubSale = collect($latestSales)->firstWhere('id', $saleRub->id);
        $this->assertNotNull($rubSale);
        $this->assertEquals('RUB', $rubSale['currency']);
        $this->assertEquals(5000.00, $rubSale['total_amount']);

        // Проверяем, что UZS валюта присутствует
        $uzsSale = collect($latestSales)->firstWhere('id', $saleUzs->id);
        $this->assertNotNull($uzsSale);
        $this->assertEquals('UZS', $uzsSale['currency']);
        $this->assertEquals(12500.00, $uzsSale['total_amount']);
    }

    /**
     * Тест: виджет инфопанели показывает правильные валюты
     */
    public function test_latest_sales_widget_displays_currencies(): void
    {
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create(['warehouse_id' => $warehouse->id]);
        $user = User::factory()->create(['warehouse_id' => $warehouse->id]);

        // Создаём продажи с разными валютами
        $saleUsd = Sale::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $user->id,
            'payment_status' => Sale::PAYMENT_STATUS_PAID,
            'currency' => 'USD',
            'total_price' => 100.00,
        ]);

        $saleRub = Sale::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $user->id,
            'payment_status' => Sale::PAYMENT_STATUS_PAID,
            'currency' => 'RUB',
            'total_price' => 1000.00,
        ]);

        $saleUzs = Sale::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $user->id,
            'payment_status' => Sale::PAYMENT_STATUS_PAID,
            'currency' => 'UZS',
            'total_price' => 5000.00,
        ]);

        // Получаем продажи как в виджете
        $sales = Sale::query()
            ->with(['product', 'warehouse', 'user'])
            ->where('payment_status', '!=', Sale::PAYMENT_STATUS_CANCELLED)
            ->latest('sale_date')
            ->limit(10)
            ->get();

        // Проверяем USD
        $usdSale = $sales->find($saleUsd->id);
        $this->assertEquals('USD', $usdSale->currency);

        // Проверяем RUB
        $rubSale = $sales->find($saleRub->id);
        $this->assertEquals('RUB', $rubSale->currency);

        // Проверяем UZS
        $uzsSale = $sales->find($saleUzs->id);
        $this->assertEquals('UZS', $uzsSale->currency);
    }
}
