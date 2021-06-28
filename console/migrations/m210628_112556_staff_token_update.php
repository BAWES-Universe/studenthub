<?php

use yii\db\Migration;

/**
 * Class m210628_112556_staff_token_update
 */
class m210628_112556_staff_token_update extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        Yii::$app->db->createCommand('delete FROM `staff_token` where staff_id in (SELECT staff_id FROM `staff` where deleted=1)')->execute();
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
        echo "m210628_112556_staff_token_update cannot be reverted.\n";

        return false;
    }
    */
}
