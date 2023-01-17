<?php

use yii\db\Migration;

/**
 * Class m230117_113129_candidate_evaluation_answers
 */
class m230117_113129_candidate_evaluation_answers extends Migration
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
        $this->createTable('candidate_evaluation_answer', [
            'can_eval_uuid' => $this->char(60)->comment('candidate_evaluation_uuid'),
            'ceq_uuid' => $this->char(60)->notNull(),
            'question' => $this->string(225),
            'answer' => $this->text(),
            'rating' => $this->tinyInteger(2)
        ], $tableOptions);

        $this->createIndex('idx_candidate_evaluation_answer_ceq_uuid','candidate_evaluation_answer','ceq_uuid');
        $this->createIndex('idx_candidate_evaluation_answer_can_eval_uuid','candidate_evaluation_answer','can_eval_uuid');

        $this->addForeignKey(
            'fk_candidate_evaluation_answer_ceq_uuid',
            'candidate_evaluation_answer',
            'ceq_uuid',
            'candidate_eval_ques',
            'ceq_uuid',
            'NO ACTION'
        );

        $this->addForeignKey(
            'fk_candidate_evaluation_answer_can_eval_uuid',
            'candidate_evaluation_answer',
            'can_eval_uuid',
            'candidate_evaluation',
            'can_eval_uuid',
            'NO ACTION'
        );

        $this->addColumn('candidate_evaluation','date', $this->date()->after('dept_id'));
        $this->dropForeignKey('fk_candidate_evaluation_ceq_uuid','candidate_evaluation');
        $this->dropColumn('candidate_evaluation','ceq_uuid');
        $this->dropColumn('candidate_evaluation','question');
        $this->dropColumn('candidate_evaluation','comment');
        $this->dropColumn('candidate_evaluation','rating');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        Yii::$app->db->createCommand( 'SET foreign_key_checks=0')->execute();
        $this->dropTable('candidate_evaluation_answer');
        Yii::$app->db->createCommand( 'SET foreign_key_checks=1')->execute();
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230117_113129_candidate_evaluation_answers cannot be reverted.\n";

        return false;
    }
    */
}
