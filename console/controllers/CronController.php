<?php

namespace console\controllers;

use admin\models\Expense;
use admin\models\TransferCandidate;
use common\models\CandidateStats;
use common\models\CompanyStats;
use common\models\DailyStandupQuestion;
use common\models\MailLog;
use common\models\RequestInterview;
use common\models\StaffWorkSession;
use common\models\Suggestion;
use common\models\Currency;
use common\models\VendorCampaign;
use Yii;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Console;
use common\models\Candidate;
use common\models\Company;
use common\models\Request;


/**
 * All Cron actions related to this project
 */
class CronController extends \yii\console\Controller {

    public function actionIndex() {

        //https://studenthub-uploads-dev-server.s3.amazonaws.com/photos/MBK-Civil-ID-1600531990157.png

        //$r = Yii::$app->idExpiryDateExtractor
        //    ->extractExpiryDate("photos/MBK-Civil-ID-1600531990157.png");

        //print_r($r);

        // Yii::$app->smsComponent->sendSms(8758702738, "test");

        /*Yii::$app->mailer->compose ([
            'text' => 'test',
        ])
            ->setFrom ([\Yii::$app->params['supportEmail'] => \Yii::$app->params['appName']])
            ->setSubject ('Test email')
            ->setTo ("kathrechakrushn@gmail.com")
            //->setCc($contactEmails)
            ->send ();*/
    }

