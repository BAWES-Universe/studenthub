<?php

use yii\db\Migration;

/**
 * Class m240901_080701_kfh_transfer
 */
class m240901_080701_kfh_transfer extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn("transfer_file", "bank", $this->string(100)->null()->after("transfer_file_id"));

        \common\models\TransferFile::updateAll(['bank' => "AUB"]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m240901_080701_kfh_transfer cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m240901_080701_kfh_transfer cannot be reverted.\n";

        return false;
    }
    */
}
