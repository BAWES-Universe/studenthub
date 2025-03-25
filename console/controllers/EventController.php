<?php

namespace console\controllers;

use admin\models\TransferCandidate;
use common\models\Company;
use common\models\Staff;
use common\models\Story;
use Yii;
use yii\db\Expression;
use yii\helpers\Console;
use \DateTime;

class EventController extends \yii\console\Controller {
    //put your code here

    public $event;

    public function options($actionID)
    {
        return array_merge(parent::options($actionID), ['event']);
    }

    public function syncStoryCreated() {

        $query = Story::find()
            ->andWhere(new Expression('`story_created_at` > (NOW() - INTERVAL 1 YEAR)'));

        $count = 0;

        $total = Story::find()
            ->andWhere(new Expression('`story_created_at` > (NOW() - INTERVAL 1 YEAR)'))
            ->count();

        Console::startProgress(0, $total);

        foreach($query->batch(100) as $stories) {

            $count += sizeof($stories);

            foreach ($stories as $story) {

                $staff = $story->getStaff()->one();

                if(!$staff)
                    continue;

                $datetime = $story->story_created_at?new \DateTime($story->story_created_at): new \DateTime();

                Yii::$app->eventManager->setUser($staff->staff_id, [
                    'name' => trim($staff->staff_name),
                    'email' => $staff->staff_email,
                ]);

                Yii::$app->eventManager->track(
                    'Story Created',
                    $story->attributes,
                    $datetime->format('c'),
                    $staff->staff_id
                );

            }

            Console::updateProgress($count, $total);
        }

        Yii::$app->eventManager->flush();
    }

    public function syncStoryUpdated() {

        $query = Story::find()
            ->andWhere(new Expression('`story_last_updated_at` > (NOW() - INTERVAL 1 YEAR)'));

        $count = 0;

        $total = Story::find()
            ->andWhere(new Expression('`story_last_updated_at` > (NOW() - INTERVAL 1 YEAR)'))
            ->count();

        Console::startProgress(0, $total);

        foreach($query->batch(100) as $stories) {

            $count += sizeof($stories);

            foreach ($stories as $story) {

                $staff = $story->getStaff()->one();

                if(!$staff)
                    continue;

                $datetime = $story->story_last_updated_at?
                    new \DateTime($story->story_last_updated_at): new \DateTime();

                Yii::$app->eventManager->setUser($staff->staff_id, [
                    'name' => trim($staff->staff_name),
                    'email' => $staff->staff_email,
                ]);

                Yii::$app->eventManager->track(
                    'Story Updated',
                    $story->attributes,
                    $datetime->format('c'),
                    $staff->staff_id
                );

            }

            Console::updateProgress($count, $total);
        }

        Yii::$app->eventManager->flush();
    }

    public function syncStaffUpdated() {

        $query = Staff::find()
            ->andWhere(new Expression('`staff_updated_at` > (NOW() - INTERVAL 1 YEAR)'));

        $count = 0;

        $total = Staff::find()
            ->andWhere(new Expression('`staff_updated_at` > (NOW() - INTERVAL 1 YEAR)'))
            ->count();

        Console::startProgress(0, $total);

        foreach($query->batch(100) as $staffs) {

            $count += sizeof($staffs);

            foreach ($staffs as $staff) {

                $datetime = $staff->staff_updated_at?
                    new \DateTime($staff->staff_updated_at): new \DateTime();

                Yii::$app->eventManager->track(
                    'Staff Updated v2',
                    [
                        "staff_name" => $staff->staff_name,
                        "staff_email" => $staff->staff_email
                    ],
                    $datetime->format('c')
                );
            }

            Console::updateProgress($count, $total);
        }

        Yii::$app->eventManager->flush();
    }

