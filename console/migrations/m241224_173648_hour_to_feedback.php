<?php

use common\models\CandidateWorkingHourAppealUpdates;
use yii\db\Migration;

use common\models\CandidateWorkLogFeedback;
use common\models\CandidateWorkingHour;

/**
 * Class m241224_173648_hour_to_feedback
 */
class m241224_173648_hour_to_feedback extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn("candidate_working_hour_appeal_updates", "is_new",
            $this->boolean()->defaultValue(true));

        $this->addColumn("candidate_working_hour_appeal_updates", "created_by",
            $this->integer(11)->null()->after("updated_at"));

        $this->addColumn("candidate_working_hour_appeal_updates", "updated_by",
            $this->integer(11)->null()->after("created_by"));

        $this->addColumn("candidate_working_hour", "cwlf_uuid", $this->char(60)->null());

        $query = \common\models\CandidateWorkingHour::find()
            ->andWhere(['!=', 'status', \common\models\CandidateWorkingHour::STATUS_PENDING])
            ->andWhere(new \yii\db\Expression("cwlf_uuid IS NULL"));

        foreach ($query->batch() as $hours) {

            foreach ($hours as $hour) {

                $feedback = CandidateWorkLogFeedback::find()
                    ->andWhere(['candidate_working_hour_uuid' => $hour->candidate_working_hour_uuid])
                    ->one();

                if (!$feedback) {
                    $feedback = \common\models\CandidateWorkLogFeedback::find()
                        ->andWhere([
                            "candidate_id" => $hour->candidate_id,
                            "store_id" => $hour->store_id,
                            "date" => $hour->date,
                        ])
                        ->one();
                }

                if (!$feedback) {
                    echo "no feedback for #". $hour->candidate_working_hour_uuid . PHP::EOF;
                }

                CandidateWorkingHour::updateAll([
                    'cwlf_uuid' => $feedback->cwlf_uuid
                ], [
                    'candidate_working_hour_uuid' => $hour->candidate_working_hour_uuid,
                ]);
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
        echo "m241224_173648_hour_to_feedback cannot be reverted.\n";

        return false;
    }
    */
}
