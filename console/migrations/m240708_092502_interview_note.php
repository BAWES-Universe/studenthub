<?php

use yii\db\Migration;

/**
 * Class m240708_092502_interview_note
 */
class m240708_092502_interview_note extends Migration
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

        $this->createTable('{{%interview_evaluation_note_version}}', [
            "ienv_uuid" => $this->char(60),
            'interview_evaluation_uuid' => $this->char(60),
            "version" => $this->tinyInteger(4)->defaultValue(1),
            "staff_id" => $this->integer(11),
            "created_at" => $this->dateTime(),
            "updated_at" => $this->dateTime(),
        ], $tableOptions);

        $this->addPrimaryKey('PK', 'interview_evaluation_note_version', 'ienv_uuid');

        //staff_id

        $this->createIndex(
            'idx-interview_evaluation_note_version-staff_id', 'interview_evaluation_note_version', 'staff_id'
        );

        $this->addForeignKey(
            'fk-interview_evaluation_note_version-staff_id', 'interview_evaluation_note_version', 'staff_id',
            'staff', 'staff_id'
        );

        //interview_evaluation_uuid

        $this->createIndex(
            'idx-interview_evaluation_note_version-interview_evaluation_uuid', 'interview_evaluation_note_version', 'interview_evaluation_uuid'
        );

        $this->addForeignKey(
            'fk-interview_evaluation_note_version-interview_evaluation_uuid', 'interview_evaluation_note_version', 'interview_evaluation_uuid',
            'interview_evaluation', 'interview_evaluation_uuid',
            "CASCADE"
        );

        //interview_evaluation_note

        $this->createTable('{{%interview_evaluation_note}}', [
            "ien_uuid" =>  $this->char(60),
            "ienv_uuid" => $this->char(60),
            "note" => $this->text()
        ], $tableOptions);

        $this->addPrimaryKey('PK', 'interview_evaluation_note', 'ien_uuid');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m240708_092502_interview_note cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m240708_092502_interview_note cannot be reverted.\n";

        return false;
    }
    */
}
