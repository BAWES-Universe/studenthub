<?php

use yii\db\Migration;

/**
 * Class m200807_085807_file_primary_key
 */
class m200807_085807_file_primary_key extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addPrimaryKey('PK','{{%file}}', 'file_uuid');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m200807_085807_file_primary_key cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200807_085807_file_primary_key cannot be reverted.\n";

        return false;
    }
    */
}
