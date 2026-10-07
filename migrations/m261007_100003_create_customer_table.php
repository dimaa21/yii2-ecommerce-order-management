<?php

declare(strict_types=1);

use app\components\BaseMigration;

final class m261007_100003_create_customer_table extends BaseMigration
{
    public function safeUp(): void
    {
        $this->createTable('{{%customer}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(255)->notNull(),
            'email' => $this->string(255)->notNull()->unique(),
            'phone' => $this->string(32)->null(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%customer}}');
    }
}
