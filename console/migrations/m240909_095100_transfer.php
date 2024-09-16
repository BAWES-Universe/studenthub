<?php

use yii\db\Migration;

/**
 * Class m240909_095100_transfer
 */
class m240909_095100_transfer extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn("transfer_candidate", "minutes", $this->tinyInteger(2)
            ->defaultValue(0)
            ->after("hours"));

        $this->addColumn("transfer_candidate", "seconds", $this->tinyInteger(2)
            ->defaultValue(0)
            ->after("minutes"));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m240909_095100_transfer cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m240909_095100_transfer cannot be reverted.\n";

        return false;
    }
    */
}
