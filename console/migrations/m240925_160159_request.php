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

        $this->addColumn("transfer_file", "status", $this->tinyInteger(1)->defaultValue(0));
        $this->addColumn("transfer_file", "error", $this->string());

        \common\models\TransferFile::updateAll(['status' => 2]);

        $this->addColumn("transfer_file", "admin_id", $this->integer(11)->null());

        // creates index for column `admin_id`
        $this->createIndex(
            'idx-transfer_file-admin_id',
            'transfer_file',
            'admin_id'
        );

        // add foreign key for table `admin`
        $this->addForeignKey(
            'fk-transfer_file-admin_id',
            'transfer_file',
            'admin_id',
            'admin',
            'admin_id'
        );
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
        echo "m240925_160159_request cannot be reverted.\n";

        return false;
    }
    */
}
