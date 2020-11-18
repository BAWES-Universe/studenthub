<?php

use yii\db\Migration;

/**
 * Class m201118_053835_remove_email_constrain
 */
class m201118_053835_remove_email_constrain extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // remove the unique index
        $this->dropIndex('candidate_email', 'candidate');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // add the unique index again
        $this->createIndex('candidate_email', 'candidate', 'candidate_email', $unique = true );
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m201118_053835_remove_email_constrain cannot be reverted.\n";

        return false;
    }
    */
}
