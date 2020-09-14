<?php

use yii\db\Migration;

/**
 * Class m200910_120515_remove_duplicate_history_data
 */
class m200910_120515_remove_duplicate_history_data extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        #SELECT candidate_id, count(*) as total,end_date FROM `candidate_work_history` GROUP by candidate_id having total > 1 and end_date is null

        Yii::$app->db->createCommand('delete from `candidate_work_history` where id in (2108,2109,2110,2112,2113,2114,2130,2131,2132)')->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m200910_120515_remove_duplicate_history_data cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200910_120515_remove_duplicate_history_data cannot be reverted.\n";

        return false;
    }
    */
}
