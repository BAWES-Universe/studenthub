<?php

namespace staff\modules\v1\controllers;

use Yii;
use yii\rest\Controller;
use yii\helpers\ArrayHelper;
use yii\data\ActiveDataProvider;
use staff\models\Store;
use staff\models\Candidate;
use common\models\InvoiceCandidates;

/**
 * Candidate controller - Manage Candidate accounts as Admin
 */
class CandidateController extends Controller
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
     * Return a List of Candidate Accounts by 
     * search criteria 
     */
    public function actionSearch()
    {
        $country_id = Yii::$app->request->get('country_id');

        $query = Candidate::find();

        if($country_id) {
            $query->where(['country_id' => $country_id]);
        }

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * Return a List of Candidate Accounts assigned to
     * Specific Store.
     */
    public function actionFilter()
    {
        $store_id = Yii::$app->request->getBodyParam("store_id");

        $query = Candidate::find();

        if($store_id) {
            $query->where(['store_id' => $store_id]);
        }

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * Return a List of Candidate Accounts available.
     */
    public function actionList()
    {
        $query = Candidate::find();

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * Return a List of Candidate not assigned to store
     */
    public function actionListNotAssigned()
    {
        $query = Candidate::find()
            ->where('store_id IS NULL or store_id = 0');

        $candidate_name = Yii::$app->request->get("candidate_name");

        if($candidate_name)
        {
            $query->andWhere(['like', 'candidate_name', $candidate_name]);
        }

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * Return a List of Candidate assigned to store
     */
    public function actionListAssigned()
    {
        $query = Candidate::find()
            ->where('store_id > 0');

        $candidate_name = Yii::$app->request->get("candidate_name");

        if($candidate_name)
        {
            $query->andWhere(['like', 'candidate_name', $candidate_name]);
        }

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * Create a Candidate account
     */
    public function actionCreate()
    {
        // Attempt to create new account
        $password = Yii::$app->security->generateRandomString(10);
        $model = new Candidate();
        $model->scenario = "newAccount";

        $model->store_id = Yii::$app->request->getBodyParam("store_id");
        $model->bank_id = Yii::$app->request->getBodyParam("bank_id");
        $model->university_id = Yii::$app->request->getBodyParam("university_id");
        $model->country_id = Yii::$app->request->getBodyParam("country_id");
        $model->bank_account_name = Yii::$app->request->getBodyParam("bank_account_name");
        $model->candidate_iban = Yii::$app->request->getBodyParam("iban");
        $model->candidate_name = Yii::$app->request->getBodyParam("name");
        $model->candidate_name_ar = Yii::$app->request->getBodyParam("name_ar");
        $model->candidate_personal_photo = Yii::$app->request->getBodyParam("personal_photo");        
        $model->candidate_email = Yii::$app->request->getBodyParam("email");
        $model->candidate_phone = Yii::$app->request->getBodyParam("phone");
        $model->candidate_birth_date = Yii::$app->request->getBodyParam("birth_date");
        $model->candidate_civil_id = Yii::$app->request->getBodyParam("civil_id");
        $model->candidate_civil_expiry_date = Yii::$app->request->getBodyParam("expiry_date");
        $model->candidate_civil_photo_front = Yii::$app->request->getBodyParam("photo_front");
        $model->candidate_civil_photo_back = Yii::$app->request->getBodyParam("photo_back");
        $model->candidate_hourly_rate = Yii::$app->request->getBodyParam("hourly_rate");
        $model->candidate_password_hash = $password;
        
        //candidate_auth_key
        
        if (!$model->signup())
        {
            if(isset($model->errors)){
                return [
                    "operation" => "error",
                    "message" => $model->errors
                ];
            }else{
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem creating the account, please contact us for assistance."
                ];
            }
        }

        //Send Email to user
        Yii::$app->mailer->htmlLayout = 'layouts/html';
        Yii::$app->mailer->compose("candidate-register",
            [
                "model" => $model,
                "password" => $password,
                'logo_1' => '',
                'logo_2' => ''
            ])
            ->setFrom(Yii::$app->params['supportEmail'])
            ->setTo($model->candidate_email)
            ->setSubject('Welcome to '.Yii::$app->name)
            ->send();

        return [
            "operation" => "success",
            "message" => "Candidate account successfully created"
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    /**
     * Update a Candidate account
     */
    public function actionUpdate($id)
    {
        // Attempt to create new account
        $model = Candidate::findOne((int) $id);

        if(!$model) {
            return [
                "operation" => "error",
                "message" => "Candidate not found",
                "code" => 1
            ];
        }

        $model->store_id = Yii::$app->request->getBodyParam("store_id");
        $model->bank_id = Yii::$app->request->getBodyParam("bank_id");
        $model->university_id = Yii::$app->request->getBodyParam("university_id");
        $model->country_id = Yii::$app->request->getBodyParam("country_id");
        $model->bank_account_name = Yii::$app->request->getBodyParam("bank_account_name");
        $model->candidate_iban = Yii::$app->request->getBodyParam("iban");
        $model->candidate_name = Yii::$app->request->getBodyParam("name");
        $model->candidate_name_ar = Yii::$app->request->getBodyParam("name_ar");
        $model->candidate_personal_photo = Yii::$app->request->getBodyParam("personal_photo"); 
        $model->candidate_email = Yii::$app->request->getBodyParam("email");
        $model->candidate_phone = Yii::$app->request->getBodyParam("phone");
        $model->candidate_birth_date = Yii::$app->request->getBodyParam("birth_date");
        $model->candidate_civil_id = Yii::$app->request->getBodyParam("civil_id");
        
        $model->candidate_civil_expiry_date = Yii::$app->request->getBodyParam("expiry_date");
        $model->candidate_civil_photo_front = Yii::$app->request->getBodyParam("photo_front");
        $model->candidate_civil_photo_back = Yii::$app->request->getBodyParam("photo_back");
        $model->candidate_hourly_rate = Yii::$app->request->getBodyParam("hourly_rate");

        if (!$model->save())
        {
            if(isset($model->errors)){
                return [
                    "operation" => "error",
                    "message" => $model->errors,
                    "code" => 2
                ];
            }else{
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem updating the account, please contact us for assistance.",
                    "code" => 3
                ];
            }
        }

        Yii::info("[Candidate Account Updated] ".$model->candidate_email, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Candidate account updated successfully"
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    /**
     * Assign Store to Candidate account
     */
    public function actionAssign($id)
    {
        // Attempt to create new account
        $model = Candidate::findOne((int) $id);

        if(!$model) {
            return [
                "operation" => "error",
                "message" => "Candidate not found",
                "code" => 1
            ];
        }

        $model->store_id = Yii::$app->request->getBodyParam("store_id");

        $store = Store::findOne($model->store_id);

        if(!$store) {
            return [
                "operation" => "error",
                "message" => "Store not found",
                "code" => 1
            ];   
        }

        if (!$model->save(false))
        {
            if(isset($model->errors)){
                return [
                    "operation" => "error",
                    "message" => $model->errors,
                    "code" => 2
                ];
            }else{
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem updating the account, please contact us for assistance.",
                    "code" => 3
                ];
            }
        }

        Yii::info("[Candidate Account Updated] ".$model->candidate_email, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Candidate assigned to store successfully",
            "store_id" => $store->store_id,
            "store_name" => $store->store_name
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    /**
     * Remove Store from Candidate account
     */
    public function actionUnassign($id)
    {
        // Attempt to create new account
        $model = Candidate::findOne((int) $id);

        if(!$model) {
            return [
                "operation" => "error",
                "message" => "Candidate not found"
            ];
        }
        
        $model->store_id = null;
        
        if (!$model->save(false))
        {
            if(isset($model->errors)){
                return [
                    "operation" => "error",
                    "message" => $model->errors
                ];
            }else{
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem updating the account, please contact us for assistance."
                ];
            }
        }

        Yii::info("[Candidate Account Updated] ".$model->candidate_email, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Candidate unassigned from store successfully"
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    /**
     * Delete candidate 
     */
    public function actionDelete($id)
    {
        // Attempt to create new account
        $model = Candidate::findOne((int) $id);

        if(!$model) {
            return [
                "operation" => "error",
                "message" => "Candidate not found"
            ];
        }

        //check if in invoice 

        $a = InvoiceCandidates::findOne([
                'candidate_id' => $id
            ]);

        if($a) 
        {
            return [
                "operation" => "error",
                "message" => "Can not delete as Candidate mansioned in Invoice"
            ];   
        }

        $model->delete();

        return [
            "operation" => "success",
            "message" => "Candidate removed successfully"
        ];
    }
}
