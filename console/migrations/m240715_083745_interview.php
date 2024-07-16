<?php

use yii\db\Migration;

/**
 * Class m240715_083745_interview
 */
class m240715_083745_interview extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->execute("SET foreign_key_checks = 0;");

        $this->db->createCommand("TRUNCATE TABLE interview_evaluation_note")->execute();
        $this->db->createCommand("TRUNCATE TABLE interview_evaluation_note_version")->execute();
        $this->db->createCommand("TRUNCATE TABLE interview_evaluation")->execute();

        $this->execute("SET foreign_key_checks = 1;");

        $this->addColumn("interview_evaluation", "candidate_id", $this->integer(11)->notNull()->after("company_id"));

        //candidate_id

        $this->createIndex(
            'idx-interview_evaluation-candidate_id', 'interview_evaluation', 'candidate_id'
        );

        $this->addForeignKey(
            'fk-interview_evaluation-candidate_id', 'interview_evaluation', 'candidate_id',
            'candidate', 'candidate_id',
            "CASCADE"
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m240715_083745_interview cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m240715_083745_interview cannot be reverted.\n";

        return false;
    }
    */
}
