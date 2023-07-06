<?php

use yii\db\Migration;

/**
 * Class m230706_042813_company_request
 */
class m230706_042813_company_request extends Migration
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
 
        $this->createTable('company_request', [
            'company_request_uuid' => $this->char (60),
            'contact_uuid' => $this->char (60),//->notNull(),
            'company_name' => $this->string(100)->notNull(),
            'company_email' => $this->string()->notNull(),
            'contact_position' => $this->string(100),
            'contact_name' => $this->string(100)->notNull(),
            'contact_receive_email' => $this->boolean()->defaultValue(1),
            'phone_number' => $this->string(),
            'contact_password_hash' =>     $this->string(),
            'status' => $this->tinyInteger(1)
                ->comment("pending=0, processing=1,  accepted=2, rejected=3")
                ->defaultValue(0),
            'created_at' => $this->dateTime(),
            'updated_at' => $this->dateTime(),
        ], $tableOptions);

        $this->addPrimaryKey('PK', 'company_request', 'company_request_uuid');

        // creates index for column `contact_uuid`
        $this->createIndex(
            'idx-company_request-contact_uuid',
            'company_request',
            'contact_uuid'
        );

        // add foreign key for table `contact_uuid`
        $this->addForeignKey(
            'fk-company_request-contact_uuid',
            'company_request',
            'contact_uuid',
            'contact',
            'contact_uuid',
            'SET NULL'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {   
        // add foreign key for table `contact_uuid`
        $this->dropForeignKey(
            'fk-company_request-contact_uuid',
            'company_request'
        );

        // creates index for column `contact_uuid`
        $this->dropIndex(
            'idx-company_request-contact_uuid',
            'company_request'
        );

        $this->dropTable('company_request');

    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230706_042813_company_request cannot be reverted.\n";

        return false;
    }
    */
}
