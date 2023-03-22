<?php

use yii\db\Migration;

/**
 * Class m230113_065332_candidate_evaluation
 */
class m230113_065332_candidate_evaluation extends Migration
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
        $this->createTable('candidate_eval_ques', [
            'ceq_uuid' => $this->char(60)->comment('candidate_evaluation_question_uuid'),
            'question' => $this->string(225),
            'created_at' => $this->datetime()->notNull(),
            'updated_at' => $this->datetime()->notNull()
        ], $tableOptions);

        $this->addCommentOnTable('candidate_eval_ques','candidate_evaluation_question');
        $this->addPrimaryKey('PK', 'candidate_eval_ques', 'ceq_uuid');

        $this->createTable('candidate_eval_dept_ques', [
            'dept_id' => $this->integer(11)->comment('1-Sales Associate,2-IT,3-Call Centre Agent, 4-Social Media, 5-Outdoor Sales Representative, '),
            'ceq_uuid' => $this->char(60),
        ], $tableOptions);
        $this->addCommentOnTable('candidate_eval_dept_ques','candidate_evaluation_department_question');

        $this->createTable('candidate_evaluation', [
            'can_eval_uuid' => $this->char(60)->comment('candidate_evaluation_uuid'),
            'candidate_id' => $this->integer(11),
            'dept_id' => $this->integer(11)->comment('1-Sales Associate,2-IT,3-Call Centre Agent, 4-Social Media, 5-Outdoor Sales Representative, '),
            'ceq_uuid' => $this->char(60)->comment('candidate_evaluation_question_uuid'),
            'question' => $this->string(225),
            'comment' => $this->text(),
            'rating' => $this->tinyInteger(2),
            'staff_id' => $this->integer(11),
            'created_at' => $this->datetime()->notNull(),
            'updated_at' => $this->datetime()->notNull()
        ], $tableOptions);

        Yii::$app->db->createCommand('ALTER TABLE `candidate_evaluation` ADD PRIMARY KEY(`can_eval_uuid`);')->execute();

        $this->createIndex('idx_candidate_eval_dept_ques_ceq_uuid','candidate_eval_dept_ques','ceq_uuid');

        $this->createIndex('idx_candidate_evaluation_candidate_id','candidate_evaluation','candidate_id');
        $this->createIndex('idx_candidate_evaluation_ceq_uuid','candidate_evaluation','ceq_uuid');
        $this->createIndex('idx_candidate_evaluation_staff_id','candidate_evaluation','staff_id');

        $this->addForeignKey(
            'fk_candidate_eval_dept_ques_ceq_uuid',
            'candidate_eval_dept_ques',
            'ceq_uuid',
            'candidate_eval_ques',
            'ceq_uuid',
            'NO ACTION'
        );

        $this->addForeignKey(
            'fk_candidate_evaluation_candidate_id',
            'candidate_evaluation',
            'candidate_id',
            'candidate',
            'candidate_id',
            'NO ACTION'
        );

        $this->addForeignKey(
            'fk_candidate_evaluation_ceq_uuid',
            'candidate_evaluation',
            'ceq_uuid',
            'candidate_eval_ques',
            'ceq_uuid',
            'NO ACTION'
        );

        $this->addForeignKey(
            'fk_candidate_evaluation_staff_id',
            'candidate_evaluation',
            'staff_id',
            'staff',
            'staff_id',
            'NO ACTION'
        );

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        Yii::$app->db->createCommand( 'SET foreign_key_checks=0')->execute();
        $this->dropTable('candidate_evaluation');
        $this->dropTable('candidate_eval_dept_ques');
        $this->dropTable('candidate_eval_ques');
        Yii::$app->db->createCommand( 'SET foreign_key_checks=1')->execute();
    }
}
