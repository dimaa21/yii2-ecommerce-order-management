<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\search\CustomerSearch $search */
/** @var yii\data\ActiveDataProvider $dataProvider */

use app\models\Customer;
use yii\bootstrap5\ActiveForm;
use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'Customers';
?>
<?= $this->render('//_page', [
    'title' => 'Customers',
    'subtitle' => 'Buyers. They do not log in to this admin.',
    'actions' => Html::a('New customer', ['create'], ['class' => 'btn btn-dark']),
]) ?>

<div class="oms-card p-3 mb-3">
    <?php $form = ActiveForm::begin(['method' => 'get', 'action' => ['index']]) ?>
        <div class="row g-2 align-items-end">
            <div class="col-md-4"><?= $form->field($search, 'search')->textInput(['placeholder' => 'Name, email or phone']) ?></div>
            <div class="col-md-3"><?= $form->field($search, 'email') ?></div>
            <div class="col-md-2">
                <?= Html::submitButton('Filter', ['class' => 'btn btn-outline-dark']) ?>
            </div>
        </div>
    <?php ActiveForm::end() ?>
</div>

<div class="oms-card p-3">
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'tableOptions' => ['class' => 'table table-hover align-middle mb-0'],
        'columns' => [
            [
                'attribute' => 'name',
                'format' => 'raw',
                'value' => static fn (Customer $model): string => Html::a(Html::encode($model->name), ['view', 'id' => $model->id]),
            ],
            'email',
            'phone',
            [
                'label' => 'Orders',
                'value' => static fn (Customer $model): int => count($model->orders),
            ],
            'created_at:datetime',
            [
                'class' => yii\grid\ActionColumn::class,
                'template' => '{view} {update} {delete}',
            ],
        ],
    ]) ?>
</div>
