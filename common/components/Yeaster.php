<?php

namespace common\components;

use yii\base\InvalidConfigException;
use yii\httpclient\Client;

/**
 * component to connect to voice-mail microservice - node.js microservice that sync voice-mails by running
 * cron in background
 * node /var/www/studenthub-microservices/yeastar-voicemails/console/sync.js
 * node /var/www/studenthub-microservices/yeastar-voicemails/console/process.js
 */
class Yeaster extends \yii\base\Component
{
    public $microserviceApiKey;

    //point to microservice handling voicemails, overriding from main-local.php
    public $apiEndpoint = "http://localhost:3001";

    private function authorizationHeaders()
    {
        if (trim((string) $this->microserviceApiKey) === '') {
            throw new InvalidConfigException('YEASTER_MICROSERVICE_API_KEY must be configured before calling the voicemail microservice.');
        }

        return [
            'Authorization' => 'Bearer ' . $this->microserviceApiKey,
            'content-type' => 'application/json',
        ];
    }

    public function listVoicemails($page, $limit = 10) {

        $client = new Client();
        $response = $client->createRequest()
            ->setMethod('GET')
            ->setUrl($this->apiEndpoint . '/list?page=' . $page . '&limit=' . $limit)
            ->setFormat(Client::FORMAT_JSON)
            ->addHeaders($this->authorizationHeaders())
            ->send();

        return $response->getData();
    }

    public function viewVoicemail($id) {

        $client = new Client();
        $response = $client->createRequest()
            ->setMethod('GET')
            ->setUrl($this->apiEndpoint . '/view/'. $id)
            ->setFormat(Client::FORMAT_JSON)
            ->addHeaders($this->authorizationHeaders())
            ->send();

        return $response->content;
    }

    public function downloadVoicemail($id) {

        $client = new Client();
        $response = $client->createRequest()
            ->setMethod('GET')
            ->setUrl($this->apiEndpoint . '/download/'. $id)
            ->setFormat(Client::FORMAT_JSON)
            ->addHeaders($this->authorizationHeaders())
            ->send();

        return $response->content;
    }
}
