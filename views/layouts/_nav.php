<?php

declare(strict_types=1);

use app\components\Rbac;
use yii\helpers\Html;

$items = [
    ['label' => 'Dashboard', 'url' => ['/site/dashboard'], 'permission' => Rbac::DASHBOARD_VIEW, 'controller' => 'site'],
    ['label' => 'Products', 'url' => ['/product/index'], 'permission' => Rbac::PRODUCT_MANAGE, 'controller' => 'product'],
    ['label' => 'Categories', 'url' => ['/category/index'], 'permission' => Rbac::CATEGORY_MANAGE, 'controller' => 'category'],
    ['label' => 'Customers', 'url' => ['/customer/index'], 'permission' => Rbac::CUSTOMER_MANAGE, 'controller' => 'customer'],
    ['label' => 'Orders', 'url' => ['/order/index'], 'permission' => Rbac::ORDER_MANAGE, 'controller' => 'order'],
    ['label' => 'Users', 'url' => ['/user/index'], 'permission' => Rbac::USER_MANAGE, 'controller' => 'user'],
];
$current = Yii::$app->controller->id;
?>
<nav class="oms-nav">
    <?php foreach ($items as $item): ?>
        <?php if (!Yii::$app->user->can($item['permission'])) {
            continue;
        } ?>
        <?= Html::a(Html::encode($item['label']), $item['url'], [
            'class' => $current === $item['controller'] ? 'active' : null,
        ]) ?>
    <?php endforeach ?>
</nav>
