<?php

use yii\db\Migration;

/**
 * Class m241106_113451_contract_delete
 */
class m241106_113451_contract_delete extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn("contract", "deleted", $this->boolean()->defaultValue(false));
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
        echo "m241106_113451_contract_delete cannot be reverted.\n";

        return false;
    }
    */
}
