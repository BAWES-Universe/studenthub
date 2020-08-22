<?php

use yii\db\Migration;

/**
 * Class m200821_134549_note_table
 */
class m200821_134549_note_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }
        $this->createTable('note', [
            "note_uuid" => $this->char(60),
            'company_id' => $this->integer(),
            'staff_id' => $this->integer()->comment("which staff made the note"),
            'note_text' => $this->text(),
            'note_created_datetime' => $this->datetime()->notNull(),
            'note_updated_datetime' => $this->datetime()->notNull(),
        ], $tableOptions);

        $this->addPrimaryKey('PK', 'note', 'note_uuid');

        // creates index for column `company_id`
        $this->createIndex(
            'idx-note-company_id',
            'note',
            'company_id'
        );

        // add foreign key for table `company_id`
        $this->addForeignKey(
            'fk-note-company_id',
            'note',
            'company_id',
            'company',
            'company_id',
            'CASCADE'
        );

        // creates index for column `company_id`
        $this->createIndex(
            'idx-note-staff_id',
            'note',
            'staff_id'
        );

        // add foreign key for table `staff_id`
        $this->addForeignKey(
            'fk-note-staff_id',
            'note',
            'staff_id',
            'staff',
            'staff_id',
            'CASCADE'
        );

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-note-company_id','note');
        $this->dropForeignKey('fk-note-staff_id','note');
        $this->dropTable('note');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200821_134549_note_table cannot be reverted.\n";

        return false;
    }
    */
}
