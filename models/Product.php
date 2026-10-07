<?php

declare(strict_types=1);

namespace app\models;

use app\components\Money;
use app\models\query\ProductQuery;
use app\models\traits\Sluggable;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * A sellable item. stock is the on-hand quantity and is changed only by OrderService.
 *
 * @property int $id
 * @property int $category_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string $price
 * @property string $sku
 * @property int $stock
 * @property string $status
 * @property int $created_at
 * @property int $updated_at
 * @property-read Category $category
 * @property-read OrderItem[] $orderItems
 */
class Product extends ActiveRecord
{
    use Sluggable;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    public static function tableName(): string
    {
        return '{{%product}}';
    }

    public static function find(): ProductQuery
    {
        return new ProductQuery(static::class);
    }

    public function behaviors(): array
    {
        return [TimestampBehavior::class];
    }

    public function rules(): array
    {
        return [
            [['category_id', 'name', 'price', 'sku', 'stock'], 'required'],
            [['category_id', 'stock'], 'integer'],
            [['stock'], 'integer', 'min' => 0],
            [['name', 'slug'], 'string', 'max' => 255],
            [['sku'], 'string', 'max' => 64],
            [['name', 'slug', 'sku', 'description'], 'trim'],
            [['description'], 'default', 'value' => null],
            [['description'], 'string'],
            [['price'], 'number', 'min' => 0.01],
            [['sku', 'slug'], 'unique'],
            [
                ['category_id'],
                'exist',
                'targetClass' => Category::class,
                'targetAttribute' => 'id',
            ],
            [['status'], 'required'],
            [['status'], 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE]],
            [['status'], 'default', 'value' => self::STATUS_ACTIVE],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'category_id' => 'Category',
            'name' => 'Name',
            'slug' => 'Slug',
            'description' => 'Description',
            'price' => 'Price',
            'sku' => 'SKU',
            'stock' => 'Stock',
            'status' => 'Status',
            'created_at' => 'Created',
            'updated_at' => 'Updated',
        ];
    }

    public function fields(): array
    {
        return [
            'id',
            'category_id',
            'name',
            'slug',
            'description',
            'price' => static fn (self $product): string => Money::normalize($product->price),
            'sku',
            'stock' => static fn (self $product): int => (int) $product->stock,
            'status',
            'created_at' => static fn (self $product): string => gmdate('c', (int) $product->created_at),
            'updated_at' => static fn (self $product): string => gmdate('c', (int) $product->updated_at),
        ];
    }

    public function extraFields(): array
    {
        return ['category'];
    }

    public function beforeValidate(): bool
    {
        $this->sku = strtoupper(trim((string) $this->sku));
        $this->normalizeSlug();

        return parent::beforeValidate();
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_INACTIVE => 'Inactive',
        ];
    }

    /**
     * Each product belongs to one category.
     * The relation lets lists and the API show the category without a hand-written join.
     */
    public function getCategory(): ActiveQuery
    {
        return $this->hasOne(Category::class, ['id' => 'category_id']);
    }

    /**
     * A product can appear on many order lines.
     * The relation is how we detect sales history and refuse a hard delete.
     */
    public function getOrderItems(): ActiveQuery
    {
        return $this->hasMany(OrderItem::class, ['product_id' => 'id']);
    }
}
