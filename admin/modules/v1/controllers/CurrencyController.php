<?php

namespace admin\modules\v1\controllers;

use Yii;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use common\models\Currency;
use yii\filters\Cors;
use yii\filters\auth\HttpBearerAuth;
use yii\web\NotFoundHttpException;


/**
 * Currency controller - Manage Currency as Admin
 */
class CurrencyController extends Controller
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
     * Return a List of Currency Accounts available.
     * @return ActiveDataProvider
     */
    public function actionList()
    {
        $keyword = Yii::$app->request->get('keyword');
        $page = Yii::$app->request->get('page');

        $query = Currency::find();
         //   ->andWhere(['status' => 1]);

        if ($keyword) {
            $query->andWhere([
                "OR",
                ['like', 'title', $keyword],
                ['like', 'code', $keyword]
            ]);
        }

        if ($page == -1) {
            return new ActiveDataProvider([
                'query' => $query,
                'pagination' => false
            ]);
        }

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * load Currency details
     * @param type $id
     * @return type
     */
    public function actionView($id)
    {
        return $this->findModel($id);
    }

    /**
     * Create a Currency
     * @return array
     */
    public function actionCreate()
    {
        $model = new Currency();

        $model->title = Yii::$app->request->getBodyParam("title");
        $model->code = Yii::$app->request->getBodyParam("code");
        $model->currency_symbol = Yii::$app->request->getBodyParam("currency_symbol");
        $model->rate = Yii::$app->request->getBodyParam("rate");
        $model->decimal_place = Yii::$app->request->getBodyParam("decimal_place");
        $model->sort_order = Yii::$app->request->getBodyParam("sort_order");
        $model->status = (int) Yii::$app->request->getBodyParam("status");

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
                    "message" => "We've faced a problem creating the Currency, please contact us for assistance."
                ];
            }
        }

        return [
            "operation" => "success",
            "message" => "Currency added successfully"
        ];
    }

    /**
     * Create a Currency account
     * @param $id
     * @return array
     */
    public function actionUpdate($id)
    {
        // Attempt to create new account
        $model = $this->findModel($id);

        $model->title = Yii::$app->request->getBodyParam("title");
        $model->code = Yii::$app->request->getBodyParam("code");
        $model->currency_symbol = Yii::$app->request->getBodyParam("currency_symbol");
        $model->rate = Yii::$app->request->getBodyParam("rate");
        $model->decimal_place = Yii::$app->request->getBodyParam("decimal_place");
        $model->sort_order = Yii::$app->request->getBodyParam("sort_order");
        $model->status = (int) Yii::$app->request->getBodyParam("status");

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
                    "message" => "We've faced a problem updating the Currency, please contact us for assistance."
                ];
            }
        }

        return [
            "operation" => "success",
            "message" => "Currency successfully updated"
        ];
    }

    /**
     * Delete an account
     * @param  integer $id
     * @return array
     */
    public function actionDelete($id)
    {
        $Currency = $this->findModel($id);

        // Delete Currency
        $Currency->delete();

        return [
            "operation" => "success",
            "message" => "Currency deleted successfully"
        ];
    }

    /**
     * Finds the Currency model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Transfer the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Currency::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
 