<?php

namespace admin\modules\v1\controllers;

use admin\models\StaffExpenses;
use Yii;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use yii\filters\Cors;
use yii\filters\auth\HttpBearerAuth;
use yii\web\NotFoundHttpException;

/**
 * StaffExpenses controller - Manage brand as Admin
 */
class StaffExpensesController extends Controller
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
     * Return a List of Brand Accounts available.
     * @return ActiveDataProvider
     */
    public function actionList()
    {
        $query = StaffExpenses::find()
            ->orderBy('updated_at DESC');

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * @param $id
     * @return StaffExpenses
     * @throws NotFoundHttpException
     */
    public function actionView($id)
    {
        return $this->findModel($id);
    }

    /**
     * Create a Note account
     * @return array
     */
    public function actionCreate()
    {
        // Attempt to create new brand
        $model = new StaffExpenses();

        $model->supplier = Yii::$app->request->getBodyParam("supplier");
        $model->category = Yii::$app->request->getBodyParam("category");
        $model->purchase_date = Yii::$app->request->getBodyParam("purchase_date");
        $model->total_amount = Yii::$app->request->getBodyParam("total_amount");
        $model->currency = Yii::$app->request->getBodyParam("currency");
        $model->vat = Yii::$app->request->getBodyParam("vat");
        $model->reimbursable = Yii::$app->request->getBodyParam("reimbursable");
        $model->description = Yii::$app->request->getBodyParam("description");
        $model->file = Yii::$app->request->getBodyParam("file");
        $model->staff_id = Yii::$app->user->getId();

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
                    "message" => "We've faced a problem creating the Note, please contact us for assistance."
                ];
            }
        }
        return [
            "operation" => "success",
            "message" => "Staff Expense created successfully",
        ];
    }

    /**
     * Create a Note account
     * @param $id
     * @return array
     */
    public function actionUpdate($id)
    {
        // Attempt to create new account
        $model = $this->findModel($id);

        $model->supplier = Yii::$app->request->getBodyParam("supplier");
        $model->category = Yii::$app->request->getBodyParam("category");
        $model->purchase_date = Yii::$app->request->getBodyParam("purchase_date");
        $model->total_amount = Yii::$app->request->getBodyParam("total_amount");
        $model->currency = Yii::$app->request->getBodyParam("currency");
        $model->vat = Yii::$app->request->getBodyParam("vat");
        $model->reimbursable = Yii::$app->request->getBodyParam("reimbursable");
        $model->description = Yii::$app->request->getBodyParam("description");
        $model->file = Yii::$app->request->getBodyParam("file");
        $model->staff_id = Yii::$app->user->getId();

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
                    "message" => "We've faced a problem updating the Note, please contact us for assistance."
                ];
            }
        }

        return [
            "operation" => "success",
            "message" => "Staff Expense successfully updated"
        ];
    }

    /**
     * Create a Note account
     * @param $id
     * @return array
     */
    public function actionChangeStatus($id)
    {
        // Attempt to create new account
        $model = $this->findModel($id);

        $model->status = Yii::$app->request->getBodyParam("status");

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
                    "message" => "We've faced a problem updating the Note, please contact us for assistance."
                ];
            }
        }

        return [
            "operation" => "success",
            "message" => "Staff Expense status changed successfully"
        ];
    }

    /**
     * Delete an account
     * @param  integer $id
     * @return array
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);

        if(!$model) {
            return [
                "operation" => "error",
                "message" => "Note not found or already deleted"
            ];
        }

        $model->delete();

        return [
            "operation" => "success",
            "message" => "Staff Expense deleted successfully"
        ];
    }
    
    /**
     * Finds the Brand model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return StaffExpenses the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = StaffExpenses::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
