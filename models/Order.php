<?php

declare(strict_types=1);

namespace app\models;

use app\components\Money;
use app\models\query\OrderQuery;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * A placed order. total_amount is the sum of its item snapshots, not a live catalog price.
 *
 * The physical table is `orders` (ORDER is reserved in SQL).
 *
 * @property int $id
 * @property int $customer_id
 * @property string $status
 * @property string $payment_status
 * @property string $total_amount
 * @property int $created_at
 * @property int $updated_at
 * @property-read Customer $customer
 * @property-read OrderItem[] $items
 */
class Order extends ActiveRecord
{
    public const STATUS_NEW = 'new';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const PAYMENT_PENDING = 'pending';
    public const PAYMENT_PAID = 'paid';
    public const PAYMENT_FAILED = 'failed';
    public const PAYMENT_REFUNDED = 'refunded';

    public static function tableName(): string
    {
        return '{{%orders}}';
    }

    public static function find(): OrderQuery
    {
        return new OrderQuery(static::class);
    }

    public function behaviors(): array
    {
        return [TimestampBehavior::class];
    }

    public function rules(): array
    {
        return [
            [['customer_id', 'status', 'payment_status', 'total_amount'], 'required'],
            [['customer_id'], 'integer'],
            [
                ['customer_id'],
                'exist',
                'targetClass' => Customer::class,
                'targetAttribute' => 'id',
            ],
            [['status'], 'in', 'range' => array_keys(self::statusOptions())],
            [['payment_status'], 'in', 'range' => array_keys(self::paymentStatusOptions())],
            [['total_amount'], 'number', 'min' => 0],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'Order',
            'customer_id' => 'Customer',
            'status' => 'Status',
            'payment_status' => 'Payment',
            'total_amount' => 'Total',
            'created_at' => 'Created',
            'updated_at' => 'Updated',
        ];
    }

    public function fields(): array
    {
        return [
            'id',
            'customer_id',
            'status',
            'payment_status',
            'total_amount' => static fn (self $order): string => Money::normalize($order->total_amount),
            'created_at' => static fn (self $order): string => gmdate('c', (int) $order->created_at),
            'updated_at' => static fn (self $order): string => gmdate('c', (int) $order->updated_at),
            'customer_name' => static fn (self $order): ?string => $order->customer->name ?? null,
            'item_count' => static fn (self $order): int => count($order->items),
        ];
    }

    public function extraFields(): array
    {
        return ['customer', 'items'];
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_NEW => 'New',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    public static function paymentStatusOptions(): array
    {
        return [
            self::PAYMENT_PENDING => 'Pending',
            self::PAYMENT_PAID => 'Paid',
            self::PAYMENT_FAILED => 'Failed',
            self::PAYMENT_REFUNDED => 'Refunded',
        ];
    }

    /**
     * Legal status changes. Cancelled is terminal.
     * Cancelling returns stock; that rule lives in OrderService, next to the transaction.
     *
     * @return array<string, list<string>>
     */
    public static function transitions(): array
    {
        return [
            self::STATUS_NEW => [self::STATUS_PROCESSING, self::STATUS_COMPLETED, self::STATUS_CANCELLED],
            self::STATUS_PROCESSING => [self::STATUS_COMPLETED, self::STATUS_CANCELLED],
            self::STATUS_COMPLETED => [self::STATUS_CANCELLED],
            self::STATUS_CANCELLED => [],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function statusChoices(): array
    {
        $options = [
            $this->status => self::statusOptions()[$this->status],
        ];
        foreach (self::transitions()[$this->status] ?? [] as $status) {
            $options[$status] = self::statusOptions()[$status];
        }

        return $options;
    }

    /**
     * Every order belongs to one customer.
     */
    public function getCustomer(): ActiveQuery
    {
        return $this->hasOne(Customer::class, ['id' => 'customer_id']);
    }

    /**
     * The lines that make up this order. The total is the sum of item totals.
     */
    public function getItems(): ActiveQuery
    {
        return $this->hasMany(OrderItem::class, ['order_id' => 'id']);
    }
}