    /**
     * add civil id expiry date from document
     * @return void
     */
    public function actionFillCivilIdExpiryDate() {

        $query = Candidate::find()
            //->andWhere(['candidate_id' => 1698])
            ->notDeleted()
            ->filterAssigned()
            ->andWhere(new Expression("candidate_civil_photo_front IS NOT NULL AND 
                candidate_civil_expiry_date IS NULL"));

        $count = 0;

        $total = $query->count();

        Console::startProgress(0, $total);

        foreach ($query->batch(100) as $candidates) {
            foreach ($candidates as $candidate) {

                $count++;
                Console::updateProgress($count, $total);

                $filePath = str_contains($candidate->candidate_civil_photo_front, "photos/") ?
                    $candidate->candidate_civil_photo_front: "photos/" . $candidate->candidate_civil_photo_front;

                $response =
                    Yii::$app->idExpiryDateExtractor
                        ->extractExpiryDate($filePath);

                if ($response['operation'] == "success") {

                    $date = array_pop($response['matches']);
                    $dateTime = strtotime(str_replace("/", "-", $date));

                    Candidate::updateAll([
                        "candidate_civil_expiry_date" => date("Y-m-d", $dateTime)
                    ], [
                        'candidate_id' => $candidate->candidate_id
                    ]);

                    $this->stdout(date("Y-m-d", $dateTime) ." for #" . $candidate->candidate_id . " \n",
                        Console::FG_RED, Console::BOLD);
                }
            }
        }
    }

    /**
     * add civil id expiry date from document
     * @return void
     */
    public function actionFillCivilIdExpiryDateNotAssigned() {

        $query = Candidate::find()
            //->andWhere(['candidate_id' => 1698])
            ->notDeleted()
            ->filterNotAssigned()
            ->andWhere(new Expression("candidate_civil_photo_front IS NOT NULL AND 
                candidate_civil_expiry_date IS NULL"));

        $count = 0;

        $total = $query->count();

        Console::startProgress(0, $total);

        foreach ($query->batch(100) as $candidates) {
            foreach ($candidates as $candidate) {

                $count++;
                Console::updateProgress($count, $total);

                $filePath = str_contains($candidate->candidate_civil_photo_front, "photos/") ?
                    $candidate->candidate_civil_photo_front: "photos/" . $candidate->candidate_civil_photo_front;

                $response =
                    Yii::$app->idExpiryDateExtractor
                        ->extractExpiryDate($filePath);

                if ($response['operation'] == "success") {

                    $date = array_pop($response['matches']);
                    $dateTime = strtotime(str_replace("/", "-", $date));

                    Candidate::updateAll([
                        "candidate_civil_expiry_date" => date("Y-m-d", $dateTime)
                    ], [
                        'candidate_id' => $candidate->candidate_id
                    ]);

                   // $this->stdout(date("Y-m-d", $dateTime) ." for #" . $candidate->candidate_id . " \n",
                   //     Console::FG_RED, Console::BOLD);
                }
            }
        }
    }

    /**
     * check if ID already expired
     * @return void
     */
    public function actionValidateCivilId() {

        $query = Candidate::find()
            ->notDeleted()
            ->filterAssigned()
            ->andWhere(new Expression("candidate_civil_photo_front IS NOT NULL AND 
                candidate_civil_expiry_date IS NOT NULL AND 
                DATE(candidate_civil_expiry_date) > DATE(NOW())"));

        $count = 0;

        $total = $query->count();

        Console::startProgress(0, $total);

        foreach ($query->batch(100) as $candidates) {
            foreach ($candidates as $candidate) {

                $count++;
                Console::updateProgress($count, $total);

                $response = Yii::$app->idExpiryDateExtractor
                    ->extractExpiryDate("photos/" . $candidate->candidate_civil_photo_front);

                if ($response['operation'] == "success") {

                    $date = array_pop($response['matches']);
                    $dateTime = strtotime(str_replace("/", "-", $date));
                    //$date = end($response['matches']);

                    /*if($candidate->candidate_civil_expiry_date &&
                        $dateTime <= strtotime($candidate->candidate_civil_expiry_date)) {
                        continue;
                    }*/

                    //if correct date was added

                    if($dateTime == strtotime($candidate->candidate_civil_expiry_date)) {
                        continue;
                    }

                    Candidate::updateAll([
                        "candidate_civil_expiry_date" => date("Y-m-d", $dateTime)
                    ], [
                        'candidate_id' => $candidate->candidate_id
                    ]);

                   //$this->stdout(date("Y-m-d", $dateTime) ." for #" . $candidate->candidate_id . " \n",
                   //     Console::FG_RED, Console::BOLD);
                }
            }
        }
    }

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

        //Candidate::birthdayAlert();

        //check civil ID expiry date

        //Candidate::civilIdExpire();

        //check salary transfer not paid
        //Invoice::unpaidAlert();

        DailyStandupQuestion::standupReport();
    }

    /**
     * Method called by cron every minute
     * php yii cron/every-minute
     */
    public function actionEveryMinute() {
        Suggestion::suggestionCandidateNotification();
        Suggestion::suggestionFulltimerNotification();
    }

    /* todo: user separate email server for marketing?
    public function actionProcessCampaign()
    {
        $campaigns = EmailCampaign::find()
            ->andWhere(['status' => EmailCampaign::STATUS_READY])
            ->all();

        foreach ($campaigns as $campaign) {
            $campaign->process();
        }

        $this->stdout( sizeof($campaigns) . " Email Campaign processed \n", Console::FG_RED, Console::BOLD);
    }*/

    /**
     * Method called by cron once a week
     */
    public function actionWeekly() {
        //Code here

        return 0;
    }

    /**
     * Method called by cron at the mid of month
     */
    public function actionMidMonth() {

        //todo: stop until we found culprit
        //Candidate::notifyMissingBankInfo();

        //Candidate::notifyCivilIDExpiring();

        return 0;
    }

    /**
     * Method called by cron at the end of month
     */
    public function actionEndOfMonth() {

        Company::requestForAttendance();

        //todo: stop until we found culprit

        //Candidate::notifyMissingBankInfo();

        //Candidate::notifyCivilIDExpiring();

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

        $data['interviewScheduledToday'] = RequestInterview::find()
            ->joinWith(['request'])
            ->andWhere([
                'request_interview.status' => RequestInterview::STATUS_SCHEDULED
            ])
            ->andWhere(new Expression('DATE(interview_at) = DATE(NOW())'))
            ->andWhere(['NOT IN', 'request.request_status', [
                Request::STATUS_DELIVERED,
                Request::STATUS_FINISHED,
                Request::STATUS_CANCELLED
            ]])
            ->count();

        //$staffs = Staff::findAll(['deleted'=>'0', 'staff_notification' => 1]);
        $staffs = \common\models\Staff::find()
            ->joinWith('staffNotifications')
            ->andWhere(['staff.deleted' => false, 'staff_notification' => true, 'permission' => "morning-report"])
            ->all();

        $emails = ArrayHelper::getColumn ($staffs, 'staff_email');

        $ml = new MailLog();
        $ml->to = Yii::$app->params['invoiceFrom'];
        $ml->from = \Yii::$app->params['supportEmail'];
        $ml->subject = 'Morning Report for ' . date('F j, Y');
        $ml->save();

        $mailer = Yii::$app->mailer->compose([
            'html' => 'summary',
        ], $data)
            ->setFrom([\Yii::$app->params['supportEmail'] => \Yii::$app->params['appName']])
            ->setTo(Yii::$app->params['invoiceFrom'])
            ->setCc($emails)
            ->setSubject('Morning Report for ' . date('F j, Y'));

        try {
            return $mailer->send();
        } catch (\Swift_TransportException $e) {
            Yii::error($e->getMessage(), "email_campaign");
        }
    }

    /**
     * Implement cron function that checks if Payable candidates with bank info avail > 0,
     * it sends an email to khalid@bawes.net telling to process transfer.
     *  Confirm values/excel shown are only for those candidates which are payable and bank info avail.
     * php yii cron/payable-candidate-notification
     */
    public function actionPayableCandidateNotification()
    {
        $currencies = Currency::find()
            ->andWhere(['status' => 1])
            ->all();

        foreach ($currencies as $currency) {

            $amount = 0;
            $payableCandidate = [];

            $transferCandidates = TransferCandidate::find()
                ->with(['transfer', 'candidate'])
                ->payable()
                ->havingBankInfo()
                ->andWhere(['transfer.currency_code' => $currency->code])
                ->all();

            //https://www.pivotaltracker.com/story/show/176535038
            // to force users to complete there profile
            foreach ($transferCandidates as $transferCandidate) {
                if (
                    $transferCandidate->candidate &&
                    $transferCandidate->candidate->isProfileCompleted &&
                    $transferCandidate->candidate->bank_id &&
                    $transferCandidate->transfer_benef_iban &&
                    $transferCandidate->transfer_benef_name &&
                    $transferCandidate->invoiceNumber
                ) {
                    $payableCandidate[] = $transferCandidate;
                    $amount += $transferCandidate->totalPaidToCandidate;
                }

                /*if(!$transferCandidate->candidate) {
                    Yii::error("Candidate profile not found for payable candidate notification #" .
                     $transferCandidate->tc_id);
                }*/
            }

            $amount = number_format($amount, 3);

            if ($payableCandidate && count($payableCandidate) > 0) {

                \moonland\phpexcel\Excel::export([
                    'isMultipleSheet' => false,
                    'fileName' => 'payable_candidate',
                    'savePath' => sys_get_temp_dir() . '/',
                    'asAttachment' => true,
                    'models' => $payableCandidate,
                    'columns' => [
                        'tc_id',
                        'transfer_id',
                        'candidate_id',
                        'candidate.candidate_name',
                        [
                            'attribute' => 'Beneficiary name',
                            'label' => 'Beneficiary name',
                            'value' => function ($data) {
                                return $data->candidate->bank_account_name;
                            }
                        ],
                        'candidate.candidate_email',
                        'candidate.store.company.company_name',
                        'candidate.store.store_name',
                        'hours',
                        'candidate_hourly_rate',
                        [
                            'attribute' => 'bonus',
                            'label' => 'Candidate Bonus',
                            'value' => function ($data) {
                                return $data->bonus - $data->bonus_commission;
                            }
                        ],
                        'transfer_cost',
                        [
                            'attribute' => 'candidate_total',
                            'value' => function ($data) {
                                return $data->totalPaidToCandidate;
                            }
                        ],
                        //  'transfer.currency_code',
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

                $file = sys_get_temp_dir() . '/' . $fileName;

                $extension = pathinfo($file, PATHINFO_EXTENSION);

                $subject = "We need to process " . $currency->code . " " . $amount . " to " . count($payableCandidate) . " people";

                if (YII_ENV != 'prod') {
                    $subject = '[Fake] [Ignore] ' . $subject;
                }

                Yii::$app->mailer->htmlLayout = "layouts/studenthub-html";

                $ml = new MailLog();
                $ml->to = Yii::$app->params['operationsEmail'];
                $ml->from = \Yii::$app->params['supportEmail'];
                $ml->subject = $subject;
                $ml->save();

                $send = Yii::$app->mailer->compose("report-payment-required",
                    [
                        "amount" => $amount,
                        "currency_code" => $currency->code,
                        "ppl" => count($payableCandidate),
                        'logo' => Yii::$app->urlManagerStaff->createAbsoluteUrl('../images/logo.png', 'https')
                    ])
                    ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->params['appName']])
                    ->setTo(Yii::$app->params['operationsEmail'])
                    ->setSubject($subject)
                    ->attachContent(file_get_contents($file), [
                        'fileName' => $fileName,
                        'contentType' => $mimeTypes[$extension]
                    ]);

                try {
                    $send->send();
                } catch (\Swift_TransportException $e) {
                    Yii::error($e->getMessage(), "email_campaign");
                }

                @unlink(sys_get_temp_dir() . '/' . $fileName);

                //return $send;
            }
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
        Candidate::kuwaitiNationalityEmail();
        return true;
    }

    /**
     * sync transfers with segment
     */
    public function actionSegmentTransfer() {

        $query = TransferCandidate::find()
            ->with('candidate')
            ->andWhere(['paid' => TransferCandidate::PAID]);
        //->limit(17545)
        //->offset(8800);

        $count = 0;

        $total = TransferCandidate::find()
            ->andWhere(['paid' => TransferCandidate::PAID])
            ->count();

        Console::startProgress(0, $total);

        foreach($query->batch(100) as $tcs) {

            $count += sizeof($tcs);

            foreach ($tcs as $tc) {

                if($tc->candidate)
                    $name = $tc->candidate->candidate_name ? $tc->candidate->candidate_name : $tc->candidate->candidate_name_ar;
                else
                    $name = null;

                $datetime = new \DateTime($tc->tc_updated_at);

                Yii::$app->eventManager->track(
                    'Candidate Transfer Paid',
                    [
                        'tc_id' => $tc->tc_id,
                        'transfer_id' => $tc->transfer_id,
                        'candidate_id' => $tc->candidate_id,
                        'name' => $name,
                        'revenue' => $tc->getProfit(),
                        'currency' => $tc->transfer->currency_code,
                        'transfer_cost' => $tc->transfer_cost,
                        'candidate_total' => $tc->candidate_total,
                        'company_total' => $tc->company_total,
                    ],
                    $datetime->format('c'),
                    'cron'
                );
            }

            Console::updateProgress($count, $total);
        }

        Yii::$app->eventManager->flush();
    }

    /**
     * sync suggestion with segment
     */
    public function actionSegmentSuggestion() {

        $query = Suggestion::find();

        $count = 0;

        $total = Suggestion::find()
            ->count();

        Console::startProgress(0, $total);

        foreach($query->batch(100) as $suggestions) {

            $count += sizeof($suggestions);

            foreach ($suggestions as $suggestion) {

                $datetime = new \DateTime($suggestion->suggestion_datetime);

                $staff = $suggestion->getCreatedBy()->one();

                if($suggestion->candidate)
                    $name = $suggestion->candidate->candidate_name ? $suggestion->candidate->candidate_name : $suggestion->candidate->candidate_name_ar;
                else
                    $name = null;

                if($suggestion->fulltimer)
                    $fulltimer = $suggestion->fulltimer->fulltimer_name;
                else
                    $fulltimer = null;

                Yii::$app->eventManager->track('Suggestion Created',
                    [
                        'suggestion_uuid' => $suggestion->suggestion_uuid,
                        'request_uuid' => $suggestion->request_uuid,
                        'candidate_id' => $suggestion->candidate_id,
                        'candidate' => $name,
                        'fulltimer_uuid' => $suggestion->fulltimer_uuid,
                        'fulltimer' => $fulltimer,
                        'staff_id' => $suggestion->note ? $suggestion->note->created_by : null,
                        'staff_name' => $staff? $staff->staff_name: null
                    ],
                    $datetime->format('c')
                );
            }

            Console::updateProgress($count, $total);
        }

        Yii::$app->eventManager->flush();
    }

    /**
     * sync expense with segment
     */
    public function actionSegmentExpense() {

        $query = Expense::find();

        $count = 0;

        $total = Expense::find()
            ->count();

        Console::startProgress(0, $total);

        foreach($query->batch(100) as $expenses) {

            $count += sizeof($expenses);

            foreach ($expenses as $expense) {

                $datetime = $expense->transaction_datetime?
                    new \DateTime($expense->transaction_datetime): new \DateTime($expense->created_at);

                Yii::$app->eventManager->track(
                    'Expense Added',
                    [
                        'expense_uuid' => $expense->expense_uuid,
                        'title' => $expense->title,
                        'type' => $expense->type,
                        'detail' => $expense->detail,
                        'amount' => $expense->amount,
                        'currency' => 'KWD',
                        'revenue' => $expense->amount,//just for beautiful graphs
                        'created_by' => $expense->createdBy?$expense->createdBy->admin_name: null
                    ],
                    $datetime->format('c')
                );
            }

            Console::updateProgress($count, $total);
        }

        Yii::$app->eventManager->flush();
    }

    /*
    public function actionTest()
    {
        $mailer = \Yii::$app->mailer->compose([
            'message' => 'test',
        ])
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->params['appName']])
            ->setReplyTo(['a.aljasser@trolley.com.kw' => 'Plugn'])//\Yii::$app->params['supportEmail']
            ->setTo(['kathrechakrushn@gmail.com'])
            ->setSubject('Test email');

        try {
            return $mailer->send();
        } catch (\Swift_TransportException $e) {
            Yii::error($e->getMessage(), "email_campaign");
        }
    }*/

    /*
     * php yii cron/check-daily-attendance
     * */
    public function actionCheckDailyAttendance() {

        $day = date('l');
        if ($day == 'Friday' || $day == 'Saturday') {
            return true;
        }

        $currentlyWorking = StaffWorkSession::find()
            ->andWhere(new Expression("DATE(created_at) = CURDATE()"))
            ->groupBy('staff_id')
            ->asArray()
            ->all();

        $query = \common\models\Staff::find()
            ->joinWith('staffNotifications')
            ->andWhere(['staff.deleted' => false, 'staff_notification' => true, 'permission' => "daily-attendance-notification"]);

        if ($day != 'Friday' && $day != 'Saturday') {
            $query->andWhere(['NOT IN', 'staff_id', $currentlyWorking]);
        }

        $query->andWhere(['staff.deleted'=>0]);

        $staffList = $query->all();
        $count = 0;
        Console::startProgress(0, count($staffList));
        if (count($staffList) > 0) {
            foreach($staffList as $staff) {
                $count ++;

                $ml = new MailLog();
                $ml->to = $staff->staff_email;
                $ml->from = \Yii::$app->params['supportEmail'];
                $ml->subject = "Daily Attendance notification";
                $ml->save();

                $mailer = Yii::$app->mailer->compose("staff/timer-notification",
                    [
                        "logo" => Yii::$app->urlManagerStaff->createAbsoluteUrl('../images/logo.png', 'https'),
                        "staff" => $staff,
                    ])
                    ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->params['appName']])
                    ->setTo($staff->staff_email)
                    ->setSubject("Daily Attendance notification");

                try {
                    $mailer->send();
                } catch (\Swift_TransportException $e) {
                    Yii::error($e->getMessage(), "email_campaign");
                }

                Console::updateProgress($count, count($staffList));
            }
        }
    }

    /**
     * @return void
     */
    public function actionUpdateCandidateStats() {

        $candidateQuery = \common\models\Candidate::find();

        $count = 0;
        $total = $candidateQuery->count();
        Console::startProgress(0, $total);

        foreach ($candidateQuery->batch() as $candidates) {
            foreach ($candidates as $candidate) {

                $rows = \common\models\TransferCandidate::find()
                    ->andWhere(['candidate_id' => $candidate->candidate_id, "paid" => \common\models\TransferCandidate::PAID])
                    ->groupBy("currency_code")
                    ->select("currency_code, SUM(((company_hourly_rate - candidate_hourly_rate) * hours) - 
                        transfer_cost + bonus_commission) AS profit")
                    ->asArray()
                    ->all();

                foreach ($rows as $row)
                {
                    // check if available

                    $stat = CandidateStats::find()
                        ->andWhere(['candidate_id' => $candidate->candidate_id, "currency_code" => $row["currency_code"]])
                        ->one();

                    // update if available

                    if($stat) {
                        $stat->updateCounters(['total_revenue' => $row['profit']]);
                    } else { // else add
                        $stat = new CandidateStats;
                        $stat->candidate_id = $candidate->candidate_id;
                        $stat->currency_code = $row["currency_code"];
                        $stat->total_revenue = $row['profit'];
                        if(!$stat->save()) {
                            echo print_r($stat->errors, true); die();
                            break;
                        }
                    }
                }

                $count++;
                Console::updateProgress($count, $total);
            }
        }
    }

    /**
     * @return void
     */
    public function actionUpdateCompanyStats() {

        $companyQuery = \common\models\Company::find();

        $count = 0;
        $total = $companyQuery->count();
        Console::startProgress(0, $total);

        foreach ($companyQuery->batch() as $companies) {
            foreach ($companies as $company) {

                $rows = \common\models\TransferCandidate::find()
                    ->andWhere(['company_id' => $company->company_id, "paid" => \common\models\TransferCandidate::PAID])
                    ->groupBy("currency_code")
                    ->select("currency_code, SUM(((company_hourly_rate - candidate_hourly_rate) * hours) - 
                        transfer_cost + bonus_commission) AS profit")
                    ->asArray()
                    ->all();

                foreach ($rows as $row)
                {
                    // check if available

                    $stat = CompanyStats::find()
                        ->andWhere(['company_id' => $company->company_id, "currency_code" => $row["currency_code"]])
                        ->one();

                    // update if available

                    if($stat) {
                        $stat->updateCounters(['total_revenue' => $row['profit']]);
                    } else { // else add
                        $stat = new CompanyStats;
                        $stat->company_id = $company->company_id;
                        $stat->currency_code = $row["currency_code"];
                        $stat->total_revenue = $row['profit'];
                        if(!$stat->save()) {
                            echo print_r($stat->errors, true); die();
                            break;
                        }
                    }
                }

                $count++;
                Console::updateProgress($count, $total);
            }
        }
    }
}



