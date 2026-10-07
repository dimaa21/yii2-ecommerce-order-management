<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\Product $model */
/** @var array<int, string> $categories */

$this->title = 'New product';
?>
<?= $this->render('//_page', [
    'title' => 'New product',
    'subtitle' => 'Price must be greater than zero. Stock cannot be negative.',
]) ?>
<?= $this->render('_form', ['model' => $model, 'categories' => $categories]) ?>
