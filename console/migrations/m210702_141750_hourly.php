<?php

use yii\db\Migration;

/**
 * Class m210702_141750_hourly
 */
class m210702_141750_hourly extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn ('transfer_candidate', 'hours', $this->double ()->defaultValue (0)->unsigned ());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210702_141750_hourly cannot be reverted.\n";

        return false;
    }
    */
}
