<?php

use yii\db\Migration;

/**
 * Class m250127_131617_job_delete
 */
class m250127_131617_job_delete extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $columnData = $this
            ->getDb()
            ->getSchema()
            ->getTableSchema('job')
            ->getColumn('deleted_at');

        if ($columnData) {
            return true;
        }

        $this->addColumn("job", "deleted_at",  $this->dateTime()->null()->after("updated_at"));
        $this->addColumn("job", "deleted_by",  $this->integer(11)->after("updated_by"));

        // creates index for column `deleted_by`
        $this->createIndex(
            'idx-job-deleted_by',
            'job',
            'deleted_by'
        );

        // add foreign key for table `deleted_by`
        $this->addForeignKey(
            'fk-job-deleted_by',
            'job',
            'deleted_by',
            'staff',
            'staff_id'
        );

        $this->addColumn("candidate_notification", "job_interest_uuid",
            $this->char(60)->null()->after("cwlf_uuid"));

        $this->addColumn("candidate_notification", "job_uuid",
            $this->char(60)->null()->after("job_interest_uuid"));

        // creates index for column `job_uuid`
        $this->createIndex(
            'idx-candidate_notification-job_uuid',
            'candidate_notification',
            'job_uuid'
        );

        // add foreign key for table `job_uuid`
        $this->addForeignKey(
            'fk-candidate_notification-job_uuid',
            'candidate_notification',
            'job_uuid',
            'job',
            'job_uuid'
        );

        // creates index for column `job_interest_uuid`
        $this->createIndex(
            'idx-candidate_notification-job_interest_uuid',
            'candidate_notification',
            'job_interest_uuid'
        );

        // add foreign key for table `job_interest_uuid`
        $this->addForeignKey(
            'fk-candidate_notification-job_interest_uuid',
            'candidate_notification',
            'job_interest_uuid',
            'job_interest',
            'job_interest_uuid'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250127_131617_job_delete cannot be reverted.\n";

        return false;
    }
    */
}
