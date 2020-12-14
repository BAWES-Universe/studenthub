<?php

use yii\db\Migration;

/**
 * Class m201214_110450_transfer_staff
 */
class m201214_110450_transfer_staff extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn ('transfer', 'transfer_created_by', $this->integer (11)->after('end_date')->null());

        $this->addColumn ('transfer', 'transfer_updated_by', $this->integer (11)->after('transfer_created_by')->null());

        // creates index for column `transfer_created_by`
        $this->createIndex(
            'idx-transfer-transfer_created_by',
            'transfer',
            'transfer_created_by'
        );

        // add foreign key for table `transfer_created_by`
        $this->addForeignKey(
            'fk-transfer-transfer_created_by',
            'transfer',
            'transfer_created_by',
            'staff',
            'staff_id'
        );

        // creates index for column `transfer_updated_by`
        $this->createIndex(
            'idx-transfer-transfer_updated_by',
            'transfer',
            'transfer_updated_by'
        );

        // add foreign key for table `transfer_updated_by`
        $this->addForeignKey(
            'fk-transfer-transfer_updated_by',
            'transfer',
            'transfer_updated_by',
            'staff',
            'staff_id'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m201214_110450_transfer_staff cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m201214_110450_transfer_staff cannot be reverted.\n";

        return false;
    }
    */
}
