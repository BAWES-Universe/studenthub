<?php

use yii\db\Migration;

/**
 * Class m201204_082140_suggestion
 */
class m201204_082140_suggestion extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->db->createCommand('update suggestion set suggestion_status="1" where suggestion_status="0"')->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m201204_082140_suggestion cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m201204_082140_suggestion cannot be reverted.\n";

        return false;
    }
    */
}
