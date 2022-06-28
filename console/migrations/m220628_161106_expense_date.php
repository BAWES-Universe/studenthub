<?php

use yii\db\Migration;

/**
 * Class m220628_161106_expense_date
 */
class m220628_161106_expense_date extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn ('expense', 'transaction_datetime', $this->dateTime ()->after('amount'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220628_161106_expense_date cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220628_161106_expense_date cannot be reverted.\n";

        return false;
    }
    */
}
