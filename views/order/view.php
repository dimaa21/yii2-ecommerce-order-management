<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\Order $model */

use yii\helpers\Html;

$this->title = 'Order #' . $model->id;
?>
<?= $this->render('//_page', [
    'title' => 'Order #' . $model->id,
    'subtitle' => $model->customer->name ?? '',
    'actions' => Html::a('All orders', ['index'], ['class' => 'btn btn-outline-secondary']),
]) ?>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="oms-card p-3">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                    <tr>
                        <th>Product</th>
                        <th>SKU</th>
                        <th class="text-end">Qty</th>
                        <th class="text-end">Unit price</th>
                        <th class="text-end">Total</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($model->items as $item): ?>
                        <tr>
                            <td><?= Html::encode($item->product->name ?? 'Removed product') ?></td>
                            <td><?= Html::encode($item->product->sku ?? '') ?></td>
                            <td class="text-end"><?= (int) $item->quantity ?></td>
                            <td class="text-end"><?= Yii::$app->formatter->asCurrency($item->price) ?></td>
                            <td class="text-end"><?= Yii::$app->formatter->asCurrency($item->total) ?></td>
                        </tr>
                    <?php endforeach ?>
                    </tbody>
                    <tfoot>
                    <tr>
                        <th colspan="4" class="text-end">Order total</th>
                        <th class="text-end"><?= Yii::$app->formatter->asCurrency($model->total_amount) ?></th>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="oms-card p-3 mb-3">
            <h2 class="h6 text-secondary">Status</h2>
            <p><?= $this->render('//_status', ['status' => $model->status]) ?></p>
            <?= Html::beginForm(['update', 'id' => $model->id], 'post') ?>
                <label class="form-label" for="order-status">Change status</label>
                <?= Html::dropDownList('status', $model->status, $model->statusChoices(), ['class' => 'form-select mb-2', 'id' => 'order-status']) ?>
                <?= Html::submitButton('Update status', ['class' => 'btn btn-dark btn-sm']) ?>
            <?= Html::endForm() ?>
            <p class="small text-secondary mt-3 mb-0">Cancelling an order returns the reserved stock.</p>
        </div>
        <div class="oms-card p-3 mb-3">
            <h2 class="h6 text-secondary">Payment</h2>
            <p><?= $this->render('//_status', ['status' => $model->payment_status]) ?></p>
            <?= Html::beginForm(['update', 'id' => $model->id], 'post') ?>
                <label class="form-label" for="payment-status">Change payment</label>
                <?= Html::dropDownList('payment_status', $model->payment_status, $model::paymentStatusOptions(), ['class' => 'form-select mb-2', 'id' => 'payment-status']) ?>
                <?= Html::submitButton('Update payment', ['class' => 'btn btn-outline-dark btn-sm']) ?>
            <?= Html::endForm() ?>
        </div>
        <div class="oms-card p-3">
            <h2 class="h6 text-secondary">Customer</h2>
            <div class="fw-semibold"><?= Html::encode($model->customer->name ?? '') ?></div>
            <div><?= Html::encode($model->customer->email ?? '') ?></div>
            <div class="text-secondary small mt-2">Created <?= Yii::$app->formatter->asDatetime($model->created_at) ?></div>
        </div>
    </div>
</div>
