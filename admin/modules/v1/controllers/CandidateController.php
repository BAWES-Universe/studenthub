<?php

namespace admin\modules\v1\controllers;

use Yii;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use admin\models\Candidate;
use yii\filters\Cors;
use yii\filters\auth\HttpBearerAuth;
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
            'class' => Cors::className(),
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
            'class' => HttpBearerAuth::className(),
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
        $query = Candidate::find();

        $by = Yii::$app->request->get('by');
        switch ($by) {
            case 'country_id' :
                $query->filterCountry(Yii::$app->request->get('country_id'));
                break;
            case 'university_id' :
                $query->filterUniversity(Yii::$app->request->get('university_id'));
                break;
            case 'review' :
                $query->byApprovalStatus(Yii::$app->request->get('review'));
                break;
            case 'store_id' :
                $query->filterStore(Yii::$app->request->get('store_id'));
                break;
            default:
                $query->byApprovalStatus(0);
                break;
        }

        $query->notDeleted();
        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * Return a No of Candidate to review
     * Return a No of Payable candidate also
     */
    public function actionTotalToReview()
    {
        $query = Candidate::find()
            ->notDeleted()
            ->byApprovalStatus(0);

        $payable = Candidate::getTotalPayableCandidate();

        return [
            'total' => $query->count(),
            'payable' => $payable['payable']
        ];
    }

    /**
     * Approve candidate account
     * @param $id
     * @return array
     */
    public function actionApprove($id)
    {
        $model = Candidate::findOne((int) $id);

        if(!$model) {
            return [
                "operation" => "error",
                "message" => "Candidate not found"
            ];
        }

        $model->approved = 1;

        if (!$model->save())
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

        Yii::info('['.$model->candidate_email.' Account Approved] Candidate account approved by '.Yii::$app->user->identity->admin_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Candidate account approved successfully"
        ];
    }

    /**
     * Return candidate's salary transfer with status
     */
    public function actionTransfers($id)
    {
        $model = Candidate::findOne((int) $id);

        if(!$model)
            return [];

        return $model->paidTransferCandidate;
    }
}
