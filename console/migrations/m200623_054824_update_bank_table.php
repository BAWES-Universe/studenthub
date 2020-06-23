<?php

use yii\db\Migration;

/**
 * Class m200623_054824_update_bank_table
 */
class m200623_054824_update_bank_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn("bank", "bank_swift_code", $this->string(100));
        $this->alterColumn("bank", "bank_address", $this->string(100));
        $this->alterColumn("bank", "bank_transfer_type", $this->char(3));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m200623_054824_update_bank_table cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200623_054824_update_bank_table cannot be reverted.\n";

        return false;
    }
    */
}
