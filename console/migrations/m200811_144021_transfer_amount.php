<?php

use yii\db\Migration;

/**
 * Class m200811_144021_transfer_amount
 */
class m200811_144021_transfer_amount extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('transfer_file', 'transfer_amount', $this->decimal(12, 3)->after('transfer_file_s3_path'));
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
        echo "m200811_144021_transfer_amount cannot be reverted.\n";

        return false;
    }
    */
}
