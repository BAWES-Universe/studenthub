<?php

use yii\db\Migration;

/**
 * Class m201207_081814_contact_note
 */
class m201207_081814_contact_note extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->dropColumn('company_contact', 'contact_note');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m201207_081814_contact_note cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m201207_081814_contact_note cannot be reverted.\n";

        return false;
    }
    */
}
