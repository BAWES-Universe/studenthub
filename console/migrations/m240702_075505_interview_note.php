<?php

use yii\db\Migration;

/**
 * Class m240702_075505_interview_note
 */
class m240702_075505_interview_note extends Migration
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

        $this->createTable('{{%interview_evaluation}}', [
            'interview_evaluation_uuid' => $this->char(60),
            "request_uuid" => $this->char(60)->notNull(),
            'company_id' => $this->integer(11)->notNull(),
            "staff_id" => $this->integer(11),
            "created_at" => $this->dateTime(),
            "updated_at" => $this->dateTime(),
        ], $tableOptions);

        $this->addPrimaryKey('PK', 'interview_evaluation', 'interview_evaluation_uuid');

        //company_id

        $this->createIndex(
            'idx-interview_evaluation-company_id', 'interview_evaluation', 'company_id'
        );

        $this->addForeignKey(
            'fk-interview_evaluation-company_id', 'interview_evaluation', 'company_id',
            'company', 'company_id',
            "CASCADE"
        );
        
        //request_uuid

        $this->createIndex(
            'idx-interview_evaluation-request_uuid', 'interview_evaluation', 'request_uuid'
        );

        $this->addForeignKey(
            'fk-interview_evaluation-request_uuid', 'interview_evaluation', 'request_uuid',
            'request', 'request_uuid',
            "CASCADE"
        );

        //staff_id

        $this->createIndex(
            'idx-interview_evaluation-staff_id', 'interview_evaluation', 'staff_id'
        );

        $this->addForeignKey(
            'fk-interview_evaluation-staff_id', 'interview_evaluation', 'staff_id', 'staff', 'staff_id'
        );

        //note relation

        $this->addColumn("note", "interview_evaluation_uuid", $this->char(60)->after("request_uuid"));

        $this->createIndex(
            'idx-note-interview_evaluation_uuid', 'interview_evaluation', 'interview_evaluation_uuid'
        );

        $this->addForeignKey(
            'fk-note-interview_evaluation_uuid', 'note', 'interview_evaluation_uuid',
            'interview_evaluation', 'interview_evaluation_uuid', "CASCADE"
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%interview_evaluation}}');

       //$this->dropColumn("note", "interview_evaluation_uuid");

    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m240702_075505_interview_note cannot be reverted.\n";

        return false;
    }
    */
}
