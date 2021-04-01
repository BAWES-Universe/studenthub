<?php

use yii\db\Migration;
use \yii\helpers\ArrayHelper;
use \common\models\Candidate;
/**
 * Class m210401_100328_update_candidate_job_status
 */
class m210401_100328_update_candidate_job_status extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $candidates = Candidate::find()
            ->filterAssigned()
            ->andWhere(['candidate_job_search_status' => Candidate::NOT_LOOKING_FOR_JOB])
            ->all();

        foreach ($candidates as $candidate) {
            $candidate->candidate_job_search_status = Candidate::ACTIVELY_LOOKING_FOR_JOB;
            $candidate->save(false);
            $candidate->updateAlgoliaIndex(false); // update algolia data
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m210401_100328_update_candidate_job_status cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210401_100328_update_candidate_job_status cannot be reverted.\n";

        return false;
    }
    */
}
