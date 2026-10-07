<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\UserForm $model */
/** @var app\models\User $user */

$this->title = 'Edit ' . $user->username;
?>
<?= $this->render('//_page', [
    'title' => 'Edit user',
    'subtitle' => $user->username,
]) ?>
<?= $this->render('_form', ['model' => $model]) ?>
