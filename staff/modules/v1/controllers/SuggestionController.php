<?php

namespace staff\modules\v1\controllers;

use Yii;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use common\models\Suggestion;
use common\models\Note;
use common\models\Request;
use yii\filters\Cors;
use yii\filters\auth\HttpBearerAuth;
use yii\web\NotFoundHttpException;


/**
 * Suggestion controller - Manage Suggestion as Admin
 */
class SuggestionController extends Controller
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
     * Return a List of Suggestion Accounts available.
     * @return ActiveDataProvider
     */
    public function actionList()
    {
        $query = Suggestion::find();

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * load Suggestion details
     * @param $id
     * @return Suggestion
     * @throws NotFoundHttpException
     */
    public function actionView($id)
    {
        return $this->findModel($id);
    }
    
    /**
     * Create a Suggestion account
     * @return array
     */
    public function actionCreate()
    {   
        $suggestion = Yii::$app->request->getBodyParam("suggestion");
        $request_uuid = Yii::$app->request->getBodyParam("request_uuid");
        $fulltimer_uuid = Yii::$app->request->getBodyParam("fulltimer_uuid");
        $candidate_id = Yii::$app->request->getBodyParam("candidate_id");

        $request = Request::findOne(['request_uuid' => $request_uuid]);

        if(!$request) {
            return [
                "operation" => "error",
                "message" => 'Invalid Request ID'
            ];
        } 

        $transaction = Yii::$app->db->beginTransaction();

        //create a "Note" of type "suggested"

        $note = new Note;
        $note->company_id = $request->company_id;
        $note->candidate_id = $candidate_id;
        $note->request_uuid = $request_uuid;
        $note->fulltimer_uuid = $fulltimer_uuid;
        $note->note_type = Note::TYPE_SUGGESTED;
        $note->note_text = $suggestion;

        if(!$note->save()) 
        {
            $transaction->rollBack();

            if(isset($note->errors)){
                return [
                    "operation" => "error",
                    "message" => $note->errors
                ];
            }else{
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem creating the Note, please contact us for assistance."
                ];
            }
        }

        //a related "Suggestion" record (type suggested)

        $model = new Suggestion();
        $model->request_uuid = $request_uuid;
        $model->fulltimer_uuid = $fulltimer_uuid;
        $model->candidate_id = $candidate_id;
        $model->note_uuid = $note->note_uuid;

        if (!$model->save())
        {
            $transaction->rollBack();

            if(isset($model->errors)){
                return [
                    "operation" => "error",
                    "message" => $model->errors
                ];
            }else{
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem creating the Suggestion, please contact us for assistance."
                ];
            }
        }

        $transaction->commit();

        return [
            "operation" => "success",
            "message" => "Suggestion created successfully"
        ];
    }

    /**
     * Delete an account
     * @param  integer $id
     * @return array
     */
    public function actionDelete($id)
    {
        $suggestion = $this->findModel($id);

        if(!$suggestion) {
            return [
                "operation" => "error",
                "message" => "Suggestion not found or already deleted"
            ];
        }

        // Delete suggestion
        $suggestion->delete();

        return [
            "operation" => "success",
            "message" => "Suggestion deleted successfully"
        ];
    }
    
    /**
     * Finds the Suggestion model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Suggestion the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Suggestion::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
