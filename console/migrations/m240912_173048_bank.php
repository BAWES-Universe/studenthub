<?php

use yii\db\Migration;

/**
 * Class m240912_173048_bank
 */
class m240912_173048_bank extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn("bank", "bank_code_abk",
            $this->integer(5)->after("bank_swift_code")->null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m240912_173048_bank cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m240912_173048_bank cannot be reverted.\n";

        return false;
    }
    */
}
