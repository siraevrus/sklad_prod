<?php

namespace Tests\Unit;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductRevisedTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_can_be_marked_as_revised(): void
    {
        $product = Product::factory()->create([
            'correction' => 'Тестовое уточнение',
            'correction_status' => 'correction',
        ]);

        $result = $product->markAsRevised();

        $this->assertTrue($result);
        $this->assertTrue($product->isRevised());
        $this->assertEquals('revised', $product->correction_status);
        $this->assertNotNull($product->revised_at);
    }

    public function test_is_revised_returns_true_for_revised_status(): void
    {
        $product = Product::factory()->create([
            'correction_status' => 'revised',
        ]);

        $this->assertTrue($product->isRevised());
    }

    public function test_is_revised_returns_false_for_other_statuses(): void
    {
        $product = Product::factory()->create([
            'correction_status' => 'correction',
        ]);

        $this->assertFalse($product->isRevised());

        $product = Product::factory()->create([
            'correction_status' => null,
        ]);

        $this->assertFalse($product->isRevised());
    }

    public function test_has_correction_or_revised_returns_true_for_correction(): void
    {
        $product = Product::factory()->create([
            'correction' => 'Тестовое уточнение',
            'correction_status' => 'correction',
        ]);

        $this->assertTrue($product->hasCorrectionOrRevised());
    }

    public function test_has_correction_or_revised_returns_true_for_revised(): void
    {
        $product = Product::factory()->create([
            'correction_status' => 'revised',
        ]);

        $this->assertTrue($product->hasCorrectionOrRevised());
    }

    public function test_has_correction_or_revised_returns_false_for_normal_product(): void
    {
        $product = Product::factory()->create([
            'correction_status' => null,
        ]);

        $this->assertFalse($product->hasCorrectionOrRevised());
    }
}
