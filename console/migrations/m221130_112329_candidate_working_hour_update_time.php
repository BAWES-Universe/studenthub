<?php

use yii\db\Migration;

/**
 * Class m221130_112329_candidate_working_hour_update_time
 */
class m221130_112329_candidate_working_hour_update_time extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn('candidate_working_hour', 'start_time', $this->dateTime());
        $this->alterColumn('candidate_working_hour', 'end_time', $this->dateTime());

        $records = Yii::$app->db->createCommand('select * from candidate_working_hour')->queryAll();

        foreach ($records as $record) {

            $candidate_working_hour_uuid = $record['candidate_working_hour_uuid'];
            $updateTime = "UPDATE candidate_working_hour";
            $updateTime .= " SET start_time = CONCAT(`date`, ' ',DATE_FORMAT(start_time, '%H:%i:%s')) ";

            if (!is_null($record['total_time']) && $record['total_time'] < 0) {
                $updateTime .= ", end_time = CONCAT(DATE_ADD(date, INTERVAL 1 DAY), ' ',DATE_FORMAT(end_time, '%H:%i:%s')) ";
            } else {
                $updateTime .= ", end_time = CONCAT(`date`, ' ',DATE_FORMAT(end_time, '%H:%i:%s')) ";
            }
            $updateTime .= ", total_time = TIMESTAMPDIFF(SECOND,start_time,end_time) ";

            $updateTime .= " where candidate_working_hour_uuid='$candidate_working_hour_uuid'";
            Yii::$app->db->createCommand($updateTime)->execute();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        return true;
    }

}
