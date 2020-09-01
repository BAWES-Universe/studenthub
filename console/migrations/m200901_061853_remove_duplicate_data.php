<?php

use yii\db\Migration;
use \yii\helpers\ArrayHelper;
use \common\models\Candidate;
/**
 * Class m200901_061853_remove_duplicate_data
 */
class m200901_061853_remove_duplicate_data extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {

        $allCandidates = Candidate::find()->all();
        foreach ($allCandidates as $candidate) {
            $skills = ArrayHelper::map($candidate->getCandidateSkills()->asArray()->all(),'skill','skill');
            $experience = ArrayHelper::map($candidate->getCandidateExperiences()->asArray()->all(),'experience','experience');
            if (
                $skills && $experience && // to check if we have both values
                (count($skills) == count($experience)) &&  // in case of same copied to other
                count(array_diff_assoc($skills,$experience)) == 0 // check string comparison too
            ) {
                // found duplicate data
                \common\models\CandidateExperience::deleteAll(['candidate_id'=>$candidate->candidate_id]);
                $candidate->updateAlgoliaIndex(false); // update algolia data
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m200901_061853_remove_duplicate_data cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200901_061853_remove_duplicate_data cannot be reverted.\n";

        return false;
    }
    */
}
