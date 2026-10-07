<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\UserForm $model */

use app\components\Rbac;
use app\models\User;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

$isNew = $model->scenario === $model::SCENARIO_CREATE;
?>
<div class="oms-card p-4">
    <?php $form = ActiveForm::begin() ?>
        <?= $form->field($model, 'username') ?>
        <?= $form->field($model, 'email') ?>
        <?= $form->field($model, 'password')->passwordInput([
            'placeholder' => $isNew ? '' : 'Leave blank to keep the current password',
        ]) ?>
        <?= $form->field($model, 'role')->dropDownList([
            Rbac::ROLE_ADMIN => 'Admin',
            Rbac::ROLE_MANAGER => 'Manager',
        ]) ?>
        <?= $form->field($model, 'status')->dropDownList(User::statusOptions()) ?>
        <div class="d-flex gap-2">
            <?= Html::submitButton($isNew ? 'Create user' : 'Save changes', ['class' => 'btn btn-dark']) ?>
            <?= Html::a('Cancel', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
        </div>
    <?php ActiveForm::end() ?>
</div>
