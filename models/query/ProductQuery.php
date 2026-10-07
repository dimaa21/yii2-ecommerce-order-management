<?php

declare(strict_types=1);

namespace app\models\query;

use app\models\Product;
use Yii;
use yii\db\ActiveQuery;

class ProductQuery extends ActiveQuery
{
    public function active(): self
    {
        return $this->andWhere(['status' => Product::STATUS_ACTIVE]);
    }

    /**
     * Products that should be reordered. Inactive products are excluded:
     * they are not for sale, so a low number is not an operational alert.
     */
    public function lowStock(?int $threshold = null): self
    {
        $threshold ??= (int) (Yii::$app->params['lowStockThreshold'] ?? 5);

        return $this->active()->andWhere(['<=', 'stock', $threshold]);
    }
}
