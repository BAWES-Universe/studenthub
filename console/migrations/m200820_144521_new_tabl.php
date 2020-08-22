<?php

use yii\db\Migration;

/**
 * Class m200820_144521_new_tabl
 */
class m200820_144521_new_tabl extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        # Company_contact

        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }
        
        $this->createTable('company_contact', [
            "contact_uuid" => $this->char(60),
            'company_id' => $this->integer(),
            'contact_name' => $this->string()->notNull(),
            'contact_position' => $this->string()->notNull(),
            'contact_note' => $this->text(),
            'contact_created_datetime' => $this->datetime()->notNull(),
            'contact_updated_datetime' => $this->datetime()->notNull(),
        ], $tableOptions);

        $this->addPrimaryKey('PK', 'company_contact', 'contact_uuid');
        
        // creates index for column `company_id`
        $this->createIndex(
            'idx-company_contact-company_id',
            'company_contact',
            'company_id'
        );
        
        // add foreign key for table `company_id`
        $this->addForeignKey(
            'fk-company_contact-CASCADE',
            'company_contact',
            'company_id',
            'company',
            'company_id',
            'CASCADE'
        );
 
        # Company_contact_email

        $this->createTable('company_contact_email', [
            "email_uuid" => $this->char(60),
            'contact_uuid' => $this->char(60),
            'email_address' => $this->string()->notNull(),
            'email_created_datetime' => $this->datetime()->notNull(),
            'email_updated_datetime' => $this->datetime()->notNull(),
        ], $tableOptions);
   
        $this->addPrimaryKey('PK', 'company_contact_email', 'email_uuid');
        
        // creates index for column `contact_uuid`
        $this->createIndex(
            'idx-company_contact_email-contact_uuid',
            'company_contact_email',
            'contact_uuid'
        );
        
        // add foreign key for table `contact_uuid`
        $this->addForeignKey(
            'fk-company_contact_email-CASCADE',
            'company_contact_email',
            'contact_uuid',
            'company_contact',
            'contact_uuid',
            'CASCADE'
        );
        
        # Company_contact_phone

        $this->createTable('company_contact_phone', [
            "phone_uuid" => $this->char(60),
            'contact_uuid' => $this->char(60),
            'phone_number' => $this->string()->notNull(),
            'phone_created_datetime' => $this->datetime()->notNull(),
            'phone_updated_datetime' => $this->datetime()->notNull(),
        ], $tableOptions);
   
        $this->addPrimaryKey('PK', 'company_contact_phone', 'phone_uuid');
        
        // creates index for column `contact_uuid`
        $this->createIndex(
            'idx-company_contact_phone-contact_uuid',
            'company_contact_phone',
            'contact_uuid'
        );
        
        // add foreign key for table `contact_uuid`
        $this->addForeignKey(
            'fk-company_contact_phone-CASCADE',
            'company_contact_phone',
            'contact_uuid',
            'company_contact',
            'contact_uuid',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m200820_144521_new_tabl cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200820_144521_new_tabl cannot be reverted.\n";

        return false;
    }
    */
}
