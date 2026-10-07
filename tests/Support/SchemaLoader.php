<?php

declare(strict_types=1);

namespace app\tests\Support;

use Yii;
use yii\db\Migration;

final class SchemaLoader
{
    private static bool $ready = false;

    public static function prepare(): void
    {
        if (!self::$ready) {
            self::rebuild();
            self::$ready = true;
        }

        self::truncate();
    }

    private static function rebuild(): void
    {
        $db = Yii::$app->db;
        $db->close();
        $path = Yii::getAlias('@runtime/test.sqlite');
        if (is_file($path)) {
            unlink($path);
        }
        $db->open();

        $files = [];
        foreach ([
            Yii::getAlias('@yii/rbac/migrations'),
            Yii::getAlias('@app/migrations'),
        ] as $directory) {
            foreach (glob($directory . '/*.php') ?: [] as $file) {
                $files[basename($file, '.php')] = $file;
            }
        }
        ksort($files);

        foreach ($files as $class => $file) {
            require_once $file;
            /** @var Migration $migration */
            $migration = new $class();
            $migration->up();
        }
    }

    private static function truncate(): void
    {
        $db = Yii::$app->db;
        $db->createCommand('PRAGMA foreign_keys = OFF')->execute();
        foreach (['order_item', 'orders', 'product', 'category', 'customer'] as $table) {
            $db->createCommand()->delete('{{%' . $table . '}}', '1=1')->execute();
        }
        $db->createCommand('PRAGMA foreign_keys = ON')->execute();
    }
}
