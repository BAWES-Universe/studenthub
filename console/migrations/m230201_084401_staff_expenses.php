<?php

use yii\db\Migration;

/**
 * Class m230201_084401_staff_expenses
 */
class m230201_084401_staff_expenses extends Migration
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
        $this->createTable('staff_expenses', [
            'staff_expense_uuid' => $this->char(60),
            'supplier' => $this->string(225),
            'category' => $this->integer(),
            'purchase_date' => $this->date(),
            'total_amount' => $this->float()->null(),
            'currency' => $this->integer(),
            'vat' => $this->float()->null(),
            'reimbursable' => $this->boolean()->defaultValue(false),
            'description' => $this->text(),
            'file' => $this->string(225),
            'staff_id' => $this->integer(),
            'status' => $this->char(5)->defaultValue('KWD'),
            'created_at' => $this->dateTime(),
            'updated_at' => $this->dateTime(),
            'created_by' => $this->integer(),
            'updated_by' => $this->integer(),

        ], $tableOptions);

        $this->addPrimaryKey('PK', 'staff_expenses', 'staff_expense_uuid');

        $this->createIndex('idx_staff_expenses_staff_id','staff_expenses','staff_id');

        $this->addForeignKey(
            'fk_staff_expenses_staff_id',
            'staff_expenses',
            'staff_id',
            'staff',
            'staff_id',
            'NO ACTION'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('staff_expenses');
    }
}
