<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\Customer $model */
/** @var app\models\Order[] $orders */

use yii\helpers\Html;
use yii\widgets\DetailView;

$this->title = $model->name;
?>
<?= $this->render('//_page', [
    'title' => $model->name,
    'subtitle' => $model->email,
    'actions' => Html::a('Edit', ['update', 'id' => $model->id], ['class' => 'btn btn-dark'])
        . Html::a('Back', ['index'], ['class' => 'btn btn-outline-secondary']),
]) ?>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="oms-card p-3">
            <?= DetailView::widget([
                'model' => $model,
                'options' => ['class' => 'table mb-0'],
                'attributes' => [
                    'id',
                    'name',
                    'email:email',
                    'phone',
                    'created_at:datetime',
                ],
            ]) ?>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="oms-card p-3">
            <h2 class="h5">Orders</h2>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                    <tr>
                        <th>Order</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th class="text-end">Total</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($orders === []): ?>
                        <tr><td colspan="4" class="text-secondary">No orders yet.</td></tr>
                    <?php endif ?>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?= Html::a('#' . $order->id, ['/order/view', 'id' => $order->id]) ?></td>
                            <td><?= $this->render('//_status', ['status' => $order->status]) ?></td>
                            <td><?= $this->render('//_status', ['status' => $order->payment_status]) ?></td>
                            <td class="text-end"><?= Yii::$app->formatter->asCurrency($order->total_amount) ?></td>
                        </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
