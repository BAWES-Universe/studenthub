<?php

namespace common\components;

use yii\base\Component;
use yii\httpclient\Client;


class Auth0 extends Component
{
    private $apiEndpoint = "https://bawes.us.auth0.com";

    /**
     * @inheritdoc
     */
    public function init() {

        // Fields required by default
        $requiredAttributes = [];

        // Process Validation
        foreach ($requiredAttributes as $attribute) {
            if ($this->$attribute === null) {
                throw new InvalidConfigException(strtr('"{class}::{attribute}" cannot be empty.', [
                    '{class}' => static::className(),
                    '{attribute}' => '$' . $attribute
                ]));
            }
        }

        parent::init();
    }

    /**
     * return user info
     * @param $accessToken
     * @return mixed
     */
    public function getUserInfo($accessToken) {

        $url = $this->apiEndpoint."/userinfo";

        $client = new Client();
        $response = $client->createRequest()
            ->setUrl($url)
            ->addHeaders([
                'authorization' => 'Bearer '.$accessToken,
                'content-type' => 'application/json',
            ])
            ->send();

        return $response;
    }
}