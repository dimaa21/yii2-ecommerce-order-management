<?php

declare(strict_types=1);

namespace app\models;

use app\components\Money;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * One line of an order.
 *
 * price is copied from the product at purchase time. The catalog price can
 * change later without rewriting this line.
 *
 * @property int $id
 * @property int $order_id
 * @property int $product_id
 * @property int $quantity
 * @property string $price
 * @property string $total
 * @property-read Order $order
 * @property-read Product $product
 */
class OrderItem extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%order_item}}';
    }

    public function rules(): array
    {
        return [
            [['order_id', 'product_id', 'quantity', 'price', 'total'], 'required'],
            [['order_id', 'product_id', 'quantity'], 'integer'],
            [['quantity'], 'integer', 'min' => 1],
            [['price', 'total'], 'number', 'min' => 0.01],
            [
                ['order_id'],
                'exist',
                'targetClass' => Order::class,
                'targetAttribute' => 'id',
            ],
            [
                ['product_id'],
                'exist',
                'targetClass' => Product::class,
                'targetAttribute' => 'id',
            ],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'order_id' => 'Order',
            'product_id' => 'Product',
            'quantity' => 'Quantity',
            'price' => 'Unit price',
            'total' => 'Line total',
        ];
    }

    public function fields(): array
    {
        return [
            'id',
            'order_id',
            'product_id',
            'quantity',
            'price' => static fn (self $item): string => Money::normalize($item->price),
            'total' => static fn (self $item): string => Money::normalize($item->total),
            'product_name' => static fn (self $item): ?string => $item->product->name ?? null,
            'sku' => static fn (self $item): ?string => $item->product->sku ?? null,
        ];
    }

    /**
     * The order this line belongs to.
     */
    public function getOrder(): ActiveQuery
    {
        return $this->hasOne(Order::class, ['id' => 'order_id']);
    }

    /**
     * The catalog product. The line still stores its own price snapshot.
     */
    public function getProduct(): ActiveQuery
    {
        return $this->hasOne(Product::class, ['id' => 'product_id']);
    }
}
