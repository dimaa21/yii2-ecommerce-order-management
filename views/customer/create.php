<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\Customer $model */

$this->title = 'New customer';
?>
<?= $this->render('//_page', ['title' => 'New customer']) ?>
<?= $this->render('_form', ['model' => $model]) ?>
