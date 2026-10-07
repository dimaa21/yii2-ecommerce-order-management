<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var int $totalProducts */
/** @var int $totalCustomers */
/** @var int $totalOrders */
/** @var int $completedOrders */
/** @var int $pendingPayments */
/** @var string $totalRevenue */
/** @var int $ordersToday */
/** @var int $ordersThisMonth */
/** @var app\models\Product[] $lowStockProducts */

use yii\helpers\Html;

$this->title = 'Dashboard';

$metrics = [
    ['Total products', (string) $totalProducts],
    ['Total customers', (string) $totalCustomers],
    ['Total orders', (string) $totalOrders],
    ['Completed orders', (string) $completedOrders],
    ['Pending payments', (string) $pendingPayments],
    ['Total revenue', Yii::$app->formatter->asCurrency($totalRevenue)],
    ['Orders today', (string) $ordersToday],
    ['Orders this month', (string) $ordersThisMonth],
];
?>
<?= $this->render('//_page', [
    'title' => 'Dashboard',
    'subtitle' => 'Live counts from the database.',
]) ?>

<div class="row g-3 mb-4">
    <?php foreach ($metrics as [$label, $value]): ?>
        <div class="col-6 col-lg-3">
            <div class="oms-card p-3 h-100">
                <div class="metric-label mb-2"><?= Html::encode($label) ?></div>
                <div class="metric-value"><?= Html::encode($value) ?></div>
            </div>
        </div>
    <?php endforeach ?>
</div>

<div class="oms-card">
    <div class="p-3 border-bottom">
        <h2 class="h5 mb-1">Low stock</h2>
        <p class="text-secondary mb-0">Active products with stock of <?= (int) Yii::$app->params['lowStockThreshold'] ?> or less.</p>
    </div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
            <tr>
                <th>Product</th>
                <th>SKU</th>
                <th>Category</th>
                <th class="text-end">Stock</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($lowStockProducts === []): ?>
                <tr>
                    <td colspan="4" class="text-secondary">No products are low on stock.</td>
                </tr>
            <?php endif ?>
            <?php foreach ($lowStockProducts as $product): ?>
                <tr>
                    <td><?= Html::a(Html::encode($product->name), ['/product/view', 'id' => $product->id]) ?></td>
                    <td><?= Html::encode($product->sku) ?></td>
                    <td><?= Html::encode($product->category->name ?? '') ?></td>
                    <td class="text-end fw-semibold"><?= (int) $product->stock ?></td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>
