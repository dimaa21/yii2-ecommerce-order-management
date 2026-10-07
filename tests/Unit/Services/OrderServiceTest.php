<?php

declare(strict_types=1);

namespace app\tests\Unit\Services;

use app\components\Money;
use app\exceptions\EntityNotFoundException;
use app\exceptions\InsufficientStockException;
use app\exceptions\OrderStateException;
use app\exceptions\ValidationException;
use app\models\Category;
use app\models\Customer;
use app\models\Order;
use app\models\OrderItem;
use app\models\Product;
use app\services\OrderService;
use app\tests\Support\SchemaLoader;

final class OrderServiceTest extends \Codeception\Test\Unit
{
    private OrderService $service;

    protected function _before(): void
    {
        SchemaLoader::prepare();
        $this->service = new OrderService();
    }

    public function testCreateOrderSnapshotsPriceCalculatesTotalAndDecrementsStock(): void
    {
        $category = $this->category();
        $customer = $this->customer();
        $phone = $this->product($category, 'SKU-A', '19.99', 10);
        $case = $this->product($category, 'SKU-B', '5.50', 8);

        $order = $this->service->create((int) $customer->id, [
            ['product_id' => $phone->id, 'quantity' => 3],
            ['product_id' => $case->id, 'quantity' => 2],
        ]);

        $stored = Order::findOne($order->id);
        $this->assertNotNull($stored);
        $this->assertSame('70.97', Money::normalize($stored->total_amount));
        $this->assertSame(Order::STATUS_NEW, $stored->status);
        $this->assertSame(Order::PAYMENT_PENDING, $stored->payment_status);
        $this->assertCount(2, $stored->items);

        $phoneLine = OrderItem::findOne(['order_id' => $order->id, 'product_id' => $phone->id]);
        $this->assertNotNull($phoneLine);
        $this->assertSame('19.99', Money::normalize($phoneLine->price));
        $this->assertSame('59.97', Money::normalize($phoneLine->total));

        $phone->refresh();
        $phone->price = '1.00';
        $this->assertTrue($phone->save(false, ['price']));
        $phoneLine->refresh();
        $this->assertSame('19.99', Money::normalize($phoneLine->price));

        $this->assertSame(7, (int) Product::findOne($phone->id)->stock);
        $this->assertSame(6, (int) Product::findOne($case->id)->stock);
    }

    public function testInsufficientStockRollsBackTheOrderAndEarlierStockChanges(): void
    {
        $category = $this->category();
        $customer = $this->customer();
        $first = $this->product($category, 'SKU-A', '10.00', 10);
        $second = $this->product($category, 'SKU-B', '5.00', 1);

        try {
            $this->service->create((int) $customer->id, [
                ['product_id' => $first->id, 'quantity' => 2],
                ['product_id' => $second->id, 'quantity' => 2],
            ]);
            $this->fail('Expected insufficient stock.');
        } catch (InsufficientStockException $e) {
            $this->assertStringContainsString('SKU-B', $e->getMessage());
        }

        $this->assertSame(0, (int) Order::find()->count());
        $this->assertSame(0, (int) OrderItem::find()->count());
        $this->assertSame(10, (int) Product::findOne($first->id)->stock);
        $this->assertSame(1, (int) Product::findOne($second->id)->stock);
    }

    public function testMissingProductDoesNotCreateAnOrder(): void
    {
        $customer = $this->customer();

        $this->expectException(EntityNotFoundException::class);
        try {
            $this->service->create((int) $customer->id, [
                ['product_id' => 99999, 'quantity' => 1],
            ]);
        } finally {
            $this->assertSame(0, (int) Order::find()->count());
        }
    }

    public function testMissingCustomerDoesNotCreateAnOrder(): void
    {
        $category = $this->category();
        $product = $this->product($category, 'SKU-A', '10.00', 5);

        $this->expectException(EntityNotFoundException::class);
        try {
            $this->service->create(99999, [
                ['product_id' => $product->id, 'quantity' => 1],
            ]);
        } finally {
            $this->assertSame(0, (int) Order::find()->count());
            $this->assertSame(5, (int) Product::findOne($product->id)->stock);
        }
    }

    public function testInactiveProductIsRejected(): void
    {
        $category = $this->category();
        $customer = $this->customer();
        $product = $this->product($category, 'SKU-A', '10.00', 5, Product::STATUS_INACTIVE);

        $this->expectException(ValidationException::class);
        try {
            $this->service->create((int) $customer->id, [
                ['product_id' => $product->id, 'quantity' => 1],
            ]);
        } finally {
            $this->assertSame(0, (int) Order::find()->count());
            $this->assertSame(5, (int) Product::findOne($product->id)->stock);
        }
    }

    public function testDuplicateLinesAreMergedBeforeStockIsChecked(): void
    {
        $category = $this->category();
        $customer = $this->customer();
        $product = $this->product($category, 'SKU-A', '10.00', 5);

        $order = $this->service->create((int) $customer->id, [
            ['product_id' => $product->id, 'quantity' => 2],
            ['product_id' => $product->id, 'quantity' => 2],
        ]);

        $this->assertCount(1, $order->items);
        $this->assertSame(4, (int) $order->items[0]->quantity);
        $this->assertSame('40.00', Money::normalize($order->total_amount));
        $this->assertSame(1, (int) Product::findOne($product->id)->stock);
    }

    public function testCancelRestoresStockAndACancelledOrderCannotBeReopened(): void
    {
        $category = $this->category();
        $customer = $this->customer();
        $product = $this->product($category, 'SKU-A', '10.00', 5);

        $order = $this->service->create((int) $customer->id, [
            ['product_id' => $product->id, 'quantity' => 2],
        ]);
        $this->assertSame(3, (int) Product::findOne($product->id)->stock);

        $this->service->update((int) $order->id, Order::STATUS_CANCELLED, null);
        $this->assertSame(5, (int) Product::findOne($product->id)->stock);
        $this->assertSame(Order::STATUS_CANCELLED, Order::findOne($order->id)->status);

        $this->expectException(OrderStateException::class);
        $this->service->update((int) $order->id, Order::STATUS_PROCESSING, null);
    }

    private function category(): Category
    {
        $category = new Category();
        $category->name = 'Phones';
        $category->status = Category::STATUS_ACTIVE;
        $this->assertTrue($category->save(), json_encode($category->getErrors()));

        return $category;
    }

    private function customer(): Customer
    {
        $customer = new Customer();
        $customer->name = 'John Smith';
        $customer->email = 'john' . bin2hex(random_bytes(3)) . '@example.com';
        $this->assertTrue($customer->save(), json_encode($customer->getErrors()));

        return $customer;
    }

    private function product(
        Category $category,
        string $sku,
        string $price,
        int $stock,
        string $status = Product::STATUS_ACTIVE,
    ): Product {
        $product = new Product();
        $product->category_id = $category->id;
        $product->name = 'Item ' . $sku;
        $product->sku = $sku;
        $product->price = $price;
        $product->stock = $stock;
        $product->status = $status;
        $this->assertTrue($product->save(), json_encode($product->getErrors()));

        return $product;
    }
}
