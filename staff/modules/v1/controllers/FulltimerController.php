<?php

namespace staff\modules\v1\controllers;

use Yii;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use common\models\Fulltimer;
use yii\filters\Cors;
use yii\filters\auth\HttpBearerAuth;
use yii\web\NotFoundHttpException;


/**
 * Fulltimer controller - Manage fulltimer as Admin
 */
class FulltimerController extends Controller
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
     * Return a List of Fulltimer Accounts available.
     * @return ActiveDataProvider
     */
    public function actionList()
    {
        $query = Fulltimer::find();

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * load fulltimer details
     * @param type $id
     * @return type
     */
    public function actionView($id)
    {
        return $this->findModel($id);
    }

    /**
     * Create a fulltimer account
     * @return array
     */
    public function actionCreate()
    {
        $model = new Fulltimer();

        $model->nationality_id = Yii::$app->request->getBodyParam("nationality_id");
        $model->fulltimer_area_uuid = Yii::$app->request->getBodyParam("area_uuid");
        $model->country_id = Yii::$app->request->getBodyParam("country_id");
        $model->fulltimer_latitude = Yii::$app->request->getBodyParam("latitude");
        $model->fulltimer_longitude = Yii::$app->request->getBodyParam("longitude");
        $model->fulltimer_name = Yii::$app->request->getBodyParam("name");
        $model->fulltimer_phone = Yii::$app->request->getBodyParam("phone");
        $model->fulltimer_email = Yii::$app->request->getBodyParam("email");
        $model->fulltimer_pdf_cv = Yii::$app->request->getBodyParam("pdf_cv");

        $model->tags = Yii::$app->request->getBodyParam("tags");

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
                    "message" => "We've faced a problem creating the fulltimer, please contact us for assistance."
                ];
            }
        }

        return [
            "operation" => "success",
            "message" => "Fulltimer created successfully"
        ];
    }

    /**
     * Create a fulltimer account
     * @param $id
     * @return array
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        $model->nationality_id = Yii::$app->request->getBodyParam("nationality_id");
        $model->fulltimer_area_uuid = Yii::$app->request->getBodyParam("area_uuid");
        $model->country_id = Yii::$app->request->getBodyParam("country_id");
        $model->fulltimer_latitude = Yii::$app->request->getBodyParam("latitude");
        $model->fulltimer_longitude = Yii::$app->request->getBodyParam("longitude");
        $model->fulltimer_name = Yii::$app->request->getBodyParam("name");
        $model->fulltimer_phone = Yii::$app->request->getBodyParam("phone");
        $model->fulltimer_email = Yii::$app->request->getBodyParam("email");
        $model->fulltimer_pdf_cv = Yii::$app->request->getBodyParam("pdf_cv");
        
        $model->tags = Yii::$app->request->getBodyParam("tags");
        
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
                    "message" => "We've faced a problem updating the fulltimer, please contact us for assistance."
                ];
            }
        }

        return [
            "operation" => "success",
            "message" => "Fulltimer successfully updated"
        ];
    }

    /**
     * Delete an fulltimer
     * @param  integer $id
     * @return array
     */
    public function actionDelete($id)
    {
        $fulltimer = $this->findModel($id);

        $fulltimer->delete();

        return [
            "operation" => "success",
            "message" => "Fulltimer deleted successfully"
        ];
    }

    /**
     * Finds the Fulltimer model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Fulltimer the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Fulltimer::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
