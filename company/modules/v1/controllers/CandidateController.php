<?php

namespace company\modules\v1\controllers;

use company\models\Request;
use Yii;
use company\models\CandidateWorkHistory;
use company\models\Candidate;
use yii\data\ActiveDataProvider;
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
