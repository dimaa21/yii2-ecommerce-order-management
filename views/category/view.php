<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\Category $model */

use yii\helpers\Html;
use yii\widgets\DetailView;

$this->title = $model->name;
?>
<?= $this->render('//_page', [
    'title' => $model->name,
    'actions' => Html::a('Edit', ['update', 'id' => $model->id], ['class' => 'btn btn-dark'])
        . Html::a('Back', ['index'], ['class' => 'btn btn-outline-secondary']),
]) ?>
<div class="oms-card p-3">
    <?= DetailView::widget([
        'model' => $model,
        'options' => ['class' => 'table mb-0'],
        'attributes' => [
            'id',
            'name',
            'slug',
            [
                'attribute' => 'status',
                'format' => 'raw',
                'value' => $this->render('//_status', ['status' => $model->status]),
            ],
            'description:ntext',
            [
                'label' => 'Products',
                'value' => count($model->products),
            ],
            'created_at:datetime',
            'updated_at:datetime',
        ],
    ]) ?>
</div>
