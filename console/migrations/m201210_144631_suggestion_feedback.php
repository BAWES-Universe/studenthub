<?php

use yii\db\Migration;

/**
 * Class m201210_144631_suggestion_feedback
 */
class m201210_144631_suggestion_feedback extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('note', 'suggestion_uuid', $this->char(60)->null()->after('request_uuid'));

        // creates index for column `suggestion_uuid`
        $this->createIndex(
            'idx-note-suggestion_uuid',
            'note',
            'suggestion_uuid'
        );

        // add foreign key for table `suggestion_uuid`
        $this->addForeignKey(
            'fk-note-suggestion_uuid',
            'note',
            'suggestion_uuid',
            'suggestion',
            'suggestion_uuid'
        );

        //update all notes 

        $suggestions = $this->db->createCommand('select * from suggestion')->queryAll();

        foreach($suggestions as $suggestion) {
            
            $sql = "update note set suggestion_uuid='".$suggestion['suggestion_uuid']."' WHERE request_uuid='".$suggestion['request_uuid']."' AND note_type IN ('Suggested', 'Accepted', 'Rejected')";

            if($suggestion['candidate_id']) {
                $sql .= " AND candidate_id='".$suggestion['candidate_id']."'";
            }

            if($suggestion['fulltimer_uuid']) {
                $sql .= " AND fulltimer_uuid='".$suggestion['fulltimer_uuid']."'";
            }

            $this->db->createCommand($sql)->execute();
        }
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
        echo "m201210_144631_suggestion_feedback cannot be reverted.\n";

        return false;
    }
    */
}
