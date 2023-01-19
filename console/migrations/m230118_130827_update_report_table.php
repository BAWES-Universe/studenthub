<?php

use yii\db\Migration;

/**
 * Class m230118_130827_update_report_table
 */
class m230118_130827_update_report_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->renameColumn('candidate_evaluation','date','start_date');
        $this->addColumn('candidate_evaluation','end_date',$this->date()->after('start_date'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m230118_130827_update_report_table cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230118_130827_update_report_table cannot be reverted.\n";

        return false;
    }
    */
}
