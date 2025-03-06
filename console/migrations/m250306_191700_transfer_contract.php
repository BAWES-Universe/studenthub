<?php

use yii\db\Migration;

/**
 * Class m250306_191700_transfer_contract
 */
class m250306_191700_transfer_contract extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->execute("SET foreign_key_checks = 0;");

        $this->dropColumn('transfer_candidate', 'contract_uuid');

        $this->addColumn('transfer_candidate', 'contract_uuid',
            $this->char(60)->null()->defaultValue(null));

        $this->addForeignKey(
            'fk_transfer_candidate_contract',
            'transfer_candidate',
            'contract_uuid',
            'contract',
            'contract_uuid',
            'SET NULL',
            'SET NULL'
        );

        $this->createIndex(
            'idx-transfer_candidate-contract_uuid',
            'transfer_candidate',
            'contract_uuid'
        );

        $this->execute("SET foreign_key_checks = 1;");
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m250306_191700_transfer_contract cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250306_191700_transfer_contract cannot be reverted.\n";

        return false;
    }
    */
}
