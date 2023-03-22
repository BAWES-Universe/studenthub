<?php

use yii\db\Migration;

/**
 * Class m221227_112131_fulltimer_changes
 */
class m221227_112131_fulltimer_changes extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        Yii::$app->db->createCommand()->execute('update `fulltimer` set fulltimer_driving_license = 2 where fulltimer_driving_license = 0');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m221227_112131_fulltimer_changes cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m221227_112131_fulltimer_changes cannot be reverted.\n";

        return false;
    }
    */
}
