<?php

declare(strict_types=1);

namespace app\models;

use app\models\traits\Sluggable;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * A catalog group. Products belong to exactly one category.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string $status
 * @property int $created_at
 * @property int $updated_at
 * @property-read Product[] $products
 */
class Category extends ActiveRecord
{
    use Sluggable;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    public static function tableName(): string
    {
        return '{{%category}}';
    }

    public function behaviors(): array
    {
        return [TimestampBehavior::class];
    }

    public function rules(): array
    {
        return [
            [['name'], 'required'],
            [['name', 'slug'], 'string', 'max' => 255],
            [['name', 'slug', 'description'], 'trim'],
            [['description'], 'default', 'value' => null],
            [['description'], 'string'],
            [['slug'], 'unique'],
            [['status'], 'required'],
            [['status'], 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE]],
            [['status'], 'default', 'value' => self::STATUS_ACTIVE],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'slug' => 'Slug',
            'description' => 'Description',
            'status' => 'Status',
            'created_at' => 'Created',
            'updated_at' => 'Updated',
        ];
    }

    public function fields(): array
    {
        return [
            'id',
            'name',
            'slug',
            'description',
            'status',
            'created_at' => static fn (self $category): string => gmdate('c', (int) $category->created_at),
            'updated_at' => static fn (self $category): string => gmdate('c', (int) $category->updated_at),
        ];
    }

    public function extraFields(): array
    {
        return ['products'];
    }

    public function beforeValidate(): bool
    {
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
     * Inverse of Product::getCategory().
     * Used to list a category's products and to refuse deleting a category that still has them.
     */
    public function getProducts(): ActiveQuery
    {
        return $this->hasMany(Product::class, ['category_id' => 'id']);
    }
}
