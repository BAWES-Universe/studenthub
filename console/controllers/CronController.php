<?php

namespace console\controllers;

use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Console;
use common\models\Staff;
use common\models\Candidate;
use common\models\Company;
use common\models\Request;


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
        return 0;
    }

    /**
     * Method called by cron once a week
     */
    public function actionWeekly(){
        //Code here

        return 0;
    }

    /**
     * reviewed candidate profiles and remove duplicate experience data which is same as skill raised due to
     * coding issue.
     */
    public function actionRemoveDuplicate() {
        $found = [];
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

    public function actionSummary() {
        // # of candidates requiring ID card to be renewed

        $data = [
            'date' => date('F j, Y'),
            "logo" => Yii::$app->urlManagerStaff->createAbsoluteUrl('../images/logo.png', 'https'),
        ];

        $data['totalExpiredCards'] = Candidate::find()
            ->idExpired()
            ->filterAssigned() // only candidate with assigned work
            ->notDeleted()
            ->count();

        // # of candidates that need id generated

        /*$result['id_need_generated'] = Candidate::find()
            ->notDeleted()
            ->filterAssigned()
            ->idNeedGenerated()
            ->count();*/

        //Candidates with profile complete requiring their profiles to be reviewed and approved.

        $data['profileApprovalRequire'] = Candidate::find()
            ->notDeleted()
            ->byApprovalStatus(0)
            ->completedProfileWithoutApproval()
            ->count();

        //Candidates are assigned to work but have incomplete profiles.

        $data['incompleteAssignedToWork'] = Candidate::find()
            ->filterAssigned()
            ->notDeleted()
            ->incompletedProfile()
            ->count();

        $data['missingBankInfo'] = Candidate::neededBankInfo();

        $data['requireFollowup'] = Company::companyFollowupCount();

        $data['totalPendingRequests'] = Request::find()
            ->filterWhere(['request_status' => Request::STATUS_PENDING])
            ->count();

        $data['activeRequests'] = Request::find()
            ->filterWhere(['request_status' => Request::STATUS_STARTED])
            ->count();

        $staffs = Staff::find()->notDeleted()->all();

        $emails = ArrayHelper::getColumn ($staffs, 'staff_email');

        return Yii::$app->mailer->compose([
            'html' => 'summary',
        ], $data)
            ->setFrom([\Yii::$app->params['supportEmail'] => \Yii::$app->params['appName']])
            ->setTo(Yii::$app->params['invoiceFrom'])
            ->setCc($emails)
            ->setSubject('Morning Report for ' . date('F j, Y'))
            ->send();
    }
}
