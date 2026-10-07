<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

use app\models\User;
use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'Users';
?>
<?= $this->render('//_page', [
    'title' => 'Users',
    'subtitle' => 'Staff accounts. Passwords are stored as hashes.',
    'actions' => Html::a('New user', ['create'], ['class' => 'btn btn-dark']),
]) ?>

<div class="oms-card p-3">
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'tableOptions' => ['class' => 'table table-hover align-middle mb-0'],
        'columns' => [
            'username',
            'email:email',
            [
                'label' => 'Role',
                'value' => static fn (User $model): string => (string) $model->getRoleName(),
            ],
            [
                'attribute' => 'status',
                'value' => static fn (User $model): string => User::statusOptions()[(int) $model->status] ?? '',
            ],
            'created_at:datetime',
            [
                'class' => yii\grid\ActionColumn::class,
                'template' => '{update}',
            ],
        ],
    ]) ?>
</div>
