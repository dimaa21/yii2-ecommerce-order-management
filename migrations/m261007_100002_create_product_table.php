<?php

declare(strict_types=1);

use app\components\BaseMigration;

/**
 * sku is unique, so the unique index is the sku index.
 * category_id is indexed explicitly; InnoDB reuses that index for the foreign key.
 */
final class m261007_100002_create_product_table extends BaseMigration
{
    public function safeUp(): void
    {
        $this->createTable('{{%product}}', [
            'id' => $this->primaryKey(),
            'category_id' => $this->integer()->notNull(),
            'name' => $this->string(255)->notNull(),
            'slug' => $this->string(255)->notNull()->unique(),
            'description' => $this->text(),
            'price' => $this->decimal(12, 2)->notNull(),
            'sku' => $this->string(64)->notNull()->unique(),
            'stock' => $this->integer()->notNull()->defaultValue(0),
            'status' => $this->string(16)->notNull()->defaultValue('active'),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx_product_category_id', '{{%product}}', 'category_id');
        $this->createIndex('idx_product_status', '{{%product}}', 'status');
        $this->createIndex('idx_product_stock', '{{%product}}', 'stock');

        $this->addForeignKeyIfSupported(
            'fk_product_category',
            '{{%product}}',
            'category_id',
            '{{%category}}',
            'id',
        );

        if ($this->supportsForeignKeys()) {
            $this->execute('ALTER TABLE {{%product}} ADD CONSTRAINT chk_product_price CHECK (price > 0)');
            $this->execute('ALTER TABLE {{%product}} ADD CONSTRAINT chk_product_stock CHECK (stock >= 0)');
        }
    }

    public function safeDown(): void
    {
        $this->dropForeignKeyIfSupported('fk_product_category', '{{%product}}');
        $this->dropTable('{{%product}}');
    }
}
