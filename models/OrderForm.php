<?php

declare(strict_types=1);

namespace app\models;

use yii\base\Model;

/**
 * Validates the payload for a new order before OrderService runs.
 * Stock is intentionally not checked here: it must be checked inside the transaction.
 *
 * @property array<int, array<string, mixed>> $items
 */
class OrderForm extends Model
{
    public string|int|null $customer_id = null;

    /** @var array<int, array<string, mixed>> */
    public array $items = [];

    public function rules(): array
    {
        return [
            [['customer_id', 'items'], 'required'],
            [['customer_id'], 'integer'],
            [
                ['customer_id'],
                'exist',
                'targetClass' => Customer::class,
                'targetAttribute' => 'id',
                'message' => 'Customer not found.',
            ],
            [['items'], 'validateItems'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'customer_id' => 'Customer',
            'items' => 'Products',
        ];
    }

    public function validateItems(string $attribute): void
    {
        $lines = $this->lineItems();
        if ($lines === []) {
            $this->addError($attribute, 'Add at least one product.');
            return;
        }

        $seen = [];
        foreach ($lines as $index => $line) {
            $productId = $line['product_id'];
            $quantity = $line['quantity'];

            if ($productId <= 0) {
                $this->addError($attribute, 'Choose a product on line ' . ($index + 1) . '.');
                continue;
            }
            if ($quantity <= 0) {
                $this->addError($attribute, 'Quantity must be greater than 0.');
                continue;
            }
            if (isset($seen[$productId])) {
                $this->addError($attribute, 'Each product can only appear once. Combine the quantities.');
                continue;
            }
            $seen[$productId] = true;

            if (!Product::find()->where(['id' => $productId])->exists()) {
                $this->addError($attribute, 'Product not found.');
            }
        }
    }

    /**
     * Drops blank rows the form leaves in place.
     *
     * @return list<array{product_id: int, quantity: int}>
     */
    public function lineItems(): array
    {
        $lines = [];
        foreach ($this->items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $productId = (int) ($item['product_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);
            if ($productId === 0 && $quantity === 0) {
                continue;
            }
            $lines[] = [
                'product_id' => $productId,
                'quantity' => $quantity,
            ];
        }

        return $lines;
    }
}
