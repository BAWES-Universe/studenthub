<?php

namespace admin\modules\v1\controllers;

use common\models\Fulltimer;
use Yii;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use yii\filters\Cors;
use yii\filters\auth\HttpBearerAuth;
use yii\web\NotFoundHttpException;


/**
 * Fulltimer controller - Manage Candidate accounts as Admin
 */
class FulltimerController extends Controller
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
    public function actionList()
    {
        $currency = Yii::$app->request->headers->get("Currency");

        $name = Yii::$app->request->get('name', null);

        $query = Fulltimer::find();

        if($currency) {
            $query->andWhere(['fulltimer.currency_code' => $currency]);
        }

        if ($name && !is_numeric($name)) {
            $query->filterName($name);
        }

        if ($name && is_numeric($name)) {
            $query->filterById($name);
        }

        if (Yii::$app->request->get('email', null)) {
            $query->filterEmail(Yii::$app->request->get('email'));
        }

        if (Yii::$app->request->get('phone', null)) {
            $query->filterPhone(Yii::$app->request->get('phone'));
        }

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * @param $id
     * @return Transfer
     * @throws NotFoundHttpException
     */
    public function actionView($id)
    {
        return $this->findModel($id);
    }

    /**
     * Delete an account
     * @param  integer $id
     * @return array
     */
    public function actionDelete($id)
    {
//        if (Yii::$app->user->identity->admin_limited_access) {
//            return [
//                "operation" => "error",
//                "message" => "You are not allowed to perform this action"
//            ];
//        }
//
//        $model = Fulltimer::findOne(['u'=>$id]);
//
//        if (!$model || ($model && $model->deleted)) {
//            return [
//                "operation" => "success",
//                "message" => "Candidate account already deleted"
//            ];
//        }
//
//        $model->scenario = 'deleteCandidate';
//        $model->deleted = 1;
//
//        if (!$model->save()) {
//            if(isset($model->errors)){
//                return [
//                    "operation" => "error",
//                    "message" => $model->errors
//                ];
//            }else{
//                return [
//                    "operation" => "error",
//                    "message" => "We've faced a problem updating the account, please contact us for assistance."
//                ];
//            }
//        }
//        Yii::$app->algolia->delete(Yii::$app->params['algolia_candidate_index'], $model->candidate_id);
//
//        Yii::info('['.$model->candidate_email.' Account Deleted] Candidate account Deleted by '.Yii::$app->user->identity->admin_name, __METHOD__);
//
//        return [
//            "operation" => "success",
//            "message" => "Candidate account deleted successfully"
//        ];
    }

    /**
     * Finds the Fulltimer model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Fulltimer the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Fulltimer::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
