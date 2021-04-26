<?php

use yii\db\Migration;

/**
 * Class m210426_102129_fulltimer_salary_fields
 */
class m210426_102129_fulltimer_salary_fields extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('fulltimer','fulltimer_current_salary',$this->string(100)->notNull()->after('fulltimer_email'));
        $this->addColumn('fulltimer','fulltimer_expected_salary',$this->string(100)->notNull()->after('fulltimer_current_salary'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('fulltimer','fulltimer_current_salary');
        $this->dropColumn('fulltimer','fulltimer_expected_salary');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210426_102129_fulltimer_salary_fields cannot be reverted.\n";

        return false;
    }
    */
}
