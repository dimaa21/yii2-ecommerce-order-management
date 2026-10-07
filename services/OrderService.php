<?php

declare(strict_types=1);

namespace app\services;

use app\components\Money;
use app\exceptions\EntityNotFoundException;
use app\exceptions\InsufficientStockException;
use app\exceptions\OrderStateException;
use app\exceptions\ValidationException;
use app\models\Customer;
use app\models\Order;
use app\models\OrderItem;
use app\models\Product;
use Throwable;
use Yii;
use yii\db\Connection;
use yii\db\Transaction;

/**
 * Owns the order transaction.
 *
 * The controller only validates the request and calls this class.
 * Everything that must succeed or fail together — the order, its lines,
 * the total, and the stock change — happens inside one database transaction.
 *
 * The unit price is copied onto the order line. A later catalog price change
 * does not rewrite the sale.
 */
final class OrderService
{
    /**
     * @param list<array{product_id?: int|string, quantity?: int|string}> $items
     */
    public function create(int $customerId, array $items): Order
    {
        return $this->transaction(function () use ($customerId, $items): Order {
            $lines = $this->normalizeItems($items);
            $customer = $this->requireCustomer($customerId);
            $products = $this->lockProducts(array_keys($lines));

            foreach ($products as $product) {
                if ($product->status !== Product::STATUS_ACTIVE) {
                    throw new ValidationException([
                        'items' => ["Product {$product->sku} is not available."],
                    ]);
                }
            }

            $order = new Order();
            $order->customer_id = $customer->id;
            $order->status = Order::STATUS_NEW;
            $order->payment_status = Order::PAYMENT_PENDING;
            $order->total_amount = '0.00';
            if (!$order->save()) {
                throw new ValidationException($order->getErrors());
            }

            $total = '0.00';
            foreach ($lines as $productId => $quantity) {
                $product = $products[$productId];
                $lineTotal = Money::mul($product->price, $quantity);

                $item = new OrderItem();
                $item->order_id = $order->id;
                $item->product_id = $product->id;
                $item->quantity = $quantity;
                $item->price = Money::normalize($product->price);
                $item->total = $lineTotal;
                if (!$item->save()) {
                    throw new ValidationException($item->getErrors());
                }

                $total = Money::add($total, $lineTotal);
            }

            if (bccomp($total, '0', 2) !== 1) {
                throw new ValidationException([
                    'items' => ['Order total must be greater than 0.'],
                ]);
            }

            $order->total_amount = $total;
            if (!$order->save(false, ['total_amount', 'updated_at'])) {
                throw new \RuntimeException('Failed to store the order total.');
            }

            foreach ($lines as $productId => $quantity) {
                $this->decrementStock($products[$productId], $quantity);
            }

            $order->populateRelation('customer', $customer);
            $order->populateRelation('items', $order->getItems()->with('product')->all());

            return $order;
        });
    }

    public function update(int $orderId, ?string $status, ?string $paymentStatus): Order
    {
        $status = $this->blankToNull($status);
        $paymentStatus = $this->blankToNull($paymentStatus);

        if ($status === null && $paymentStatus === null) {
            throw new ValidationException([
                'status' => ['Provide a status or a payment status.'],
            ]);
        }

        return $this->transaction(function () use ($orderId, $status, $paymentStatus): Order {
            $order = $this->lockOrder($orderId);

            if ($status !== null && $status !== $order->status) {
                $this->applyStatus($order, $status);
            }
            if ($paymentStatus !== null && $paymentStatus !== $order->payment_status) {
                $this->applyPaymentStatus($order, $paymentStatus);
            }

            if (!$order->save()) {
                throw new ValidationException($order->getErrors());
            }

            $order->populateRelation('customer', $order->customer);
            $order->populateRelation('items', $order->getItems()->with('product')->all());

            return $order;
        });
    }

    /**
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    private function transaction(callable $callback): mixed
    {
        $transaction = $this->db()->beginTransaction();
        try {
            $result = $callback();
            $transaction->commit();

            return $result;
        } catch (Throwable $e) {
            $this->rollBack($transaction);
            throw $e;
        }
    }

    private function rollBack(Transaction $transaction): void
    {
        if ($transaction->isActive) {
            $transaction->rollBack();
        }
    }

    /**
     * @param list<array{product_id?: int|string, quantity?: int|string}> $items
     * @return array<int, int> product id => quantity
     */
    private function normalizeItems(array $items): array
    {
        if ($items === []) {
            throw new ValidationException([
                'items' => ['At least one product is required.'],
            ]);
        }

        $merged = [];
        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);

