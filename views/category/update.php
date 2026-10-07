<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\Category $model */

$this->title = 'Edit ' . $model->name;
?>
<?= $this->render('//_page', ['title' => 'Edit category', 'subtitle' => $model->name]) ?>
<?= $this->render('_form', ['model' => $model]) ?>
