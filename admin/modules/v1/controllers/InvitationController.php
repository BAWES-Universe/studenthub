<?php

namespace admin\modules\v1\controllers;

use admin\models\Invitation;
use Yii;
use yii\db\Expression;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use yii\filters\Cors;
use yii\filters\auth\HttpBearerAuth;
use yii\web\NotFoundHttpException;


/**
 * Invitations controller - Manage brand as Admin
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
     * Return a List of Brand Accounts available.
     * @return ActiveDataProvider
     */
    public function actionList()
    {
        $currency = Yii::$app->request->headers->get("Currency", "KWD");

        $start_date = Yii::$app->request->get('start_date', null);
        $end_date = Yii::$app->request->get('end_date', null);
        $request_uuid = Yii::$app->request->get('request_uuid', null);
        $story_uuid = Yii::$app->request->get('story_uuid', null);
        $staff_id = Yii::$app->request->get('staff_id', null);
        $invitation_status = Yii::$app->request->get('invitation_status', null);

        $query = Invitation::find()
            ->orderBy('invitation_created_at DESC');

        if(!$request_uuid && !$story_uuid && $currency) {
            $query->joinWith(['company'])
                ->andWhere(['company.currency_code' => $currency]);
        }

        if ($request_uuid) {
            $query->filterRequest($request_uuid);
        }

        if ($story_uuid) {
            $query->filterStory($story_uuid);
        }

        if ($invitation_status) {
            $query->andWhere(['invitation_status' => $invitation_status]);
        }

        if ($staff_id) {
            $query->andWhere(['invitation_created_by_staff' => $staff_id]);
        }

        if($start_date) {
            $query->andWhere(new Expression("DATE(invitation_created_at) >= DATE('".
                date('Y-m-d', strtotime ($start_date)) ."')"));
        }

        if($end_date) {
            $query->andWhere(new Expression("DATE(invitation_created_at) <= DATE('".
                date('Y-m-d', strtotime ($end_date))."')"));
        }

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * Finds the Brand model based on its primary key value.
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
