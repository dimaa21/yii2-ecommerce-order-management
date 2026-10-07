<?php

declare(strict_types=1);

use app\components\BaseMigration;

final class m261007_100001_create_category_table extends BaseMigration
{
    public function safeUp(): void
    {
        $this->createTable('{{%category}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(255)->notNull(),
            'slug' => $this->string(255)->notNull()->unique(),
            'description' => $this->text(),
            'status' => $this->string(16)->notNull()->defaultValue('active'),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx_category_status', '{{%category}}', 'status');
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%category}}');
    }
}
