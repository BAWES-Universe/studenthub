<?php

use yii\db\Migration;

/**
 * Class m240701_062331_hitmap_alert
 */
class m240701_062331_hitmap_alert extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn("firing_hitmap", "is_alerted", $this->boolean()->defaultValue(false)->after("total"));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m240701_062331_hitmap_alert cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m240701_062331_hitmap_alert cannot be reverted.\n";

        return false;
    }
    */
}
