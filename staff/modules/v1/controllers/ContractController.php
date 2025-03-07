<?php

namespace staff\modules\v1\controllers;

use Yii;
use common\models\Contract;
use yii\data\ActiveDataProvider;
use yii\db\Expression;
use yii\rest\Controller;
use yii\web\NotFoundHttpException;

class ContractController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // remove authentication filter for cors to work
        unset($behaviors['authenticator']);

        // Allow XHR Requests from our different subdomains and dev machines
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::class,
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
            'class' => \yii\filters\auth\HttpBearerAuth::class,
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
            'collectionOptions' => ['GET', 'OPTIONS'],
            'resourceOptions' => ['GET', 'OPTIONS'],
        ];
        return $actions;
    }

    /**
     * Return a List of Company Accounts available.
     */
    public function actionList()
    {
        $company_id = Yii::$app->request->get("company_id");
        $type = Yii::$app->request->get("type");
        $q = Yii::$app->request->get("q");

        $query = Contract::find();

        //list only candidate contracts
        $query->andWhere(new Expression("contract.candidate_id IS NOT NULL"));

        if ($company_id) {
            $query->andWhere([
                "OR",
                ['company_id' => $company_id],
                ['parent_company_id' => $company_id]
            ]);
        }

        if ($type) {
            $query->andWhere(['type' => $type]);
        }

        if ($q) {
            $query->filterSearch($q);
        }

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * @param $id
     * @return Contract
     * @throws NotFoundHttpException
     */
    public function actionView($id)
    {
        return $this->findModel($id);
    }

    /**
     * @param $id
     * @return array|string[]
     * @throws NotFoundHttpException
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);

        $model->deleted = true;

        if (!$model->save()) {
            if (isset($model->errors)) {
                return [
                    "operation" => "error",
                    "message" => $model->getErrors()
                ];
            } else {
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem deleting the contract, please contact us for assistance"
                ];
            }
        }

        return [
            "operation" => "success",
            "message" => "Contract successfully deleted",
        ];
    }

    /**
     * @return array|string[]
     */
    public function actionCreate()
    {
        $model = new Contract();

        $model->scenario = Contract::SCENARIO_ASSIGN;// _TEMPLATE;

        $model->company_id = Yii::$app->request->getBodyParam("company_id");
        $model->parent_company_id = Yii::$app->request->getBodyParam("parent_company_id");
        $model->candidate_id = Yii::$app->request->getBodyParam("candidate_id");
        $model->store_id = Yii::$app->request->getBodyParam("store_id");

        $model->type = Yii::$app->request->getBodyParam("type");
        $model->detail = Yii::$app->request->getBodyParam("detail");
        $model->start_date = Yii::$app->request->getBodyParam("start_date");
        $model->end_date = Yii::$app->request->getBodyParam("end_date");
        $model->transfer_cost = Yii::$app->request->getBodyParam("transfer_cost");
        $model->currency_code = Yii::$app->request->getBodyParam("currency_code");
        $model->status =  Yii::$app->request->getBodyParam("status");
        $model->auto_generate = Yii::$app->request->getBodyParam("auto_generate");
        $model->amountDetails = Yii::$app->request->getBodyParam("amount");

        if (!$model->save()) {
            if (isset($model->errors)) {
                return [
                    "operation" => "error",
                    "message" => $model->getErrors()
                ];
            } else {
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem adding the contract, please contact us for assistance"
                ];
            }
        }

        return [
            "operation" => "success",
            "message" => "Contract successfully added",
        ];
    }

    /**
     * @param $id
     * @return array|string[]
     * @throws NotFoundHttpException
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        
        $model->scenario = Contract::SCENARIO_ASSIGN;// _TEMPLATE;

        $model->company_id = Yii::$app->request->getBodyParam("company_id");
        $model->parent_company_id = Yii::$app->request->getBodyParam("parent_company_id");
        $model->candidate_id = Yii::$app->request->getBodyParam("candidate_id");
        $model->store_id = Yii::$app->request->getBodyParam("store_id");

        $model->type = Yii::$app->request->getBodyParam("type");
        $model->detail = Yii::$app->request->getBodyParam("detail");
        $model->start_date = Yii::$app->request->getBodyParam("start_date");
        $model->end_date = Yii::$app->request->getBodyParam("end_date");
        $model->transfer_cost = Yii::$app->request->getBodyParam("transfer_cost");
        $model->currency_code = Yii::$app->request->getBodyParam("currency_code");
        $model->status =  Yii::$app->request->getBodyParam("status");
        $model->auto_generate = Yii::$app->request->getBodyParam("auto_generate");
        $model->amountDetails = Yii::$app->request->getBodyParam("amount");

        if (!$model->save()) {
            if (isset($model->errors)) {
                return [
                    "operation" => "error",
                    "message" => $model->getErrors()
                ];
            } else {
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem updating the contract, please contact us for assistance"
                ];
            }
        }

        return [
            "operation" => "success",
            "message" => "Contract successfully updated",
        ];
    }

    /**
     * Finds the Contract model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return \common\models\Contract the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Contract::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}