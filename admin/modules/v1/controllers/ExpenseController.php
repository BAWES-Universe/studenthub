<?php

namespace admin\modules\v1\controllers;

use Yii;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use admin\models\Expense;
use yii\filters\Cors;
use yii\filters\auth\HttpBearerAuth;
use yii\web\NotFoundHttpException;


/**
 * Expense controller - Manage expense as Admin
 */
class ExpenseController extends Controller
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
     * Return a List of Expense Accounts available.
     * @return ActiveDataProvider
     */
    public function actionList()
    {
        $query = Expense::find();

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * load expense details
     * @param type $id
     * @return type
     */
    public function actionView($id)
    {
        return $this->findModel($id);
    }

    /**
     * Create a expense account
     * @return array
     */
    public function actionCreate()
    {
        // Attempt to create new expense
        $model = new Expense();

        $model->title = Yii::$app->request->getBodyParam("title");
        $model->type = Yii::$app->request->getBodyParam("type");
        $model->detail = Yii::$app->request->getBodyParam("detail");
        $model->amount = Yii::$app->request->getBodyParam("amount");

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
                    "message" => "We've faced a problem creating the expense, please contact us for assistance."
                ];
            }
        }

        Yii::info('[Expense Added: '.$model->title.'] By '.Yii::$app->user->identity->admin_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Expense created successfully"
        ];
    }

    /**
     * Create a expense account
     * @param $id
     * @return array
     */
    public function actionUpdate($id)
    {
        // Attempt to create new account
        $model = $this->findModel((int) $id);

        $model->title = Yii::$app->request->getBodyParam("title");
        $model->type = Yii::$app->request->getBodyParam("type");
        $model->detail = Yii::$app->request->getBodyParam("detail");
        $model->amount = Yii::$app->request->getBodyParam("amount");

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
                    "message" => "We've faced a problem updating the expense, please contact us for assistance."
                ];
            }
        }

        //Yii::info('[Expense Updated: '.$model->expense_name.'] By '.Yii::$app->user->identity->admin_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Expense successfully updated"
        ];
    }

    /**
     * Delete an account
     * @param  integer $id
     * @return array
     */
    public function actionDelete($id)
    {
        $expense = $this->findModel((int)$id);

        // Delete expense
        $expense->softDelete();

        Yii::info('[Expense Soft Deleted: '.$expense->expense_name.'] By '.Yii::$app->user->identity->admin_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Expense deleted successfully"
        ];
    }

    /**
     * Finds the Expense model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Transfer the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Expense::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
