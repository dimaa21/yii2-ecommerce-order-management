<?php

declare(strict_types=1);

use app\components\BaseMigration;

/**
 * The table is named `orders` because ORDER is a reserved word in SQL.
 */
final class m261007_100004_create_order_table extends BaseMigration
{
    public function safeUp(): void
    {
        $this->createTable('{{%orders}}', [
            'id' => $this->primaryKey(),
            'customer_id' => $this->integer()->notNull(),
            'status' => $this->string(16)->notNull()->defaultValue('new'),
            'payment_status' => $this->string(16)->notNull()->defaultValue('pending'),
            'total_amount' => $this->decimal(12, 2)->notNull()->defaultValue('0.00'),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx_orders_customer_id', '{{%orders}}', 'customer_id');
        $this->createIndex('idx_orders_status', '{{%orders}}', 'status');
        $this->createIndex('idx_orders_payment_status', '{{%orders}}', 'payment_status');
        $this->createIndex('idx_orders_created_at', '{{%orders}}', 'created_at');

        $this->addForeignKeyIfSupported(
            'fk_orders_customer',
            '{{%orders}}',
            'customer_id',
            '{{%customer}}',
            'id',
        );
    }

    public function safeDown(): void
    {
        $this->dropForeignKeyIfSupported('fk_orders_customer', '{{%orders}}');
        $this->dropTable('{{%orders}}');
    }
}
