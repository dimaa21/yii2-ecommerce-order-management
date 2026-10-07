<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\Category $model */

use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

?>
<div class="oms-card p-4">
    <?php $form = ActiveForm::begin() ?>
        <?= $form->field($model, 'name') ?>
        <?= $form->field($model, 'slug')->textInput(['placeholder' => 'Leave empty to generate from the name']) ?>
        <?= $form->field($model, 'status')->dropDownList($model::statusOptions()) ?>
        <?= $form->field($model, 'description')->textarea(['rows' => 4]) ?>
        <div class="d-flex gap-2">
            <?= Html::submitButton($model->isNewRecord ? 'Create category' : 'Save changes', ['class' => 'btn btn-dark']) ?>
            <?= Html::a('Cancel', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
        </div>
    <?php ActiveForm::end() ?>
</div>
