<?php

use yii\db\Migration;

/**
 * Class m241104_073200_contract
 */
class m241104_073200_contract extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn("transfer", "contract_uuid", $this->char(60)
            ->after("company_id")->null());

        // creates index for column `contract_uuid`
        $this->createIndex(
            'idx-transfer-contract_uuid',
            'transfer',
            'contract_uuid'
        );

        // add foreign key for table `contract`
        $this->addForeignKey(
            'fk-transfer-contract_uuid',
            'transfer',
            'contract_uuid',
            'contract',
            'contract_uuid'
        );

        //in case contract get deleted or updated, save contract details in transfer

        $this->addColumn("transfer", "contract_type", $this->string()->notNull()
            ->after("contract_uuid")->null());



    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m241104_073200_contract cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m241104_073200_contract cannot be reverted.\n";

        return false;
    }
    */
}
