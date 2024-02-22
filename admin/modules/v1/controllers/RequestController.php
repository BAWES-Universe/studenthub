<?php

namespace admin\modules\v1\controllers;

use admin\models\Staff;
use Yii;
use yii\db\Expression;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use admin\models\Company;
use admin\models\Request;
use yii\filters\Cors;
use yii\filters\auth\HttpBearerAuth;
use yii\web\NotFoundHttpException;


/**
 * Request controller - Manage brand as Admin
 */
class RequestController extends Controller
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
     * Return a List of requests available.
     * @return ActiveDataProvider
     */
    public function actionList()
    {
        $currency = Yii::$app->request->headers->get("Currency");
        $start_date = Yii::$app->request->get('start_date', null);
        $end_date = Yii::$app->request->get('end_date', null);

        $query = Request::find();

        if($currency) {
            $query->joinWith(['company'])
                ->andWhere(['company.currency_code' => $currency]);
        }

        if (Yii::$app->request->get('staff_id', null)) {
            $query->filterByStaff(Yii::$app->request->get('staff_id'));
        }

        if (Yii::$app->request->get('name', null)) {
            $query->filterByTitle(Yii::$app->request->get('name'));
        }
        if (Yii::$app->request->get('status', null)) {
            $query->filterByStatus(Yii::$app->request->get('status'));
        }
        if (Yii::$app->request->get('type', null)) {
            $query->filterByType(Yii::$app->request->get('type'));
        }
        if (Yii::$app->request->get('company_id', null)) {
            $query->filterByCompany(Yii::$app->request->get('company_id'));
        }

       if ($start_date) {
            $query->andWhere(new Expression("DATE(request_created_datetime) >= DATE('".
                date('Y-m-d', strtotime ($start_date)) ."')"));
        }
        if ($end_date) {
            $query->andWhere(new Expression("DATE(request_created_datetime) <= DATE('".
                date('Y-m-d', strtotime ($start_date)) ."')"));
        }

        $query->orderByDateDESC();
        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * @param $id
     * @return Request
     * @throws NotFoundHttpException
     */
    public function actionView($id)
    {
        return $this->findModel($id);
    }
    
    /**
     * Create a Request 
     * @return array
     */
    public function actionCreate()
    {
        // Attempt to create new request
        $model = new Request();
 
        $model->company_id = Yii::$app->request->getBodyParam("company_id");
        $model->contact_uuid = Yii::$app->request->getBodyParam("contact_uuid");
        $model->request_position_type = Yii::$app->request->getBodyParam("position_type");
        $model->request_position_title = Yii::$app->request->getBodyParam("position_title");
        $model->request_number_of_employees = Yii::$app->request->getBodyParam("number_of_employees");
        $model->request_additional_info = Yii::$app->request->getBodyParam("additional_info");
        $model->currency_code = Yii::$app->request->getBodyParam("currency_code");
        $model->request_status = Request::STATUS_STARTED;

        if(!$model->currency_code) {
            $model->currency_code = Yii::$app->request->headers->get("Currency");
        }

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
                    "message" => "We've faced a problem creating the Request, please contact us for assistance."
                ];
            }
        }

        return [
            "operation" => "success",
            "message" => "Request created successfully"
        ];
    }

    /**
     * Create a Request
     * @param $id
     * @return array
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if(!$model){
            return [
                    "operation" => "error",
                    "message" => "Request not found."
                ];
        }

        $model->company_id = Yii::$app->request->getBodyParam("company_id");
        $model->contact_uuid = Yii::$app->request->getBodyParam("contact_uuid");
        $model->request_position_type = Yii::$app->request->getBodyParam("position_type");
        $model->request_position_title = Yii::$app->request->getBodyParam("position_title");
        $model->request_number_of_employees = Yii::$app->request->getBodyParam("number_of_employees");
        $model->request_additional_info = Yii::$app->request->getBodyParam("additional_info");
        $model->currency_code = Yii::$app->request->getBodyParam("currency_code");

        if(!$model->currency_code) {
            $model->currency_code = Yii::$app->request->headers->get("Currency");
        }

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
                    "message" => "We've faced a problem updating the Request, please contact us for assistance."
                ];
            }
        }

        return [
            "operation" => "success",
            "message" => "Request successfully updated"
        ];
    }

    /**
     * Assign request to staff
     * @param $id
     * @return array|string[]
     * @throws NotFoundHttpException
     */
    public function actionAssign($id)
    {
        $model = $this->findModel($id);

        $model->staff_id = Yii::$app->request->getBodyParam("staff_id");

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
                    "message" => "We've faced a problem updating the Request, please contact us for assistance."
                ];
            }
        }

        $staff = Staff::find()
            ->andWhere(['staff_id' => $id])
            ->one();

        return [
            "operation" => "success",
            "message" => "Request assigned to staff successfully",
            "staff" => $staff
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
        $company_id = $model->company_id;
        // Delete model
        $model->delete();
        Company::updateRequest($company_id);
        return [
            "operation" => "success",
            "message" => "Request deleted successfully"
        ];
    }

    /**
     * check if request updated
     */
    public function actionIsRequestUpdated($id) {

        $request = $this->findModel ($id);

        return [
            "request_updated_datetime" => $request->request_updated_datetime
        ];
    }

    /**
     * Finds the Request model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Request the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Request::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
