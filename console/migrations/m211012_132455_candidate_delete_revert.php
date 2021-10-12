<?php

use yii\db\Migration;

/**
 * Class m211012_132455_candidate_delete_revert
 */
class m211012_132455_candidate_delete_revert extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        Yii::$app->db->createCommand('SET foreign_key_checks = 0')->execute();
        Yii::$app->db->createCommand("UPDATE `candidate` SET `deleted` = '0' WHERE `candidate`.`candidate_id` = 6642")->execute();
        Yii::$app->db->createCommand('SET foreign_key_checks = 1')->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        Yii::$app->db->createCommand('SET foreign_key_checks = 0')->execute();
        Yii::$app->db->createCommand("UPDATE `candidate` SET `deleted` = '1' WHERE `candidate`.`candidate_id` = 6642")->execute();
        Yii::$app->db->createCommand('SET foreign_key_checks = 1')->execute();
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m211012_132455_candidate_delete_revert cannot be reverted.\n";

        return false;
    }
    */
}
