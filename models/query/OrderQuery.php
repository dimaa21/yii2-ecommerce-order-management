<?php

declare(strict_types=1);

namespace app\models\query;

use app\models\Order;
use yii\db\ActiveQuery;

class OrderQuery extends ActiveQuery
{
    public function completed(): self
    {
        return $this->andWhere(['status' => Order::STATUS_COMPLETED]);
    }

    public function paid(): self
    {
        return $this->andWhere(['payment_status' => Order::PAYMENT_PAID])
            ->andWhere(['<>', 'status', Order::STATUS_CANCELLED]);
    }

    public function pendingPayment(): self
    {
        return $this->andWhere(['payment_status' => Order::PAYMENT_PENDING])
            ->andWhere(['<>', 'status', Order::STATUS_CANCELLED]);
    }

    public function createdBetween(int $from, int $to): self
    {
        return $this->andWhere(['>=', 'created_at', $from])->andWhere(['<', 'created_at', $to]);
    }
}
