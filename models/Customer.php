<?php

declare(strict_types=1);

namespace app\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * A buyer. Not a login account — staff users live in the user table.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property int $created_at
 * @property int $updated_at
 * @property-read Order[] $orders
 */
class Customer extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%customer}}';
    }

    public function behaviors(): array
    {
        return [TimestampBehavior::class];
    }

    public function rules(): array
    {
        return [
            [['name', 'email'], 'required'],
            [['name', 'email', 'phone'], 'trim'],
            [['name', 'email'], 'string', 'max' => 255],
            [['phone'], 'default', 'value' => null],
            [['phone'], 'string', 'max' => 32],
            [['phone'], 'match', 'pattern' => '/^[0-9+\-\s()]{3,32}$/', 'skipOnEmpty' => true],
            [['email'], 'email'],
            [['email'], 'unique'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'email' => 'Email',
            'phone' => 'Phone',
            'created_at' => 'Created',
            'updated_at' => 'Updated',
        ];
    }

    public function fields(): array
    {
        return [
            'id',
            'name',
            'email',
            'phone',
            'created_at' => static fn (self $customer): string => gmdate('c', (int) $customer->created_at),
            'updated_at' => static fn (self $customer): string => gmdate('c', (int) $customer->updated_at),
        ];
    }

    public function extraFields(): array
    {
        return ['orders'];
    }

    public function beforeValidate(): bool
    {
        $this->email = strtolower(trim((string) $this->email));

        return parent::beforeValidate();
    }

    /**
     * A customer places many orders.
     * Used on the customer page and to block deletion while history exists.
     */
    public function getOrders(): ActiveQuery
    {
        return $this->hasMany(Order::class, ['customer_id' => 'id']);
    }
}
