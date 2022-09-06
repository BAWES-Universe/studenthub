<?php

namespace status\modules\v1\controllers;

use Yii;
use admin\models\Company;
use yii\data\ActiveDataProvider;
use yii\rest\Controller;
use yii\web\NotFoundHttpException;

class CompanyController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // remove authentication filter for cors to work
        unset($behaviors['authenticator']);

        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::className(),
            'cors' => [
                'Origin' => Yii::$app->params['allowedOrigins'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => null,
                'Access-Control-Max-Age' => 86400,
            ],
        ];

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
     * Return a List of Company Accounts available.
     * @return ActiveDataProvider
     */
    public function actionList()
    {
        $status = Yii::$app->request->getQueryParam("status",0);
        $name = Yii::$app->request->getQueryParam("name",0);
        $approved_to_hire = Yii::$app->request->getQueryParam("approved_to_hire");

        $query = Company::find()
            ->filterParent();

        if ($status == 1) {
            $query->filterActive();
        }

        if ($status == 2) {
            $query->filterInActive();
        }

        if ($status == 3) {
            $query->filterByActive40DaysPassedWithoutPayment();
        }

        if ($name) {
            $query->filterByName($name);
        }

        if (!is_null($approved_to_hire) && in_array ($approved_to_hire, [0, 1])) {
            $query->filterByApprovedToHire($approved_to_hire);
        }

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * @return array|\yii\db\DataReader
     * @throws \yii\db\Exception
     */
    public function actionYearReport() {
        $year = Yii::$app->request->get("year", date('Y'));
        $company_id = Yii::$app->request->get("company_id" , null);

        $stats = [];
        foreach(range(1,12) as $key => $value) {
            $value = str_pad($value,2,"0", STR_PAD_LEFT);
            // request
            $q = 'SELECT count(*) as total, MONTH(request_created_datetime) as month, YEAR(request_created_datetime) as year, monthname(str_to_date(MONTH(request_created_datetime),"%m")) as monthName FROM `request` WHERE';
            if ($company_id) {
                $q .= ' `company_id`=' . $company_id . ' AND ';
            }
            $q .= ' YEAR(request_created_datetime) = '.$year.' AND MONTH(request_created_datetime) = '.$value;

            $records = \Yii::$app->db->createCommand($q)->queryOne();

            $stats[$key]['request'] = $records['total'];
            $stats[$key]['month'] = date("F", mktime(0, 0, 0, $value, 10));
            $stats[$key]['month_number'] = $value;

            // suggestions
            $q = 'SELECT count(*) as total FROM `suggestion` left join request on request.request_uuid = suggestion.request_uuid WHERE';
            if ($company_id) {
                $q .= ' `request`.`company_id`=' . $company_id . ' AND ';
            }
            $q .= ' YEAR(suggestion_datetime) = '.$year.' AND MONTH(suggestion_datetime) = '.$value;

            $suggestion = \Yii::$app->db->createCommand($q)->queryOne();

            $stats[$key]['suggestions'] = $suggestion['total'];

            // hired
            $q = 'SELECT count(*) as total FROM `candidate_work_history` WHERE';
            if ($company_id) {
                $q .= ' (`company_id`=' . $company_id . ' OR `parent_company_id`=' . $company_id . ') AND ';
            }
            $q .= ' YEAR(start_date) = '.$year.' AND MONTH(start_date) = '.$value;

            $joined = \Yii::$app->db->createCommand($q)->queryOne();

            $stats[$key]['hired'] = $joined['total'];
        }
        return $stats;
    }

    /**
     * View company detail
     * @param $id
     * @return array|null|\yii\db\ActiveRecord
     */
    public function actionView($id)
    {
        $company = Company::find()
            ->filterCompany($id)
            ->one();

        if(!$company){
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        return $company;
    }

    /**
     * Finds the Company model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Company the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Company::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}