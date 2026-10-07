<?php

declare(strict_types=1);

if (PHP_SAPI === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    if (is_string($path) && $path !== '/' && is_file(__DIR__ . $path)) {
        return false;
    }
}

require __DIR__ . '/../config/env.php';
loadEnv(dirname(__DIR__) . '/.env');

$debug = getenv('YII_DEBUG');
$env = getenv('YII_ENV');

defined('YII_DEBUG') or define('YII_DEBUG', $debug === false ? true : filter_var((string) $debug, FILTER_VALIDATE_BOOLEAN));
defined('YII_ENV') or define('YII_ENV', $env === false || $env === '' ? 'dev' : (string) $env);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../config/web.php';

(new yii\web\Application($config))->run();
