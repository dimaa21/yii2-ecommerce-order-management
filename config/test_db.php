<?php

declare(strict_types=1);

return [
    'class' => \yii\db\Connection::class,
    'dsn' => 'sqlite:' . dirname(__DIR__) . '/runtime/test.sqlite',
    'charset' => 'utf8',
    'on afterOpen' => static function ($event): void {
        $event->sender->createCommand('PRAGMA foreign_keys = ON')->execute();
    },
];
