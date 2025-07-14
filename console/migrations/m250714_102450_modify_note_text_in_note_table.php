<?php

use yii\db\Migration;

/**
 * Class m250714_102450_modify_note_text_in_note_table
 */
class m250714_102450_modify_note_text_in_note_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->execute("
            ALTER TABLE note
              MODIFY note_text TEXT
              CHARACTER SET utf8mb4
              COLLATE utf8mb4_unicode_ci;
        ");
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->execute("
            ALTER TABLE note
              MODIFY note_text TEXT
              CHARACTER SET utf8
              COLLATE utf8_general_ci;
        ");
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250714_102450_modify_note_text_in_note_table cannot be reverted.\n";

        return false;
    }
    */
}
