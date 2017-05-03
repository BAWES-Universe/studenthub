<?php

namespace staff\modules\v1\controllers;

use Yii;
use yii\rest\Controller;
use yii\helpers\ArrayHelper;
use yii\data\ActiveDataProvider;
use staff\models\Candidate;
use common\models\CandidateIdCard;

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
     * List candidates to generate ID Cards
     */ 
    public function actionListCandidates()
    {
        $cards = CandidateIdCard::find()
            ->all();

        $candidate_ids = ArrayHelper::map($cards, 'candidate_id', 'candidate_id');

        $candidates = Candidate::find()
            ->where(['NOT IN', 'candidate_id', $candidate_ids])
            ->all();

        return $candidates;
    }

    /**
     * Generate ID for candidates 
     */
    public function actionGenerate()
    {
        $transaction = Yii::$app->db->beginTransaction();

        $candidates = Yii::$app->request->getBodyParam('candidates');

        foreach ($candidates as $key => $value) 
        {
            $ID = new CandidateIdCard;
            $ID->candidate_id = $value;
            $ID->expiry_date = date('Y-m-d', strtotime('+3 months'));
            
            if(!$ID->save())
            {
                $transaction->rollBack();

                return [
                    'operation' => 'error',
                    'message' => 'Invalid Candidate Id #'.$value
                ];
            }
        }

        $transaction->commit();

        $file = sys_get_temp_dir() . '/IdCards.zip';

        $zip = new ZipArchive();
        if ($zip->open($file, ZipArchive::CREATE) !== TRUE) {
            throw new \Exception('Cannot create a zip file');
        }

        /*foreach($files as $file){
            $zip->addFile($file[file_name], $file[local_name]);
        }*/

        $zip->close();

        Yii::$app->response->xSendFile($file);
    }
}
