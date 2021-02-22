<?php

use yii\db\Migration;

/**
 * Class m210215_113028_request_tbl_changes
 */
class m210215_113028_request_tbl_changes extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('request','request_job_description',$this->text()->notNull()->after('request_position_title'));
        $this->addColumn('request','request_compensation',$this->string()->notNull()->after('request_job_description'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('request','request_job_description');
        $this->dropColumn('request','request_compensation');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210215_113028_request_tbl_changes cannot be reverted.\n";

        return false;
    }
    */
}
