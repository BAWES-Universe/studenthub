<?php

use yii\db\Migration;

/**
 * Class m240902_074003_chat
 */
class m240902_074003_chat extends Migration
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

        //chat

        $this->createTable('{{%chat}}', [
            "chat_uuid" => $this->char(60),
            "candidate_id" => $this->integer(11)->notNull(),
            "company_id" => $this->integer(11)->notNull(),
            "parent_company_id" => $this->integer(11)->null(),
            "store_id"=> $this->integer(11)->notNull(),
            "staff_id" => $this->integer(11),
            "contact_uuid"=> $this->char(60),
            "created_at" => $this->dateTime(),
            "updated_at" => $this->dateTime(),
        ], $tableOptions);

        $this->addPrimaryKey('pk-chat', 'chat', "chat_uuid");

        //contact_uuid

        $this->createIndex(
            'idx-chat-contact_uuid', 'chat', 'contact_uuid'
        );

        $this->addForeignKey(
            'fk-chat-contact_uuid', 'chat', 'contact_uuid',
            'contact', 'contact_uuid', "CASCADE"
        );

        //candidate_id

        $this->createIndex(
            'idx-chat-candidate_id', 'chat', 'candidate_id'
        );

        $this->addForeignKey(
            'fk-chat-candidate_id', 'chat', 'candidate_id',
            'candidate', 'candidate_id', "CASCADE"
        );

        //company_id

        $this->createIndex(
            'idx-chat-company_id', 'chat', 'company_id'
        );

        $this->addForeignKey(
            'fk-chat-company_id', 'chat', 'company_id',
            'company', 'company_id', "CASCADE"
        );

        //parent_company_id

        $this->createIndex(
            'idx-chat-parent_company_id', 'chat', 'parent_company_id'
        );

        $this->addForeignKey(
            'fk-chat-parent_company_id', 'chat', 'parent_company_id',
            'company', 'company_id', "CASCADE"
        );

        //store_id

        $this->createIndex(
            'idx-chat-store_id', 'chat', 'store_id'
        );

        $this->addForeignKey(
            'fk-chat-store_id', 'chat', 'store_id',
            'store', 'store_id', "CASCADE"
        );

        //staff_id

        $this->createIndex(
            'idx-chat-staff_id', 'chat', 'staff_id'
        );

        $this->addForeignKey(
            'fk-chat-staff_id', 'chat', 'staff_id',
            'staff', 'staff_id', "CASCADE"
        );

        //chat_message

        $this->createTable('{{%chat_message}}', [
            "chat_message_uuid" => $this->char(60),
            "chat_uuid" => $this->char(60)->notNull(),
            'from' => "Enum('candidate', 'company', 'staff')",
            "message" => $this->text()->notNull(),
            "message_index" => $this->integer(11),
            "status" => $this->tinyInteger(1)->defaultValue(0)->comment("0-sent 1-received 2-read"),
            "created_at" => $this->dateTime(),
            "updated_at" => $this->dateTime(),
        ], $tableOptions);

        $this->addPrimaryKey('pk-chat_message', 'chat_message', "chat_message_uuid");

        //chat_uuid

        $this->createIndex(
            'idx-chat_message-chat_uuid', 'chat_message', 'chat_uuid'
        );

        $this->addForeignKey(
            'fk-chat_message-chat_uuid', 'chat_message', 'chat_uuid',
            'chat', 'chat_uuid', "CASCADE"
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable("chat_message");
        $this->dropTable("chat");
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m240902_074003_chat cannot be reverted.\n";

        return false;
    }
    */
}
