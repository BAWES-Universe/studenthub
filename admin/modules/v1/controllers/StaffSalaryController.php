<?php

namespace admin\modules\v1\controllers;

use admin\models\Staff;
use common\models\StaffSalary;
use Yii;
use yii\db\Expression;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use yii\filters\Cors;
use yii\filters\auth\HttpBearerAuth;
use yii\web\NotFoundHttpException;

/**
 * StaffSalary controller - Manage staff accounts as Admin
 */
class StaffSalaryController extends Controller
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
     * Return a List of Staff Accounts available.
     */
    public function actionList()
    {
        $query = StaffSalary::find();
        if($staff_id = Yii::$app->request->get('staff_id',null)) {
            $query->andWhere(['staff_id' => $staff_id]);
        }
        if($month = Yii::$app->request->get('month',null)) {
            $query->andFilterWhere(['YEAR(salary_date)'=>
                new \yii\db\Expression("YEAR('$month')")
            ]);
        }

        if($month = Yii::$app->request->get('month',null)) {
            $query->andFilterWhere(['MONTH(salary_date)'=>
                new \yii\db\Expression("MONTH('$month')")
            ]);
        }

        $query->orderBy('created_at desc');
        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * @return string[]
     */
    public function actionCreateSalary() {

        $list = Yii::$app->request->post('list');
        $date = Yii::$app->request->post('month');
        foreach ($list as $each) {
            $staff = Staff::findOne($each);
            $model = new StaffSalary();
            $model->staff_id = $each;
            $model->salary = $staff->staff_salary;
            $model->salary_currency = $staff->staff_salary_currency;
            $model->comment = 'Monthly Salary';
            $model->salary_date = date('Y-m-d',strtotime($date));
            $model->save(false);
        }

        return [
            "operation" => "success",
            "message" => "Salary data Saved Successfully"
        ];
    }

    /**
     * Delete an account
     * @param  integer $id
     * @return array
     */
    public function actionDelete($id)
    {
        $staffMember = $this->findModel($id);

        if($staffMember)
        {
            Yii::info('[Salary Entry Deleted] Salary "'.$staffMember->staff_id.'" soft deleted by Admin: "'.Yii::$app->user->identity->admin_name.'"', __METHOD__);

            // Delete the account
            $staffMember->delete();

            return [
                "operation" => "success",
                "message" => "Staff salary entry deleted successfully"
            ];
        }else{
            return [
                "operation" => "error",
                "message" => "Staff salary entry not found or already deleted"
            ];
        }

        // Error for cases not accounted for
        return [
            "operation" => "error",
            "message" => "Unknown error occured, please contact us for assistance."
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    /**
     * Add staff salary
     */
    public function actionAddSalary($id)
    {
        $staff = $this->findModel($id);

        $model = new StaffSalary();

        $model->staff_id = $staff->staff_id;
        $model->salary =Yii::$app->request->getBodyParam("salary");
        $model->salary_currency = Yii::$app->request->getBodyParam("salary_currency");
        $model->comment = Yii::$app->request->getBodyParam("comment");
        $model->salary_date = date('Y-m-d', strtotime(Yii::$app->request->getBodyParam("salary_date")));

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
                    "message" => "We've faced a problem adding salary, please contact us for assistance."
                ];
            }
        }

        /*if(YII_ENV == 'prod') {
            Yii::$app->eventManager->setUser('admin_' . Yii::$app->user->getId(), [
                '$first_name' => Yii::$app->user->identity->admin_name,
                '$email' => Yii::$app->user->identity->admin_email
            ]);
        }*/

        //todo: send to segment

        Yii::info('[Staff Salary Added] For "'.$staff->staff_email.'" by Admin: "'.Yii::$app->user->identity->admin_name.'"', __METHOD__);

        return [
            "operation" => "success",
            "message" => "Staff salary successfully added"
        ];
    }

    /**
     * Add staff salary
     */
    public function actionUpdateSalary($id)
    {
        $model = $this->findModel($id);
        $model->salary =Yii::$app->request->getBodyParam("salary");
        $model->salary_currency = Yii::$app->request->getBodyParam("salary_currency");
        $model->comment = Yii::$app->request->getBodyParam("comment");
        $model->salary_date = date('Y-m-d', strtotime(Yii::$app->request->getBodyParam("salary_date")));

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
                    "message" => "We've faced a problem adding salary, please contact us for assistance."
                ];
            }
        }

        /*if(YII_ENV == 'prod') {
            Yii::$app->eventManager->setUser('admin_' . Yii::$app->user->getId(), [
                '$first_name' => Yii::$app->user->identity->admin_name,
                '$email' => Yii::$app->user->identity->admin_email
            ]);
        }*/

        //todo: send to segment

        Yii::info('[Staff Salary Added] For "'.$model->staff->staff_email.'" by Admin: "'.Yii::$app->user->identity->admin_name.'"', __METHOD__);

        return [
            "operation" => "success",
            "message" => "Staff salary successfully added"
        ];
    }
    /**
     * Finds the Staff model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return StaffSalary the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = StaffSalary::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
