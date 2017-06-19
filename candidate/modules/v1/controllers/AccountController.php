<?php

namespace candidate\modules\v1\controllers;

use common\models\Candidate;
use Yii;
use yii\rest\Controller;
use yii\helpers\ArrayHelper;
use yii\data\ActiveDataProvider;
use common\models\TransferCandidate;

/**
 * Account controller will return the actual Instagram Accounts and all controls associated
 */
class AccountController extends Controller
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
     * Return a List of Salary transfers
     */
    public function actionSalary()
    {
        $list = [];
        
        $TransferCandidate = Yii::$app->user->identity->transferCandidate;

        foreach ($TransferCandidate as $key => $transferCandidate) {

            if (
                empty($transferCandidate->invoice) ||
                $transferCandidate->invoice->invoice_status != 'paid'
            ) {
                continue;
            }

            if(isset($transferCandidate->transfer->company->company_name)) {
                $company_name = $transferCandidate->transfer->company->company_name;
            } else {
                $company_name = '';
            }

            $list[] = [
                'transfer_id' => $transferCandidate->transfer_id,
                'candidate_id' => $transferCandidate->candidate_id,
                'candidate_hourly_rate' => $transferCandidate->candidate_hourly_rate,
                'hours' => $transferCandidate->hours,
                'bonus' => $transferCandidate->bonus,
                'status' => ($transferCandidate->paid) ? 'Paid' : 'Unpaid',
                'tc_created_at' => $transferCandidate->tc_created_at,
                'company_name' => $company_name,
                'total' => ($transferCandidate->candidate_hourly_rate * $transferCandidate->hours) + $transferCandidate->bonus,
            ];
        }
        
        return array_reverse($list);
    }

    /**
     * Return currnet employer detail
     */
    public function actionEmployer()
    {
        $candidate = Yii::$app->user->identity;

        //store detail 

        if(empty($candidate->store)) {
            return [
                "operation" => "error",
                "message" => "No employer detail found"
            ];
        }

        //company details 

        if(empty($candidate->store->company)) {
            $company_id = '';
            $company_name = '';
            $company_email = '';            
        }else{
            $company_id = $candidate->store->company->company_id; 
            $company_name = $candidate->store->company->company_name;
            $company_email = $candidate->store->company->company_email;           
        }

        return [
            'company_id' => $company_id,
            'store_id' => $candidate->store->store_id,
            'store_name' => $candidate->store->store_name,
            'company_name' => $company_name,
            'company_email'=> $company_email
        ];
    }

    public function actionChangePassword()
    {
        $model = Yii::$app->user->identity;

        $oldPassword = Yii::$app->request->getBodyParam("old_password");
        $newPassword = Yii::$app->request->getBodyParam("new_password");

        if (empty($oldPassword)) {
            return [
                "operation" => "error",
                "message" => "Empty old password"
            ];
        } else if (empty($newPassword)) {
            return [
                "operation" => "error",
                "message" => "Empty new password"
            ];
        }

        if ($oldPassword === $newPassword) {
            return [
                "operation" => "error",
                "message" => "New password should not be same as old password"
            ];
        }

        if (!$model->validatePassword($oldPassword)) {
            return [
                "operation" => "error",
                "message" => "Invalid Old Password"
            ];
        }

        if (strlen($newPassword) < 5) {
            return [
                "operation" => "error",
                "message" => "New password length should be great then equal to 5"
            ];
        }

        $candidate = Candidate::findOne($model->getId());
        $candidate->setPassword($newPassword);
        if ($candidate->save(false)) {
            return [
                "operation" => "success",
                "message" => "Password changed successfully!"
            ];
        }
    }
}
