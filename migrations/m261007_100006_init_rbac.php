<?php

declare(strict_types=1);

use app\components\BaseMigration;
use app\components\Rbac;

/**
 * Admin inherits the manager role, then adds catalog structure and user management.
 * A manager can sell (products, customers, orders) but cannot change categories or staff.
 */
final class m261007_100006_init_rbac extends BaseMigration
{
    public function safeUp(): void
    {
        $auth = \Yii::$app->authManager;
        $auth->removeAll();

        $dashboard = $auth->createPermission(Rbac::DASHBOARD_VIEW);
        $dashboard->description = 'View the dashboard';
        $auth->add($dashboard);

        $category = $auth->createPermission(Rbac::CATEGORY_MANAGE);
        $category->description = 'Manage categories';
        $auth->add($category);

        $product = $auth->createPermission(Rbac::PRODUCT_MANAGE);
        $product->description = 'Manage products';
        $auth->add($product);

        $customer = $auth->createPermission(Rbac::CUSTOMER_MANAGE);
        $customer->description = 'Manage customers';
        $auth->add($customer);

        $order = $auth->createPermission(Rbac::ORDER_MANAGE);
        $order->description = 'Manage orders';
        $auth->add($order);

        $user = $auth->createPermission(Rbac::USER_MANAGE);
        $user->description = 'Manage users';
        $auth->add($user);

        $manager = $auth->createRole(Rbac::ROLE_MANAGER);
        $manager->description = 'Store manager';
        $auth->add($manager);
        $auth->addChild($manager, $dashboard);
        $auth->addChild($manager, $product);
        $auth->addChild($manager, $customer);
        $auth->addChild($manager, $order);

        $admin = $auth->createRole(Rbac::ROLE_ADMIN);
        $admin->description = 'Administrator';
        $auth->add($admin);
        $auth->addChild($admin, $manager);
        $auth->addChild($admin, $category);
        $auth->addChild($admin, $user);
    }

    public function safeDown(): void
    {
        \Yii::$app->authManager->removeAll();
    }
}
