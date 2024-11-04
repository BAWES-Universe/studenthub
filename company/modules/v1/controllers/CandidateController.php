<?php

namespace company\modules\v1\controllers;

use common\models\CandidateWorkingDate;
use company\models\CandidateWorkingHour;
use company\models\Request;
use Yii;
use company\models\CandidateWorkHistory;
use company\models\Candidate;
use yii\data\ActiveDataProvider;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;


/**
 * Candidate controller - Manage Candidate accounts as Admin
 */
class CandidateController extends BaseController
{
    /**
     * Return a List of Candidate Accounts by
     * search criteria
     */
    public function actionSearch()
    {
        $currency = Yii::$app->request->headers->get("Currency", "KWD");

        $country_id = Yii::$app->request->get('country_id');
        $match_request_id = Yii::$app->request->get('match_request_id');

        $by = Yii::$app->request->get('by');

        //validate request id

        //if ($match_request_id) {
            $companyIds = Yii::$app->companyManager->getCompanyIds();

            $isValidRequest = Request::find()
                ->andWhere(['in', 'company_id', $companyIds])//current company and childs
                ->andWhere(['request_uuid' => $match_request_id])
                ->exists();

            if (!$isValidRequest) {
                throw new NotFoundHttpException('The requested page does not exist.');
            }
        //}

        $query = \staff\models\Candidate::find()
            ->verifiedProfile();

        if($currency) {
            $query->andWhere(['candidate.currency_code' => $currency]);
        }

        switch ($by) {
            case 'review' :
                $query->byApprovalStatus(Yii::$app->request->get('review'));
                $query->completedProfileWithoutApproval();
                break;
            default:
                # nothing
                break;
        }

        if($match_request_id) {
            $query->filterByRequestRequirement($match_request_id);
        }

        if($country_id) {
            $query->filterCountry($country_id);
        }

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * Return a List of Candidate Accounts assigned to work
     * for current company.
     */
    public function actionList()
    {
        return Yii::$app->companyManager->getCompany()
            ->getCandidates()->all();
    }

    /**
     * @return ActiveDataProvider
     * @throws NotFoundHttpException
     */
    public function actionWorkingDates() {

        $candidate_id = Yii::$app->request->get("candidate_id");
        //$start_date = Yii::$app->request->get("start_date");
        //$end_date = Yii::$app->request->get("end_date");

        $candidate = Yii::$app->companyManager->getCompany()
            ->getCandidates()
            ->andWhere(['candidate_id' => $candidate_id])
            ->one();

        if(!$candidate) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        $query = $candidate->getCandidateWorkingDates();

        /*if ($start_date && $end_date) {
            $query->filterByDateRange($start_date, $end_date);
        }*/

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    public function actionWorkLogExcel() {

        $start_date = Yii::$app->request->get("start_date");
        $end_date = Yii::$app->request->get("end_date");

        $session_status = Yii::$app->request->get("session_status");
        $store_id = Yii::$app->request->get("store_id");
        $approved = Yii::$app->request->get("approved");

        $q = Yii::$app->request->get("q");

        $company = Yii::$app->companyManager->getCompany();

        $query = $company
            ->getCandidates();

        if($store_id) {
            $query->andWhere(['candidate.store_id' => $store_id]);
        }

        if($q) {
            $query->andWhere([
                "OR",
                ["like", 'candidate_name', $q],
                ["like", 'candidate_name_ar', $q],
            ]);
        }

        if ($session_status || $start_date || $end_date) {

            $query->joinWith(['candidateWorkingDates']);
                //->andWhere(new Expression('end_time IS NOT NULL'));

            if ($session_status) {
                $query->andWhere(['candidate_working_date.status' => $session_status]);
            }

            if ($start_date) {
                $query->andWhere(new Expression('DATE(candidate_working_date.date) >= DATE("'. $start_date .'")'));
            }

            if ($end_date) {
                $query->andWhere(new Expression('DATE(candidate_working_date.date) <= DATE("'. $end_date .'")'));
            }
        }

        $logData = [];

        foreach ($query->batch(100) as $candidates) {

            foreach ($candidates as $candidate) {

                $query = CandidateWorkingDate::find()->andWhere([
                        "candidate_id" => $candidate->candidate_id,
                        "store_id" => $candidate->store_id, //filter by store, in case store changed in month
                    ])
                    ->filterByDateRange($start_date, $end_date);

                if ($approved) {
                    $query->andWhere(["status" => CandidateWorkingDate::STATUS_APPROVED]);
                }

                $seconds = $query
                    ->sum("total_time");

                $hours = floor($seconds / 3600);
                $minutes = floor(($seconds - ($hours * 3600)) / 60);

                $logData[$candidate->candidate_id] = [
                    "hours" => $hours,
                    "minutes" => $minutes,
                    "seconds" => $seconds - ($hours * 3600) - ($minutes * 60),
                    "bonus" => 0
                ];
            }
        }

        header('Access-Control-Allow-Origin: *');

        \moonland\phpexcel\Excel::export([
            'isMultipleSheet' => false,
            'models' => $company->candidates,
            'columns' => [
                [
                    'header' => 'candidate_id',
                    'value' => function($data) {
                        return $data->candidate_id;
                    }
                ],
                [
                    'header' => 'candidate_name',
                    'value' => function($data) {
                        return $data->candidate_name;
                    }
                ],
                [
                    'header' => 'company_name',
                    'value' => function($data) {
                        return $data->company->company_name;
                    }
                ],
                [
                    'header' => 'store_name',
                    'value' => function($data) {
                        return $data->store->store_name;
                    }
                ],
                [
                    'header' => 'hours',
                    'value' => function($data) use ($logData) {
                        return $logData[$data->candidate_id]['hours'];
                    }
                ],
                [
                    'header' => 'minutes',
                    'value' => function($data) use ($logData) {
                        return $logData[$data->candidate_id]['minutes'];
                    }
                ],
                [
                    'header' => 'seconds',
                    'value' => function($data) use ($logData) {
                        return $logData[$data->candidate_id]['seconds'];
                    }
                ],
                [
                    'header' => 'bonus',
                    'value' => function($data) use ($logData) {
                        return $logData[$data->candidate_id]['bonus'];
                    }
                ]
            ]
        ]);
    }

    public function actionWorkLogStats() {

        $company = Yii::$app->companyManager->getCompany();

        $candidates = ArrayHelper::getColumn($company
            ->getCandidates()
            ->all(), "candidate_id");

        $activeSessionQuery = CandidateWorkingHour::find()
            ->select('candidate_id')
            ->andWhere(["IN", "candidate_id", $candidates])
            ->andWhere(['date' => date("Y-m-d")])
            ->andWhere(new Expression("end_time IS NULL"));

        $data['currentHourlyPaying'] = (float) CandidateWorkHistory::find()
            ->andWhere(["IN", "candidate_id", $activeSessionQuery])
            ->andWhere(['parent_company_id' => $company->company_id])// current company
            ->andWhere(new Expression("end_date IS NULL")) // active assignment
            ->sum("candidate_hourly_rate");

        $data['todayTotalHours'] = (int) $activeSessionQuery->sum("total_time");

        $data['activeSessions'] = (int) $activeSessionQuery
            ->count();

        $data['todayTotalPaying'] = (float) ($data['todayTotalHours'] * ($data['currentHourlyPaying'] / 3600));

        return $data;
    }

    /**
     * @return ActiveDataProvider
     */
    public function actionListWithPagination()
    {
        $start_date = Yii::$app->request->get("start_date");
        $end_date = Yii::$app->request->get("end_date");
        $with_session = Yii::$app->request->get("with_session");
        $session_status = Yii::$app->request->get("session_status");
        $store_id = Yii::$app->request->get("store_id");
        $q = Yii::$app->request->get("q");

        $query = Yii::$app->companyManager->getCompany()
            ->getCandidates();

        if($store_id) {
            $query->andWhere(['candidate.store_id' => $store_id]);
        }

        if($q) {
            $query->andWhere([
                "OR",
                ["like", 'candidate_name', $q],
                ["like", 'candidate_name_ar', $q],
            ]);
        }

        if ($with_session || $session_status || $start_date || $end_date) {
            
            $query->joinWith(['candidateWorkingDates']);
               // ->andWhere(new Expression('end_time IS NOT NULL'));

            /*if ($session_status) {
                $query->andWhere(['candidate_working_date.status' => $session_status]);
            }*/

            if ($session_status !== null) {
                if ($session_status == \common\models\CandidateWorkingHour::STATUS_APPROVED) {
                    $query->andWhere(new Expression('candidate_working_date.total_approved > 0'));
                } else if ($session_status == \common\models\CandidateWorkingHour::STATUS_REJECTED) {
                    $query->andWhere(new Expression('candidate_working_date.total_rejected > 0'));
                } else if ($session_status == \common\models\CandidateWorkingHour::STATUS_PENDING) {
                    $query->andWhere(new Expression('candidate_working_date.total_pending > 0'));
                }
            }

            if ($start_date) {
                $query->andWhere(new Expression('DATE(candidate_working_date.date) >= DATE("'. $start_date .'")'));
            }

            if ($end_date) {
                $query->andWhere(new Expression('DATE(candidate_working_date.date) <= DATE("'. $end_date .'")'));
            }
        }

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * Return no of Candidates assigned to work
     * for current company.
     */
    public function actionTotal()
    {
        return Yii::$app->companyManager->getCompany()
            ->getCandidates()->count();
    }

    /**
     * get candidate work history
     * @param $id
     * @return array|static[]
     */
    public function actionWorkHistory($id)
    {
        $model = CandidateWorkHistory::find()
            ->filterCandidate($id)
            ->all();

        if(!$model)
            return [];

        return $model;
    }

    public function actionWorkHistoryDetail($id)
    {
        $companyIds = Yii::$app->companyManager->getCompanyIds();

        $model =  CandidateWorkHistory::find()
            ->andWhere(['id' => $id])
            ->andWhere(['in', 'company_id', $companyIds])//current company and childs
            ->one();

        if(!$model) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        return $model;
    }

    /**
     * Return no of Candidate detail
     */
    public function actionView($id)
    {
        //$data = Yii::$app->user->identity->getCandidates()->filterById($id)->one();

        return $this->findModel($id);
    }

    /**
     * @param $candidate_id
     * @return ActiveDataProvider
     * @throws NotFoundHttpException
     */
    public function actionApplications($candidate_id)
    {
        /**
         * Yii::$app->companyManager->getCompany()
        ->getCandidates()
        ->filterById($candidate_id)
         */

        $companyIds = Yii::$app->companyManager->getCompanyIds();

        $query = $this->findModel($candidate_id)
            ->getRequestApplications()
            ->joinWith(['request'])
            ->andWhere(['in', 'company_id', $companyIds])//current company and childs
            ->orderBy("created_at DESC");

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * Finds the Candidate model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        $model = Candidate::find()
            ->filterById($id)
            ->one();

        if ($model) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
