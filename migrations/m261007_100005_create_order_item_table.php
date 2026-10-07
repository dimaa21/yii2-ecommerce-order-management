<?php

declare(strict_types=1);

use app\components\BaseMigration;

/**
 * price and total are a snapshot of the purchase.
 * Changing the product price later must not rewrite history.
 *
 * The unique pair (order_id, product_id) also indexes order_id,
 * because order_id is the leftmost column.
 */
final class m261007_100005_create_order_item_table extends BaseMigration
{
    public function safeUp(): void
    {
        $this->createTable('{{%order_item}}', [
            'id' => $this->primaryKey(),
            'order_id' => $this->integer()->notNull(),
            'product_id' => $this->integer()->notNull(),
            'quantity' => $this->integer()->notNull(),
            'price' => $this->decimal(12, 2)->notNull(),
            'total' => $this->decimal(12, 2)->notNull(),
        ]);

        $this->createIndex('uq_order_item_order_product', '{{%order_item}}', ['order_id', 'product_id'], true);
        $this->createIndex('idx_order_item_product_id', '{{%order_item}}', 'product_id');

        $this->addForeignKeyIfSupported(
            'fk_order_item_order',
            '{{%order_item}}',
            'order_id',
            '{{%orders}}',
            'id',
        );
        $this->addForeignKeyIfSupported(
            'fk_order_item_product',
            '{{%order_item}}',
            'product_id',
            '{{%product}}',
            'id',
        );

        if ($this->supportsForeignKeys()) {
            $this->execute('ALTER TABLE {{%order_item}} ADD CONSTRAINT chk_order_item_quantity CHECK (quantity > 0)');
            $this->execute('ALTER TABLE {{%order_item}} ADD CONSTRAINT chk_order_item_price CHECK (price > 0)');
        }
    }

    public function safeDown(): void
    {
        $this->dropForeignKeyIfSupported('fk_order_item_product', '{{%order_item}}');
        $this->dropForeignKeyIfSupported('fk_order_item_order', '{{%order_item}}');
        $this->dropTable('{{%order_item}}');
    }
}
