<?php

namespace admin\modules\v1\controllers;

use common\models\Setting;
use Yii;
use yii\rest\Controller;
use yii\filters\Cors;
use yii\filters\auth\HttpBearerAuth;

/**
 * Setting controller - Manage settings as Admin
 */
class SettingController extends Controller
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

    public function actionList()
    {
        return Setting::find()->all();// Yii::$app->config->data;
    }

    /**
     * Update settings
     * @return mixed
     */
    public function actionUpdate()
    {
        $mixpanel = Yii::$app->request->getBodyParam('Mixpanel-Key');
        $testMixpanel = Yii::$app->request->getBodyParam('Test-Mixpanel-Key');
        $mixpanelStatus = Yii::$app->request->getBodyParam('Mixpanel-Status');

        $testSegment = Yii::$app->request->getBodyParam('Test-Segment-Key');
        $testSegmentWallet = Yii::$app->request->getBodyParam('Test-Segment-Key-Wallet');
        $segment = Yii::$app->request->getBodyParam('Segment-Key');
        $segmentWallet = Yii::$app->request->getBodyParam('Segment-Key-Wallet');
        $segmentStatus = Yii::$app->request->getBodyParam('Segment-Status');

        Setting::setConfig('EventManager', 'Mixpanel-Status', $mixpanelStatus? "enabled": null);
        Setting::setConfig('EventManager', 'Mixpanel-Key', $mixpanel);
        Setting::setConfig('EventManager', 'Test-Mixpanel-Key', $testMixpanel);

        Setting::setConfig('EventManager', 'Segment-Status', $segmentStatus? "enabled": null);
        Setting::setConfig('EventManager', 'Segment-Key', $segment);
        Setting::setConfig('EventManager', 'Segment-Key-Wallet', $segmentWallet);
        Setting::setConfig('EventManager', 'Test-Segment-Key', $testSegment);
        Setting::setConfig('EventManager', 'Test-Segment-Key-Wallet', $testSegmentWallet);

        return [
            "operation" => "success",
            "message" => 'Settings updated successfully'
        ];
    }
}
