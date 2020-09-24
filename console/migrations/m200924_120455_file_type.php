<?php

use yii\db\Migration;

/**
 * Class m200924_120455_file_type
 */
class m200924_120455_file_type extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn('file', 'file_type', $this->string(100));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m200924_120455_file_type cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200924_120455_file_type cannot be reverted.\n";

        return false;
    }
    */
}
