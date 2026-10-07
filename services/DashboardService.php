<?php

declare(strict_types=1);

namespace app\services;

use app\components\Money;
use app\models\Customer;
use app\models\Order;
use app\models\Product;
use yii\db\Query;

/**
 * Read model for the admin home page.
 * Aggregates go through the query builder; the low-stock list uses ActiveQuery.
 */
final class DashboardService
{
    /**
     * @return array{
     *     totalProducts: int,
     *     totalCustomers: int,
     *     totalOrders: int,
     *     completedOrders: int,
     *     pendingPayments: int,
     *     totalRevenue: string,
     *     ordersToday: int,
     *     ordersThisMonth: int,
     *     lowStockProducts: Product[]
     * }
     */
    public function summary(): array
    {
        $todayStart = strtotime('today');
        $tomorrow = strtotime('tomorrow');
        $monthStart = strtotime('first day of this month midnight');
        $nextMonth = strtotime('first day of next month midnight');

        $orders = static fn (): Query => (new Query())->from(['o' => Order::tableName()]);

        $revenue = $orders()
            ->where(['o.payment_status' => Order::PAYMENT_PAID])
            ->andWhere(['<>', 'o.status', Order::STATUS_CANCELLED])
            ->sum('o.total_amount');

        return [
            'totalProducts' => (int) Product::find()->count(),
            'totalCustomers' => (int) Customer::find()->count(),
            'totalOrders' => (int) $orders()->count(),
            'completedOrders' => (int) $orders()->where(['o.status' => Order::STATUS_COMPLETED])->count(),
            'pendingPayments' => (int) $orders()
                ->where(['o.payment_status' => Order::PAYMENT_PENDING])
                ->andWhere(['<>', 'o.status', Order::STATUS_CANCELLED])
                ->count(),
            'totalRevenue' => Money::normalize($revenue === null || $revenue === false || $revenue === '' ? '0' : $revenue),
            'ordersToday' => (int) $orders()
                ->where(['>=', 'o.created_at', $todayStart])
                ->andWhere(['<', 'o.created_at', $tomorrow])
                ->count(),
            'ordersThisMonth' => (int) $orders()
                ->where(['>=', 'o.created_at', $monthStart])
                ->andWhere(['<', 'o.created_at', $nextMonth])
                ->count(),
            'lowStockProducts' => Product::find()->lowStock()->with('category')->orderBy(['stock' => SORT_ASC, 'name' => SORT_ASC])->all(),
        ];
    }
}
