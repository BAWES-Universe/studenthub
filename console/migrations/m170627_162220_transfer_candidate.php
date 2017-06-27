<?php

use yii\db\Migration;

class m170627_162220_transfer_candidate extends Migration
{
    public function safeUp()
    {
        $this->dropColumn('transfer_candidate', 'deleted');
    }

    public function safeDown()
    {
        echo "m170627_162220_transfer_candidate cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m170627_162220_transfer_candidate cannot be reverted.\n";

        return false;
    }
    */
}
