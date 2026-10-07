<?php

declare(strict_types=1);

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'order-management',
    'name' => 'Order Management',
    'basePath' => dirname(__DIR__),
    'language' => 'en-US',
    'timeZone' => 'Europe/Kyiv',
    'defaultRoute' => 'site/index',
    'bootstrap' => ['log'],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm' => '@vendor/npm-asset',
    ],
    'components' => [
        'request' => [
            'cookieValidationKey' => getenv('COOKIE_VALIDATION_KEY') ?: 'k3N8pQ1sV6mR4tY7wZ2bC5dF8hJ0lA9e',
            'parsers' => [
                'application/json' => \yii\web\JsonParser::class,
            ],
        ],
        'response' => [
            'class' => \yii\web\Response::class,
            'formatters' => [
                \yii\web\Response::FORMAT_JSON => [
                    'class' => \yii\web\JsonResponseFormatter::class,
                    'prettyPrint' => YII_DEBUG,
                ],
            ],
            'on beforeSend' => static function ($event): void {
                $response = $event->sender;
                $path = Yii::$app->request->pathInfo;
                if (!str_starts_with($path, 'api')) {
                    return;
                }

                $response->format = \yii\web\Response::FORMAT_JSON;
                if (is_array($response->data) && array_key_exists('success', $response->data)) {
                    return;
                }

                $exception = Yii::$app->errorHandler->exception;
                $message = 'Internal server error.';
                if ($exception instanceof \yii\web\HttpException) {
                    $message = $exception->getMessage();
                } elseif ($exception !== null && YII_DEBUG) {
                    $message = $exception->getMessage();
                }

                $response->data = [
                    'success' => false,
                    'message' => $message,
                ];
            },
        ],
        'cache' => [
            'class' => \yii\caching\FileCache::class,
        ],
        'user' => [
            'identityClass' => \app\models\User::class,
            'enableAutoLogin' => true,
            'loginUrl' => ['site/login'],
        ],
        'authManager' => [
            'class' => \yii\rbac\DbManager::class,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'formatter' => [
            'class' => \yii\i18n\Formatter::class,
            'datetimeFormat' => 'php:Y-m-d H:i',
            'dateFormat' => 'php:Y-m-d',
            'currencyCode' => 'USD',
            'thousandSeparator' => ',',
            'decimalSeparator' => '.',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                'login' => 'site/login',
                'logout' => 'site/logout',
                'dashboard' => 'site/dashboard',

                'products' => 'product/index',
                'products/create' => 'product/create',
                'products/<action:update|view|delete>/<id:\d+>' => 'product/<action>',

                'categories' => 'category/index',
                'categories/create' => 'category/create',
                'categories/<action:update|view|delete>/<id:\d+>' => 'category/<action>',

                'customers' => 'customer/index',
                'customers/create' => 'customer/create',
                'customers/<action:update|view|delete>/<id:\d+>' => 'customer/<action>',

                'orders' => 'order/index',
                'orders/create' => 'order/create',
                'orders/<action:update|view>/<id:\d+>' => 'order/<action>',

                'users' => 'user/index',
                'users/create' => 'user/create',
                'users/update/<id:\d+>' => 'user/update',

                [
                    'class' => \yii\rest\UrlRule::class,
                    'controller' => ['products' => 'api/product'],
                    'prefix' => 'api',
                    'pluralize' => false,
                    'only' => ['index', 'view', 'create', 'update', 'delete'],
                    'tokens' => ['{id}' => '<id:\\d+>'],
                ],
                [
                    'class' => \yii\rest\UrlRule::class,
                    'controller' => ['categories' => 'api/category'],
                    'prefix' => 'api',
                    'pluralize' => false,
                    'only' => ['index', 'view'],
                    'tokens' => ['{id}' => '<id:\\d+>'],
                ],
                [
                    'class' => \yii\rest\UrlRule::class,
                    'controller' => ['customers' => 'api/customer'],
                    'prefix' => 'api',
                    'pluralize' => false,
                    'only' => ['index', 'view'],
                    'tokens' => ['{id}' => '<id:\\d+>'],
                ],
                [
                    'class' => \yii\rest\UrlRule::class,
                    'controller' => ['orders' => 'api/order'],
                    'prefix' => 'api',
                    'pluralize' => false,
                    'only' => ['index', 'view', 'create', 'update'],
                    'tokens' => ['{id}' => '<id:\\d+>'],
                ],
            ],
        ],
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => \yii\debug\Module::class,
    ];
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => \yii\gii\Module::class,
    ];
}

return $config;