    public function syncStaffCreated() {

        $query = Staff::find()
            ->andWhere(new Expression('`staff_created_at` > (NOW() - INTERVAL 1 YEAR)'));

        $count = 0;

        $total = Staff::find()
            ->andWhere(new Expression('`staff_created_at` > (NOW() - INTERVAL 1 YEAR)'))
            ->count();

        Console::startProgress(0, $total);

        foreach($query->batch(100) as $staffs) {

            $count += sizeof($staffs);

            foreach ($staffs as $staff) {

                $datetime = $staff->staff_created_at?
                    new \DateTime($staff->staff_created_at): new \DateTime();

                //$staff->attributes,
                Yii::$app->eventManager->track(
                    'Staff Created  v2',
                    [
                        "staff_name" => $staff->staff_name,
                        "staff_email" => $staff->staff_email
                    ],
                    $datetime->format('c')
                );
            }

            Console::updateProgress($count, $total);
        }

        Yii::$app->eventManager->flush();
    }

    /**
     * sync suggestion with mixpanel BAWES project
     * e.g., ./yii event/emulate --event "Candidate Transfer Paid"
     */
    public function syncCandidateTransferPaid() {

        $query = TransferCandidate::find()
            ->joinWith(['candidate'])
            ->andWhere(new Expression('`tc_updated_at` > (NOW() - INTERVAL 1 YEAR)'));

        $count = 0;

        $total = $query->count();

        Console::startProgress(0, $total);

        foreach($query->batch(100) as $tcs) {

            foreach ($tcs as $tc) {

                $datetime = $tc->tc_updated_at?
                    new \DateTime($tc->tc_updated_at): new \DateTime($tc->tc_created_at);

                $name = !empty($tc->candidate->candidate_name) ?
                    $tc->candidate->candidate_name: $tc->candidate->candidate_name_ar;

                Yii::$app->eventManager->track(
                    'Candidate Transfer Paid',
                    [
                        "candidate_id" => $tc->candidate_id,
                        "currency" => $tc->candidate_id,// "KWD",
                        "name" => $name,
                        "revenue" => $tc->getProfit(),
                        "tc_id" => $tc->tc_id,
                        "transfer_id" => $tc->transfer_id,
                    ],
                    $datetime->format('c'),
                    null,
                    true
                );

                $count ++;
                Console::updateProgress($count, $total);

               // print_r($mixpanelData);
                die();
            }
        }

        Yii::$app->eventManager->flush();
    }

    public function syncCompanyProfileUpdated() {

        $query = Company::find()
            ->andWhere(new Expression('`company_updated_at` > (NOW() - INTERVAL 1 YEAR)'));

        $count = 0;

        $total = Company::find()
            ->andWhere(new Expression('`company_updated_at` > (NOW() - INTERVAL 1 YEAR)'))
            ->count();

        Console::startProgress(0, $total);

        foreach($query->batch(100) as $companies) {

            $count += sizeof($companies);

            foreach ($companies as $company) {

                $datetime = $company->company_updated_at?
                    new \DateTime($company->company_updated_at): new \DateTime();

                Yii::$app->eventManager->track(
                    'Company Profile Updated',
                    $company->attributes,
                    $datetime->format('c')
                );
            }

            Console::updateProgress($count, $total);
        }

        Yii::$app->eventManager->flush();
    }

    /**
     * sync suggestion with segment
     */
    public function actionEmulate() {

        switch ($this->event) {
            case "Story Created":
                $this->syncStoryCreated();
                break;
            case "Story Updated":
                $this->syncStoryUpdated();
                break;
            case "Staff Created":
                $this->syncStaffCreated();
                break;
            case "Staff Updated":
                $this->syncStaffUpdated();
                break;
            case "Company Profile Updated":
                $this->syncCompanyProfileUpdated();
                break;
            case "Candidate Transfer Paid":
                $this->syncCandidateTransferPaid();
                break;
            default:
                $this->stdout("Missing event name \n", Console::FG_RED, Console::BOLD);
            //throwException("Missing event name");
        }
    }
}