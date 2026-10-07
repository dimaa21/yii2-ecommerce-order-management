<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\Product $model */
/** @var array<int, string> $categories */

$this->title = 'Edit ' . $model->name;
?>
<?= $this->render('//_page', [
    'title' => 'Edit product',
    'subtitle' => $model->sku,
]) ?>
<?= $this->render('_form', ['model' => $model, 'categories' => $categories]) ?>
