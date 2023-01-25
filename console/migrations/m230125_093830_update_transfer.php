<?php

use yii\db\Migration;

/**
 * Class m230125_093830_update_transfer
 */
class m230125_093830_update_transfer extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn('transfer_candidate','company_name',$this->string(225));
        $this->alterColumn('transfer_candidate','company_email',$this->string(225));
        $this->alterColumn('transfer_candidate','store_name',$this->string(225));
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
        echo "m230125_093830_update_transfer cannot be reverted.\n";

        return false;
    }
    */
}
