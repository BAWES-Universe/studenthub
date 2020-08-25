<?php

use yii\db\Migration;

/**
 * Class m200825_145436_request
 */
class m200825_145436_request extends Migration
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
        $this->createTable('request', [
            "request_uuid" => $this->char(60),
            'company_id' => $this->integer()->comment('Which company is this request for?'),
            'contact_uuid' => $this->char(60)->comment("Which contact from this company made the request?"),
            'request_created_by' => $this->integer(),
            'request_updated_by' => $this->integer(),
            'request_position_type' => $this->tinyInteger()->comment('1 - Fulltime, 2 - Partime'),
            'request_position_title' => $this->string()->comment('the job title being requested'),
            'request_number_of_employees' => $this->smallInteger(),
            'request_additional_info' => $this->string(),
            'request_status' => "Enum('pending', 'started', 'delivered', 'cancelled')",
            'request_feedback' => $this->string()->null(),
            'request_created_datetime' => $this->datetime()->notNull(),
            'request_updated_datetime' => $this->datetime()->notNull(),
        ], $tableOptions);

        $this->addPrimaryKey('PK', 'request', 'request_uuid');

        // creates index for column `company_id`
        $this->createIndex(
            'idx-request-company_id',
            'request',
            'company_id'
        );

        // add foreign key for table `company_id`
        $this->addForeignKey(
            'fk-request-company_id',
            'request',
            'company_id',
            'company',
            'company_id',
            'CASCADE'
        );

        // creates index for column `contact_uuid`
        $this->createIndex(
            'idx-request-contact_uuid',
            'request',
            'contact_uuid'
        );

        // add foreign key for table `contact_uuid`
        $this->addForeignKey(
            'fk-request-contact_uuid',
            'request',
            'contact_uuid',
            'company_contact',
            'contact_uuid',
            'CASCADE'
        );
        
        // creates index for column `request_created_by`
        $this->createIndex(
            'idx-request-request_created_by',
            'request',
            'request_created_by'
        );

        // add foreign key for table `request_created_by`
        $this->addForeignKey(
            'fk-request-request_created_by',
            'request',
            'request_created_by',
            'staff',
            'staff_id',
            'CASCADE'
        );
        
        // creates index for column `request_updated_by`
        $this->createIndex(
            'idx-request-request_updated_by',
            'request',
            'request_updated_by'
        );

        // add foreign key for table `request_updated_by`
        $this->addForeignKey(
            'fk-request-request_updated_by',
            'request',
            'request_updated_by',
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
        echo "m200825_145436_request cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200825_145436_request cannot be reverted.\n";

        return false;
    }
    */
}
