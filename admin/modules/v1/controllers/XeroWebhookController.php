<?php

namespace admin\modules\v1\controllers;

use Yii;
use yii\filters\Cors;
use yii\rest\Controller;
use yii\web\UnauthorizedHttpException;

class XeroWebhookController extends Controller
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
                'Access-Control-Expose-Headers' => [],
            ],
        ];

        // Basic Auth accepts Base64 encoded username/password and decodes it for you
        $behaviors['authenticator'] = null;

        return $behaviors;
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        $actions = parent::actions();

        // Return Header explaining what options are available for next request
        $actions['options'] = [
            'class' => 'yii\rest\OptionsAction',
            // optional:
            'collectionOptions' => ['GET', 'POST', 'HEAD', 'OPTIONS'],
            'resourceOptions' => ['GET', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
        ];
        return $actions;
    }

    /**
     * @param $action
     * @return bool
     * @throws \yii\web\BadRequestHttpException
     */
    public function beforeAction($action)
    {
        if(!parent::beforeAction($action)) {
            return false;
        }

        $key = getenv('XERO_WEBHOOK_SIGNING_KEY') ?: '';
        if ($key === '') {
            Yii::error('XERO_WEBHOOK_SIGNING_KEY is not configured.', __METHOD__);
            throw new UnauthorizedHttpException('Webhook signature validation is not configured.');
        }

        // Get the provided signature from the request headers
        $provided_signature = Yii::$app->request->headers->get("x-xero-signature"); // $_SERVER['HTTP_X_Xero_Signature'];

        // Get the request data
        $request_data = Yii::$app->request->rawBody;

        // Calculate the HMAC signature
        $generated_signature = base64_encode(hash_hmac('sha256', $request_data, $key, true));

        // Compare the provided signature with the generated one
        if (!$provided_signature || !hash_equals($generated_signature, $provided_signature)) {
            throw new UnauthorizedHttpException('Signature mismatch.');
        } /*else {
            // Signature matched
            http_response_code(200);
            echo "Signature matched";
        }*/

        return true;
    }

    /**
     * @return void
     */
    public function actionIncomming()
    {
        $events = Yii::$app->request->getBodyParam("events");

        foreach ($events as $event) {

            //todo: get data from Xero, Need custom connection but that not supported for Kuwait organsation in xero

            //"resourceUrl": "https://api.xero.com/api.xro/2.0/Contacts/717f2bfc-c6d4-41fd-b238-3f2f0c0cf777",

            $data = Yii::$app->xero->getResource($event['resourceUrl']);

            Yii::$app->eventManager->track ($event['eventCategory'] . " " . $event['eventType'], $data);
        }
    }
}
