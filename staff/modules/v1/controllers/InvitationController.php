<?php

namespace staff\modules\v1\controllers;

use staff\models\Candidate;
use Yii;
use yii\db\Expression;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use staff\models\Invitation;
use staff\models\Request;
use yii\filters\Cors;
use yii\filters\auth\HttpBearerAuth;
use yii\web\NotFoundHttpException;


/**
 * Invitation controller - Manage Invitation as Staff
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
        $request_uuid = Yii::$app->request->get("request_uuid");
        $candidate_id = Yii::$app->request->get("candidate_id");
        $status = Yii::$app->request->get("status");

        $query = Invitation::find()
            ->joinWith(['candidate'])
            ->andWhere(new Expression('candidate.candidate_id is not null'))
            ->orderBy('invitation_created_at DESC');

        if($request_uuid) {
            $query->andWhere(['request_uuid' => $request_uuid]);
        }

        if($candidate_id) {
            $query->andWhere(['candidate.candidate_id' => $candidate_id]);
        }

        if($status > 1) {
            $query->andWhere(['invitation_status' => $status]);
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
     * Create a Suggestion
     * @return array
     */
    public function actionCreate()
    {
        $request_uuid = Yii::$app->request->getBodyParam("request_uuid");
        $candidate_id = Yii::$app->request->getBodyParam("candidate_id");

        $request = Request::findOne(['request_uuid' => $request_uuid]);

        if(!$request) {
            return [
                "operation" => "error",
                "message" => 'Invalid Request ID'
            ];
        }

        $model = new Invitation();
        $model->request_uuid = $request_uuid;
        $model->candidate_id = $candidate_id;
        $model->invitation_status = Invitation::STATUS_INVITED;

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
                    "message" => "We've faced a problem creating the Invitation, please contact us for assistance."
                ];
            }
        }

        $invitedCount = Candidate::findOne($candidate_id)
            ->getInvitations()
            ->filterInvited()
            ->count();

        return [
            "operation" => "success",
            "message" => "Candidate invited successfully",
            "invitedCount" => $invitedCount
        ];
    }

    /**
     * Delete an invitation
     * @param  integer $id
     * @return array
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);

        $model->delete();

        return [
            "operation" => "success",
            "message" => "Invitation deleted successfully"
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
        if (($model = Invitation::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
