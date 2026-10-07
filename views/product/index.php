<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\search\ProductSearch $search */
/** @var yii\data\ActiveDataProvider $dataProvider */

use app\models\Category;
use app\models\Product;
use yii\bootstrap5\ActiveForm;
use yii\grid\GridView;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

$this->title = 'Products';
$categories = ArrayHelper::map(Category::find()->orderBy(['name' => SORT_ASC])->all(), 'id', 'name');
?>
<?= $this->render('//_page', [
    'title' => 'Products',
    'subtitle' => 'Catalog, stock and price.',
    'actions' => Html::a('New product', ['create'], ['class' => 'btn btn-dark']),
]) ?>

<div class="oms-card p-3 mb-3">
    <?php $form = ActiveForm::begin(['method' => 'get', 'action' => ['index']]) ?>
        <div class="row g-2 align-items-end">
            <div class="col-md-3"><?= $form->field($search, 'search')->textInput(['placeholder' => 'Name, SKU or description']) ?></div>
            <div class="col-md-2"><?= $form->field($search, 'category_id')->dropDownList($categories, ['prompt' => 'All categories']) ?></div>
            <div class="col-md-2"><?= $form->field($search, 'status')->dropDownList(Product::statusOptions(), ['prompt' => 'Any status']) ?></div>
            <div class="col-6 col-md-1"><?= $form->field($search, 'min_price') ?></div>
            <div class="col-6 col-md-1"><?= $form->field($search, 'max_price') ?></div>
            <div class="col-6 col-md-1"><?= $form->field($search, 'min_stock') ?></div>
            <div class="col-6 col-md-1"><?= $form->field($search, 'max_stock') ?></div>
            <div class="col-md-1">
                <?= Html::submitButton('Filter', ['class' => 'btn btn-outline-dark w-100']) ?>
            </div>
        </div>
    <?php ActiveForm::end() ?>
</div>

<div class="oms-card p-3">
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'tableOptions' => ['class' => 'table table-hover align-middle mb-0'],
        'columns' => [
            'sku',
            [
                'attribute' => 'name',
                'format' => 'raw',
                'value' => static fn (Product $model): string => Html::a(Html::encode($model->name), ['view', 'id' => $model->id]),
            ],
            [
                'label' => 'Category',
                'value' => static fn (Product $model): string => $model->category->name ?? '',
            ],
            [
                'attribute' => 'price',
                'format' => 'currency',
            ],
            'stock',
            [
                'attribute' => 'status',
                'format' => 'raw',
                'value' => static fn (Product $model): string => Yii::$app->view->render('//_status', ['status' => $model->status]),
            ],
            [
                'class' => yii\grid\ActionColumn::class,
                'template' => '{view} {update} {delete}',
            ],
        ],
    ]) ?>
</div>
