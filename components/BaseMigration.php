<?php

declare(strict_types=1);

namespace app\components;

use Yii;
use yii\db\Migration;

/**
 * Shared helpers for schema migrations.
 *
 * SQLite cannot add a foreign key after the table exists, and the automated
 * tests run on SQLite. MySQL 8 receives the real constraints.
 */
abstract class BaseMigration extends Migration
{
    protected function addForeignKeyIfSupported(
        string $name,
        string $table,
        string $columns,
        string $refTable,
        string $refColumns,
    ): void {
        if (!$this->supportsForeignKeys()) {
            return;
        }

        $this->addForeignKey($name, $table, $columns, $refTable, $refColumns, 'RESTRICT', 'CASCADE');
    }

    protected function dropForeignKeyIfSupported(string $name, string $table): void
    {
        if (!$this->supportsForeignKeys()) {
            return;
        }

        $this->dropForeignKey($name, $table);
    }

    protected function supportsForeignKeys(): bool
    {
        return Yii::$app->db->driverName === 'mysql';
    }
}
