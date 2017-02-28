<?php

namespace company\modules\v1\controllers;

use Yii;
use yii\rest\Controller;
use yii\helpers\ArrayHelper;
use yii\data\ActiveDataProvider;
use common\models\Transfer;
use common\models\TransferCandidates;
use yii\db\Query;

/**
 * Transfer controller - Manage Transfer
 */
class TransferController extends Controller
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
                'Access-Control-Request-Method' => ['POST', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => null,
                'Access-Control-Max-Age' => 86400,
                'Access-Control-Expose-Headers' => [],
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
            'collectionOptions' => ['POST'],
            'resourceOptions' => ['POST', 'OPTIONS'],
        ];
        return $actions;
    }

    /**
     * Initiate transfer.
     */
    public function actionCreate()
    {
        $company = Yii::$app->user->identity;

        //validate input 

        $errors = Transfer::validate_candidates(
            $company->company_id, 
            Yii::$app->request->getBodyParam("candidates")
        );

        if($errors) {
            return [
                    "operation" => "error",
                    "message" => $errors
                ];
        }

        //save transfer 

        $transaction = Yii::$app->db->beginTransaction();

        $transfer = new Transfer;
        $transfer->company_id = $company->company_id;
        
        if(!$transfer->save()){

            if(isset($transfer->errors)){
                return [
                    "operation" => "error",
                    "message" => $transfer->errors
                ];
            }else{
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem creating the account, please contact us for assistance."
                ];
            }
        }

        //save candidates 

        $candidates = Yii::$app->request->getBodyParam("candidates");

        foreach ($candidates as $key => $value) {
            $tc = new TransferCandidates;
            $tc->attributes = $value;
            $tc->transfer_id = $transfer->transfer_id;
            
            if(!$tc->save()) 
            {
                $transaction->rollBack();

                if(isset($tc->errors)){
                    return [
                        "operation" => "error",
                        "message" => $tc->errors
                    ];
                }else{
                    return [
                        "operation" => "error",
                        "message" => "We've faced a problem creating the account, please contact us for assistance."
                    ];
                }
            }
        }

        $transaction->commit();

        return [
            "operation" => "success",
            "message" => "Transfer initiated successfully"
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    /** 
     *  Lock transfer to invoice 
     */ 
    public function actionLock($id)
    {
        $company = Yii::$app->user->identity;

        $transfer = Transfer::findOne([
                'company_id' => $company->company_id,
                'transfer_id' => $id
            ]);

        if(!$transfer) {
            return [
                    "operation" => "error",
                    "message" => 'Transfer not found!'
                ];
        }

        $transfer->transfer_status = Transfer::STATUS_LOCK;
        $transfer->save();

        return [
                "operation" => "success",
                "message" => "Transfer locked successfully"
            ];
    }
}
