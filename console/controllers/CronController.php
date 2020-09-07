<?php

namespace console\controllers;

use common\models\Company;
use yii\helpers\ArrayHelper;
use yii\helpers\Console;
use common\models\Candidate;


/**
 * All Cron actions related to this project
 */
class CronController extends \yii\console\Controller {

    /**
     * Used for testing only
     */
    public function actionIndex() {
        $this->stdout("Sample Output \n", Console::FG_RED, Console::BOLD);
    }
    
    /**
     * Method called by cron once a day
     */
    public function actionDaily() {

        //check for birthday

        Candidate::birthdayAlert();

        //check for invalid age

        Candidate::ageAlert();

        //check civil ID expiry date

        Candidate::civilIdExpire();

        //check salary transfer not paid
        //Invoice::unpaidAlert();

        // notification to admin regarding
        // company who didn't created transfer after 35 days
        Company::adminPendingPaymentNotification();

    }

    /**
     * Method called by cron every minute
     */
    public function actionEveryMinute() {

        return self::EXIT_CODE_NORMAL;
    }

    /**
     * Method called by cron once a week
     */
    public function actionWeekly(){
        //Code here

        return self::EXIT_CODE_NORMAL;
    }

    /**
     * reviewed candidate profiles and remove duplicate experience data which is same as skill arrised due to
     * coding issue.
     */
    public function actionRemoveDuplicate() {
        $found = 0;
        $allCandidates = Candidate::find()->all();

        foreach ($allCandidates as $candidate) {
            $skills = ArrayHelper::map($candidate->getCandidateSkills()->asArray()->all(),'skill','skill');
            $experience = ArrayHelper::map($candidate->getCandidateExperiences()->asArray()->all(),'experience','experience');

            if (
                $skills && $experience && // to check if we have both values
                (count($skills) == count($experience)) &&  // in case of same copied to other
                count(array_diff_assoc($skills,$experience)) == 0 // check string comparison too
            ) {
                $found++;
                // found duplicate data
                \common\models\CandidateExperience::deleteAll(['candidate_id'=>$candidate->candidate_id]);
                $candidate->updateAlgoliaIndex(false); // update algolia data
            }
        }

        $this->stdout("Total candidate reviewed: ".count($allCandidates).", Total duplicate data removed: ".$found." \n", Console::FG_RED, Console::BOLD);
    }
}
