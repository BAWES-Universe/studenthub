<?php

namespace staff\modules\v1\controllers;

use common\models\DiscountCategory;
use Yii;
use yii\data\ActiveDataProvider;
use yii\rest\Controller;
use yii\web\NotFoundHttpException;

class DiscountCategoryController extends Controller
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
            'collectionOptions' => ['GET', 'POST', 'HEAD', 'OPTIONS'],
            'resourceOptions' => ['GET', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
        ];
        return $actions;
    }

    /**
     * Return a List of DiscountCategory Accounts available.
     */
    public function actionList()
    {
        $query = DiscountCategory::find();

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * load DiscountCategory details
     * @param type $id
     * @return type
     */
    public function actionView($id)
    {
        return $this->findModel($id);
    }

    /**
     * Create a DiscountCategory account
     */
    public function actionCreate()
    {
        $model = new DiscountCategory();

        $model->name_en = Yii::$app->request->getBodyParam("name_en");
        $model->name_ar = Yii::$app->request->getBodyParam("name_ar");
        $model->image = Yii::$app->request->getBodyParam("image");

        if (!$model->save()) {
            if (isset($model->errors)) {
                return [
                    "operation" => "error",
                    "message" => $model->errors
                ];
            } else {
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem creating the Discount Category, please contact us for assistance."
                ];
            }
        }

        Yii::info('[DiscountCategory Created] Discount Category "' . $model->name_en . '" created by Admin: "' . Yii::$app->user->identity->admin_name . '"', __METHOD__);

        return [
            "operation" => "success",
            "message" => "Discount Category created successfully"
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    /**
     * Create a DiscountCategory account
     * @param $id
     * @return array
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        $model->name_en = Yii::$app->request->getBodyParam("name_en");
        $model->name_ar = Yii::$app->request->getBodyParam("name_ar");
        $model->image = Yii::$app->request->getBodyParam("image");

        if (!$model->save()) {
            if (isset($model->errors)) {
                return [
                    "operation" => "error",
                    "message" => $model->errors
                ];
            } else {
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem updating the Discount Category, please contact us for assistance."
                ];
            }
        }

        Yii::info('[Discount Category Updated] Discount Category "' . $model->name_en . '" updated by Admin: "' . Yii::$app->user->identity->admin_name . '"', __METHOD__);

        return [
            "operation" => "success",
            "message" => "Discount Category successfully updated"
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

        Yii::info('[Discount Category Deleted] Discount Category "' . $discount->name_en . '" deleted by Admin: "' . Yii::$app->user->identity->admin_name . '"',
            __METHOD__);

        if ($discount->delete()) {

            return [
                "operation" => "success",
                "message" => "Discount Category deleted successfully"
            ];
        } else {
            return [
                "operation" => "error",
                "message" => "Discount Category deleted failed. Please try again."
            ];
        }

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    /**
     * Finds the DiscountCategory model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return DiscountCategory the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        $model = DiscountCategory::findOne($id);

        if ($model !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}