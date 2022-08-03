<?php

namespace admin\modules\v1\controllers;

use Yii;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use staff\models\Note;
use yii\filters\Cors;
use yii\filters\auth\HttpBearerAuth;
use yii\web\NotFoundHttpException;


/**
 * Note controller - Manage brand as Admin
 */
class NoteController extends Controller
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
        $query = Note::find()
            ->orderBy('note_created_datetime DESC');

        if (Yii::$app->request->get("company_id")) {
            $query->filterCompany(Yii::$app->request->get("company_id"));
        }

        if (Yii::$app->request->get('staff_id', null)) {
            $query->filterCreatedBy(Yii::$app->request->get('staff_id'));
        }


        if (Yii::$app->request->get('request_uuid', null)) {
            $query->filterRequest(Yii::$app->request->get('request_uuid'));
        }

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * @param $id
     * @return Note
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
        $model = new Note();

        $model->note_text = htmlentities(Yii::$app->request->getBodyParam("note"));
        $model->company_id = Yii::$app->request->getBodyParam("company_id");

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
            "message" => "Note created successfully"
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

        $model->note_text = htmlentities(Yii::$app->request->getBodyParam("note"));

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
            "message" => "Note successfully updated"
        ];
    }

    /**
     * Delete an account
     * @param  integer $id
     * @return array
     */
    public function actionDelete($id)
    {
        $brand = $this->findModel($id);

        if(!$brand) {
            return [
                "operation" => "error",
                "message" => "Note not found or already deleted"
            ];
        }

        // Delete brand
        $brand->delete();

        return [
            "operation" => "success",
            "message" => "Note deleted successfully"
        ];
    }
    
    /**
     * Finds the Brand model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Note the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Note::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
