<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var string $content */

use app\assets\AppAsset;
use app\models\User;
use app\widgets\Alert;
use yii\helpers\Html;

AppAsset::register($this);
$this->registerCsrfMetaTags();
$this->registerMetaTag(['charset' => Yii::$app->charset], 'charset');
$this->registerMetaTag(['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1']);

$identity = Yii::$app->user->identity;
$role = $identity instanceof User ? $identity->getRoleName() : null;
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <title><?= Html::encode($this->title ? $this->title . ' · Order Management' : 'Order Management') ?></title>
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>
<div class="oms-shell d-flex">
    <aside class="oms-sidebar d-none d-lg-block">
        <a class="oms-brand" href="<?= Html::encode(Yii::$app->urlManager->createUrl(['/site/dashboard'])) ?>">
            Order Management
            <small>Admin</small>
        </a>
        <?= $this->render('_nav') ?>
    </aside>
    <div class="flex-grow-1 d-flex flex-column min-vh-100">
        <header class="oms-topbar d-flex align-items-center justify-content-between px-3 px-lg-4">
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-outline-secondary d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileNav">
                    Menu
                </button>
                <span class="fw-semibold d-lg-none">Order Management</span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <?php if ($identity instanceof User): ?>
                    <div class="text-end">
                        <div class="fw-semibold"><?= Html::encode($identity->username) ?></div>
                        <div class="small text-secondary text-capitalize"><?= Html::encode((string) $role) ?></div>
                    </div>
                <?php endif ?>
                <?= Html::beginForm(['/site/logout'], 'post') ?>
                <?= Html::submitButton('Log out', ['class' => 'btn btn-outline-secondary btn-sm']) ?>
                <?= Html::endForm() ?>
            </div>
        </header>
        <main class="oms-content flex-grow-1">
            <?= Alert::widget() ?>
            <?= $content ?>
        </main>
    </div>
</div>
<div class="offcanvas offcanvas-start" tabindex="-1" id="mobileNav">
    <div class="offcanvas-header">
        <h2 class="offcanvas-title h5 mb-0">Order Management</h2>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body bg-dark">
        <?= $this->render('_nav') ?>
    </div>
</div>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
