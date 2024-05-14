<?php

use yii\db\Migration;

/**
 * Class m240505_081539_request_interview
 */
class m240505_081539_request_interview extends Migration
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

        $this->createTable('request_interview', [
            "request_interview_uuid" => $this->char(60),
            "application_uuid" => $this->char(60)->notNull(),
            'request_uuid' => $this->char(60)->notNull(),
            "fulltimer_uuid" => $this->char(60)->null(),
            "candidate_id" => $this->integer(11)->null(),
            "interview_at" => $this->datetime(),
            "internal_note" => $this->text()->null(),
            "status" => $this->tinyInteger(2)->defaultValue(0)
                ->comment("0 - requested, 1 - scheduled, 2 - rejected"),
            "staff_id" => $this->integer(11)->comment("staff assigned to host interview"),  
            "interview_note" => $this->text()->comment("interview joining link / instruction"),  
            "created_by" => $this->char(60)->null(),
            'created_at' => $this->datetime(),
            'updated_at' => $this->datetime(),
        ], $tableOptions);

        $this->addPrimaryKey('PK', 'request_interview', 'request_interview_uuid');

        // creates index for column `staff_id`
        $this->createIndex(
            'idx-request_interview-staff_id',
            'request_interview',
            'staff_id'
        );

        // add foreign key for table `staff_id`
        $this->addForeignKey(
            'fk-request_interview-staff_id',
            'request_interview',
            'staff_id',
            'staff',
            'staff_id'
        );

        // creates index for column `created_by`
        $this->createIndex(
            'idx-request_interview-created_by',
            'request_interview',
            'created_by'
        );

        // add foreign key for table `created_by`
        $this->addForeignKey(
            'fk-request_interview-created_by',
            'request_interview',
            'created_by',
            'contact',
            'contact_uuid'
        );

        // creates index for column `application_uuid`
        $this->createIndex(
            'idx-request_interview-application_uuid',
            'request_interview',
            'application_uuid'
        );

        // add foreign key for table `application_uuid`
        $this->addForeignKey(
            'fk-request_interview-application_uuid',
            'request_interview',
            'application_uuid',
            'request_application',
            'application_uuid',
            'CASCADE'
        );

        // creates index for column `request_uuid`
        $this->createIndex(
            'idx-request_interview-request_uuid',
            'request_interview',
            'request_uuid'
        );

        // add foreign key for table `request_uuid`
        $this->addForeignKey(
            'fk-request_interview-request_uuid',
            'request_interview',
            'request_uuid',
            'request',
            'request_uuid',
            'CASCADE'
        );

        // creates index for column `fulltimer_uuid`
        $this->createIndex(
            'idx-request_interview-fulltimer_uuid',
            'request_interview',
            'fulltimer_uuid'
        );

        // add foreign key for table `fulltimer_uuid`
        $this->addForeignKey(
            'fk-request_interview-fulltimer_uuid',
            'request_interview',
            'fulltimer_uuid',
            'fulltimer',
            'fulltimer_uuid',
            'CASCADE'
        );

        // creates index for column `candidate_id`
        $this->createIndex(
            'idx-request_interview-candidate_id',
            'request_interview',
            'candidate_id'
        );

        // add foreign key for table `candidate_id`
        $this->addForeignKey(
            'fk-request_interview-candidate_id',
            'request_interview',
            'candidate_id',
            'candidate',
            'candidate_id',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m240505_081539_request_interview cannot be reverted.\n";
        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m240505_081539_request_interview cannot be reverted.\n";

        return false;
    }
    */
}
