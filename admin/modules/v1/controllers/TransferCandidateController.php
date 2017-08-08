<?php

namespace admin\modules\v1\controllers;

use admin\models\Candidate;
use Yii;
use yii\base\Exception;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use admin\models\Invoice;
use admin\models\Transfer;
use admin\models\TransferCandidate;
use yii\filters\auth\HttpBearerAuth;
/**
 * Transfer controller - Manage Transfer
 */
class TransferCandidateController extends Controller
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
                'Access-Control-Request-Method' => ['GET', 'POST', 'PATCH', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => null,
                'Access-Control-Max-Age' => 86400,
                'Access-Control-Expose-Headers' => [
                    'filename',
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
        $behaviors['authenticator']['except'] = ['options','text'];

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
            'resourceOptions' => ['GET', 'POST', 'PATCH', 'OPTIONS'],
        ];
        return $actions;
    }

    /**
     * Return a List of Transfer.
     * @return ActiveDataProvider
     */
    public function actionList()
    {
        $tc_id          = Yii::$app->request->get('tc_id');
        $status         = Yii::$app->request->get('status');
        $candidate_id   = Yii::$app->request->get('candidate_id');

        $query = TransferCandidate::find();

        if ($status) {
            $query->totalPaymentStatus($status);
        }

        if ($candidate_id) {
            $query->filterCandidate($candidate_id);
        }

        if ($tc_id) {
            $query->filterByPrimaryKey($tc_id);
        }

        $query->payableWithPaid();

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * @param $id
     * @return array
     */
    public function actionUnpaid($id)
    {
        return TransferCandidate::markUnpaid($id);
    }
}
