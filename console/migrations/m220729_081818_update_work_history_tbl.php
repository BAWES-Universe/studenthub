<?php

use yii\db\Migration;

/**
 * Class m220729_081818_update_work_history_tbl
 */
class m220729_081818_update_work_history_tbl extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $sql = "SELECT * FROM `note` where candidate_id is not null and (note_text like 'Assigned to work at%' or note_text like 'Assigned to %') and note_type = 'Internal Note'";
        $notes = $this->db->createCommand($sql)->queryAll();

        foreach($notes as $note) {
            $candidate_id = $note['candidate_id'];
            $company_id = $note['company_id'];
            $staff_id = $note['created_by'];
            $date = $note['note_created_datetime']? date('Y-m-d',strtotime($note['note_created_datetime'])): null;

            if ($candidate_id) {
                $sql = "update `candidate_work_history` set staff_id = '$staff_id' WHERE `candidate_id` = '$candidate_id' AND `start_date` = '$date'";
                $this->db->createCommand($sql)->execute();
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220729_081818_update_work_history_tbl cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220729_081818_update_work_history_tbl cannot be reverted.\n";

        return false;
    }
    */
}
