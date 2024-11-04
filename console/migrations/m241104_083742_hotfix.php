<?php

use common\models\CandidateWorkingDate;
use common\models\CandidateWorkingHour;
use yii\db\Migration;

/**
 * Class m241104_083742_hotfix
 */
class m241104_083742_hotfix extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn("candidate_working_date", "total_approved", $this->integer(11));
        $this->addColumn("candidate_working_date", "total_rejected", $this->integer(11));
        $this->addColumn("candidate_working_date", "total_pending", $this->integer(11));

        $query = CandidateWorkingDate::find();
          //  ->andWhere(['total_time' => 0]);

        foreach ($query->batch() as $dates) {
            foreach ($dates as $date) {

                //$total_time = $date->getCandidateWorkingHours()
                //    ->sum("total_time");

                $total_time = CandidateWorkingHour::find()->andWhere([
                    "candidate_id" => $date->candidate_id,
                    "store_id" => $date->store_id,
                    "date" => $date->date,
                ])
                    ->sum("total_time");

                $date->total_time = $total_time;

                //if (!$date->end_time) {
                    $date->end_time = $date->getCandidateWorkingHours()
                        ->one()->end_time;
                //}

                //set stats

                $date->total_approved = $date->getCandidateWorkingHours()
                    ->andWhere(['status' => CandidateWorkingHour::STATUS_APPROVED])
                    ->count();

                $date->total_rejected = $date->getCandidateWorkingHours()
                    ->andWhere(['status' => CandidateWorkingHour::STATUS_REJECTED])
                    ->count();

                $date->total_pending = $date->getCandidateWorkingHours()
                    ->andWhere(['status' => CandidateWorkingHour::STATUS_PENDING])
                    ->count();

                $date->save(false);
            }
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
        echo "m241104_083742_hotfix cannot be reverted.\n";

        return false;
    }
    */
}
