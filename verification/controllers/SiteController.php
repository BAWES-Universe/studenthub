<?php
namespace verification\controllers;

use yii\web\Controller;
use common\models\Candidate;
use common\models\University;
use common\models\Store;
use common\models\Company;
use common\models\CandidateIdCard;
use yii\web\NotFoundHttpException;
/**
 * Site controller
 */
class SiteController extends Controller
{
    /**
     * @return \yii\web\Response
     * 400 code for missing variable
     * redirect to home page
     */
    public function actionError()
    {
        $exception = \Yii::$app->errorHandler->exception;

        if ($exception->statusCode == 400) {
            return $this->redirect('https://studenthub.co');
        }
    }

    /**
     * @param $candidate_uid
     * @return string
     * @throws \yii\web\NotFoundHttpException
     */
    public function actionIndex($candidate_uid)
    {
        $candidate = Candidate::find()
            ->andWhere([
                'candidate_uid' => $candidate_uid
            ])
            ->one();

        if(!$candidate)
        {
            return $this->redirect('https://studenthub.co');
        }

        $id = CandidateIdCard::find()
            ->andWhere(['candidate_id' => $candidate->candidate_id])
            ->one();

        // don't show if candidate ID is expired or candidate not assigned to store

        if($id && time() > strtotime($id->expiry_date) || !$candidate->store)
        {
            $id = null;
        }

        return $this->render('index', [
            'candidate' => $candidate,
            'id' => $id
        ]);
    }
}
