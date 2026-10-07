<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\Customer $model */

$this->title = 'Edit ' . $model->name;
?>
<?= $this->render('//_page', ['title' => 'Edit customer', 'subtitle' => $model->email]) ?>
<?= $this->render('_form', ['model' => $model]) ?>
