<?php

use yii\db\Migration;

/**
 * Class m220322_091722_update_request_table_status_field
 */
class m220322_091722_update_request_table_status_field extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        Yii::$app->db->createCommand("ALTER TABLE `request` CHANGE `request_status` `request_status` ENUM('pending','started','delivered','cancelled','finished_by_recruitment','re_work') CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;")->execute();
        $this->addColumn ('request', 'request_finished_at',
            $this->dateTime ()->after('request_assigned_at'));

        $this->addColumn ('request', 'request_re_worked_at',
            $this->dateTime ()->after('request_finished_at'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        Yii::$app->db->createCommand("ALTER TABLE `request` CHANGE `request_status` `request_status` ENUM('pending','started','delivered','cancelled') CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;")->execute();
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220322_091722_update_request_table_status_field cannot be reverted.\n";

        return false;
    }
    */
}
