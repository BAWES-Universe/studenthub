<?php

use yii\db\Migration;

/**
 * Class m240612_071616_ticket
 */
class m240612_071616_ticket extends Migration
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

        $this->createTable('{{%ticket}}', [
            'ticket_uuid' => $this->char(60),
            'candidate_id' => $this->integer(11),
            'staff_id' => $this->integer(11),
            'ticket_detail' => $this->text(),
            'ticket_status' => $this->smallInteger(1)->defaultValue(0),
            'ticket_started_at' => $this->dateTime()->null(),
            'ticket_completed_at' => $this->dateTime()->null(),
            'response_time' => $this->integer()->null(),
            'resolution_time' => $this->integer()->null(),
            'created_at' => $this->dateTime(),
            'updated_at' => $this->dateTime()
        ], $tableOptions);

        $this->addPrimaryKey('PK', 'ticket', 'ticket_uuid');

        $this->createIndex(
            'idx-ticket-candidate_id', 'ticket', 'candidate_id'
        );

        $this->addForeignKey(
            'fk-ticket-candidate_id', 'ticket', 'candidate_id', 'candidate', 'candidate_id'
        );

        $this->createIndex(
            'idx-ticket-staff_id', 'ticket', 'staff_id'
        );

        $this->addForeignKey(
            'fk-ticket-staff_id', 'ticket', 'staff_id', 'staff', 'staff_id'
        );

        $this->createTable('{{%ticket_attachment}}', [
            'ticket_attachment_uuid' => $this->char(60),
            'ticket_uuid' => $this->char(60),
            'attachment_uuid' => $this->char(60),
        ], $tableOptions);

        $this->addPrimaryKey('PK', 'ticket_attachment', 'ticket_attachment_uuid');

        $this->createIndex(
            'idx-ticket_attachment-attachment_uuid', 'ticket_attachment', 'attachment_uuid'
        );

        $this->createTable('{{%attachment}}', [
            'attachment_uuid' => $this->char(60),
            'file_path' => $this->string(250)
        ], $tableOptions);

        $this->addPrimaryKey('PK', 'attachment', 'attachment_uuid');


        $this->addForeignKey(
            'fk-ticket_attachment-attachment_uuid', 'ticket_attachment', 'attachment_uuid', 'attachment', 'attachment_uuid'
        );

        $this->createIndex(
            'idx-ticket_attachment-candidate_id', 'ticket_attachment', 'ticket_uuid'
        );

        $this->addForeignKey(
            'fk-ticket_attachment-candidate_id', 'ticket_attachment', 'ticket_uuid', 'ticket', 'ticket_uuid'
        );

        $this->createTable('{{%ticket_comment}}', [
            'ticket_comment_uuid'=> $this->char(60),
            'ticket_uuid' => $this->char(60),
            'candidate_id' => $this->integer(11),
            'staff_id' => $this->integer(11),
            'ticket_comment_detail' => $this->text(),
            'created_at' => $this->dateTime(),
            'updated_at' => $this->dateTime()
        ], $tableOptions);

        $this->addPrimaryKey('PK', 'ticket_comment', 'ticket_comment_uuid');

        $this->createIndex(
            'idx-ticket_comment-ticket_uuid', 'ticket_comment', 'ticket_uuid'
        );

        $this->addForeignKey(
            'fk-ticket_comment-ticket_uuid', 'ticket_comment', 'ticket_uuid', 'ticket', 'ticket_uuid'
        );

        $this->createIndex(
            'idx-ticket_comment-candidate_id', 'ticket_comment', 'candidate_id'
        );

        $this->addForeignKey(
            'fk-ticket_comment-candidate_id', 'ticket_comment', 'candidate_id', 'candidate', 'candidate_id'
        );

        $this->createIndex(
            'idx-ticket_comment-staff_id', 'ticket_comment', 'staff_id'
        );

        $this->addForeignKey(
            'fk-ticket_comment-staff_id', 'ticket_comment', 'staff_id', 'staff', 'staff_id'
        );

        $this->createTable('{{%ticket_comment_attachment}}', [
            'ticket_comment_attachment_uuid' => $this->char(60),
            'ticket_comment_uuid' => $this->char(60),
            'attachment_uuid' => $this->char(60),
        ], $tableOptions);

        $this->addPrimaryKey('PK', 'ticket_comment_attachment', 'ticket_comment_uuid');

        $this->createIndex(
            'idx-ticket_comment_attachment-ticket_comment_uuid', 'ticket_comment_attachment', 'ticket_comment_uuid'
        );

        $this->addForeignKey(
            'fk-ticket_comment_attachment-ticket_comment_uuid', 'ticket_comment_attachment', 'ticket_comment_uuid', 'ticket_comment', 'ticket_comment_uuid'
        );

        $this->createIndex(
            'idx-ticket_comment_attachment-attachment_uuid', 'ticket_comment_attachment', 'attachment_uuid'
        );

        $this->addForeignKey(
            'fk-ticket_comment_attachment-attachment_uuid', 'ticket_comment_attachment', 'attachment_uuid', 'attachment', 'attachment_uuid'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m240612_071616_ticket cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m240612_071616_ticket cannot be reverted.\n";

        return false;
    }
    */
}
