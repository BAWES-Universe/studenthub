<?php

use yii\db\Migration;

class m170803_071117_candidate_history_table_data_migration extends Migration
{
    public function safeUp()
    {
        $candidates = \common\models\Candidate::find()
            ->where(['>=', 'store_id', 1])->all();
        foreach ($candidates as $candidate) {
            $model = new \common\models\CandidateWorkHistory;
            $model->candidate_id = $candidate->candidate_id;
            $model->store_id = $candidate->store_id;
            $model->candidate_hourly_rate = $candidate->candidate_hourly_rate;
            $model->start_date = $candidate->candidate_updated_at;
            $model->save();
        }
    }

    public function safeDown()
    {
        echo "m170803_071117_candidate_history_table_data_migration cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m170803_071117_candidate_history_table_data_migration cannot be reverted.\n";

        return false;
    }
    */
}
