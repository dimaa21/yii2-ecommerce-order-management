<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\UserForm $model */

$this->title = 'New user';
?>
<?= $this->render('//_page', [
    'title' => 'New user',
    'subtitle' => 'Admin can manage everything. Manager can manage products, customers and orders.',
]) ?>
<?= $this->render('_form', ['model' => $model]) ?>
