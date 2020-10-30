<?php

namespace console\controllers;

use admin\models\TransferCandidate;
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
        Candidate::merge (1, 2);
        $this->stdout("Sample Output \n", Console::FG_RED, Console::BOLD);
    }
    
    /**
     * Method called by cron once a day
     */
    public function actionDaily() {

        //check for birthday

        Candidate::birthdayAlert();

        //check civil ID expiry date

        Candidate::civilIdExpire();

        //check salary transfer not paid
        //Invoice::unpaidAlert();

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

        $data['assignedExpiredCivilID'] =  Candidate::find()
            ->civilIdExpired()
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

    /**
     * Implement cron function that checks if Payable candidates with bank info avail > 0,
     * it sends an email to khalid@bawes.net telling to process transfer.
     *  Confirm values/excel shown are only for those candidates which are payable and bank info avail.
     */
    public function actionPayableCandidateNotification()
    {
        $amount = 0;
        $candidates = TransferCandidate::find()
            ->payable()
            ->andWhere(new \yii\db\Expression('transfer_candidate.bank_id IS NOT NULL'))
            ->all();

        if ($candidates) {
            foreach ($candidates as $transfer) {
                if($transfer->candidate->bank_id && $transfer->transfer_benef_iban && $transfer->transfer_benef_name) {
                    $amount += $transfer->transfer->getRemainingPaymentTransferTotal();
                }
            }
        }

        if ($candidates && count($candidates) > 0) {

            \moonland\phpexcel\Excel::export([
                'isMultipleSheet' => false,
                'fileName'=>'payable_candidate',
                'savePath' => sys_get_temp_dir() . '/',
                'asAttachment' => true,
                'models' => $candidates,
                'columns' => [
                    'tc_id',
                    'transfer_id',
                    'candidate_id',
                    'candidate.candidate_name',
                    [
                        'attribute'=>'Beneficiary name',
                        'label'=>'Beneficiary name',
                        'value'=>function($data) {
                            return $data->candidate->bank_account_name;
                        }
                    ],
                    'candidate.candidate_email',
                    'candidate.store.company.company_name',
                    'candidate.store.store_name',
                    'hours',
                    'candidate_hourly_rate',
                    [
                        'attribute'=>'bonus',
                        'label'=>'Candidate Bonus',
                        'value' => function($data){
                            return $data->bonus - $data->bonus_commission;
                        }
                    ],
                    'transfer_cost',
                    [
                        'attribute'=>'candidate_total',
                        'value' => function($data){
                            return $data->totalPaidToCandidate;
                        }
                    ],
                    'candidate.candidate_iban',
                    'candidate.bank.bank_name'
                ]
            ]);

            Yii::$app->mailer->htmlLayout = 'layouts/html';

            $mimeTypes = [
                'xls' => 'application/vnd.ms-excel',
                'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            ];
            $fileName = 'payable_candidate.xlsx';

            $file = sys_get_temp_dir() . '/'.$fileName;

            $extension = pathinfo($file, PATHINFO_EXTENSION);

            $subject = "Payable candidates Detail";

            if (YII_ENV != 'prod') {
                $subject = '[Fake] [Ignore] ' . $subject;
            }

            Yii::$app->mailer->htmlLayout = "layouts/studenthub-html";

            $send =  Yii::$app->mailer->compose("report-payment-required",
                [
                    "amount" => $amount,
                    "ppl" => count($candidates),
                    'logo' => Yii::$app->urlManagerStaff->createAbsoluteUrl('../images/logo.png', 'https')
                ])

                ->setFrom([Yii::$app->params['invoiceFrom'] => Yii::$app->params['appName']])
                ->setTo(Yii::$app->params['invoiceFrom'])
                ->setSubject($subject)
                ->attachContent(file_get_contents($file), [
                    'fileName' => $fileName,
                    'contentType' => $mimeTypes[$extension]
                ])
                ->send();

            @unlink(sys_get_temp_dir() . '/' . $fileName);
            return $send;
        }
        return true;
    }
}
