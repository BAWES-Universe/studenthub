<?php

namespace admin\modules\v1\controllers;

use Yii;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use common\models\Country;
use yii\filters\Cors;
use yii\filters\auth\HttpBearerAuth;
use yii\web\NotFoundHttpException;


/**
 * Country controller - Manage Country as Admin
 */
class CountryController extends Controller
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
     * Return a List of Country Accounts available.
     */
    public function actionList()
    {
        $s = Yii::$app->request->get('query');
        
        $query = Country::find()
            ->filterNotFromGoogle()
            ->listWithCandidateCount();
            
        if ($s) {
            $query->filterName($s);
        }

        $query->asArray();

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }
    
    /**
     * load country details
     * @param type $id
     * @return type
     */
    public function actionView($id)
    {
        return $this->findModel($id);
    }

    /**
     * Create a Country account
     * @return array
     */
    public function actionCreate()
    {
        // Attempt to create new Country
        $model = new Country();

        $model->country_name_en = Yii::$app->request->getBodyParam("name_en");
        $model->country_name_ar = Yii::$app->request->getBodyParam("name_ar");
        $model->country_nationality_name_en = Yii::$app->request->getBodyParam("nationality_name_en");
        $model->country_nationality_name_ar = Yii::$app->request->getBodyParam("nationality_name_ar");
        $model->country_from_google_map = Yii::$app->request->getBodyParam("google_map");

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
                    "message" => "We've faced a problem creating the country, please contact us for assistance."
                ];
            }
        }

        Yii::info('[Country Added: '.$model->country_name_en.'] By '.Yii::$app->user->identity->admin_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Country created successfully"
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    /**
     * Create a Country account
     * @param $id
     * @return array
     */
    public function actionUpdate($id)
    {
        // Attempt to create new account
        $model = $this->findModel((int) $id);

        if(!$model){
            return [
                "operation" => "error",
                "message" => "Country not found."
            ];
        }

        $model->country_name_en = Yii::$app->request->getBodyParam("name_en");
        $model->country_name_ar = Yii::$app->request->getBodyParam("name_ar");
        $model->country_nationality_name_en = Yii::$app->request->getBodyParam("nationality_name_en");
        $model->country_nationality_name_ar = Yii::$app->request->getBodyParam("nationality_name_ar");
        $model->country_from_google_map = Yii::$app->request->getBodyParam("google_map");


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
                    "message" => "We've faced a problem updating the country, please contact us for assistance."
                ];
            }
        }

        Yii::info('[Country Updated: '.$model->country_name_en.'] By '.Yii::$app->user->identity->admin_name, __METHOD__);


        return [
            "operation" => "success",
            "message" => "Country successfully updated"
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }
    
    /**
     * Finds the Country model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Transfer the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Country::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