            if ($productId <= 0) {
                throw new EntityNotFoundException('Product not found.');
            }
            if ($quantity <= 0) {
                throw new ValidationException([
                    'items' => ['Quantity must be greater than 0.'],
                ]);
            }

            $merged[$productId] = ($merged[$productId] ?? 0) + $quantity;
        }

        return $merged;
    }

    private function requireCustomer(int $customerId): Customer
    {
        $customer = Customer::findOne($customerId);
        if ($customer === null) {
            throw new EntityNotFoundException('Customer not found.');
        }

        return $customer;
    }

    /**
     * Locks product rows in id order so two checkouts cannot deadlock,
     * then reads the stock this transaction is allowed to sell.
     *
     * @param list<int> $ids
     * @return array<int, Product>
     */
    private function lockProducts(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        sort($ids);

        $query = Product::find()->where(['id' => $ids])->orderBy(['id' => SORT_ASC]);
        $command = $query->createCommand();
        $sql = $command->sql;
        if ($this->db()->driverName === 'mysql') {
            $sql .= ' FOR UPDATE';
        }

        $rows = $this->db()->createCommand($sql, $command->params)->queryAll();
        if (count($rows) !== count($ids)) {
            throw new EntityNotFoundException('Product not found.');
        }

        $products = [];
        foreach ($rows as $row) {
            $product = Product::instantiate($row);
            Product::populateRecord($product, $row);
            $products[(int) $product->id] = $product;
        }

        return $products;
    }

    private function lockOrder(int $orderId): Order
    {
        $query = Order::find()->where(['id' => $orderId]);
        $command = $query->createCommand();
        $sql = $command->sql;
        if ($this->db()->driverName === 'mysql') {
            $sql .= ' FOR UPDATE';
        }

        $row = $this->db()->createCommand($sql, $command->params)->queryOne();
        if ($row === false) {
            throw new EntityNotFoundException('Order not found.');
        }

        $order = Order::instantiate($row);
        Order::populateRecord($order, $row);
        $order->populateRelation('items', $order->getItems()->all());

        return $order;
    }

    private function decrementStock(Product $product, int $quantity): void
    {
        $updated = Product::updateAllCounters(
            ['stock' => -$quantity],
            ['and', ['id' => $product->id], ['>=', 'stock', $quantity]],
        );

        if ($updated !== 1) {
            throw new InsufficientStockException(
                "Insufficient stock for {$product->name} ({$product->sku}). "
                . "Available: {$product->stock}, requested: {$quantity}.",
            );
        }
    }

    private function applyStatus(Order $order, string $status): void
    {
        if (!isset(Order::statusOptions()[$status])) {
            throw new ValidationException([
                'status' => ['Invalid order status.'],
            ]);
        }

        $allowed = Order::transitions()[$order->status] ?? [];
        if (!in_array($status, $allowed, true)) {
            throw new OrderStateException("Cannot change order status from {$order->status} to {$status}.");
        }

        if ($status === Order::STATUS_CANCELLED) {
            $this->restoreStock($order);
        }

        $order->status = $status;
    }

    private function applyPaymentStatus(Order $order, string $paymentStatus): void
    {
        if (!isset(Order::paymentStatusOptions()[$paymentStatus])) {
            throw new ValidationException([
                'payment_status' => ['Invalid payment status.'],
            ]);
        }

        $order->payment_status = $paymentStatus;
    }

    private function restoreStock(Order $order): void
    {
        $items = $order->items;
        if ($items === []) {
            return;
        }

        $ids = array_map(static fn (OrderItem $item): int => (int) $item->product_id, $items);
        $this->lockProducts($ids);

        foreach ($items as $item) {
            Product::updateAllCounters(
                ['stock' => (int) $item->quantity],
                ['id' => (int) $item->product_id],
            );
        }
    }

    private function blankToNull(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function db(): Connection
    {
        return Yii::$app->db;
    }
}
