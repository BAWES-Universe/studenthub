<?php

namespace company\modules\v1\controllers;

use Yii;
use company\models\CandidateWorkHistory;
use company\models\Candidate;


/**
 * Candidate controller - Manage Candidate accounts as Admin
 */
class CandidateController extends BaseController
{
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
        return Yii::$app->companyManager->getCompany()->getCandidates()->count();
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

        $data = Candidate::find()->filterById($id)->one();

        if (!$data)
            throw new \yii\web\NotFoundHttpException('The requested page does not exist.');

        return $data;
    }
}
