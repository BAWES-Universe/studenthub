<?php

namespace staff\modules\v1\controllers;

use Yii;
use yii\helpers\Url;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use staff\models\Candidate;
use staff\models\CandidateIdCard;
use common\components\Excel;
use dosamigos\qrcode\QrCode;

/**
 * CandidateIdcard controller - Manage Candidate ID as Staff
 */
class CandidateIdCardController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // remove authentication filter for cors to work
        unset($behaviors['authenticator']);

        // Allow XHR Requests from our different subdomains and dev machines
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::className(),
            'cors' => [
                'Origin' => Yii::$app->params['allowedOrigins'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => null,
                'Access-Control-Max-Age' => 86400,
                'Access-Control-Expose-Headers' => [
                    'X-Pagination-Current-Page',
                    'X-Pagination-Page-Count',
                    'X-Pagination-Per-Page',
                    'X-Pagination-Total-Count'
                ],
            ],
        ];

        // Bearer Auth checks for Authorize: Bearer <Token> header to login the user
        $behaviors['authenticator'] = [
            'class' => \yii\filters\auth\HttpBearerAuth::className(),
        ];
        // avoid authentication on CORS-pre-flight requests (HTTP OPTIONS method)
        $behaviors['authenticator']['except'] = ['options'];

        return $behaviors;
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        $actions = parent::actions();
        $actions['options'] = [
            'class' => 'yii\rest\OptionsAction',
            // optional:
            'collectionOptions' => ['GET', 'POST', 'HEAD', 'OPTIONS'],
            'resourceOptions' => ['GET', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
        ];
        return $actions;
    }

    /**
     * List candidates having ID Cards
     */
    public function actionListCandidateIds()
    {
        $candidate_name = Yii::$app->request->get("candidate_name");

        $query = Candidate::find()
            ->joinWith('candidateIdCard', true, 'INNER JOIN')
            ->notDeleted();

        if($candidate_name) {
            $query->filterName($candidate_name);
        }

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * List candidates to generate ID Cards
     */
    public function actionListCandidates()
    {
        $candidate_name = Yii::$app->request->get("candidate_name");

        $query = Candidate::find()
            ->filterWithoutCard()
            ->notDeleted();
            
        if($candidate_name)
        {
            $query->filterName($candidate_name);
        }

        $query->filterAssigned(); // only candidate with assigned work

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * Generate ID for candidates
     */
    public function actionGenerate()
    {
        $transaction = Yii::$app->db->beginTransaction();

        $candidate_ids = [];

        //remove null values 

        $a = Yii::$app->request->getBodyParam('candidates');

        foreach ($a as $key => $value) 
        {
            if($value)
                $candidate_ids[] = $value;
        }

        // create ID Card entry 
        
        foreach ($candidate_ids as $key => $value)
        {
            //check if id card already available

            $ID = CandidateIdCard::find()
                ->where(['candidate_id' => $value])
                ->one();

            if($ID)
                continue;

            $ID = new CandidateIdCard;
            $ID->candidate_id = $value;
            $ID->expiry_date = date('Y-m-d', strtotime('+3 months'));

            if(!$ID->save())
            {
                $transaction->rollBack();

                Yii::$app->response->statusCode = 400;

                return [
                    'operation' => 'error',
                    'message' => 'Invalid Candidate Id #'.$value
                ];
            }
        }

        $transaction->commit();

        //create zip file to download generated IDs

        $candidates = Candidate::find()
            ->where(['in', 'candidate_id', $candidate_ids])
            ->all();

        $result = CandidateIdCard::createZip($candidates);

        if($result['operation'] == 'error') 
            return $result;
        
        //log message 

        $names = ArrayHelper::map($candidates, 'candidate_id', 'candidate_name');

        Yii::info('[Candidate ID Cards Generated] Candidate ID Cards for ['.implode(', ', $names).'] got generated by Staff: "'.Yii::$app->user->identity->staff_name.'"', __METHOD__);

        // Download Zip File

        return Yii::$app->response->sendFile($result['zip']);
    }

    /**
     * List candidates having expired ID Cards
     */
    public function actionListExpired()
    {
        $candidate_name = Yii::$app->request->get("candidate_name");

        $query = Candidate::find()
            ->idExpired()
            ->notDeleted();
        if($candidate_name) {
            $query->filterName($candidate_name);
        }

        $query->filterAssigned(); // only candidate with assigned work

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }
    
    /**
     * Renew Candidate IDs
     */
    public function actionRenew()
    {
        $transaction = Yii::$app->db->beginTransaction();

        $candidate_ids = Yii::$app->request->getBodyParam('candidates');

        foreach ($candidate_ids as $key => $value)
        {
            if(!$value)
                continue;
            
            $ID = CandidateIdCard::find()
                ->where(['candidate_id' => $value])
                ->one();

            if(!$ID)
            {
                $transaction->rollBack();

                return [
                    'operation' => 'error',
                    'message' => 'Candidate ID not found'
                ];
            }

            $ID->expiry_date = date('Y-m-d', strtotime('+3 months'));
            $ID->save();            
        }

        $transaction->commit();

        //log 

        $candidates = Candidate::find()
            ->where(['in', 'candidate_id', $candidate_ids])
            ->all();

        $names = ArrayHelper::map($candidates, 'candidate_id', 'candidate_name');
        
        Yii::info('[Candidate ID Cards Renewed] Candidate ID Cards for ['.implode(', ', $names).'] got renewed by Staff: "'.Yii::$app->user->identity->staff_name.'"', __METHOD__);

        return [
            'operation' => 'success',
            'message' => 'Candidate ID Renewed Successfully'
        ];
    }

    /**
     * Return no. of expired ID Cards
     */
    public function actionTotalExpired()
    {
        $query = Candidate::find()
            ->idExpired()
            ->filterAssigned() // only candidate with assigned work
            ->notDeleted();

        return [
            'total' => $query->count()
        ];
    }
}
