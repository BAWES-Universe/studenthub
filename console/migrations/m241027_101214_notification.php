<?php

use yii\db\Migration;

/**
 * Class m241027_101214_notification
 */
class m241027_101214_notification extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn("candidate_work_log_feedback", "created_by", $this->char(60)->after("rating"));

        // creates index for column `created_by`
        $this->createIndex(
            'idx-candidate_work_log_feedback-created_by',
            'candidate_work_log_feedback',
            'created_by'
        );

        // add foreign key for table `created_by`
        $this->addForeignKey(
            'fk-candidate_work_log_feedback-created_by',
            'candidate_work_log_feedback',
            'created_by',
            'contact',
            'contact_uuid'
        );

        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%candidate_notification}}', [
            "cn_uuid" => $this->char(60),
            "candidate_id"=> $this->integer(11)->notNull(),
            "type" => $this->tinyInteger(2)->notNull(),
            "candidate_work_history_id" => $this->integer(11),
            "candidate_working_date_uuid" => $this->char(60),
            "invitation_uuid" => $this->char(60),
            "request_uuid" => $this->char(60),
            "tc_id" => $this->integer(11),
            "cwlf_uuid" => $this->char(60),
            "company_id" => $this->integer(11),
            "store_id" => $this->integer(11),
            "staff_id" => $this->integer(11),
            "is_new" => $this->boolean()->defaultValue(true),
            "message" => $this->text(),
            "created_at" => $this->dateTime(),
            "updated_at" => $this->dateTime(),
        ], $tableOptions);

        $this->addPrimaryKey('pk-candidate_notification-tba_uuid', 'candidate_notification', "cn_uuid");

        // creates index for column `staff_id`
        $this->createIndex(
            'idx-candidate_notification-staff_id',
            'candidate_notification',
            'staff_id'
        );

        // add foreign key for table `staff_id`
        $this->addForeignKey(
            'fk-candidate_notification-staff_id',
            'candidate_notification',
            'staff_id',
            'staff',
            'staff_id'
        );

        // creates index for column `store_id`
        $this->createIndex(
            'idx-candidate_notification-store_id',
            'candidate_notification',
            'store_id'
        );

        // add foreign key for table `store_id`
        $this->addForeignKey(
            'fk-candidate_notification-store_id',
            'candidate_notification',
            'store_id',
            'store',
            'store_id'
        );

        // creates index for column `cwlf_uuid`
        $this->createIndex(
            'idx-candidate_notification-cwlf_uuid',
            'candidate_notification',
            'cwlf_uuid'
        );

        // add foreign key for table `cwlf_uuid`
        $this->addForeignKey(
            'fk-candidate_notification-cwlf_uuid',
            'candidate_notification',
            'cwlf_uuid',
            'candidate_work_log_feedback',
            'cwlf_uuid'
        );

        // creates index for column `tc_id`
        $this->createIndex(
            'idx-candidate_notification-tc_id',
            'candidate_notification',
            'tc_id'
        );

        // add foreign key for table `tc_id`
        $this->addForeignKey(
            'fk-candidate_notification-tc_id',
            'candidate_notification',
            'tc_id',
            'transfer_candidate',
            'tc_id'
        );

        // creates index for column `candidate_working_date_uuid`
        $this->createIndex(
            'idx-candidate_notification-candidate_working_date_uuid',
            'candidate_notification',
            'candidate_working_date_uuid'
        );

        // add foreign key for table `candidate_working_date`
        $this->addForeignKey(
            'fk-candidate_notification-candidate_working_date_uuid',
            'candidate_notification',
            'candidate_working_date_uuid',
            'candidate_working_date',
            'cwd_uuid'
        );

        // creates index for column `invitation_uuid`
        $this->createIndex(
            'idx-candidate_notification-invitation_uuid',
            'candidate_notification',
            'invitation_uuid'
        );

        // add foreign key for table `invitation`
        $this->addForeignKey(
            'fk-candidate_notification-invitation_uuid',
            'candidate_notification',
            'invitation_uuid',
            'invitation',
            'invitation_uuid'
        );

        // creates index for column `request_uuid`
        $this->createIndex(
            'idx-candidate_notification-request_uuid',
            'candidate_notification',
            'request_uuid'
        );

        // add foreign key for table `request`
        $this->addForeignKey(
            'fk-candidate_notification-request_uuid',
            'candidate_notification',
            'request_uuid',
            'request',
            'request_uuid'
        );

        // creates index for column `company_id`
        $this->createIndex(
            'idx-candidate_notification-company_id',
            'candidate_notification',
            'company_id'
        );

        // add foreign key for table `company`
        $this->addForeignKey(
            'fk-candidate_notification-company_id',
            'candidate_notification',
            'company_id',
            'company',
            'company_id'
        );

        // creates index for column `candidate_work_history_id`
        $this->createIndex(
            'idx-candidate_notification-candidate_work_history_id',
            'candidate_notification',
            'candidate_work_history_id'
        );

        // add foreign key for table `candidate_work_history`
        $this->addForeignKey(
            'fk-candidate_notification-candidate_work_history_id',
            'candidate_notification',
            'candidate_work_history_id',
            'candidate_work_history',
            'id'
        );

        // creates index for column `candidate_id`
        $this->createIndex(
            'idx-candidate_notification-candidate_id',
            'candidate_notification',
            'candidate_id'
        );

        // add foreign key for table `candidate`
        $this->addForeignKey(
            'fk-candidate_notification-candidate_id',
            'candidate_notification',
            'candidate_id',
            'candidate',
            'candidate_id'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn("candidate_work_log_feedback", "created_by");
        $this->dropTable('{{%candidate_notification}}');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m241027_101214_notification cannot be reverted.\n";

        return false;
    }
    */
}
