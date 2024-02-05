<?php

namespace status\modules\v1;

use Yii;
use Segment\Segment;

/**
 * v1 module definition class
 */
class Module extends \yii\base\Module
{
    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'status\modules\v1\controllers';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if(YII_ENV == 'prod') {

            if(!Yii::$app->user->isGuest)
            {
                $user = Yii::$app->user->identity;

                Yii::$app->eventManager->setUser(Yii::$app->user->getId(), [
                    "name" => $user->status_name,
                    "email" => $user->status_email
                ]);
            }
        }

        // Check if request is forwarded via load balancer or cloudfront on behalf of user
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $forwardedFor = $_SERVER['HTTP_X_FORWARDED_FOR'];

            // as "X-Forwarded-For" is usually a list of IP addresses that have routed
            $IParray = array_values(array_filter(explode(',', $forwardedFor)));

            // Get the first ip from forwarded array to get original requester
            $ip = $IParray[0];
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
