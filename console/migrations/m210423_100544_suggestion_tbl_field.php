<?php

use yii\db\Migration;

/**
 * Class m210423_100544_suggestion_tbl_field
 */
class m210423_100544_suggestion_tbl_field extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        Yii::$app->db->createCommand('update suggestion set `mail_to_company`=1 where 1')->execute();
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
        echo "m210423_100544_suggestion_tbl_field cannot be reverted.\n";

        return false;
    }
    */
}
