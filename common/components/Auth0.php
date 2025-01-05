<?php

namespace common\components;

use yii\base\Component;
use yii\httpclient\Client;


class Auth0 extends Component
{
    private $apiEndpoint = "https://bawes.us.auth0.com";
    private $client_id = "sDIOpy1be7Y59ocKoXxHVL5euFNdJN3e";
    private $connection = "Username-Password-Authentication";

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
                    '{class}' => static::class,
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

    /**
     * @param $userData
     * @return \yii\httpclient\Response
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\httpclient\Exception
     */
    public function createUser($userData) {
        $url = $this->apiEndpoint."/dbconnections/signup";

        $client = new Client();

        $response = $client->createRequest()
            ->setUrl($url)
            ->setMethod('POST')
            ->setData(array_merge($userData,['client_id'=>$this->client_id,'connection'=>$this->connection]))
            ->send();

        return $response;
    }
}
