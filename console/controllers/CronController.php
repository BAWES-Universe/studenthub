<?php

namespace console\controllers;

use admin\models\TransferCandidate;
use common\models\Note;
use common\models\Suggestion;
use common\models\Transfer;
use kartik\mpdf\Pdf;
use Yii;
use yii\base\BaseObject;
use yii\helpers\ArrayHelper;
use yii\helpers\Console;
use common\models\Staff;
use common\models\Candidate;
use common\models\Company;
use common\models\Request;
use common\models\CompanyContact;
use common\models\Contact;
use Segment\Segment;

/**
 * All Cron actions related to this project
 */
class CronController extends \yii\console\Controller {

    /**
     * todo: mail on transfer total mismatch?
     * Check if candidate total mismatch
     */
    public function actionCheckIfCandidateTotalMismatch() {

        $transferCandidates = TransferCandidate::find()
            ->payable()
            ->havingBankInfo()
            ->all();

        $ids = [];

        foreach ($transferCandidates as $transferCandidate) {
            if($transferCandidate->totalPaidToCandidate != $transferCandidate->candidate_total) {
                $ids[] = $transferCandidate->tc_id;
                //$this->stdout( $transferCandidate->tc_id . " \n", Console::FG_RED);
            }
        }

        $this->stdout(implode (', ', $ids) . " \n", Console::FG_RED, Console::BOLD);
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
     * php yii cron/every-minute
     */
    public function actionEveryMinute() {
        Suggestion::suggestionCandidateNotification();
        Suggestion::suggestionFulltimerNotification();
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

        $data['totalExpiredCards'] = \staff\models\Candidate::totalExpiredCards()->count();

        $data['assignedExpiredCivilID'] =  \staff\models\Candidate::assignedExpiredCivilID()->count();

        // # of candidates that need id generated

        $data['id_need_generated'] = Candidate::find()
            ->filterAssigned()
            ->idNeedGenerated()
            ->count();

        //Candidates with profile complete requiring their profiles to be reviewed and approved.

        $data['profileApprovalRequire'] = Candidate::find()
            ->byApprovalStatus(0)
            ->completedProfileWithoutApproval()
            ->count();

        //Candidates are assigned to work but have incomplete profiles.

        $data['incompleteAssignedToWork'] = \staff\models\Candidate::incompleteAssignedToWork()->count();

        $data['missingBankInfo'] = \staff\models\Candidate::withoutBankInfoOrWithPayment()->count();

        $data['requireFollowup'] = Company::companyFollowupCount();

        $data['activeRequests'] = Request::activeRequestCount();

        $data['assignedIdleCandidates'] = \staff\models\Candidate::getAssignedIdleCandidate()->count();
        $data['companyMoreThen40DaysWithoutPayment'] = \staff\models\Company::companiesCountWithNoPaymentIn40Days();
        $data['last40daysNoRequest'] = Company::last40daysWithoutRequest();

        $staffs = Staff::findAll(['deleted'=>'0']);

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
     * php yii cron/payable-candidate-notification
     */
    public function actionPayableCandidateNotification()
    {
        $amount = 0;
        $payableCandidate = [];
        $candidates = TransferCandidate::find()
            ->payable()
            ->havingBankInfo()
            ->all();

        if ($candidates) {
        //https://www.pivotaltracker.com/story/show/176535038
        // to force users to complete there profile
            foreach ($candidates as $candidate) {
                if (
                    $candidate->candidate->isProfileCompleted &&
                    $candidate->candidate->bank_id &&
                    $candidate->transfer_benef_iban &&
                    $candidate->transfer_benef_name &&
                    $candidate->invoiceNumber) {
                    $payableCandidate[] = $candidate;
                    $amount += $candidate->totalPaidToCandidate;
                }
            }
            $amount = number_format($amount, 3);
        }

        if ($payableCandidate && count($payableCandidate) > 0) {

            \moonland\phpexcel\Excel::export([
                'isMultipleSheet' => false,
                'fileName'=>'payable_candidate',
                'savePath' => sys_get_temp_dir() . '/',
                'asAttachment' => true,
                'models' => $payableCandidate,
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

            $subject = "We need to process KWD $amount to ".count($payableCandidate)." people";

            if (YII_ENV != 'prod') {
                $subject = '[Fake] [Ignore] ' . $subject;
            }

            Yii::$app->mailer->htmlLayout = "layouts/studenthub-html";

            $send =  Yii::$app->mailer->compose("report-payment-required",
                [
                    "amount" => $amount,
                    "ppl" => count($payableCandidate),
                    'logo' => Yii::$app->urlManagerStaff->createAbsoluteUrl('../images/logo.png', 'https')
                ])

                ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->params['appName']])
                ->setTo(Yii::$app->params['operationsEmail'])
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

    /**
     * @return bool
     * command to all candidates that have emailed verified,
     * home location of Kuwait, and nationality is NOT Kuwaiti.
     * Email will tell them to update their profile.
     */
    public function actionKuwaitMomCheck() {
        // can use below query also
        // kuwait id is 84
        // SELECT * FROM `candidate` where candidate_email_verification = 1 and country_id != 84 and candidate_area_uuid IN
        // (SELECT `area_uuid` FROM `area` WHERE `country_id` = 84)
        $total = Candidate::kuwaitiNationalityEmail();
        return true;
    }

    /**
     * sync transfers with segment
     */
    public function actionSegmentTransfer() {

        Segment::init('WZc7uvfkM1uhsjT1Eie6PONXFZK3ME15');

        $query = TransferCandidate::find()
            ->with('candidate')
            ->andWhere(['paid' => TransferCandidate::PAID]);
        //->limit(1)

        $count = 0;

        $total = TransferCandidate::find()
            ->andWhere(['paid' => TransferCandidate::PAID])
            ->count();

        Console::startProgress(0, $total);

        foreach($query->batch(100) as $tcs) {

            $count += sizeof($tcs);

            foreach ($tcs as $tc) {

                $name = $tc->candidate->candidate_name ? $tc->candidate->candidate_name : $tc->candidate->candidate_name_ar;

                $datetime = new \DateTime($tc->tc_updated_at);

                Segment::track([
                    'userId' => 'cron',//Yii::$app->user->getId()
                    'event' => 'Candidate Transfer Paid',
                    'properties' => [
                        'tc_id' => $tc->tc_id,
                        'transfer_id' => $tc->transfer_id,
                        'candidate_id' => $tc->candidate_id,
                        'name' => $name,
                        'revenue' => 0 - $tc->getProfit(),
                        'currency' => 'KWD',
                        'transfer_cost' => $tc->transfer_cost,
                        'candidate_total' => $tc->candidate_total,
                        'company_total' => $tc->company_total,
                    ],
                    'timestamp' => $datetime->format('c')
                ]);
            }

            Console::updateProgress($count, $total);
        }

        Segment::flush();
    }

    /**
     * sync suggestion with segment
     */
    public function actionSegmentSuggestion() {

        Segment::init('WZc7uvfkM1uhsjT1Eie6PONXFZK3ME15');

        $query = Suggestion::find();

        $count = 0;

        $total = Suggestion::find()
            ->count();

        Console::startProgress(0, $total);

        foreach($query->batch(100) as $suggestions) {

            $count += sizeof($suggestions);

            foreach ($suggestions as $suggestion) {

                $datetime = new \DateTime($suggestion->suggestion_datetime);

                Segment::track([
                    'userId' => 'cron',
                    'event' => 'Suggestion Created',
                    'properties' => [
                        'suggestion_uuid' => $this->suggestion_uuid,
                        'request_uuid' => $this->request_uuid,
                        'candidate_id' => $this->candidate_id,
                        'fulltimer_uuid' => $this->fulltimer_uuid,
                        'by' => $this->note ? $this->note->created_by : null
                    ],
                    'timestamp' => $datetime->format('c')
                ]);
            }

            Console::updateProgress($count, $total);
        }

        Segment::flush();
    }
}
