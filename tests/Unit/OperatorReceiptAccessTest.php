<?php

namespace Tests\Unit;

use App\Filament\Resources\ReceiptResource;
use App\Models\Product;
use App\Models\User;
use App\UserRole;
use PHPUnit\Framework\TestCase;

class OperatorReceiptAccessTest extends TestCase
{
    /**
     * Тест: Оператор может просматривать Поступление товара
     */
    public function test_operator_can_view_receipt_resource(): void
    {
        // Проверяем что Operator имеет разрешение на просмотр receipt ресурса
        $permissions = UserRole::OPERATOR->permissions();
        $this->assertContains('product_receipt', $permissions);
    }

    /**
     * Тест: Оператор может редактировать карточку товара в Поступлении
     */
    public function test_operator_can_edit_receipt_product(): void
    {
        // Создаем тестовое изделие
        $product = new Product;
        $product->id = 1;
        $product->warehouse_id = 1;
        $product->name = 'Test Product';

        // Устанавливаем тестового оператора
        \Illuminate\Support\Facades\Auth::shouldReceive('user')
            ->andReturn(new User(['role' => UserRole::OPERATOR]));

        // Проверяем что Operator может редактировать
        $this->assertTrue(ReceiptResource::canEdit($product));
    }

    /**
     * Тест: Проверяем что Operator имеет разрешение 'product_receipt' в permissions
     */
    public function test_operator_has_product_receipt_permission(): void
    {
        $permissions = UserRole::OPERATOR->permissions();

        $this->assertContains('product_receipt', $permissions);
        $this->assertContains('products', $permissions);
        $this->assertContains('inventory', $permissions);
        $this->assertContains('products_in_transit', $permissions);
    }

    /**
     * Тест: Admin может редактировать товар в Поступлении
     */
    public function test_admin_can_edit_receipt_product(): void
    {
        $product = new Product;
        $product->id = 1;
        $product->warehouse_id = 1;

        \Illuminate\Support\Facades\Auth::shouldReceive('user')
            ->andReturn(new User(['role' => UserRole::ADMIN]));

        $this->assertTrue(ReceiptResource::canEdit($product));
    }

    /**
     * Тест: Работник склада может редактировать товар в Поступлении
     */
    public function test_warehouse_worker_can_edit_receipt_product(): void
    {
        $product = new Product;
        $product->id = 1;
        $product->warehouse_id = 1;

        \Illuminate\Support\Facades\Auth::shouldReceive('user')
            ->andReturn(new User(['role' => UserRole::WAREHOUSE_WORKER]));

        $this->assertTrue(ReceiptResource::canEdit($product));
    }
}
