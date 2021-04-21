<?php

use yii\db\Migration;

/**
 * Class m210421_110559_update_request_table
 */
class m210421_110559_update_request_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('request','num_hours_followup_interval',$this->integer()->defaultValue(1)->after('request_feedback'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('request','num_hours_followup_interval');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210421_110559_update_request_table cannot be reverted.\n";

        return false;
    }
    */
}
