<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\Category $model */

$this->title = 'New category';
?>
<?= $this->render('//_page', ['title' => 'New category']) ?>
<?= $this->render('_form', ['model' => $model]) ?>
