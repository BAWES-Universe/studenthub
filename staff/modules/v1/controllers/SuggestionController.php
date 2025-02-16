<?php

namespace staff\modules\v1\controllers;


use Yii;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use staff\models\Suggestion;
use staff\models\Candidate;
use staff\models\Fulltimer;
use staff\models\Note;
use staff\models\Request;
use staff\models\Story;
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
     * Return a List of Suggestion s available.
     * @return ActiveDataProvider
     */
    public function actionList()
    {
        $request_uuid = Yii::$app->request->get("request_uuid");
        $fulltimer_uuid = Yii::$app->request->get("fulltimer_uuid");
        $candidate_id = Yii::$app->request->get("candidate_id");
        $story_uuid = Yii::$app->request->get("story_uuid");
        $status = Yii::$app->request->get("status");
        $withPagination = Yii::$app->request->get("withPagination");

        $query = Suggestion::find()
            ->joinWith(['fulltimer', 'candidate'])
            ->andWhere([
                'or',
                'candidate.candidate_id is not null',
                'fulltimer.fulltimer_uuid is not null'
            ])
            ->orderBy('suggestion_datetime DESC');

        if($request_uuid) {
            $query->andWhere(['request_uuid' => $request_uuid]);
        }

        if($fulltimer_uuid) {
            $query->andWhere(['fulltimer.fulltimer_uuid' => $fulltimer_uuid]);
        }

        if($candidate_id) {
            $query->andWhere(['candidate.candidate_id' => $candidate_id]);
        }

        if($status > 1) {
            $query->andWhere(['suggestion_status' => $status]);
        }


        if($story_uuid) {
            $query->andWhere(['suggestion.story_uuid' => $story_uuid]);
        }

        if($withPagination)
        {
            return new ActiveDataProvider([
                'query' => $query,
            ]);
        }
        else {
            return new ActiveDataProvider([
                'query' => $query,
                'pagination' => false
            ]);
        }
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
     * Create a Suggestion 
     * @return array
     */
    public function actionCreate()
    {   
        $suggestion = Yii::$app->request->getBodyParam("suggestion");
        $story_uuid = Yii::$app->request->getBodyParam("story_uuid");
        $request_uuid = Yii::$app->request->getBodyParam("request_uuid");
        $fulltimer_uuid = Yii::$app->request->getBodyParam("fulltimer_uuid");
        $candidate_id = Yii::$app->request->getBodyParam("candidate_id");

        $story = $story_uuid? Story::findOne([
            'request_uuid' => $request_uuid,
            'story_uuid' => $story_uuid,
            'story_status' => Story::STATUS_STARTED,
            'staff_id' => Yii::$app->user->getId ()
        ]): Story::findOne([
            'request_uuid' => $request_uuid,
            'story_status' => Story::STATUS_STARTED,
            'staff_id' => Yii::$app->user->getId ()
        ]);

        if(!$story) {
            return [
                "operation" => "error",
                "message" => 'You need to start story on selected request'
            ];
        }

        $request = Request::findOne(['request_uuid' => $request_uuid]);

        // only check if candidate is rejected case

//        $exist = $story->getSuggestions()->andWhere(
//            ['or',
//                ['suggestion_status'=>Suggestion::TYPE_SUGGESTED],
//                ['suggestion_status'=>Suggestion::TYPE_ACCEPTED]
//            ]
//        )->exists();
//
//        if ($exist) {
//            return [
//                "operation" => "error",
//                "message" => 'Candidate Already suggested. only one candidate suggestion allowed per story',
//            ];
//        }

        //$transaction = Yii::$app->db->beginTransaction();

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
            //$transaction->rollBack();

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
        $model->story_uuid = $story->story_uuid;
        $model->suggestion_status = Suggestion::TYPE_SUGGESTED;

        if (!$model->save())
        {
            //$transaction->rollBack();

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

        //$transaction->commit();

        Note::updateAll(['suggestion_uuid' => $model->suggestion_uuid], [
            'note_uuid' => $note->note_uuid
        ]);

        if ($candidate_id) {
            $suggestions = Candidate::findOne($candidate_id)->getSuggestion()->count();
        } else if ($fulltimer_uuid) {
            $suggestions = Fulltimer::findOne($fulltimer_uuid)->getSuggestion()->count();
        }

        return [
            "operation" => "success",
            "message" => "Candidate Suggested successfully",
            "suggestionCount" => $suggestions
        ];
    }

    /**
     * @param $id
     * @return array|string[]
     * @throws NotFoundHttpException
     * @throws \yii\db\Exception
     */
    public function actionRescheduleCvEmail($id)
    {
        $model = $this->findModel($id);

        $model->mail_to_company = false;

        if (!$model->save()) {
            return [
                "operation" => "error",
                "message" => $model->errors
            ];
        }

        return [
            "operation" => "success",
            "message" => "CV Email rescheduled successfully"
        ];
    }

    /**
     * accept a Suggestion 
     * @return array
     */
    public function actionAccept($id)
    {   
        $reason = Yii::$app->request->getBodyParam("reason");
        
        $model = $this->findModel($id);

        //$transaction = Yii::$app->db->beginTransaction();

        $note = new Note;
        $note->request_uuid = $model->request_uuid;
        $note->company_id = $model->request->company_id;
        $note->candidate_id = $model->candidate_id;
        $note->fulltimer_uuid = $model->fulltimer_uuid;
        $note->suggestion_uuid = $model->suggestion_uuid;
        $note->note_type = Note::TYPE_ACCEPTED;
        $note->note_text = $reason;

        if(!$note->save()) 
        {
            //$transaction->rollBack();

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

        $model->suggestion_status = Suggestion::TYPE_ACCEPTED;

        if (!$model->save())
        {
            //$transaction->rollBack();

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

        //$transaction->commit();

        return [
            "operation" => "success",
            "message" => "Suggestion marked as accepted successfully"
        ];
    }

    /**
     * reject a Suggestion 
     * @return array
     */
    public function actionReject($id)
    {   
        $reason = Yii::$app->request->getBodyParam("reason");
        
        $model = $this->findModel($id);

        //$transaction = Yii::$app->db->beginTransaction();

        $note = new Note;
        $note->request_uuid = $model->request_uuid;
        $note->company_id = $model->request->company_id;
        $note->candidate_id = $model->candidate_id;
        $note->fulltimer_uuid = $model->fulltimer_uuid;
        $note->suggestion_uuid = $model->suggestion_uuid;
        $note->note_type = Note::TYPE_REJECTED;
        $note->note_text = $reason;

        if(!$note->save()) 
        {
            //$transaction->rollBack();

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

        $model->suggestion_status = Suggestion::TYPE_REJECTED;

        if (!$model->save())
        {
            //$transaction->rollBack();

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

        //$transaction->commit();

        return [
            "operation" => "success",
            "message" => "Suggestion marked as rejected successfully"
        ];
    }

    /**
     * Delete an 
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
