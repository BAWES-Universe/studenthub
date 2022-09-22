<?php


namespace common\components;

use understeam\jira;
use yii\httpclient\Client;


class JiraComponent extends jira\Client
{
    public $jiraUrl;
    public $email;
    public $apiToken;

    /**
     * @inheritdoc
     */
    public function init()
    {
        foreach (['jiraUrl', 'email', 'apiToken'] as $attribute) {
            if ($this->$attribute === null) {
                throw new yii\base\InvalidConfigException(strtr('"{class}::{attribute}" cannot be empty.', [
                    '{class}' => static::className(),
                    '{attribute}' => '$' . $attribute
                ]));
            }
        }

        parent::init();
    }

    public function getApiEndpointUrl()
    {
        return rtrim($this->jiraUrl, '/') . '/rest/api/3/';
    }

    public function request($method, $path, $body = [])
    {
        $url = $this->getUrlOfPath($path);

        /*if (is_array($body) && !empty($body)) {
            $body = Json::encode($body);
        }

        $cacheKey = md5($method . $url. $body);

        $result = Yii::$app->cache->get($cacheKey);

        if ($result !== false)
        {
            return $result;
        }*/

            $authString = base64_encode($this->email . ':' . $this->apiToken);
            //$authString = base64_encode($this->username . ':' . $this->password);
            //$request->addHeader("Authorization", "Basic " . $authString);

            $client = new Client();

            return $client->createRequest()
                ->setUrl($url)
                ->setMethod($method)
                ->addHeaders([
                    'authorization' => 'Bearer '.$authString,
                    'content-type' => 'application/json',
                    "Accept" => "application/json"
                    //"Upgrade" => "HTTP/2.0, SHTTP/1.3, IRC/6.9, RTA/x11",
                    //"HTTP/2.0"
                ])
                ->send();

        //Yii::$app->cache->set($cacheKey, $result, $this->cacheDuration);

    }
}