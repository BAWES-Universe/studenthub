<?php

use yii\db\Migration;

/**
 * Class m210910_045917_devidutt_data_migration_from_cell9_to_axiom
 */
class m210910_045917_devidutt_data_migration_from_cell9_to_axiom extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        Yii::$app->db->createCommand('SET foreign_key_checks = 0')->execute();
        Yii::$app->db->createCommand("DELETE FROM `company_contact` WHERE `company_contact`.`company_contact_uuid` = 'company_contact_6d0509ab-86fb-11eb-bbc9-02b31902a3f6'")->execute();
        Yii::$app->db->createCommand("update `request` set `contact_uuid`='contact_94259525-ed31-11eb-bbc9-02b31902a3f6' WHERE `company_id`='85'")->execute();
        Yii::$app->db->createCommand("update `note` set `contact_uuid`='contact_94259525-ed31-11eb-bbc9-02b31902a3f6' WHERE `company_id`='85'")->execute();
        Yii::$app->db->createCommand('SET foreign_key_checks = 1')->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        return true;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210910_045917_devidutt_data_migration_from_cell9_to_axiom cannot be reverted.\n";

        return false;
    }
    */
}
