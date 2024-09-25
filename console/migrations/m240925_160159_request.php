<?php

use yii\db\Migration;

/**
 * Class m240925_160159_request
 */
class m240925_160159_request extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn("request", "our_fees", $this->decimal(10, 3));
        $this->addColumn("request", "our_fees_unit", $this->string(15));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m240925_160159_request cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m240925_160159_request cannot be reverted.\n";

        return false;
    }
    */
}
