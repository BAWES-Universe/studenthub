<?php

use yii\db\Migration;

/**
 * Class m201105_063544_candidate_notes
 */
class m201105_063544_candidate_notes extends Migration
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
        $this->createTable('candidate_note', [
            "candidate_note_uuid" => $this->char(60),
            'candidate_id' => $this->integer(),
            'staff_id' => $this->integer()->comment("which staff made the note"),
            'note_text' => $this->text(),
            'note_created_datetime' => $this->datetime()->notNull(),
            'note_updated_datetime' => $this->datetime()->notNull(),
        ], $tableOptions);

        $this->addPrimaryKey('PK', 'candidate_note', 'candidate_note_uuid');

        // creates index for column `company_id`
        $this->createIndex(
            'idx-candidate_note-candidate_id',
            'candidate_note',
            'candidate_id'
        );

        // add foreign key for table `company_id`
        $this->addForeignKey(
            'fk-candidate_note-candidate_id',
            'candidate_note',
            'candidate_id',
            'candidate',
            'candidate_id'
        );

        // creates index for column `company_id`
        $this->createIndex(
            'idx-candidate_note-staff_id',
            'candidate_note',
            'staff_id'
        );

        // add foreign key for table `staff_id`
        $this->addForeignKey(
            'fk-candidate_note-staff_id',
            'candidate_note',
            'staff_id',
            'staff',
            'staff_id'

        );

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-candidate_note-company_id','candidate_note');
        $this->dropForeignKey('fk-candidate_note-staff_id','candidate_note');
        $this->dropTable('candidate_note');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m201105_063544_candidate_notes cannot be reverted.\n";

        return false;
    }
    */
}
