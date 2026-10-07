<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\Product $model */
/** @var array<int, string> $categories */

use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

?>
<div class="oms-card p-4">
    <?php $form = ActiveForm::begin() ?>
        <div class="row g-3">
            <div class="col-md-8">
                <?= $form->field($model, 'name') ?>
            </div>
            <div class="col-md-4">
                <?= $form->field($model, 'sku') ?>
            </div>
            <div class="col-md-6">
                <?= $form->field($model, 'category_id')->dropDownList($categories, ['prompt' => 'Select a category']) ?>
            </div>
            <div class="col-md-3">
                <?= $form->field($model, 'price')->textInput(['type' => 'number', 'step' => '0.01', 'min' => '0.01']) ?>
            </div>
            <div class="col-md-3">
                <?= $form->field($model, 'stock')->textInput(['type' => 'number', 'min' => '0', 'step' => '1']) ?>
            </div>
            <div class="col-md-4">
                <?= $form->field($model, 'status')->dropDownList($model::statusOptions()) ?>
            </div>
            <div class="col-md-8">
                <?= $form->field($model, 'slug')->textInput(['placeholder' => 'Leave empty to generate from the name']) ?>
            </div>
            <div class="col-12">
                <?= $form->field($model, 'description')->textarea(['rows' => 4]) ?>
            </div>
        </div>
        <div class="mt-3 d-flex gap-2">
            <?= Html::submitButton($model->isNewRecord ? 'Create product' : 'Save changes', ['class' => 'btn btn-dark']) ?>
            <?= Html::a('Cancel', $model->isNewRecord ? ['index'] : ['view', 'id' => $model->id], ['class' => 'btn btn-outline-secondary']) ?>
        </div>
    <?php ActiveForm::end() ?>
</div>
