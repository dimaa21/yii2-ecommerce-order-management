<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\search\OrderSearch $search */
/** @var yii\data\ActiveDataProvider $dataProvider */

use app\models\Order;
use yii\bootstrap5\ActiveForm;
use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'Orders';
?>
<?= $this->render('//_page', [
    'title' => 'Orders',
    'subtitle' => 'Creating an order checks stock and writes everything in one transaction.',
    'actions' => Html::a('New order', ['create'], ['class' => 'btn btn-dark']),
]) ?>

<div class="oms-card p-3 mb-3">
    <?php $form = ActiveForm::begin(['method' => 'get', 'action' => ['index']]) ?>
        <div class="row g-2 align-items-end">
            <div class="col-md-3"><?= $form->field($search, 'customer')->textInput(['placeholder' => 'Name or email']) ?></div>
            <div class="col-md-2"><?= $form->field($search, 'status')->dropDownList(Order::statusOptions(), ['prompt' => 'Any status']) ?></div>
            <div class="col-md-2"><?= $form->field($search, 'payment_status')->dropDownList(Order::paymentStatusOptions(), ['prompt' => 'Any payment']) ?></div>
            <div class="col-md-2"><?= $form->field($search, 'date_from')->input('date') ?></div>
            <div class="col-md-2"><?= $form->field($search, 'date_to')->input('date') ?></div>
            <div class="col-md-1"><?= Html::submitButton('Filter', ['class' => 'btn btn-outline-dark w-100']) ?></div>
        </div>
    <?php ActiveForm::end() ?>
</div>

<div class="oms-card p-3">
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'tableOptions' => ['class' => 'table table-hover align-middle mb-0'],
        'columns' => [
            [
                'attribute' => 'id',
                'format' => 'raw',
                'value' => static fn (Order $model): string => Html::a('#' . $model->id, ['view', 'id' => $model->id]),
            ],
            [
                'label' => 'Customer',
                'value' => static fn (Order $model): string => $model->customer->name ?? '',
            ],
            [
                'attribute' => 'status',
                'format' => 'raw',
                'value' => static fn (Order $model): string => Yii::$app->view->render('//_status', ['status' => $model->status]),
            ],
            [
                'attribute' => 'payment_status',
                'format' => 'raw',
                'value' => static fn (Order $model): string => Yii::$app->view->render('//_status', ['status' => $model->payment_status]),
            ],
            [
                'attribute' => 'total_amount',
                'format' => 'currency',
                'label' => 'Total',
            ],
            [
                'label' => 'Items',
                'value' => static fn (Order $model): int => count($model->items),
            ],
            'created_at:datetime',
            [
                'class' => yii\grid\ActionColumn::class,
                'template' => '{view}',
            ],
        ],
    ]) ?>
</div>
