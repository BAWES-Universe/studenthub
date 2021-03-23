<?php

namespace candidate\modules\v1\controllers;


use staff\models\Note;
use Yii;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use candidate\models\Invitation;
use yii\filters\Cors;
use yii\filters\auth\HttpBearerAuth;
use yii\web\NotFoundHttpException;


/**
 * Invitation controller - Manage Invitation as Candidate
 */
class InvitationController extends Controller
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
     * Return a List of Invitation
     * @return ActiveDataProvider
     */
    public function actionList()
    {
        $count = Yii::$app->request->get("count");

        $query = Yii::$app->user->identity->getInvitations()
            ->orderBy('invitation_created_at DESC');

        if ($count) {
            $query->andWhere(['invitation_status' => Invitation::STATUS_INVITED]);
            return $query->count();
        }

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => false
        ]);
    }

    /**
     * load Invitationn details
     * @param $id
     * @return Invitationn
     * @throws NotFoundHttpException
     */
    public function actionView($id)
    {
        return $this->findModel($id);
    }

    /**
     * accept a Invitation
     * @return array
     */
    public function actionAccept($id)
    {
        $reason = Yii::$app->request->getBodyParam("reason");

        $model = $this->findModel($id);

        $transaction = Yii::$app->db->beginTransaction();

        $note = new Note;
        $note->request_uuid = $model->request_uuid;
        $note->company_id = $model->request->company_id;
        $note->candidate_id = Yii::$app->user->getId();
        $note->invitation_uuid = $model->invitation_uuid;
        $note->note_type = Note::TYPE_INVITATION_ACCEPTED;
        $note->note_text = $reason;

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

        $model->invitation_status = Invitation::STATUS_ACCEPTED;

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
                    "message" => "We've faced a problem updating the Invitation, please contact us for assistance."
                ];
            }
        }

        $transaction->commit();

        return [
            "operation" => "success",
            "message" => "Invitation marked as accepted successfully"
        ];
    }

    /**
     * reject a Invitation
     * @return array
     */
    public function actionReject($id)
    {
        $reason = Yii::$app->request->getBodyParam("reason");

        $model = $this->findModel($id);

        $transaction = Yii::$app->db->beginTransaction();

        $note = new Note;
        $note->request_uuid = $model->request_uuid;
        $note->company_id = $model->request->company_id;
        $note->candidate_id = $model->candidate_id;
        $note->invitation_uuid = $model->invitation_uuid;
        $note->note_type = Note::TYPE_INVITATION_REJECTED;
        $note->note_text = $reason;

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

        $model->invitation_status = Invitation::STATUS_REJECTED;

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
                    "message" => "We've faced a problem updating the Invitation, please contact us for assistance."
                ];
            }
        }

        $transaction->commit();

        return [
            "operation" => "success",
            "message" => "Invitation marked as rejected successfully"
        ];
    }

    /**
     * Finds the Invitation model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Invitation the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        $model = Yii::$app->user->identity->getInvitations()
            ->andWhere (['invitation_uuid' => $id])
            ->one();

        if ($model !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
