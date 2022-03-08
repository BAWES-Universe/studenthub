<?php

use yii\db\Migration;

/**
 * Class m220308_093506_remove_candidate_id_data
 */
class m220308_093506_remove_candidate_id_data extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        Yii::$app->db->createCommand("update `candidate_id_card` set deleted = '1' where DATE(created_at) >= '2022-03-06'")->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        Yii::$app->db->createCommand("update `candidate_id_card` set deleted = '0' where DATE(created_at) >= '2022-03-06'")->execute();
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220308_093506_remove_candidate_id_data cannot be reverted.\n";

        return false;
    }
    */
}
