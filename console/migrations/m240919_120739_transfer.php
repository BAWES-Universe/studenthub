<?php

use yii\db\Migration;

/**
 * Class m240919_120739_transfer
 */
class m240919_120739_transfer extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn("transfer_candidate", "prev_candidate_id",
            $this->integer(11)->after("candidate_id")->null());


    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m240919_120739_transfer cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m240919_120739_transfer cannot be reverted.\n";

        return false;
    }
    */
}
