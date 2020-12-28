<?php

use yii\db\Migration;

/**
 * Class m201228_070956_modify_request
 */
class m201228_070956_modify_request extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn('request','request_additional_info',$this->text()->null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m201228_070956_modify_request cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m201228_070956_modify_request cannot be reverted.\n";

        return false;
    }
    */
}
