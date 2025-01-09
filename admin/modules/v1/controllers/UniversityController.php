<?php

namespace admin\modules\v1\controllers;

use Yii;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use admin\models\University;
use yii\web\NotFoundHttpException;


/**
 * University controller - Manage university as Admin
 */
class UniversityController extends Controller
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
     * Return a List of University Accounts available.
     */
    public function actionList()
    {
        $query = University::find()
            ->listWithCandidateCount();

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * load university details
     * @param type $id
     * @return type
     */
    public function actionView($id)
    {
        return $this->findModel($id);
    }
    
    /**
     * Create a university account
     */
    public function actionCreate()
    {
        // Attempt to create new university
        $model = new University();

        $model->university_name_en = Yii::$app->request->getBodyParam("name_en");
        $model->university_name_ar = Yii::$app->request->getBodyParam("name_ar");

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
                    "message" => "We've faced a problem creating the university, please contact us for assistance."
                ];
            }
        }

        Yii::info('[University Account Created] University "'.$model->university_name_en.'" created by Admin: "'.Yii::$app->user->identity->admin_name.'"', __METHOD__);

        return [
            "operation" => "success",
            "message" => "University created successfully"
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    /**
     * Create a university account
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
                    "message" => "University not found."
                ];
        }

        $model->university_name_en = Yii::$app->request->getBodyParam("name_en");
        $model->university_name_ar = Yii::$app->request->getBodyParam("name_ar");

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
                    "message" => "We've faced a problem updating the university, please contact us for assistance."
                ];
            }
        }

        Yii::info('[University Account Updated] University "'.$model->university_name_en.'" updated by Admin: "'.Yii::$app->user->identity->admin_name.'"', __METHOD__);

        return [
            "operation" => "success",
            "message" => "University successfully updated"
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    /**
     * Delete an account
     * @param  integer $id
     * @return array
     */
    public function actionDelete($id)
    {
        $university = $this->findModel((int)$id);

        if(!$university) {
            return [
                "operation" => "error",
                "message" => "University not found or already deleted"
            ];

        }

        if (count($university->candidates)>0) {
            return [
                "operation" => "error",
                "message" => "University cannot be delete as ".count($university->candidates)." candidate(s) belongs to this university"
            ];
        }

        Yii::info('[University Soft Deleted] University "'.$university->university_name_en.'" account deleted by Admin: "'.Yii::$app->user->identity->admin_name.'"', __METHOD__);

        // Delete university
        if ($university->softDelete()) {

            return [
                "operation" => "success",
                "message" => "University deleted successfully"
            ];
        } else {
            return [
                "operation" => "error",
                "message" => "University deleted failed. Please try again."
            ];
        }

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    public function actionDownloadListExcel()
    {
        $query = University::find();

        header('Access-Control-Allow-Origin: *');

        \common\components\PhpExcel::export([
            'isMultipleSheet' => false,
            'models' => $query->all(),
            'columns' => [
                'university_id',
                'university_name_en',
                'university_name_ar',
                [
                    'attribute'=>'Total Candidate',
                    'label'=>'Total Candidate',
                    'value'=>function($model) {
                        return $model->getCandidates()->count();
                    }
                ],
            ]
        ]);
    }
    /**
     * Finds the University model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Transfer the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = University::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
