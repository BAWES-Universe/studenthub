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

        $tableSchema = $this->getDb()->getSchema()->getTableSchema('interview_evaluation');
        
        if ($tableSchema && !isset($tableSchema->columns['candidate_id'])) {
            $this->addColumn("interview_evaluation", "candidate_id", $this->integer(11)->notNull()->after("company_id"));
        }

        //candidate_id

        $indexExists = false;
        if ($tableSchema) {
            // Check if index exists using raw SQL query
            $indexCheck = $this->db->createCommand("SHOW INDEX FROM `interview_evaluation` WHERE Key_name = 'idx-interview_evaluation-candidate_id'")
                ->queryOne();
            $indexExists = $indexCheck !== false;
        }
        
        if (!$indexExists) {
            $this->createIndex(
                'idx-interview_evaluation-candidate_id', 'interview_evaluation', 'candidate_id'
            );
        }

        $foreignKeys = $tableSchema ? $tableSchema->foreignKeys : [];
        $fkExists = false;
        foreach ($foreignKeys as $fk) {
            if (isset($fk['fk-interview_evaluation-candidate_id']) || 
                (isset($fk[0]) && $fk[0] === 'candidate' && isset($fk['candidate_id']))) {
                $fkExists = true;
                break;
            }
        }
        
        // Check by constraint name
        if (!$fkExists && $tableSchema) {
            $constraints = $this->getDb()->getSchema()->getTableForeignKeys('interview_evaluation');
            foreach ($constraints as $constraint) {
                if ($constraint->name === 'fk-interview_evaluation-candidate_id') {
                    $fkExists = true;
                    break;
                }
            }
        }
        
        if (!$fkExists) {
            $this->addForeignKey(
                'fk-interview_evaluation-candidate_id', 'interview_evaluation', 'candidate_id',
                'candidate', 'candidate_id',
                "CASCADE"
            );
        }
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
