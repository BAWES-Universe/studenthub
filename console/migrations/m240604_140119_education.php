<?php

use common\models\Candidate;
use common\models\CandidateEducation;
use yii\db\Migration;
use yii\helpers\Console;

/**
 * Class m240604_140119_education
 */
class m240604_140119_education extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $query = Candidate::find()
            ->andWhere(new \yii\db\Expression("university_id IS NOT NULL"))
            ->andWhere(['deleted' => 0]);

        $count = 0;

        //$total = $query->count();

        //Console::startProgress(0, $total);

        $educations = [];

        foreach ($query->batch(100) as $candidates) {
            foreach ($candidates as $candidate) {

                $count++;
                //Console::updateProgress($count, $total);

                $educations[] = [
                    "education_uuid" => 'education_'.$count,
                    "candidate_id" => $candidate->candidate_id,
                    "university_id" => $candidate->university_id,
                    "created_at" => new \yii\db\Expression("NOW()"),
                    "updated_at" => new \yii\db\Expression("NOW()")
                ];
            }

            Yii::$app->db->createCommand()->batchInsert('candidate_education',
                ['education_uuid', 'candidate_id', "university_id", 'created_at', "updated_at"],
                $educations)->execute();

            $educations = [];
        }

        echo "Migration completed \n";

        //todo: comment out once published as it will call algolia api
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
        echo "m240604_140119_education cannot be reverted.\n";

        return false;
    }
    */
}
