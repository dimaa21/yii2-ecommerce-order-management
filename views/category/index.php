<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

use app\models\Category;
use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'Categories';
?>
<?= $this->render('//_page', [
    'title' => 'Categories',
    'subtitle' => 'Only administrators can change the catalog structure.',
    'actions' => Html::a('New category', ['create'], ['class' => 'btn btn-dark']),
]) ?>

<div class="oms-card p-3">
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'tableOptions' => ['class' => 'table table-hover align-middle mb-0'],
        'columns' => [
            [
                'attribute' => 'name',
                'format' => 'raw',
                'value' => static fn (Category $model): string => Html::a(Html::encode($model->name), ['view', 'id' => $model->id]),
            ],
            'slug',
            [
                'label' => 'Products',
                'value' => static fn (Category $model): int => count($model->products),
            ],
            [
                'attribute' => 'status',
                'format' => 'raw',
                'value' => static fn (Category $model): string => Yii::$app->view->render('//_status', ['status' => $model->status]),
            ],
            [
                'class' => yii\grid\ActionColumn::class,
                'template' => '{view} {update} {delete}',
            ],
        ],
    ]) ?>
</div>
