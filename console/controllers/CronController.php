<?php

namespace console\controllers;

use common\models\Company;
use Yii;
use yii\helpers\Url;
use yii\helpers\Console;
use yii\helpers\ArrayHelper;
use common\models\Candidate;
use common\models\Invoice;


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

    public function actionTestIdGenerate() {
        $c = Candidate::find()->limit(2)->all();
        $data = \staff\models\CandidateIdCard::createIdCards($c);
        var_dump($data);
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
}
