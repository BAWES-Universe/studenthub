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
            ->where([
                'candidate_uid' => $candidate_uid
            ])
            ->one();

        if(!$candidate)
        {
            return $this->redirect('https://studenthub.co');
        }

        $id = CandidateIdCard::find()
            ->where(['candidate_id' => $candidate->candidate_id])
            ->one();

        if(!$id)
        {
            return $this->redirect('https://studenthub.co');
        }

        $store = Store::findOne($candidate->store_id);

        // show 404 if unassigned from store

        if(!$store)
        {
            return $this->redirect('https://studenthub.co');
        }

        // show 404 if candidate ID is expired

        if(time() > strtotime($id->expiry_date))
        {
            return $this->redirect('https://studenthub.co');
        }

        $university = University::findOne($candidate->university_id);

        $company = null;

        if($store)
            $company = Company::findOne($store->company_id);

        return $this->render('index', [
                'candidate' => $candidate,
                'university' => $university,
                'store' => $store,
                'company' => $company,
                'id' => $id
            ]);
    }
}
