<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\Customer $model */

use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

?>
<div class="oms-card p-4">
    <?php $form = ActiveForm::begin() ?>
        <?= $form->field($model, 'name') ?>
        <?= $form->field($model, 'email') ?>
        <?= $form->field($model, 'phone')->textInput(['placeholder' => 'Optional']) ?>
        <div class="d-flex gap-2">
            <?= Html::submitButton($model->isNewRecord ? 'Create customer' : 'Save changes', ['class' => 'btn btn-dark']) ?>
            <?= Html::a('Cancel', $model->isNewRecord ? ['index'] : ['view', 'id' => $model->id], ['class' => 'btn btn-outline-secondary']) ?>
        </div>
    <?php ActiveForm::end() ?>
</div>
