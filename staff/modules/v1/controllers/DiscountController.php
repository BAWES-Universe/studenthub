<?php

namespace staff\modules\v1\controllers;

use common\models\Discount;
use Yii;
use yii\data\ActiveDataProvider;
use yii\rest\Controller;
use yii\web\NotFoundHttpException;


class DiscountController extends Controller
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
     * Return a List of Discount Accounts available.
     */
    public function actionList()
    {
        $query = Discount::find();

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * load Discount details
     * @param type $id
     * @return type
     */
    public function actionView($id)
    {
        return $this->findModel($id);
    }

    /**
     * Create a Discount account
     */
    public function actionCreate()
    {
        $model = new Discount();

        $model->category_id = Yii::$app->request->getBodyParam("category_id");
        $model->company_id = Yii::$app->request->getBodyParam("company_id");
        $model->store_id = Yii::$app->request->getBodyParam("store_id");
        $model->description_en = Yii::$app->request->getBodyParam("description_en");
        $model->description_ar = Yii::$app->request->getBodyParam("description_ar");
        $model->how_to_apply_en = Yii::$app->request->getBodyParam("how_to_apply_en");
        $model->how_to_apply_ar = Yii::$app->request->getBodyParam("how_to_apply_ar");
        $model->image = Yii::$app->request->getBodyParam("image");
        $model->valid_until = Yii::$app->request->getBodyParam("valid_until");

        if (!$model->save()) {
            if (isset($model->errors)) {
                return [
                    "operation" => "error",
                    "message" => $model->errors
                ];
            } else {
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem creating the Discount, please contact us for assistance."
                ];
            }
        }

        Yii::info('[Discount Created] Discount "' . $model->description_en . '" created by Staff: "' . Yii::$app->user->identity->staff_name . '"', __METHOD__);

        return [
            "operation" => "success",
            "message" => "Discount created successfully"
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    /**
     * Create a Discount account
     * @param $id
     * @return array
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        $model->category_id = Yii::$app->request->getBodyParam("category_id");
        $model->company_id = Yii::$app->request->getBodyParam("company_id");
        $model->store_id = Yii::$app->request->getBodyParam("store_id");
        $model->description_en = Yii::$app->request->getBodyParam("description_en");
        $model->description_ar = Yii::$app->request->getBodyParam("description_ar");
        $model->how_to_apply_en = Yii::$app->request->getBodyParam("how_to_apply_en");
        $model->how_to_apply_ar = Yii::$app->request->getBodyParam("how_to_apply_ar");
        $model->image = Yii::$app->request->getBodyParam("image");
        $model->valid_until = Yii::$app->request->getBodyParam("valid_until");

        if (!$model->save()) {
            if (isset($model->errors)) {
                return [
                    "operation" => "error",
                    "message" => $model->errors
                ];
            } else {
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem updating the Discount, please contact us for assistance."
                ];
            }
        }

        Yii::info('[Discount Updated] Discount "' . $model->description_en . '" updated by Staff: "' . Yii::$app->user->identity->staff_name . '"', __METHOD__);

        return [
            "operation" => "success",
            "message" => "Discount successfully updated"
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    /**
     * Delete an account
     * @param integer $id
     * @return array
     */
    public function actionDelete($id)
    {
        $discount = $this->findModel($id);

        Yii::info('[Discount Deleted] Discount "' . $discount->description_en . '" deleted by Staff: "' . Yii::$app->user->identity->staff_name . '"',
            __METHOD__);

        if ($discount->delete()) {

            return [
                "operation" => "success",
                "message" => "Discount deleted successfully"
            ];
        } else {
            return [
                "operation" => "error",
                "message" => "Discount deleted failed. Please try again."
            ];
        }

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    /**
     * Finds the Discount model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return Discount the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        $model = Discount::findOne($id);

        if ($model !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}