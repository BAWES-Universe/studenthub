<?php

namespace candidate\modules\v1\controllers;

use Yii;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use candidate\models\University;
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
        $behaviors['authenticator']['except'] = ['options','list'];
        
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
        $q = Yii::$app->request->getQueryParam('q');
        $page = Yii::$app->request->get("page");
        $limit = Yii::$app->request->get("limit");

        $query = University::find();
        
        if ($q) {
            $query->filterName($q);
        }

        if ($page == -1) {
            return new ActiveDataProvider([
                'query' => $query,
                'pagination' => false,
            ]);
        }

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $limit > 0 ? $limit: 200,
            ],
        ]);
    }

    /**
     * Create a University if it isnt exists
     */
    public function actionCreate()
    {
        // Attempt to create new University
        $model = new University();

        $model->university_name_ar = Yii::$app->request->getBodyParam("name");
        $model->university_name_en = Yii::$app->request->getBodyParam("name");
        
        $model->university_data_source = University::FROM_CANDIDATE;

        if (!$model->save())
        {
            if (isset($model->errors)) {
                return [
                    "operation" => "error",
                    "message" => $model->errors
                ];
            } else {
                return [
                    "operation" => "error",
                    "message" => Yii::t('candidate',"We've faced a problem creating the university, please contact us for assistance.")
                ];
            }
        }

        return [
            "operation" => "success",
            "message" => Yii::t('candidate',"University created successfully"),
            "university" => $model
        ];
    }

    /**
     * Check if typed university is exists
     */
    public function actionIsExists()
    {
        // Attempt to find typed university

        $keyword = Yii::$app->request->getBodyParam("keyword");

        $model = University::findOne([
            'or',
            'university_name_en' => $keyword,
            'university_name_ar' => $keyword
        ]);

        if(!$model) {
            return [
                "operation" => "error",
                "message" => Yii::t('candidate', "No record found")
            ];
        }

        return [
            "operation" => "success",
            "message" => Yii::t('candidate', "Record found")
        ];
    }
}
