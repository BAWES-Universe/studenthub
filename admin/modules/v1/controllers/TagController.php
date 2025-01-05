<?php

namespace admin\modules\v1\controllers;

use Yii;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use common\models\Tag;
use yii\filters\Cors;
use yii\filters\auth\HttpBearerAuth;
use yii\web\NotFoundHttpException;


/**
 * Tag controller - Manage tag as Admin
 */
class TagController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // remove authentication filter for cors to work
        unset($behaviors['authenticator']);

        // Allow XHR Requests from our different subdomains and dev machines
        $behaviors['corsFilter'] = [
            'class' => Cors::class,
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
            'class' => HttpBearerAuth::class,
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
     * Return a List of Tag Accounts available.
     * @return ActiveDataProvider
     */
    public function actionList()
    {
        $query = Tag::find();

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * load tag details
     * @param type $id
     * @return type
     */
    public function actionView($id)
    {
        return $this->findModel($id);
    }

    /**
     * Create a tag account
     * @return array
     */
    public function actionCreate()
    {
        // Attempt to create new tag
        $model = new Tag();

        $model->tag = Yii::$app->request->getBodyParam("tag");

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
                    "message" => "We've faced a problem creating the tag, please contact us for assistance."
                ];
            }
        }

        Yii::info('[Tag Added: '.$model->tag.'] By '.Yii::$app->user->identity->admin_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Tag created successfully"
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    /**
     * Create a tag account
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
                "message" => "Tag not found."
            ];
        }

        $model->tag = Yii::$app->request->getBodyParam("tag");

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
                    "message" => "We've faced a problem updating the tag, please contact us for assistance."
                ];
            }
        }

        Yii::info('[Tag Updated: '.$model->tag.'] By '.Yii::$app->user->identity->admin_name, __METHOD__);


        return [
            "operation" => "success",
            "message" => "Tag successfully updated"
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
        $tag = $this->findModel((int)$id);
        
        if(!$tag->delete()) {
            return [
                "operation" => "error",
                "message" => $tag->getErrors()
            ];
        }
 
        Yii::info('[Tag Deleted: '.$tag->tag.'] By '.Yii::$app->user->identity->admin_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Tag deleted successfully"
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    /**
     * Finds the Tag model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Transfer the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Tag::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
