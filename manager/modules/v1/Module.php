<?php

namespace manager\modules\v1;

use Yii;
use common\models\BlockedIp;

/**
 * v1 module definition class
 */
class Module extends \yii\base\Module
{
    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'manager\modules\v1\controllers';

    /**
     * @inheritdoc
     */
    public function init()
    {
        $lang = \Yii::$app->request->headers->get('language');

        if ($lang && $lang != \Yii::$app->language)
        {
            \Yii::$app->language = $lang;
        }

        parent::init();

        if(YII_ENV == 'prod') {

            if(!Yii::$app->user->isGuest)
            {
                $user = Yii::$app->user->identity;

                Yii::$app->eventManager->setUser(Yii::$app->user->getId(), [
                    "name" => $user->name,
                    "email" => $user->email
                ]);
            }
        }

        if($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            return true;
        }

        // Get initial IP address of requester
        $ip = Yii::$app->request->getRemoteIP();


        // Check if request is forwarded via load balancer or cloudfront on behalf of user
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $forwardedFor = $_SERVER['HTTP_X_FORWARDED_FOR'];

            // as "X-Forwarded-For" is usually a list of IP addresses that have routed
            if ($forwardedFor) {
                $IParray = array_values(array_filter(explode(',', $forwardedFor)));

                // Get the first ip from forwarded array to get original requester
                if ($IParray) {
                    $ip = $IParray[0];
                }
            }
        }

        //check if ip is blocked

        $isBlocked = BlockedIp::find()->andWhere(['ip_address' => $ip])->exists();

        if($isBlocked) {
            header('Access-Control-Allow-Origin: *');

            //header('Access-Control-Request-Method': 'GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', '');
            //header('Access-Control-Request-Headers' => ['*'],
            //header('Access-Control-Allow-Credentials' => null,
            /*header('Access-Control-Allow-Methods: POST, GET, DELETE, PUT, PATCH, OPTIONS');
            header('Access-Control-Allow-Headers: token, Content-Type');
            header('Access-Control-Max-Age: 1728000');
            header('Content-Length: 0');
            header('Content-Type: text/plain');*/
            throw new \yii\web\HttpException(403, 'ILLEGAL USAGE');
        }
    }
}
