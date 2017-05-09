<?php
namespace verification\controllers;

use Yii;
use yii\base\InvalidParamException;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use common\models\Candidate;
use common\models\University;
use common\models\Store;
use common\models\company;
use common\models\CandidateIdCard;

/**
 * Site controller
 */
class SiteController extends Controller
{
    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ]
        ];
    }

    /**
     * Displays homepage.
     *
     * @return mixed
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
            throw new \yii\web\NotFoundHttpException('The requested page does not exist.');
        }

        $university = University::findOne($candidate->university_id);

        $store = Store::findOne($candidate->store_id);

        $company = null;   

        if($store)
            $company = Company::findOne($store->company_id);    
                
        $id = CandidateIdCard::find()
            ->where(['candidate_id' => $candidate->candidate_id])
            ->one();

        return $this->render('index', [
                'candidate' => $candidate,
                'university' => $university,
                'store' => $store,
                'company' => $company,
                'id' => $id
            ]);
    }
}
