<?php

declare(strict_types=1);

namespace app\components;

/**
 * Names of RBAC roles and permissions.
 *
 * Stored as constants so controllers, the seed command and the RBAC migration
 * cannot drift apart.
 */
final class Rbac
{
    public const ROLE_ADMIN = 'admin';
    public const ROLE_MANAGER = 'manager';

    public const DASHBOARD_VIEW = 'dashboard.view';
    public const CATEGORY_MANAGE = 'category.manage';
    public const PRODUCT_MANAGE = 'product.manage';
    public const CUSTOMER_MANAGE = 'customer.manage';
    public const ORDER_MANAGE = 'order.manage';
    public const USER_MANAGE = 'user.manage';
}
