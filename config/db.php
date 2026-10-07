<?php

declare(strict_types=1);

return [
    'class' => \yii\db\Connection::class,
    'dsn' => getenv('DB_DSN') ?: 'mysql:host=127.0.0.1;dbname=oms;charset=utf8mb4',
    'username' => getenv('DB_USERNAME') !== false ? (string) getenv('DB_USERNAME') : 'root',
    'password' => getenv('DB_PASSWORD') !== false ? (string) getenv('DB_PASSWORD') : '',
    'charset' => 'utf8mb4',
    'enableSchemaCache' => YII_ENV_PROD,
    'schemaCacheDuration' => 3600,
    'schemaCache' => 'cache',
];
