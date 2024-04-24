<?php

namespace manager\modules\v1\controllers;

use Yii;
use manager\models\CandidateWorkHistory;
use manager\models\Candidate;


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
        $store = Yii::$app->user->identity
            ->getStore()->one();

        return $store->getCandidates()->all();
    }

    /**
     * Return no of Candidates assigned to work
     * for current company.
     */
    public function actionTotal()
    {
        $store = Yii::$app->user->identity
            ->getStore()->one();

        return $store->getCandidates()->count();
    }

    /**
     * get candidate work history
     * @param $id
     * @return array|static[]
     */
    public function actionWorkHistory($id)
    {
        $store = Yii::$app->user->identity
            ->getStore()->one();

        $model = CandidateWorkHistory::find()
            ->filterCandidate($id)
            ->andWhere(['store_id' => $store->store_id])
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

        $store = Yii::$app->user->identity
            ->getStore()->one();

        $data = Candidate::find()
            ->filterById($id)
            ->andWhere(['store_id' => $store->store_id])
            ->one();

        if (!$data)
            throw new \yii\web\NotFoundHttpException('The requested page does not exist.');

        return $data;
    }
}
