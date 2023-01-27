<?php

use yii\db\Migration;

/**
 * Class m230127_065610_fulltimer_salary_changes
 */
class m230127_065610_fulltimer_salary_changes extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn('fulltimer','fulltimer_current_salary',$this->string(100)->null());
        $this->alterColumn('fulltimer','fulltimer_expected_salary',$this->string(100)->null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m230127_065610_fulltimer_salary_changes cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230127_065610_fulltimer_salary_changes cannot be reverted.\n";

        return false;
    }
    */
}
