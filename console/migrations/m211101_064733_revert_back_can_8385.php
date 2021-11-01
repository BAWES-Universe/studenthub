<?php

use yii\db\Migration;

/**
 * Class m211101_064733_revert_back_can_8385
 */
class m211101_064733_revert_back_can_8385 extends Migration
{
    public function safeUp()
    {
        Yii::$app->db->createCommand('SET foreign_key_checks = 0')->execute();
        Yii::$app->db->createCommand("UPDATE `candidate` SET `deleted` = '0' WHERE `candidate`.`candidate_id` = 8385")->execute();
        Yii::$app->db->createCommand('SET foreign_key_checks = 1')->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        Yii::$app->db->createCommand('SET foreign_key_checks = 0')->execute();
        Yii::$app->db->createCommand("UPDATE `candidate` SET `deleted` = '1' WHERE `candidate`.`candidate_id` = 8385")->execute();
        Yii::$app->db->createCommand('SET foreign_key_checks = 1')->execute();
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m211101_064733_revert_back_can_8385 cannot be reverted.\n";

        return false;
    }
    */
}
