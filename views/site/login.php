<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\LoginForm $model */

use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

$this->title = 'Sign in';
?>
<div class="guest-card oms-card p-4 p-md-5">
    <h1 class="h3 mb-1">Order Management</h1>
    <p class="text-secondary mb-4">Sign in to the admin console.</p>
    <?php $form = ActiveForm::begin() ?>
        <?= $form->field($model, 'username')->textInput(['autofocus' => true]) ?>
        <?= $form->field($model, 'password')->passwordInput() ?>
        <?= $form->field($model, 'rememberMe')->checkbox() ?>
        <?= Html::submitButton('Sign in', ['class' => 'btn btn-dark w-100']) ?>
    <?php ActiveForm::end() ?>
</div>
