<?php

namespace staff\modules\v1\controllers;

use common\models\EmailCampaignFilter;
use common\models\EmailCampaign;
use Yii;
use yii\helpers\ArrayHelper;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use yii\filters\Cors;
use yii\filters\auth\HttpBearerAuth;
use yii\web\NotFoundHttpException;


/**
 * EmailCampaign controller - Manage EmailCampaign as Admin
 */
class EmailCampaignController extends Controller
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
     * Return a List of EmailCampaign available.
     * @return ActiveDataProvider
     */
    public function actionList()
    {
        $query = EmailCampaign::find();

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    public function actionStatusList()
    {
        $campaignIDs = Yii::$app->request->getBodyParam('campaignIDs');

        $query = EmailCampaign::find()
            ->andWhere(['NOT IN', 'status', [EmailCampaign::STATUS_DRAFT]]);

        if($campaignIDs) {
            $query->andWhere(['IN', 'campaign_uuid', $campaignIDs]);
        }

        return ArrayHelper::index($query->all(), 'campaign_uuid');
    }

    /**
     * load details
     * @param type $id
     * @return type
     */
    public function actionView($id)
    {
        return $this->findModel($id);
    }

    /**
     * start campaign
     * @param $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionRun($id)
    {
        $model = $this->findModel($id);
        $model->status = EmailCampaign::STATUS_READY;

        if (!$model->save()) {
            return [
                "operation" => "errors",
                "message" => $model->errors
            ];
        }

        return [
            "operation" => "success",
            "message" => "Campaign started"
        ];
    }

    /**
     * Create a bank account
     * @return array
     */
    public function actionCreate()
    {
        // Attempt to create new bank
        $model = new EmailCampaign();

        $model->subject = Yii::$app->request->getBodyParam("subject");
        $model->message = Yii::$app->request->getBodyParam("message");

        $model->trigger_date_time = Yii::$app->request->getBodyParam("trigger_date_time");

        if ($model->trigger_date_time) {
            $model->trigger_date_time = date('Y-m-d H:i:s', strtotime($model->trigger_date_time));
        }

       // $model->last_trigger_date_time = Yii::$app->request->getBodyParam("last_trigger_date_time");
        $model->is_recurring = Yii::$app->request->getBodyParam("is_recurring");
        $model->trigger_period = Yii::$app->request->getBodyParam("trigger_period");
        $model->target = Yii::$app->request->getBodyParam("target");

        $model->status = EmailCampaign::STATUS_DRAFT;


        if (!$model->save()) {
            if (isset($model->errors)) {
                return [
                    "operation" => "error",
                    "message" => $model->errors,
                    "values" => $model->attributes
                ];
            } else {
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem creating the email campaign, please contact us for assistance."
                ];
            }
        }

        $campaignFilters = Yii::$app->request->post('emailCampaignFilters');

        foreach ($campaignFilters as $key => $campaignFilter) {

            if(!$campaignFilter['param'] || strlen($campaignFilter['param']) == 0) {
                continue;
            }

            $cf = new EmailCampaignFilter();
            $cf->campaign_uuid = $model->campaign_uuid;
            $cf->param = $campaignFilter['param'];
            $cf->value = $campaignFilter['value'];

            if (!$cf->save()) { 
                break;
            }
        }

        Yii::info('[Email Campaign Added: ' . $model->subject . '] By ' . Yii::$app->user->identity->staff_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Email campaign created successfully"
        ];
    }

    /**
     * Create a bank account
     * @param $id
     * @return array
     */
    public function actionUpdate($id)
    {
        // Attempt to create new account
        $model = $this->findModel($id);

        $model->subject = Yii::$app->request->getBodyParam("subject");
        $model->message = Yii::$app->request->getBodyParam("message");

        $model->trigger_date_time = Yii::$app->request->getBodyParam("trigger_date_time");

        if ($model->trigger_date_time) {
            $model->trigger_date_time = date('Y-m-d H:i:s', strtotime($model->trigger_date_time));
        }

        //$model->last_trigger_date_time = Yii::$app->request->getBodyParam("last_trigger_date_time");
        $model->is_recurring = Yii::$app->request->getBodyParam("is_recurring");
        $model->trigger_period = Yii::$app->request->getBodyParam("trigger_period");
        $model->target = Yii::$app->request->getBodyParam("target");

        if (!$model->save()) {
            if (isset($model->errors)) {
                return [
                    "operation" => "error",
                    "message" => $model->errors
                ];
            } else {
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem updating the campaign, please contact us for assistance."
                ];
            }
        }

        $campaignFilters = Yii::$app->request->post('emailCampaignFilters');

        if(!$campaignFilters) {
            $campaignFilters = [];
        }

        $cf_uuids = [];

        foreach ($campaignFilters as $key => $campaignFilter) {

            if(!$campaignFilter['param'] || strlen($campaignFilter['param']) == 0) {
                continue;
            }

            if(empty($campaignFilter['cf_uuid'])) {
                $cf = new EmailCampaignFilter();
            } else {
                $cf = EmailCampaignFilter::find()
                    ->andWhere(['cf_uuid' => $campaignFilter['cf_uuid']])
                    ->one();
            }

            $cf->campaign_uuid = $model->campaign_uuid;
            $cf->param = $campaignFilter['param'];
            $cf->value = $campaignFilter['value'];

            if (!$cf->save()) {
                break;
            }

            $cf_uuids[] = $cf->cf_uuid;
        }

        EmailCampaignFilter::deleteAll([
            'AND',
            ['NOT IN', 'cf_uuid', $cf_uuids],
            ['campaign_uuid' => $id]
        ]);

        Yii::info('[Email Campaign Updated: ' . $model->subject . '] By ' . Yii::$app->user->identity->staff_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Email campaign successfully updated"
        ];
    }

    /**
     * Delete an account
     * @param integer $id
     * @return array
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);

        Yii::info('[Email Campaign Deleted: ' . $model->subject . '] By ' . Yii::$app->user->identity->staff_name, __METHOD__);

        if(!$model->delete()) {
            return [
                "operation" => "error",
                "message" => $model->errors
            ];
        }

        return [
            "operation" => "success",
            "message" => "Email campaign deleted successfully"
        ];
    }

    /**
     * Finds the Email Campaign model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return EmailCampaign the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = EmailCampaign::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
