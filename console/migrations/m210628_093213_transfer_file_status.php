<?php

use yii\db\Migration;

/**
 * Class m210628_093213_transfer_file_status
 */
class m210628_093213_transfer_file_status extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn ('transfer_file_entry', 'status_description', $this->string ());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m210628_093213_transfer_file_status cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210628_093213_transfer_file_status cannot be reverted.\n";

        return false;
    }
    */
}
