<?php

namespace common\components;

use Yii;
use yii\httpclient\Client;

class ReCaptcha
{
    public $secretKey;

    /**
     * @inheritdoc
     */
    public function init()
    {
        foreach (['secretKey'] as $attribute) {
            if ($this->$attribute === null) {
                throw new yii\base\InvalidConfigException(strtr('"{class}::{attribute}" cannot be empty.', [
                    '{class}' => static::className(),
                    '{attribute}' => '$' . $attribute
                ]));
            }
        }

        //parent::init();
    }

    public function verify($token) {

        // Get initial IP address of requester
        $ip = Yii::$app->request->getRemoteIP();

        // Check if request is forwarded via load balancer or cloudfront on behalf of user
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $forwardedFor = $_SERVER['HTTP_X_FORWARDED_FOR'];

            // as "X-Forwarded-For" is usually a list of IP addresses that have routed
            $IParray = array_values(array_filter(explode(',', $forwardedFor)));

            // Get the first ip from forwarded array to get original requester
            $ip = $IParray[0];
        }

        $data = [
            "secret" => $this->secretKey,
            "response" => $token,
            "remoteip" => $ip
        ];

        $client = new Client();

        return $client->createRequest()
            ->setMethod('POST')
            ->setUrl("https://www.google.com/recaptcha/api/siteverify")
            //->setFormat(Client::FORMAT_JSON)
            ->setData($data)
            ->send();
    }
}