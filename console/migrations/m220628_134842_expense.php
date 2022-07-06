<?php

use yii\db\Migration;

/**
 * Class m220628_134842_expense
 */
class m220628_134842_expense extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%expense}}', [
            'expense_uuid' => $this->char(60),
            'title' => $this->string (128)->notNull(),
            'type' => $this->string(128)->notNull(),
            'detail' => $this->text (),
            'amount' => $this->decimal(10, 3),
            'created_by' => $this->integer(11),
            'updated_by' => $this->integer(11),
            'created_at' => $this->dateTime(),
            'updated_at' => $this->dateTime(),
        ], $tableOptions);

        $this->addPrimaryKey('PK', 'expense', 'expense_uuid');

        $this->createIndex(
            'idx-expense-created_by',
            'expense',
            'created_by'
        );

        $this->addForeignKey(
            'fk-expense-created_by',
            'expense',
            'created_by',
            'admin',
            'admin_id',
            'SET NULL'
        );

        $this->createIndex(
            'idx-expense-updated_by',
            'expense',
            'updated_by'
        );

        $this->addForeignKey(
            'fk-expense-updated_by',
            'expense',
            'updated_by',
            'admin',
            'admin_id',
            'SET NULL'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220628_134842_expense cannot be reverted.\n";

        return false;
    }
    */
}
