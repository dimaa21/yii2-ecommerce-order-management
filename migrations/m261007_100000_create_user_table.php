<?php

declare(strict_types=1);

use app\components\BaseMigration;

/**
 * Staff accounts. Passwords are stored only as hashes.
 * Roles live in the RBAC tables, not in a role column.
 */
final class m261007_100000_create_user_table extends BaseMigration
{
    public function safeUp(): void
    {
        $this->createTable('{{%user}}', [
            'id' => $this->primaryKey(),
            'username' => $this->string(64)->notNull()->unique(),
            'email' => $this->string(255)->notNull()->unique(),
            'password_hash' => $this->string()->notNull(),
            'auth_key' => $this->string(32)->notNull(),
            'access_token' => $this->string(64)->notNull()->unique(),
            'status' => $this->smallInteger()->notNull()->defaultValue(10),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx_user_status', '{{%user}}', 'status');
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%user}}');
    }
}
