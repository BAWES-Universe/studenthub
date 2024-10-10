<?php

use yii\db\Migration;

/**
 * Class m241010_110144_transfer
 */
class m241010_110144_transfer extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->dropIndex("idx-transfer_candidate-transfer_confirmation_id", "transfer_candidate");

        $this->createIndex('idx-transfer_candidate-transfer_confirmation_id', 'transfer_candidate', ['transfer_confirmation_id', "bank_id", 'deleted'], true);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m241010_110144_transfer cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m241010_110144_transfer cannot be reverted.\n";

        return false;
    }
    */
}
