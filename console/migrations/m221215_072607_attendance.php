<?php

use yii\db\Migration;

/**
 * Class m221215_072607_attendance
 */
class m221215_072607_attendance extends Migration
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

        $this->addColumn('staff', 'week_start_day', $this->tinyInteger(1)->defaultValue(7));
        $this->addColumn('staff', 'work_days', $this->tinyInteger(1)->defaultValue(5));
        $this->addColumn('staff', 'hours_per_day', $this->tinyInteger(1)->defaultValue(8));

        $this->createTable('daily_standup_question', [
            'question_uuid' => $this->char(60),
            'question' => $this->string(),
            'created_at' => $this->datetime()->notNull(),
            'updated_at' => $this->datetime()->notNull()
        ], $tableOptions);

        $this->addPrimaryKey('PK', 'daily_standup_question', 'question_uuid');

        $this->createTable('daily_standup_answer', [
            'answer_uuid' => $this->char(60),
            'staff_id' => $this->integer(11),
            'question_uuid' => $this->char(60),
            'question' => $this->string(),
            'answer' => $this->text(),
            'created_at' => $this->datetime()->notNull(),
            'updated_at' => $this->datetime()->notNull()
        ], $tableOptions);

        $this->addPrimaryKey('PK', 'daily_standup_answer', 'answer_uuid');

        $this->createIndex(
            'idx-daily_standup_answer-staff_id',
            'daily_standup_answer',
            'staff_id'
        );

        // add foreign key for table `staff_id`
        $this->addForeignKey(
            'fk-daily_standup_answer-staff_id',
            'daily_standup_answer',
            'staff_id',
            'staff',
            'staff_id'
        );

        $this->createIndex(
            'idx-daily_standup_answer-question_uuid',
            'daily_standup_answer',
            'question_uuid'
        );

        // add foreign key for table `question_uuid`
        $this->addForeignKey(
            'fk-daily_standup_answer-question_uuid',
            'daily_standup_answer',
            'question_uuid',
            'daily_standup_question',
            'question_uuid'
        );

        $this->createTable('staff_work_session', [
            'work_session_uuid' => $this->char(60),
            'staff_id' => $this->integer(11),
            'total_minutes' => $this->integer(4),
            'created_at' => $this->datetime()->notNull(),
            'updated_at' => $this->datetime()->notNull()
        ], $tableOptions);

        $this->addPrimaryKey('PK', 'staff_work_session', 'work_session_uuid');

        $this->createIndex(
            'idx-staff_work_session-staff_id',
            'staff_work_session',
            'staff_id'
        );

        // add foreign key for table `staff_id`
        $this->addForeignKey(
            'fk-staff_work_session-staff_id',
            'staff_work_session',
            'staff_id',
            'staff',
            'staff_id'
        );

        $this->createTable('staff_leave', [
            'staff_leave_uuid' => $this->char(60),
            'staff_id' => $this->integer(11),
            'from_date' => $this->date(),
            'to_date' => $this->date(),
            'note' => $this->text(),
            'created_at' => $this->datetime()->notNull(),
            'updated_at' => $this->datetime()->notNull()
        ], $tableOptions);

        $this->addPrimaryKey('PK', 'staff_leave', 'staff_leave_uuid');

        $this->createIndex(
            'idx-staff_leave-staff_id',
            'staff_leave',
            'staff_id'
        );

        // add foreign key for table `staff_id`
        $this->addForeignKey(
            'fk-staff_leave-staff_id',
            'staff_leave',
            'staff_id',
            'staff',
            'staff_id'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m221215_072607_attendance cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m221215_072607_attendance cannot be reverted.\n";

        return false;
    }
    */
}
