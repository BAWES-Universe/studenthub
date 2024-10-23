<?php

use yii\db\Migration;

/**
 * Class m241023_091738_timer
 */
class m241023_091738_timer extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn("candidate_working_hour", "via", $this->string()->defaultValue("Timer"));

        $this->addColumn("candidate_work_log_feedback", "candidate_working_hour_uuid",
            $this->char(60)->after("date"));

        // creates index for column `candidate_working_hour_uuid`
        $this->createIndex(
            'idx-candidate_work_log_feedback-candidate_working_hour_uuid',
            'candidate_work_log_feedback',
            'candidate_working_hour_uuid'
        );

        // add foreign key for table `candidate_working_hour`
        $this->addForeignKey(
            'fk-candidate_work_log_feedback-candidate_working_hour_uuid',
            'candidate_work_log_feedback',
            'candidate_working_hour_uuid',
            'candidate_working_hour',
            'candidate_working_hour_uuid'
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
        echo "m241023_091738_timer cannot be reverted.\n";

        return false;
    }
    */
}
